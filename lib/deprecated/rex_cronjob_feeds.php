<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\Cronjob instead
 */
class rex_cronjob_feeds extends \FriendsOfRedaxo\Feeds\Cronjob
{
    public function __construct()
    {
        if (class_exists('rex_logger')) {
            rex_logger::logError('rex_cronjob_feeds is deprecated. Use FriendsOfRedaxo\Feeds\Cronjob instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}