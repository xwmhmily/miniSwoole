<?php

class Registry {

	private static $obj = [];

	public static function set($key, $val){
		self::$obj[$key] = $val;
	}

	public static function get($key){
		return self::$obj[$key];
	}

	public static function del($key){
		unset(self::$obj[$key]);
	}

	public static function getAll(){
		return self::$obj;
	}

}