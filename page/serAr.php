<?php

$str = 'O:12:"TwitterOAuth":14:{s:9:"http_code";i:403;s:3:"url";s:51:"https://api.twitter.com/1.1/friendships/create.json";s:4:"host";s:28:"https://api.twitter.com/1.1/";s:7:"timeout";i:30;s:14:"connecttimeout";i:30;s:14:"ssl_verifypeer";b:0;s:6:"format";s:4:"json";s:11:"decode_json";b:1;s:9:"http_info";a:26:{s:3:"url";s:51:"https://api.twitter.com/1.1/friendships/create.json";s:12:"content_type";s:30:"application/json;charset=utf-8";s:9:"http_code";i:403;s:11:"header_size";i:700;s:12:"request_size";i:484;s:8:"filetime";i:-1;s:17:"ssl_verify_result";i:0;s:14:"redirect_count";i:0;s:10:"total_time";d:0.71652300000000002;s:15:"namelookup_time";d:0.060637000000000003;s:12:"connect_time";d:0.19811899999999999;s:16:"pretransfer_time";d:0.47651199999999999;s:11:"size_upload";d:293;s:13:"size_download";d:76;s:14:"speed_download";d:106;s:12:"speed_upload";d:408;s:23:"download_content_length";d:76;s:21:"upload_content_length";d:293;s:18:"starttransfer_time";d:0.71650800000000003;s:13:"redirect_time";d:0;s:12:"redirect_url";s:0:"";s:10:"primary_ip";s:12:"199.16.156.8";s:8:"certinfo";a:0:{}s:12:"primary_port";i:443;s:8:"local_ip";s:12:"192.168.1.34";s:10:"local_port";i:41961;}s:9:"useragent";s:25:"TwitterOAuth v0.2.0-beta2";s:11:"sha1_method";O:30:"OAuthSignatureMethod_HMAC_SHA1":0:{}s:8:"consumer";O:13:"OAuthConsumer":3:{s:3:"key";s:22:"gSD6pWs1zZnJPHRHFvYVTg";s:6:"secret";s:41:"t4Y64ig7pPcRUQKP0jMUEpJWeVF5K6XzDwg6GeHZQ";s:12:"callback_url";N;}s:5:"token";O:13:"OAuthConsumer":3:{s:3:"key";s:50:"702736716-f4ifmBjUQLU1IyUOPzDGUdPCsah89LVpZrOix50G";s:6:"secret";s:42:"Nfr8XZtFJcKKCYxZfSzEhtewp4M2axwxSn3fvNt2x0";s:12:"callback_url";N;}s:11:"http_header";a:16:{s:13:"cache_control";s:62:"no-cache, no-store, must-revalidate, pre-check=0, post-check=0";s:14:"content_length";s:2:"76";s:12:"content_type";s:30:"application/json;charset=utf-8";s:4:"date";s:29:"Fri, 06 Jun 2014 05:49:28 GMT";s:7:"expires";s:29:"Tue, 31 Mar 1981 05:00:00 GMT";s:13:"last_modified";s:29:"Fri, 06 Jun 2014 05:49:28 GMT";s:6:"pragma";s:8:"no-cache";s:6:"server";s:3:"tfe";s:10:"set_cookie";s:100:"guest_id=v1%3A140203376869856713; Domain=.twitter.com; Path=/; Expires=Sun, 05-Jun-2016 05:49:28 UTC";s:6:"status";s:13:"403 Forbidden";s:25:"strict_transport_security";s:17:"max-age=631138519";s:14:"x_access_level";s:25:"read-write-directmessages";s:22:"x_content_type_options";s:7:"nosniff";s:15:"x_frame_options";s:10:"SAMEORIGIN";s:13:"x_transaction";s:16:"2815e347d2cc5c03";s:16:"x_xss_protection";s:13:"1; mode=block";}}';
echo $str;
$aa =  unserialize($str);
echo '<pre>';
print_r($aa);
die;

$account = new Account( 702736716 );
$account->syncAccount();
die;

// $search = new SearchUsers($account);
// $result = $search->searchUsers();
// print_r( App::$accountLimit );
// print_r( $result );
// print_r( $account->updateUserData(  ) );
// print_r($account);
// die;

$schedule = new Schedule( $account );
// print_r($schedule);
// die;


$result = $schedule->schedule();
print_r( $result );
die;