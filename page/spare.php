<?php

// Logger::write( serialize($_POST) ,basename(__FILE__) , __LINE__ );
// file_put_contents('spare', '1');

// переопределаем название метода
$account = new Account( (int)$_POST['account_id'] );

// костыль до завтра 07,05
$action = (string)$_POST['action'];

// проверяем лимит запусков данного метода
if( isset( $_POST['params'] ) ){
	$param = $_POST['params'];
} else {
	$param = NULL;
}

// Logger::write( $action ,basename(__FILE__) , __LINE__ );

$limit = $account->checkLimit( $action, $param );
if( ! $limit ){
	Logger::write( $account->account_id . ' превышен лимит - ' . $action, __FILE__, __LINE__ );
	exit;
}

if( isset( $param ) && ! empty( $param ) ){
	$account->$action( $param );
} else {
	$account->$action();
}

die;
