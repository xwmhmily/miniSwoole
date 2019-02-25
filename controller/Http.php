<?php

class C_Http extends Controller {

	private $m_user;
	private $m_news;
    
    function __construct(){
    	$this->m_user = $this->load('User');
        $this->m_news = $this->load('News');
    }

    // 测试onError事件
	public function onError(){
        // 使用 try... catch(Throwable) 来避免 worker 退出
		try{
			$result = $this->m_player->SelectOne();
			$this->response->end('Result is => '.$result);
		}catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }
    
    // Insert && Update
    public function demo(){
        $this->httpHeader();

        try{
            // Insert new user
            $i = [];
            $i['username'] = 'Carryf';
            $i['password'] = md5('Carryf');
            $userID = $this->m_user->Insert($i);

            $this->response->write('userID => '.$userID.'<br />');

            // Update
            $u = [];
            $u['username'] = 'IAMCARRY';
            $retval = $this->m_user->UpdateByID($u, $userID);

            // Select
            $user = $this->m_user->SelectByID('', $userID);
            $this->response->write('user => '.JSON($user).'<br />');

            $this->response->end();
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    public function index(){
        try{
            $this->response->write(date('Y-m-d H:i:s'));

            // Task
            $args = [];
            $args['controller']   = 'http';
            $args['action']       = 'myTask';
            $args['data']['line'] = __LINE__;
            Task::add($args);

            $this->response->write('Task is running');
            $this->response->end();
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    public function myTask($args){
        $url = 'http://www.baidu.com';
        $content = file_get_contents($url);
        Logger::log($content);
    }

    // Get all news
    public function users(){
        $this->httpHeader();
        try{
            $users = $this->m_user->SelectAll();
            $this->response->end(JSON($users));
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // Post
    public function post(){
        $key = $this->getParam('key');
        $this->response->end('Your key is '.$key);
    }

    // Get all news
    public function news(){
        $this->httpHeader();
        try{
            $news = $this->m_news->Select();
            $this->response->end(JSON($news));
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // login 及参数过滤
    public function login(){
        try{
            $username = $this->getParam('username');
            $password = $this->getParam('password');

            $this->response->write('Username => '.$username.', password => '.$password);

            $username = $this->getParam('username', FALSE);
            $this->response->write('<br />Username => '.$username.', password => '.$password);

            $this->response->end('__DONE__');
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // Test process
    public function process(){
        $process = new swoole_process(function (swoole_process $process) {
            $process->name("Tiny_Swoole_process") && $process->daemon(1);

            // 创建Process自身的连接池
            Pool::createMySQLConnectionPool();

            $i = 1;
            $max = 100;
            while($i <= $max){
                Logger::log($i.' Process is running .....');
                $i++; sleep(1);
            }
        }, 0);

        // Process 里要使用 MySQL 连接池并且共用 Model 的使用方式, 则在 start 前干掉当前的 MySQL 连接, 否则 Process 将继承这些连接, 导致 Worker 中调用报: MySQL has gone away
        Pool::destroy(Pool::TYPE_MYSQL);

        $process->start();

        // Process 里则调用Pool::createMySQLConnectionPool()创建 Process 自身的连接池, Worker也是调用该方便再重新创建即可
        Pool::createMySQLConnectionPool();

        $this->response->end('Process is running ......');
    }

    // Suffix
    public function suffix(){
        $this->httpHeader();
        try{
            $customer = $this->load('Customer')->SetDB('SLAVE')->Suffix(38)->ClearSuffix()->Suffix(52)->SelectOne();
            $this->response->end(' Suffix Customer => '.JSON($customer));
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // Redis
    public function redis(){
        $this->httpHeader();
        try{
            $key = $this->getParam('key');
            
            if($key){
                $this->response->write('Key is '.$key);
                while(1){
                    $val = Cache::get($key);
                    $this->response->write(date('Y-m-d H:i:s'). ' => '.$val);
                    sleep(1);
                }
            }else{
                $this->response->end('Key is required !');
            }
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }
}