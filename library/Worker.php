<?php

class Worker {

	// Do something after worker start
    public static function afterStart(swoole_server $server, int $workerID){
		WorkerMiddleware::afterStart($server, $workerID);

		if($workerID == 0){
			$server->tick(1000, function(){
				Server::stat();
			});
		}
	}

	// Do something after worker stop
	public static function afterStop(swoole_server $server, int $workerID){
		WorkerMiddleware::afterStop($server, $workerID);
	}

	// Do something want before http request
	public static function beforeRequest($method, swoole_http_request $request, swoole_http_response $response){
		$response->header('Content-Type', 'text/html; charset=utf-8');
		if(isset($request->get['page'])){
			Server::$page = intval($request->get['page']);
		}

		HttpMiddleware::beforeRequest($method, $request, $response);
	}

	// Do something after http request
	// TO-DO: 检测Connection是否Alive
	public static function afterRequest($method, swoole_http_request $request, swoole_http_response $response){
		HttpMiddleware::afterRequest($method, $request, $response);
		$response->end();
	}

	// Do something before udp packet
	public static function beforePacket(swoole_server $server, string $json, array $client){
		UdpMiddleware::beforePacket($server, $json, $client);
	}

	// Do something after udp packet
	public static function afterPacket(swoole_server $server, string $json, array $client){
		UdpMiddleware::afterPacket($server, $json, $client);
	}

	// Do something after tcp connect
	public static function afterConnect(swoole_server $server, int $fd, int $reactorID){
		TcpMiddleware::afterConnect($server, $fd, $reactorID);
	}

	// Do something before tcp receive
	public static function beforeReceieve(swoole_server $server, int $fd, int $reactorID, string $json){
		TcpMiddleware::beforeReceieve($server, $fd, $reactorID, $json);
	}

	// Do something after tcp receive
	public static function afterReceieve(swoole_server $server, int $fd, int $reactorID, string $json){
		TcpMiddleware::afterReceieve($server, $fd, $reactorID, $json);
	}

	// Do something after tcp close
    public static function afterClose(swoole_server $server, int $fd, int $reactorID){
        TcpMiddleware::afterClose($server, $fd, $reactorID);
    }

	// Do something after websocket open
    public static function afterOpen(swoole_websocket_server $server, swoole_http_request $request){
        WebsocketMiddleware::afterOpen($server, $request);
	}
	
	// Do something before websocket message
	public static function beforeMessage(swoole_websocket_server $server, swoole_websocket_frame $frame){
		WebsocketMiddleware::beforeMessage($server, $frame);
	}

	// Do something after websocket message
	public static function afterMessage(swoole_websocket_server $server, swoole_websocket_frame $frame){
		WebsocketMiddleware::afterMessage($server, $frame);
	}
	
}