<?php
/**
 * TcpServer
 * Remark: 1.7.15+版本,当设置dispatch_mode = 1/3时会自动去掉onConnect/onClose事件回调
 */

class TcpServer {

    private $server;

    public function __construct($config) {
        $ip   = $config['tcp']['ip'];
        $port = $config['tcp']['port'];

        $this->server = new Swoole_Server($ip, $port);

        $c = [];
        foreach($config['common'] as $key => $val){
            $c[$key] = $val;
        }
        
        $this->server->set($c);
        $this->server->on('task',         ['Task',   'onTask']);
        $this->server->on('finish',       ['Task',   'onFinish']);
        $this->server->on('Close',        ['Hooker', 'onClose']);
        $this->server->on('Connect',      ['Hooker', 'onConnect']);
        $this->server->on('Receive',      ['Hooker', 'onReceive']);
        $this->server->on('WorkerStop',   ['Hooker', 'onWorkerStop']);
        $this->server->on('WorkerError',  ['Hooker', 'onWorkerError']);
        $this->server->on('WorkerStart',  ['Hooker', 'onWorkerStart']);
        $this->server->on('ManagerStart', ['Hooker', 'onManagerStart']);

        // 是否需要监听额外的端口
        if(isset($config['tcp']['listen_ip'])){
            $this->server->addlistener($config['tcp']['listen_ip'], $config['tcp']['listen_port'], SWOOLE_SOCK_TCP);
        }
    }

    public function start() {
        Server::setType(Server::TYPE_TCP);
        Server::setInstance($this->server);

        if(strtoupper(PHP_OS) == Server::OS_LINUX){
            swoole_set_process_name(APP_NAME.'_tcp_master');
        }
        
        Logger::log('======= TCP master start =======');
        $this->server->start();
    }
}