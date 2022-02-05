<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PicoFeed\Reader\Reader;
use PicoFeed\Processor;

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
        $reader = new Reader();
        $resource = $reader->download($this->typeParams['url'], $this->lastModified, $this->etag);
        if (!$resource->isModified()) {
            return;
        }
        $parser = $reader->getParser(
            $resource->getUrl(),
            $resource->getContent(),
            $resource->getEncoding()
        );
        $feed = $parser->execute();

        /** @var Item $rssItem */
        foreach ($feed->getItems() as $rssItem) {
            $item = new rex_feeds_item($this->streamId, $rssItem->getId());
            $item->setTitle($rssItem->getTitle());
            $item->setContentRaw($rssItem->getContent());
            $item->setContent(strip_tags($rssItem->getContent()));

            $item->setUrl($rssItem->getUrl());
            $item->setDate($rssItem->getDate());
            $item->setAuthor($rssItem->getAuthor());
            $item->setLanguage($rssItem->getLanguage());
            if ($rssItem->getEnclosureUrl()) {
                $item->setMedia($rssItem->getEnclosureUrl());
            } elseif ($rssItem->getTag('media:content', 'url')) {
                $item->setMedia($rssItem->getTag('media:content', 'url')[0]);
            }
            $item->setRaw($rssItem);
            
            $this->updateCount($item);
            $item->save();
        }
        self::registerExtensionPoint($this->streamId);
    }
}
