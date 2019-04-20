<?php
/**
 * File: MiniSwoole
 * Author: 大眼猫
 */

class MiniSwoole {

	private $min_version = '7.0';
	private $extensions  = ['pdo', 'redis', 'swoole', 'pdo_mysql'];

	public function boostrap(){
		$this->checkSapi();
		$this->checkVersion();
		$this->checkExtension();
		$this->config();
		$this->init();

		return $this;
	}

	// Only run in CLI
	private function checkSapi(){
		$sapi_type = php_sapi_name();
		if (strtoupper($sapi_type) != 'CLI') {
		    echo 'Error: Mini Swoole ONLY run in cli mode'; die;
		}

		return TRUE;
	}

	// PHP Version must be greater then 7.0
	private function checkVersion(){
		$retval = version_compare(PHP_VERSION, $this->min_version);
		if(-1 == $retval){
			echo 'Error: PHP version must be greater then 7.0'; die;
		}

		return TRUE;
	}

	// Must install necessary extensions
	private function checkExtension(){
		foreach($this->extensions as $extension){
			if(!extension_loaded($extension)){
				echo 'Error: Extension '.$extension.' is required '; die;
			}
		}

		return TRUE;
	}

	public function config(){
		define('TB_PK', 'id');
		define('MINI_SWOOLE_VERSION', '2.0');
		define('LIB_PATH',  APP_PATH.'/library');
		define('CORE_PATH', LIB_PATH.'/core');
		define('CONF_PATH', APP_PATH.'/conf');

		require_once CORE_PATH.'/Task.php';
		require_once CORE_PATH.'/Pool.php';
		require_once LIB_PATH.'/Worker.php';
		require_once CORE_PATH.'/Cache.php';
		require_once CORE_PATH.'/Timer.php';
		require_once CORE_PATH.'/Model.php';
		require_once CORE_PATH.'/Logger.php';
		require_once CORE_PATH.'/Helper.php';
		require_once CORE_PATH.'/Config.php';
		require_once CORE_PATH.'/Server.php';
		require_once CORE_PATH.'/Hooker.php';
		require_once LIB_PATH.'/Function.php';
		require_once CORE_PATH.'/Security.php';
		require_once CORE_PATH.'/Controller.php';

		// Error log
		error_reporting(E_ALL ^ E_NOTICE);
		
		$config = Config::getConfig();
		ini_set('log_errors', 'on');
		ini_set('display_errors', 'off');
        ini_set('error_log', $config['common']['log_file']);
        set_error_handler(['Logger', 'errorHandler'], E_ALL | E_STRICT);

        // APP_NAME
		define('APP_NAME', $config['common']['app_name']);
		
		// TABLE_PREFIX and TB_SUFFIX_SF
		define('TB_PREFIX', $config['common']['tb_prefix']);
		if($config['common']['tb_suffix_sf']){
			define('TB_SUFFIX_SF', $config['common']['tb_suffix_sf']);
		}

        // Autoload
        spl_autoload_register(function($class){
			$file = LIB_PATH.'/'.$class.'.php';
			if(file_exists($file)){
				require_once($file);
			}else{
				$error = 'Error in autoload: No such file => '.$file;
				Helper::raiseError(debug_backtrace(), $error);
			}
		});

		return $this;
	}

	public function Init(){
		include APP_PATH.'/Init.php';
		return $this;
	}

	// Let's go
	public function run(){
		$config = Config::getConfig();

		if($config['tcp']['enable']){
			require CORE_PATH.'/TcpServer.php';
			$server = new TcpServer($config);
		}else if($config['http']['enable']){
			require CORE_PATH.'/HttpServer.php';
			$server = new HttpServer($config);
		}else if($config['udp']['enable']){
			require CORE_PATH.'/UdpServer.php';
			$server = new UdpServer($config);
		}else if($config['websocket']['enable']){
			require CORE_PATH.'/WebSocket.php';
			$server = new WebSocket($config);
		}

		$server->start();
	}
}