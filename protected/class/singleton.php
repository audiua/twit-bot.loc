<?php

class Singleton{

	const DB_NAME = 'my.db';
	private $_db;

	static private $_instance = null;

	private function __construct(){
		$this->_db = new SQLiteDatabase( self::DB_NAME );
	}

	private function __clone(){}

	static function getInstance(){

		if(self::$_instance == null){
			self::$_instance = new singleton();
		}

		return self::$_instance;
	}



}


$db = Singleton::getInstance();