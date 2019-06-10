<?php

class Worker {

	// Do something after worker start
    public static function afterStart(swoole_server $server, int $workerID){
		if($workerID == 0){
			$server->tick(1000, function(){
				Server::stat();
			});
		}
	}

	// Do something after worker stop
	public static function afterStop(swoole_server $server, int $workerID){

	}

	// Do something want before http request
	public static function beforeRequest($method, swoole_http_request $request, swoole_http_response $response){
		if(isset($request->get['page'])){
			Request::setPage(intval($request->get['page']));
		}else{
			Request::setPage(1);
		}

		Request::setInstance($request);
		Response::setInstance($response);
		
		$method = strtoupper($method);
		if($method == Request::HTTP_METHOD_GET){
			Request::setClientData($request->get);
		}else{
			Request::setClientData($request->post);
		}
		
		$response->header('Content-Type', 'text/html; charset=utf-8');
	}

	// Do something after http request
	// TO-DO: 检测Connection是否Alive
	public static function afterRequest($method, swoole_http_request $request, swoole_http_response $response){
		
	}

	// Do something before udp packet
	public static function beforePacket(swoole_server $server, string $json, array $client){
		Response::setInstance($server);
		Request::setClientInfo($client);
		$clientData = json_decode($json, TRUE);
		Request::setClientData($clientData);
	}

	// Do something after udp packet
	public static function afterPacket(swoole_server $server, string $json, array $client){
		
	}

	// Do something after tcp connect
	public static function afterConnect(swoole_server $server, int $fd, int $reactorID){
		
	}

	// Do something before tcp receive
	public static function beforeReceieve(swoole_server $server, int $fd, int $reactorID, string $json){
		Response::setInstance($server);

		Request::setFd($fd);
		$clientData = json_decode($json, TRUE);
		Request::setClientData($clientData);
	}

	// Do something after tcp receive
	public static function afterReceieve(swoole_server $server, int $fd, int $reactorID, string $json){
		
	}

	// Do something after tcp close
    public static function afterClose(swoole_server $server, int $fd, int $reactorID){
        
    }

	// Do something after websocket open
    public static function afterOpen(swoole_websocket_server $server, swoole_http_request $request){
        
	}
	
	// Do something before websocket message
	public static function beforeMessage(swoole_websocket_server $server, swoole_websocket_frame $frame){
		Response::setInstance($server);
		Request::setFd($frame->fd);
		$data = json_decode($frame->data, TRUE);
		Request::setClientData($data);
	}

	// Do something after websocket message
	public static function afterMessage(swoole_websocket_server $server, swoole_websocket_frame $frame){
		
	}
	
}