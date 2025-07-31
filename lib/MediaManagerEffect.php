<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfRedaxo\Feeds;

class MediaManagerEffect extends \rex_effect_abstract
{
    public function execute()
    {
        $filename = $this->media->getMediaFilename();
        if (!$filename) {
            return;
        }

        // Check if this is a feeds file and extract the ID
        if (preg_match('/^(\d+)\.feeds$/', $filename, $match)) {
            // Handle ID-based format
            $id = $match[1];
            
            // Get the item from database
            $sql = \rex_sql::factory()
                ->setTable(Item::table())
                ->setWhere(['id' => $id, 'status' => 1])
                ->select('media_filename');
                
            if (!$sql->getRows()) {
                return;
            }
            
            $mediaFilename = $sql->getValue('media_filename');
            if (!$mediaFilename) {
                return;
            }
        } else {
            // Handle direct filename format
            $sql = \rex_sql::factory()
                ->setTable(Item::table())
                ->setWhere(['media_filename' => $filename, 'status' => 1])
                ->select('media_filename');
                
            if (!$sql->getRows()) {
                return;
            }
            
            $mediaFilename = $filename;
        }

        // Set the media path to the feeds media file
        $mediaPath = \rex_path::addonData('feeds', 'media/' . $mediaFilename);
        $this->media->setMediaPath($mediaPath);
    }

    public function getName()
    {
        return \rex_i18n::msg('feeds_media_manager_effect');
    }
}
