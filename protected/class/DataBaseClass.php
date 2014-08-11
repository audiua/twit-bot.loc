<?php

class DataBase extends Base{


	use DataBasePOST, DataBaseGET;

	public $connect;
	private $account_id;
	
	function __construct( $account_id = NULL ){
		$this->connect = DB::connect();

		if( $account_id ){
			$this->account_id = (int)$account_id;
		}
	}

	/**
	* Статический метод для проверки существования аккаунта по логину
	*
	* @param $screen_name string  логин аккаунта
	* @return boolean
	*/
	public static function issetAccount( $screen_name ){
		$sql = "SELECT 
					account_id
				FROM 
					accounts
				WHERE
					screen_name='$screen_name'";
		$account = DB::connect()->query( $sql )->fetch();

		if( !empty($account) ){
			return true;
		}

		return false;
	}

	/**
	* Очистка данных
	*
	* @param $value mixed  
	* @return mixed
	*/
	public static function clearData( $value ){

		if( is_array( $value ) ){
			foreach( $value as $key => &$val ){
				$value[$key] = trim($val);
			}
		} else {
			$value = trim( $value );
		}

		return $value;
	}

	/**
	* Функция записи логов в базу
	*
	* @return boolean
	*/
	public function writeLog( $exception, $mode ){
		$message = $this->connect->quote( $exception->getMessage() );
		$line = (int)$exception->getLine();
		$file = $this->connect->quote( $exception->getFile() );
		$time = time();
		
		$sql = "INSERT INTO 
					`logs`
				SET 
					`message`=$message,
					`in_line`=$line,
					`in_file`=$file,
					`in_time`=$time,
					`mode`=$mode";

		$success = $this->connect->exec( $sql );

		return $success;
	}

}