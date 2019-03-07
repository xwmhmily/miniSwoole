<?php

$config = [
	'common' => [
		'app_name'                 => 'Mini_Swoole',
		'tb_prefix'                => 'sl_',
		'tb_suffix_sf'             => '_',
		'user'                     => 'www',
		'group'                    => 'www',
		'backlog'                  => 128,
		'daemonize'                => 1,
		'worker_num'               => 4,
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
		'package_length_type'      => 'N',
		'package_length_offset'    => 8,
  		'package_body_offset'      => 16,
		'pid_file'   => __DIR__.'/../pid/swoole.pid',
		'log_level'  => 3,
		'log_file'   => '/var/log/app/mini_swoole_'.date('Y-m-d').'.log',
		'error_file' => '/var/log/app/mini_swoole_error_'.date('Y-m-d').'.log',
		'mysql_log_file' => '/var/log/app/mini_swoole_mysql_'.date('Y-m-d').'.log',
	],

	'tcp' => [
		'enable' => FALSE,
		'ip'     => '192.168.1.31',
		'port'   => 9500,
	],

	'udp' => [
		'enable' => FALSE,
		'ip'     => '192.168.1.31',
		'port'   => 9503,
	],

	'http' => [
		'enable' => FALSE,
		// 正常监听的端口
		'ip'     => '192.168.1.31',
		'port'   => 9502,
		// 额外监听的端口
		'listen_ip'   => '127.0.0.1',
		'listen_port' => 9908,
	],

	'websocket' => [
		'enable' => true,
		'ip'     => '192.168.1.31',
		'port'   => 9505,
	],

	'mysql' => [
		'db'   => 'test',
		'host' => '192.168.1.34',
		'port' => 3306,
		'user' => 'root',
		'pwd'  => '123456789',
		'max'  => 3,
	],
	
	'redis' => [
		'db'   => '0',
		'host' => '192.168.1.31',
		'port' => 6379,
		'pwd'  => '123456789',
	],
];

return $config;