<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\Stream instead
 */
class rex_feeds_stream extends \FriendsOfRedaxo\Feeds\Stream
{
    public function __construct()
    {
        if (class_exists('rex_logger')) {
            rex_logger::logError('rex_feeds_stream is deprecated. Use FriendsOfRedaxo\Feeds\Stream instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}