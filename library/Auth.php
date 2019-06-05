<?php
/**
 * Author: 大眼猫
 * File: Auth.php
 * Functionality: 测试 Middleware
 */

class Auth implements Middleware {

	public static function handle(swoole_http_request $request, Closure $next){
		if(!$request->get['token']){
			throw new Error('Access denied !', 401);
			return;
		}else{
			$request->get['token'] = 'ABCDEFG';
			$next($request);
		}
	}

}