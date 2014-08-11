<?php

/**
* трейт для отправки данных на сервера твиттера
* может использоваться классами с установленными соединениями с сервером твиттера
* и с обьектом класса Account
*
* @author audiua <audiua@yandex.ru>
*/
trait TwitterAPIPOST{

/**
* Добавление и удаление друзей 
* Данная функция ожидает наличие свойств: $this->twitter, $this->account в вызываемом классе
*
* @param $users array  список id юзеров которых нужно добавить в друзья или удалить из друзей
* @param $mode string  модификатор действий ('create', 'destroy'), по умолчанию 'create'
* @return array  список юзеров к которым применялись действия
*/
function actionFriends( array $users, $mode = 'create' ){

	if( empty( $users ) ){
		throw new Exception( 'Пустой массив юзеров для добавления в друзья' );
	}

	$actionFriends = array();
	$options = array();
	foreach( $users as &$user ){

		if( $mode == 'create' ){
			$options['id'] = $user;
			$options['follow'] = true;
		} else {
			$options['user_id'] = $user;
		}
		// echo $mode;
		// print_r($options);
		// die;

		$this->twitter->post( 'friendships/' . $mode, $options );
		// print_r($this->twitter);
		// die;

		if( $this->twitter->http_code == 200 ){

			// инкрементировать действие в базе для статистики
			$this->account->db->updateStatistics( $mode );

			if( $mode == 'create' ){
				$action = 'follow';
			} else {
				$action = 'unfollow';
			}
			$this->writeLimitRate( $action );

			$actionFriends[] = $user;

			//TODO исправить!!
			$name = $this->account->screen_name;
			$id= $this->account->account_id;
			Logger::write( "($id)$name $action $user", basename(__FILE__), __LINE__ );

		} else {
			$code=(int)$this->twitter->http_code;
			
			// возвращаем его для дальнейшей обработки системой - чтобы не застопориться
			$actionFriends[] = $user;

			Logger::write( "ERROR($code)-". $this->account->screen_name." не удалось ".$mode.' '.$user, basename(__FILE__), __LINE__ );

			// $this->account->checkErrorCode( $this->twitter->http_code );
			
		}

		// пауза между добавлениями юзеров в друзья или удалением
		usleep( mt_rand( 3000000, 5000000 ) );
	}

	if( empty( $actionFriends ) ){
		return array();
	}
	

	return $actionFriends;
}


/**
* Публикация в твиттер - твиты, ретвиты, ссылки
*
* @param $massage string
* @param $mode string  модификатор ( 'twit', 'retweet' )
*/
function twit( $message, $mode = 'twit' ){


	if( $mode == 'twit' ){
		// return $this->twitter;
		$this->twitter->post( 'statuses/update', array( 'status' => (string)$message ) );
	} else {
		$this->twitter->post( 'statuses/retweet/' . $message );
		// return $this->twitter;
	}

	$this->writeLimitRate( 'twit' );
	if( $this->twitter->http_code == 200 ){
		return true;
	} else {
		throw new Exception( 'ошибка при добавлении твита' );
	}

}

/**
* Отправкаличных сообщений благодарности за подписку на наш аккаунт
*
*/
function sendMassageNewFollowers( array $data ){

	foreach( $data as $one ){

		// отправляем личное сообщение новым фолловерам
		$this->twitter->post('direct_messages/new', array( 'user_id' => $one['user_id'], 'text' => $one['message'] ));
		$this->writeLimitRate( 'sendMassageNewFollowers' );
		sleep( mt_rand( 1, 3 ) );
	}


	return;
}





}