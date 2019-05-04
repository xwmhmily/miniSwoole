<?php

class WebsocketMiddleware {

	// Do anything you want befpre websocket packet
	public static function afterOpen(swoole_websocket_server $server, swoole_http_request $request){
		Logger::log(__METHOD__.' => '.JSON($request));
	}

	// Do anything you want before websocket message
	public static function beforeMessage(swoole_websocket_server $server, swoole_websocket_frame $frame){
		Logger::log(__METHOD__.' => '.JSON($frame));
	}

	// Do anything you want after websocket message
	public static function afterMessage(swoole_websocket_server $server, swoole_websocket_frame $frame){
		Logger::log(__METHOD__.' => '.JSON($frame));
	}

}