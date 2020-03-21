<?php
/**
 * Author: 大眼猫
 * File: Importer.php
 * Functionality: 测试 Process Manager 与 task
 */

class Importer {

	public static function handle(Closure $next){
		if(!Request::has('file') || empty(Request::get('file'))){
			throw new Error('Empty file !', 402);
			return;
		}else{
			$next();
		}
	}

	public static function task(...$param){
		$time = $param[0];
		Logger::log('Time in '.__METHOD__.' is => '.$time);
	}

	public static function run(...$param){
		Logger::log('Importer process is ready !');

		while (TRUE) {
			$key = 'Key_current_time';
			Cache::set($key, date('Y-m-d H:i:s'));
			$val = Cache::get($key);
			Logger::log('Time in '.__METHOD__.' => '.$val);
			sleep(1);
		}
	}

}