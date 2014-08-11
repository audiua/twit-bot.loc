<?php

if( isset($_GET['account_id']) ){
	$account_id = (int)$_GET['account_id'];

	$account = new Account( $account_id );
	// print_r($account);
	// die;
	$condition = $account->db->getCondition(
		array( 
			array( 
				'field' => 'account_id',
				'mode' => '=',
				'value' => $account->account_id,
				'moreCondition' => ''
			)
		)
	);
	$success = true;
	$success &= $account->db->deleteData( 'accounts', $condition );
	// print_r($success);
	// die;

	if( $success ){
		header("Location: http://final.loc/page/showUsers.php?success=1");
	} else {
		header("Location: http://final.loc/page/showUsers.php?error=1");
	}

}