<?php

/**
 * This file is part of the Feeds package.
 *
 * @author (c) Yakamara Media GmbH & Co. KG
 * @author thomas.blum@redaxo.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_feeds_stream
{
    private static $streams = [];

    /**
     * @param int $id
     *
     * @return rex_feeds_stream_abstract|null
     */
    public static function get($id)
    {
        $id = (int) $id;
        $sql = rex_sql::factory();
        $data = $sql->getArray('SELECT * FROM ' . self::table() . ' WHERE `id` = :id LIMIT 1', ['id' => $id]);

        if (!isset($data[0])) {
            return null;
        }

        return self::create($data[0]);
    }

    /**
     * @return rex_feeds_stream_abstract[]
     */
    public static function getAllActivated()
    {
        $sql = rex_sql::factory();
        $data = $sql->getArray('SELECT * FROM ' . self::table() . ' WHERE `status` = 1');

        return array_map('rex_feeds_stream::create', $data);
    }

    /**
     * @param array $data
     *
     * @return rex_feeds_stream_abstract
     * @throws rex_exception
     */
    public static function create(array $data)
    {
        if (empty($data['type'])) {
            throw new rex_exception('Unexpected feeds stream type');
        }

        $type = $data['type'];
        $streams = self::getSupportedStreams();
        if (!isset($streams[$type])) {
            throw new rex_exception('The feeds stream type is not supported');
        }

        /** @var rex_feeds_stream_abstract $stream */
        $stream = new $streams[$type]();
        if (isset($data['type_params'])) {
            $stream->setTypeParams(json_decode($data['type_params'], true));
        }
        $stream->setStreamId($data['id']);
        $stream->setTitle($data['title']);
        $stream->setEtag($data['etag']);
        $stream->setLastModified($data['last_modified']);

        return $stream;
    }

    public static function table()
    {
        return rex::getTable('feeds_stream');
    }

    public static function getSupportedStreams()
    {
        static $loaded = false;

        if ($loaded) {
            return self::$streams;
        }

        $files = glob(__DIR__ . '/stream/' . '*.php');
        if ($files) {
            foreach ($files as $file) {
                $type = substr(basename($file), 0, -4);
                self::$streams[$type] = 'rex_feeds_stream_'.$type;
            }
        }
        $loaded = true;

        return self::$streams;
    }

    public static function addStream($class, $type = null)
    {
        $type = $type ?: str_replace(['rex_', 'feeds_', 'stream_'], '', $class);
        self::$streams[$type] = $class;
    }
}
