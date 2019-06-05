<?php
/**
 * File: Server.php
 * Author: 大眼猫
 */

abstract class Server {

	private static $type;
	private static $page;
	private static $instance;

	private static $httpRequest;
	private static $httpResponse;
	private static $http_middleware_status;

	const HTTP_METHOD_GET  = 'GET';
	const HTTP_METHOD_POST = 'POST';
	
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

	public static function getPage(){
		return self::$page;
	}

	public static function setPage($page){
		self::$page = $page;
	}

	public static function getHttpMiddlewareStatus(){
		return self::$http_middleware_status;
	}

	public static function setHttpMiddlewareStatus($status){
		self::$http_middleware_status = $status;
	}

	public static function getHttpRequest(){
		return self::$httpRequest;
	}

	public static function setHttpRequest($request){
		self::$httpRequest = $request;
	}

	public static function getHttpResponse(){
		return self::$httpResponse;
	}

	public static function setHttpResponse($response){
		self::$httpResponse = $response;
	}

	public static function getType(){
		return self::$type;
	}

	public static function setType($type){
		self::$type = $type;
	}

	public static function stat(){
		$stat = [];
		$stat['app']    = APP_NAME;
		$stat['server'] = self::$type;
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