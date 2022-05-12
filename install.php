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

rex_sql_table::get(rex::getTable('feeds_stream'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('namespace', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('type', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('type_params', 'text'))
    ->ensureColumn(new rex_sql_column('title', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('image', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('etag', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('last_modified', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('status', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('createuser', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('updateuser', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
    ->removeForeignKey('rex_feeds_stream_ibfk_1')
    ->ensure();

rex_sql_table::get(rex::getTable('feeds_item'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('stream_id', 'int(10) unsigned'))
    ->ensureColumn(new rex_sql_column('uid', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('type', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('title', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('content', 'text', true))
    ->ensureColumn(new rex_sql_column('content_raw', 'text'))
    ->ensureColumn(new rex_sql_column('url', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('date', 'datetime'))
    ->ensureColumn(new rex_sql_column('author', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('username', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('language', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('media', 'longtext'))
    ->ensureColumn(new rex_sql_column('mediasource', 'text'))
    ->ensureColumn(new rex_sql_column('raw', 'text'))
    ->ensureColumn(new rex_sql_column('status', 'tinyint(1)', false, '1'))
    ->ensureColumn(new rex_sql_column('changed_by_user', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('createuser', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('updateuser', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
    ->ensureIndex(new rex_sql_index('stream_id', ['stream_id']))
    ->ensureForeignKey(new rex_sql_foreign_key('rex_feeds_item_ibfk_1', rex::getTable('feeds_stream'), ['stream_id' => 'id'], rex_sql_foreign_key::RESTRICT, rex_sql_foreign_key::CASCADE))
    ->ensure();

//CHANGE content to utf8mb4_unicode_ci to display Emoticons
$c = rex_sql::factory();
$c->setQuery('ALTER TABLE `' . rex::getTable('feeds_item') . '` CHANGE `content` `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$c->setQuery('ALTER TABLE `' . rex::getTable('feeds_item') . '` CHANGE `title` `title` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
