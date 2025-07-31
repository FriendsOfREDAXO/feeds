<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfRedaxo\Feeds\Stream;

use FriendsOfRedaxo\Feeds\Item;

class Rss extends AbstractStream
{
    public function getTypeName()
    {
        return \rex_i18n::msg('feeds_rss_feed');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => \rex_i18n::msg('feeds_rss_url'),
                'name' => 'url',
                'type' => 'string',
            ],
        ];
    }

    private function generateUid($url)
    {
        return 'rss_' . substr(md5($url), 0, 30);
    }

    private function findMediaUrl($rssItem, $title)
    {
        // 1. Prüfe zuerst media:content Elemente
        $elements = iterator_to_array($rssItem->getAllElements());
        foreach ($elements as $element) {
            if ($element->getName() === 'media:content') {
                $attributes = $element->getAttributes();
                if (isset($attributes['url'])) {
                    return $attributes['url'];
                }
            }
        }

        // 2. Prüfe die MediaItems
        $medias = $rssItem->getMedias();
        foreach ($medias as $media) {
            $type = $media->getType();
            if (strpos($type, 'image/') === 0) {
                return $media->getUrl();
            }
        }

        // 3. Prüfe enclosure Elemente
        foreach ($elements as $element) {
            if ($element->getName() === 'enclosure') {
                $attributes = $element->getAttributes();
                if (isset($attributes['url']) && isset($attributes['type'])) {
                    if (strpos($attributes['type'], 'image/') === 0) {
                        return $attributes['url'];
                    }
                }
            }
        }

        // 4. Prüfe auf Bilder im Content
        $content = $rssItem->getContent();
        if ($content) {
            if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches)) {
                return $matches[1];
            }
        }

        return null;
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
                    $url = $rssItem->getLink() ?: $rssItem->getPublicId();
                    if (empty($url)) {
                        $url = uniqid('feed_', true);
                    }
                    
                    $uid = $this->generateUid($url);
                    $title = $rssItem->getTitle();
                    
                    $item = new Item($this->streamId, $uid);

                    // Basis-Felder setzen
                    $item->setTitle($title ?: '');
                    $content = $rssItem->getContent() ?: '';
                    $item->setContentRaw($content);
                    $item->setContent(strip_tags($content));
                    $item->setUrl($rssItem->getLink() ?: '');

                    if ($lastModified = $rssItem->getLastModified()) {
                        $item->setDate($lastModified);
                    }

                    $author = $rssItem->getAuthor();
                    $authorName = ($author && method_exists($author, 'getName')) ? $author->getName() : '';
                    $item->setAuthor($authorName);

                    // Media URL finden und setzen
                    if ($mediaUrl = $this->findMediaUrl($rssItem, $title)) {
                        $item->setMedia($mediaUrl);
                        $item->setMediaSource($mediaUrl);
                    }

                    $this->updateCount($item);
                    $item->save();

                } catch (\Exception $e) {
                    \rex_logger::logException($e);
                    continue;
                }
            }

            self::registerExtensionPoint($this->streamId);

        } catch (\Exception $e) {
            \rex_logger::logException($e);
            echo \rex_view::error($e->getMessage());
        }
    }
}
