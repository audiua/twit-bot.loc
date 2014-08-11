<?php 

/**
* Класс аккаунта
*
* @author audiua <audiua@yandex.ru>
*/
class Account extends Base{

use SearchTwits;
use Parser;
use Links;

/**
* мета данные аккаунта
* @var $account_id int
* @access public
*/
public $account_id;

/**
* мета данные аккаунта
* @var $screen_name string
* @access public
*/
public $screen_name;

/**
* мета данные аккаунта
* @var $password string
* @access public
*/
public $password;

/**
* мета данные аккаунта
* @var $followers_count int
* @access public
*/
public $followers_count;

/**
* мета данные аккаунта
* @var $friends_count int
* @access public
*/
public $friends_count;

/**
* мета данные аккаунта
* @var $last_post_lenta int
* @access public
*/
public $last_post_lenta;

/**
* мета данные аккаунта
* @var $accountConfig array
* @access public
*/
public $accountConfig;

/**
* данные аккаунта для подлючения к серверу твиттера
* @var $access_token string
* @access public
*/
public $access_token;

/**
* данные аккаунта для подлючения к серверу твиттера
* @var $costumer_key string
* @access public
*/
public $costumer_key;

/**
* данные аккаунта для подлючения к серверу твиттера
* @var $costumer_secret string
* @access public
*/
public $costumer_secret;

/**
* данные аккаунта для подлючения к серверу твиттера
*
* @var $access_token_secret string
* @access public
*/
public $access_token_secret;

/**
* обьект базы данных - с соединением и трейтами
* @var $db object
* @access public
*/
public $db;

/**
* обьект твиттера - с соединением и трейтами
* @var $twitter object
* @access public
*/
public $twitter;

function __construct( $account_id ){

	$this->db = new DataBase( $account_id );
	$condition = $this->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $account_id,
				'moreCondition' => ''
			) 
		) 
	);

	$account = $this->db->get( 'accounts', array('*'), $condition );
	if( empty( $account ) ){
		throw new Exception( 'аккаунта не существует' );
	} else {
		$account = $account[0];
	}

	$this->account_id = $account->account_id;
	$this->screen_name = $account->screen_name;
	$this->password = $account->password;
	$this->followers_count = $account->followers_count;
	$this->friends_count = $account->friends_count;
	$this->last_post_lenta = $account->last_post_lenta;
	$this->access_token = $account->access_token;
	$this->costumer_key = $account->costumer_key;
	$this->costumer_secret = $account->costumer_secret;
	$this->access_token_secret = $account->access_token_secret;
	
	// TODO дефолтные мержить с настройками аккаунта так что бы настройки аккаунта перекрывали дефолтные
	// настройки аккаунта хранить в базе в виде сериализированого массива
	$this->accountConfig = $this->getAccountConfig( $account->config );

	$this->twitter = new TwitterAPI( $this );
}


/**
* Формируем настройки аккаунта
*
*/
private function getAccountConfig( $config ){
	$defaultAccountConfig = include( $_SERVER['DOCUMENT_ROOT'] . '/protected/config/accountConfig.php' );

	if( ! empty( $config ) ){
		return array_merge( unserialize( $accountConfig ), $defaultAccountConfig );
	} else {
		return $defaultAccountConfig;
	}
}

/**
* Синхронизация данных аккаунта с сервером твиттера
*
* @access public
* @param $modeUser array  вид юзеров которых нужно обновить ('followers', 'friends')
* @return boolean
*/
function syncAccount( array $mode = array( 'followers', 'friends' ) ){
	$arCount = array();

	// фолловеров и друзей
	foreach( $mode as $oneMode ){

		// получаем данные с сервера твиттера + кеш
		$file = $_SERVER['DOCUMENT_ROOT'] .'/protected/runtime/cache/'. $this->screen_name .'_'. $oneMode . '.txt';
		if( file_exists( $file ) && ( time() - filemtime( $file ) ) < 1000 ){
			$tUsers = unserialize( file_get_contents( $file ) );
		} else {
			$tUsers = $this->twitter->getUsers( $oneMode );
			file_put_contents( $file, serialize( $tUsers ) );
		}
		// print_r( $tUsers );
		// die;

		if( empty( $tUsers ) ){
			// если данных с твиттера нет то выходим
			continue;
		}

		//запишем в базу количество юзеров аккаунта - для статистики
		$this->db->addAccount( array( 'account_id' =>$this->account_id, $oneMode.'_count' => count( $tUsers->ids ) ) );

		// фолловеры с базы данных
		$condition = $this->db->getCondition(
			array( 
				array( 
					'field' => 'account_id',
					'mode' => '=',
					'value' => $this->account_id,
					'moreCondition' => ''
				) 
			)
		);
		$dbUsers = $this->db->get( $oneMode, array('user_id'), $condition, 0, PDO::FETCH_ASSOC );
		// print_r($dbUsers);
		// die;

		// если база пуста
		if( empty( $dbUsers ) ){

			// записываем новых юзеров в таблицу юзеров
			foreach( $tUsers->ids as &$user ){
				$addUsers[] = array('user_id'=>$user);
			}

			// пишем в таблицу юзеров
			$this->db->addUsers( $addUsers );
			unset( $addUsers, $user );

			// формируем список поле=значение поля которое нужно добавить
			foreach( $tUsers->ids as &$oneUser ){
				$assocUsers[] = "`account_id`=$this->account_id, `user_id`=$oneUser";
			}
			unset( $tUsers, $oneUser );
			// print_r( $assocUsers );
			// die;

			// пишем в таблицу фолловеров или друзей
			$this->db->addData( $oneMode, $assocUsers );
			$assocUsers = array();
		}

		// юзеры с базы
		$tmpDbUsers = array();
		foreach( $dbUsers as &$user ){
			$tmpDbUsers[] = $user['user_id'];
		}
		$dbUsers = $tmpDbUsers;
		unset( $tmpDbUsers, $user );
		// print_r($dbUsers);
		// die;

		// получаем по массивам юзеров на удаление и на добавление
		$diffUsers = $this->getDiff( $tUsers->ids, $dbUsers );
		unset( $dbUsers, $tUsers );
		// print_r($diffUsers);
		// die;

		// синхронизируем базу
		// $key == add || delete
		foreach( $diffUsers as $key => $value ){
			if( empty( $value ) ){
				continue;
			}
			// print_r($value);
			// die;

			// в базе юзерa такого нету
			// пишем их в талицу юзеров и таблицу фолловеров или друзей
			if( $key == 'add' ){
				$time = time();
				foreach( $value as &$val ){

					// вставка в таблицу юзеров
					$addUsers[] = array( 'user_id'=>$val );

					// вставка в таблицу фол или друзей
					$assocUsers[]="`account_id`=$this->account_id, `user_id`=$val";
					$assocNewFollowers[]="`account_id`=$this->account_id, `user_id`=$val, `create_time`=$time";

				}
				unset( $val );

			} else {

				foreach( $value as &$val ){

					// условие для удаления
					$assocUsers[]= "`account_id`=$this->account_id AND `user_id`=$val" ;
				}
				unset( $val );
			}

			// добавляем юзеров
			if( isset( $addUsers ) && !empty( $addUsers ) ){
				$this->db->addUsers( $addUsers );
				$addUsers = array();
			}
			// echo $key;
			// print_r($assocUsers);
			// die;

			// удаляем или записываем фолловеров или друзей
			if( isset( $assocUsers ) && !empty( $assocUsers ) ){

				// добавляем новых фолловеров в таблицу new_followers - и отправляем им письма благодарности за подписку
				if( $key == 'add' && $oneMode == 'followers' ){
					$this->db->addData( 'new_followers', $assocNewFollowers );
				}

				$action = $key.'Data';
				$this->db->$action( $oneMode, $assocUsers );
				$assocUsers = array();
			}
			
		}
	}

	// добавляем анфолловинг
	Logger::write( 'Cинхр ' . $this->screen_name , __FILE__ , __LINE__ );
	$this->addUnfollowing();
}

/**
* Добавления данных о фолловерах и анфолловерах в базу для статистики
*
*/
function addStatistics(){
	
	// после синхронизации в базе точное количество фолловеров и анфолловеров
	$condition = $this->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $this->account_id,
				'moreCondition' => ''
			) 
		) 
	);
	$account = $this->db->get( 'accounts', array('*'), $condition );
	$data = array( 'account_id'=>$account[0]->account_id, 
		'followers_count'=> $account[0]->followers_count, 
		'friends_count'=> $account[0]->friends_count ,
		'statuses_count'=> $account[0]->statuses_count );
	$this->db->addStatistics( $data );
	Logger::write( 'Стат ' . $this->screen_name , __FILE__ , __LINE__ );
	return true;	

}


/**
* Получаем разницу между юзерами сервера твиттера и локальной базой
*
* @access public
* @param $tUsers array  ссылка на список юзеров с сервера твиттера, текущего акаунта
* @param $dbUsers array  ссылка на список юзеров с базы. текущего акаунта
* @return array  асоциативный массив с ключами array('add'=>array(), 'delete'=>array())
*/
function getDiff( $tUsers, $dbUsers ){

	// юзеры которых нет в аккаунта, а есть на сервере, необходимо записать аккаунту
	$result['add'] = array_diff( $tUsers, $dbUsers );

	// юзеры которые есть в аккаунта, но нет на сервере необходимо удалить у аккаунта
	$result['delete'] = array_diff( $dbUsers, $tUsers );


	return $result;
}

/**
* Задаем юзеров на анфолловинг после синхрониации
*
*/
function addUnfollowing(){
	// $time =time() - $this->accountConfig['timeAnswerFollowing'] * 86400;
	$unfollowUsersObject = $this->db->getUnfollowUsers();
	if( empty( $unfollowUsersObject ) ){
		$unfollowUsers = array();
	} else {
		$unfollowUsers =  array();
		foreach( $unfollowUsersObject as $i => &$oneUser ){
			$unfollowUsers[] = $oneUser->user_id;
			unset( $unfollowUsersObject[$i] );
		}
		unset( $unfollowUsersObject, $oneUser, $i );
	}

	// print_r($unfollowUsers);
	// die;

	// получаем юзеров время которых еще не вышло на ожидания
	$condition = $this->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $this->account_id,
				'moreCondition' => ' AND '
			),
			array( 
				'field' => 'create_time',
				'mode' => '>',
				'value' => time() - $this->accountConfig['timeAnswerFollowing'] * 86400,
				'moreCondition' => ''
			)
		)
	);
	$oldTryFollowers = $this->db->get( 'try_following', array( 'user_id' ), $condition );
	unset($condition);

	// сразу удаляем из таблицы
	$condition[] = $this->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $this->account_id,
				'moreCondition' => ' AND '
			),
			array( 
				'field' => 'create_time',
				'mode' => '<',
				'value' => time() - $this->accountConfig['timeAnswerFollowing'] * 86400,
				'moreCondition' => ''
			)
		)
	);
	$this->db->deleteData( 'try_following', $condition );
	unset( $condition );
	// print_r( $tryFollowers );
	// die;
	
	if( empty( $oldTryFollowers ) ){
		unset($oldTryFollowers);
		$tryFollowersId = array();
	} else {
		$tryFollowersId =  array();
		foreach( $oldTryFollowers as $i => &$oneF ){
			$tryFollowersId[] = $oneF->user_id;
			unset( $oldTryFollowers[$i] );
		}
		unset( $oldTryFollowers, $oneF, $i );
	}
	// print_r( $tryFollowersId );
	// die;

	// получаем юзеров на анфоллов
	$unfollowers = array_diff( $unfollowUsers, $tryFollowersId );
	unset( $unfollowUsers, $tryFollowersId );
	// print_r( $unfollowers );
	// die;

	// фильтр по языкам (пока по рускому)
	$sql = "SELECT 
				friends.user_id
			FROM 
				friends, users
			WHERE
				friends.account_id = $this->account_id
			AND
				friends.user_id = users.user_id
			AND
				users.lang <> 'ru' ";
	$langFilter = $this->db->connect->query( $sql )->fetchAll( PDO::FETCH_OBJ );
	unset( $sql );

	if( empty( $langFilter ) ){
		$onlyLang = array();
	} else {
		foreach( $langFilter as $i => &$lang ){
			$onlyLang[] = $lang->user_id;
			unset( $langFilter[$i] );
		}
		unset( $langFilter, $lang, $i );
	}
	// print_r( $onlyLang );
	// die;


	$unfollowers = array_merge( $onlyLang, $unfollowers );
	unset( $onlyLang, $langFilter, $lang, $sql );
	// print_r( $unfollowUsers );
	// die;


	// формируем массив
	$time = time();
	foreach( $unfollowers as &$oneUser ){
		$assocUsers[]="`account_id`=$this->account_id, `user_id`=$oneUser, `create_time`=$time";
	}
	// print_r($assocUsers);
	// die;

	$this->db->addData( 'unfollowing', $assocUsers );
	Logger::write( 'Опр анфол ' . $this->screen_name , __FILE__ , __LINE__ );

	return;
}


/**
* Анфолловинг
*
* @param $count int  количество юзеров на афолловинг
*/
function unfollow( $count ){

	$condition = $this->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $this->account_id,
				'moreCondition' => '',
			)
		)
	);

	// получаем юзеров на анфоллов
	$unfollowUsers = $this->db->get( 'unfollowing', array( 'user_id' ), $condition, $count );
	if( empty( $unfollowUsers ) ){
		return;
	}
	unset( $condition );
	// print_r( $unfollowUsers );
	// die;

	// формируем массив
	foreach( $unfollowUsers as &$oneUser ){
		$tmpUnfollow[] = $oneUser->user_id;
	}
	unset( $unfollowUsers, $oneUser );
	// print_r( $tmpUnfollow );
	// die;

	// удаляем с твиттера - возвращает удаленные юзеры
	$destroyUsers = $this->twitter->actionFriends( $tmpUnfollow, 'destroy' );
	unset( $tmpUnfollow );

	// TEST
	// $destroyUsers = array();
	// $destroyUsers[] = 277416059;

	if( !empty( $destroyUsers ) ){

		// удаляем с таблицы анфолловинга
		foreach( $destroyUsers as &$oneUser ){
			$assocUsers[]= "`account_id`=$this->account_id AND `user_id`=$oneUser" ;
			$increment[] = array('user_id'=>$oneUser,'unsuccessful_try'=>1);
		}
		unset( $destroyUsers, $oneUser );

		// удаляем с анфолловеров и с друзей
		$this->db->deleteData( 'unfollowing', $assocUsers );
		$this->db->deleteData( 'friends', $assocUsers );
		$this->db->deleteData( 'try_following', $assocUsers );
		unset( $assocUsers );

		// инкрементируем неудачную попытку
		$this->db->addUsers( $increment );
		unset( $increment );

		// Logger::write( 'анфол ' . $this->screen_name , __FILE__ , __LINE__ );
		// return $result;
		return true;
	}
}

/**
* Добавление юзеров в друзья с надежной на взаимный фоловинг
*
* TODO переделать на поиск минимального неудачных попыток фолловига
* @param $count int
*/
function follow( $count ){

	$followers = $this->db->getFollowers( $count );
	if( empty( $followers ) ){

		// ищем новых юзеров
		$search =new SearchUsers( $this );
		$search->searchUsers();

		$name = $this->screen_name;
		$id= $this->account_id;
		Logger::write( "($id)$name не найдено кандидатов на фол", basename(__FILE__), __LINE__ );
		return;
	}
	// return $followers;
	// print_r($followers );
	// die;

	// формируем массив
	foreach( $followers as &$oneUser ){
		$tmpFollow[] = $oneUser->user_id;
	}
	unset( $followers, $oneUser );
	// return $tmpFollow;

	// добавляем в твиттер - возвращает добавленные юзеры 
	$createUsers = $this->twitter->actionFriends( $tmpFollow );
	// unset( $tmpFollow );
	// print_r($createUsers);
	// return $createUsers;

	//TEST
	// $createUsers = array();
	// $createUsers[] = 277416059;


	if( !empty( $createUsers ) ){

		// формируем массив для обновления в базе юзеров
		$time = time();
		foreach( $createUsers as &$oneUser ){
			$assocUsers[]="`account_id`=$this->account_id, `user_id`=$oneUser, `create_time`=$time";
			$frAssocUsers[] ="`account_id`=$this->account_id, `user_id`=$oneUser";
		}
		unset( $createUsers, $oneUser );

		// записываем в таблицу попытки фолловинга
		$this->db->addData( 'try_following', $assocUsers );
		$this->db->addData( 'friends', $frAssocUsers );
		// Logger::write( 'фол ' . $this->screen_name , __FILE__ , __LINE__ );
		return true;
	}
}

/**
* публикация
*
* @param $mode string  модификатор ( 'hello', 'bay', 'link', 'twit', 'msg', 'retwit' )
*/
function twit( $mode = 'twit' ){

	switch( $mode ){

		case 'twit': 

			// добавить рандомность - юмор, анекдот. цитаты. фразы. афоризы + хештеги
			
			// необходимо получить с базы и сразу удалить
			$this->db->connect->beginTransaction();
			$twit = $this->db->get( 'twits', array( '*' ), '', 1 );
			// print_r($twit);
			// die;
			if( empty( $twit ) ){
				// return 'empty';
				$this->db->clearTable( 'twits' );
				return $this->searchTwits();
				break;
			}

			$condition[] = $this->db->getCondition(
				array( 
					array( 
						'field' => 'id',
						'mode' => '=',
						'value' => $twit[0]->id,
						'moreCondition' => ''
					)
				)
			);
			$this->db->deleteData( 'twits', $condition, false );
			$this->db->connect->commit();

			$success = $this->twitter->twit( $twit[0]->twit, 'twit' );
			// print_r($success) ;
			// print_r( $twit );
			break;

		// утренее приветствие
		case 'hello': 

			// добавить хеш тег утро, доброеутрро ...

			$condition = $this->db->getCondition(
				array( 
					array( 
						'field' => 'category',
						'mode' => '=',
						'value' => "'welcome'",
						'moreCondition' => ''
					)
				)
			);
			// return $condition;
			$allHello = $this->db->get( 'msg', array('*'), $condition);
			$hello = $allHello[array_rand( $allHello )];


			if( mt_rand( 0, 99 ) < 35 ){

				$condition = $this->db->getCondition(
					array( 
						array( 
							'field' => 'category',
							'mode' => '=',
							'value' => "'hello'",
							'moreCondition' => ''
						) 
					) 
				);
				$allImg = $this->db->get( 'img', array('link'), $condition );

				$img = $allImg[ array_rand( $allImg ) ];
				$hello->text .= "\n " . $img->link;
			}

			// return $hello;

			$this->twitter->twit($hello->text); 

			// публикация сообщения с тематики приветствия
			// return;
			break;

		// пока
		case 'bay': 

			// публикация сообщения с тематики прощания
			// return;
			break;

		// ответы на фразы юзеров
		case 'message': 

			$time=time();
			$condition = $this->db->getCondition(
				array( 
					array( 
						'field' => 'account_id',
						'mode' => '=',
						'value' => $this->account_id,
						'moreCondition' => ' AND '
					),
					array( 
						'field' => 'create_time',
						'mode' => '>',
						'value' => $time - ( 30 * 60 ),
						'moreCondition' => ''
					),
				)
			);
			// return $condition;
			$message = $this->db->get( 'message', array('*'), $condition, 1 );
			unset($condition);
			// print_r($message);
			// return $message;
			// die;
			if( empty( $message ) ){
				return;
			}

			$this->twitter->twit($message[0]->message);

			$condition[] = $this->db->getCondition(
				array( 
					array( 
						'field' => 'id',
						'mode' => '=',
						'value' => $message[0]->id,
						'moreCondition' => ''
					)
				)
			);
			// return $condition;
			$this->db->deleteData( 'message', $condition );

				


			// return;
			break;

		// публикуем ссылки + хештеги
		case 'link': 
			
			break;

		// если твит будет работать после 21.45 - говорить всем спокойной ночи
		case 'sleep': 
			
			break;

		// публиковать хеши про взаимный фолловинг
		case 'followBack': 
			
			break;

		// рекомендовать юзеров которые меня реплеели
		case 'followMyFriend': 
			
			break;

		case 'retweet' : 

			$this->db->connect->beginTransaction();
			$retwit = $this->db->get( 'retweets', array( '*' ), '', 1 );
			if( empty( $retwit ) ){
				$this->db->clearTable( 'retweets' );
				break;
			}
			$condition[] = $this->db->getCondition(
				array( 
					array( 
						'field' => 'retweet',
						'mode' => '=',
						'value' => $retwit[0]->retweet,
						'moreCondition' => ''
					)
				)
			);
			$this->db->deleteData( 'retweets', $condition, false );
			$this->db->connect->commit();
			// print_r($retwit[0]->retwit);
			// die;

			$success = $this->twitter->twit( $retwit[0]->retweet, 'retweet' );
			// print_r( $success );
			break;

		// ретвиты с ссылками
		case 'link_retweet' :
			print_r($this->linkRetweet());
			break;

		default : 

			break;
	}

	// Logger::write( $this->screen_name . '-'. $mode, __FILE__, __LINE__ );
	return;
}

/**
* Метод обновления данных юзера
*/
function updateUserData(){

	// получаем юзера у которого не полные данные - самое старое время обновления
	$userMinUpdateTime = $this->db->getAgrigate( 'min', 'users', 'update_time' );
	if( ! $userMinUpdateTime ){
		return;
	}
	$condition = $this->db->getCondition(
		array( 
			array( 
				'field' => 'update_time',
				'mode' => '=',
				'value' => $userMinUpdateTime,
				'moreCondition' => ''
			)
		)
	);
	$user = $this->db->get( 'users', array('*'), $condition , 1 );
	// return $user;

	// print_r( $user[0]->user_id );
	// die;
	$twUser = $this->twitter->showUser( $user[0]->user_id, 'updateUserData' );
	// return $twUser;
	if( isset( $twUser->errors, $twUser->errors[0]->code ) && $twUser->errors[0]->code == 34 ){
		$ban = "BAN";
		$assocUsers[] = array( 
			'user_id' => $user[0]->user_id, 
			'description' => $ban,
			'name'=> $ban,
			'screen_name'=>$ban,
			'location'=>$ban,
			'lang'=>$ban,
			'followers_count'=>10000,
			'friends_count'=>1,
			'unsuccessful_try'=>10,
			'update_time'=>time()
		 );
	} else {
		if( isset( $twUser->status ) && isset( $twUser->status->created_at ) ){
			$cteate = strtotime( $twUser->status->created_at );
		} else {
			$cteate = 1;
		}
		$assocUsers[] = array( 
			'user_id' => $twUser->id, 
			'description' => $twUser->description,
			'name'=> $twUser->name,
			'screen_name'=>$twUser->screen_name,
			'location'=>$twUser->location,
			'lang'=>$twUser->lang,
			'followers_count'=>$twUser->followers_count,
			'friends_count'=>$twUser->friends_count,
			'last_public'=>$cteate,
			'update_time'=>time()
		 );
	}

	// return $assocUsers;
	// die;
	

	$this->db->addUsers( $assocUsers );
	// Logger::write( $this->screen_name . '-'. 'updateUserData-'.$user = (isset($twUser->screen_name))?$twUser->screen_name:'BAN', __FILE__, __LINE__ );
	return true;
}

/**
* Метод зачистки данных
*
*
*/
function clear(){

}

/**
* Поиск юзеров
*/
function searchUsers(){
	$search = new SearchUsers( $this );
	$search->searchUsers();
	return true;
}

/**
* Метод проверки лимита запусков
*
* @param $action string  метод который надо проверить
* @param $param mixid
* @return boolean
*/
function checkLimit( $action, $param = NULL ){
	$condition = $this->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $this->account_id,
				'moreCondition' => ' AND '
			),
			array( 
				'field' => 'action',
				'mode' => '=',
				'value' => "'".$action."'",
				'moreCondition' => ''
			)
		)
	); 

	$limit = $this->db->get( 'limit_action', array('*'), $condition );
	// return $limit[0]->remeining;

	if( empty( $limit ) ){
		Logger::write( "($this->account_id)$this->screen_name" . ' пустой экшн-' . $action, basename(__FILE__), __LINE__ );
		return true;
	}
	$rem = $limit[0]->remeining;
	Logger::write( "($this->account_id)$this->screen_name" . " $action($param) лимит-$rem : ", basename(__FILE__), __LINE__ );

	if( isset( $limit->remeining, $limit->reset_time ) ){
		$topicalResetTime = time() - ( 15 * 60 );
		if( $limit->reset_time > $topicalResetTime ){

			// разрешаем запуск при лимите > 3
			if( $limit->remeining <= 3 ){
				return false;
			}
		}
	}


	return true;
}

/**
* Ответный фолловинг
*
*/
function backFollow( $count ){

	// получаем фоллловеров которых мы не читаем
	$backFollowers = $this->db->getBackFollowers( $count );

	// print_r($backFollowers);
	// die;
	
	if( empty( $backFollowers ) ){
		return;
	}


	// формируем массив и фолловим в ответ
	// формируем массив
	foreach( $backFollowers as &$oneUser ){
		$tmpFollow[] = $oneUser->user_id;
	}
	unset( $backFollowers, $oneUser );
	// return $tmpFollow;

	// добавляем в твиттер - возвращает добавленные юзеры 
	$createUsers = $this->twitter->actionFriends( $tmpFollow );

	unset( $tmpFollow );

	//TEST
	// $createUsers = array();
	// $createUsers[] = 277416059;


	if( !empty( $createUsers ) ){

		// формируем массив для обновления в базе юзеров
		foreach( $createUsers as &$oneUser ){
			$assocUsers[]="`account_id`=$this->account_id, `user_id`=$oneUser";
		}
		unset( $createUsers, $oneUser );

		// записываем в таблицу попытки фолловинга
		$this->db->addData( 'friends', $assocUsers );
		$this->twitter->writeLimitRate( 'backFollow' );
		Logger::write( 'backFollow ' . $this->screen_name , __FILE__ , __LINE__ );
		return true;
	}
}

/**
* Отпрвка личных сообщений новым фолловерам - необходимо удалять новых фолловеров из базы которые старше 3 дней!
*
*/
function sendMassageNewFollowers( $count ){
	// return;

	$condition = $this->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $this->account_id,
				'moreCondition' => ''
			)
		)
	); 
	$newFollowers = $this->db->get( 'new_followers', array('user_id'), $condition, $count, PDO::FETCH_ASSOC );
	foreach( $newFollowers as $i => &$follower ){
		$follow[] = $follower['user_id'];
		unset( $newFollowers[$i] );
	}
	unset( $condition, $newFollowers, $follower, $i );

	// удаляем новых фолловеров с таблицы новых фолловеров
	foreach($follow as $one){
		$assocUsers[]= "`account_id`=$this->account_id AND `user_id`=$one" ;
	}
	$this->db->deleteData( 'new_followers', $assocUsers );
	unset($assocUsers, $one);

	// print_r( $follow );
	// die;

	// получаем сообщения для новых фолловеров
	$condition = $this->db->getCondition(
		array( 
			array( 
				'field' => 'category',
				'mode' => '=',
				'value' => "'".'new_followers'."'",
				'moreCondition' => ''
			)
		)
	); 
	$allMassage = $this->db->get( 'msg', array('text'), $condition, 0, PDO::FETCH_ASSOC );
	foreach( $allMassage as $i => &$message ){
		$text[] = $message['text'];
		unset( $allMassage[$i] );
	}
	unset( $allMassage, $i, $message, $condition );
	// print_r($text);
	// die;

	foreach ( $follow as $i => $one ) {
		$data[] = array( 'user_id'=>$one, 'message'=>$text[array_rand($text)] );
	}

	// print_r($data);
	// die;

	// отправляем сообщения
	$this->twitter->sendMassageNewFollowers( $data ); 

}







}