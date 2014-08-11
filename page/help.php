<?php

if( isset( $_POST['time'] ) && !empty( $_POST['time'] ) ){
	$time =(int)$_POST['time'];
} else {
	$time = '';
}

?>

Конвертация времени:<br>
<form method="post" action="">
	<input placeholder="Введите timestamp" type="text" name="time" value="<?php echo $time; ?>"  >
	<button>Click</button>
</form>

<?php 
	if( $time ){
		echo date('Y/m/d H:i:s', $time );
	}
?>
