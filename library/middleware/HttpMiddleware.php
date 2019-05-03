<?php

class HttpMiddleware {

	public static function beforeHandle($method, swoole_http_request $request, swoole_http_response $response){
		
	}

	public static function handle($method, swoole_http_request $request, swoole_http_response $response){
		$response->write(__METHOD__.'<br />');
	}

	public static function afterHandle($method, swoole_http_request $request, swoole_http_response $response){
		
	}
}