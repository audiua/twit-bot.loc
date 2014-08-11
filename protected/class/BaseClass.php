<?php

/**
* Базовый класс для все классов
* определяет свойство ностроек
*
* @author audiua <audiua@yandex.ru> 
*/
class Base{

	public $config;

	function __construct(){
		$this->config = new Config();
	}
}