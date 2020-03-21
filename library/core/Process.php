<?php
/**
 * Author: 大眼猫
 * File: Process.php
 */

class Process {

	private static function getConfig($name = NULL){
		$process = Config::get('process');
		if($name){
			return $process[$name];
		}else{
			return $process;
		}
	}

	private static function fork($name, $config){
		pcntl_signal(SIGCHLD, SIG_IGN);
		for($i = 1; $i <= $config['num']; $i++){
			Logger::log('Forking '.$name.'......');
			$process = new swoole_process(function (swoole_process $process) use($name, $config, $i) {
				$process->name($name) && $process->daemon(1);

				Logger::init();
				if($config['mysql']){
					$retval = Pool::createMySQLConnectionPool();
					if(!$retval){
						Logger::warn('Process '.$name.' fail to connect MySQL, exits');
					}
				}

				if($config['redis']){
					$retval = Pool::createRedisConnectionPool(Pool::TYPE_REDIS);
					if(!$retval){
						Logger::warn('Process '.$name.' fail to connect Redis, exits');
					}
				}

				$param = [];
				if(isset($config['param'])){
					$param = $config['param'];
				}
				$param[] = $i;
				call_user_func_array($config['callback'], $param);
			}, 0);

			Logger::destroy();
			$pid = $process->start();
			Logger::init();
			Logger::log('Process '.$name.' with pid '.$pid.' is running !');
		}
	}

	public static function heartbeat(){
		Logger::log('Heartbeating process ......');
		$process = self::getConfig();
		Logger::log('Process config => '.JSON($process));
		if($process){
			foreach($process as $name => $config){
				if($config['num']){
					$output = $retval = [];
					$cmd = "ps -ef | grep $name | grep -v \"grep\" | wc -l";
					exec($cmd, $output, $retval);
					if($output[0] == 0){
						Logger::warn('Heartbeat: '.$name.' is DOWN, restarting ......');
						self::fork($name, $config);
					}else{
						Logger::log('Heartbeat: '.$name.' is running');
					}
				}
			}
		}
	}

}