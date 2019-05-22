<?php
/**
 * File: Task.php
 * Author: 大眼猫
 */

abstract class Task {

	public static function add($args){
		return Server::getInstance()->task($args);
	}

	public static function onTask(swoole_server $server, int $taskID, int $workerID, $args) {
		call_user_func_array($args['callback'], $args['param']);
		$server->finish($taskID);
	}

	public static function onFinish(swoole_server $server, int $taskID, string $data){
		Logger::log(__METHOD__.' taskID => '.$taskID.' finish');
	}
}