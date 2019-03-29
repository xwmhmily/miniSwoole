<?php
/**
 * File: Logger.php
 * Author: 大眼猫
 */

abstract class Logger {

    const LEVEL_DEBUG = 1;
    const LEVEL_INFO  = 2;
    const LEVEL_WARN  = 3;
    const LEVEL_ERROR = 4;
    const LEVEL_FATAL = 5;

    public static $last_error;

    public static function debug($msg){
        self::appand(self::LEVEL_DEBUG, $msg);
    }

    public static function info($msg){
        self::appand(self::LEVEL_INFO, $msg);
    }

    // Alias of info
    public static function log($msg){
        self::appand(self::LEVEL_INFO, $msg);
    }

    public static function warn($msg){
        self::appand(self::LEVEL_WARN, $msg);
    }

    public static function error($msg) {
        self::appand(self::LEVEL_ERROR, $msg);
    }

    public static function fatal($msg){
        self::appand(self::LEVEL_FATAL, $msg);
    }

    private static function appand($level, $msg){
        $config = Config::getConfig();
        $log_level = $config['common']['error_level'];
        if($level < $log_level){
            return;
        }else{
            $error_file = $config['common']['error_file'];
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

        $error = date('Y-m-d H:i:s').' | '.self::getMicrotime().' | ' .$level_text.' | '.$msg.PHP_EOL;
        file_put_contents($error_file, $error, FILE_APPEND);
    }

	public static function logMySQL($msg) {
        $config = Config::getConfig();
        $error = date('Y-m-d H:i:s').' | '.self::getMicrotime().' | ERROR | '.$msg.PHP_EOL;
        file_put_contents($config['common']['mysql_log_file'], $error, FILE_APPEND);        
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

        // if(strpos($error, 'Undefinedindex') === FALSE && strpos($error, 'Undefinedvariable') === FALSE){
            if(!$sql){
                self::error('ErrorNO: '.$errorNO);
                self::error('Error: '.$errorStr);
                self::error('File: '.$errorFile);
                self::error('Line: '.$errorLine);
                self::error(str_repeat('=', 80));
            }else{
                self::logMySQL('ErrorNO: '.$errorNO);
                self::logMySQL('Error: '.$errorStr);
                self::logMySQL('File: '.$errorFile);
                self::logMySQL('Line: '.$errorLine);
                self::logMySQL('SQL: '.$sql);
                self::logMySQL(str_repeat('=', 80));
            }
        //}
	}
}