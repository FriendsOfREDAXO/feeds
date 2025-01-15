<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_feeds_media_helper
{
    /**
     * Stores a media file from a URL in the addon's data directory
     * 
     * @param string $url The URL of the media file
     * @param int $streamId The ID of the stream
     * @param string $itemId The unique ID of the item
     * @return string|null The filename if successful, null otherwise
     */
    public static function saveMediaFile($url, $streamId, $itemId)
    {
        if (!$url || !$streamId || !$itemId) {
            return null;
        }

        try {
            // Create unique filename using stream ID and item ID
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = sprintf('%d_%s.%s', $streamId, $itemId, $extension);
            
            // Create directory if it doesn't exist
            $mediaPath = self::getMediaPath();
            if (!is_dir($mediaPath)) {
                rex_dir::create($mediaPath, true);
            }

            // Download and save file
            $response = rex_socket::factoryUrl($url)->doGet();
            if ($response->isOk()) {
                $filepath = $mediaPath . '/' . $filename;
                
                // Use rex_file for safe file operations
                if (rex_file::put($filepath, $response->getBody())) {
                    @chmod($filepath, rex::getFilePerm());
                    return $filename;
                }
            }
        } catch (Exception $e) {
            rex_logger::logException($e);
        }

        return null;
    }

    /**
     * Gets the absolute path to the media directory
     * 
     * @return string Absolute path to media directory
     */
    public static function getMediaPath()
    {
        return rex_path::addonData('feeds', 'media');
    }

    /**
     * Deletes a media file
     * 
     * @param string $filename The filename to delete
     * @return bool TRUE if successful, FALSE otherwise
     */
    public static function deleteMediaFile($filename)
    {
        if (!$filename) {
            return false;
        }
        
        $filepath = self::getMediaPath() . '/' . $filename;
        return rex_file::delete($filepath);
    }

    /**
     * Delete all media files for a specific stream
     * 
     * @param int $streamId The stream ID
     * @return bool TRUE if successful, FALSE otherwise
     */
    public static function deleteStreamMedia($streamId)
    {
        if (!$streamId) {
            return false;
        }

        $mediaPath = self::getMediaPath();
        if (!is_dir($mediaPath)) {
            return true;
        }

        $pattern = $mediaPath . '/' . $streamId . '_*';
        $files = glob($pattern);
        
        if (!$files) {
            return true;
        }

        $success = true;
        foreach ($files as $file) {
            if (!rex_file::delete($file)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Clean up orphaned media files that are no longer referenced in the database
     * 
     * @return int Number of deleted files
     */
    public static function cleanupOrphanedMedia()
    {
        $mediaPath = self::getMediaPath();
        if (!is_dir($mediaPath)) {
            return 0;
        }

        $files = glob($mediaPath . '/*');
        if (!$files) {
            return 0;
        }

        $deletedCount = 0;
        $sql = rex_sql::factory();

        foreach ($files as $file) {
            $filename = basename($file);
            
            // Check if file is referenced in database
            $query = 'SELECT id FROM ' . rex_feeds_item::table() . ' WHERE media_filename = ?';
            $sql->setQuery($query, [$filename]);
            
            if (0 === $sql->getRows()) {
                if (rex_file::delete($file)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }
}
