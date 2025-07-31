<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\Stream\YoutubePlaylist instead
 */
class rex_feeds_stream_youtube_playlist extends \FriendsOfRedaxo\Feeds\Stream\YoutubePlaylist
{
    public function __construct()
    {
        if (class_exists('rex_logger')) {
            rex_logger::logError('rex_feeds_stream_youtube_playlist is deprecated. Use FriendsOfRedaxo\Feeds\Stream\YoutubePlaylist instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}