<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\Stream\YoutubeChannel instead
 */
class rex_feeds_stream_youtube_channel extends \FriendsOfRedaxo\Feeds\Stream\YoutubeChannel
{
    public function __construct()
    {
        if (class_exists('rex_logger') && method_exists('rex_logger', 'logError')) {
            rex_logger::logError('rex_feeds_stream_youtube_channel is deprecated. Use FriendsOfRedaxo\Feeds\Stream\YoutubeChannel instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}