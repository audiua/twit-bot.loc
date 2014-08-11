<?php

// перехват все исключений
function myExceptionHandler( Exception $exception, $mode = 1 ){

	print_r( $exception );
	die;
	// запись исключений в базу
	$db = new DataBase();
	$db->writeLog( $exception, $mode );
}

set_exception_handler( 'myExceptionHandler' );