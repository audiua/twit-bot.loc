<?php

class Logger{

	static function write( $str, $file = __FILE__, $line = __LINE__  ){
		$concatStr = "$str $file, $line, ".date('Y|m|d/H:i:s') . "\n";
		file_put_contents( $_SERVER['DOCUMENT_ROOT'] . '/protected/runtime/log/log-'.date('Y|m|d').'.txt' , $concatStr  , FILE_APPEND );
		// $str = "\tОшибка - $exception->getMessage(),\n 
		// 	стока - $exception->getLine(), \n
		// 	файл - $exception->getFile(), \n\n";

		// DataBase::writeLog( $str );
	}

}