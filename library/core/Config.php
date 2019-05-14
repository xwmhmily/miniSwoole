<?php
/**
 * File: Config.php
 * Author: 大眼猫
 */

abstract class Config {

	private static $config;

	public static function get($section = '', $key = '') {
		if(!self::$config){
			$config_file = CONF_PATH.'/'.ENV.'.php';
			if(!file_exists($config_file)){
				echo 'Error: config file '.$config_file.' NOT FOUND'.PHP_EOL; exit(0);
			}

			self::$config = include $config_file;
		}

		if(!$section){
			return self::$config;
		}
		
		if($key){
			return self::$config[strtolower($section)][$key];
		}else{
			return self::$config[strtolower($section)];
		}
	}
}