<?php 
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
                $content = $rssItem->getContent();
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

                // Media-Handling verbessert
                $mediaUrl = null;
                
                // 1. Versuche es zuerst mit Media Enclosures
                $medias = $rssItem->getMedias();
                if ($medias && !empty($medias)) {
                    foreach ($medias as $media) {
                        if ($media->getUrl()) {
                            $mediaUrl = $media->getUrl();
                            break;
                        }
                    }
                }
                
                // 2. Wenn kein Media gefunden, suche nach einem Bild im Content
                if (!$mediaUrl && $content) {
                    if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches)) {
                        $mediaUrl = $matches[1];
                    }
                }

                // 3. Versuche es mit custom Elementen (falls vorhanden)
                if (!$mediaUrl) {
                    $elements = $rssItem->getCustomElements();
                    $mediaElements = array_filter($elements, function($element) {
                        return strpos(strtolower($element->getName()), 'image') !== false 
                            || strpos(strtolower($element->getName()), 'thumbnail') !== false;
                    });
                    
                    if (!empty($mediaElements)) {
                        foreach ($mediaElements as $element) {
                            if ($element->getValue()) {
                                $mediaUrl = $element->getValue();
                                break;
                            }
                        }
                    }
                }

                // Setze das gefundene Bild
                if ($mediaUrl) {
                    $item->setMedia($mediaUrl);
                }

                // Raw-Daten und Debug-Informationen
                $rawData = [
                    'title' => $title,
                    'content' => $content,
                    'link' => $link,
                    'media_url' => $mediaUrl,
                    'custom_elements' => $rssItem->getCustomElements()
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
