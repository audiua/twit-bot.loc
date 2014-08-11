<?php

/**
* Основной класс - движок
* запускает только файлы из расписания
* вся логика первых запусков и последних должна быть в файлах расписания
*
* @author audiua
*/
class App extends Base{

	/**
	* Подключаем трейт сокет соединения
	*/
	use SpareSocket;

	/**
	* Соединение с базой данных
	* @var $db object
	*/
	public $db; 

	/**
	* статический массив аккумулятор для хранения лимитов запросов  
	*
	* @var $accountLimit  array
	*/
	public static $accountLimit = array();

	function __construct(){
		parent::__construct();
		$this->db = new DataBase();
		Logger::write( 'Запуск App ', basename(__FILE__) , __LINE__ );		
	}

	/**
	* Демон - цикл который запускает файлы расписания
	*/
	public function run(){

		// получаем блокировку файла
		$handler = $this->lock();
		if( ! is_resource( $handler ) ){
			return false;
		}

		// главный цикл - пока установлен флаг 
		while( $this->config->getRun() ){
			// Logger::write( 'loop' , basename(__FILE__), __LINE__ );

			// sleep( 30 );
			// continue;

			// получаем файлы которые необходимо запустить за прошедшие 15 мин макс
			// (ограничение для предотвращения большого количества запусков)
			$condition = $this->db->getCondition(
				array( 
					array( 
						'field' => 'start_time',
						'mode' => '<',
						'value' => time(),
						'moreCondition' => ' AND '
					),
					array( 
						'field' => 'start_time',
						'mode' => '>',
						'value' => time()-(15*60),
						'moreCondition' => ''
					),
				)
			);
			// echo $condition;
			// die;
			$this->db->connect->beginTransaction();
			$files = $this->db->get( 'schedule', array('*'), $condition, 0, PDO::FETCH_ASSOC );
			// print_r($files);
			// die;
			unset( $condition );
			
			// сразу удаляем с базы полученые файлы (время запуска которых меньше текущего время)
			$condition[] = $this->db->getCondition(
				array( 
					array( 
						'field' => 'start_time',
						'mode' => '<',
						'value' => time(),
						'moreCondition' => ''
					)
				)
			);
			$this->db->deleteData( 'schedule', $condition, false );
			unset( $condition );
			
			$this->db->connect->commit();
			unset( $file );

			echo '<pre>';
			// print_r( $files );
			// die;

			// если нет файлов на запуск - запускаем генератор расписания на завтра
			if( empty( $files ) ){

				// количество файлов на запуск на сегодня
				$count = $this->db->getCount( 'schedule' );
				// print_r($count);
				// die;

				if( $count == 0 ){
					unset( $count );

					// конец рабочего дня

					// запускаем зачистку временных или ненужных файлов и данных
					$this->clear();
					
					// генератор расписание
					print_r($this->addAllAccountsSchedule());
					
					// уходим в lite режим до завтрашнего первого запуска
					$tomorowFirstStart = $this->db->getAgrigate( 'MIN', 'schedule' , 'start_time' );
					if( $tomorowFirstStart ){
						unset( $tomorowFirstStart );

						// лайт режим в ночное время - будет спать до завтрашего первого запуска - 1 мин
						$sleepTime = $tomorowFirstStart - time() - 60;
						sleep( $sleepTime );
						unset( $$sleepTime );
						continue;
					}
				} else {

					// проверяем сколько времени до ближаешего запуска
					$minTime = $this->db->getAgrigate( 'MIN', 'schedule' , 'start_time' );
					// echo time() . '<br>';
					// print_r($minTime);
					// die;
					if( $minTime ){
						$sleepTime = $minTime - (time());
						unset( $minTime );

						// если до ближайшего запуска больше чем итерация цика то спим это время
						if( $sleepTime > $this->config->loopStep ){
							Logger::write( 'sleep '.$sleepTime , basename(__FILE__), __LINE__ );
							sleep( $sleepTime + 1 );
							unset( $sleepTime );
							continue;
						}
					}
				}	
			}

			// запускаем файлы
			foreach( $files as $i => &$file ){

				// $this->writeLog('inforeach');
				// die;
				$success = $this->spareSocket( $file );
				// print_r($success) ;
				// die;
				// Logger::write( serialize($file) ,basename(__FILE__) , __LINE__ );
				// die;
				if( ! $success ){
					Logger::write( 'Ошибка соkет соединения', basename(__FILE__) , __LINE__ );
					die;
				} 
				unset( $success, $files[$i] );

				// пауза между запусками вспомогательных файлов 0,1 
				usleep( 100000 );
			}
			unset( $file, $files, $i );

			// break;

			// периодичность c паузой
			sleep( $this->config->loopStep );
		}

		// отпираем файл
		$this->unlock( $handler );
	}

	/**
	* Метод блокировки файла - для предотвращения повторного запуска демона
	*
	* @return boolean  если блокировка не удалась значит файл заблокирован
	*/
	function lock(){
		$handler = fopen( $this->config->lockDir . '/run.lock', 'r+' );

		// выполняем эксклюзивную блокировку
		$flock = flock( $handler, LOCK_NB | LOCK_EX );

		if ( $flock ) { 
			Logger::write( 'Заблокировал файл' , __FILE__ , __LINE__ );
			return $handler;
		} 

		Logger::write( 'Обьект этого класа уже запущен!', __FILE__ , __LINE__ );
		return false;

	}

	/**
	* Метод розблокировки файла при завершении работы демона
	*
	* @param $handler resours
	* @return boolean
	*/
	function unlock( $handler ){
		flock( $handler, LOCK_UN );
		fclose( $handler );
		Logger::write( 'Разблокировал файл' , basename(__FILE__) , __LINE__ );
	}

	/**
	* Метод запуска расписания для всех аккунтов
	*
	* @return boolean
	*/
	function addAllAccountsSchedule(){
		$this->db->clearTable( 'schedule' );

		// получаем всех аккаунтов
		$allAccounts = $this->db->get( 'accounts' );
		// print_r( $allAccounts );
		// die;

		foreach( $allAccounts as $i => &$oneAccount ){
			$account = new Account( $oneAccount->account_id );
			$schedule = new Schedule( $account );
			$schedule->schedule();
			// print_r( $schedule );
			// die;

			// return  $accountSchedule = $schedule->schedule();
		}
		Logger::write( 'Расписание всех акаунтов' , basename(__FILE__) , __LINE__ );
		return true;
	}

	/**
	* Зачистка временных или ненужных данных
	*
	* @return boolean
	*/
	function clear(){
		$logDir = $_SERVER['DOCUMENT_ROOT'] . '/protected/runtime/log/';

		// удалить старые логи
		$logFiles = scandir( $logDir );
		$week = 86400 * 7;
		foreach( $logFiles as $file ){
			if( $file == '.' || $file == '..'  ){
				continue;
			}
			if( file_exists( $logDir.$file ) ){

				// если файлы старше недели - удаляем
				if( time() - filemtime( $logDir.$file ) > $week  ){
					unlink( $logDir.$file );
				}
			}
		}

		// удалить вчерашние твиты, если их > 100
		$answer =array();
		$count = $this->db->getCount('twits');
		if( $count > 100 ){

			// сегодня полночь
			$today = strtotime( "00:00:01" );

			// удаляем все вчерашние твиты
			$condition[] = $this->db->getCondition(
				array( 
					array( 
						'field' => 'create',
						'mode' => '<',
						'value' => $today,
						'moreCondition' => ''
					) 
				) 
			);
			$this->db->deleteData( 'twits', $condition );
			$answer[] = $this->db->connect->errorInfo();
		}

		// удалить вчерашние ретвиты, если их > 100
		$count = $this->db->getCount('retweets');
		if( $count > 100 ){

			// сегодня полночь
			$today = strtotime( "00:00:01" );

			// удаляем все вчерашние твиты
			$condition[] = $this->db->getCondition(
				array( 
					array( 
						'field' => 'create',
						'mode' => '<',
						'value' => $today,
						'moreCondition' => ''
					) 
				) 
			);
			$this->db->deleteData( 'retweets', $condition );
			$answer[] = $this->db->connect->errorInfo();
		}

		// удаляем старые message
		$this->db->clearTable( 'message' );

		// return $answer;
		return true;
	}
 }