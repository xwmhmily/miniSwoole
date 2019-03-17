<?php
/**
 * File: Controller.php
 * Author: 大眼猫
 */

abstract class Controller {

	public $fd;       // client fd
	public $server;   // tcp server object
	public $data;     // client data, array format
	public $client;   // udp client info

	public $request;  // http request
	public $response; // http response
	public $method;   // http request method: GET or POST

	// 获取参数
	protected function getParam($key, $filter = TRUE){
		if(Server::$type == Server::TYPE_HTTP){
			$method = strtolower($this->method);
			if(!$filter){
				return $this->request->$method[$key];
			}else{
				return Security::filter($this->request->$method[$key]);
			}
		}else{
			// TCP, UDP, Websocket
			if(!$filter){
				return $this->data[$key];
			}else{
				return Security::filter($this->data[$key]);
			}
		}
	}

	// Output error
	protected function error($error, $errorCode = 500){
		$rep['code'] = $errorCode;
		if(ENV == 'DEV'){
			$rep['error'] = $error->getMessage();
			$rep['trace'] = $error->getTraceAsString();
			$rep['debug'] = debug_print_backtrace();
		}else{
			$rep['error'] = 'Internal Server Error';
		}

		$rep = JSON($rep);
		if(Server::$type == Server::TYPE_HTTP){
			$this->httpStatus($errorCode);
			$this->response->write($rep);
			$this->response->end();
		}else{
			$this->response($rep);
		}
	}

	// Http debug ouput
	protected function debug($tip, $data = NULL){
		$debug = $this->getParam('debug');
		if($debug){
			$this->response->write('__'.$tip.'__<br />');
			if(!is_array($data)){
				$this->response->write($data.'<br /><br />');
			}else{
				$this->response->write(JSON($data).'<br /><br />');
			}
		}
	}

	// HTTP header
	protected function httpHeader(string $key = 'Content-Type', string $value = 'text/html; charset=utf-8'){
		return $this->response->header($key, $value);
	}

	// HTTP cookie
	protected function httpCookie(string $key, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = FALSE, bool $httpOnly = FALSE){
		return $this->response->cookie($key, $value, $expire, $path, $domain, $secure, $httpOnly);
	}

	// HTTP status
	protected function httpStatus(int $statusCode){
		return $this->response->status($statusCode);
	}

	// HTTP gzip
	protected function httpGzip(int $level = 1){
		return $this->response->gzip($level);
	}

	// 加载模型
	protected function load($model){
		return Helper::load($model);
	}

	// TCP/UDP/Web Socket 输出数据给客户端
	protected function response($data){
		switch (Server::$type) {
			case Server::TYPE_TCP:
				return $this->server->send($this->fd, $data);
			break;

			case Server::TYPE_WEB_SOCKET:
				return $this->server->push($this->fd, $data);
			break;

			case Server::TYPE_UDP:
				return $this->server->sendto($this->client['address'], $this->client['port'], $data);
			break;
		}
	}

	public function __call($name, $arguments){
		$rep['code']  = 0;
		$rep['error'] = 'Method '.$name.' not found';
		$rep = JSON($rep);

		switch (Server::$type) {
			case Server::TYPE_HTTP:
				return $this->response->end($rep);
			break;

			case Server::TYPE_TCP:
				return $this->server->send($this->fd, $rep);
			break;

			case Server::TYPE_WEB_SOCKET:
				return $this->server->push($this->fd, $rep);
			break;

			case Server::TYPE_UDP:
				return $this->server->sendto($this->client['address'], $this->client['port'], $rep);
			break;
		}
	}
	
}