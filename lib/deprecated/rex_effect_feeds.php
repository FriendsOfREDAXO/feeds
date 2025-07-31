<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\MediaManagerEffect instead
 */
class rex_effect_feeds extends \FriendsOfRedaxo\Feeds\MediaManagerEffect
{
    public function __construct()
    {
        if (class_exists('rex_logger')) {
            rex_logger::logError('rex_effect_feeds is deprecated. Use FriendsOfRedaxo\Feeds\MediaManagerEffect instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}