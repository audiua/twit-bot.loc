<?php
echo '<pre>';
$account = new Account( 702736716 );
$I = $account->twitter->showUser( 84056605 );
print_r($I);
echo '<hr>';
// $limit = $account->twitter->twitter->get('application/rate_limit_status');
$i = $account->checkLimit( 'showUser' );
print_r( $i );
echo '<hr>';
// print_r( $account->twitter->twitter->http_header );
die;


echo $account->twitter->twitter->http_header['x_rate_limit_remaining'];
echo $account->twitter->twitter->http_header['x_rate_limit_reset'];
print_r( $account->twitter->twitter->http_header );
die;




$account->addStatistics();
die;
$result = $search->searchUsers();
print_r( $result );