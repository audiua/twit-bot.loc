<?php

trait Parser{



function parser(){
	// return 'parser';

	// получаем данные с сервера твиттера + кеш
	$file = $_SERVER['DOCUMENT_ROOT'] .'/protected/runtime/cache/'. $this->screen_name .'_tape.txt';
	if( file_exists( $file ) && ( time() - filemtime( $file ) ) < 10000 ){
		$tape = unserialize( file_get_contents( $file ) );
	} else {
		if( $this->last_post_lenta ){
			$tape = $this->twitter->getTape( $this->last_post_lenta );
		} else {
			$tape = $this->twitter->getTape();
		}
		file_put_contents( $file, serialize( $tape ) );
	}

	$log = $_SERVER['DOCUMENT_ROOT'] .'/protected/runtime/log/'. $this->screen_name .'count_tape.txt';
	$time = date( 'Y|m|d/H:i:s', time() );
	file_put_contents( $log, $time." количество твитов в ленте - ".count( $tape ) . ' last post - '. $tape[0]->id_str ."\n", FILE_APPEND );
	unset( $log, $time );

	// обновить последний пост
	$this->db->addAccount( array( 'account_id' => $this->account_id, 'last_post_lenta' => $tape[0]->id_str ) );
	$this->last_post_lenta = $tape[0]->id_str;
	// echo '<pre>';
	// print_r(  $tape );
	// return 'parser';

	// фильтруем ленту - получаем только те посты где будем искать совпадение фраз
	$filterTape = $this->filterTape( $tape );
	// print_r($filterTape);
	// die;
	if( empty($filterTape) ){
		return;
	}
	unset($tape);

	// добавляем ретвиты
	$this->db->addPublic( 'retweets', 'retweet', $filterTape['retweet'] );
	// print_r($filterTape['retweet']);
	unset( $filterTape['retweet'] );
	// die;

	// поиск совпадений
	$searchFraze = $this->searchFraze( $filterTape['message'] );
	// print_r($searchFraze);
	// die;
	if( !empty( $searchFraze ) ){
		$time=time();
		foreach( $searchFraze as $onePost ){
			$assocPost[] = "`account_id`=$this->account_id, `message`='$onePost', `create_time`=$time";
		}

		$this->db->addData( 'message', $assocPost );

	}




	// print_r( $retwit );
	// Logger::write( "Парсер $this->screen_name " , __FILE__ , __LINE__ );

}

function filterTape( &$tape ){

	$searchFraze = array();
	foreach( $tape as $i => &$post ){

		// отсеиваем свои твиты
		if( $post->user->screen_name == $this->screen_name ){
			unset( $tape[$i] );
			continue;
		}

		// print_r($post) ;
		// echo '<hr>';
		// die;
		// continue;

		// отсеиваем посты с ссылками
		if( stripos( $post->text, 'http' ) !== false ){
			unset( $tape[$i] );
			continue;
		}

		// ретвиты - проще ретвитиь готовые ретвиты
		if( stripos( $post->text, 'RT @' ) !== false ){

			// проверяем по количеству @ - чобы не ретвитить чужих логинов
			if( substr_count( $post->text, '@' ) > 1 ){
				unset( $tape[$i] );
				continue;
			}

			$searchFraze['retweet'][] = $post->id_str;
			unset( $tape[$i] );
			continue;
		}

		// личные сообщения
		if( stripos( $post->text, '@' ) !== false ){

			// проверяем на упоминание нас
			if( stripos( $post->text, '@'.$this->screen_name ) == false ){
				unset( $tape[$i] );
				continue;
			}

		}

		// echo $post->text . "=".$post->user->screen_name."<br>";



		// поиск совпадения фраз - эти посты без ссылок и логинов
		$searchFraze['message'][] = array( 'text'=>$post->text, 'screen_name'=>$post->user->screen_name );
	}

	return $searchFraze;

}


/**
* Поиск совпадений фраз в твиттерах и формирует их по категория=фраза
*
* @param $allText array  текст сообщения
*/
function searchFraze( array $allText ){
	// print_r($allText);
	// die;

	// проверяем на совпадения по фразам
	//TODO - переделать на поиск по категориям фраз для оптимизации
	
	$allFraze = $this->db->get( 'msg', array( '*' ) );
	$allAnswerFraze = array();
	// print_r( $allFraze );
	// die;

	foreach( $allFraze as &$fraze ){
		if( empty( $fraze->category ) ){
			continue;
		}
		// print_r($fraze);
		// continue; 

		foreach( $allText as $i => &$text ){
			// print_r($text);
			// continue;
			// die;
			$one = mb_strtolower( $text['text'], 'UTF-8' );
			$two = mb_strtolower( $fraze->text, 'UTF-8' );
			if( mb_strpos( $one, $two ) === false ){
				// echo $text['text'];
				// echo "=";
				// echo $fraze->text."<br>";
				continue;

			} else {

				// print_r($text);
				// die;

				// есть совпадение 
				// проверяем или не писали сегодня етим юзерам по данным категорияч
				$speak = $this->db->issetSpeakToday( $text['screen_name'], $fraze->category );
				if( $speak ){
					continue;
				}

				// print_r($fraze->category);
				// print_r($allAnswerFraze);
				// echo '<hr>';
				// print_r($fraze);
				// continue;

				// die;

				// получаем фразу на ответ
				if( ! isset( $allAnswerFraze[$fraze->category] ) || empty( $allAnswerFraze[$fraze->category] ) ){
					foreach( $allFraze as $answerFraze ){
						if( $answerFraze->answer_category == $fraze->category ){
							$allAnswerFraze[$fraze->category][] = $answerFraze->text;
							// print_r($allAnswerFraze);
							// die;
						}
					}
				}

				// print_r($allAnswerFraze);
				// die;

				// группируем похожие фразы - привет, пока, спокойной ночи
				if( $fraze->category == 'hello' || $fraze->category == 'bay' ){
					$group[$fraze->category][] = $text['screen_name'];
				} else {


					$randAnswerFraze = $allAnswerFraze[$fraze->category][ array_rand( $allAnswerFraze[$fraze->category] ) ];
					$answerText = '@'. $text['screen_name'].' '.$randAnswerFraze;
					$result[]= $answerText ;
					$speakToday[] = "`screen_name`='{$text['screen_name']}', `category`='$fraze->category', `account_id`=$this->account_id ";
					unset( $randAnswerFraze, $answerText );
				}
			}

		}
		unset( $text );
	}
	unset( $fraze );

	// print_r($allAnswerFraze);

	// print_r($result);
	// die;

	// формируем посты с группированными 
	
	if( isset( $group ) && !empty( $group )  ){

		foreach( $group as $cat => &$value ){
			$condition = $this->db->getCondition(
				array( 
					array( 
						'field' => 'category',
						'mode' => '=',
						'value' => "'".$cat."'",
						'moreCondition' => ''
					) 
				) 
			);
			$allImg = $this->db->get( 'img', array('link'), $condition );
			$answerText = array();
			$answer = '';
			$newPost = true;
			$separatorArray = array( "\n", ' ', ",\n", ' | ', ' + ');
			foreach( $value as $i => &$screenName ){

				if( strlen( $answer ) > 120 ){

					if( mt_rand(0,99) < 35 ){
						$img = $allImg[ mt_rand( 0, count( $allImg )-1 ) ];
						$answer .= "\n " . $img->link;
					}

					$result[]=$answer;
					// $answerText[] = $answer;
					$answer = '';
					$newPost = true;
					continue;
				} else {

					$randNumber = mt_rand(0,120);
					if( $randNumber < 30 ){
						$forSeparator = "\n";
					} elseif( $randNumber > 30 && $randNumber < 60 ){
						$forSeparator = " для ";
					} elseif( $randNumber > 60 && $randNumber < 90 ) {
						$forSeparator = " ";
					} else {
						$forSeparator = " для \n";
					}

					// добавляем рандомный текст в начало привета
					if( $newPost ){
						$separator = $separatorArray[array_rand( $separatorArray )];
						$answer = $allAnswerFraze[$cat][ array_rand( $allAnswerFraze[$cat] ) ].$forSeparator;
						$newPost = false;
						$firstSeparator = true;
					}

					$sep = ( $firstSeparator == true ) ? '' : $separator;
					$answer .= $sep . '@'. $screenName;
					$firstSeparator = false;

					$speakToday[] = "`screen_name`='$screenName', `category`='$cat', `account_id`=$this->account_id ";
				}
			}
			unset($screenName);
			if( !empty($answer) ){

				if( mt_rand(0,99) < 35 ){
					$img = $allImg[ mt_rand( 0, count( $allImg )-1 ) ];
					$answer .= "\n " . $img->link;
				}

				$result[] = $answer;
			}
		}
		unset($value);
	}

	

	// echo '<br>';
	// print_r($result);
	// die;

	// запишем с кем мы сегодня общаемся и на какую тему
	if( isset( $speakToday ) && !empty( $speakToday ) ){
		$this->db->addData( 'speak_today', $speakToday );
	}

	if( !isset( $result ) || empty( $result ) ){
		return array();
	}
	return $result;
}


}