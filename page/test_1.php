<?php


echo '<pre>';

$d1=new DateTime("2014-07-08 10:27:57");
echo $d1->format('Ymd');


// print_r( date('Y/m/d H:i:s', 1403763279) );
die;


$account = new Account( 702736716 );
print_r($account->parser());
die;

// $str = file_get_contents('akpat123_tape.txt');
echo '<pre>';

// $str = array( 'привет всем' );
// echo serialize($str);


// die;


// print_r(unserialize($str));
// die;
// $account->twit('link_retweet');
// die;

// print_r( $account->searchLinkTwit() );
// die;

print_r($account->parser());
die;

$account->addUnfollowing();
die;

$account->syncAccount();
die;

$search = new SearchUsers($account);

print_r( count($search->searchUsers()) );
die;

echo serialize(array());
die;

$tomorow = strtotime( "00:00:01" );
echo date('Y/m/d H:i:s', $tomorow );
echo '<br>';
echo date('Y/m/d H:i:s' );

die;

// echo date('Y/m/d H:i:s', 1401949887);
// die;


$account = new Account( 1298892565 );
$retweets = $account->twit('retweet');



// $retweets = $account->twitter->twitter->get('statuses/user_timeline', array('screen_name' => $account->screen_name, 'include_entities' => 'true', 'include_rts' => 'true', 'count' => '10'));
// $aaa = $account->twit( 'retweet' );

// echo '<pre>';
// print_r($retweets);
// print_r($account->unfollow( 1 ));
?>





