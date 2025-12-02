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
use rex_addon;
use rex_cronjob;
use rex_i18n;
use Throwable;

use function count;
use function in_array;
use function sprintf;

class Cronjob extends rex_cronjob
{
    public function execute()
    {
        set_time_limit(0);
        $streams = [];
        $streamlist = [];

        if ($this->getParam('stream_list') && '' !== $this->getParam('stream_list')) {
            $streamlist = explode('|', $this->getParam('stream_list'));
        }

        foreach (Stream::getAllActivated() as $stream) {
            if (in_array($stream->getStreamId(), $streamlist) || !$this->getParam('stream_list') || '' === $this->getParam('stream_list')) {
                $streams[] = $stream;
            } else {
                continue;
            }
        }

        $errors = [];
        $countAdded = 0;
        $countUpdated = 0;
        $countNotUpdatedChangedByUser = 0;
        foreach ($streams as $stream) {
            try {
                $stream->fetch();
            } catch (Exception $e) {
                $errors[] = $stream->getTitle();
            } catch (Throwable $e) {
                $errors[] = $stream->getTitle();
            }
            $countAdded += $stream->getAddedCount();
            $countUpdated += $stream->getUpdateCount();
            $countNotUpdatedChangedByUser += $stream->getChangedByUserCount();
        }
        $this->setMessage(sprintf(
            '%d errors%s, %d items added, %d items updated, %d items not updated because changed by user',
            count($errors),
            $errors ? ' (' . implode(', ', $errors) . ')' : '',
            $countAdded,
            $countUpdated,
            $countNotUpdatedChangedByUser,
        ));
        return empty($errors);
    }

    public function getTypeName()
    {
        return rex_addon::get('feeds')->i18n('feeds_cronjob');
    }

    public function getParamFields()
    {
        $options = [];
        foreach (Stream::getAllActivated() as $stream) {
            $options[$stream->getStreamId()] = $stream->getTitle();
        }

        $fields[] = [
            'label' => rex_i18n::msg('feeds_stream_list'),
            'name' => 'stream_list',
            'type' => 'select',
            'attributes' => ['multiple' => 'multiple', 'data-live-search' => 'true'],
            'options' => $options,
            'notice' => rex_i18n::msg('feeds_stream_list_notice'),
        ];

        return $fields;
    }
}
