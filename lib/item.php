<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_feeds_item
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
        if ($streamId !== false && $uid !== false) {
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
                ]
            );

            if ($sql->getRows()) {
                if ($sql->getValue('changed_by_user') == '1') {
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
     * Read object stored in database
     * @param rex_feeds_item $rex_feeds_item
     * @return rex_feeds_item Feeds item or null if not found
     */
    public static function get($id)
    {
        $rex_feeds_item = new rex_feeds_item();
        $rex_feeds_item->primaryId = $id;

        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . self::table() . ' WHERE `id` = ' . $id);

        if ($sql->getRows()) {
            $rex_feeds_item->changedByUser = $sql->getValue('changed_by_user') == '1' ? true : false;
            $rex_feeds_item->exists = $sql->getValue('changed_by_user') == '1' ? false : true;
            $rex_feeds_item->streamId = $sql->getValue('stream_id');
            $rex_feeds_item->uid = $sql->getValue('uid');
            $rex_feeds_item->title = $sql->getValue('title');
            $rex_feeds_item->content = $sql->getValue('content');
            $rex_feeds_item->contentRaw = $sql->getValue('content_raw');
            $rex_feeds_item->url = $sql->getValue('url');
            $rex_feeds_item->date = new DateTimeImmutable($sql->getValue('date'));
            $rex_feeds_item->author = $sql->getValue('author');
            $rex_feeds_item->username = $sql->getValue('username');
            $rex_feeds_item->language = $sql->getValue('language');
            $rex_feeds_item->media_filename = $sql->getValue('media_filename');
            $rex_feeds_item->raw = $sql->getValue('raw');
            $rex_feeds_item->status = $sql->getValue('changed_by_user') == '1' ? true : false;
            return $rex_feeds_item;
        } else {
            return null;
        }
    }

    /**
     * Get item title
     * @return string title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get raw content
     * @return string Raw content
     */
    public function getContentRaw()
    {
        return $this->contentRaw;
    }

    /**
     * Get content
     * @return string Content
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * Get database Id
     * @return int Id
     */
    public function getId()
    {
        return $this->primaryId;
    }
    
    /**
     * Get URL
     * @return string URL
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get datetime object
     * @return DateTimeInterface Date
     */
    public function getDateTime()
    {
        return $this->date;
    }

    /**
     * Get author
     * @return string Authors name
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Get username
     * @return string username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get language
     * @return string Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Get media filename
     * @return string|null Media filename
     */
    public function getMediaFilename()
    {
        return $this->media_filename;
    }

    /**
     * Get media manager url
     * @param string $type Media Manager type
     * @param bool $escape Whether to escape the URL
     * @return string|null Media Manager URL
     */
    public function getMediaManagerUrl($type, $escape = true)
    {
        if (!rex_addon::get('media_manager')->isAvailable()) {
            throw new rex_exception(__CLASS__.'::getMediaManagerUrl() can be used only when media_manager is activated.');
        }

        if (!$this->primaryId) {
            return null;
        }

        return rex_media_manager::getUrl($type, $this->primaryId .'.feeds', $this->date ? $this->date->getTimestamp() : null, $escape);
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

    public function setDate(DateTimeInterface $value)
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
     * Set media from URL
     * @param string $url URL of the media file
     */
    public function setMedia($url)
    {
        // Delete old media file if exists
        if ($this->media_filename) {
            $filepath = rex_feeds_media_helper::getMediaPath() . '/' . $this->media_filename;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Save new media file
        $this->media_filename = rex_feeds_media_helper::saveMediaFile($url, $this->streamId, $this->uid);
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
        if ($this->media_filename !== null) {
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
            ['stream_id' => $this->streamId, 'uid' => $this->uid]
        ));
    }
}
