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

class rex_yfeed_stream_googleplaces_reviews extends rex_yfeed_stream_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('yfeed_googleplaces_reviews');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('yfeed_googleplaces_placeid'),
                'name' => 'place_id',
                'type' => 'string',
                'notice' => 'https://developers.google.com/places/web-service/details?hl=de'
            ]
        ];
    }

    public function fetch()
    {

	
		$params = array(
			'url' => 'https://maps.googleapis.com/maps/api/place/details/json?',
            'key' => rex_config::get('yfeed', 'googleplaces_key'),
            'placeid' => $this->typeParams['place_id']			
		);
		
		$url = urldecode( array_shift( $params ) . http_build_query( $params, '', '&' ) );
	
		$response = false;
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);
		curl_close($curl);
		
		if($response !== false) {
 			$data = json_decode($response,true);
			$place = $data['result'];
			dump($place);
			foreach ($place['reviews'] as $review) {
				$item = new rex_yfeed_item($this->streamId, $review['time']);
				$item->setContentRaw($review['text']);
				$item->setContent($review['text']);
				$item->setTitle($review['rating']);

				$item->setUrl($place['url']);
				$item->setDate(new DateTime("@".$review['time']));

				$item->setAuthor($review['author_name']);
				$item->setLanguage($review['language']);
				$item->setRaw(json_encode($review));

				$item->setMedia($review['profile_photo_url']);

				$this->updateCount($item);
				$item->save();
			} 
		}

    }
}
