<?php
/**
 * Index Controller
 */

class C_Index extends Controller {

    // http index 就写这里
    // URL: http://192.168.1.31:9502/?hello=world
    public function index(){
        $this->httpHeader();
        $this->response->end('Welcome to '.APP_NAME.' http server');
    }
}