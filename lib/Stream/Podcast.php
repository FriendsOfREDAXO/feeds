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

class Podcast extends AbstractStream
{
    public function getTypeName()
    {
        return \rex_i18n::msg('feeds_podcast');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => \rex_i18n::msg('feeds_podcast_url'),
                'name' => 'url',
                'type' => 'string',
            ],
            [
                'label' => \rex_i18n::msg('feeds_podcast_count'),
                'name' => 'count',
                'type' => 'select',
                'options' => [5 => 5, 10 => 10, 15 => 15, 20 => 20, 30 => 30, 50 => 50, 100 => 100],
                'default' => 10,
            ],
        ];
    }

    private function generateUid($url)
    {
        return 'podcast_' . substr(md5($url), 0, 30);
    }

    private function findMediaUrl($rssItem, $title)
    {
        // 1. Prüfe itunes:image
        $elements = iterator_to_array($rssItem->getAllElements());
        foreach ($elements as $element) {
            if ($element->getName() === 'itunes:image') {
                $attributes = $element->getAttributes();
                if (isset($attributes['href'])) {
                    return $attributes['href'];
                }
            }
        }

        // 2. Prüfe media:content
        foreach ($elements as $element) {
            if ($element->getName() === 'media:content') {
                $attributes = $element->getAttributes();
                if (isset($attributes['url'])) {
                    return $attributes['url'];
                }
            }
        }

        // 3. Prüfe MediaItems (FeedIo standard)
        $medias = $rssItem->getMedias();
        foreach ($medias as $media) {
            $type = $media->getType();
            if (strpos($type, 'image/') === 0) {
                return $media->getUrl();
            }
        }

        // 4. Prüfe enclosure (Images)
        foreach ($elements as $element) {
            if ($element->getName() === 'enclosure') {
                $attributes = $element->getAttributes();
                if (isset($attributes['url'])) {
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

        return null;
    }

    private function findAudioUrl($rssItem)
    {
        $elements = iterator_to_array($rssItem->getAllElements());
        
        // 1. Prüfe enclosure (Audio)
        foreach ($elements as $element) {
            if ($element->getName() === 'enclosure') {
                $attributes = $element->getAttributes();
                if (isset($attributes['url'])) {
                    if (isset($attributes['type']) && strpos($attributes['type'], 'audio/') === 0) {
                        return $attributes['url'];
                    }
                    // Fallback: Extension check
                    if (preg_match('/\.(mp3|m4a|wav|ogg)(\?.*)?$/i', $attributes['url'])) {
                        return $attributes['url'];
                    }
                }
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
            
            $count = 0;
            $maxCount = isset($this->typeParams['count']) ? (int) $this->typeParams['count'] : 10;

            /** @var \FeedIo\Feed\ItemInterface $rssItem */
            foreach ($result->getFeed() as $rssItem) {
                if ($count >= $maxCount) {
                    break;
                }
                try {
                    $url = $rssItem->getLink() ?: $rssItem->getPublicId();
                    if (empty($url)) {
                        $url = uniqid('podcast_', true);
                    }
                    
                    $uid = $this->generateUid($url);
                    $title = $rssItem->getTitle();
                    
                    $item = new Item($this->streamId, $uid);

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

                    // Media URL (Cover Image)
                    if ($mediaUrl = $this->findMediaUrl($rssItem, $title)) {
                        $item->setMedia($mediaUrl);
                        $item->setMediaSource($mediaUrl);
                    }

                    // Audio URL in Raw Data speichern
                    $rawData = [];
                    $audioUrl = $this->findAudioUrl($rssItem);
                    if ($audioUrl) {
                        $rawData['audio_url'] = $audioUrl;
                    }

                    // Duration
                    $elements = iterator_to_array($rssItem->getAllElements());
                    foreach ($elements as $element) {
                        if ($element->getName() === 'itunes:duration') {
                            $rawData['duration'] = $element->getValue();
                        }
                    }
                    
                    if (!empty($rawData)) {
                        $item->setRaw($rawData);
                    }

                    if (!$this->filter($item)) {
                        continue;
                    }

                    $this->updateCount($item);
                    $item->save();
                    $count++;

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
