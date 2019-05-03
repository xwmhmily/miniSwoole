<?php

class WorkerMiddleware {

	// Do anything you want after worker start
	public static function afterStart(swoole_server $server, int $workerID){
		Logger::log(__METHOD__);
	}

	// Do anything you want after worker stop
	public static function afterStop(swoole_server $server, int $workerID){
		Logger::log(__METHOD__);
	}

}