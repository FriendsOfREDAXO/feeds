<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$addon = rex_addon::get('feeds');

// Ensure the media directory exists
$mediaPath = rex_path::addonData('feeds', 'media');
if (!is_dir($mediaPath)) {
    mkdir($mediaPath, 0777, true);
}

// Add new column if it doesn't exist
rex_sql_table::get(rex::getTable('feeds_item'))
    ->ensureColumn(new rex_sql_column('media_filename', 'varchar(255)'))
    ->alter();

// Get all items with media content
$sql = rex_sql::factory();
$items = $sql->getArray('SELECT id, stream_id, uid, media FROM ' . rex::getTable('feeds_item') . ' WHERE media IS NOT NULL AND media != ""');

if ($items) {
    foreach ($items as $item) {
        try {
            // Extract media data
            $mediaData = $item['media'];
            
            // Skip if no valid base64 data
            if (!preg_match('@^data:image/(.*?);base64,(.+)$@', $mediaData, $matches)) {
                continue;
            }
            
            // Get file extension from mime type
            $extension = $matches[1];
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }
            
            // Create filename
            $filename = sprintf('%d_%s.%s', $item['stream_id'], $item['uid'], $extension);
            $filepath = $mediaPath . '/' . $filename;
            
            // Decode and save file
            $imageData = base64_decode($matches[2]);
            if (file_put_contents($filepath, $imageData)) {
                // Update database record
                $update = rex_sql::factory();
                $update->setTable(rex::getTable('feeds_item'));
                $update->setWhere('id = :id', ['id' => $item['id']]);
                $update->setValue('media_filename', $filename);
                $update->setValue('media', null); // Clear old media data
                $update->update();
            }
            
        } catch (Exception $e) {
            // Log any errors but continue with next item
            rex_logger::logException($e);
        }
    }
}
