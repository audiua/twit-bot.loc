<?php



class Schedule extends Base{

	private $_user;
	public $followToday = true;
	public $allSchedule = array();
	public $followersOfDay = 0;
	public $unfollowers = 0;

	function __construct( Account $user ){
		parent::__construct();
		$this->_user = $user;
		$this->unfollowers = $this->_user->friends_count - $this->_user->followers_count;

		// отключаем фолловинг если много анфолловинга
		if( $this->unfollowers > 100 ){
			$this->writeLog(
				'Количество анфолловинга больше за фолловинг на 100 - ' 
				. $this->_user->screen_name
			);
			$this->followToday = false;
		} else {
			$this->followersOfDay = (int)( 
				$this->_user->followers_count * 
				$this->_user->accountConfig['followOfDay'] / 100 );
		}
	}

	private function addTimeStart( $action, $time, $param = 0 ){

		$timeStartAction = array(
			'account_id' => (int)$this->_user->account_id,
			'action' => $action,
			'time' => (int)$time
		);

		if( $action == 'addFollowers' && $this->followToday && $this->followersOfDay > 0 ){
			if( $this->followersOfDay > (int)$param ){
				$this->followersOfDay = $this->followersOfDay - (int)$param;
			} else {
				$param = $this->followersOfDay;
				$this->followersOfDay = 0;
			}

			$timeStartAction = array_merge( $timeStartAction, array('param' => $param) );
		} 

		if( $action == 'unfollowing' && $this->unfollowers > 0 ){
			if( $this->unfollowers > (int)$param ){
				$this->unfollowers = $this->unfollowers - (int)$param;
			} else {
				$param = $this->unfollowers;
				$this->unfollowers = 0;
			}

			$timeStartAction = array_merge( $timeStartAction, array('param' => $param) );
		} 

		// print_r( $timeStartAction );
		// die;

		$this->allSchedule[] = $timeStartAction;

		return true;
	}

	// function addAllUsersSchedule(){
	// 	// получаем все юзеров
	// 	$allUsers = User::getAllUsers();

	// 	foreach( $allUsers as $oneUser ){
	// 		$user = new User( $oneUser['id'] );
	// 		$success = $this->addUserSchedule();
	// 		if( !$success ){
	// 			$user->writeLog( 
	// 				'Для юзера ' 
	// 				. $user->login 
	// 				. ' не удалось записать росписание запусков файлов '
	// 				. date('Y/m/d H:i:s', time())
	// 			);
	// 		}
	// 	}
	// }


	function addUserSchedule(){

		$schedule = $this->_user->accountConfig;

		// print_r($schedule);
		// echo '<pre>';
		// die;


		// определяем время работы
		$tomorow = strtotime("+1 day 00:00:01");
		$schedule['startTime'] += $tomorow;
		$schedule['endTime'] += $tomorow;

		unset($tomorow);

		// print_r($schedule);
		// die;

		// разбить по периодам весь день
		$dayRang = array();
		$startRang = $schedule['startTime'];
		while( $startRang < $schedule['endTime'] ){
			$rang = array(
				'start' => $startRang,
				'end' => $startRang + mt_rand( 16*60, 20*60 ),
			);

			$dayRang[] = $rang;

			// промежуток между диапазонами до 5 мин
			$startRang = $rang['end'] + mt_rand( 1, 300 );
		}

		unset( $startRang );
		unset( $rang );

		// print_r($dayRang);

		// foreach($dayRang as $rang){
		// 	echo date('Y/m/d H:i:s', $rang['start']).'<br>';
		// 	echo date('Y/m/d H:i:s', $rang['end']).'<br><hr>';
		// }

		// die;

		// на каждый диапазон расписать количество запусков файлов согласно настройкам
		$lastRang = count( $dayRang ) - 1;
		foreach( $dayRang as $index => $oneRange ){
			$scheduleOfRang = array();
			$scheduleOfRang['time'] = array();

			// запуск утренего приветсвия в первый диапазон времени
			if( $index == 0 ){
				$timeStart = mt_rand( $oneRange['start'], $oneRange['end'] );
				$this->addTimeStart( 'helloMessage', $timeStart );
				$scheduleOfRang['time'][] = $timeStart;
			}

			// print_r( $this->allSchedule );
			// die;

			// запуск вечернего прощания в последний диапазон времени
			if( $index == $lastRang ){
				$timeStart = mt_rand( $oneRange['start'], $oneRange['end'] );
				$this->addTimeStart( 'bayMessage', $timeStart );
				$scheduleOfRang['time'][] = $timeStart;
			}

			// запускаем парсер ленты - вначале каждого периода
			// max 3 запуска за 15 мин - каждие 5 мин
			$rangStartParser = '';
			$counter = $schedule['parser']['count'];
			while( $counter ){
				$this->addTimeStart( 'parser', $oneRange['start'] + $rangStartParser );
				$scheduleOfRang['time'][] = $oneRange['start'] + $rangStartParser;
				$rangStartParser += $schedule['parser']['step'];
				$counter--;
			}

			// print_r( $this->allSchedule );
			// die;

			// print_r($schedule['parser']);

			unset($rangStartParser);

			$scheduleOfRang['schedule']['addMessage'] = array_rand( $schedule['message'] );
			if( $scheduleOfRang['schedule']['addMessage'] > 1 ){
				$scheduleOfRang['schedule']['addMessage'] = 0;
			}

			// 30%
			$scheduleOfRang['schedule']['addTwit'] = array_rand( $schedule['twit'] );
			if( $scheduleOfRang['schedule']['addTwit'] > 1 ){
				$scheduleOfRang['schedule']['addTwit'] = 0;
			}

			// делаем 25%
			$scheduleOfRang['schedule']['addRetwit'] = array_rand( $schedule['retwit'] );
			if( $scheduleOfRang['schedule']['addRetwit'] > 1 ){
				$scheduleOfRang['schedule']['addRetwit'] = 0;
			}

			// запуск методов - twit, retwit, message
			foreach( $scheduleOfRang['schedule'] as $action => $countStartAction ){
				if( $countStartAction == 0 ){
					continue;
				} 

				while( $countStartAction ){

					// для одного юзера не повторять время запуска
					$timeStart = mt_rand( $oneRange['start'], $oneRange['end'] );

					// print_r($scheduleOfRang['time']);
					// die;

					while( in_array( $timeStart, $scheduleOfRang['time'] ) ){
						$timeStart = mt_rand( $oneRange['start'], $oneRange['end'] );
					}

					// print_r($scheduleOfRang['time']);
					// die;

					$this->addTimeStart( $action, $timeStart );
					$scheduleOfRang['time'][] = $timeStart;
					$countStartAction--;
				} // end while
			} // end foreach

			unset($action,$countStartAction);

			// print_r( $this->allSchedule );
			// die;


			// запуск follow, unfollow
			$scheduleOfRang['schedule']['follow']['addFollowers']['count'] = 1;
			$scheduleOfRang['schedule']['follow']['addFollowers']['param'] = mt_rand( 
				$schedule['followOnce']['min'], 
				$schedule['followOnce']['max'] 
			);

			$scheduleOfRang['schedule']['follow']['unfollowing']['count'] = 1;
			$scheduleOfRang['schedule']['follow']['unfollowing']['param'] = mt_rand( 
				$schedule['followOnce']['min'], 
				$schedule['followOnce']['max'] 
			);

			// print_r( $scheduleOfRang );
			// die;

			foreach( $scheduleOfRang['schedule']['follow'] as $action => $param){
				// if( $action['param'] == 0 ){
				// 	continue;
				// }

				// print_r($param);
				// die;

				$timeStart = mt_rand( $oneRange['start'], $oneRange['end'] );
				while( in_array( $timeStart, $scheduleOfRang['time'] ) ){
					$timeStart = mt_rand( $oneRange['start'], $oneRange['end'] );
				}

				$this->addTimeStart( $action, $timeStart, $param['param'] );
				$scheduleOfRang['time'][] = $timeStart;
			} // end foreach

			unset($action,$countStartAction);


			// print_r( $this->allSchedule );
			// die;

			// echo '<hr>';

			// $this->allSchedule[] = array();

		} // end foreach



		// print_r( $scheduleOfRang );
		// print_r( $this->allSchedule );
		// echo $this->followersOfDay;
		// die;

		// если расписаны не все фолловеры, то добавим по 1 к каждому запуску
		if( $this->followersOfDay > 0 ){
			$this->incrementFollowParam();
		}

		// print_r();

		// die;

		// удаляем запуски фоллов с пустыми параметрами
		$this->removeEmptyFollowAction();

		// print_r( $this->allSchedule );
		foreach( $this->allSchedule as $oneS ){
			@$action[$oneS['action']] += 1;
		}

		print_r($action);
		// die;

		foreach( $this->allSchedule as $oneS ){



			echo $oneS['account_id'].'<br>';
			echo $oneS['action'].'<br>';
			if( isset($oneS['param']) ){
				echo $oneS['param'].'<br>';
			}
			echo date('Y/m/d H:i:s', $oneS['time']) . '<br><hr>';
		}
		// die;

		// записываем в базу
		$this->writeUserSchedule( $this->allSchedule );

		return true;

	} 

	function incrementFollowParam(){
		$paramFollow = 1;
		while( $this->followersOfDay > 0 ){

			foreach( $this->allSchedule as $ind => &$oneSchedule ){
				if( !isset( $oneSchedule['action'] ) ){
					continue;
				}

				if( $oneSchedule['action'] != 'addFollowers' ){
					continue;
				}

				// добавим где меньше всего
				if( (int)$oneSchedule['param'] < $paramFollow ){

					// задаем рандомность
					$plusOne = mt_rand( 0, 3 );
					if( $plusOne == 0 ){
						continue;
					}

					(int)$oneSchedule['param']++;

					$this->followersOfDay--;
					if( $this->followersOfDay < 1 ){
						// print_r($this->allSchedule);
						// die;
						return true;
					}
				}
			}
	
			$paramFollow++;
		}

		return true;
	}

	function removeEmptyFollowAction(){
		foreach( $this->allSchedule as $ix => &$oneSchedule ){
			if( ! isset( $oneSchedule['action'] ) ){
				unset($this->allSchedule[$ix]);
				continue;
			}

			if( $oneSchedule['action'] == 'addFollowers' || $oneSchedule['action'] == 'unfollowing' ){

				if( !isset( $oneSchedule['param'] ) || $oneSchedule['param'] < 1 ){
					unset($this->allSchedule[$ix]);
				}
			}
		}
	}


	function writeUserSchedule( $allSchedule ){

		// print_r($allSchedule);
		// die;

		// TODO эту операцию делать только в методе зачистки - ввечером после всей работы!!
		$sql = "TRUNCATE TABLE `schedule` ";
		$this->_user->db->connect->exec($sql);
		// die;

		// print_r($db);
		// die;
		// $success = true;
		$this->_user->db->connect->beginTransaction();

		foreach( $allSchedule as $schedule ){
			// print_r($schedule);
			// die;

			if( $schedule['action'] == 'addFollowers' || $schedule['action'] == 'unfollowing' ){
				$paramStr = "params='".$schedule['param']."',";
			} else {
				$paramStr = '';
			}

			$sql = "INSERT INTO schedule SET
				account_id='".$schedule['account_id']."',
				action='".$schedule['action']."',
				".$paramStr."
				time='".$schedule['time']."'";

			// echo $sql . '<br>';
			// die;
			
			$success = $this->_user->db->connect->exec( $sql );

			// print_r($success);
			// die;
		}

		// if( $success ){
		$this->_user->db->connect->commit();
		// } else {
		// 	$db->connect->rollback();
		// 	$this->writeLog('Ошибка записи в базу');
		// }
	}
}

echo '<pre> ';
$user = new Account( 702736716 );

$userSchedule = new Schedule($user);
$result = $userSchedule->addUserSchedule();

print_r( $result );
die;
