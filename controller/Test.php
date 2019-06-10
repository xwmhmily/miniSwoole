<?php

class C_Test extends Controller {

	private $m_user;
	private $m_news;
    
    function __construct(){
        // 测试 http 中间件
        $this->http_middleware(['Auth', 'Importer']);
    	$this->m_user = $this->load('User');
        $this->m_news = $this->load('News');
    }

    public function log(){
        try{
            Logger::debug('This is a debug msg');
            Logger::info('This is an info msg');
            Logger::warn('This is a warn msg');
            Logger::error('This is an error msg');
            Logger::fatal('This is a fatal msg');
            Logger::log('This is a log msg');

            $level = Config::get('common', 'error_level');
            $this->response->write('Current error_level is => '.$level);
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Get all users
    public function users(){
        try{
            $users = $this->m_user->SelectAll();
            $this->response->write(JSON($users));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Auto pagination
    public function news(){
        try{
            $news = $this->m_news->Limit()->Select();
            $this->response->write(JSON($news));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // SelectAll
    public function all(){
        try{
            $users = $this->m_user->SelectAll();
            $this->response->write(JSON($users));

            $news = $this->m_news->Select();
            $this->response->write(JSON($news));

            $one_news = $this->m_news->SelectOne();
            $this->response->write(JSON($one_news));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function pagination(){
        $news = $this->m_news->Select();
        $this->response->write('ALL => '.sizeof($news));
        $this->response->write('<br />============================<br />');

        $news = $this->m_news->Limit()->Select();
        $this->response->write('Limit 10 => '.sizeof($news));
        $this->response->write('<br />============================<br />');

        $news = $this->m_news->Limit(20)->Select();
        $this->response->write('Limit 20 => '.sizeof($news));
        $this->response->write('<br />============================<br />');
    }
    
}