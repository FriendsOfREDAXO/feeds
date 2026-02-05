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

use DateTimeImmutable;
use Exception;
use rex;
use rex_addon;
use rex_exception;
use rex_extension;
use rex_extension_point;
use rex_media_manager;
use rex_sql;

use function defined;

use const PATHINFO_EXTENSION;

class Item
{
    private $streamId;
    private $uid;
    private $title;
    private $type;
    private $content;
    private $contentRaw;
    private $url;
    /** @var DateTimeInterface */
    private $date;
    private $author;
    private $username;
    private $language;
    private $media_filename;
    private $mediasource;
    private $raw;

    private $primaryId;
    private $debug = false;
    private $changedByUser;
    private $exists;
    private $status;

    /**
     * Constructor. If paramters are omitted, empty object is created.
     * @param $streamId Stream ID
     * @param $uid UID
     */
    public function __construct($streamId = false, $uid = false)
    {
        if (false !== $streamId && false !== $uid) {
            $this->primaryId = 0;
            $this->streamId = (int) $streamId;
            $this->uid = $uid;
            $this->exists = false;
            $this->changedByUser = false;

            $sql = rex_sql::factory();
            $sql->setQuery(
                '
                SELECT      `id`,
                    `changed_by_user`
                FROM        ' . self::table() . '
                WHERE       `stream_id` = :stream_id
                AND     `uid` = :uid
                LIMIT       1',
                [
                    'stream_id' => $this->streamId,
                    'uid' => $this->uid,
                ],
            );

            if ($sql->getRows()) {
                if ('1' == $sql->getValue('changed_by_user')) {
                    $this->changedByUser = true;
                } else {
                    $this->primaryId = $sql->getValue('id');
                    $this->exists = true;
                }
            }
        }
    }

    public static function table()
    {
        return rex::getTable('feeds_item');
    }

    /**
     * Check if URL already exists in database for given stream.
     * @param string $url URL to check
     * @param int $streamId Stream ID to check within
     * @param int|null $excludeId Optional Item ID to exclude from check (for updates)
     * @return bool True if duplicate found, false otherwise
     */
    public static function isDuplicate($url, $streamId, $excludeId = null)
    {
        if (empty($url)) {
            return false;
        }
        
        $sql = rex_sql::factory();
        $query = 'SELECT COUNT(*) as count FROM ' . self::table() . ' WHERE `url` = :url AND `stream_id` = :stream_id';
        $params = ['url' => $url, 'stream_id' => (int) $streamId];
        
        if ($excludeId !== null) {
            $query .= ' AND `id` != :exclude_id';
            $params['exclude_id'] = (int) $excludeId;
        }
        
        $sql->setQuery($query, $params);
        return $sql->getValue('count') > 0;
    }

    /**
     * Read object stored in database.
     * @return Item|null Feeds item or null if not found
     */
    public static function get($id)
    {
        $item = new self();
        $item->primaryId = (int) $id;

        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . self::table() . ' WHERE `id` = :id', ['id' => $item->primaryId]);

        if ($sql->getRows()) {
            return self::createFromDbRow($sql->getRow());
        }
        return null;
    }

    /**
     * Create Item instance from database row.
     * @internal
     * @return Item
     */
    public static function createFromDbRow(array $row)
    {
        $item = new self();
        $item->primaryId = $row['id'] ?? 0;
        $item->changedByUser = '1' == ($row['changed_by_user'] ?? '0');
        $item->exists = '1' != ($row['changed_by_user'] ?? '0');
        $item->streamId = $row['stream_id'] ?? 0;
        $item->uid = $row['uid'] ?? '';
        $item->title = $row['title'] ?? '';
        $item->content = $row['content'] ?? '';
        $item->contentRaw = $row['content_raw'] ?? '';
        $item->url = $row['url'] ?? '';
        $dateValue = $row['date'] ?? null;
        if (!empty($dateValue)) {
            try {
                $item->date = new DateTimeImmutable($dateValue);
            } catch (Exception $e) {
                $item->date = null;
            }
        } else {
            $item->date = null;
        }
        $item->author = $row['author'] ?? '';
        $item->username = $row['username'] ?? '';
        $item->language = $row['language'] ?? '';
        $item->media_filename = $row['media_filename'] ?? null;
        $item->raw = $row['raw'] ?? '';
        $item->status = '1' == ($row['status'] ?? '1');
        return $item;
    }

    /**
     * Get item title.
     * @return string title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get raw content.
     * @return string Raw content
     */
    public function getContentRaw()
    {
        return $this->contentRaw;
    }

    /**
     * Get content.
     * @return string Content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get database Id.
     * @return int Id
     */
    public function getId()
    {
        return $this->primaryId;
    }

    /**
     * Get URL.
     * @return string URL
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get datetime object.
     * @return \DateTimeInterface Date
     */
    public function getDateTime()
    {
        return $this->date;
    }

    /**
     * Get author.
     * @return string Authors name
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Get username.
     * @return string username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get language.
     * @return string Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Get media filename.
     * @return string|null Media filename
     */
    public function getMediaFilename()
    {
        return $this->media_filename;
    }

    /**
     * Get media source URL.
     * @return string|null Media source URL
     */
    public function getMediaSource()
    {
        return $this->mediasource;
    }

    /**
     * Get media manager url.
     * @param string $type Media Manager type
     * @param bool $useOriginalFilename Whether to use the original filename instead of ID.feeds
     * @param bool $escape Whether to escape the URL
     * @return string|null Media Manager URL
     */
    public function getMediaManagerUrl($type, $useOriginalFilename = false, $escape = true)
    {
        if (!rex_addon::get('media_manager')->isAvailable()) {
            throw new rex_exception(__CLASS__ . '::getMediaManagerUrl() can be used only when media_manager is activated.');
        }

        if (!$this->primaryId) {
            return null;
        }

        $filename = $useOriginalFilename && $this->media_filename
            ? $this->media_filename
            : $this->primaryId . '.feeds';

        return rex_media_manager::getUrl(
            $type,
            $filename,
            $this->date ? $this->date->getTimestamp() : null,
            $escape,
        );
    }

    /**
     * Get media information including dimensions and format.
     * @param string $type Media Manager type
     * @throws rex_exception If media_manager addon is not available
     * @return array|null Array containing media information or null if not available
     */
    public function getMediaInfo($type)
    {
        if (!rex_addon::get('media_manager')->isAvailable()) {
            throw new rex_exception(__CLASS__ . '::getMediaInfo() can be used only when media_manager is activated.');
        }

        if (!$this->primaryId) {
            return null;
        }

        // Get the actual media filename from the database
        if (!$this->media_filename) {
            return null;
        }

        // Use the actual media filename instead of {id}.feeds
        $media = rex_media_manager::create($type, $this->media_filename)->getMedia();

        if (!$media) {
            return null;
        }

        return [
            'format' => pathinfo($this->media_filename, PATHINFO_EXTENSION),
            'width' => $media->getWidth(),
            'height' => $media->getHeight(),
            'filename' => $this->media_filename,
            'type' => $type,
        ];
    }

    /**
     * Get raw data.
     * @return string JSON encoded raw data
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * Prüft, ob ein Medium vorhanden ist.
     * @return bool
     */
    public function hasMedia()
    {
        return (bool) $this->media_filename;
    }

    /**
     * Liefert den Inhalt als reinen Text (HTML entfernt), bereinigt von überflüssigen Whitespaces.
     * @return string
     */
    public function getPlainTextContent()
    {
        $text = $this->content ?? $this->contentRaw ?? '';
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    /**
     * Kürzt den Inhalt intelligent.
     * @param int $length Maximale Länge in Zeichen
     * @param bool $endOnSentence Ob bis zum Satzende gekürzt werden soll (wenn möglich)
     * @param string $ellipsis Auslassungszeichen
     * @return string
     */
    public function getTruncatedContent($length = 200, $endOnSentence = true, $ellipsis = '…')
    {
        $text = $this->getPlainTextContent();
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $length);

        if ($endOnSentence) {
            $posArr = [mb_strrpos($truncated, '.'), mb_strrpos($truncated, '!'), mb_strrpos($truncated, '?')];
            $posArr = array_map(function ($p) { return false === $p ? -1 : $p; }, $posArr);
            $lastPos = max($posArr);
            if ($lastPos > 0) {
                return rtrim(mb_substr($truncated, 0, $lastPos + 1)) . $ellipsis;
            }

            $punctPos = [mb_strrpos($truncated, ','), mb_strrpos($truncated, ';')];
            $punctPos = array_map(function ($p) { return false === $p ? -1 : $p; }, $punctPos);
            $lastP = max($punctPos);
            if ($lastP > 0) {
                return rtrim(mb_substr($truncated, 0, $lastP + 1)) . $ellipsis;
            }
        }

        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace > 0) {
            return rtrim(mb_substr($truncated, 0, $lastSpace)) . $ellipsis;
        }

        return rtrim($truncated) . $ellipsis;
    }

    /**
     * Entfernt Emojis aus einem Text.
     * @param string $text
     * @return string
     */
    public static function removeEmojis($text)
    {
        if (!$text) {
            return '';
        }

        $regexPatterns = [
            '/[\x{1F600}-\x{1F64F}]/u', // Emoticons
            '/[\x{1F300}-\x{1F5FF}]/u', // Misc Symbols and Pictographs
            '/[\x{1F680}-\x{1F6FF}]/u', // Transport and Map
            '/[\x{1F1E6}-\x{1F1FF}]/u', // Flags
            '/[\x{2600}-\x{26FF}]/u',   // Misc symbols
            '/[\x{2700}-\x{27BF}]/u',   // Dingbats
        ];

        foreach ($regexPatterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }

        // remove remaining invisible characters
        $text = preg_replace('/[\p{Cf}]+/u', '', $text);

        return $text;
    }

    /**
     * Entfernt Hashtags aus einem Text.
     * @param string $text
     * @return string
     */
    public static function removeHashtags($text)
    {
        if (!$text) {
            return '';
        }

        // Entfernt #hashtag oder #hashtag123, lässt normale Wörter stehen
        return preg_replace('/#([\p{L}0-9_\-]+)/u', '', $text);
    }

    /**
     * Sanitizes content by removing emojis/hashtags and trimming.
     * @param array $options [remove_emojis => bool, remove_hashtags => bool, normalize_whitespace => bool]
     * @return string
     */
    public function sanitizeContent(array $options = [])
    {
        $defaults = [
            'remove_emojis' => true,
            'remove_hashtags' => false,
            'normalize_whitespace' => true,
        ];
        $opts = array_merge($defaults, $options);

        $text = $this->getPlainTextContent();

        if ($opts['remove_emojis']) {
            $text = self::removeEmojis($text);
        }
        if ($opts['remove_hashtags']) {
            $text = self::removeHashtags($text);
        }
        if ($opts['normalize_whitespace']) {
            $text = preg_replace('/\s+/u', ' ', $text);
        }

        return trim($text);
    }

    /**
     * Extrahiert einen Titel aus dem Content. Wenn ein "Stop-Zeichen" (z.B. "::") verwendet wird,
     * wird alles vor dem Stop-Zeichen als Titel genommen. Ansonsten kann die erste Zeile oder ein
     * gekürzter Ausschnitt als Titel dienen.
     * @param string $stopSign Stop-Zeichen, das das Ende des Titels markiert
     * @param int $maxLength Maximale Länge des extrahierten Titels
     * @param bool $fallbackToFirstLine Falls true, nimmt erste Zeile wenn kein Stop-Sign gefunden
     * @return string
     */
    public function extractTitleFromContent($stopSign = '::', $maxLength = 120, $fallbackToFirstLine = true)
    {
        $text = $this->getPlainTextContent();
        if (!$text) {
            return '';
        }

        // Prüfe explizites Stop-Zeichen
        if ($stopSign && false !== ($pos = mb_strpos($text, $stopSign))) {
            $title = mb_substr($text, 0, $pos);
            $title = trim($title);
            return mb_substr($title, 0, $maxLength);
        }

        // Fallback: erste Zeile
        if ($fallbackToFirstLine) {
            $lines = preg_split('/\r?\n/', $text);
            if (!empty($lines[0])) {
                $first = trim($lines[0]);
                if (mb_strlen($first) <= $maxLength) {
                    return $first;
                }
                // sonst kürzen sauber an Wortgrenze
                $tr = mb_substr($first, 0, $maxLength);
                $lastSpace = mb_strrpos($tr, ' ');
                if ($lastSpace > 0) {
                    return rtrim(mb_substr($tr, 0, $lastSpace));
                }
                return rtrim($tr);
            }
        }

        // Letzte Möglichkeit: vorhandenen Titel nutzen oder leeren String
        return $this->title ? mb_substr(trim($this->title), 0, $maxLength) : '';
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function setContentRaw($value)
    {
        $this->contentRaw = $value;
    }

    public function setContent($value)
    {
        $this->content = $value;
    }

    public function setUrl($value)
    {
        $this->url = $value;
    }

    public function setDate(\DateTimeInterface $value)
    {
        $this->date = $value;
    }

    public function setAuthor($value)
    {
        $this->author = $value;
    }

    public function setUsername($value)
    {
        $this->username = $value;
    }

    public function setLanguage($value)
    {
        $this->language = $value;
    }

    /**
     * Set media from URL.
     * @param string $url URL of the media file
     */
    public function setMedia($url)
    {
        // Delete old media file if exists
        if ($this->media_filename) {
            $filepath = MediaHelper::getMediaPath() . '/' . $this->media_filename;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Save new media file
        $this->media_filename = MediaHelper::saveMediaFile($url, $this->streamId, $this->uid);
    }

    public function setMediaSource($value)
    {
        $this->mediasource = $value;
    }

    public function setRaw($value)
    {
        $this->raw = json_encode((array) $value);
    }

    public function setOnline($online)
    {
        $this->status = (bool) $online;
    }

    public function isOnline()
    {
        return $this->status;
    }

    public function exists()
    {
        return $this->exists;
    }

    public function changedByUser()
    {
        return $this->changedByUser;
    }

    public function save()
    {
        if ($this->changedByUser) {
            return;
        }

        $sql = rex_sql::factory();
        $sql->setDebug($this->debug);
        $sql->setTable(self::table());

        if ($this->title) {
            $sql->setValue('title', $this->title);
        }
        if ($this->type) {
            $sql->setValue('type', $this->type);
        }
        if ($this->content) {
            $sql->setValue('content', $this->content);
        }
        if ($this->contentRaw) {
            $sql->setValue('content_raw', $this->contentRaw);
        }
        if ($this->url) {
            $sql->setValue('url', $this->url);
        }
        if ($this->date) {
            $sql->setValue('date', $this->date->format('Y-m-d H:i:s'));
        }
        if ($this->author) {
            $sql->setValue('author', $this->author);
        }
        if ($this->username) {
            $sql->setValue('username', $this->username);
        }
        if ($this->language) {
            $sql->setValue('language', $this->language);
        }
        if (null !== $this->media_filename) {
            $sql->setValue('media_filename', $this->media_filename);
        }
        if ($this->mediasource) {
            $sql->setValue('mediasource', $this->mediasource);
        }
        if ($this->raw) {
            $sql->setValue('raw', $this->raw);
        }

        if (rex::getUser()) {
            $user = rex::getUser()->getLogin();
        } else {
            $user = defined('REX_CRONJOB_SCRIPT') && REX_CRONJOB_SCRIPT ? 'cronjob_script' : 'frontend';
        }

        if ($this->exists) {
            $where = '`id` = :id AND `uid` = :uid';
            $params = ['id' => $this->primaryId, 'uid' => $this->uid];
            $sql->setWhere($where, $params);
            $sql->addGlobalUpdateFields($user);
            $sql->update();
        } else {
            $sql->setValue('uid', $this->uid);
            $sql->setValue('stream_id', $this->streamId);
            $sql->addGlobalCreateFields($user);
            $sql->addGlobalUpdateFields($user);
            $sql->insert();
        }

        rex_extension::registerPoint(new rex_extension_point(
            'FEEDS_ITEM_SAVED',
            null,
            ['stream_id' => $this->streamId, 'uid' => $this->uid],
        ));
    }
}
