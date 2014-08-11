<?php

/**
* Singleton класс соединенияс базой данных
*
* @author audiua <audiua@yandex.ru>
*/
class DB{

	private static $instance = NULL;
	private function __construct(){}
	private function __clone(){}

	/**
	* Статический метод возвращает единственное установленное соединение
	*
	* @return PDO object
	*/
	public static function connect(){
		if( ! self::$instance ){
			$config = self::getDNS();

			switch( Config::DB_DRIVER ){

				// подключения к базе mysql
				case 'mysql': 

					$config['options'] = array(
						PDO::ATTR_PERSISTENT => true,
						PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8'
					);

					self::$instance = new PDO( 
						$config['conn'], 
						$config['user'], 
						$config['pass'], 
						$config['options'] 
					);

					self::$instance->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

					return self::$instance;

				// подключение к sqlite
				case 'sqlite':
					throw new Exception( 'Нет настроек для базы данных '. Config::DB_DRIVER .', база не поддерживается' );
					break;

				// сюда попадаем если используем не поддержуемую базуданных
				default: 
					throw new Exception( 'Нет настроек для базы данных '. Config::DB_DRIVER .', база не поддерживается' );
			}
		}
		unset( $config );


		return self::$instance;
	}

	/**
	* Метод возвращает DNS для текущей базы данных
	*
	* @return array готовый DNS
	*/
	private static function getDNS(){

		if( file_exists( $confFile = $_SERVER['DOCUMENT_ROOT'] . '/protected/config/' . Config::DB_DRIVER . 'Config.ini' ) ){
			$conf = parse_ini_file( $confFile );
			if( empty( $conf ) ){
				throw new Exception( 'Файл настройки для базы данных - '.Config::DB_DRIVER. ' пустой или нет прав на чтение' );
			}

			return $conf;

		}

		throw new Exception( 'Нет файла настроек для базы данных ' . Config::DB_DRIVER );
	}
}