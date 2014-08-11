<?php

require_once( $_SERVER['DOCUMENT_ROOT'] . '/protected/helpers/autoload.php' );

$followers = unserialize( file_get_contents( $_SERVER['DOCUMENT_ROOT'] . '/protected/data/followers.txt' ) );
$db = new DataBase();
$db->connect->beginTransaction();

foreach( $followers as $follower ){
	$sql = 
	"INSERT IGNORE INTO 
		users 
	SET 
		user_id=$follower,
		used_follower=CONCAT_WS(',',used_follower, 18 )";
	$db->connect->exec( $sql );
}

$db->connect->commit();