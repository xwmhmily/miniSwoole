<?php
/**
 * File: Logger.php
 * Author: 大眼猫
 */

abstract class Logger {

    public static function error($msg) {
        $error = date('Y-m-d H:i:s').' | '.self::getMicrotime().' | '.$msg.PHP_EOL;
        file_put_contents(ERROR_FILE, $error, FILE_APPEND);        
    }
    
    public static function log($msg) {
        $error = date('Y-m-d H:i:s').' | '.self::getMicrotime().' | ';

        if(Server::$clientFD){
            $client = Server::$instance->getClientInfo(Server::$clientFD);
            $error .= $client['remote_ip'].' | ';
        }

        $error .= $msg.PHP_EOL;
        file_put_contents(LOG_FILE, $error, FILE_APPEND);        
    }

	public static function logMySQL($msg) {
        $error = date('Y-m-d H:i:s').' | '.self::getMicrotime().' | '.$msg.PHP_EOL;
        file_put_contents(MYSQL_LOG_FILE, $error, FILE_APPEND);        
    }
    
    // Get current microtime
    public static function getMicrotime() {
        list($usec, $sec) = explode(' ', microtime());
        return sprintf('%.4f', (float) $usec + (float) $sec);
    }

	public static function errorHandler($errorNO, $errorStr, $errorFile, $errorLine, $errorContext = '', $sql = '') {
        // if(strpos($error, 'Undefinedindex') === FALSE && strpos($error, 'Undefinedvariable') === FALSE){
            if(!$sql){
                self::log('ErrorNO: '.$errorNO);
                self::log('Error: '.$errorStr);
                self::log('File: '.$errorFile);
                self::log('Line: '.$errorLine);
                self::log(str_repeat('=', 80));
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