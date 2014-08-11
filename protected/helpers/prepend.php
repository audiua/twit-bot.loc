<?php
require_once( $_SERVER['DOCUMENT_ROOT'] . '/protected/helpers/autoload.php' );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/protected/helpers/myExceptionHandler.php' );
if( function_exists( 'set_time_limit' ) ){
	set_time_limit( 0 );
} else {
	$this->writeLog( 'set_time_limit not work' );
	return false;
}	