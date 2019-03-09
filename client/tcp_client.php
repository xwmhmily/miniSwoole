<?php

//异步客户端
$client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

$client->on("connect", function($cli){
    $d = [];
    $d['controller']  = 'tcp';
    $d['action']      = 'redis';
    $d['key']         = 'foo';
    $d['username'] = 'DELETE FROM sl_table <script>dym</script>';
    $d['password'] = md5(123456);

    // 以 "\r\n" 分包
    $cli->send(json_encode($d)."\r\n");
});

$client->on("receive", function(swoole_client $cli, $data){
    echo $data.PHP_EOL.PHP_EOL;
});

$client->on("error", function(swoole_client $cli){
    echo "error".PHP_EOL;
    $cli->close();
});

$client->on("close", function(swoole_client $cli){
    echo "Connection close".PHP_EOL;
});

$client->connect('127.0.0.1', 9501);