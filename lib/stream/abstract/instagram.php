<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Instagram\Instagram;
use Instagram\Media;
use InstagramScraper\Instagram as InstagramScraper;
use InstagramScraper\Model\Media as ScapedMedia;

abstract class rex_feeds_stream_instagram_abstract extends rex_feeds_stream_abstract
{
    public function fetch()
    {
        $accessToken = rex_config::get('feeds', 'instagram_access_token');

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
            $item = new rex_feeds_item($this->streamId, $instagramItem->getId());
            $item->setTitle($instagramItem->getCaption());

            $item->setUrl($instagramItem->getLink());
            $item->setDate(new DateTime($instagramItem->getCreatedTime('Y-m-d H:i:s')));

            if ($image = $instagramItem->getStandardResImage()) {
                $item->setMedia($image->url);
            }

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
            $item = new rex_feeds_item($this->streamId, $instagramItem->getId());
            $item->setTitle($instagramItem->getCaption() ?: null);

            $item->setUrl($instagramItem->getLink());
            $item->setDate(new DateTime('@'.$instagramItem->getCreatedTime()));

            $image = $instagramItem->getImageHighResolutionUrl() ?: $instagramItem->getImageStandardResolutionUrl();
            if ($image) {
                $item->setMedia($image);
            }

//            $owner = $instagramItem->getOwner();
//            if (!$owner->getFullName()) {
//                if (isset($owners[$instagramItem->getOwnerId()])) {
//                    $owner = $owners[$instagramItem->getOwnerId()];
//                    $instagramItem['owner'] = $owner;
//                } else {
//                    $itemWithAuthor = $instagram->getMediaByUrl($instagramItem->getLink());
//                    $owner = $itemWithAuthor->getOwner();
//                    if ($owner->getFullName()) {
//                        $instagramItem['owner'] = $owner;
//                        $owners[$instagramItem->getOwnerId()] = $owner;
//                    }
//                }
//            }
//
//            $item->setAuthor($owner->getFullName() ?: null);
//            $item->setUsername($owner->getUsername());

            $item->setRaw($instagramItem);

            $this->updateCount($item);
            $item->save();
        }
        self::registerExtensionPoint($this->streamId);

    }
}
