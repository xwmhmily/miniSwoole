<?php
/*
 * Server callback functions
 * Remark: 1.7.15+版本, 当设置dispatch_mode = 1/3 时会自动去掉 onConnect / onClose 事件回调
 * */

class Hooker {

    // Manager start
    public static function onManagerStart(swoole_server $server){
        swoole_set_process_name(APP_NAME.'_manager');
    }

    // Worker start
    public static function onWorkerStart(swoole_server $server, int $workerID){
		if ($server->taskworker) {
            $max = 1;
            $process_name = APP_NAME.'_task';
        }else{
            $config = Config::getConfig(Pool::TYPE_MYSQL);
            $max = $config['max'];
            !$max && $max = 1;
            $process_name = APP_NAME.'_worker';
        }

        swoole_set_process_name($process_name);

        for($i = 1; $i <= $max; $i++){
            $retval = Pool::getInstance(Pool::TYPE_MYSQL);
            if($retval === FALSE){
                Logger::log('Error: Worker '.$workerID.' fail to connect MySQL !');
            }
        }

        $retval = Pool::getInstance(Pool::TYPE_REDIS);
        if($retval === FALSE){
            Logger::log('Error: Worker '.$workerID.' fail to connect Redis !');
        }

        Worker::afterStart($server, $workerID);
        Logger::log('Worker '.$workerID.' ready for connections ...');
    }

    // Http onRequest, 将请求路由至控制器
    public static function onRequest(swoole_http_request $request, swoole_http_response $response){
        $method = strtoupper($request->server['request_method']);
        $request_uri = explode('/', $request->server['request_uri']);

        $controller = $action = '';
        if($method == 'GET'){
            if(isset($request->get['controller'])){
                $action     = trim($request->get['action']);
                $controller = trim($request->get['controller']);
            }else{
                if(isset($request_uri[2])){
                    $action = trim($request_uri[2]);
                }

                $controller = trim($request_uri[1]);
            }
        }else if($method == 'POST'){
            if(isset($request->post['controller'])){
                $action     = trim($request->post['action']);
                $controller = trim($request->post['controller']);
            }else{
                $action     = trim($request_uri[2]);
                $controller = trim($request_uri[1]);
            }
        }else{
            $response->end('Error: Only GET and POST supported now !'); return;
        }

        if(!$controller){
            $controller = 'index';
        }

        Worker::beforeRequest($method, $request, $response);

        if($controller){
            $instance = Helper::import($controller);

            if($instance !== FALSE){
                $instance->method   = $method;
                $instance->request  = $request;
                $instance->response = $response;

                if(!$action){
                    $action = 'index';
                }
                $instance->$action();
            }else{
                $response->status('404');

                $rep['code']  = 0;
                $rep['error'] = 'Controller '.$controller.' not found';
                $response->end(JSON($rep));
            }
        }
    }

    // TCP onConnect
    public static function onConnect(swoole_server $server, int $fd, int $reactorID){
        Worker::afterConnect($server, $fd, $reactorID);
        Logger::log('Client '.$fd.' connected');
    }

    // TCP onReceive
    public static function onReceive(swoole_server $server, int $fd, int $reactorID, string $json){
        Worker::beforeReceieve($server, $fd, $reactorID, $json);

        // 分包
        $data_list = explode("\r\n", $json);
        if($data_list){
            foreach($data_list as $msg){
                $data = json_decode($msg, TRUE);
                if($data){
                    $controller = trim($data['controller']);
                    if($controller){
                        $instance = Helper::import($controller);

                        if($instance !== FALSE){
                            $instance->data   = $data;
                            $instance->server = $server;
                            $instance->fd = Server::$clientFD = $fd;

                            $action = trim($data['action']);
                            !$action && $action = 'index';
                            $instance->$action();
                        }else{
                            $rep['code']  = 0;
                            $rep['error'] = 'Controller '.$controller.' not found';
                            $server->send($fd, JSON($rep));
                        }
                    }
                }
            }
        }
    }

    // UDP onPacket
    public static function onPacket(swoole_server $server, string $json, array $client){
        Worker::beforePacket($server, $json, $client);
        $data = json_decode($json, TRUE);
        if(!$data){
            $rep['code']  = 0;
            $rep['error'] = 'Not valid JSON';
            $server->sendto($client['address'], $client['port'], JSON($rep));
        }else{
            $controller = trim($data['controller']);
            if($controller){
                $instance = Helper::import($controller);

                if($instance !== FALSE){
                    $instance->data   = $data;
                    $instance->server = $server;
                    $instance->client = $client;

                    $action = trim($data['action']);
                    !$action && $action = 'index';
                    $instance->$action();
                }else{
                    $rep['code']  = 0;
                    $rep['error'] = 'Controller '.$controller.' not found';
                    $server->sendto($client['address'], $client['port'], JSON($rep));
                }
            }
        }
    }

    // Websocket onOpen
    public static function onOpen(swoole_websocket_server $server, swoole_http_request $request){
        Worker::afterOpen($server, $request);
        Logger::log('Client '.$request->fd.' connected');
    }

    // Websocket onMessage
    public static function onMessage(swoole_websocket_server $server, swoole_websocket_frame $frame){
        Worker::beforeMessage($server, $frame);
        $data = json_decode($frame->data, 1);
        if(!$data){
            $rep['code']  = 0;
            $rep['error'] = 'Not valid JSON';
            $server->push($frame->fd, JSON($rep));
        }else{
            $controller = trim($data['controller']);
            if($controller){
                $instance = Helper::import($controller);

                if($instance !== FALSE){
                    $instance->data   = $data;
                    $instance->server = $server;
                    $instance->fd     = $frame->fd;

                    $action = trim($data['action']);
                    !$action && $action = 'index';
                    $instance->$action();
                }else{
                    $rep['code']  = 0;
                    $rep['error'] = 'Controller '.$controller.' not found';
                    $server->push($frame->fd, JSON($rep));
                }
            }
        }
    }

    // onClose
    public static function onClose(swoole_server $server, int $fd, int $reactorID){
        Worker::afterClose($server, $fd, $reactorID);
        Logger::log('Client '.$fd.' closed');
    }

    // Worker error
	public static function onWorkerError(swoole_server $serv, int $workerID, int $workerPID, int $exitCode, int $signal){
		Logger::log('Worker '.$workerID.' exit with code '.$exitCode.' and signal '.$signal);
	}

    // Worker stop
	public static function onWorkerStop(swoole_server $server, int $workerID){
		Worker::afterStop($server, $workerID);
		Logger::log('Worker '.$workerID.' stop');
	}

}