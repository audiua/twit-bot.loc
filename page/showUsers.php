<?php

include( $_SERVER['DOCUMENT_ROOT'] . '/protected/class/DataBaseClass.php' );

if( isset( $_GET['error'] ) ){
	echo '<h3>Не удалось удалить аккаунт</h3><br>';
} else if( isset($_GET['success']) ){
	echo '<h3>Аккаунт успешно удален</h3><br>';
}
?>

<html>
<head>
	<link href="http://twit-bot.loc/css/style.css" rel="stylesheet">
</head>
<body>

<?php
	$db = new DataBase();
	$allAccounts = $db->get('accounts', array('*'));

	foreach( $allAccounts as $account ){
		echo '<div class="account">';
		echo '<div class="avatar"><img src="../img/avatar_of_account/'.$account->screen_name.'.png"/></div>';
		echo '<div class="account-data">';
		echo 'account_id = '. $account->account_id . '<br>';
		echo 'screen_name = '. $account->screen_name . '<br>';
		echo 'password = '. $account->password . '<br>';
		echo '<a href="https://twitter.com/'.$account->screen_name.'" target="_blank">In twitter</a><br>';
		echo '<a href="http://twit-bot.loc/page/deleteAccount.php?account_id='.$account->account_id.'">delete Account</a><hr>';
		echo '</div>';
		echo '</div>';
	}
?>

</body>
		
