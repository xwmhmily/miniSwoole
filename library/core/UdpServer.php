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
    }

    public function start() {
        Server::$type = Server::TYPE_UDP;
        Server::$instance = $this->server;

        swoole_set_process_name(APP_NAME.'_udp_master');
        Logger::log('======= UDP master start =======');
        
        $this->server->start();
    }
}