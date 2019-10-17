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
use InstagramScraper\Instagram as InstagramScraper;

class rex_feeds_stream_instagram_user extends rex_feeds_stream_instagram_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_instagram_user');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_instagram_username'),
                'name' => 'user',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_instagram_username_notice'),
            ],
            [
                'label' => rex_i18n::msg('feeds_instagram_count'),
                'name' => 'count',
                'type' => 'select',
                'options' => [5 => 5, 10 => 10, 15 => 15, 20 => 20, 30 => 30, 50 => 50, 75 => 75, 100 => 100],
                'default' => 10,
            ],
        ];
    }

    protected function fetchItemsFromOfficialApi(Instagram $instagram)
    {
        if (preg_match('/^\d+$/', $this->typeParams['user']) || $this->typeParams['user'] =='self') {
            $user = $instagram->getUser($this->typeParams['user']);
        } else {
            $user = $instagram->getUserByUsername($this->typeParams['user']);
        }

        return $user->getMedia(['count' => $this->typeParams['count']]);
    }

    protected function fetchItemsFromFrontendApi(InstagramScraper $instagram)
    {
        return $instagram->getMedias($this->typeParams['user'], $this->typeParams['count']);
    }
}
