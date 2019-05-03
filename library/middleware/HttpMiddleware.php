<?php

class HttpMiddleware {

	private static function beforeHandle($method, swoole_http_request $request, swoole_http_response $response){
		$response->write(__METHOD__.'<br />');
	}

	public static function handle($method, swoole_http_request $request, swoole_http_response $response){
		self::beforeHandle($method, $request, $response);
		$response->write(__METHOD__.'<br />');
		self::afterHandle($method, $request, $response);
	}

	private static function afterHandle($method, swoole_http_request $request, swoole_http_response $response){
		$response->write(__METHOD__.'<br />');
		$response->end(__FILE__.'<br />');
	}
}