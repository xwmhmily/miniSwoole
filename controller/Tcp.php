<?php

class C_Tcp extends Controller {

	private $m_user;
	private $m_news;
    
    function __construct(){
    	$this->m_user = $this->load('User');
        $this->m_news = $this->load('News');
    }

    // 测试onError事件
    // 为了避免由于exception, error 导致worker 退出后客户端一直收不回复的问题
    // 使用 try...catch(Throwable) 来处理
	public function onError(){
		try{
			$result = $this->m_player->SelectOne();
			$this->response('Result is => '.$result);
		}catch (Throwable $e){
			$this->error($e);
		}
	}

    // Pong
    public function ping(){
        try{
            $this->response('PONG');
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Get all users
    public function users(){
        try{
            $users = $this->m_user->SelectAll();
            $this->response(JSON($users));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Get all news
    public function news(){
        try{
            $news = $this->m_news->Select();
            $this->response(JSON($news));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // MySQL 压力测试
    public function stress(){
        try{
            $max = 100000;
            $start_time = Logger::getMicrotime();
            for($i = 1; $i <= $max; $i++){
                $news = $this->m_news->Select();
            }
            $end_time = Logger::getMicrotime();
            $cost = $end_time - $start_time;
            $this->response('Time => '.$cost.', TPS => '.$max/$cost);
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // tcp SelectAll
    public function all(){
        try{
            $users = $this->m_user->SelectAll();
            $this->response(JSON($users));

            $news = $this->m_news->Select();
            $this->response(JSON($news));

            $one_news = $this->m_news->SelectOne();
            $this->response(JSON($one_news));
        }catch (Throwable $e){
			$this->error($e);
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
			$this->error($e);
		}
    }

    // Transaction
    public function transaction(){
        try{
            $this->m_user->SetDB('MASTER')->BeginTransaction();
            $user = $this->m_user->SelectOne();
            $news = $this->m_news->Select();

            if($user && $news){
                $this->m_news->Commit();
                $this->response('Master => '.JSON($user));
                $this->response('Master => '.JSON($news));
            }else{
                $this->m_news->Rollback();
                $this->response('ERRORRRRRRRRR');
            }

            $field = ['id', 'username', 'password'];
            $where = ['id' => 2];
            $user = $this->m_user->SetDB('SLAVE')->Field($field)->Where($where)->SelectOne();
            $this->response('Slave => '.JSON($user));

            $where = ['status' => 1];
            $order = ['id' => 'DESC'];
            $user = $this->m_user->SetDB('SLAVE')->Suffix(38)->Field($field)->Where($where)->Order($order)->Limit(10)->Select();
            $this->response('Slave with suffix => '.JSON($user));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Security
    public function security(){
        try{
            $this->response(JSON($this->data));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Autoload
    public function rabbit(){
        try{
            $rabbit = new RabbitMQ();
            $this->response('A Rabbit is running happily now');
        }catch (Throwable $e){
			$this->error($e);
		}
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

                $i++; sleep(1);
            }
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // MySQL slave
    public function slave(){
        try{
            $m_user = $this->load('User');

            $i = 1;
            while($i <= 100){
                $user = $m_user->SetDB('SLAVE')->SelectOne();
                $this->response('Slave first => '.JSON($user));

                $user = $m_user->SetDB('MASTER')->SelectOne();
                $this->response('Master => '.JSON($user));

                $field = ['id', 'username'];
                $where = ['id' => 2];
                $user = $m_user->SetDB('SLAVE')->Field($field)->Where($where)->SelectOne();
                $this->response('Slave again => '.JSON($user));

                $field = ['id', 'username'];
                $user = $m_user->SetDB('SLAVE')->SelectByID($field, 2);
                $this->response('Slave by ID => '.JSON($user));

                $field = ['id', 'username', 'password'];
                $user = $this->load('User')->SetDB('SLAVE')->Field($field)->Suffix(38)->SelectOne();
                $this->response('Slave with suffix => '.JSON($user));

                $i++; sleep(1);
            }
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // 测试SQL 报错
    public function sql(){
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
			$this->error($e);
		}
    }

    // Suffix
    public function suffix(){
        try{
            $user = $this->load('User')->Suffix(38)->ClearSuffix()->Suffix(52)->SelectOne();
            $this->response(' Suffix user => '.JSON($user));
        }catch (Throwable $e){
			$this->error($e);
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

                // Master
                $user = $this->m_user->SelectByID('', 2);
                $this->response(' Master => '.JSON($user));

                $key = $this->getParam('key');
                $val = Cache::get($key);
                $this->response(' Redis => '.$val);

                // Suffix
                $user = $this->load('User')->SetDB('SLAVE')->Suffix(38)->SelectOne();
                $this->response(' Suffix user => '.JSON($user));

                // What if errors occur
                $user = $this->load('User')->SetDB('SLAVE')->Suffix(52)->SelectOne();
                $this->response(' Suffix user => '.JSON($user));

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
			$this->error($e);
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
			$this->error($e);
		}
    }
}