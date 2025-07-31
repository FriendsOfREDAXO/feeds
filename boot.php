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

// Create backward compatibility class aliases
if (!class_exists('rex_feeds_stream') && class_exists('FriendsOfRedaxo\Feeds\Stream')) {
    class_alias('FriendsOfRedaxo\Feeds\Stream', 'rex_feeds_stream');
}
if (!class_exists('rex_feeds_item') && class_exists('FriendsOfRedaxo\Feeds\Item')) {
    class_alias('FriendsOfRedaxo\Feeds\Item', 'rex_feeds_item');
}
if (!class_exists('rex_cronjob_feeds') && class_exists('FriendsOfRedaxo\Feeds\Cronjob')) {
    class_alias('FriendsOfRedaxo\Feeds\Cronjob', 'rex_cronjob_feeds');
}
if (!class_exists('rex_effect_feeds') && class_exists('FriendsOfRedaxo\Feeds\MediaManagerEffect')) {
    class_alias('FriendsOfRedaxo\Feeds\MediaManagerEffect', 'rex_effect_feeds');
}
if (!class_exists('rex_feeds_stream_abstract') && class_exists('FriendsOfRedaxo\Feeds\Stream\AbstractStream')) {
    class_alias('FriendsOfRedaxo\Feeds\Stream\AbstractStream', 'rex_feeds_stream_abstract');
}
if (!class_exists('rex_feeds_media_helper') && class_exists('FriendsOfRedaxo\Feeds\MediaHelper')) {
    class_alias('FriendsOfRedaxo\Feeds\MediaHelper', 'rex_feeds_media_helper');
}
if (!class_exists('rex_feeds_helper') && class_exists('FriendsOfRedaxo\Feeds\Helper')) {
    class_alias('FriendsOfRedaxo\Feeds\Helper', 'rex_feeds_helper');
}

// Stream type aliases
if (!class_exists('rex_feeds_stream_rss') && class_exists('FriendsOfRedaxo\Feeds\Stream\Rss')) {
    class_alias('FriendsOfRedaxo\Feeds\Stream\Rss', 'rex_feeds_stream_rss');
}
if (!class_exists('rex_feeds_stream_youtube_playlist') && class_exists('FriendsOfRedaxo\Feeds\Stream\YoutubePlaylist')) {
    class_alias('FriendsOfRedaxo\Feeds\Stream\YoutubePlaylist', 'rex_feeds_stream_youtube_playlist');
}
if (!class_exists('rex_feeds_stream_youtube_channel') && class_exists('FriendsOfRedaxo\Feeds\Stream\YoutubeChannel')) {
    class_alias('FriendsOfRedaxo\Feeds\Stream\YoutubeChannel', 'rex_feeds_stream_youtube_channel');
}
if (!class_exists('rex_feeds_stream_ics') && class_exists('FriendsOfRedaxo\Feeds\Stream\Ics')) {
    class_alias('FriendsOfRedaxo\Feeds\Stream\Ics', 'rex_feeds_stream_ics');
}
if (!class_exists('rex_feeds_stream_vimeo_pro') && class_exists('FriendsOfRedaxo\Feeds\Stream\VimeoPro')) {
    class_alias('FriendsOfRedaxo\Feeds\Stream\VimeoPro', 'rex_feeds_stream_vimeo_pro');
}
    
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
