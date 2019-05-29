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

use Instagram\Instagram;
use InstagramScraper\Instagram as InstagramScraper;

class rex_feeds_stream_instagram_tag extends rex_feeds_stream_instagram_abstract
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_instagram_tag');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_instagram_tag_name'),
                'name' => 'tag',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_instagram_tag_without_prefix'),
            ],
            [
                'label' => rex_i18n::msg('feeds_instagram_count'),
                'name' => 'count',
                'type' => 'select',
                'options' => [5 => 5, 10 => 10, 15 => 15, 20 => 20, 30 => 30, 50 => 50, 75 => 75, 100 => 100],
                'default' => 10,
            ],
        ];
    }

    protected function fetchItemsFromOfficialApi(Instagram $instagram)
    {
        return $instagram->getTagMedia($this->typeParams['tag'], ['count' => $this->typeParams['count']]);
    }

    protected function fetchItemsFromFrontendApi(InstagramScraper $instagram)
    {
        return $instagram->getMediasByTag($this->typeParams['tag'], $this->typeParams['count']);
    }
}
