<?php


// $avatar = file_get_contents('http://pbs.twimg.com/profile_images/480716889111269377/w40MMBTP_normal.jpeg');
// file_put_contents($_SERVER['DOCUMENT_ROOT'].'/img/avatar_of_account/akpat123.jpeg', $avatar);
// die;
// echo date('Y/m/d H:i:s',1403429210);
// die;

// $cteate = strtotime('Wed Jun 18 21:17:16 +0000 2014');

// echo $cteate;
// die;
echo '<pre />';

$account = new Account( 702736716 );


print_r($account->twitter->getUsersList( 125140402 ));
die;



// 	print_r($account->twitter->showUser(125140402));
// // $account->parser();
die;
// print_r($account);
// die;

$schedule = new Schedule( $account );

print_r( $schedule->schedule() );
die;
print_r($account->addUnfollowing());
die;
// $schedule = new Schedule($account);
// print_r($schedule) ;
// die;
// print_r($account->unfollow(1));
// die;

// print_r($account->follow(1));
// die;

$options = array( 'user_id'=>277416059 );
$options['follow'] = true;

$aaa = $account->twitter->twitter->post( 'friendships/create', $options );
$limit = $account->twitter->twitter->get('application/rate_limit_status');
// $aaa = $account->twitter->twitter->post( 'statuses/retweet/473823196886859776');
echo '<pre />';
// print_r($limit->resources->application);
foreach($limit->resources->application as $one){
	print_r($one->remaining);
}
echo '<hr>';
// print_r($aaa);


//277416059

//473823196886859776

//473865380373737472

