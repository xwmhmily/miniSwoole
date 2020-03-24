<?php
/*
 * Server callback functions
 * Remark: 1.7.15+版本, 当设置dispatch_mode = 1/3 时会自动去掉 onConnect / onClose 事件回调
 * */

class Hooker {

    // Manager start
    public static function onManagerStart(swoole_server $server){
        if(strtoupper(PHP_OS) == Server::OS_LINUX){
            swoole_set_process_name(APP_NAME.'_manager');
        }
    }

    // Worker start
    public static function onWorkerStart(swoole_server $server, int $workerID){
		if ($server->taskworker) {
            $max = 1;
            $process_name = APP_NAME.'_task';
        }else{
            $config = Config::get(Pool::TYPE_MYSQL);
            $max = $config['max'];
            !$max && $max = 1;
            $process_name = APP_NAME.'_worker';
        }

        if(strtoupper(PHP_OS) == Server::OS_LINUX){
            swoole_set_process_name($process_name);
        }

        for($i = 1; $i <= $max; $i++){
            $retval = Pool::getInstance(Pool::TYPE_MYSQL);
            if($retval === FALSE){
                Logger::error('Worker '.$workerID.' fail to connect MySQL !');
            }
        }

        $retval = Pool::getInstance(Pool::TYPE_REDIS);
        if($retval === FALSE){
            Logger::error('Worker '.$workerID.' fail to connect Redis !');
        }

        Worker::afterStart($server, $workerID);
        Logger::log('Worker '.$workerID.' ready for connections ...');
    }

    // Http onRequest, 将请求路由至控制器
    public static function onRequest(swoole_http_request $request, swoole_http_response $response){
        $method = strtoupper($request->server['request_method']);
        if($method != Request::HTTP_METHOD_GET && $method != Request::HTTP_METHOD_POST){
            $response->end('Error: Only GET and POST supported now !'); return;
        }

        Worker::beforeRequest($method, $request, $response);
        Router::routerStartup();
        $retval = Router::parse($request->server['request_uri']);
        $module = $retval['module'];
        $controller = $retval['controller'];
        $action = $retval['action'];
        Router::routerShutdown();

        $instance = Helper::import($module, $controller);
        $middleware_status = Response::getMiddlewareStatus();

        if($middleware_status !== FALSE){
            if($instance !== FALSE){
                $instance->method   = $method;
                $instance->request  = $request;
                $instance->response = $response;

                $retval = $instance->$action();
                $response->end($retval);
            }else{
                $response->status(404);

                $rep['code']  = 0;
                $rep['error'] = 'Controller '.$controller.' not found';
                $response->end(JSON($rep));
            }
        }else{
            Response::endByMiddleware();
        }
    }

    // TCP onConnect
    public static function onConnect(swoole_server $server, int $fd, int $reactorID){
        Worker::afterConnect($server, $fd, $reactorID);
    }

    // TCP onReceive
    public static function onReceive(swoole_server $server, int $fd, int $reactorID, string $json){
        Worker::beforeReceieve($server, $fd, $reactorID, $json);
        Router::routerStartup();

        // 分包
        $eof = Config::get('common', 'package_eof');
        $data_list = explode($eof, $json);
        if($data_list){
            foreach($data_list as $msg){
                $data = json_decode($msg, TRUE);

                if($data){
                    $retval = Router::parse($data);
                    $module = $retval['module'];
                    $controller = $retval['controller'];
                    $action = $retval['action'];
                    Router::routerShutdown();

                    if($controller){
                        $instance = Helper::import($module, $controller);
                        $middleware_status = Response::getMiddlewareStatus();
                        if($middleware_status !== FALSE){
                            if($instance !== FALSE){
                                $instance->fd     = $fd;
                                $instance->data   = $data;
                                $instance->server = $server;

                                $retval = $instance->$action();
                                $server->send($fd, $retval);
                            }else{
                                $rep['code']  = 0;
                                $rep['error'] = 'Controller '.$controller.' not found';
                                $server->send($fd, JSON($rep));
                            }
                        }else{
                            Response::endByMiddleware();
                        }
                    }
                }
            }
        }
    }

    // UDP onPacket
    public static function onPacket(swoole_server $server, string $json, array $client){
        Worker::beforePacket($server, $json, $client);
        Router::routerStartup();

        $data = json_decode($json, TRUE);
        if(!$data){
            $rep['code']  = 0;
            $rep['error'] = 'Not valid JSON';
            $server->sendto($client['address'], $client['port'], JSON($rep));
        }else{
            $retval = Router::parse($data);
            $module = $retval['module'];
            $controller = $retval['controller'];
            $action = $retval['action'];
            Router::routerShutdown();

            if($controller){
                $instance = Helper::import($module, $controller);
                $middleware_status = Response::getMiddlewareStatus();
                if($middleware_status !== FALSE){
                    if($instance !== FALSE){
                        $instance->data   = $data;
                        $instance->server = $server;
                        $instance->client = $client;

                        $retval = $instance->$action();
                        $server->sendto($client['address'], $client['port'], $retval);
                    }else{
                        $rep['code']  = 0;
                        $rep['error'] = 'Controller '.$controller.' not found';
                        $server->sendto($client['address'], $client['port'], JSON($rep));
                    }
                }else{
                    Response::endByMiddleware();
                }
            }
        }
    }

    // Websocket onOpen
    public static function onOpen(swoole_websocket_server $server, swoole_http_request $request){
        Worker::afterOpen($server, $request);
    }

    // Websocket onMessage
    public static function onMessage(swoole_websocket_server $server, swoole_websocket_frame $frame){
        Worker::beforeMessage($server, $frame);
        Router::routerStartup();
        
        $data = json_decode($frame->data, 1);
        if(!$data){
            $rep['code']  = 0;
            $rep['error'] = 'Not valid JSON';
            $server->push($frame->fd, JSON($rep));
        }else{
            $retval = Router::parse($data);
            $module = $retval['module'];
            $controller = $retval['controller'];
            $action = $retval['action'];
            Router::routerShutdown();

            if($controller){
                $instance = Helper::import($module, $controller);
                $middleware_status = Response::getMiddlewareStatus();
                if($middleware_status !== FALSE){
                    if($instance !== FALSE){
                        $instance->data   = $data;
                        $instance->server = $server;
                        $instance->fd     = $frame->fd;

                        $retval = $instance->$action();
                        $server->push($frame->fd, $retval);
                    }else{
                        $rep['code']  = 0;
                        $rep['error'] = 'Controller '.$controller.' not found';
                        $server->push($frame->fd, JSON($rep));
                    }
                }else{
                    Response::endByMiddleware();
                }
            }
        }
    }

    // onClose
    public static function onClose(swoole_server $server, int $fd, int $reactorID){
        Worker::afterClose($server, $fd, $reactorID);
    }

    // Worker error
	public static function onWorkerError(swoole_server $serv, int $workerID, int $workerPID, int $exitCode, int $signal){
		Logger::fatal('Worker '.$workerID.' exit with code '.$exitCode.' and signal '.$signal);
	}

    // Worker stop
	public static function onWorkerStop(swoole_server $server, int $workerID){
		Worker::afterStop($server, $workerID);
		Logger::log('Worker '.$workerID.' stop');
	}

}