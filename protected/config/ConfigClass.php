<?php

class Config{
	
	const DB_DRIVER = 'mysql';
	const URL = 'http://twit-bot.loc';
	const SPARE_FILE_PATH = '/page/spare.php';

	/**
	* Флаг для главного цикла(демона)
	* @var boolean
	*/
	private $_run = true;
	
	public $homeDir;
	public $appDir;
	public $lockDir;
	public $log;

	// язык для юзеров на фолловинг
	public $lang = 'ru';

	// пауза между итерациями демона
	public $loopStep = 10;

	function __construct(){
		$this->homeDir = $_SERVER['DOCUMENT_ROOT'];
		$this->appDir = $this->homeDir.'/protected';
		$this->lockDir = $this->homeDir.'/protected/runtime/lock';
		$this->log = $this->homeDir.'/protected/runtime/log/log.txt';
	}

	/**
	* Метод получения флага на выполнения вечнего цикла(демона)
	*/
	function getRun(){
		return $this->_run;
	}

}
