<?php
/**
 * File: Server.php
 * Author: 大眼猫
 */

abstract class Server {

	private static $instance;
	private static $serverType;
	
	const OS_LINUX        = 'LINUX';
	const TYPE_TCP        = 'tcp';
	const TYPE_UDP        = 'udp';
	const TYPE_HTTP       = 'http';
	const TYPE_WEB_SOCKET = 'websocket';

	public static function getInstance(){
		return self::$instance;
	}

	public static function setInstance($instance){
		if(!self::$instance){
			self::$instance = $instance;
		}

		return TRUE;
	}

	public static function getServerType(){
		return self::$serverType;
	}

	public static function setServerType($serverType){
		self::$serverType = $serverType;
	}

	public static function stat(){
		$stat = [];
		$stat['app']    = APP_NAME;
		$stat['server'] = self::$serverType;
		$stat['php_version']    = phpversion();
		$stat['swoole_version'] = swoole_version();
		$stat['masterPID'] = self::$instance->master_pid;

		$ports = array(self::$instance->ports)[0];
		$ports_arr = [];
		foreach($ports as $port){
			$port = array($port);
			foreach($port as $p){
				$p = array($p)[0];
				unset($p->setting);
				$ports_arr[] = $p;
			}
		}

		$stat['ports'] = $ports_arr;
		$server_stat = self::$instance->stats();
		$server_stat['start_time'] = date('Y-m-d H:i:s', $server_stat['start_time']);
		$stat['stats'] = $server_stat;
		$json = json_encode($stat, JSON_UNESCAPED_UNICODE);

		$stat_file = Config::get('common', 'stat_file');
		file_put_contents($stat_file, $json.PHP_EOL);
	}
}