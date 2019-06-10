<?php
/**
 * WebSocket
 */

class WebSocket {

    private $server;

    public function __construct($config) {
        $ip   = $config['websocket']['ip'];
        $port = $config['websocket']['port'];
        $this->server = new swoole_websocket_server($ip, $port);

        $c = [];
        foreach($config['common'] as $key => $val){
            $c[$key] = $val;
        }
        
        $this->server->set($c);
        $this->server->on('task',         ['Task',   'onTask']);
        $this->server->on('finish',       ['Task',   'onFinish']);
        $this->server->on('open',         ['Hooker', 'onOpen']);
        $this->server->on('close',        ['Hooker', 'onClose']);
        $this->server->on('message',      ['Hooker', 'onMessage']);
        $this->server->on('WorkerStop',   ['Hooker', 'onWorkerStop']);
        $this->server->on('WorkerError',  ['Hooker', 'onWorkerError']);
        $this->server->on('WorkerStart',  ['Hooker', 'onWorkerStart']);
        $this->server->on('ManagerStart', ['Hooker', 'onManagerStart']);

        // 是否需要监听额外的端口
        if(isset($config['websocket']['listen_ip'])){
            $this->server->addlistener($config['websocket']['listen_ip'], $config['websocket']['listen_port'], SWOOLE_SOCK_TCP);
        }
    }

    public function start() {
        Server::setInstance($this->server);
        Server::setServerType(Server::TYPE_WEB_SOCKET);

        if(strtoupper(PHP_OS) == Server::OS_LINUX){
            swoole_set_process_name(APP_NAME.'_websocket_master');
        }

        Logger::log('======= Websocket master start =======');
        $this->server->start();
    }
}