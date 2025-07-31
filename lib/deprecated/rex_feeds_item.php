<?php

/**
 * @deprecated Use FriendsOfRedaxo\Feeds\Item instead
 */
class rex_feeds_item extends \FriendsOfRedaxo\Feeds\Item
{
    public function __construct()
    {
        if (class_exists('rex_logger')) {
            rex_logger::logError('rex_feeds_item is deprecated. Use FriendsOfRedaxo\Feeds\Item instead.', [], __FILE__, __LINE__);
        }
        parent::__construct();
    }
}