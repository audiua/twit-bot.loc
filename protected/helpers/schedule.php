<?php
require_once( $_SERVER['DOCUMENT_ROOT'].'/protected/class/UserClass.php' );

// получаем все юзеров
$allUsers = User::getAllUsers();

foreach( $allUsers as $oneUser ){
	$user = new User( $oneUser['id'] );
	$success = $user->addUserSchedule();
	if( !$success ){
		$user->writeLog( 
			'Для юзера ' 
			. $user->login 
			. ' не удалось записать росписание запусков файлов '
			. date('Y/m/d H:i:s', time())
		);
	}
}
