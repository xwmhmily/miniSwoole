<?php

$client = new Swoole\Client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_ASYNC);

$client->on("connect", function(swoole_client $cli) {
	$d = [];
	//$d['module']     = 'api';
	$d['controller'] = 'udp';
	$d['action']     = 'redis';
	$d['username']   = 'fooDELETE FROM sl_table <script>dym</script>';
	$d['password']   = 'fooDELETE 123123</script>';
	$d['captcha']    = 'go';
	$d['key']        = 'foo';
	$d['token']      = 'token';
	$d['file']       = 'mvp';
	$data = json_encode($d);

	$cli->send($data);
});

$client->on("receive", function(swoole_client $cli, $data){
    print_r($data);
});

$client->on("error", function(swoole_client $cli){
    
});

$client->on("close", function(swoole_client $cli){
    
});

$client->connect('127.0.0.1', 9510, 0.5);
