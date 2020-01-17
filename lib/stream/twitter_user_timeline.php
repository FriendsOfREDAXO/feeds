<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use TwitterOAuth\Auth\ApplicationOnlyAuth;
use TwitterOAuth\Serializer\ObjectSerializer;

class rex_feeds_stream_twitter_user_timeline extends rex_feeds_stream_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_twitter_user_timeline');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_twitter_screen_name'),
                'name' => 'screen_name',
                'type' => 'string',
            ],
            [
                'label' => rex_i18n::msg('feeds_twitter_count'),
                'name' => 'count',
                'type' => 'select',
                'options' => [5 => 5, 10 => 10, 15 => 15, 20 => 20, 30 => 30, 50 => 50, 75 => 75, 100 => 100],
                'default' => 10,
            ],
            [
                'label' => rex_i18n::msg('feeds_twitter_exclude_replies'),
                'name' => 'exclude_replies',
                'type' => 'select',
                'options' => ['1' => rex_i18n::msg('yes'), '0' => rex_i18n::msg('no')],
                'default' => 1,
            ],
        ];
    }

    public function fetch()
    {
        $credentials = [
            'consumer_key' => rex_config::get('feeds', 'twitter_consumer_key'),
            'consumer_secret' => rex_config::get('feeds', 'twitter_consumer_secret'),
            'oauth_token' => rex_config::get('feeds', 'twitter_oauth_token'),
            'oauth_token_secret' => rex_config::get('feeds', 'twitter_oauth_token_secret'),
        ];

        $auth = new ApplicationOnlyAuth($credentials, new ObjectSerializer());
        $params = $this->typeParams;
        $params['tweet_mode'] = 'extended';

        $items = $auth->get('statuses/user_timeline', $params);

        foreach ($items as $twitterItem) {
            $item = new rex_feeds_item($this->streamId, $twitterItem->id_str);
            $item->setContentRaw($twitterItem->full_text);
            $item->setContent(strip_tags($twitterItem->full_text));

            dump($twitterItem);

            $item->setUrl('https://twitter.com/'.$twitterItem->user->screen_name.'/status/'.$twitterItem->id_str);
            $item->setDate(new DateTime($twitterItem->created_at));

            $item->setAuthor($twitterItem->user->name);
            $item->setUsername($twitterItem->user->screen_name);
            $item->setLanguage($twitterItem->lang);
            $item->setRaw($twitterItem);

            if (isset($twitterItem->entities->media)) {
                $media = $twitterItem->entities->media;
                if (isset($media[0])) {
                    if ($media[0]->type == 'photo') {
                        $item->setMedia($media[0]->media_url_https);
                    }
                }
            }

            $this->updateCount($item);
            $item->save();
        }
        self::registerExtensionPoint($this->streamId);

    }
}
