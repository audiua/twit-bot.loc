<?php
require_once( $_SERVER['DOCUMENT_ROOT'] . '/protected/class/twitteroauth/twitteroauth.php' );

/**
* Класс для работы с твиттер API сервером
*
* @author audiua <audiua@yandex.ru>
*/
class TwitterAPI extends Base{

	/**
	* Подключаем трейт с методами типа get
	*/
	use TwitterAPIGET;

	/**
	* Подключаем трейт с методами типа post
	*/
	use TwitterAPIPOST;

	/**
	* @var object соединение с сервером твиттера
	*/
	public $twitter;

	/**
	* @var object Account
	*/
	public $account;


	function __construct( Account $account ){
		parent::__construct();

		// соединяемся с сервером твиттера 
		$this->twitter = new TwitterOAuth( 
			$account->costumer_key,
			$account->costumer_secret,
			$account->access_token,
			$account->access_token_secret
		);

		$this->twitter->host = 'https://api.twitter.com/1.1/';

		// сохраняем обьект аккаунта
		$this->account = $account;
	}

	/**
	* Метод проверки лимита запросов
	* Если запросы выходят за лимит, то удаляем запуск этого метода из росписания до обновления лимита
	*
	* @param string  полное название запроса  который был запущен
	*/
	function writeLimitRate( $action ){
		if( isset( $this->twitter->http_header['x_rate_limit_remaining'], $this->twitter->http_header['x_rate_limit_reset'] ) ){
			$remeining = $this->twitter->http_header['x_rate_limit_remaining'];
			$reset = $this->twitter->http_header['x_rate_limit_reset'];

			$this->account->db->addLimit( $action, $remeining, $reset );
		} else {
			// нужно записать общий лимит
			$limit = $this->twitter->get('application/rate_limit_status');
			foreach( $limit->resources->application as $one ){
				$remeining = $one->remaining;
				$reset = $one->reset;
			}
			$this->account->db->addLimit( $action, $remeining, $reset );
		}

		// записываем данные о лимите метода в статический масив
		// App::$accountLimit[$this->account->account_id][$action] = array( 'remeining'=>$remeining, 'reset'=>$reset );
		return;
	}


	public static function getAccountId( $param ){
		
		// $connect = new TwitterOAuth( 
		// 	$param['costumer_key'],
		// 	$param['costumer_secret'],
		// 	$param['access_token'],
		// 	$param['access_token_secret']
		// );

		// $accountId = $connect->get(
		// 	'users/show', 
		// 	array(
		// 		'screen_name'=>$param['login'], 
		// ));

		// file_put_contents( $_SERVER['DOCUMENT_ROOT'].'/getAccountId.txt', serialize($accountId));
		$accountId = unserialize(file_get_contents( $_SERVER['DOCUMENT_ROOT'].'/getAccountId.txt'));

		return $accountId;
	}


	// проверяем лимит запросов
	function checkLimit(){
		$check = $this->_connection->get('application/rate_limit_status');

		$str = serialize( $check );
		file_put_contents( $_SERVER['DOCUMENT_ROOT'] 
			. '/protected/runtime/log/checkLimit_' 
			. date('__Y_m_d-H_i_s__').'.txt', $str."\n", FILE_APPEND);

		// проверяем на принадлежность метода до ограничения

		return $check;
	}

}