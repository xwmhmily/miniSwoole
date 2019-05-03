<?php

class RabbitMQ {

    public $configs = [];

    //交换机名称
    public $exchange_name;

    //队列名称
    public $queue_name;

    //路由名称
    public $route_key;

    /*
     * 持久化，默认True
     */
    public $durable = True;

    /*
     * 自动删除
     * exchange is deleted when all queues have finished using it
     * queue is deleted when last consumer unsubscribes
     * 
     */
    public $autodelete = False;

    /*
     * 镜像
     * 镜像队列，打开后消息会在节点之间复制，有master和slave的概念
     */
    public $mirror = False;
    
    private $type;
    private $conn;
    private $queue;
    private $channel;
    private $exchange;

    /*
     * @configs array('host'=>$host,'port'=>5672,'username'=>$username,'password'=>$password,'vhost'=>'/')
     */
    public function __construct($exchange_name = 'D_APP_PUSHER', $queue_name = 'Q_APP_PUSHER', $route_key = 'R_APP_PUSHER', $type = AMQP_EX_TYPE_DIRECT) {

        $global_config = Config::get();
        $config = [
            'host' => $global_config['rabbit_host'],
            'port' => $global_config['rabbit_port'],
            'username' => $global_config['rabbit_username'],
            'password' => $global_config['rabbit_password'],
            'vhost' => $global_config['rabbit_vhost'],
        ];

        // TO-DO: 暂时注释做测试
        // $this->setConfig($config);
        $this->type          = $type;
        $this->route_key     = $route_key;
        $this->queue_name    = $queue_name;
        $this->exchange_name = $exchange_name;
    }
    
    private function setConfig($configs) {
        if (!is_array($configs)) {
            throw new Exception('configs is not array');
        }

        if (!($configs['host'] && $configs['port'] && $configs['username'] && $configs['password'])) {
            throw new Exception('configs is empty');
        }

        if (empty($configs['vhost'])) {
            $configs['vhost'] = '/';
        }

        $configs['login'] = $configs['username'];
        unset($configs['username']);
        $this->configs = $configs;
    }

    /*
     * 设置是否持久化，默认为True
     */
    public function setDurable($durable) {
        $this->durable = $durable;
    }

    /*
     * 设置是否自动删除
     */
    public function setAutoDelete($autodelete) {
        $this->autodelete = $autodelete;
    }

    /*
     * 设置是否镜像
     */
    public function setMirror($mirror) {
        $this->mirror = $mirror;
    }

    /*
     * 打开amqp连接
     */
    private function open() {
        if (!$this->conn) {
            try {
                $this->conn = new AMQPConnection($this->configs);
                $this->conn->connect();
                $this->initConnection();
            } catch (AMQPConnectionException $ex) {
                throw new Exception('Cannot connect to RabbitMQ', 500);
            }
        }
    }

    /*
     * RabbitMQ 连接不变
     * 重置交换机，队列，路由等配置
     */
    public function reset($exchange_name, $queue_name, $route_key) {
        $this->exchange_name = $exchange_name;
        $this->queue_name    = $queue_name;
        $this->route_key     = $route_key;
        $this->initConnection();
    }

    /*
     * 初始化Rabbit连接的相关配置
     */
    private function initConnection() {
        if (!$this->exchange_name || !$this->queue_name || !$this->route_key) {
            throw new Exception('exchange_name or queue_name or route_key is empty', 500);
        }

        $this->channel  = new AMQPChannel($this->conn);
        $this->exchange = new AMQPExchange($this->channel);
        //pr($this->exchange); die;
        $this->exchange->setType($this->type);
        $this->exchange->setName($this->exchange_name);

        if($this->durable){
            $this->exchange->setFlags(AMQP_DURABLE);
        }

        if($this->autodelete){
            $this->exchange->setFlags(AMQP_AUTODELETE);
        }

        $this->exchange->declareExchange();

        $this->queue = new AMQPQueue($this->channel);
        $this->queue->setName($this->queue_name);

        if ($this->durable){
            $this->queue->setFlags(AMQP_DURABLE);
        }

        if ($this->autodelete){
            $this->queue->setFlags(AMQP_AUTODELETE);
        }

        if ($this->mirror){
            $this->queue->setArgument('x-ha-policy', 'all');
        }

        $this->queue->declareQueue();
        
        $this->queue->bind($this->exchange_name, $this->route_key);
    }

    public function close() {
        if ($this->conn) {
            $this->conn->disconnect();
        }
    }
    
    public function __sleep() {
        $this->close();
        return array_keys(get_object_vars($this));
    }

    public function __destruct() {
        $this->close();
    }
    
    /*
     * 生产者发送消息
     */
    public function produce($msg) {
        $this->open();

        if(is_array($msg)){
            $msg = json_encode($msg);
        }else{
            $msg = trim(strval($msg));
        }

        return $this->exchange->publish($msg, $this->route_key);
    }

    /*
     * 消费者
     * $fun_name = array($classobj,$function) or function name string
     * $autoack 是否自动应答
     * 
     * function processMessage($envelope, $queue) {
            $msg = $envelope->getBody(); 
            echo $msg."\n"; //处理消息
            $queue->ack($envelope->getDeliveryTag());//手动应答
        }
     */
    public function consume($fun_name, $autoack = True){
        $this->open();
        if (!$fun_name || !$this->queue) return False;

        while(True){
            if ($autoack){
                $this->queue->consume($fun_name, AMQP_AUTOACK); 
            }else{
                $this->queue->consume($fun_name);
            }
        }
    }

}