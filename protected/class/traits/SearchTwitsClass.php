<?php

/**
* Трейт для поиска твитов - с рсс лент
*
*/
trait SearchTwits{

/**
* Парсер рсс лент из настроек аккаунта
*
*/
function searchTwits(){

	// получаем случайную
	foreach( $this->accountConfig['rss'] as $rssPath ){
		$rss = simplexml_load_file( $rssPath );
		// return $rss;

		if( empty( $rss ) ){
			continue;
		}

		foreach( $rss->channel->item as $i => $item ){
			$text = str_replace('&quot;', '"', (string)$item->title);
			$sinText = $this->sinonym( $text );
			// return $sinText;
			if( strlen( $text ) > 210 ){
				$text = $this->shortTwit( $text );
			}

			if( !empty($text) ){
				$newTwits[] = $text;
			}
		}
		// return $newTwits;
	}

	// запись в базу данных
	$this->db->addPublic( 'twits', 'twit', $newTwits, false );
}

/**
* Укоротитель твиттеров
*
* @param $twit string
* @return string  long < 210 ch
*/
function shortTwit( $twit ){
	preg_match('/^.{210}[^\s]*/is', $twit, $stat);
	$status  = $stat[0].'…';

	return $status;
}

/**
* Синонимайзер твиттов
*
* @param $twit string
* @return string  синонимизированный твит
*/
function sinonym( $twit ){
	
	// разбиваем на слова
	$words = preg_split("/[\s,]+/", $twit );
	// echo $twit;
	foreach( $words as $word ){
		$sinonyms = array();
		$qWord = $this->db->connect->quote($word);
		$condition = $this->db->getCondition(
			array( 
				array( 
					'field' => 'word',
					'mode' => '=',
					'value' => strtolower($qWord),
					'moreCondition' => ''
				) 
			) 
		);
		$query = $this->db->get( 'sinonyms', array('*'), $condition );
		// echo $query;
		// die;
		if( !empty($query) ){
			$sin =  str_replace( $word, $query[0]->sinonym, $twit );
		} else {
			$sin = $twit;
		}
	}

	return $sin;
}

}