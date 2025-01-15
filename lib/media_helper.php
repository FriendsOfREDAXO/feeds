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
                mkdir($mediaPath, 0777, true);
            }

            // Download and save file
            $response = rex_socket::factoryUrl($url)->doGet();
            if ($response->isSuccess()) {
                $filepath = $mediaPath . '/' . $filename;
                if (file_put_contents($filepath, $response->getBody())) {
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
     * @return string
     */
    public static function getMediaPath()
    {
        return rex_path::addonData('feeds', 'media');
    }

    /**
     * Gets the web-accessible URL for a media file
     * 
     * @param string $filename The filename
     * @return string|null
     */
    public static function getMediaUrl($filename)
    {
        if (!$filename) {
            return null;
        }
        return rex_url::addonData('feeds', 'media/' . $filename);
    }

    /**
     * Deletes a media file
     * 
     * @param string $filename The filename to delete
     * @return bool
     */
    public static function deleteMediaFile($filename)
    {
        if (!$filename) {
            return false;
        }
        
        $filepath = self::getMediaPath() . '/' . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}
