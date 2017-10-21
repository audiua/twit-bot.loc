<?php

/**
* Трейт для записи данных в базу данных
*
* @author audiua <audiua@yandex.ru>
*/

trait DataBasePOST{

/**
* Добавление или изменение юзеров в базе
* Данный метод будет всегда пытаться вставить навую запись
* если будет нарушен первичный ключ, то будет обновление записи
*
* @param $users array список ассоциативных массивов юзеров которых нужно добавить или иодифицировать
* array( array( 'field'=>'value', 'field2'=>'value2' ) )
* @return boolean
*/
function addUsers( array $users ){
		
	$this->connect->beginTransaction();
	// return $users;

	// по юзерам
	foreach( $users as $user ){
		$setArr = array();

		// по свойствам юзера
		foreach( $user as $field => $value ){

			// обычные поля
			if( empty( $value ) ){
				$setArr[] = " `$field` = NULL ";
			} else {

				if( $field  == 'unsuccessful_try' ){
					$setArr[] ="`unsuccessful_try` = `unsuccessful_try` + $value";
					continue;
				}

				$value = $this->connect->quote( $value );
				$setArr[] = " `$field` = $value ";
			}

		}

		// формируем строку значений field=value
		$setStr = implode( ',' , $setArr );
		$sql = "INSERT INTO 
					`users`
				SET 
					$setStr
				ON DUPLICATE KEY UPDATE
					$setStr";

		// return $sql;
		// die;
		$a = $this->connect->exec( $sql );
		// echo $a;
		// print_r( $this->connect->errorInfo() );
		// die;
	}

	return $this->connect->commit();

}



/**
* Добавление акаунта
* Синзронизация пройдет автоматически на следующий день
* поэтому добавлять фолловеров и друзей нет смысла( хотя возможно )
*
* @param $account array  асоциативный масив с данными аккаунта из фортмы + из твиттер обьекта
* @return boolean
*/
function addAccount( $account ){

	foreach( $account as $key => &$value ){
		$setArr[] = "$key = '$value'";
	}
	unset( $value, $key );

	// формируем строку значений field=value
	$setStr = implode( ',' , $setArr );
	unset( $setArr );

	$sql = "INSERT INTO 
				`accounts` 
			SET 
				$setStr
			ON DUPLICATE KEY UPDATE
				$setStr";

	// echo $sql;
	// die;
	$success = $this->connect->exec( $sql );
	if( ! $success ){
		return false;
		// throw new Exception( $this->connect->errorInfo() );
	}

	// print_r($success);
	// die;

	return true;	
}



/**
* Метод добавления произвольных данных в произвольную таблицу
*
* @param $table string
* @param $setValueArray array список set значений поле=значение
* @return boolean
*/
function addData( $table, array $setValueArray ){

	$this->connect->beginTransaction();

	foreach( $setValueArray as $setValue ){

		if( 'unfollowing' == $table ){

			$sql = "INSERT IGNORE INTO
					`$table`
				SET
					$setValue";
		} else {
			$sql = "INSERT INTO
					`$table`
				SET
					$setValue
				ON DUPLICATE KEY UPDATE
					$setValue";
		}

		// return $sql;

		$this->connect->exec( $sql );
		// return $this->connect->errorInfo();

		$sql = '';
	}
	$this->connect->commit();

	return true;
}


/**
* Удаления данных
* 
* @param $table string  таблица с которой удаляем
* @param $condition array  список условий с метода getCondition()
* @return boolean
*/
function deleteData( $table, array $conditions, $trnsaction=true ){

	if($trnsaction){
		$this->connect->beginTransaction();
	}

	foreach( $conditions as $condition ){

		// удаляем из таблицы
		$sql = "DELETE FROM
					`$table`
				WHERE
					$condition";
		// return $sql;

		$this->connect->exec( $sql );
		// return $this->connect->errorInfo();

	}
	if($trnsaction){
		$this->connect->commit();
	}

	return true;
}

/**
* Очистка таблици
*
* @param $table
*/
function clearTable( $table ){
	$sql = "TRUNCATE TABLE
				`$table` ";
	$success = $this->connect->exec( $sql );

	return $success;
}

/**
* Метод добавления данных в базу
* 
* @param $data array  ('key'=> 'value')
*/
function addStatistics( $data ){
	$sql = "INSERT INTO
				`statistics`
			SET
				`day`=NOW(),
				`account_id`={$data['account_id']},
				`followers_count`={$data['followers_count']},
				`friends_count`={$data['friends_count']},
				`statuses_count`={$data['statuses_count']}
			ON DUPLICATE KEY UPDATE
				`day`=NOW(),
				`account_id`={$data['account_id']},
				`followers_count`={$data['followers_count']},
				`friends_count`={$data['friends_count']},
				`statuses_count`={$data['statuses_count']}";
	$this->connect->exec( $sql );
	return true;
}

/**
* Метод обновления данных в статистике
* 
*/
function updateStatistics( $mode ){
	$field = $mode . '_count';
	$date = date('Y-m-d');
	$sql = "UPDATE
				`statistics`
			SET
				`$field` = `$field`+1
			WHERE
				`account_id`=$this->account_id
			AND
				`day`='$date'";
	// return $sql;
	$this->connect->exec( $sql );
	return $this->connect->errorInfo();
	return true;
}

/**
* Запись росписания запусков аккаунта
*
* @param $allSchedule array link  ссылка на массив запусков
*/
function addSchedule( &$allSchedule ){
	$success = true;
	$this->connect->beginTransaction();

	foreach( $allSchedule as $i => &$schedule ){
		// print_r($schedule);
		// die;
		if( ! isset($schedule['param']) ){
			$schedule['param'] = NULL;
		} 
		
		$param = (string)$schedule['param'];
		

		$sql = "INSERT INTO 
					`schedule` 
				SET
					`account_id`={$schedule['account_id']},
					`action`='{$schedule['action']}',
					`start_time`={$schedule['time']},
					`params`='$param'";
		// echo $sql;
		// die;
		
		$success &= $this->connect->exec( $sql );
		print_r($this->connect->errorInfo()) ;

		// print_r($success);
		// die;
	}

	$this->connect->commit();
	if( $success ){
		return true;
	}
}

/**
* Запись лимитов запусков для лимитированных методов
* сохранение данных между запусками - пишем данные в базу
*
* @param $action string
* @param $remeining int
* @param $reset 
* @return booloean
*/
function addLimit( $action, $remeining, $reset ){
	$sql = "INSERT INTO 
				limit_action
			SET 
				account_id = $this->account_id,
				action = '$action',
				remeining = $remeining,
				reset_time = $reset
			ON DUPLICATE KEY UPDATE
				remeining = $remeining,
				reset_time = $reset";
	$this->connect->exec( $sql );
	return true;

}

/**
* Метод добавления твитов и ретвитов в базу данных
*
* @param $table string
* @param $field string
* @param &$twits array
*/
function addPublic( $table, $field, &$twits, $transaction=true ){
	// Logger::write( 'addPublic ' . $this->account_id , __FILE__ , __LINE__ );

	if($transaction){
		$this->connect->beginTransaction();
	}
	foreach( $twits as &$twit ){
		$time = time();
		$post = $this->connect->quote( $twit );
		$sql = "INSERT IGNORE INTO 
					`$table`
				SET
					`$field`=$post,
					`create`=$time";

		$this->connect->exec( $sql );			
	}
	if($transaction){
		$this->connect->commit();
	}
}

/**
* Добавление в базу найденные твиты с ссылками
*
*/
function addLinkTwit( array $links ){

	$this->connect->beginTransaction();
	foreach( $links as $link ){
		$sql="INSERT IGNORE INTO
				`link_twit`
			SET
				`link_twit_id`=$link";
		$this->connect->exec( $sql );	
	}
	$this->connect->commit();

}

/**
* Записываем ретвит с ссылкой
* @param $retweetId int
* @param $twitId int
*/
function addLinkRetweet( $retweetId, $twitId ){
	$time = time();
	$sql="INSERT INTO 
				`link_retweet`
			SET
				`account_id`=$this->account_id,
				`link_twit_id`=$twitId,
				`link_retweet_id`=$retweetId,
				`retweet_time`=$time
			ON DUPLICATE KEY UPDATE
				`account_id`=$this->account_id,
				`link_twit_id`=$twitId,
				`link_retweet_id`=$retweetId,
				`retweet_time`=$time";
	$this->connect->exec( $sql );	
	return $this->connect->errorInfo();
}




}
