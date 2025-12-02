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

use DateTime;
use Exception;
use FriendsOfRedaxo\Feeds\Item;
use Madcoda\Youtube\Youtube;
use rex_i18n;
use rex_view;

class YoutubePlaylist extends AbstractStream
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_youtube_playlist');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_youtube_playlist_id'),
                'name' => 'playlist_id',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_youtube_playlist_id_notice'),
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
                'options' => [5 => 5, 10 => 10, 15 => 15, 20 => 20, 30 => 30, 50 => 50],
                'default' => 10,
            ],
        ];
    }

    public function fetch()
    {
        $argSeparator = ini_set('arg_separator.output', '&');

        $youtube = new Youtube(['key' => $this->typeParams['api_key']]);

        $videos = null;
        try {
            $videos = $youtube->getPlaylistItemsByPlaylistId($this->getPlaylistId($youtube), $this->typeParams['count']);

            ini_set('arg_separator.output', $argSeparator);

            foreach ($videos as $video) {
                $item = new Item($this->streamId, $video->contentDetails->videoId);

                $item->setTitle($video->snippet->title);
                $item->setContentRaw($video->snippet->description);
                $item->setContent(strip_tags($video->snippet->description));

                $item->setUrl('https://youtube.com/watch?v=' . $video->contentDetails->videoId);

                foreach (['maxres', 'standard', 'high', 'medium', 'default'] as $thumbnail) {
                    if (isset($video->snippet->thumbnails->$thumbnail->url)) {
                        $item->setMedia($video->snippet->thumbnails->$thumbnail->url);

                        break;
                    }
                }

                $item->setDate(new DateTime($video->snippet->publishedAt));
                $item->setAuthor($video->snippet->channelTitle);

                $item->setRaw($video);

                if (!$this->filter($item)) {
                    continue;
                }

                $this->updateCount($item);
                $item->save();
            }
        } catch (Exception $e) {
            echo rex_view::error($e->getMessage());
        }
        self::registerExtensionPoint($this->streamId);
    }

    protected function getPlaylistId(Youtube $youtube)
    {
        return $this->typeParams['playlist_id'];
    }
}
