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
        } catch (exception $e) {
            rex_logger::logException($e);
            echo rex_view::error($e->getMessage());
            return;
        }

        /** @var Item $rssItem */
        foreach ($result->getFeed() as $rssItem) {
            try {
                // Sichere Extraktion der PublicId
                $publicId = $rssItem->getPublicId();
                if (empty($publicId)) {
                    // Generiere eine eindeutige ID wenn keine vorhanden
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

                // Media mit mehrfacher Null-Check
                $medias = $rssItem->getMedias();
                if ($medias && !empty($medias) && isset($medias[0])) {
                    $mediaUrl = $medias[0]->getUrl();
                    if ($mediaUrl) {
                        $item->setMedia($mediaUrl);
                    }
                }

                $this->updateCount($item);
                $item->save();
            } catch (Exception $e) {
                // Logge Fehler für einzelne Items, aber fahre mit dem nächsten fort
                rex_logger::logException($e);
                continue;
            }
        }

        self::registerExtensionPoint($this->streamId);
    }
}
