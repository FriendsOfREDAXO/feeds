<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require "vendor/autoload.php";

// Load backward compatibility deprecated classes
require_once __DIR__ . '/lib/deprecated/rex_feeds_stream.php';
require_once __DIR__ . '/lib/deprecated/rex_feeds_item.php';
require_once __DIR__ . '/lib/deprecated/rex_feeds_stream_abstract.php';
require_once __DIR__ . '/lib/deprecated/rex_cronjob_feeds.php';
require_once __DIR__ . '/lib/deprecated/rex_effect_feeds.php';
require_once __DIR__ . '/lib/deprecated/rex_feeds_media_helper.php';
require_once __DIR__ . '/lib/deprecated/rex_feeds_helper.php';

// Load deprecated stream classes
require_once __DIR__ . '/lib/deprecated/rex_feeds_stream_rss.php';
require_once __DIR__ . '/lib/deprecated/rex_feeds_stream_youtube_playlist.php';
require_once __DIR__ . '/lib/deprecated/rex_feeds_stream_youtube_channel.php';
require_once __DIR__ . '/lib/deprecated/rex_feeds_stream_ics.php';
require_once __DIR__ . '/lib/deprecated/rex_feeds_stream_vimeo_pro.php';
    
if (\rex_addon::get('cronjob')->isAvailable()) {
    \rex_cronjob_manager::registerType(\FriendsOfRedaxo\Feeds\Cronjob::class);
}

\rex_media_manager::addEffect(\FriendsOfRedaxo\Feeds\MediaManagerEffect::class);

if (\rex_addon::get('watson')->isAvailable()) {
 
    function feedsearch(\rex_extension_point $ep){
      $subject = $ep->getSubject();
      $subject[] = 'Watson\Workflows\Feeds\FeedProvider';
      return $subject;
    }

 \rex_extension::register('WATSON_PROVIDER', 'feedsearch', \rex_extension::LATE); 

}
