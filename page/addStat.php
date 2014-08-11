<?php
$db = new DataBase();
$allAccount = $db->get( 'accounts' );
$summa=0;
foreach( $allAccount as $account ){
	$obj = new Account( $account->account_id );
	$obj->addStatistics();
}