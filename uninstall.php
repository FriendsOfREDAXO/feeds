<?php 
# delete media effect
$sql = rex_sql::factory();
$sql->setTable(rex::getTablePrefix().'media_manager_type');
$sql->setWhere(['name'=>'feeds_thumb']);
$sql->delete();

$sql->setTable(rex::getTablePrefix().'media_manager_type_effect');
$sql->setWhere(['createuser'=>'feeds']);
$sql->delete();
