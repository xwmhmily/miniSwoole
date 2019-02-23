<?php
/**
 * File: Config.php
 * Author: 大眼猫
 */

abstract class Config {

	public static function getConfig($section = '') {
		$config_file = CONF_PATH.'/config_'.ENV.'.php';
		if(!file_exists($config_file)){
			echo 'Error: config file '.$config_file.' NOT FOUND'.PHP_EOL; exit(0);
		}

		$config = include $config_file;
		if($section){
			return $config[strtolower($section)];
		}else{
			return $config;
		}
	}
}
