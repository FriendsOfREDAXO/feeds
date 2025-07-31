<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
