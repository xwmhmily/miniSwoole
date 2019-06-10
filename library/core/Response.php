<?php
/**
 * Author: 大眼猫
 * File: HTTP Response.php
 */

class Response {

	private static $instance;
	private static $middleware_error;
	private static $middleware_status;

	public static function getInstance(){
		return self::$instance;
	}

	public static function setInstance($instance){
		self::$instance = $instance;
	}

	public static function setMiddlewareStatus($status){
		self::$middleware_status = $status;
	}

	public static function getMiddlewareStatus(){
		return self::$middleware_status;
	}

	public static function setMiddlewareError($error){
		self::$middleware_error = $error;
	}

	public static function getMiddlewareError(){
		return self::$middleware_error;
	}

	public static function endByMiddleware(){
		$error = self::getMiddlewareError();
		switch (Server::getServerType()) {
			case Server::TYPE_HTTP:
				return self::getInstance()->end($error);
			break;

			case Server::TYPE_TCP:
				return self::getInstance()->send(Request::getFd(), $error);
			break;

			case Server::TYPE_WEB_SOCKET:
				return self::getInstance()->push(Request::getFd(), $error);
			break;

			case Server::TYPE_UDP:
				$clientInfo = Request::getClientInfo();
				return self::getInstance()->sendto($clientInfo['address'], $clientInfo['port'], $error);
			break;
		}
	}

}