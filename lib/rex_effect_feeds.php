<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use FriendsOfRedaxo\Feeds\Item;

class rex_effect_feeds extends rex_effect_abstract
{
    public function execute(): void
    {
        $filename = $this->media->getMediaFilename();
        if (!$filename) {
            return;
        }

        // Check if this is a feeds file and extract the ID
        if (preg_match('/^(\d+)\.feeds$/', $filename, $match)) {
            // Handle ID-based format
            $id = (int) $match[1];

            $sql = rex_sql::factory()
                ->setTable(Item::table())
                ->setWhere(['id' => $id, 'status' => 1])
                ->select('media_filename');

            if (!$sql->getRows()) {
                return;
            }

            $mediaFilename = (string) $sql->getValue('media_filename');
            if (!$mediaFilename) {
                return;
            }
        } else {
            // Handle direct filename format
            $sql = rex_sql::factory()
                ->setTable(Item::table())
                ->setWhere(['media_filename' => $filename, 'status' => 1])
                ->select('media_filename');

            if (!$sql->getRows()) {
                return;
            }

            $mediaFilename = $filename;
        }

        $mediaPath = rex_path::addonData('feeds', 'media/' . $mediaFilename);
        $this->media->setMediaPath($mediaPath);
    }

    public function getName(): string
    {
        return rex_i18n::msg('feeds_media_manager_effect');
    }
}
