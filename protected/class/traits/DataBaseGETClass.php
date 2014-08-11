<?php

/**
* трейт для получения данных из базы данных
*
* @author audiua <audiua@yandex.ru>
*/
trait DataBaseGET{

/**
* Метод получения данных из базы
*
* @param $field array  список полей которые нужно вернуть
* @param $condition array  строка условия сформирована методом getCondition()
* @return array  массив обьектов ( PDO::FETCH_OBJ )
*/
function get( $table, $field = array('*'), $condition = '', $limit = 0, $fetch = PDO::FETCH_OBJ ){
	$strField = implode( ',', $field );
	$sql = "SELECT 
				$strField 
			FROM 
				$table ";

	if( !empty( $condition ) ){
		$sql .= "WHERE ";
		$sql .= $condition;
	}

	if( (int)$limit > 0 ){
		$sql .= " LIMIT $limit ";
	}

	// echo $sql;
	// die;

	$data = $this->connect->query( $sql )->fetchAll( $fetch );
	if( empty( $data ) ){
		return array();
		//TODO залогировать 
	}

	return $data;
}

/**
* Формирование условия для запроса базы данных
* получаем не пустой массив - проверка в вызывающей функции
* Конкатенация условий будет соответсвовать порядку елементов массива
* поэтому, необходимо следить за елементами которые включают расширенные условия
*
* @param $condition array  многомерный массив с условиями 
*  array('field'=>'', 'value'=>'', 'mode'=>'>|<|=|IS NULL|<>|', 'moreCondition' => 'AND|OR')
* @return string  строка условия для запроса
*/
function getCondition( array $condition ){
	$sql = '';
	foreach( $condition as $cond ){
		$sql .= " `$cond[field]` $cond[mode] $cond[value] $cond[moreCondition] ";
	}

	return $sql;
}


/**
* Метод для получения количества данных
*
* @param $table string  таблицa
* @param $condition  условия метода getCondition()
* @return int
*/
function getCount( $table, $condition = '' ){
	$sql = 
		"SELECT 
		 	COUNT( * )
	 	AS 
	 		count
	 	FROM 
	 		`$table` ";

	if( $condition ){
		$sql .= " WHERE $condition";
	}

	$result = $this->connect->query( $sql )->fetch( PDO::FETCH_OBJ );

	return $result->count;
}

/**
* Метод для получения агрегативных данных
*
* @param $agrigate string  агрегатная функция ('MIN|MAX')
* @param $field string  поле таблицы по умолчению '*'
* @param $table string  таблицa
* @return int
*/
function getAgrigate( $agrigate, $table, $field ){
	$sql = 
		"SELECT 
		 	$agrigate( $field )
	 	AS 
	 		$agrigate
	 	FROM 
	 		$table ";

	$result = $this->connect->query( $sql )->fetch( PDO::FETCH_OBJ );
	
	if( isset( $result->$agrigate ) ){
		return $result->$agrigate;
	}

	return false;
}

/**
*  Мега сложный запрос - получаем кандидатов на фолловинг тольько если он не используется
*
*
*/
function getFollowers( $count ){

	$sql =  "SELECT 
				user_id
			FROM
				users first
			WHERE NOT EXISTS(
				SELECT 
					*
				FROM
					followers second
				WHERE
					first.user_id = second.user_id 
				AND
					second.account_id = $this->account_id )

			AND NOT EXISTS(
				SELECT 
					*
				FROM
					friends three
				WHERE
					first.user_id = three.user_id 
				AND
					three.account_id = $this->account_id
			)
			
			AND NOT EXISTS(
				SELECT 
					*
				FROM
					try_following four
				WHERE
					first.user_id = four.user_id 
				AND
					four.account_id = $this->account_id
			)
			
			AND
				first.lang='ru'
			AND 
				first.followers_count < first.friends_count+first.friends_count
			AND 
				first.unsuccessful_try = (  SELECT 
												MIN( unsuccessful_try ) as unsuccessful_try
											FROM 
												users
											LIMIT 
												1 ) 
			";

			if( $count > 0 ){
				$sql .= " LIMIT $count";
			}

			// return $sql;

	$result = $this->connect->query( $sql )->fetchAll( PDO::FETCH_OBJ );
	return $result;
}

/**
*  Мега сложный запрос - получаем кандидатов на фолловинг тольько если он не используется
*
*
*/
function getUnfollowUsers(){
	
	
	$sql =  "SELECT 
				user_id
			FROM
				users first
			WHERE NOT EXISTS(
				SELECT 
					*
				FROM
					followers second
				WHERE
					first.user_id = second.user_id 
				AND
					second.account_id = $this->account_id )

			AND EXISTS(
				SELECT 
					*
				FROM
					friends three
				WHERE
					first.user_id = three.user_id 
				AND
					three.account_id = $this->account_id
			)

			AND NOT EXISTS(
				SELECT 
					*
				FROM
					unfollowing four
				WHERE
					first.user_id = four.user_id 
				AND
					four.account_id = $this->account_id
			)";


			// return $sql;

	$result = $this->connect->query( $sql )->fetchAll( PDO::FETCH_OBJ );
	return $result;
}

/**
* Получаем не ретвитнутные посты с сссылками
*
*/
function getFreeLinkTwit(){
	
	$sql = "SELECT 
				link_twit_id 
			FROM
				link_twit

			WHERE NOT EXISTS(
				SELECT 
					*
				FROM 
					link_retweet
				WHERE 
					link_twit.link_twit_id = link_retweet.link_twit_id
				AND 
					link_retweet.account_id = $this->account_id )
			LIMIT 1";
			// return $sql;

	$result = $this->connect->query( $sql )->fetchAll( PDO::FETCH_OBJ );
	return $result;
}

/**
* Получаем самый старый ретвит с ссылкой
*
*/
function getOldLinkRetweet(){
	$sql = "SELECT
				*
			FROM
				link_retweet
			WHERE 
				`account_id`=$this->account_id
			AND 
				`retweet_time`=   ( SELECT 
										MIN( retweet_time ) as retweet_time
									FROM 
										link_retweet 
									WHERE 
										`account_id`=$this->account_id)
			LIMIT 1";
	
	$result = $this->connect->query( $sql )->fetchAll( PDO::FETCH_OBJ );
	return $result;
}

/**
* ответный фолловинг
*/
function getBackFollowers( $count ){
	$week = time() - 86400*7;
	
	$sql =  "SELECT 
				user_id
			FROM
				users first
			WHERE EXISTS(
				SELECT 
					*
				FROM
					followers second
				WHERE
					first.user_id = second.user_id 
				AND
					second.account_id = $this->account_id )

			AND NOT EXISTS(
				SELECT 
					*
				FROM
					friends three
				WHERE
					first.user_id = three.user_id 
				AND
					three.account_id = $this->account_id
			)
			
			AND
				first.lang='ru' 
			";

			if( $count > 0 ){
				$sql .= " LIMIT $count";
			}

			// return $sql;

	$result = $this->connect->query( $sql )->fetchAll( PDO::FETCH_OBJ );
	return $result;
}


function getNewFollowersWithData(){

}

function issetSpeakToday( $screen_name, $category ){
	$sql= "SELECT 
				* 
			FROM
				`speak_today`
			WHERE 
				`account_id`=$this->account_id
			AND
				`screen_name`='$screen_name'
			AND
				`category`='$category'";

	$result = $this->connect->query($sql)->fetchAll( PDO::FETCH_OBJ );
	
	return !empty( $result );

}



}