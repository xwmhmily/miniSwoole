<?php
/**
 * File: Helper.php
 * Functionality: Model, function loader, raiseError, generateSign, response
 * Author: 大眼猫
 * Date: 2013-5-8
 */

abstract class Helper {
	
	public static function import($module, $controller){
		if($module == 'index'){
			$file = APP_PATH .'/controller/'.ucfirst($controller).'.php';
		}else{
			$file = APP_PATH .'/module/'.ucfirst($module).'/controller/'.ucfirst($controller).'.php';
		}

		if(file_exists($file)){
			require_once $file;
			$class = 'C_'.$controller;
			return new $class();
		}else{
			$error = 'No such file or directory: '.$file;
			self::raiseError(debug_backtrace(), $error);
			return FALSE;
		}
	}

	/**
	 * Load model
	 * <br />After loading a model, the new instance will be added into $obj immediately,
	 * <br />which is used to make sure that the same model is only loaded once per request !
	 *
	 * @param string => model to be loaded
	 * @param string => DB to use: master or slave
	 * @return new instance of $model or raiseError on failure !
	 */
	public static function load($model){
		$default = FALSE;
		$file = APP_PATH .'/model/'.ucfirst($model).'.php';

		if(!file_exists($file)) {
			// 加载默认模型, 减少没啥通用方法的模型
			$default = TRUE;
			$table   = strtolower($model);
			$file    = APP_PATH.'/model/Default.php';
		}

		require_once $file;

		if(!$default){
			$model = 'M_'.$model;
			$class = new $model();
		}else{
			$model = 'M_Default';
			$class = new $model($table);
		}
		
		return $class;
	}

	/**
	 * Raise error if it is under DEV
	 *
	 * @param string debug back trace info
	 * @param string error to display
	 * @param string error SQL statement
	 * @return null
	 */
	public static function raiseError($trace, $error, $sql = '') {
		$errorNO   = 9999; 

		if(isset($trace[0]['file'])){
			$errorFile = $trace[0]['file'];
		}

		if(isset($trace[0]['line'])){
			$errorLine = $trace[0]['line'];
		}

		Logger::errorHandler($errorNO, $error, $errorFile, $errorLine, NULL, $sql);
	}
}
