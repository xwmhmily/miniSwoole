<?php

class HttpMiddleware {

	// Do anything you want before http request
	public static function beforeRequest($method, swoole_http_request $request, swoole_http_response $response){
		
	}

	// Do anything you want after http request
	public static function afterRequest($method, swoole_http_request $request, swoole_http_response $response){
		
	}

}