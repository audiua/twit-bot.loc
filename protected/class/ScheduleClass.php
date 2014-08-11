<?php

/** 
* Класс расписания запусков файлов
* Работает по 15-20 мин диапазонам - макимум 10 get и 10 post!
* Расписание будет запускаться ввечером после всех запусковфайлов аккаунта,
* С утра и вечером нужно запустить синхронизацию аккаунта
*
* @author audiua
*/
class Schedule{

/**
* @var $account object  обьект аккаунта для которого ведется расписание
*/
public $account;

/**
* @var $followToday boolean  флаг для фолловинга для аккаунта на день
*/
public $followToday = true;

/**
* @var $allAccountSchedule array  список всех расписаний аккаунта
*/
public $allSchedule = array();

/**
* @var $countFollowers int  количество фолловеров на день
*/
public $countFollowers = 0;

/**
* @var $countUnfollowers int  количество анфолловеров на день
*/
public $countUnfollowers = 0;

/**
* @var serchUsers boolean  флаг для поиска юзеров
*/
public $searchUsers = false;

/**
* @var $countBackFollowers int 
*/
public $countBackFollowers = 0;

/**
* @var $countNewFollowers int 
*/
public $countNewFollowers = 0;

/**
* @var $uniqTime array
*/
public $uniqTime = array();

function __construct( Account $account ){
	$this->account = $account;

	// расчитать количество анфолловеров - на сегодня(процент от фолловеров)
	$todayUnfollow = ceil( ( $this->getRandConfigValue( 'unfollowOfDay' ) * $this->account->followers_count ) / 100 );
	
	// получаем количество анфолловеров
	$condition = $this->account->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $this->account->account_id,
				'moreCondition' => ''
			)
		)
	);
	// print_r( $condition );
	// die;
	$allUnfollowers = $this->account->db->getCount( 'unfollowing', $condition );
	// print_r( $allUnfollowers );
	// die;
	
	// получаем реальное число анфоловеров на сегодня
	$this->countUnfollowers = ( $todayUnfollow > $allUnfollowers ) ? $allUnfollowers : $todayUnfollow ;
	if( $allUnfollowers > 20 && $todayUnfollow < 10 ){
		$this->countUnfollowers = mt_rand( 10, 20 );
	}
	unset( $todayUnfollow, $condition,$allUnfollowers );
	// print_r( $this->countUnfollowers );
	// die;

	// останавливаем на сегодня фолловинг если количество анфолловеров 
	// превышает лиммит разницы анфолловеров и фолловеров
	$diffUsers = $this->account->friends_count - $this->account->followers_count;
	// print_r($diffUsers);
	// die;
	$this->followToday = ( $diffUsers > $this->account->accountConfig['maxDiffUnfollowers'] ) ? false : true ;
	unset( $diffUsers );
	// var_dump( $this->followToday );
	// die;

	// расчитать количество фолловеров  на сегодня(процент от фолловеров)
	if( $this->followToday ){
		$this->countFollowers = (int) ( ( $this->getRandConfigValue('followOfDay') * $this->account->followers_count ) / 100 );
		if( $this->countFollowers < 10 ){
			$this->countFollowers = mt_rand( 10, 20 );
		}
	}

	// условие для поиска свободных для фолловинга юзеров
	$freeUsers =  count( $this->account->db->getFollowers( 0 ) );
	// print_r( $this->account->db->getFollowers( 0 ) );
	// print_r( $freeUsers );
	// die;

	if( $freeUsers < $this->account->accountConfig['minFreeUsers'] ){
		// TODO отключил пока не обновлятся данные по всем юзерам
		$this->searchUsers = true;
	}

	// количество юзеров на обратный фолловинг по настройкам
	$todayBackFollow = $this->getRandConfigValue('backFollowOfDay');
	// print_r( $todayBackFollow );
	// die;

	// реальное количество юзеров на обратный фолловинг - из бд
	$backFollowers = count( $this->account->db->getBackFollowers(0) );
	// print_r( $backFollowers );
	// die;
	$this->countBackFollowers = $todayBackFollow > $backFollowers ? $backFollowers : $todayBackFollow ;

	// количество новых фолловеров в базе
	$condition = $this->account->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $this->account->account_id,
				'moreCondition' => ''
			)
		)
	);
	$this->countNewFollowers = $this->account->db->getCount( 'new_followers', $condition );
	unset( $condition, $countNewFollowersInDb );
	
	Logger::write( "Росписание - $account->screen_name,  фолловеров: $this->countFollowers, анфолловеров: $this->countUnfollowers"   , __FILE__ , __LINE__ );
	return;
}


/**
* Возвращает рандомное значение из настроек по ключу
*
* @param $key string
* @return int  рандомное значение из настроек 
*/
private function getRandConfigValue( $key ){
	if( isset( $this->account->accountConfig[$key]['min'], $this->account->accountConfig[$key]['max'] ) ){
		return mt_rand( $this->account->accountConfig[$key]['min'], $this->account->accountConfig[$key]['max']);
	}
}

/**
* Возвращает true рандомно по заданому проценту
*
* @param $prc int
* @return boolean
*/
private function getPercentValue( $prc ){
	$randVal = mt_rand( 0, 99 );
	if( $randVal <= $prc ){
		return true;
	} else {
		return false;
	}
}

/**
* Уникальное время, так что записи для одного аккаунта не могут быть ближе 30 сек
*
* @param $rang array  массив временного диапазона
*/
public function getUniqTime( $rang ){

	// print_r( $rang );
	// die;

	$timeStart = mt_rand( $rang['startRang'], $rang['endRang'] );
	$notUniq = array( true );
	$start = $end = time();
	while( !empty( $notUniq )  ){
		$notUniq = array();

		// страховка на случай зацикливания 5 sek
		if( ( $end - $start ) > 5 ){
			$this->uniqTime[] = $timeStart;
			return $timeStart;
		}

		foreach( $this->uniqTime as $uniqTime ){

			// ближе 15 сек с низу и ближе 15 сек сверху - для всех запусков 
			if( ( $timeStart < $uniqTime && $timeStart + 15 > $uniqTime ) || ( $timeStart > $uniqTime && $timeStart - 15 < $uniqTime ) ){

				// ставим метку
				$notUniq[] = $uniqTime;
			}
		}

		// если нарушения диапазонов нет то возвращаем уникальное время
		if( empty( $notUniq ) ){
			$this->uniqTime[] = $timeStart;
			return $timeStart;
		}

		$timeStart = mt_rand( $rang['startRang'], $rang['endRang'] );
		$end = time();
	}

	$this->uniqTime[] = $timeStart;
	return $timeStart;
}

/**
* генерация росписания запусков файлов для аккаунта
*
* @return booloean
*/
public function schedule(){

	// var_dump();

	$options = array(); 

	// определяем время работы
	// время первой секунды завтрашнего дня
	$tomorow = strtotime( "+1 day 00:00:01" );

	// время начала работы = рандомное количество часов * секунды + рандомно секунд(чтобы начинать день всередине часа)
	$options['startDay'] = $this->getRandConfigValue('startDay') * 3600 + $tomorow + mt_rand( 0, 3000 );
	$options['endDay'] = $this->getRandConfigValue('endDay') * 3600 + $tomorow + mt_rand( 0, 1000 );
	unset($tomorow);

	// return $options;

	// разбить по периодам( 15 мин ) весь день
	$dayRangs = array();
	$startRang = $options['startDay'];
	while( $startRang < $options['endDay'] ){
		$rang = array(
			'startRang' => $startRang,
			'endRang' => $startRang + 15*60,
		);

		$dayRangs[] = $rang;

		// промежуток между диапазонами до 1-5 мин
		$startRang = $rang['endRang'] + mt_rand( 60, 300 );
	}
	unset( $startRang );
	unset( $rang );

	// return $dayRangs;

	// расписываем запуски по диапазонам
	$lastRangIndex = count( $dayRangs ) - 1;
	// return $lastRangIndex;

	// return $this->getUniqTime( $dayRangs[0] );

	/** первые и последние запуски можно расписать вне цикла, что упростит несколько проверок **/
	// первый диапазон - запуск первых файлов
	// запустить синхронизацию аккаунта - старт первого диапазона минус 5 мин
	$this->addTimeStart( 'syncAccount', $dayRangs[0]['startRang'] - 300 );

	// запустить запись статистики
	$this->addTimeStart( 'addStatistics', $dayRangs[0]['startRang'] - 200 );

	// запустить запись статистики
	// $this->addTimeStart( 'addUnfollowing', $dayRangs[0]['startRang'] - 150 );

	// запустить приветствие - проверять уникальность время не нужно - синхронизация запуститься ранише.
	$this->addTimeStart( 'twit', $this->getUniqTime( $dayRangs[0] ), 'hello' );
	// print_r( $this->uniqTime );
	// die;

	/** последний диапазон **/
	// запустить прощание
	$this->addTimeStart( 'twit', $this->getUniqTime( $dayRangs[$lastRangIndex] ), 'bay' );

	// запустить синхронизацию аккаунта
	$this->addTimeStart( 'syncAccount', $dayRangs[$lastRangIndex]['endRang'] + 60 );
	// return $this->allSchedule;

	// print_r( $this->uniqTime );
	// die;

	// запуск делать из метода класса апп - в цикле по всем аккаунтам
	// запустить расписание - самая последняя запись
	// $this->addTimeStart( 'schedule', $dayRangs[$lastRangIndex]['endRang'] + 120 );

	// сдесь определяем нужен ли поиск юзеров 
	// если да, то ищем целый час до первого диапазона - примерно 10 запусков ( 200 * 10 = 2000 )
	if( $this->searchUsers ){

		// не ближе 20 мин до первого диапазона
		$startSerchUsers = $dayRangs[0]['startRang'] - 20 * 60;

		for( $i = 0; $i < 10 ; $i++ ){
			$this->addTimeStart( 'searchUsers', $startSerchUsers );
			$startSerchUsers -= mt_rand( 90, 120 );
		}
		unset( $startSerchUsers );
	}
	// return $this->allSchedule;

	// параметры запуска парсера ленты
	$parserCounter = $this->account->accountConfig['parser'];
	if( $parserCounter > 1 && $parserCounter < 4 ){
		$rangStartParser = (int)( 15 / $parserCounter ) * 60;
	} else {
		$rangStartParser = 1;
	}

	// параметры запуска updateUserData
	$updateUserDataCounter = $this->account->accountConfig['updateUserData'];
	if( $updateUserDataCounter > 1 && $updateUserDataCounter < 4 ){
		$rangStartUpdateUserData = (int)( 15 / $updateUserDataCounter ) * 60 + mt_rand( 20, 30 );
	} else {
		$rangStartUpdateUserData = 1 + mt_rand( 20, 30 );
	}



	// расписание запусков файлов по диапазонам времени
	foreach( $dayRangs as $i => &$rang ){

		// массив для проверки уникальности времени в диапазоне
		$scheduleOfRang = array();

		// запускаем парсер ленты - вначале каждого периода
		// max 3 запуска за 15 мин - каждие 5 мин ( 5-10-15 )
		$counter = $parserCounter;
		$oneRangStartParser = $rangStartParser;
		while( $counter ){
			$this->addTimeStart( 'parser', $rang['startRang'] + $oneRangStartParser );

			// запускаем message после парсера и до парсера
			$this->addTimeStart( 'twit', $rang['startRang'] + $oneRangStartParser - 112, 'message' );
			$this->addTimeStart( 'twit', $rang['startRang'] + $oneRangStartParser + 112, 'message' );

			$this->uniqTime[] = $rang['startRang'] + $oneRangStartParser - 112;
			$this->uniqTime[] = $rang['startRang'] + $oneRangStartParser + 112;
			$this->uniqTime[] = $rang['startRang'] + $oneRangStartParser;
			$oneRangStartParser += $oneRangStartParser;
			$counter--;
		}
		unset( $counter, $oneRangStartParser );


		// запускаем обновление данных юзеров
		$counter = $updateUserDataCounter;
		$oneRangStartUpdateUserData = $rangStartUpdateUserData;
		while( $counter ){
			$this->addTimeStart( 'updateUserData', $rang['startRang'] + $oneRangStartUpdateUserData );
			$this->uniqTime[] = $rang['startRang'] + $oneRangStartUpdateUserData;
			$oneRangStartUpdateUserData += $rangStartUpdateUserData;
			$counter--;
		}
		unset( $counter, $oneRangStartUpdateUserData );

		// print_r($this->allSchedule);
		// die;

		// запускаем обновление данных юзеров  1 запуск на диапазон
		// $this->addTimeStart( 'updateUserData', $this->getUniqTime( $rang ) );

		/*  запускаем файлы публикации постов ( twit, retwit, message )  */
		// определяем временный массив для генерации похожих данных
		$scheduleOfRang['public']['twit'] = $this->getPercentValue( $this->account->accountConfig['twit'] );
		$scheduleOfRang['public']['retweet'] = $this->getPercentValue( $this->account->accountConfig['retweet'] );
		$scheduleOfRang['public']['link_retweet'] = $this->getPercentValue( $this->account->accountConfig['link_retweet'] );
		
		// запуск методов - twit, retwit, message
		foreach( $scheduleOfRang['public'] as $action => &$boolean ){
			if( ! $boolean ){
				continue;
			} 

			$this->addTimeStart( 'twit', $this->getUniqTime( $rang ), $action );
		} // end foreach
		unset( $action, $scheduleOfRang['public'], $boolean );


		/* *** */
		// запуск follow, unfollow
		// TODO - переделать параметрально количество запусков фолловинга и анфолловинга в диапазоне
		$scheduleOfRang['users'] = ['follow','unfollow'];
		// return $scheduleOfRang['users'];

		$countStartFlUn = $this->account->accountConfig['countStartFlUn'];
		foreach( $scheduleOfRang['users'] as $action ){
			$countStart = $countStartFlUn;
			while( $countStart > 0 ){
				$this->addTimeStart( $action, $this->getUniqTime( $rang ), $this->getRandConfigValue( $action.'Once') );
				$countStart--;
			}
			
		} // end foreach

		unset( $action, $scheduleOfRang['users'], $param );

		// запуск ответного фолловинга
		$this->addTimeStart( 'backFollow', $this->getUniqTime( $rang ), $this->getRandConfigValue( 'backFollowOnce' ) );

		// запуск личных сообщений новым фолловерам
		$this->addTimeStart( 'sendMassageNewFollowers', $this->getUniqTime( $rang ), $this->getRandConfigValue( 'sendMassageNewFollowersOnce' ) );

		// print_r($this->allSchedule);
		// die;

	}
	// return $this->allSchedule;

	// увеличим нероспределенныые параметры, если они есть
	$this->increment();
	// return $this->allSchedule;


	// удалим запуск параметризированных методов с пустыми параметрами 
	$this->removeEmptyParamAction();
	// return $this->allSchedule;

	// записываем в базу данных
	Logger::write( 'Росписание ' . $this->account->screen_name , __FILE__ , __LINE__ );
	$this->account->db->clearTable('schedule');
	return $this->account->db->addSchedule( $this->allSchedule );

	return true;
}


/**
* Метод формирования запуска файла и добавления в общий массив запусков (можно писать в базу)
*
* @param $action string
* @param $time int
* @param $param 
* @return boolean
*/
private function addTimeStart( $action, $time, $param = '' ){

	$timeStartAction = array(
		'account_id' => $this->account->account_id,
		'action' => $action,
		'time' => $time,
		'param' => $param
	);

	if( $action == 'follow' ){
		if( $this->countFollowers == 0 ){
			$timeStartAction['param'] = 0;
		} else {
			if( $this->countFollowers > $param ){
				$this->countFollowers = $this->countFollowers - $param;
			} else {
				$timeStartAction['param'] = $this->countFollowers;
				$this->countFollowers = 0;
			}
		}
	} 

	if( $action == 'unfollow' ){
		if( $this->countUnfollowers == 0 ){
			$timeStartAction['param'] = 0;
		} else {
			if( $this->countUnfollowers > $param ){
				$this->countUnfollowers = $this->countUnfollowers - $param;
			} else {
				$timeStartAction['param'] = $this->countUnfollowers;
				$this->countUnfollowers = 0;
			}
		}
	} 

	if( $action == 'backFollow' ){
		if( $this->countBackFollowers == 0 ){
			$timeStartAction['param'] = 0;
		} else {
			if( $this->countBackFollowers > $param ){
				$this->countBackFollowers = $this->countBackFollowers - $param;
			} else {
				$timeStartAction['param'] = $this->countBackFollowers;
				$this->countBackFollowers = 0;
			}
		}
	} 

	if( $action == 'sendMassageNewFollowers' ){
		if( $this->countNewFollowers == 0 ){
			$timeStartAction['param'] = 0;
		} else {
			if( $this->countNewFollowers > $param ){
				$this->countNewFollowers = $this->countNewFollowers - $param;
			} else {
				$timeStartAction['param'] = $this->countNewFollowers;
				$this->countNewFollowers = 0;
			}
		}
	} 
	
	// добавляем в общий массив
	// можно сразу писать в базу - что снизит нагрузку?
	$this->allSchedule[] = $timeStartAction;

	return true;
}

/**
* Увеличения +1 самых маленьких параметров
*
* @param $mode string  ( 'follow', 'unfollow' )
*/
function increment( $mode = array( 'follow', 'unfollow' ) ){

	// нижний порог параметра
	$param = 3;

	// мотаем по методам - 'follow', 'unfollow'
	foreach( $mode as &$oneMode ){
		$modeAction = 'count'. ucfirst($oneMode) . 'ers';

		// пока не роспределены все запланированые на сегодня юзеры по параметрам
		while( $this->$modeAction > 0 ){

			// перебираем все запуски файлов с поиском самых меньших параметров и ++
			foreach( $this->allSchedule as $i => &$oneSchedule ){
				
				// на случай пусого массива или не тот метод, получаем только нужные файлы
				if( ! isset( $oneSchedule['action'], $oneSchedule['param'] ) || $oneSchedule['action'] != $oneMode ){
					continue;
				}

				// добавим где меньше нижнего порога
				if( $oneSchedule['param'] < $param ){

					// задаем рандомность для добавления в процентах
					if( $this->getPercentValue( 50 ) ){

						// метка для определения уровня самых малых параметров
						$minParam = true;
						continue;
					}

					// синхронизируем параметры
					$oneSchedule['param']++;
					$this->$modeAction--;
					if( $this->$modeAction < 1 ){
						// print_r($this->allSchedule);
						// die;

						// выходим аж до первого foreach
						break(2);
					}
				}
			} // end foreach
			unset( $oneSchedule, $i );

			if( ! $minParam ){
				$param++;
			}
			$minParam = false;

		} // endwhile
	} // end foreach

	return true;
}

/**
* Удаляем запуски файлов с пустыми параметрами, 
* если ети файлы параметризированные
*
* @return booloea
*/
function removeEmptyParamAction(){
	foreach( $this->allSchedule as $i => &$oneSchedule ){
		
		// удаляем пустые массивы - не запуски методов
		if( ! isset( $oneSchedule['action'] ) ){
			unset($this->allSchedule[$i]);
			continue;
		}

		// если параметр пустой удаляем этот запуск
		if( $oneSchedule['action'] == 'follow' || $oneSchedule['action'] == 'unfollow'  || $oneSchedule['action'] == 'backFollow' 
			|| $oneSchedule['action'] == 'sendMassageNewFollowers' ){
			if( ! isset( $oneSchedule['param'] ) || $oneSchedule['param'] < 1 ){
				unset( $this->allSchedule[$i] );
			}
		}
	}
	unset( $oneSchedule, $i );

	return true;
}


} // end class