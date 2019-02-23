<?php
/**
 * File: Pool.php
 * Author: 大眼猫
 */

abstract class Pool {

	public static $pool;
	const TYPE_MYSQL = 'MYSQL';
	const TYPE_REDIS = 'REDIS';

	public static function getInstance($type) {
		$obj = self::connect($type);
		if($obj){
			self::$pool[$type][] = $obj;
			return TRUE;
		}

		return FALSE;
	}

	public static function getSlaveInstance(){
		$config = Config::getConfig('mysql_slave');
        return self::connectMySQL($config['host'], $config['port'], $config['user'], $config['pwd'], $config['db']);
	}

	public static function pop($type){
		if(strtoupper($type) == Pool::TYPE_MYSQL){
			return array_pop(self::$pool[$type]);
		}else{
			return self::$pool[$type][0];
		}
	}

	public static function unshift($type, $obj){
		array_unshift(self::$pool[$type], $obj);
	}

	private static function connect($type){
		$config = Config::getConfig($type);

		if(strtoupper($type) == self::TYPE_MYSQL){
			$db   = $config['db'];
	        $host = $config['host'];
	        $user = $config['user'];
	        $port = $config['port'];
	        $pwd  = $config['pwd'];

	        return self::connectMySQL($host, $port, $user, $pwd, $db);
		}else if(strtoupper($type) == self::TYPE_REDIS){
			$host = $config['host'];
			$port = $config['port'];
			$pwd  = $config['pwd'];
			$db   = $config['db'];

	        return self::connectRedis($host, $port, $pwd, $db);
		}
	}

	// 可以单独调用该方法创建新的 MySQL 连接
	public static function connectMySQL($host, $port, $user, $pwd, $db){
		$dsn = 'mysql:host='.$host.';port='.$port.';dbname='.$db;

        try {
            $mysql = new PDO($dsn, $user, $pwd);
        } catch (PDOException $e) {
        	Helper::raiseError(debug_backtrace(), $e->getMessage());
        	Logger::log('Error: fail to init MySQL instance');
            return FALSE;
        }

        Logger::log('Success to init MySQL instance');
        $mysql->query('SET NAMES utf8');
        return $mysql;
	}

	public static function destroy($type){
		self::$pool[$type] = NULL;
	}

	// 可以单独调用该方法创建新的 Redis 连接
	public static function connectRedis($host, $port, $pwd, $db){
		$redis  = new \Redis();
        $retval = $redis->connect($host, $port);
        if(!$retval){
        	Logger::log('Error: fail to init Redis instance');
            return FALSE;
        }

        if($pwd){
        	$auth_retval = $redis->auth($pwd);
        	if(!$auth_retval){
        		Logger::log('Error: fail to auth Redis');
        		return FALSE;
        	}
        }

        $db && $redis->select($db);
        Logger::log('Success to init Redis instance');
        return $redis;
	}
}