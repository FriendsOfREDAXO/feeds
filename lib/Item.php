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
     * Read object stored in database.
     * @return Item|null Feeds item or null if not found
     */
    public static function get($id)
    {
        $item = new self();
        $item->primaryId = $id;

        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . self::table() . ' WHERE `id` = ' . $id);

        if ($sql->getRows()) {
            $item->changedByUser = '1' == $sql->getValue('changed_by_user') ? true : false;
            $item->exists = '1' == $sql->getValue('changed_by_user') ? false : true;
            $item->streamId = $sql->getValue('stream_id');
            $item->uid = $sql->getValue('uid');
            $item->title = $sql->getValue('title');
            $item->content = $sql->getValue('content');
            $item->contentRaw = $sql->getValue('content_raw');
            $item->url = $sql->getValue('url');
            $dateValue = $sql->getValue('date');
            if (!empty($dateValue)) {
                try {
                    $item->date = new DateTimeImmutable($dateValue);
                } catch (Exception $e) {
                    // Handle invalid date format gracefully
                    $item->date = null;
                    // Optionally log the error
                    // error_log('Invalid date format: ' . $dateValue);
                }
            } else {
                $item->date = null;
            }
            $item->author = $sql->getValue('author');
            $item->username = $sql->getValue('username');
            $item->language = $sql->getValue('language');
            $item->media_filename = $sql->getValue('media_filename');
            $item->raw = $sql->getValue('raw');
            $item->status = '1' == $sql->getValue('changed_by_user') ? true : false;
            return $item;
        }
        return null;
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
