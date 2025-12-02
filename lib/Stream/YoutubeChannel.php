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

use Exception;
use Madcoda\Youtube\Youtube;
use rex_i18n;
use rex_view;

class YoutubeChannel extends YoutubePlaylist
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_youtube_channel');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_youtube_channel_id'),
                'name' => 'channel_id',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_youtube_channel_id_notice'),
            ],
            [
                'label' => rex_i18n::msg('feeds_youtube_api_key'),
                'name' => 'api_key',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_youtube_api_key_notice'),
            ],
            [
                'label' => rex_i18n::msg('feeds_youtube_count'),
                'name' => 'count',
                'type' => 'select',
                'options' => [5 => 5, 10 => 10, 15 => 15, 20 => 20, 30 => 30, 50 => 50, 75 => 75, 100 => 100],
                'default' => 10,
            ],
        ];
    }

    protected function getPlaylistId(Youtube $youtube)
    {
        try {
            $channel = $youtube->getChannelById($this->typeParams['channel_id'], ['part' => 'contentDetails']);
            return $channel->contentDetails->relatedPlaylists->uploads;
        } catch (Exception $e) {
            echo rex_view::error($e->getMessage());
            return false;
        }
    }
}
