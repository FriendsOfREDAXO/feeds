<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfRedaxo\Feeds\Stream;

use FriendsOfRedaxo\Feeds\Item;

abstract class AbstractStream
{
    protected $typeParams = [];
    protected $streamId;
    protected $title;
    protected $etag;
    protected $lastModified;
    protected $countAdded = 0;
    protected $countUpdated = 0;
    protected $countNotUpdatedChangedByUser = 0;

    public function setTypeParams(array $params)
    {
        $this->typeParams = $params;
    }

    public function setStreamId($value)
    {
        $this->streamId = $value;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

	/**
	 * Get in Feeds database stored items belonging to this stream orderd by date.
	 * @param int $number Number of items to be returned
	 * @return Item[] Array with item objects
	 */
	public function getPreloadedItems($number = 5, $orderBy = 'date')
	{
		$items = [];
		$result = \rex_sql::factory();
		$result->setQuery('SELECT id FROM '. rex::getTablePrefix() .'feeds_item WHERE status = 1 AND stream_id = '. $this->streamId .' ORDER BY '.$orderBy.' DESC LIMIT 0, '. $number .';');

		for ($i = 0; $i < $result->getRows(); $i++) {
			$item = Item::get($result->getValue('id'));
			if($item != null) {
				$items[] = $item;
			}
			$result->next();
		}
		return $items;
	}
    
    public function getStreamId()
    {
        return $this->streamId;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setEtag($value)
    {
        $this->etag = $value;
    }

    public function setLastModified($value)
    {
        $this->lastModified = $value;
    }


    protected function registerExtensionPoint($stream_id) {
        return \rex_extension::registerPoint(new \rex_extension_point(
        'FEEDS_STREAM_FETCHED',
        null, ['stream_id' => $stream_id]
        ));
    }   

    public function getAddedCount()
    {
        return $this->countAdded;
    }

    public function getUpdateCount()
    {
        return $this->countUpdated;
    }

    public function getChangedByUserCount()
    {
        return $this->countNotUpdatedChangedByUser;
    }

    abstract public function getTypeName();

    abstract public function getTypeParams();

    abstract public function fetch();

    protected function updateCount(Item $item)
    {
        if ($item->changedByUser()) {
            ++$this->countNotUpdatedChangedByUser;
        } elseif ($item->exists()) {
            ++$this->countUpdated;
        } else {
            ++$this->countAdded;
        }
    }
}
