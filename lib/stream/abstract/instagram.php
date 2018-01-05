<?php

/**
 * This file is part of the YFeed package.
 *
 * @author (c) Yakamara Media GmbH & Co. KG
 * @author thomas.blum@redaxo.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Instagram\Instagram;
use Instagram\Media;
use InstagramScraper\Instagram as InstagramScraper;
use InstagramScraper\Model\Media as ScapedMedia;

abstract class rex_yfeed_stream_instagram_abstract extends rex_yfeed_stream_abstract
{
    public function fetch()
    {
        $accessToken = rex_config::get('yfeed', 'instagram_access_token');

        if ($accessToken) {
            $this->fetchOfficialApi($accessToken);

            return;
        }

        $this->fetchFrontendApi();
    }

    /**
     * @param Instagram $instagram
     *
     * @return Media[]
     */
    abstract protected function fetchItemsFromOfficialApi(Instagram $instagram);

    /**
     * @param InstagramScraper $instagram
     *
     * @return ScapedMedia[]
     */
    abstract protected function fetchItemsFromFrontendApi(InstagramScraper $instagram);

    private function fetchOfficialApi($accessToken)
    {
        $instagram = new Instagram($accessToken);
        $instagramItems = $this->fetchItemsFromOfficialApi($instagram);

        foreach ($instagramItems as $instagramItem) {
            $item = new rex_yfeed_item($this->streamId, $instagramItem->getId());
            $item->setTitle($instagramItem->getCaption());

            $item->setUrl($instagramItem->getLink());
            $item->setDate(new DateTime($instagramItem->getCreatedTime('Y-m-d H:i:s')));

            $item->setMedia($instagramItem->getStandardResImage()->url);

            $item->setAuthor($instagramItem->getUser()->getFullName());
            $item->setRaw($instagramItem);

            $this->updateCount($item);
            $item->save();
        }
    }

    private function fetchFrontendApi()
    {
        $instagram = new InstagramScraper();
        $instagramItems = $this->fetchItemsFromFrontendApi($instagram);

        $owners = [];

        foreach ($instagramItems as $instagramItem) {
            $item = new rex_yfeed_item($this->streamId, $instagramItem->getId());
            $item->setTitle($instagramItem->getCaption() ?: null);

            $item->setUrl($instagramItem->getLink());
            $item->setDate(new DateTime('@'.$instagramItem->getCreatedTime()));

            $item->setMedia($instagramItem->getImageStandardResolutionUrl());

            $owner = $instagramItem->getOwner();
            if (!$owner->getFullName()) {
                if (isset($owners[$instagramItem->getOwnerId()])) {
                    $owner = $owners[$instagramItem->getOwnerId()];
                    $instagramItem['owner'] = $owner;
                } else {
                    $itemWithAuthor = $instagram->getMediaByUrl($instagramItem->getLink());
                    $owner = $itemWithAuthor->getOwner();
                    if ($owner->getFullName()) {
                        $instagramItem['owner'] = $owner;
                        $owners[$instagramItem->getOwnerId()] = $owner;
                    }
                }
            }

            $item->setAuthor($owner->getFullName() ?: null);

            $item->setRaw($instagramItem);

            $this->updateCount($item);
            $item->save();
        }
    }
}
