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
		if(Server::getServerType() == Server::TYPE_HTTP){
			$method = strtolower($this->method);
			if(isset($this->request->$method[$key])){
				$value = $this->request->$method[$key];
			}else{
				$value = NULL;
			}
		}else{
			// TCP, UDP, Websocket
			if(isset($this->data[$key])){
				$value = $this->data[$key];
			}else{
				$value = NULL;
			}
		}

		if($filter){
			$value = Security::filter($value);
		}

		return $value;
	}

	// Output error
	protected function error($error, $errorCode = 500){
		if(ENV == 'DEV'){
			$trace = $error->getTrace();
			$last_error = Logger::getLastError();
			if(Server::getServerType() == Server::TYPE_HTTP){
				$this->httpStatus($errorCode);
				$error  = $this->importStatic();
				$error .= $this->initStatic();
				$error .= $this->initHtml($last_error['errorStr']);
				$error = $this->generalError($last_error, $error);
				$error = $this->traceError($trace, $error);
				$error = $this->configError($error);
				$error = $this->getError($error);
				$error = $this->postError($error);
				$error = $this->cookieError($error);
				$error = $this->serverError($error);
				$error = $this->sqlError($last_error, $error);

				$this->response->write($error);
				$this->response->end();
			}else{
				$this->response(JSON($last_error));
			}
		}else{
			if(Server::getServerType() == Server::TYPE_HTTP){
				$html = '<html>
					<head><title>500 Internal Server Error</title></head>
					<body bgcolor="white">
					<center><h1>500 Internal Server Error</h1></center>
					<hr>
					</body>
				</html>';
				$this->response->status($errorCode);
				$this->response->write($html);
				$this->response->end();
			}else{
				$this->response('Internal Server Error'.PHP_EOL);
			}
		}
	}

	private function initStatic(){
		$html = '<style>
				body{
					font-family:"ff-tisa-web-pro-1","ff-tisa-web-pro-2","Lucida Grande","Helvetica Neue",Helvetica,Arial,"Hiragino Sans GB","Hiragino Sans GB W3","Microsoft YaHei UI","Microsoft YaHei","WenQuanYi Micro Hei",sans-serif;
					padding: 10px;
				}
				</style>';
		$html .= "<script> 
				$(function(){
					$('#errorTab a').click(function(e){
						e.preventDefault();
						$('#errorTab a').parent().removeClass('active'); 
						$(this).parent().addClass('active');

						$('.tab-content div').removeClass('active');
						var id = $(this).attr('val');
						$('#'+id).addClass('active');
					}) 
				}) 
				</script>";
		return $html;
	}

	private function initHtml($errorString){
		$html = '<h4>Error : '.$errorString.'</h4>
					<ul class="nav nav-tabs" id="errorTab"> 
					<li class="active"><a val="general" href="#general">General</a></li> 
					<li><a val="trace" href="#trace">Trace</a></li>
					<li><a val="config" href="#config">Config</a></li>
					<li><a val="get" href="#get">GET</a></li>
					<li><a val="post" href="#post">POST</a></li> 
					<li><a val="cookie" href="#cookie">COOKIE</a></li>  
					<li><a val="server" href="#server">SERVER</a></li>
					<li><a val="sql" href="#sql">SQL</a></li>
					</ul>';
		$html .= '<div class="tab-content">
					<div class="tab-pane active" id="general">[GENERAL_ERR]</div> 
					<div class="tab-pane" id="config">[CONFIG_ERR]</div>
					<div class="tab-pane" id="trace">[TRACE_ERR]</div>
					<div class="tab-pane" id="get">[GET_ERR]</div>
					<div class="tab-pane" id="post">[POST_ERR]</div>
					<div class="tab-pane" id="cookie">[COOKIE_ERR]</div>
					<div class="tab-pane" id="server">[SERVER_ERR]</div>
					<div class="tab-pane" id="sql">[SQL_ERR]</div>
					</div>';
		return $html;
	}

	private function importStatic(){
		$html = '<link href="/css/bootstrap.min.css" rel="stylesheet">
		<link href="/css/bootstrap-responsive.min.css" rel="stylesheet">
		<link href="/css/docs.css" rel="stylesheet">
		<script src="/js/jquery-1.7.min.js"></script>
		<link href="/css/prettify.css" rel="stylesheet">';
		return $html;
	}

	private function sqlError($last_error, $error){
		$sqlError = '<ul>';
		if ($last_error['errorSQL']) {
			$sqlError .= '<li>' . $last_error['errorSQL'] . '</li>';
		}
		$sqlError .= '</ul>';
		return str_replace('[SQL_ERR]', $sqlError, $error);
	}

	private function serverError($error){
		$serverError = '<ul>';
		foreach ($this->request->server as $key => $val) {
			$serverError .= '<li>' . $key . ' => ' . var_export($val, TRUE) . '</li>';
		}
		$serverError .= '</ul>';
		return str_replace('[SERVER_ERR]', $serverError, $error);
	}

	private function cookieError($error){
		$cookieError = '<ul>';
		if(isset($this->request->cookie)){
			foreach ($this->request->cookie as $key => $val) {
				$cookieError .= '<li>' . $key . ' => ' . $val . '</li>';
			}
		}
		$cookieError .= '</ul>';
		return str_replace('[COOKIE_ERR]', $cookieError, $error);
	}

	private function postError($error){
		$postError = '<ul>';
		if($this->method == Request::HTTP_METHOD_POST && isset($this->request->post)){
			foreach ($this->request->post as $key => $val) {
				$postError .= '<li>' . $key . ' => ' . $val . '</li>';
			}
		}
		$postError .= '</ul>';
		return str_replace('[POST_ERR]', $postError, $error);
	}

	private function getError($error){
		$getError = '<ul>';
		if($this->method == Request::HTTP_METHOD_GET && isset($this->request->get)){
			foreach ($this->request->get as $key => $val) {
				$getError .= '<li>' . $key . ' => ' . $val . '</li>';
			}
		}
		$getError .= '</ul>';
		return str_replace('[GET_ERR]', $getError, $error);
	}

	private function configError($error){
		$config = Config::get();
		$configError = '<ul>';
		foreach ($config as $key => $val) {
			$configError .= '<li>' . $key . ' => ' . var_export($val, TRUE) . '</li>';
		}
		$configError .= '</ul>';
		return str_replace('[CONFIG_ERR]', $configError, $error);
	}

	private function traceError($trace, $error){
		$traceError = '<ul>';
		foreach ($trace as $val) {
			foreach ($val as $k => $v) {
				if($k != 'type' && $k != 'args'){
					$traceError .= '<li>' . $k . ' => '.var_export($v, TRUE).'</li>';
				}
			}
			$traceError .= '<hr />';
		}
		$traceError .= '</ul>';
		return str_replace('[TRACE_ERR]', $traceError, $error);
	}

	private function generalError($last_error, $error){
		$generalError = '<ul>';
		$generalError .= '<li>APP: '.APP_NAME.'</li>';
		$generalError .= '<li>Environ: ' . ENV . '</li>';
		$generalError .= '<li>Error NO: ' . $last_error['errorNO'] . '</li>';
		$generalError .= '<li>Error: ' . $last_error['errorStr'] . '</li>';
		$generalError .= '<li>File: ' . $last_error['errorFile'] . '</li>';
		$generalError .= '<li>Line: ' . $last_error['errorLine'] . '</li>';
		$generalError .= '</ul>';
		return str_replace('[GENERAL_ERR]', $generalError, $error);
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

	// 中间件
	protected function middleware($middleware){
		try{
			(new Pipeline)->send()->through($middleware)->via('handle')->then(function(){
				Response::setMiddlewareStatus(TRUE);
			});
		}catch (Throwable $e){
			Response::setMiddlewareStatus(FALSE);

			$error = [];
			$error['code']  = $e->getCode();
			$error['error'] = $e->getMessage();
			Response::setMiddlewareError(JSON($error));
			return Response::endByMiddleware();
		}
	}

	// 加载模型
	protected function load($model){
		return Helper::load($model);
	}

	// TCP/UDP/Web Socket 输出数据给客户端
	protected function response($data){
		switch (Server::getServerType()) {
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

		switch (Server::getServerType()) {
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