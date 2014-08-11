<?php

function __autoload( $class ) {
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    $class .= 'Class.php';

    // массив путей к классам
    $path = array(
    	$documentRoot . '/protected/class/',
    	$documentRoot . '/protected/class/traits/',
    	$documentRoot . '/protected/helpers/',
    	$documentRoot . '/protected/config/',
	);
	unset( $documentRoot );

	$classFile = '';

	foreach( $path as $onePath ){
		if( file_exists( $onePath . $class ) ){
			$classFile = $onePath . $class;
			break;
		}
	}

	if( empty( $classFile ) ){
		throw new Exception( "Файл класса $class не найден" );
	}
	unset( $path );
	unset( $onePath );
	unset( $class );

	require_once $classFile;
}
