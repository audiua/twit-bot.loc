<?php

/**
* Трейт по ссылкам
*
*/
trait Links{

/**
* Поиск и добавление в базу твитов с ссылками на наши сайты.
*/
function searchLinkTwit(){

	// получаем список твитов с ссылками
	foreach( $this->accountConfig['accountOfSite'] as $one ){

		$linkTwitList = $this->twitter->twitter->get( 'statuses/user_timeline', array('screen_name'=>$one) );
		foreach( $linkTwitList as &$linkTwit ){
			$links[] = $linkTwit->id;
		}
		unset( $linkTwitList, $linkTwit );
	}

	// записываем в базу
	if( !empty( $links ) ){
		$this->db->addLinkTwit( $links );
	}

	print_r($links);
}

/**
* Ретвитим посты с ссылками
*/
function linkRetweet(){

	// получаем id твита которого ретвитим из link_twit
	// которого мы еще не ретвитнули
	$twit = $this->db->getFreeLinkTwit();
	// print_r( $twit );
	// die;

	if( !empty( $twit ) ){

		// ретвитим этот твит
		$retwit = $this->twitter->twitter->post( 'statuses/retweet/' . $twit[0]->link_twit_id );

	} else {
		unset( $twit );

		// echo 'aaa';
		// die;

		// если свободных твитов нет - удаляем самый старый 
		// и ретвитим его
		$twit = $this->db->getOldLinkRetweet();
		// echo $twit[0]->link_retweet_id;
		// die;
		// return $twit;
		if( empty( $twit ) ){
			Logger::write( 'Ошибка с ретвитами-' . $this->account_id , basename(__FILE__), __LINE__);
			return;
		}

		// удаляем ретвит в твитере
		$this->twitter->twitter->post( 'statuses/destroy/' . $twit[0]->link_retweet_id );
		
		// удаляем с таблицы
		$condition[] = $this->db->getCondition(
			array( 
				array( 
					'field' => 'account_id',
					'mode' => '=',
					'value' => $this->account_id,
					'moreCondition' => ' AND '
				),
				array( 
					'field' => 'link_retweet_id',
					'mode' => '=',
					'value' => $twit[0]->link_retweet_id,
					'moreCondition' => ''
				)
			)
		);
		// return $condition;
		$this->db->deleteData( 'link_retweet' , $condition );
		// return;
		// print_r($this->twitter->twitter);
		// die;

		// ретвитим этот твит
		$retwit = $this->twitter->twitter->post( 'statuses/retweet/' . $twit[0]->link_twit_id );
	}

	// если ретвит не добавился - возможно сбой - удаляем с твиттера и с базы 
	if( !isset( $retwit->id ) ){
		$this->twitter->twitter->post( 'statuses/destroy/' . $twit[0]->link_retweet_id );

		$condition[] = $this->db->getCondition(
			array( 
				array( 
					'field' => 'account_id',
					'mode' => '=',
					'value' => $this->account_id,
					'moreCondition' => ' AND '
				),
				array( 
					'field' => 'link_retweet_id',
					'mode' => '=',
					'value' => $twit[0]->link_retweet_id,
					'moreCondition' => ''
				)
			)
		);
		$this->db->deleteData( 'link_retweet' , $condition );
	} else {

		// записываем id ретвита в базу ретвитов с ссылками
		$this->db->addLinkRetweet( $retwit->id, $twit[0]->link_twit_id );
	}

}

}