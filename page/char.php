<?php

$charGroup = array( 
	'a'=>array(
		'count'=>70, 
		'email'=>'v-semin@rambler.ru', 
		'pass'=>'asdfghjkl123456' ), 
	'b'=>array(
		'count'=>70, 
		'email'=>'v-semin@rambler.ru', 
		'pass'=>'asdfghjkl123456' ),
	'c'=>array(
		'count'=>70, 
		'email'=>'v-semin@rambler.ru', 
		'pass'=>'asdfghjkl123456' )
	);

$sites = array();

foreach( $charGroup as $char => $group ){

	for( $i=1; $i<=$group['count']; $i++ ){

		$sites[]=array(
			'url' => "http://zvezda-$char-$i.ucoz.ru",
			'email' => $group['email'],
			'parol' => $group['pass'],
		);

	}

}

echo '<pre>';
print_r($sites);