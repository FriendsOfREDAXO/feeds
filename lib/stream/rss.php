<?php

/**
 * This file is part of the Feeds package.
 *
 * @author (c) Yakamara Media GmbH & Co. KG
 * @author thomas.blum@redaxo.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use PicoFeed\Parser\Item;
use PicoFeed\Reader\Reader;

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
        $parser->disableContentFiltering();
        $feed = $parser->execute();

        /** @var Item $rssItem */
        foreach ($feed->getItems() as $rssItem) {
            $item = new rex_feeds_item($this->streamId, $rssItem->getId());
            $item->setTitle($rssItem->getTitle());
            $item->setContentRaw($rssItem->getContent());

            $parser->filterItemContent($feed, $rssItem);
            $item->setContent(strip_tags($rssItem->getContent()));

            $item->setUrl($rssItem->getUrl());
            $item->setDate($rssItem->getDate());
            $item->setAuthor($rssItem->getAuthor());
            $item->setLanguage($rssItem->getLanguage());
            if ($rssItem->getEnclosureUrl()) {
                $item->setMedia($rssItem->getEnclosureUrl());
            }
            $item->setRaw($rssItem);
            
            $this->updateCount($item);
            $item->save();
        }
    }
}
