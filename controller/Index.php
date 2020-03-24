<?php
/**
 * Index Controller
 */

class C_Index extends Controller {

    // http index 就写这里
    public function index(){
        try{
            return 'Welcome to '.APP_NAME.' http server';
        }catch (Throwable $e){
			$this->error($e);
		}
    }
}