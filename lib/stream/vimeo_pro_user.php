<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Vimeo\Vimeo;

class rex_feeds_stream_vimeo_pro_user extends rex_feeds_stream_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_vimeo_pro_user');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_vimeo_user_id'),
                'name' => 'client_id',
                'type' => 'string',
            ],
            [
                'label' => rex_i18n::msg('feeds_vimeo_access_token'),
                'name' => 'access_token',
                'type' => 'string',
            ],
            [
                'label' => rex_i18n::msg('feeds_vimeo_client_secret'),
                'name' => 'client_secret',
                'type' => 'string',
            ],
        ];
    }

    public function fetch()
    {
        $argSeparator = ini_set('arg_separator.output', '&');

        $vimeo = new Vimeo($this->getVimeoClientID(), $this->getVimeoClientSecret());
        if (!empty($this->getVimeoAccessToken())) {
            $vimeo->setToken($this->getVimeoAccessToken());
            $videos = $vimeo->request('/me/videos?per_page=100');
            // $videos = $videos['body'];
            $videos_data = $videos['body']['data'];
            while($videos['body']['paging']['next'] != "") {
                $videos = $vimeo->request($videos['body']['paging']['next']);
                $videos_data = array_merge($videos_data, $videos['body']['data']);
                }
            $videos = $videos_data;
			
        }
        ini_set('arg_separator.output', $argSeparator);

        foreach ($videos as $video) {
            $uri = $video['uri'];
            $uri = str_replace("/videos/", "", $uri);
            $item = new rex_feeds_item($this->streamId, $uri);
            // only Videos with View-Right
            if ($video['privacy']['view'] === 'anybody') {
            } else {
                    continue;
            } 
            $item->setTitle($video['name']);
                
            $item->setContentRaw($video['description']);
            $item->setContent($video['description']);
                
            $item->setUrl($video['link']);
                
            $item->setMedia($video['pictures']['base_link']);
            $item->setDate(new DateTime($video['created_time']));
                
            //$item->setAuthor($video->snippet->channelTitle);
            $item->setRaw($video);
            $this->updateCount($item);
            $item->save();
        }
        
        self::registerExtensionPoint($this->streamId);
    }
    protected function getVimeoClientID()
    {
        return $this->typeParams['client_id'];
    }
    protected function getVimeoAccessToken()
    {
        return $this->typeParams['access_token'];
    }
    protected function getVimeoClientSecret()
    {
        return $this->typeParams['client_secret'];
    }
}
