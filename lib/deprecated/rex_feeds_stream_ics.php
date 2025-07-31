<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\Stream\Ics instead
 */
class rex_feeds_stream_ics extends \FriendsOfRedaxo\Feeds\Stream\Ics
{
    public function __construct()
    {
        if (class_exists('rex_logger')) {
            rex_logger::logError('rex_feeds_stream_ics is deprecated. Use FriendsOfRedaxo\Feeds\Stream\Ics instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}