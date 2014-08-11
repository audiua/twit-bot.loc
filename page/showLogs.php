<?php

if( isset( $_GET['logs'] ) ){
	
	$limit = 20;
	$sql = "SELECT count( id ) FROM logs";
	$countLogs = (int) DB::connect()->query( $sql )->fetchColumn();
	$urlPage = "/page/showLogs.php?logs&page=";

	$sql = "SELECT
				*
			FROM
				logs
			ORDER BY 
				id DESC
			LIMIT
				$limit";

	if( isset( $_GET['page'] ) ){
		$page = (int)$_GET['page'];
		$offset = $page * $limit;

		$sql .= ",$offset";
	}


	$logs = DB::connect()->query( $sql )->fetchAll( PDO::FETCH_ASSOC );
	
	echo '<table border = 1><tr><td>Id</td> <td>Message</td> <td>In line</td> <td>In file</td> <td>time</td> <td>mode</td>';
	foreach( $logs as $log ){
		$time = date('Y/m/d/ H:i:s',$log['in_time']);
		echo "<tr><td>$log[id]</td> <td>$log[message]</td> <td>$log[in_line]</td> <td>$log[in_file]</td> <td>$time</td> <td>$log[mode]</td></tr>";
	}

	echo '</table><br><hr>';

	if( $countLogs > $limit ){
		echo '<a href="/page/showLogs.php?logs">1</a>';
	}


	if( $countLogs ){
		$countPage = floor( $countLogs / $limit );

		$i = 1;
		while( $countPage ){
			$i++;

			$urlPage .= $i;
			echo '<a href="'. $urlPage .'"> ' . $i . ' </a>';
			

			$countPage--;
		}
	}

}