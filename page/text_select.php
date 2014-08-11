<?php
header('Content-type: text/html; charset=utf-8');
$one = mb_strtolower('Доброе утро, супергерои.','UTF-8');
$two = mb_strtolower('доброе утро','UTF-8');
echo $one;
echo $two;
echo mb_detect_encoding($one);
echo mb_detect_encoding($two);

if( mb_strpos( $one , $two ) === false ){
	echo 'not';
} else {
	echo 'yes';
}