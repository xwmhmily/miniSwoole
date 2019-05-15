<?php

class C_Stat extends Controller{

    public function index(){
        $stat = [];
        $stat['swoole_version'] = swoole_version();
        $stat['masterPID'] = Server::$instance->master_pid;
        $stat['server']    = Server::$type;

        $ports = array(Server::$instance->ports)[0];
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
        $stat['stats']   = Server::$instance->stats();
        $stat['setting'] = Server::$instance->setting;
        $this->response->write(JSON($stat));
        $this->response->end();
    }

    public function ping(){
        $this->response->end('PONG');
    }

    public function process(){
        $cmd = "ps -ef | grep Mini_Swoole | grep -v grep | awk -F ' ' '{print $2\"-\"$8}'";
        exec($cmd, $retval, $execState);
        $this->response->end(JSON($retval));
    }
}