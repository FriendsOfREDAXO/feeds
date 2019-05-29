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

class rex_effect_feeds extends rex_effect_abstract
{
    public function execute()
    {
        $filename = rex_media_manager::getMediaFile();

        if (!preg_match('/^(\d+)\.feeds$/', $filename, $match)) {
            return;
        }

        $id = $match[1];

        $sql = rex_sql::factory()
            ->setTable(rex_feeds_item::table())
            ->setWhere(['id' => $id, 'status' => 1])
            ->select('media');

        if (!$sql->getRows()) {
            return;
        }

        $data = $sql->getValue('media');

        if (!$data || !preg_match('@^data:image/(.*?);base64,(.+)$@', $data, $match)) {
            return;
        }

        $img = @imagecreatefromstring(base64_decode($match[2]));

        if (!$img) {
            return;
        }

        $media = $this->media;
        $media->setMediaPath(null);
        $media->setMediaFilename($filename);
        $media->setImage($img);
        $media->setFormat($match[1]);
        $media->setHeader('Content-Type', 'image/'.$match[1]);
        $media->refreshImageDimensions();
    }

    public function getName()
    {
        return rex_i18n::msg('feeds_media_manager_effect');
    }
}
