<?php
/**
 * UdpServer
 */

class UdpServer {

    private $server;

    public function __construct($config) {
        $ip   = $config['udp']['ip'];
        $port = $config['udp']['port'];

        $this->server = new Swoole_Server($ip, $port, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

        $c = [];
        foreach($config['common'] as $key => $val){
            $c[$key] = $val;
        }
        
        $this->server->set($c);
        $this->server->on('task',         ['Task',   'onTask']);
        $this->server->on('finish',       ['Task',   'onFinish']);
        $this->server->on('Packet',       ['Hooker', 'onPacket']);
        $this->server->on('WorkerStop',   ['Hooker', 'onWorkerStop']);
        $this->server->on('WorkerError',  ['Hooker', 'onWorkerError']);
        $this->server->on('WorkerStart',  ['Hooker', 'onWorkerStart']);
        $this->server->on('ManagerStart', ['Hooker', 'onManagerStart']);

        require_once LIB_PATH.'/middleware/UdpMiddleware.php';

        // 是否需要监听额外的端口
        if(isset($config['udp']['listen_ip'])){
            $this->server->addlistener($config['udp']['listen_ip'], $config['udp']['listen_port'], SWOOLE_SOCK_TCP);
        }
    }

    public function start() {
        Server::$type = Server::TYPE_UDP;
        Server::$instance = $this->server;

        if(strtoupper(PHP_OS) == Server::OS_LINUX){
            swoole_set_process_name(APP_NAME.'_udp_master');
        }

        Logger::log('======= UDP master start =======');
        $this->server->start();
    }
}