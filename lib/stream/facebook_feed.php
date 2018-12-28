<?php

/**
 * This file is part of the YFeed package.
 *
 * @author (c) Yakamara Media GmbH & Co. KG
 * @author thomas.blum@redaxo.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_yfeed_stream_facebook_feed extends rex_yfeed_stream_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('yfeed_facebook_feed');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('yfeed_facebook_profile_id'),
                'name' => 'profile_id',
                'type' => 'string',
            ],
            [
                'label' => rex_i18n::msg('yfeed_facebook_token'),
                'name' => 'token',
                'type' => 'string',
                'notice' => rex_i18n::msg('yfeed_facebook_token_note')
            ],
            [
                'label' => rex_i18n::msg('yfeed_facebook_result_type'),
                'name' => 'result_type',
                'type' => 'select',
                'options' => [
                    'feed' => rex_i18n::msg('yfeed_facebook_result_type_feed'),
                    'posts' => rex_i18n::msg('yfeed_facebook_result_type_posts'),
                    'tagged' => rex_i18n::msg('yfeed_facebook_result_type_tagged'),
                ],
                'default' => 'feed',
            ],
            [
                'label' => rex_i18n::msg('yfeed_facebook_count'),
                'name' => 'count',
                'type' => 'select',
                'options' => [5 => 5, 10 => 10, 15 => 15, 20 => 20, 30 => 30, 50 => 50, 75 => 75, 100 => 100],
                'default' => 10,
            ],
            [
                'label' => rex_i18n::msg('yfeed_facebook_api_version'),
                'name' => 'api_version',
                'type' => 'select',
                'options' => ["v3.2" => "3.2", "v3.1" => "3.1", "v3.0" => "3.0", "v2.12" => "2.12"],
                'default' => "v3.2",
            ],
        ];
    }

    public function fetch()
    {
        $fb = $this->getFacebook();

        $fields = 'id,permalink_url,from,story,message,link,created_time,attachments';
        $url = sprintf(
            '/%s/%s?locale=de&fields=%s&limit=%d',
            $this->typeParams['profile_id'],
            $this->typeParams['result_type'],
            $fields,
            $this->typeParams['count']
        );
        $items = $fb->get($url)->getGraphEdge();

        /** @var Facebook\GraphNodes\GraphNode $facebookItem */
        foreach ($items as $facebookItem) {
            $item = new rex_yfeed_item($this->streamId, $facebookItem->getField('id'));
            $item->setTitle($facebookItem->getField('story'));
            $item->setContentRaw($facebookItem->getField('message'));
            $item->setContent(strip_tags($facebookItem->getField('message')));
            $item->setUrl($facebookItem->getField('permalink_url'));
            $item->setDate($facebookItem->getField('created_time'));

            $from = $facebookItem->getField('from');
            if ($from && $name = $from->getField('name')) {
                $item->setAuthor($name);
            }

            $attachments = $facebookItem->getField('attachments');
            if ($attachments) {
                /** @var Facebook\GraphNodes\GraphNode $attachment */
                foreach ($attachments as $attachment) {
                    if ('photo' !== $attachment->getField('type') || !$media = $attachment->getField('media')) {
                        continue;
                    }
                    /** @var Facebook\GraphNodes\GraphNode $image */
                    $image = $media->getField('image');
                    if ($image) {
                        $item->setMedia($image->getField('src'));
                        break;
                    }
                }
            }

            $item->setRaw($facebookItem);

            $this->updateCount($item);
            $item->save();
        }
    }

    /**
     * @return \Facebook\Facebook
     */
    private function getFacebook()
    {
        static $facebook;

        if (!$facebook) {
            $credentials = [
                'app_id' => rex_config::get('yfeed', 'facebook_app_id'),
                'app_secret' => rex_config::get('yfeed', 'facebook_app_secret'),
                'default_graph_version' => $this->typeParams['api_version'],
            ];
            $facebook = new Facebook\Facebook($credentials);
            if ($this->typeParams['token']) {
                $this->checkAccessToken($facebook);
                $facebook->setDefaultAccessToken($this->typeParams['token']);
            } else {
                $facebook->setDefaultAccessToken(rex_config::get('yfeed', 'facebook_app_id').'|'.rex_config::get('yfeed', 'facebook_app_secret'));
            }
        }

        return $facebook;
    }

    private function checkAccessToken(Facebook\Facebook $facebook)
    {
        $oauth = $facebook->getOAuth2Client();
        $metaData = $oauth->debugToken($this->typeParams['token']);

        if (!$metaData->getExpiresAt() || $metaData->getExpiresAt()->getTimestamp() > time() + 60 * 60 * 24 * 50) {
            return;
        }

        try {
            $code = $oauth->getCodeFromLongLivedAccessToken($this->typeParams['token']);
            $newToken = $oauth->getAccessTokenFromCode($code);
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            if (false === strpos($e->getMessage(), 'long-lived')) {
                throw $e;
            }

            $newToken = $oauth->getLongLivedAccessToken($this->typeParams['token']);
        }

        if (!$newToken) {
            return;
        }

        $this->typeParams['token'] = (string) $newToken;
        rex_sql::factory()
            ->setTable(rex_yfeed_stream::table())
            ->setWhere('id = :id', ['id' => $this->streamId])
            ->setArrayValue('type_params', $this->typeParams)
            ->update();
    }
}
