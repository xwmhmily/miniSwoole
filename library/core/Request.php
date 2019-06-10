<?php
/**
 * Author: 大眼猫
 * File: Request.php
 */

class Request {

	private static $fd;
	private static $page;
	private static $instance;
	private static $clientData;
	private static $clientInfo;

	const HTTP_METHOD_GET  = 'GET';
	const HTTP_METHOD_POST = 'POST';

	public static function has($key){
		return isset(self::$clientData[$key]);
	}

	public static function get($key){
		return self::$clientData[$key];
	}

	public static function set($key, $val){
		self::$clientData[$key] = $val;
	}

	public static function setInstance($instance){
		self::$instance = $instance;
	}

	public static function getInstance(){
		return self::$instance;
	}
	
	public static function setFd($fd){
		self::$fd = $fd;
	}

	public static function getFd(){
		return self::$fd;
	}

	public static function getPage(){
		return self::$page;
	}

	public static function setPage($page){
		self::$page = $page;
	}

	public static function setClientData($clientData){
		self::$clientData = $clientData;
	}

	public static function getClientData(){
		return self::$clientData;
	}

	public static function setClientInfo($clientInfo){
		self::$clientInfo = $clientInfo;
	}

	public static function getClientInfo(){
		return self::$clientInfo;
	}

}