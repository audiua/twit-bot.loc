<?php
require_once( $_SERVER['DOCUMENT_ROOT'] . '/protected/helpers/autoload.php' );
// include( $_SERVER['DOCUMENT_ROOT'] . '/protected/class/BaseClassClass.php' );
// include( $_SERVER['DOCUMENT_ROOT'] . '/protected/class/DataBaseClass.php' );

class User{
	// public $userData;
	// public $id;
	// public $login;
	// public $password;
	// public $access_token;
	// public $costumer_key;
	// public $costumer_secret;
	// public $access_token_secret;
	public $user;
	public $userConfig;
	public $db;
	


	function __construct( $userId=null, $withData = false ){
		// parent::__construct();
		
		$this->db = new DataBase();

		if( $userId ){
			
			
			$sql = 'SELECT * FROM
						accounts 
					WHERE 
						account_id=' . (int)$userId;

			if( $withData ){
				$sql = 'SELECT * FROM 
							accounts
						INNER JOIN 
							account_data
						ON 
							accounts.account_id = account_data.account_id
				 		WHERE 
				 			accounts.account_id=' . (int)$userId;
			}

			$user = $this->db->connect->query( $sql )->fetch( PDO::FETCH_ASSOC );
			$this->user = $user;

			$this->userConfig = $this->getUserConfig();

			// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/spare.txt', time().serialize( $user )."\n", FILE_APPEND);
			
			// $this->id = (int)$userId;
			// $this->login = $user['login'];
			// $this->password = $user['password'];
			// $this->access_token = $user['access_token'];
			// $this->costumer_key = $user['costumer_key'];
			// $this->costumer_secret = $user['costumer_secret'];
			// $this->access_token_secret = $user['access_token_secret'];
		}
	}

	function getUserConfig(){
		// $userConfig = unserialize( $this->user['user_schedule_config'] );
		// if( ! $userConfig ){
			$userConfig = array();
		// }

		$defaultUserConfig = include( $_SERVER['DOCUMENT_ROOT'] . '/protected/config/userConfig.php' );

		$mergeUserConfig = array_merge( $defaultUserConfig, $userConfig );
		return $mergeUserConfig;
	}

	public static function getAllUsers(){

		$allUsers = $this->db->connect->query('SELECT id FROM accounts')->fetchAll( PDO::FETCH_NUM );

		return $allUsers;
	}

	function showUser(){
		echo 'login - ' . $this->login . '<br>';
		echo 'password - ' .$this->password . '<br>';
		echo 'access_token - ' .$this->access_token . '<br>';
		echo 'costumer_key - ' .$this->costumer_key . '<br>';
		echo 'costumer_secret - ' .$this->costumer_secret . '<br>';
		echo 'access_token_secret - ' .$this->access_token_secret;
	}

	// получить данные юзера по его id
	function getUserData( $id ){

		$userData = $this->db->connect->query('SELECT * FROM account_data WHERE id='.$id );

		return $userData;
	}

	private function _issetUserInDb( $login ){
		$account = $this->db->connect->query('SELECT * FROM accounts WHERE screen_name='.$login );

		if( ! empty( $account ) ){
			return true;
		}

		return false;
	}



	function addUser( $user ){

		$success = true;

		$check = $this->_issetUserInDb( $user['login'] );
		if( $check ){
			return false;
		}

		// проверяем ключи и узнаем ID аккаунта
		$accountId = TwitterAPI::getAccountId( $user );

		// print_r($accountId);
		// die;


		if( !$accountId ){
			$this->writeLog( 'Ошибка записи нового юзера' );
			die;
		}











		// $this->db->connect->beginTransaction();

		$success &= $this->db->connect->exec(
			"INSERT INTO 
				accounts 
			SET 
				account_id=$accountId->id,
				screen_name='". $user['login'] ."',
				password='". $user['password'] ."',
				access_token='". $user['access_token'] ."',
				costumer_key='". $user['costumer_key'] ."',
				costumer_secret='". $user['costumer_secret'] ."',
				access_token_secret='". $user['access_token_secret'] ."'");


		// print_r($this->db->connect->errorCode());
		// die;

		$name = $this->db->connect->quote( $accountId->name );
		$location = $this->db->connect->quote( $accountId->location );
		$description = $this->db->connect->quote( $accountId->description );

		if( $success ){
			$success &= $this->db->connect->exec(
				"INSERT INTO 
					account_data 
				SET 
					account_id=$accountId->id,
					name=$name,
					location=$location,
					description=$description,
					followers=$accountId->followers_count,
					friends=$accountId->friends_count,
					twits=$accountId->statuses_count"
			);
		}

		// var_dump($this->db->connect->errorInfo());
		// die;

		

		// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/addFriend.txt', serialize($userFromDb) );
		// die;

		if( $success ){


			// получаем имеющиеся set значения
			$oldSetValue = $this->getSetValue();

			if( ! $oldSetValue ){
				$this->writeLog( 'Ошибка записи нового юзера' );
				$success &= false;
			}

			// получаем имеющиеся set значения
			$oldEnumValue = $this->getEnumValue();

			if( ! $oldEnumValue ){
				$this->writeLog( 'Ошибка записи нового юзера' );
				$success &= false;
			}


			// конкатенируем нового аккаунта в множества
			$success &= $this->addNewAccountInSet( $oldSetValue, $accountId->id );

			// конкатенируем нового аккаунта в множества в расписания
			$success &= $this->addNewAccountInEnumSchedule( $oldEnumValue, $accountId->id );
			
			// var_dump( $success );
			// die;
			
			// записываем в базу друзей и фолловеров аккаунта
			$this->writeUsersOfAccountInDb( $accountId->id );
				
		}

		return $success;
	}

	function getEnumValue(){
		$sql = "SELECT 
					* 
				FROM 
					information_schema.columns 
				WHERE 
					table_schema='autotwitter' 
				AND 
					table_name='schedule'
				AND 
					data_type='enum'
				AND 
					column_name='account_id'";
			

		$enumValue = $this->db->connect->query( $sql );

		if( ! $enumValue ){
			print_r($this->db->connect->errorInfo());
			die;
		} 

		$enumValue = $enumValue->fetchAll( PDO::FETCH_ASSOC );
		
		return $enumValue;
	}

	function getSetValue(){
		$sql = "SELECT 
					* 
				FROM 
					information_schema.columns 
				WHERE 
					table_schema='autotwitter' 
				AND 
					table_name='users'
				AND 
					data_type='set'";
			

		$setValue = $this->db->connect->query( $sql );

		if( ! $setValue ){
			print_r($this->db->connect->errorInfo());
			die;
		} 

		$setValue = $setValue->fetchAll( PDO::FETCH_ASSOC );
		
		return $setValue;
	}

	// поиск юзера по логину
	// function getUser( $login ){

	// 	$sql = "SELECT 
	// 				id 
	// 			FROM 
	// 				accounts 
	// 			WHERE 
	// 				login='$login'";
			

	// 	$user = $this->db->connect->query( $sql )->fetch( PDO::FETCH_ASSOC );
	// 	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/addFriend.txt', $user['id'] );
	// 	// die;
	// 	if($user){
	// 		return $user['id'];
	// 	}

	// 	return false;

	// }

	// добавляем новый аккаунт в множества
	function addNewAccountInSet( $oldSet, $account ){
		$oldSetValue = array();
		$success = true;

		foreach( $oldSet as $field ){
			if( $field['DATA_TYPE'] = 'set'){
				$string = $field['COLUMN_TYPE'];
			}

			// получаем числа из множества
			preg_match_all('/\'[0-9]+\'/', $string, $return);

			if( empty( $return[0] ) ){
					$oldSetValue[ $field['COLUMN_NAME'] ] = "'$account','null'";
			} else {
				if( !in_array( $account, $return[0] ) ){
					$oldSetValue[ $field['COLUMN_NAME'] ] = implode( ',', $return[0] ) . ",'". $account ."','null'";
				}
			}

			$newSetValue = "set(".$oldSetValue[ $field['COLUMN_NAME'] ].")";
			$field = $field['COLUMN_NAME'];

			$sql = 
				"ALTER TABLE 
					autotwitter.users 
				MODIFY 
					$field $newSetValue";


			$this->db->connect->exec($sql);
			// $arsuccess[] = $this->db->connect->errorInfo();

		}

		// print_r($arsuccess);
		// die;

		return $success;

	}


	// добавляем новый аккаунт в множества
	function addNewAccountInEnumSchedule( $oldEnum, $account ){
		$oldEnumValue = array();
		$success = true;

		foreach( $oldEnum as $field ){
			if( $field['DATA_TYPE'] = 'enum'){
				$string = $field['COLUMN_TYPE'];
			}

			// получаем числа из множества
			preg_match_all('/\'[0-9]+\'/', $string, $return);

			if( empty( $return[0] ) ){
					$oldEnumValue[ $field['COLUMN_NAME'] ] = "'$account','null'";
			} else {
				if( !in_array( $account, $return[0] ) ){
					$oldEnumValue[ $field['COLUMN_NAME'] ] = implode( ',', $return[0] ) . ",'". $account ."','null'";
				}
			}

			$newEnumValue = "enum(".$oldEnumValue[ $field['COLUMN_NAME'] ].")";
			$field = $field['COLUMN_NAME'];

			$sql = 
				"ALTER TABLE 
					autotwitter.schedule 
				MODIFY 
					$field $newEnumValue";


			$this->db->connect->exec($sql);
			// $arsuccess[] = $this->db->connect->errorInfo();

		}

		// print_r($arsuccess);
		// die;

		return $success;

	}

	// удаляем аккаунт + все множества нужно редактировать
	function deleteAccount( $accountId ){
		$accountId = (int)$accountId;
		$success = true;

		// удаляем из таблицы аккаунтов
		$sql = "DELETE FROM
					accounts
				WHERE
					account_id=$accountId";
		$success &= $this->db->connect->exec($sql);

		// удаляем из таблицы данных о аккаунтах
		$sql = "DELETE FROM
					account_data
				WHERE
					account_id=$accountId";
		$success &= $this->db->connect->exec($sql);

		// удалить из множеств
		// получаем имеющиеся set значения
		$oldSetValue = $this->getSetValue();

		// return $oldSetValue;

		if( ! $oldSetValue ){
			$this->writeLog( 'Ошибка записи нового юзера' );
			$success &= false;
		}

		// получаем имеющиеся set значения
		$oldEnumValue = $this->getEnumValue();

		// return $oldEnumValue;

		if( ! $oldEnumValue ){
			$this->writeLog( 'Ошибка записи нового юзера' );
			$success &= false;
		}


		// удаляем id аккаунта из использования юзерами
		$result = $this->deleteFromUsedValueAccountId( $accountId );

		// print_r($result);
		// die;

		// удаляем ид аккаунта из возьожного значения множества
		$success &= $this->removeAccountFromSet( $oldSetValue, $accountId );
		// return var_dump($this->removeAccountFromSet( $oldSetValue, $accountId ));

		// конкатенируем нового аккаунта в множества в расписания
		$success &= $this->removeAccountFromEnumSchedule( $oldEnumValue, $accountId );
		// return $success;



		return $success;
		
	}


	function removeAccountFromSet( $oldSet, $account ){

		$account = "'".$account."'";
		$oldSetValue = array();
		$success = true;

		foreach( $oldSet as $field ){
			if( $field['DATA_TYPE'] = 'set'){
				$string = $field['COLUMN_TYPE'];
			}

			// получаем числа из множества
			preg_match_all('/\'[0-9]+\'/', $string, $return);

			// return $return;

			// нет аккаунтов
			if( empty( $return[0] ) ){
				return $return;
			} else {
					// return $account;


				// удаляем из множества
				if( in_array( $account, $return[0] ) ){
					// return $account;


					foreach( $return[0] as $ix => &$accountInSet ){

						// return $accountInSet;

						if( $accountInSet == $account ){
							unset( $return[0][$ix] );
						}
					}

					if( empty( $return[0] ) ){
						$oldSetValue[ $field['COLUMN_NAME'] ] = "'null'";
					} else {
						$oldSetValue[ $field['COLUMN_NAME'] ] = implode( ',', $return[0] ).",'null'";
					}

				}
			}



			$newSetValue = "set(".$oldSetValue[ $field['COLUMN_NAME'] ].")";
			// return $oldSetValue;
			$field = $field['COLUMN_NAME'];

			$sql = 
				"ALTER TABLE 
					autotwitter.users 
				MODIFY 
					$field $newSetValue";

			$this->db->connect->exec($sql);
			// $arsuccess[] = $this->db->connect->errorInfo();

		}

		// print_r($arsuccess);
		// die;

		return $success;
	}


	function removeAccountFromEnumSchedule( $oldEnum, $account ){
		$account = "'".$account."'";
		$oldEnumValue = array();
		$success = true;

		foreach( $oldEnum as $field ){
			if( $field['DATA_TYPE'] = 'enum' ){
				$string = $field['COLUMN_TYPE'];
			}

			// получаем числа из множества
			preg_match_all( '/\'[0-9]+\'/', $string, $return );

			// return $return;

			if( empty( $return[0] ) ){
				$oldEnumValue[ $field['COLUMN_NAME'] ] = "'null'";
			} else {
				if( in_array( $account, $return[0] ) ){

					foreach( $return[0] as $ix => &$accountInEnum ){
						if( $accountInEnum == $account ){
							unset( $return[0][$ix] );
						}
					}

					if( empty( $return[0] ) ){
						$oldEnumValue[ $field['COLUMN_NAME'] ] = "'null'";
					} else {
						$oldEnumValue[ $field['COLUMN_NAME'] ] = implode( ',', $return[0] ).",'null'";
					}
				}
			}

			$newEnumValue = "enum(".$oldEnumValue[ $field['COLUMN_NAME'] ].")";
			$field = $field['COLUMN_NAME'];

			$sql = 
				"ALTER TABLE 
					autotwitter.schedule 
				MODIFY 
					$field $newEnumValue";


			$this->db->connect->exec($sql);
			// $arsuccess[] = $this->db->connect->errorInfo();

		}

		// print_r($arsuccess);
		// die;

		return $success;
	}


	//-----------------
	function addAccountIdInSetValue( $accountId ){
		$sql = "UPDATE 
					users
				SET
					used_follower= APPEND_TO_SET($this->user->id, used_follower)
					user_id=$user_id
				";
		$db->connect->exec( $sql );
	}

	//remove
	function removeAccountIdFromSetValue( $user_id ){
		$sql = "UPDATE 
					users
				SET
					used_follower= REMOVE_FROM_SET($this->user->id, used_follower)
				WHERE
					user_id=$user_id
				";
		$db->connect->exec( $sql );
	}

	function writeUsersOfAccountInDb( $accountID ){
		$this->user = new User( (int)$accountID, true );

		// получить фолловеров аккаунта
		$twitter = new TwitterAPI( $this->user );

		$followers = $twitter->getFollowers();
		$friends = $twitter->getFriends();

		$sortUsers = $this->getSortFriendsAndFollowersOfAccount( $followers, $friends );
		file_put_contents( 'sortArray.txt', serialize($sortUsers));

		// записываем общих и друзей и фолловеров
		if( !empty( $sortUsers['sameUsers'] ) ){

			$this->db->connect->beginTransaction();

			foreach( $sortUsers['sameUsers'] as &$sameUser ){
				$sql = 
				"INSERT INTO 
					users 
				SET 
					user_id=$sameUser,
					used_follower='$accountID',
					used_friend='$accountID'
				ON DUPLICATE KEY UPDATE
					used_follower=APPEND_TO_SET( $accountID, used_follower),
					used_friend=APPEND_TO_SET( $accountID, used_friend)";

				$this->db->connect->exec( $sql );
			}

			$this->db->connect->commit();
		}

		
		// записываем только друзей
		if( !empty( $sortUsers['friends'] ) ){

			$this->db->connect->beginTransaction();

			foreach( $sortUsers['friends'] as &$friend ){
				$sql = 
				"INSERT INTO 
					users 
				SET 
					user_id=$friend,
					used_follower='null',
					used_friend='$accountID'
				ON DUPLICATE KEY UPDATE
					used_friend=APPEND_TO_SET( $accountID, used_friend)";

				$this->db->connect->exec( $sql );
			}

			$this->db->connect->commit();
		}

		// записываем только фолловеров
		if( !empty( $sortUsers['followers'] ) ){

			$this->db->connect->beginTransaction();

			foreach( $sortUsers['followers'] as &$follower ){
				$sql = 
				"INSERT INTO 
					users 
				SET 
					user_id=$follower,
					used_friend='null',
					used_follower='$accountID'
				ON DUPLICATE KEY UPDATE
					used_follower=APPEND_TO_SET( $accountID, used_follower)";

				$this->db->connect->exec( $sql );
			}

			$this->db->connect->commit();
		}

		return true;
	}

	// удалить из всех значений множеств 
	function deleteFromUsedValueAccountId( $accountID ){
		$accountID = $accountID;

		$sql = "SELECT
					*
				FROM 
					users
				WHERE 
					find_in_set('$accountID',used_follower)
				OR
					find_in_set('$accountID',used_friend)
				OR  
					find_in_set('$accountID',unfollowing)
				OR 
					find_in_set('$accountID',paid_friend)";


		$result = $this->db->connect->query( $sql )->fetchAll( PDO::FETCH_ASSOC );

		// return $result;
	
		if( $result ){
			
			$this->db->connect->beginTransaction();

			foreach( $result as $oneUser ){
				$userID = $oneUser['user_id'];
				$sql = "UPDATE 
							users
						SET
							used_follower=
							CASE
								WHEN
									count_of_set(used_follower) < 2
								THEN
									'null'
								ELSE
									REMOVE_FROM_SET($accountID, used_follower)
							END,

							used_friend=
							CASE
								WHEN
									count_of_set(used_friend) < 2
								THEN
									'null'
								ELSE
									REMOVE_FROM_SET($accountID, used_friend)
							END,

							unfollowing=
							CASE
								WHEN
									count_of_set(unfollowing) < 2
								THEN
									'null'
								ELSE
									REMOVE_FROM_SET($accountID, unfollowing)
							END,
							
							paid_friend=
							CASE
								WHEN
									count_of_set(paid_friend) < 2
								THEN
									'null'
								ELSE
									REMOVE_FROM_SET($accountID, paid_friend)
							END
						WHERE 
							user_id=$userID";
				$this->db->connect->exec($sql);
			}

			$this->db->connect->commit();
		}

	}

	// сортируем фолловеров и друзей 
	function getSortFriendsAndFollowersOfAccount( $followers, $friends ){
		$users = array();

		// общие - юзер является и фолловером и другом
		$users['sameUsers'] = array_intersect( $followers, $friends );

		// только фолловеры
		$users['followers'] = array_diff( $followers, $users['sameUsers']);

		// только друзья
		$users['friends'] = array_diff( $friends, $users['sameUsers']);

		return $users;
	}


	function syncUsers( $accountID, $field ){
		
		// получить юзеров аккаунта
		// return $this->user;
		$twitter = new TwitterAPI( $this );

		$usersOfTwitter = '';
		$usersOfDb = '';

		// юзеры с твиттера
		if( $field == 'used_follower' ){
			$usersOfTwitter = $twitter->getFollowers();
			// $usersOfTwitter = unserialize( file_get_contents('followers.txt') );



			// file_put_contents('followers.txt', serialize( $usersOfTwitter ) );

			// return $usersOfTwitter;
			$usersOfDb = $this->getUsersFromDb('used_follower', $accountID);
			// return $usersOfDb;

		}

		// юзеры сбазы данных
		if( $field == 'used_friend' ){
			$usersOfTwitter = $twitter->getFriends();
			$usersOfDb = $this->getUsersFromDb('used_friend', $accountID);
		}

		$addUsers = array_diff( $usersOfTwitter, $usersOfDb );

		// return $addUsers;	

		$removeUsers = array_diff( $usersOfDb, $usersOfTwitter );
		// return $removeUsers;
		unset($usersOfTwitter);
		unset($usersOfDb);


		// пишем в базу с проверкой на существование
		foreach( $addUsers as &$oneUser ){
			$sql = "INSERT INTO 
						users
					SET 
						user_id = $oneUser,
						$field = $field=APPEND_TO_SET( '$accountID', $field)
					ON DUPLICATE KEY UPDATE
						$field=APPEND_TO_SET( '$accountID', $field)";

						// return $sql;

			$this->db->connect->exec($sql);

		}

		// return $this->db->connect->errorInfo();

		unset($addUsers);
		unset($oneUser);

		// удалить из множества
		foreach( $removeUsers as &$oneUser ){
			$sql = "UPDATE 
						users
					SET
						$field=
							CASE
								WHEN
									count_of_set($field) < 2
								THEN
									'null'
								ELSE
									REMOVE_FROM_SET($accountID, $field)
							END,
						unsuccessful_try= unsuccessful_try + 1
					WHERE 
						user_id=$oneUser";
			$res = $this->db->connect->exec( $sql );

			// return $this->db->connect->errorInfo();

		}

		return ;
	}

	function getUsersFromDb( $field, $accountID ){
		$sql = "SELECT 
					user_id
				FROM 
					users
				WHERE
					find_in_set( $accountID, $field )";

		$result = $this->db->connect->query( $sql )->fetchAll( PDO::FETCH_NUM );

		if( empty( $result ) ){
			$result = array(); 
		}

		$arrayUsers = array();
		foreach( $result as $one ){
			$arrayUsers[] = $one[0];
		}

		return $arrayUsers;
	}



	// получаем анфолловеров
	function unfollowing( $accountID ){
		$unfollowTime = time() - 1;

		$sql = "SELECT
					user_id
				FROM 
					users
				WHERE
					find_in_set('$accountID', try_friend)
				AND 
					try_time < $unfollowTime";

		$unfollow = $this->db->connect->query( $sql )->fetchAll( PDO::FETCH_ASSOC );

		if( !empty( $unfollow ) ){
			// пишем в базу
			$sql = '';
			foreach( $unfollow as &$oneUser ){
				$sql = "UPDATE 
							users
						SET
							unfollowing= APPEND_TO_SET( $accountID, unfollowing)
						WHERE 
							user_id=$oneUser[user_id]";

				$this->db->connect->exec( $sql );
			}
		}
	}


}