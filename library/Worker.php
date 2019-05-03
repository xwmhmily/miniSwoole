<?php

class Worker {

	// Do anything you want after worker start
    public static function afterStart(swoole_server $server, int $workerID){
		Logger::log(__FUNCTION__);
		
		// Set a timer on the first workerID, say hello to each connection per second
		/*
		if($workerID == 0){
			$server->tick(1000, function() use ($server, $workerID) {
				foreach($server->connections as $fd){
					$server->push($fd, date('Y-m-d H:i:s ').$fd. " => Hello");
				}
			});
		}
		*/
	}

	// Do anything you want before http request
	public static function beforeRequest($method, swoole_http_request $request, swoole_http_response $response){
		$response->header('Content-Type', 'text/html; charset=utf-8');
		if(isset($request->get['page'])){
			Server::$page = intval($request->get['page']);
		}
	}

	// Do anything you want before websocket message
	public static function beforeMessage(swoole_websocket_server $server, swoole_websocket_frame $frame){
		Logger::log(__FUNCTION__);
	}

	// Do anything you want before udp packet
	public static function beforePacket(swoole_server $server, string $json, array $client){
		Logger::log(__FUNCTION__);
	}

	// Do anything you want before tcp receive
	public static function beforeReceieve(swoole_server $server, int $fd, int $reactorID, string $json){
		Logger::log(__FUNCTION__);
	}

	// Do anything you want after websocket open
    public static function afterOpen(swoole_websocket_server $server, swoole_http_request $request){
        Logger::log(__FUNCTION__);
    }

	// Do anything you want after connection close
    public static function afterClose(swoole_server $server, int $fd, int $reactorID){
        Logger::log(__FUNCTION__);
    }

	// Do anything you want after tcp connect
	public static function afterConnect(swoole_server $server, int $fd, int $reactorID){
		Logger::log(__FUNCTION__);
	}

	// Do anything you want after worker stop
	public static function afterStop(swoole_server $server, int $workerID){
		Logger::log(__FUNCTION__);
	}
	
}