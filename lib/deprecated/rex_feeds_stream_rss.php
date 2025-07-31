<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\Stream\Rss instead
 */
class rex_feeds_stream_rss extends \FriendsOfRedaxo\Feeds\Stream\Rss
{
    public function __construct()
    {
        if (class_exists('rex_logger')) {
            rex_logger::logError('rex_feeds_stream_rss is deprecated. Use FriendsOfRedaxo\Feeds\Stream\Rss instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}