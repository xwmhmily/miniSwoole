<?php

class C_User extends Controller {

	private $m_user;
    
    function __construct(){
    	$this->m_user = $this->load('User');
    }

    // Profile
    // URL: http://127.0.0.1:9100/api/user
    public function index(){
        try{
            $user = [];
            $user['team'] = 'Lakers';
            $user['username'] = 'Kobe';

            $rep['code'] = 1;
            $rep['data']['user'] = $user;
            $this->response->end(JSON($rep));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

}