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

    private function generateUid($url)
    {
        return 'rss_' . substr(md5($url), 0, 30);
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
                    
                    $item = new rex_feeds_item($this->streamId, $uid);

                    // Standard Item Processing...
                    $title = $rssItem->getTitle();
                    $content = $rssItem->getContent();
                    
                    $item->setTitle($title ?: '');
                    $item->setContentRaw($content ?: '');
                    $item->setContent($content ? strip_tags($content) : '');
                    $item->setUrl($rssItem->getLink() ?: '');
                    
                    if ($lastModified = $rssItem->getLastModified()) {
                        $item->setDate($lastModified);
                    }

                    $author = $rssItem->getAuthor();
                    $authorName = ($author && method_exists($author, 'getName')) ? $author->getName() : '';
                    $item->setAuthor($authorName);

                    // Media Processing
                    $mediaUrl = null;

                    // Get all media elements
                    $medias = $rssItem->getMedias();
                    foreach ($medias as $media) {
                        rex_logger::logError(E_NOTICE, 'Found media in feed', [
                            'type' => $media->getType(),
                            'url' => $media->getUrl(),
                            'title' => $title
                        ], __FILE__, __LINE__);
                        if (strpos($media->getType(), 'image/') === 0) {
                            $mediaUrl = $media->getUrl();
                            break;
                        }
                    }

                    // Get all elements
                    $elements = iterator_to_array($rssItem->getAllElements());
                    foreach ($elements as $element) {
                        if ($element->getName() === 'media:content') {
                            $attributes = $element->getAttributes();
                            rex_logger::logError(E_NOTICE, 'Found media:content', [
                                'name' => $element->getName(),
                                'attributes' => $attributes,
                                'title' => $title
                            ], __FILE__, __LINE__);
                            if (isset($attributes['url'])) {
                                if (!isset($attributes['type']) || 
                                    strpos($attributes['type'], 'image/') === 0 ||
                                    (isset($attributes['medium']) && $attributes['medium'] === 'image')) {
                                    $mediaUrl = $attributes['url'];
                                    break;
                                }
                            }
                        }
                    }

                    // Set media if found
                    if ($mediaUrl) {
                        rex_logger::logError(E_NOTICE, 'Setting media URL', [
                            'url' => $mediaUrl,
                            'title' => $title
                        ], __FILE__, __LINE__);
                        $item->setMedia($mediaUrl);
                        $item->setMediaSource($mediaUrl);
                    } else {
                        rex_logger::logError(E_NOTICE, 'No media URL found', [
                            'title' => $title
                        ], __FILE__, __LINE__);
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
