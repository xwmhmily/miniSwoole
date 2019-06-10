<?php

class C_User extends Controller {

	private $m_user;
    
    function __construct(){
        // 本控制器需要登录验证，我们就用中间件的 Auth, Importer 来处理
        $this->middleware(['Auth', 'Importer']);
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

    // Upload
    public function upload(){
        try{
            $file = $this->getParam('file');
            Logger::log('File is => '.$file);
            $this->response->end('File is => '.$file);
        }catch (Throwable $e){
			$this->error($e);
		}
    }

}