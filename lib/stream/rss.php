<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_feeds_stream_rss extends rex_feeds_stream_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_rss_feed');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_rss_url'),
                'name' => 'url',
                'type' => 'string',
            ],
        ];
    }

    public function fetch()
    {
        $client = new \FeedIo\Adapter\Http\Client(new Symfony\Component\HttpClient\HttplugClient());
        $logger = new \Psr\Log\NullLogger();
        $feedIo = new \FeedIo\FeedIo($client, $logger);

        try {
            $result = $feedIo->read($this->typeParams['url']);

            /** @var Item $rssItem */
            foreach ($result->getFeed() as $rssItem) {
                try {
                    // Sichere Extraktion der PublicId
                    $publicId = $rssItem->getPublicId();
                    if (empty($publicId)) {
                        $publicId = uniqid('feed_', true);
                    }
                    
                    $item = new rex_feeds_item($this->streamId, $publicId);

                    // Titel mit Fallback
                    $title = $rssItem->getTitle();
                    $item->setTitle($title ?: '');

                    // Content mit Fallback und speziellem Content:encoded Handling
                    $content = '';
                    
                    // 1. Versuche content:encoded zu bekommen
                    $elements = $rssItem->getAllElements();
                    foreach ($elements as $element) {
                        if ($element->getName() === 'content:encoded') {
                            $content = $element->getValue();
                            break;
                        }
                    }
                    
                    // 2. Fallback auf normalen Content
                    if (empty($content)) {
                        $content = $rssItem->getContent();
                    }
                    
                    // 3. Fallback auf description
                    if (empty($content)) {
                        foreach ($elements as $element) {
                            if ($element->getName() === 'description') {
                                $content = $element->getValue();
                                break;
                            }
                        }
                    }

                    $item->setContentRaw($content ?: '');
                    $item->setContent($content ? strip_tags($content) : '');

                    // URL/Link mit Fallback
                    $link = $rssItem->getLink();
                    $item->setUrl($link ?: '');

                    // Datum mit Null-Check
                    $lastModified = $rssItem->getLastModified();
                    if ($lastModified) {
                        $item->setDate($lastModified);
                    }

                    // Author mit Null-Check
                    $author = $rssItem->getAuthor();
                    $authorName = ($author && method_exists($author, 'getName')) ? $author->getName() : '';
                    $item->setAuthor($authorName);

                    // Media-Handling speziell für rss.app
                    $mediaUrl = null;
                    
                    // 1. Suche nach img-Tags im Content
                    if ($content) {
                        $dom = new DOMDocument();
                        // Suppress warnings for invalid HTML
                        @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        $xpath = new DOMXPath($dom);
                        
                        // Suche nach dem ersten img-Tag
                        $images = $xpath->query('//img');
                        if ($images->length > 0) {
                            // Nehme das erste Bild
                            $mediaUrl = $images->item(0)->getAttribute('src');
                            
                            // Prüfe auf data-src falls src leer ist (lazy loading)
                            if (empty($mediaUrl)) {
                                $mediaUrl = $images->item(0)->getAttribute('data-src');
                            }
                        }
                    }
                    
                    // 2. Fallback auf Media Enclosures
                    if (!$mediaUrl) {
                        $medias = $rssItem->getMedias();
                        if ($medias && !empty($medias)) {
                            foreach ($medias as $media) {
                                if ($media->getUrl()) {
                                    $mediaUrl = $media->getUrl();
                                    break;
                                }
                            }
                        }
                    }

                    // Setze das gefundene Bild
                    if ($mediaUrl) {
                        // Entferne Query-Parameter aus der URL
                        $mediaUrl = preg_replace('/\?.*/', '', $mediaUrl);
                        
                        $item->setMedia($mediaUrl);
                        $item->setMediaSource($mediaUrl);
                        
                        // Debug Log
                        rex_logger::logInfo('RSS Feed Image found', [
                            'url' => $mediaUrl,
                            'title' => $title,
                            'stream_id' => $this->streamId
                        ]);
                    } else {
                        // Debug Log wenn kein Bild gefunden wurde
                        rex_logger::logError('No image found for RSS item', [
                            'title' => $title,
                            'content_length' => strlen($content),
                            'stream_id' => $this->streamId
                        ]);
                    }

                    // Raw-Daten und Debug-Informationen
                    $rawData = [
                        'title' => $title,
                        'content' => $content,
                        'link' => $link,
                        'media_url' => $mediaUrl,
                        'elements' => array_map(function($el) {
                            return [
                                'name' => $el->getName(),
                                'value' => substr($el->getValue(), 0, 100) . '...'
                            ];
                        }, $elements),
                    ];
                    $item->setRaw($rawData);

                    $this->updateCount($item);
                    $item->save();

                } catch (Exception $e) {
                    rex_logger::logException($e);
                    continue;
                }
            }

            self::registerExtensionPoint($this->streamId);

        } catch (Exception $e) {
            rex_logger::logException($e);
            echo rex_view::error($e->getMessage());
        }
    }
}
