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
        foreach ($result->getFeed()  as $rssItem) {
            $item = new rex_feeds_item($this->streamId, $rssItem->getPublicId());
            $item->setTitle($rssItem->getTitle());
            $item->setContentRaw($rssItem->getContent());
            $item->setContent(strip_tags($rssItem->getContent()));
            $item->setUrl($rssItem->getLink());
            if($rssItem->getLastModified())
            {
            $item->setDate($rssItem->getLastModified());
            }
            $item->setAuthor($rssItem->getAuthor()->getName());
            if ($rssItem->getMedias() && isset($rssItem->getMedias()[0])) {
                $item->setMedia($rssItem->getMedias()[0]->getUrl());
            }
            $this->updateCount($item);
            $item->save();
        }
        self::registerExtensionPoint($this->streamId);
    }
}
