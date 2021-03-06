<?php

$config = [
	'common' => [
		'app_name'                 => 'Mini_Swoole',
		'app_version'              => '2.0',
		'tb_pk'                    => 'id',
		'tb_prefix'                => 'sl_',
		'tb_suffix_sf'             => '_',
		'user'                     => 'www',
		'group'                    => 'www',
		'backlog'                  => 128,
		'daemonize'                => 1,
		'worker_num'               => 1,
		'task_ipc_mode'            => 1,
		'task_worker_num'          => 1,
		'open_tcp_nodelay'         => 0,
		'open_mqtt_protocol'       => 0,
		'open_cpu_affinity'        => 1,
		'dispatch_mode'            => 2,
		'tcp_fastopen'             => 1,
		'heartbeat_idle_time'      => 120,
		'heartbeat_check_interval' => 30,
		'open_eof_check'           => TRUE,
		'package_eof'              => "\r\n",
		'open_length_check'        => true,
		'package_length_type'      => 'N',
		'package_length_offset'    => 8,
  		'package_body_offset'      => 16,
		'log_level'                => 3,
		'error_level'              => 2,
		'module'                   => 'api',
		'log_method'               => 'file',
		'pid_file'                 => __DIR__.'/../log/swoole.pid',
		'stat_file'                => __DIR__.'/../log/swoole.stat',
		'log_file'       => __DIR__.'/../log/swoole_error_'.date('Y-m-d').'.log',
		'mysql_log_file' => __DIR__.'/../log/swoole_mysql_'.date('Y-m-d').'.log',
	],

	'tcp' => [
		'enable' => FALSE,
		'ip'     => '127.0.0.1',
		'port'   => 9501,
	],

	'udp' => [
		'enable' => FALSE,
		'ip'     => '127.0.0.1',
		'port'   => 9510,
	],

	'http' => [
		'enable' => TRUE,

		// 正常监听的端口
		'ip'     => '*',
		'port'   => 9100,

		// 额外监听的端口
		'listen_ip'   => '127.0.0.1',
		'listen_port' => 9908,

		'enable_static_handler' => true,
		'document_root' => APP_PATH.'/public',
	],

	'websocket' => [
		'enable' => FALSE,
		'ip'     => '127.0.0.1',
		'port'   => 9509,
	],

	'mysql' => [
		'db'   => 'tongshang',
		'host' => '127.0.0.1',
		'port' => 3306,
		'user' => 'root',
		'pwd'  => '123456',
		'max'  => 2,
		'log_sql' => true,
	],

	'mysql_slave' => [
		'db'   => 'tongshang',
		'host' => '127.0.0.1',
		'port' => 3306,
		'user' => 'root',
		'pwd'  => '123455',
	],
	
	'redis' => [
		'db'   => '0',
		'host' => '127.0.0.1',
		'port' => 6379,
		'pwd'  => '123456',
	],

	'redis_log' => [
		'db'    => '0',
		'host'  => '127.0.0.1',
		'port'  => 6379,
		'pwd'   => '123456',
		'queue' => 'Queue_mini_log',
	],

	'process' => [
		'Mini_Swoole_importer'=> [
			'num' => 1, 
			'mysql' => true,
			'redis' => true,
			'callback' => ['Importer', 'run'],
		],
	],
];

return $config;