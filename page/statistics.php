<?php

$file = $_SERVER['DOCUMENT_ROOT'] .'/protected/runtime/cache/statistics.txt';
if( file_exists( $file ) && ( time() - filemtime( $file ) ) < 10000 ){
	$table = unserialize( file_get_contents( $file ) );
	echo 'cache';
} else {
	
	$db = new DataBase();

	// берем за неделю
	$table = '<table border=1 align="center"><tr><th rowspan=2 >account</th>';
	$timeNow = time();
	for( $i=6; $i >= 0; $i-- ){

		$time = $timeNow - ( $i * 86400 );
		$date = date('Y-m-d',$time);
		$table .= "<th colspan=2 >$date</th>";

	}
	$table .= '</tr>';
	$table .= '<tr>';
	for( $i=6; $i >= 0; $i-- ){
		$table .= "<th>fl</th><th>fr</th>";
	}
	$table .= '</tr>';

	$allAccount = $db->get( 'accounts' );
	$summa = 0;
	foreach( $allAccount as $account ){


		$summa += $account->followers_count;
		$table .= "<tr><td>$account->screen_name</td>";
		$condition = $db->getCondition(
			array( 
				array( 
					'field' => 'account_id',
					'mode' => '=',
					'value' => $account->account_id,
					'moreCondition' => ''
				)
			)
		);
		$accountStat = $db->get( 'statistics', array('*'), $condition );
		for( $i=6; $i >= 0; $i-- ){
			
			$dayStatFol = 0;
			$dayStatFr = 0;
			$time = $timeNow - ( $i * 86400 );
			$date = date('Y-m-d',$time);
			foreach( $accountStat as $oneStat ){
				if( !isset($result[$date]['fl']) ){
					$result[$date]['fl'] = 0;
				}

				if( !isset($result[$date]['fr']) ){
					$result[$date]['fr'] = 0;
				}
				if( $oneStat->day == $date ){
					$result[$date]['fl']+= $oneStat->followers_count;
					$result[$date]['fr']+= $oneStat->friends_count;
					$dayStatFol = "$oneStat->followers_count" ;
					$dayStatFr = "$oneStat->friends_count" ;
				} 
			}
			$table .= "<td>$dayStatFol</td><td>$dayStatFr</td>";
		}

		

		// print_r($accountStat);
		// echo '<hr>';

		$table .= '</tr>';

		
	}

	// всего
	$table .= '<tr><td>Всего</td>';
	$i = 0;
	foreach( $result as $day => $oneDay ){
		$list[$i] = $oneDay;
		$resFl = 0;
		$resFr = 0;
		$opratorFl = '';
		$opratorFr = '';
		$colorFl = 'white';
		$colorFr = 'white';
		if( $i > 0 ){
			if( isset( $list[$i]['fl'],$list[$i]['fr'],$list[$i-1]['fl'],$list[$i-1]['fr'] ) ){
				$resFl = $list[$i]['fl'] - $list[$i-1]['fl'];
				if( $resFl > 0 ){
					$opratorFl = '+';
					$colorFl = 'green';
				}
				else {
					$opratorFl = '-';
					$colorFl = 'red';
				}

				$resFr = $list[$i]['fr'] - $list[$i-1]['fr'];
				if( $resFr > 0 ){
					$opratorFr = '+';
					$colorFr = 'green';
				}
				else {
					$opratorFr = '-';
					$colorFr = 'red';
				}

			} 
		}

		$resFl = abs($resFl);
		$resFr = abs($resFr);

		$table .= "<td>$oneDay[fl]<span style=color:$colorFl><sup>$opratorFl$resFl</sup></span></td>
					<td>$oneDay[fr]<span style=color:$colorFr><sup>$opratorFr$resFr</sup></span></td> ";

		$i++;
	}
	$table .="</tr>";

	file_put_contents( $file, serialize( $table ) );
}

echo $table;

die;