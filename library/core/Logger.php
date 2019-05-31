<?php
/**
 * File: Logger.php
 * Author: 大眼猫
 */

abstract class Logger {

    const LEVEL_DEBUG      = 1;
    const LEVEL_INFO       = 2;
    const LEVEL_WARN       = 3;
    const LEVEL_ERROR      = 4;
    const LEVEL_FATAL      = 5;
    const LOG_METHOD_FILE  = 'FILE';
    const LOG_METHOD_REDIS = 'REDIS';

    private static $log_file;
    private static $last_error;
    private static $error_level;
    private static $mysql_log_file;

    private static $log_method;
    private static $log_redis;
    private static $log_redis_queue;

    public static function init(){
        if(empty(self::$log_method)){
            $config = Config::get('common');
            self::$log_file       = $config['log_file'];
            self::$error_level    = $config['error_level'];
            self::$mysql_log_file = $config['mysql_log_file'];
            self::$log_method = strtoupper($config['log_method']);

            if(self::$log_method == self::LOG_METHOD_REDIS){
                self::getRedisInstance();
            }
        }
    }

    public static function debug($msg){
        self::append(self::LEVEL_DEBUG, $msg);
    }

    public static function info($msg){
        self::append(self::LEVEL_INFO, $msg);
    }

    // Alias of info
    public static function log($msg){
        self::append(self::LEVEL_INFO, $msg);
    }

    public static function warn($msg){
        self::append(self::LEVEL_WARN, $msg);
    }

    public static function error($msg) {
        self::append(self::LEVEL_ERROR, $msg);
    }

    public static function fatal($msg){
        self::append(self::LEVEL_FATAL, $msg);
    }

    private static function append($level, $msg, $mysql_error = FALSE){
        if($level < self::$error_level){
            return;
        }

        switch($level){
            case self::LEVEL_DEBUG:
                $level_text = 'DEBUG';
            break;

            case self::LEVEL_INFO:
                $level_text = 'INFO';
            break;

            case self::LEVEL_WARN:
                $level_text = 'WARN';
            break;

            case self::LEVEL_ERROR:
                $level_text = 'ERROR';
            break;

            case self::LEVEL_FATAL:
                $level_text = 'FATAL';
            break;
        }

        $error = self::combine($level_text, $msg);

        if($mysql_error){
            $file = self::$mysql_log_file;
        }else{
            $file = self::$log_file;
        }

        self::save($file, $error);
    }

    private static function combine($level_text, $msg){
       return self::getDatetime().' | '.self::getMicrotime().' | '.$level_text.' | '.$msg;
    }

    private static function save($file, $error){
        $error .= PHP_EOL;
        if(self::$log_method == self::LOG_METHOD_FILE){
            file_put_contents($file, $error, FILE_APPEND);
        }else if(self::$log_method == self::LOG_METHOD_REDIS){
            try{
                self::$log_redis->lpush(self::$log_redis_queue, $error);
            }catch (Throwable $e){
                file_put_contents($file, $error, FILE_APPEND);
                file_put_contents($file, 'ERROR '.$e->getMessage().PHP_EOL, FILE_APPEND);
            }
        }else{
            // TO-DO: other methods to save log

        }
    }

    private static function saveToSystemLog($msg){
        file_put_contents(self::$log_file, $msg.PHP_EOL, FILE_APPEND);
    }

    public static function getLastError(){
        return self::$last_error;
    }

    public static function logSQL($sql) {
        self::append(self::LEVEL_INFO, $sql, TRUE);
    }

    public static function logErrorSQL($msg) {
        self::append(self::LEVEL_ERROR, $msg, TRUE);
    }

    public static function destroy(){
        self::$log_redis  = NULL;
        self::$log_method = NULL;
    }

    private static function getRedisInstance(){
        $redis_config = Config::get('redis_log');
        self::$log_redis_queue = $redis_config['queue'];
        $retval = self::connectRedis($redis_config);

        if($retval === FALSE){
            self::$log_method = self::LOG_METHOD_FILE;
            self::saveToSystemLog('Logger FAIL TO CONNECT LOG REDIS');
        }else{
            self::$log_redis = $retval;
        }
    }

    private static function connectRedis($config){
		$host = $config['host'];
		$port = $config['port'];
		$pwd  = $config['pwd'];
		$db   = $config['db'];

		$redis  = new \Redis();
        $retval = $redis->connect($host, $port);
        if(!$retval){
            return FALSE;
        }

        if($pwd){
        	$auth_retval = $redis->auth($pwd);
        	if(!$auth_retval){
        		return FALSE;
        	}
        }

        $db && $redis->select($db);
        return $redis;
    }
    
    public static function getDatetime() {
        return date('Y-m-d H:i:s');
    }
    
    // Get current microtime
    public static function getMicrotime() {
        list($usec, $sec) = explode(' ', microtime());
        return sprintf('%.4f', (float) $usec + (float) $sec);
    }

	public static function errorHandler($errorNO, $errorStr, $errorFile, $errorLine, $errorContext = '', $sql = '') {
        self::$last_error['errorNO']   = $errorNO;
        self::$last_error['errorStr']  = $errorStr;
        self::$last_error['errorFile'] = $errorFile;
        self::$last_error['errorLine'] = $errorLine;
        self::$last_error['errorSQL']  = $sql;

        if(!$sql){
            self::error('ErrorNO: '.$errorNO);
            self::error('Error: '.$errorStr);
            self::error('File: '.$errorFile);
            self::error('Line: '.$errorLine);
            self::error(str_repeat('=', 80));
        }else{
            self::logErrorSQL('ErrorNO: '.$errorNO);
            self::logErrorSQL('Error: '.$errorStr);
            self::logErrorSQL('File: '.$errorFile);
            self::logErrorSQL('Line: '.$errorLine);
            self::logErrorSQL('SQL: '.$sql);
            self::logErrorSQL(str_repeat('=', 80));

            if(ENV == 'DEV'){
                throw new Error();
            }
        }
    }

}