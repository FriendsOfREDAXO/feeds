<?php

/**
 * feeds.
*/

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
    ->ensure();

$fk = new rex_sql_foreign_key("stream_id", rex::getTable('feeds_stream'), ["stream_id" => "id"], $onUpdate = rex_sql_foreign_key::CASCADE , $onDelete = rex_sql_foreign_key::CASCADE );

rex_sql_table::get(rex::getTable('feeds_item'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('stream_id', 'int(10) unsigned'))
    ->ensureColumn(new rex_sql_column('uid', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('type', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('title', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('content', 'text'))
    ->ensureColumn(new rex_sql_column('content_raw', 'text'))
    ->ensureColumn(new rex_sql_column('url', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('date', 'datetime'))
    ->ensureColumn(new rex_sql_column('author', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('language', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('media', 'longtext'))
    ->ensureColumn(new rex_sql_column('mediasource', 'text'))
    ->ensureColumn(new rex_sql_column('raw', 'text'))
    ->ensureColumn(new rex_sql_column('status', 'tinyint(1)', false, 1))
    ->ensureColumn(new rex_sql_column('changed_by_user', 'tinyint(1)'))
    ->ensureColumn(new rex_sql_column('createuser', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('updateuser', 'varchar(255)'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
    ->ensureForeignKey($fk)
    ->ensure();


//CHANGE content to utf8mb4_unicode_ci to display Emoti
$c = rex_sql::factory();
$c->setQuery('ALTER TABLE `' . rex::getTable('feeds_item') . '` CHANGE `content` `content` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

?>