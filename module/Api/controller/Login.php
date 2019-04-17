<?php

class C_Login extends Controller {

	private $m_user;
    
    function __construct(){
    	$this->m_user = $this->load('User');
    }

    // http demo
    public function index(){
        try{
            $captcha  = $this->getParam('captcha');
            $username = $this->getParam('username');
            $password = $this->getParam('password');

            $this->response->write('Username => '.$username.'<br />');
            $this->response->write('Password => '.$password.'<br />');
            $this->response->write('Captcha => '.$captcha.'<br />');
            $this->response->end();
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // tcp / udp / websocket demo
    public function go(){
        try{
            $captcha  = $this->getParam('captcha');
            $username = $this->getParam('username');
            $password = $this->getParam('password');

            $this->response('Username => '.$username.PHP_EOL);
            $this->response('Password => '.$password.PHP_EOL);
            $this->response('Captcha => '.$captcha.PHP_EOL);
        }catch (Throwable $e){
			$this->error($e);
		}
    }

}