<?php

$client = new Swoole\Client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_ASYNC);

$client->on("connect", function(swoole_client $cli) {
    $d = [];
	$d['controller'] = 'udp';
	$d['action']     = 'login';
	$d['username']   = 'fooDELETE FROM sl_table <script>dym</script>';
	$d['password']   = 'fooDELETE 123123</script>';
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

$client->connect('192.168.1.31', 9510, 0.5);
