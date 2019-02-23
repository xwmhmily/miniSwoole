<?php
/**
 * File: Task.php
 * Author: 大眼猫
 */

abstract class Task {

	public static function add($args){
		Server::$instance->task($args);
	}

	public static function onTask(swoole_server $server, int $taskID, int $workerID, $args = NULL) {
		$data       = $args['data'];
		$action     = $args['action'];
		$controller = $args['controller'];

		$instance = Helper::import($controller);
        if($instance !== FALSE){
            if(method_exists($instance, $action)){
                $instance->$action($data);
                $server->finish(JSON($args));
            }else{
            	$error = 'Error: Method '.$action.' NOT found !';
            	Helper::raiseError(debug_backtrace(), $error);
            }
        }
	}

	// $data 即为 onTask $server->finish($data) 的参数
	public static function onFinish(swoole_server $server, int $taskID, string $data){
		Logger::log(__METHOD__.' taskID => '.$taskID.' finish');
	}
}