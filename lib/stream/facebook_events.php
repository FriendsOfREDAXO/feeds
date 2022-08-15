<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_feeds_stream_facebook_events extends rex_feeds_stream_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_facebook_events');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_facebook_events_page_name'),
                'name' => 'page_name',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_facebook_events_page_name_notice'),
            ],
            [
                'label' => rex_i18n::msg('feeds_facebook_events_access_token'),
                'name' => 'access_token',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_facebook_events_access_token_notice'),
            ],
        ];
    }

    public function fetch()
    {
        $params = $this->typeParams;

        try {
            $socket = rex_socket::factory("graph.facebook.com", 443, true);
            $socket->setPath('/'.$params['page_name'].'/events/?fields=cover,description,name,start_time,id,link&access_token='.$params['access_token'].'');
            $response = $socket->doGet();
            if ($response->isOk()) {
                $result = json_decode($response->getBody());
                if (!is_array($result->data)) {
                    echo rex_view::error("Fehler beim Abruf. Sind die Daten korrekt?");
                } else {
                    foreach ($result->data as $event) {
                        $item = new rex_feeds_item($this->streamId, $event->id);
                        $item->setTitle($event->name);
                        $item->setContent($event->description);
                        $item->setUrl('https://de-de.facebook.com/events/' . $event->id);
                        $item->setDate(new DateTime($event->start_time));
                        $item->setRaw(json_encode($event));

                        if (isset($event->cover->source)) {
                            $item->setMedia($event->cover->source);
                        }
                        $this->updateCount($item);
                        $item->save();
                    }
                }
            }
        } catch (rex_socket_exception $e) {
            echo rex_view::error($e->getMessage());
        }
        self::registerExtensionPoint($this->streamId);

    }
}
