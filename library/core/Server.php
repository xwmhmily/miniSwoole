<?php
/**
 * File: Server.php
 * Author: 大眼猫
 */

abstract class Server {

	public static $type;
	public static $page;
	public static $instance;

	const HTTP_METHOD_GET  = 'GET';
	const HTTP_METHOD_POST = 'POST';
	
	const OS_LINUX        = 'LINUX';
	const TYPE_TCP        = 'tcp';
	const TYPE_UDP        = 'udp';
	const TYPE_HTTP       = 'http';
	const TYPE_WEB_SOCKET = 'websocket';

	public static function stat(){
		$stat = [];
		$stat['swoole_version'] = swoole_version();
		$stat['server']    = self::$type;
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