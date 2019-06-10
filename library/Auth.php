<?php
/**
 * Author: 大眼猫
 * File: Auth.php
 * Functionality: 测试 Middleware
 */

class Auth implements Middleware {

	public static function handle(Closure $next){
		if(!Request::has('token') || empty(Request::get('token'))){
			throw new Error('Access denied !', 401);
			return;
		}else{
			Request::set('token', 'ABCDEFG');
			$next();
		}
	}

}