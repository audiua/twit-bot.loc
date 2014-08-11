<?php

/**
* Поиск новых юзеров
*
*/
class SearchUsers{

/**
* @var $account object  обект аккаунта
*
*/
protected $account;

function __construct( Account $account ){
	$this->account = $account;
}

/**
* Поиск новых юзеров для фолловинга
*
*/
function searchUsers(){

	// юзер у которого скопируем аго фолловеров к себе в юзеры
	$condition = $this->account->db->getCondition(
		array( 
			array( 
				'field' => 'next_cursor',
				'mode' => '<>',
				'value' => 0,
				'moreCondition' => ' AND '
			),
			array( 
				'field' => 'lang',
				'mode' => '=',
				'value' => "'".$this->account->accountConfig['lang']."'",
				'moreCondition' => ''
			) 
		) 
	);

	$user = $this->account->db->get( 'users', array('user_id', 'next_cursor'), $condition, 1);
	unset($condition);
	// print_r($user);
	// die;

	// return $follower;
	if( empty( $user )  ){
		$this->writeLog('Поиск фолловеров у фолловеров не возможен');
		die;
	}

	$newUsers = $this->account->twitter->getUsersList( $user[0]->user_id, $user[0]->next_cursor );
	// $file = $_SERVER['DOCUMENT_ROOT'] .'/protected/runtime/cache/'. "searchUsers_" . $user[0]->user_id . '.txt';
	
	// file_put_contents( $file, serialize( $newUsers ) );
	// $newUsers = unserialize( file_get_contents('newUsers') );
	// echo '<pre>';
	// print_r($newUsers);
	// die;

	$filterUsers = $this->filterUsers( $newUsers->users );
	// print_r($filterUsers);
	// die;

	if( empty( $filterUsers ) ){
		return;
	}

	// обновить курсор юзера
	$assocUsers[] = array( 'user_id' => $user[0]->user_id, 'next_cursor' => $newUsers->next_cursor );
	$this->account->db->addUsers( $assocUsers );
	unset($assocUsers);

	// print_r($this->db->errorInfo());
	// die;

	// пишем в базу - так как id = pk, то проверять на уникальность не нужно
	// база сама не даст записать повторяющиеся данные
	// пишем в транзакции без прерывания на ошибку записи - запишем только уников
	foreach( $filterUsers as &$oneUser ){
		$assocUsers[] = array( 
			'user_id' => $oneUser->id, 
			'description' => $oneUser->description,
			'name'=> $oneUser->name,
			'screen_name'=>$oneUser->screen_name,
			'location'=>$oneUser->location,
			'lang'=>$oneUser->lang,
			'followers_count'=>$oneUser->followers_count,
			'friends_count'=>$oneUser->friends_count,
			'update_time'=>time()
			 );
	}
	$countNewUsers = count($assocUsers);

	// echo '<pre>';
	// print_r($assocUsers);
	// die;

	$this->account->db->addUsers( $assocUsers );
	Logger::write( "Добавленно - $countNewUsers новых юзеров" , __FILE__ , __LINE__ );

} 


/**
* Отбор юзеров - соответствующим настройкам
*
* @param $users array  массив юзеров
* @return array
*/
function filterUsers( $users ){
	$week = time() - 86400*7;
	foreach( $users as $i => &$one ){

		// фильтр по разнице фолловеров и друзей
		if( ( $one->friends_count * 2 ) < $one->followers_count ){
			unset( $users[$i] );
			continue;
		}

		// фильтр по языку
		if( $one->lang != $this->account->accountConfig['lang'] ){
			unset( $users[$i] );
			continue;
		}

		// фильтр по давности последнего твита!
	}

	return $users;
}


}