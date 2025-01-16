<?php

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
            rex_logger::logError(E_NOTICE, 'Starting feed fetch', ['url' => $this->typeParams['url']]);
            $result = $feedIo->read($this->typeParams['url']);
            
            $feed = $result->getFeed();
            rex_logger::logError(E_NOTICE, 'Feed fetched', [
                'itemCount' => iterator_count($feed)
            ]);

            /** @var Item $rssItem */
            foreach ($result->getFeed() as $rssItem) {
                try {
                    // Sichere Extraktion der PublicId
                    $publicId = $rssItem->getPublicId();
                    if (empty($publicId)) {
                        $publicId = uniqid('feed_', true);
                    }
                    
                    rex_logger::logError(E_NOTICE, 'Processing feed item', [
                        'publicId' => $publicId
                    ]);
                    
                    $item = new rex_feeds_item($this->streamId, $publicId);

                    // Titel mit Fallback
                    $title = $rssItem->getTitle();
                    $item->setTitle($title ?: '');

                    // Content mit Fallback
                    $content = '';
                    
                    // Versuche alle möglichen Content-Quellen
                    $elements = iterator_to_array($rssItem->getAllElements());
                    
                    rex_logger::logError(E_NOTICE, 'Found elements', [
                        'count' => count($elements),
                        'names' => array_map(function($el) { 
                            return $el->getName(); 
                        }, $elements)
                    ]);
                    
                    foreach ($elements as $element) {
                        if ($element->getName() === 'content:encoded') {
                            $content = $element->getValue();
                            break;
                        }
                    }

                    if (empty($content)) {
                        $content = $rssItem->getContent();
                    }

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

                    // Media-Handling
                    $mediaUrl = null;

                    // Hole alle MediaItems
                    $mediaItems = $rssItem->getMedias();
                    rex_logger::logError(E_NOTICE, 'Found media items', [
                        'count' => count($mediaItems)
                    ]);

                    // Versuche zuerst über die MediaItems
                    foreach ($mediaItems as $mediaItem) {
                        rex_logger::logError(E_NOTICE, 'Checking media item', [
                            'type' => $mediaItem->getType(),
                            'url' => $mediaItem->getUrl()
                        ]);
                        
                        if (strpos($mediaItem->getType(), 'image/') === 0) {
                            $mediaUrl = $mediaItem->getUrl();
                            rex_logger::logError(E_NOTICE, 'Found image in media items', [
                                'url' => $mediaUrl
                            ]);
                            break;
                        }
                    }

                    // Wenn kein Bild gefunden, versuche es über die Elemente
                    if (!$mediaUrl) {
                        foreach ($elements as $element) {
                            $name = $element->getName();
                            $attributes = $element->getAttributes();
                            
                            rex_logger::logError(E_NOTICE, 'Checking element for media', [
                                'name' => $name,
                                'attributes' => $attributes
                            ]);
                            
                            // Prüfe media:content
                            if ($name === 'media:content') {
                                if (isset($attributes['url']) && isset($attributes['medium']) && $attributes['medium'] === 'image') {
                                    $mediaUrl = $attributes['url'];
                                    rex_logger::logError(E_NOTICE, 'Found image in media:content', [
                                        'url' => $mediaUrl
                                    ]);
                                    break;
                                }
                            }
                            
                            // Prüfe enclosure
                            if ($name === 'enclosure') {
                                if (isset($attributes['url']) && isset($attributes['type']) && strpos($attributes['type'], 'image/') === 0) {
                                    $mediaUrl = $attributes['url'];
                                    rex_logger::logError(E_NOTICE, 'Found image in enclosure', [
                                        'url' => $mediaUrl
                                    ]);
                                    break;
                                }
                            }
                        }
                    }

                    // Als letztes im Content suchen
                    if (!$mediaUrl && $content) {
                        if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches)) {
                            $mediaUrl = $matches[1];
                            rex_logger::logError(E_NOTICE, 'Found image in content', [
                                'url' => $mediaUrl
                            ]);
                        }
                    }

                    // Setze das Bild wenn gefunden
                    if ($mediaUrl) {
                        $item->setMedia($mediaUrl);
                        $item->setMediaSource($mediaUrl);
                        rex_logger::logError(E_NOTICE, 'Set media for item', [
                            'url' => $mediaUrl,
                            'publicId' => $publicId
                        ]);
                    } else {
                        rex_logger::logError(E_NOTICE, 'No media found for item', [
                            'publicId' => $publicId
                        ]);
                    }

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
