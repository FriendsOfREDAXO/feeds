<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_effect_feeds extends rex_effect_abstract
{
    public function execute()
    {
        $filename = $this->media->getMediaFilename();
        if (!$filename) {
            return;
        }

        // Check if this is a feeds file and extract the ID
        if (!preg_match('/^(\d+)\.feeds$/', $filename, $match)) {
            return;
        }
        $id = $match[1];

        // Get the item from database
        $sql = rex_sql::factory()
            ->setTable(rex_feeds_item::table())
            ->setWhere(['id' => $id, 'status' => 1])
            ->select('media_filename');

        if (!$sql->getRows()) {
            return;
        }
        
        $mediaFilename = $sql->getValue('media_filename');
        if (!$mediaFilename) {
            return;
        }

        // Get the physical file path
        $filepath = rex_feeds_media_helper::getMediaPath() . '/' . $mediaFilename;
        if (!file_exists($filepath)) {
            return;
        }

        // Get file extension
        $extension = strtolower(pathinfo($mediaFilename, PATHINFO_EXTENSION));
        
        // Create image resource based on file type
        $img = null;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $img = @imagecreatefromjpeg($filepath);
                break;
            case 'png':
                $img = @imagecreatefrompng($filepath);
                break;
            case 'gif':
                $img = @imagecreatefromgif($filepath);
                break;
            case 'webp':
                $img = @imagecreatefromwebp($filepath);
                break;
        }

        if (!$img) {
            return;
        }

        $media = $this->media;
        $media->setMediaPath(null);
        $media->setMediaFilename($filename);
        $media->setImage($img);
        $media->setFormat($extension);
        $media->setHeader('Content-Type', 'image/'.$extension);
        $media->refreshImageDimensions();
    }

    public function getName()
    {
        return rex_i18n::msg('feeds_media_manager_effect');
    }
}
