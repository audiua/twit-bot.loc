<?php

/**
* Добавление нового аккаунта
*/
// print_r($_POST);
// die;

if( isset( $_POST['ajax'] ) ){



	//  аякс валидация
	$form = $_POST['formValidation'];

	// очистка от пробелов
	$form = DataBase::clearData( $form );

	$answer = array();
	foreach( $form as $key => $val ){
		if( empty( $form[$key] ) ){
			$answer['error'][$key] = 'Not empty!';
		}
	}
	// print_r($form);
	// die;

	// форма не пуста
	if( empty( $answer ) ){

		// проверяем есть ли такой Аккаунт в базе
		$issetAccount = DataBase::issetAccount( $form['screen_name'] );
		// var_dump($issetAccount);
		// die;

		if( $issetAccount ){
			$answer['success'] = "Аккаунт {$form['screen_name']} существует";
			echo json_encode( $answer );
			exit;
		}
		// print_r(' ! isset Account ');
		// die;

		// проверяем соединение с твиттером + кэш в файл на 1000 сек
		$file = $_SERVER['DOCUMENT_ROOT'] .'/protected/runtime/cache/'. $form['screen_name'].'TwitterObject.txt';
		if( file_exists( $file ) && ( time() - filemtime( $file ) ) < 1000 ){
			$account = unserialize( file_get_contents( $file ) );
		} else {
			$account = TwitterAPI::showAccount( $form );
			file_put_contents( $file, serialize( $account ) );
		}

		// print_r($account);
		// die;

		if( empty( $account ) ){
			$answer['success'] = 'Нет соединения с твиттером';
			echo json_encode( $answer );
			exit;
		}

		// формируем данные для записи
		$form['account_id'] = $account->id;
		$form['name'] = $account->name;
		$form['location'] = $account->location;
		$form['description'] = $account->description;
		$form['followers_count'] = $account->followers_count;
		$form['friends_count'] = $account->friends_count;
		$form['statuses_count'] = $account->statuses_count;

		// print_r($form);
		// die;

		// записываем нового аккаунта
		$db = new DataBase();
		// print_r($db);
		// die;

		$success = $db->addAccount( $form );
		// var_dump($success);
		// die;

		if( $success ){

			$acc = new Account( $account->id );
			// print_r($acc);
			// die;

			// запускаем синхронизацию
			$acc->syncAccount();

			$answer['success'] = 'Новый твиттер аккаутн успешно добавлен!';
		} else {
			throw new Exception( 'Ошибка записи в базу данных' );
		}
	}

	sleep(1);

	// ответ на аякс запрос
	echo json_encode( $answer );
	exit;
}



?>


<html>

	<head>
		<meta charset="utf-8" />
		<link rel="stylesheet" type="text/css" href="../css/style.css">
		<script type="text/javascript" src="../js/jquery-1.11.0.js"></script>


	</head>

	<body>
	<h1>
		Главная страница
	</h1>
	<hr>

	<p>Добавить твиттер аккаунт</p>
	<div id = "formAddUser">
		<form enctype="multipart/form-data" method="post" name="addUser" >
			<input type="text" name="screen_name" placeholder = 'screen_name' /><span id="screen_name" ></span><br />
			<input type="text" name="password" placeholder = 'Password' /><span id='password'></span><br />
			<input type="text" name="costumer_key" placeholder = 'Costumer key' /><span id='costumer_key'></span><br />
			<input type="text" name="costumer_secret" placeholder = 'Costumer secret' /><span id='costumer_secret'></span><br />
			<input type="text" name="access_token" placeholder = 'Access token' /><span id='access_token'></span><br />
			<input type="text" name="access_token_secret" placeholder = 'Access token secret' /><span id='access_token_secret'></span><br />

			<button name='submit'> Добавить </button>
		</form>
		<div id='msg'></div>
	</div>

	<script type="text/javascript" src="../js/validationFormAddNewUser.js"></script>
	</body>

</html>







