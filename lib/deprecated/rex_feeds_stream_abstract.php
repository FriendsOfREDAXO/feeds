<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\Stream\AbstractStream instead
 */
abstract class rex_feeds_stream_abstract extends \FriendsOfRedaxo\Feeds\Stream\AbstractStream
{
    public function __construct()
    {
        if (class_exists('rex_logger')) {
            rex_logger::logError('rex_feeds_stream_abstract is deprecated. Use FriendsOfRedaxo\Feeds\Stream\AbstractStream instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}