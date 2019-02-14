<?php

class C_Udp extends Controller {

	private $m_user;
	private $m_news;
    
    function __construct(){
    	$this->m_user = $this->load('User');
        $this->m_news = $this->load('News');
    }

    // 测试onError事件
    // 为了避免由于exception, error 导致worker 退出后客户端一直收不回复的问题, 使用 try...catch(Throwable) 来处理
	public function onError(){
		try{
			$result = $this->m_player->SelectOne();
			$this->response('Result is => '.$result);
		}catch (Throwable $e){
			$this->error($e->getMessage());
		}
	}

    // 使用 timer 定时 ping mysql
    public function ping(){
        $this->response('PONG');
    }

    public function all(){
        try{
            $users = $this->m_user->SelectAll();
            $this->response(JSON($users));

            $news = $this->m_news->Select();
            $this->response(JSON($news));

            $one_news = $this->m_news->SelectOne();
            $this->response(JSON($one_news));
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // Mix common sql and transaction test
    public function mix(){
        try{
            $users = $this->m_user->SelectAll();
            $this->response(JSON($users));

            $news = $this->m_news->Select();
            $this->response(JSON($news));

            $one_news = $this->m_news->SelectOne();
            $this->response(JSON($one_news));

            $this->response(PHP_EOL.'=============HERE IS TRANSACTION============='.PHP_EOL);

            $this->m_user->BeginTransaction();
            $users = $this->m_user->SelectAll();
            $news = $this->m_news->Select();

            if($users && $news){
                $this->m_news->Commit();
                $this->response(JSON($users));
                $this->response(JSON($news));
            }else{
                $this->m_news->Rollback();
                $this->response('ERRORRRRRRRRR');
            }
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // Transaction
    public function transaction(){
        try{
            $this->m_user->BeginTransaction();
            $user = $this->m_user->SetDB('MASTER')->SelectOne();
            $news = $this->m_news->Select();

            if($user && $news){
                $this->m_news->Commit();
                $this->response('Master => '.JSON($user));
                $this->response('Master => '.JSON($news));
            }else{
                $this->m_news->Rollback();
                $this->response('ERRORRRRRRRRR');
            }

            $field = ['id', 'username', 'platform', 'device', 'addTime'];
            $where = ['id' => 2];
            $user = $this->m_user->SetDB('SLAVE')->Field($field)->Where($where)->SelectOne();
            $this->response('Slave => '.JSON($user));

            $field = ['id', 'mobile', 'summary', 'address'];
            $where = ['companyID' => 38];
            $order = ['id' => 'DESC'];
            $customer = $this->load('Customer')->SetDB('SLAVE')->Suffix(38)->Field($field)->Where($where)->Order($order)->Limit(10)->Select();
            $this->response('Slave with suffix => '.JSON($customer));
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // Test process
    public function process(){
        $process = new swoole_process(function (swoole_process $process) {
            $process->name("Mini_Swoole_process") && $process->daemon(1);

            $i = 1;
            $max = 100;
            while($i <= $max){
                Logger::log($i.' Process is running .....');
                $i++; sleep(1);
            }
        }, 0);

        $process->start();
        $this->response->end('Process is running ......');
    }

    // Client info
    public function client(){
        $this->response(JSON($this->server->getClientInfo($this->fd)));
    }

    // Security
    public function security(){
        $this->response(JSON($this->data));
    }

    // Autoload
    public function rabbit(){
        try{
            $rabbit = new RabbitMQ();
            $this->response('A Rabbit is running happily now');
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }
    
    // Task 
    public function Task(){
        // Task
        $args = [];
        $args['controller']   = 'user';
        $args['action']       = 'myTask';
        $args['data']['line'] = __LINE__;
        Task::add($args);

        // Timer for test
        Timer::add(2000, [$this, 'tick'], [__LINE__, Server::$type]);

        // After timer
        Timer::after(3000, [$this, 'after'], __METHOD__);

        $this->response('DONE');
    }

    public function myTask($args){
        $url = 'http://www.baidu.com';
        $content = file_get_contents($url);
        Logger::log($content);

        /*
        while(1){
            Logger::log(__METHOD__);
            Logger::log(JSON($args));
            $news = $this->m_news->SelectOne();
            Logger::log(JSON($news));
            sleep(3);
        }
        */
    }

    public function tick(int $timerID, $args){
        $this->response('Args in tick '.JSON($args));
    }

    // Timer after for test
    public function after(){
        Logger::log('Execute '.__METHOD__.' in after timer');
        $news = $this->m_news->SelectOne();
        Logger::log(JSON($news));
    }

    // 测试 MySQL 自动断线重连及压测
    public function reconnect(){
        try{
            $i = 1;
            $max = 1000;
            while($i <= $max){
                $news = $this->m_news->SelectOne();
                if(!$news){
                    $news = 'Stop reconnecting';
                    Logger::log($news);
                    $retval = $this->response($news);
                    break;
                }else{
                    $news = JSON($news);
                }

                $retval = $this->response($i.' => '.$news.PHP_EOL);
                if(!$retval){
                    break;
                }

                $where  = ['id' => 2];
                $news   = $this->m_news->Where($where)->SelectOne();
                $retval = $this->response('Another '.JSON($news).PHP_EOL);

                $u = [];
                $u['remark'] = $i;
                $news = $this->m_news->Where($where)->UpdateOne($u);

                /*
                $this->m_user->BeginTransaction();
                $users = $this->m_user->SelectAll();
                $news = $this->m_news->Select();

                if($users && $news){
                    $this->m_news->Commit();
                    $this->response('Transaction => '.JSON($users).PHP_EOL);
                    $this->response('Transaction => '.JSON($news).PHP_EOL);
                }else{
                    $this->m_news->Rollback();
                    $this->response('ERRORRRRRRRRR');
                }
                */

                $i++; sleep(1);
            }
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // MySQL slave
    public function slave(){
        try{
            $m_user = $this->load('User');

            $i = 1;
            while($i <= 100){
                $user = $m_user->SetDB('SLAVE')->SelectOne();
                $this->response('Slave => '.JSON($user));

                $user = $m_user->SetDB('MASTER')->SelectOne();
                $this->response('Master => '.JSON($user));

                $field = ['id', 'username', 'sex', 'realname', 'companyName'];
                $where = ['id' => 2];
                $user = $m_user->SetDB('SLAVE')->Field($field)->Where($where)->SelectOne();
                $this->response('Slave again => '.JSON($user));

                $field = ['id', 'username', 'sex', 'realname', 'companyName'];
                $user = $m_user->SetDB('SLAVE')->SelectByID($field, 2);
                $this->response('Slave by ID => '.JSON($user));

                $field = ['id', 'mobile', 'companyID', 'poolTime'];
                $customer = $this->load('Customer')->SetDB('SLAVE')->Field($field)->Suffix(38)->SelectOne();
                $this->response('Slave with suffix => '.JSON($customer));

                $i++; sleep(1);
            }
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // 测试SQL 报错
    public function user(){
        try{
            $field = ['id', 'usernamex'];
            $order = ['id' => 'DESC'];
            $users = $this->m_user->Field($field)->Order($order)->Select();
            if(!$users){
                $this->response('NO USERS FOUND');
            }else{
                $this->response(JSON($users));
            }

            $users = $this->m_user->SelectAll();
            $this->response(JSON($users));

            $user = $this->m_user->SelectByID('', 1);
            $this->response(JSON($user));
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // Suffix
    public function suffix(){
        try{
            $customer = $this->load('Customer')->SetDB('SLAVE')->Suffix(38)->ClearSuffix()->Suffix(52)->SelectOne();
            $this->response('Suffix Customer => '.JSON($customer));
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // Redis and MySQL with Master / slave
    public function connector(){
        try{
            for($i = 1; $i <= 100; $i++){
                $this->response('======================='.$i.'======================='.PHP_EOL);

                // Master
                $news = $this->m_news->Select();
                $this->response(' Master => '.JSON($news));

                $users = $this->m_user->SetDB('MASTER')->SelectAll();
                $this->response(' Master => '.JSON($users));

                // Slave
                $admins = $this->load('Admin')->SetDB('SLAVE')->Select();
                $this->response(' Slave => '.JSON($admins));

                // Slave
                $city = $this->load('City')->SetDB('SLAVE')->SelectOne();
                $this->response(' Slave => '.JSON($city));

                // Master
                $user = $this->m_user->SelectByID('', 2);
                $this->response(' Master => '.JSON($user));

                // Slave
                $company = $this->load('Company')->SetDB('SLAVE')->SelectOne();
                $this->response(' Slave => '.JSON($company));

                $key = $this->getParam('key');
                $val = Cache::get($key);
                $this->response(' Redis => '.$val);

                // Suffix
                $customer = $this->load('Customer')->SetDB('SLAVE')->Suffix(38)->SelectOne();
                $this->response(' Suffix Customer => '.JSON($customer));

                // What if errors occur
                $customer = $this->load('Customer')->SetDB('SLAVE')->Suffix(52)->SelectOne();
                $this->response(' Suffix Customer => '.JSON($customer));

                // Master
                $user = $this->m_user->SelectByID('', 1);
                $this->response(' Master => '.JSON($user));

                // Change Master to Slave, just call the SetDB()
                $user = $this->m_user->SetDB('SLAVE')->SelectByID('', 1);
                $this->response(' Slave => '.JSON($user));

                $this->response(PHP_EOL.'======================='.$i.'======================='.PHP_EOL);

                sleep(1);
            }
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }

    // Redis
    public function redis(){
        try{
            $key = $this->getParam('key');
            $this->response($key);
            
            if($key){
                while(1){
                    $val = Cache::get($key);
                    $this->response(date('Y-m-d H:i:s'). ' => '.$val);
                    sleep(1);
                }
            }else{
                $this->response('Key is required !');
            }
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }
}