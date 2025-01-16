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

                    // Content mit Fallback
                    $content = '';
                    
                    // Versuche alle möglichen Content-Quellen
                    $elements = iterator_to_array($rssItem->getAllElements());
                    
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
                    $mediaItems = [];

                    // 1. Media:Content prüfen
                    foreach ($elements as $element) {
                        if ($element->getName() === 'media:content') {
                            $attributes = $element->getAttributes();
                            if (isset($attributes['url']) && (!isset($attributes['type']) || str_starts_with($attributes['type'], 'image/'))) {
                                $mediaItems[] = [
                                    'url' => $attributes['url'],
                                    'type' => $attributes['type'] ?? 'image/jpeg'
                                ];
                            }
                        }
                    }

                    // 2. Enclosure prüfen
                    foreach ($elements as $element) {
                        if ($element->getName() === 'enclosure') {
                            $attributes = $element->getAttributes();
                            if (isset($attributes['url']) && (!isset($attributes['type']) || str_starts_with($attributes['type'], 'image/'))) {
                                $mediaItems[] = [
                                    'url' => $attributes['url'],
                                    'type' => $attributes['type'] ?? 'image/jpeg'
                                ];
                            }
                        }
                    }

                    // 3. Im Content suchen
                    if (empty($mediaItems) && $content) {
                        if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches)) {
                            $mediaItems[] = [
                                'url' => $matches[1],
                                'type' => 'image/jpeg'
                            ];
                        }
                    }

                    // Erstes gefundenes Bild verwenden
                    if (!empty($mediaItems)) {
                        $mediaUrl = $mediaItems[0]['url'];
                        $item->setMedia($mediaUrl);
                        $item->setMediaSource($mediaUrl);
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
