<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\Stream\VimeoPro instead
 */
class rex_feeds_stream_vimeo_pro extends \FriendsOfRedaxo\Feeds\Stream\VimeoPro
{
    public function __construct()
    {
        if (class_exists('rex_logger')) {
            rex_logger::logError('rex_feeds_stream_vimeo_pro is deprecated. Use FriendsOfRedaxo\Feeds\Stream\VimeoPro instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}