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

use Exception;
use rex;
use rex_dir;
use rex_file;
use rex_logger;
use rex_path;
use rex_sql;
use Symfony\Component\HttpClient\HttpClient;

use function in_array;
use function is_array;
use function sprintf;

use const PATHINFO_EXTENSION;
use const PHP_URL_PATH;

class MediaHelper
{
    /**
     * Stores a media file from a URL in the addon's assets directory.
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
            $client = HttpClient::create([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; REDAXO Feeds Addon; +https://github.com/FriendsOfREDAXO/feeds)',
                ],
                'max_redirects' => 5,
                'timeout' => 10,
            ]);

            $response = $client->request('GET', $url);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $headers = $response->getHeaders();
            $mime = $headers['content-type'][0] ?? '';

            // Create unique filename using stream ID and item ID
            $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

            // If no extension found, try to get from mime type
            if (!$extension || !in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
                $extension = match ($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    'image/avif' => 'avif',
                    default => 'jpg',
                };
            }

            $filename = sprintf('%d_%s.%s', $streamId, $itemId, $extension);

            // Create directory if it doesn't exist
            $mediaPath = self::getMediaPath();
            if (!is_dir($mediaPath)) {
                rex_dir::create($mediaPath);
            }

            $filepath = $mediaPath . '/' . $filename;
            $content = $response->getContent();

            // Validate image
            if (@imagecreatefromstring($content)) {
                // Use rex_file for safe file operations
                if (rex_file::put($filepath, $content)) {
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
     * Gets the absolute path to the media directory.
     *
     * @return string Absolute path to media directory
     */
    public static function getMediaPath()
    {
        return rex_path::addonData('feeds', 'media');
    }

    /**
     * Deletes a media file.
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
     * Delete all media files for a specific stream.
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
     * Clean up orphaned media files that are no longer referenced in the database.
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
        $dbFiles = $sql->getArray('SELECT media_filename FROM ' . Item::table() . ' WHERE media_filename != ""');
        $dbFiles = array_column($dbFiles, 'media_filename');

        foreach ($files as $file) {
            $filename = basename($file);

            if (!in_array($filename, $dbFiles, true)) {
                if (rex_file::delete($file)) {
                    ++$deletedCount;
                }
            }
        }

        return $deletedCount;
    }
}
