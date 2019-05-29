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

class rex_feeds_stream_youtube_playlist extends rex_feeds_stream_abstract
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

        $youtube = new Youtube(['key' => rex_config::get('feeds', 'google_key')]);

        $videos = $youtube->getPlaylistItemsByPlaylistId($this->getPlaylistId($youtube), $this->typeParams['count']);

        ini_set('arg_separator.output', $argSeparator);

        foreach ($videos as $video) {
            $item = new rex_feeds_item($this->streamId, $video->contentDetails->videoId);

            $item->setTitle($video->snippet->title);
            $item->setContentRaw($video->snippet->description);
            $item->setContent(strip_tags($video->snippet->description));

            $item->setUrl('https://youtube.com/watch?v='.$video->contentDetails->videoId);

            foreach (['maxres', 'standard', 'high', 'medium', 'default'] as $thumbnail) {
                if (isset($video->snippet->thumbnails->$thumbnail->url)) {
                    $item->setMedia($video->snippet->thumbnails->$thumbnail->url);

                    break;
                }
            }

            $item->setDate(new DateTime($video->contentDetails->videoPublishedAt));
            $item->setAuthor($video->snippet->channelTitle);

            $item->setRaw($video);

            $this->updateCount($item);
            $item->save();
        }
    }

    protected function getPlaylistId(Youtube $youtube)
    {
        return $this->typeParams['playlist_id'];
    }
}
