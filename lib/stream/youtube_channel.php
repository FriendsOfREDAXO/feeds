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

use Madcoda\Youtube\Youtube;

class rex_feeds_stream_youtube_channel extends rex_feeds_stream_youtube_playlist
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
        $channel = $youtube->getChannelById($this->typeParams['channel_id'], ['part' => 'contentDetails']);

        return $channel->contentDetails->relatedPlaylists->uploads;
    }
}
