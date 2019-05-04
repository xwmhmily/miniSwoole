<?php

class TcpMiddleware {

	// Do anything you want after tcp connect
	public static function afterConnect(swoole_server $server, int $fd, int $reactorID){
		Logger::log(__METHOD__.' => Client '.$fd.' connected');
	}

	// Do anything you want befpre tcp receive
	public static function beforeReceieve(swoole_server $server, int $fd, int $reactorID, string $json){
		Logger::log(__METHOD__.' => '.$json);
	}

	// Do anything you want after tcp receive
	public static function afterReceieve(swoole_server $server, int $fd, int $reactorID, string $json){
		Logger::log(__METHOD__.' => '.$json);
	}

	// Do anything you want after tcp close
	public static function afterClose(swoole_server $server, int $fd, int $reactorID){
		Logger::log(__METHOD__.' => Client '.$fd.' closed');
	}

}