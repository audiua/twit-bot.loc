<?php

/**
* трейт для получения данных с сервера твиттера
* может использоваться классами с установленными соединениями с сервером твиттера
* и с обьектом Account класса
*
* @author audiua <audiua@yandex.ru>
*/
trait TwitterAPIGET{

	/**
	* Получаем юзеров аккунта
	* Данная функция ожидает наличие свойств: $this->twitter, $this->account в вызываемом классе
	*
	* @param $mode string  модификация запрашиваемых юзеров ('followers, friends')
	* @return array
	*/
	function getUsers( $mode ){

		// TODO кешировать данный результат на сутки
		$allUsers = array();
		$cursor = -1;
		do{
			$UsersObj = $this->twitter->get( 
				$mode . '/ids', 
				array( 
					'cursor' => $cursor, 
					'screen_name' => $this->account->screen_name,
					'count' => 5000 
				) 
			);
			$this->writeLimitRate( 'getUsers' );

			$cursor = $UsersObj->next_cursor;
			$allUsers = array_merge( $allUsers, $UsersObj->ids );

		} while( $cursor > 0 );

		if( $this->twitter->http_code == 200 ){
			return $UsersObj;
		} else {
			throw new Exception( 'Нет подключения к твиттер апи' );
		}
	}


	/**
	* Получаем юзеров наших юзеров
	* Данная функция ожидает наличие свойств: $this->twitter в вызываемом классе
	*
	* @param $userId int id юзера, которого будем получать его юзеров
	* @param $cursor int по умолчанию -1, нужен для указания смещения поиска юзров
	* @param $mode string  модификация запрашиваемых юзеров ('follower, friends'), по умолчанию 'followers'
	* @return array 200 юзеров
	*/
	function getUsersList( $userId, $cursor = -1, $mode = 'followers' ){
		$userId = 125140402;
		// TODO кешировать данный результат на сутки
		$mode = (string)$mode;
		$UsersObj = $this->twitter->get( 
			$mode . '/list', 
			array( 
				'cursor' => $cursor, 
				'id' => $userId,
				'skip_status' => true,
				'include_user_entities' => false,
				'count' => 200
			) 
		);
		$this->writeLimitRate( 'getUsersList' );

		if( $this->twitter->http_code == 200 ){
			return $UsersObj;
		} else {
			return array();

			// TODO залогировать ошибку
			// throw new Exception( 'Нет подключения к твиттер апи' );
		}
	}

	/**
	* статический метод получения юзера по логину или по id
	*
	* @param $param array  данные с формы
	* @return object   обьект юзера
	*/
	public static function showAccount( $param ){
		$connect = new TwitterOAuth( 
			$param['costumer_key'],
			$param['costumer_secret'],
			$param['access_token'],
			$param['access_token_secret']
		);

		if( isset( $param['screen_name'] ) ){
			$options = array('screen_name'=>$param['screen_name']);
		} elseif( isset( $param['id'] ) ){
			$options = array('id'=>$param['id']);
		}

		$user = $connect->get( 'users/show', $options );
		// $this->checkLimitRate( $connect );

		// file_put_contents( $_SERVER['DOCUMENT_ROOT'].'/getAccountId.txt', serialize($accountId));
		// $accountId = unserialize(file_get_contents( $_SERVER['DOCUMENT_ROOT'].'/getAccountId.txt'));

		if( $connect->http_code != 200 ){
			throw new Exception( 'Ошибка подключения к серверу твиттера, проверьте ключи' );
		} 

		return $user;
	}

	/**
	* Метод получения полной информации о юзере
	*
	* @param $user_id int
	* @return object user
	*/
	function showUser( $user_id, $actionName = 'showUser' ){
		$user = $this->twitter->get( 'users/show', array( 'id' => $user_id ) );
		$this->writeLimitRate( $actionName );
		return $user;
	}

	/**
	* Метод получения ленты аккаунта
	*
	* @param $lastPost int  последний пост
	* @return $tape array
	*/
	function getTape( $lastPost = NULL ){
		$option = array('count' => 200);
		if( $lastPost ){
			$option = array_merge( array('since_id'=>$lastPost), $option );
		}

		$tape = $this->twitter->get( 'statuses/home_timeline', $option );
		$this->writeLimitRate( 'parser' );

		return $tape;
	}
}