<?php

/**
 * This file is part of the Feeds package.
 *
 * @author (c) Yakamara Media GmbH & Co. KG
 * @author thomas.blum@redaxo.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use TwitterOAuth\Auth\ApplicationOnlyAuth;
use TwitterOAuth\Serializer\ObjectSerializer;

class rex_feeds_stream_twitter_hashtag extends rex_feeds_stream_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_twitter_hashtag');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_twitter_hashtag_q'),
                'name' => 'q',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_twitter_hashtag_with_prefix'),
            ],
            [
                'label' => rex_i18n::msg('feeds_twitter_count'),
                'name' => 'count',
                'type' => 'select',
                'options' => [5 => 5, 10 => 10, 15 => 15, 20 => 20, 30 => 30, 50 => 50, 75 => 75, 100 => 100],
                'default' => 10,
            ],
            [
                'label' => rex_i18n::msg('feeds_twitter_result_type'),
                'name' => 'result_type',
                'type' => 'select',
                'options' => [
                    'mixed' => rex_i18n::msg('feeds_twitter_result_type_mixed'),
                    'recent' => rex_i18n::msg('feeds_twitter_result_type_recent'),
                    'popular' => rex_i18n::msg('feeds_twitter_result_type_popular'), ],
                'default' => 'mixed',
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
        $params['q'] .= ' -filter:retweets';
        $params['q'] .= ' -filter:retweets';
        $params['tweet_mode'] = 'extended';

        $items = $auth->get('search/tweets', $params);
        $items = $items->statuses;

        foreach ($items as $twitterItem) {
            $item = new rex_feeds_item($this->streamId, $twitterItem->id_str);
            $item->setContentRaw($twitterItem->full_text);
            $item->setContent(strip_tags($twitterItem->full_text));

            $item->setUrl('https://twitter.com/statuses/'.$twitterItem->id_str);
            $item->setDate(new DateTime($twitterItem->created_at));

            $item->setAuthor($twitterItem->user->name);
            $item->setLanguage($twitterItem->lang);
            $item->setRaw($twitterItem);

            $media = $twitterItem->entities->media;
            if (isset($media[0])) {
                if ($media[0]->type == 'photo') {
                    $item->setMedia($media[0]->media_url);
                }
            }

            $this->updateCount($item);
            $item->save();
        }
    }
}
