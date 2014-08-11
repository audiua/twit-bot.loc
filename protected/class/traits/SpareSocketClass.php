<?php

/**
* Трейт с методом сокета
*
*
*/
trait SpareSocket{

	/**
	* Метод установления сокет соединения
	*
	* @param $file array
	* @return booloean
	*/
	function spareSocket( $file ){
		// Logger::write( serialize($file) ,basename(__FILE__) , __LINE__ );
		// file_put_contents('spare', 'aaa');
		
		$parts = parse_url( Config::URL . Config::SPARE_FILE_PATH );
		// return $parts;
		// $this->writeLog($file);
		if ( !$fp = fsockopen( $parts['host'], isset($parts['port']) ? $parts['port'] : 80) ){
	        return false;
		}

	    $data = http_build_query( $file, '', '&' );
	    fwrite( $fp, "POST " . ( !empty($parts['path']) ? $parts['path'] : '/' ) . " HTTP/1.1\r\n" );
	    fwrite( $fp, "Host: " . $parts['host'] . "\r\n" );
	    fwrite( $fp, "Content-Type: application/x-www-form-urlencoded\r\n" );
	    fwrite( $fp, "Content-Length: " . strlen( $data ) . "\r\n" );
	    fwrite( $fp, "Connection: Close\r\n\r\n" );
	    fwrite( $fp, $data );
	    fclose( $fp );

	    return true;
	}

}