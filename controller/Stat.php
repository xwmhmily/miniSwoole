<?php

class C_Stat extends Controller{

    public function index(){
        try{
            $stat['app']    = APP_NAME;
            $stat['server'] = Server::getType();
            $stat['php_version']    = phpversion();
            $stat['swoole_version'] = swoole_version();
            $stat['masterPID'] = Server::getInstance()->master_pid;

            $ports = array(Server::getInstance()->ports)[0];
            $ports_arr = [];
            foreach($ports as $port){
                $port = array($port);
                foreach($port as $p){
                    $p = array($p)[0];
                    unset($p->setting);
                    $ports_arr[] = $p;
                }
            }

            $stat['ports']   = $ports_arr;
            $stat['config']  = Config::get();
            $stat['stats']   = Server::getInstance()->stats();
            $stat['setting'] = Server::getInstance()->setting;
            $this->response->write(JSON($stat));
            $this->response->end();
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function ping(){
        try{
            $this->response->end('PONG');
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function process(){
        try{
            $cmd = "ps -ef | grep Mini_Swoole | grep -v grep | awk -F ' ' '{print $2\"-\"$8}'";
            exec($cmd, $retval, $execState);
            $this->response->end(JSON($retval));
        }catch (Throwable $e){
			$this->error($e);
		}
    }
}