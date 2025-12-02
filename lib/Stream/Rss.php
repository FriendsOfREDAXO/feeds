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

use FeedIo\Adapter\Http\Client as FeedIoClient;
use FeedIo\FeedIo;
use FriendsOfRedaxo\Feeds\Item;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttplugClient;

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
                if (isset($attributes['url'])) {
                    // Check type if exists, otherwise check file extension
                    $isImage = false;
                    if (isset($attributes['type']) && strpos($attributes['type'], 'image/') === 0) {
                        $isImage = true;
                    } elseif (preg_match('/\.(jpg|jpeg|png|gif|webp|avif)(\?.*)?$/i', $attributes['url'])) {
                        $isImage = true;
                    }

                    if ($isImage) {
                        return $attributes['url'];
                    }
                }
            }
        }

        // 4. Prüfe auf Bilder im Content
        $content = $rssItem->getContent() ?: $rssItem->getDescription();
        if ($content) {
            if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public function fetch()
    {
        $symfonyClient = HttpClient::create([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; REDAXO Feeds Addon; +https://github.com/FriendsOfREDAXO/feeds)',
            ],
            'max_redirects' => 5,
            'timeout' => 10,
        ]);

        $client = new FeedIoClient(new HttplugClient($symfonyClient));
        $logger = new NullLogger();
        $feedIo = new FeedIo($client, $logger);

        try {
            $result = $feedIo->read($this->typeParams['url']);
            
            /** @var \FeedIo\Feed\ItemInterface $rssItem */
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
                    $content = $rssItem->getContent() ?: $rssItem->getDescription();
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

                    if (!$this->filter($item)) {
                        continue;
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
