<?php

class C_Http extends Controller {

	private $m_user;
	private $m_news;
    
    function __construct(){
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

    // 测试onError事件
    // 为了避免由于exception, error 导致worker 退出后客户端一直收不回复的问题
    // 使用 try...catch(Throwable) 来处理
	public function onError(){
		try{
			$result = $this->m_player->SelectOne();
			$this->response->write('Result is => '.$result);
		}catch (Throwable $e){
			$this->error($e);
		}
	}

    // Ping and Pong
    public function ping(){
        try{
            $this->response->write('PONG');
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // get Config with key
    public function configAndKey(){
        try{
            $redis_config = Config::get('redis');
            $this->response->write(JSON($redis_config).'<br />');

            $redis_host = Config::get('redis', 'host');
            $this->response->write(JSON('Host is '.$redis_host).'<br />');

            $redis_port = Config::get('redis', 'port');
            $this->response->write(JSON('Port is '.$redis_port));
            $this->response->write(JSON($redis_config));
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
            $this->response->write('Time => '.$cost.', TPS => '.$max/$cost);
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

    // Mix common sql and transaction test
    public function mix(){
        try{
            $users = $this->m_user->SelectAll();
            $this->response->write(JSON($users));

            $news = $this->m_news->Select();
            $this->response->write(JSON($news));

            $one_news = $this->m_news->SelectOne();
            $this->response->write(JSON($one_news));

            $this->response->write(PHP_EOL.'======= HERE IS TRANSACTION ========='.PHP_EOL);

            $this->m_user->BeginTransaction();
            $users = $this->m_user->SelectAll();
            $news = $this->m_news->Select();

            if($users && $news){
                $this->m_news->Commit();
                $this->response->write(JSON($users));
                $this->response->write(JSON($news));
            }else{
                $this->m_news->Rollback();
                $this->response->write('ERRORRRRRRRRRRRRRR');
            }
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Transaction
    public function transaction(){
        try{
            $this->m_user->BeginTransaction();
            $user = $this->m_user->SelectOne();
            $news = $this->m_news->Select();

            if($user && $news){
                $this->m_news->Commit();
                $this->response->write('Master user => '.JSON($user)."<br />");
                $this->response->write('Master news => '.JSON($news)."<br />");
            }else{
                $this->m_news->Rollback();
                $this->response->write('ERRORRRRRRRRRRRRR');
            }

            $field = ['id', 'username', 'password'];
            $where = ['id' => 2];
            $user = $this->m_user->SetDB('SLAVE')->ClearSuffix()->Field($field)->Where($where)->SelectOne();
            $this->response->write('Slave => '.JSON($user)."<br />");

            $where = ['status' => 1];
            $order = ['id' => 'DESC'];
            $user = $this->m_user->SetDB('SLAVE')->Suffix(38)->Field($field)->Where($where)->Order($order)->Limit(10)->Select();
            $this->response->write('Slave with suffix => '.JSON($user)."<br />");
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Security
    public function security(){
        try{
            $this->response->write(JSON($this->request));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Autoload
    public function rabbit(){
        try{
            $rabbit = new RabbitMQ();
            $this->response->write('A Rabbit is running happily now');
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
                    $retval = $this->response->write($news);
                    break;
                }else{
                    $news = JSON($news);
                }

                $retval = $this->response->write($i.' => '.$news.'<br />');
                if(!$retval){
                    break;
                }

                $where  = ['id' => 2];
                $news   = $this->m_news->Where($where)->SelectOne();
                $retval = $this->response->write('Another '.JSON($news).'<br />');

                $u = [];
                $u['remark'] = $i;
                $news = $this->m_news->Where($where)->UpdateOne($u);

                $i++; sleep(1);
            }
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function pool(){
        try{
            $this->response->write(JSON(Pool::$pool[Pool::TYPE_MYSQL]));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // MySQL slave
    public function slave(){
        try{
            $m_user = $this->load('User');

            $i = 1;
            while($i <= 3){
                $user = $m_user->SetDB('SLAVE')->SelectOne();
                $this->response->write('Slave first => '.JSON($user).'<br />');

                $user = $m_user->SetDB('MASTER')->SelectOne();
                $this->response->write('Master => '.JSON($user).'<br />');

                $field = ['id', 'username'];
                $where = ['id' => 2];
                $user = $m_user->SetDB('SLAVE')->Field($field)->Where($where)->SelectOne();
                $this->response->write('Slave again => '.JSON($user).'<br />');

                $field = ['id', 'username'];
                $user = $m_user->SetDB('SLAVE')->SelectByID($field, 2);
                $this->response->write('Slave by ID => '.JSON($user).'<br />');

                $field = ['id', 'username', 'password'];
                $user = $this->load('User')->SetDB('SLAVE')->Field($field)->Suffix(38)->SelectOne();
                $this->response->write('Slave with suffix => '.JSON($user).'<br />');

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
                $this->response->write('NO USERS FOUND'.'<br />');
            }else{
                $this->response->write(JSON($users).'<br />');
            }

            $users = $this->m_user->SelectAll();
            $this->response->write(JSON($users).'<br />');

            $user = $this->m_user->SelectByID('', 1);
            $this->response->write(JSON($user).'<br />');
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Suffix
    public function suffix(){
        try{
            $user = $this->load('User')->Suffix(38)->ClearSuffix()->Suffix(52)->SelectOne();
            $this->response->write('Suffix user => '.JSON($user));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Redis and MySQL with Master / slave
    public function connector(){
        try{
            for($i = 1; $i <= 100; $i++){
                $this->response->write('=============='.$i.'===================<br />');

                // Master
                $news = $this->m_news->Select();
                $this->response->write(' Master => '.JSON($news).'<br />');

                $users = $this->m_user->SetDB('MASTER')->SelectAll();
                $this->response->write(' Master => '.JSON($users).'<br />');

                // Master
                $user = $this->m_user->SelectByID('', 2);
                $this->response->write(' Master => '.JSON($user).'<br />');

                $key = $this->getParam('key');
                $val = Cache::get($key);
                $this->response->write(' Redis => '.$val.'<br />');

                // Suffix
                $user = $this->load('User')->SetDB('SLAVE')->Suffix(38)->SelectOne();
                $this->response->write(' Suffix user => '.JSON($user).'<br />');

                // What if errors occur
                $user = $this->load('User')->SetDB('SLAVE')->Suffix(52)->SelectOne();
                $this->response->write(' Suffix user => '.JSON($user).'<br />');

                // Master
                $user = $this->m_user->SelectByID('', 1);
                $this->response->write(' Master => '.JSON($user).'<br />');

                // Change Master to Slave, just call the SetDB()
                $user = $this->m_user->SetDB('SLAVE')->SelectByID('', 1);
                $this->response->write(' Slave => '.JSON($user).'<br />');

                $this->response->write(PHP_EOL.'==============='.$i.'============'.'<br />');

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
            $this->response->write('Key => '.$key.'<br />');
            
            if($key){
                $i = 1;
                while($i < 10){
                    $val = Cache::get($key);
                    $this->response->write(date('Y-m-d H:i:s'). ' => '.$val.'<br />');
                    $i++; sleep(1);
                }
            }else{
                $this->response->write('Key is required !');
            }
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function QueryOne(){
        try{
            $username = $this->getParam('username');
            $user = $this->m_user->getUserByUsername($username);
            $this->response->write(JSON($user));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function multiInsert(){
        try{
            $user = $users = [];

            $user['username'] = 'Kobe';
            $user['password'] = md5('Lakers');
            $user['status']   = 1;
            $users[] = $user;

            $user['username'] = 'Curry';
            $user['password'] = md5('Warriors');
            $user['status']   = 1;
            $users[] = $user;

            $user['username'] = 'Thompson';
            $user['password'] = md5('Warriors');
            $user['status']   = 1;
            $users[] = $user;

            $retval = $this->m_user->multiInsert($users);
            $this->response->write($retval);
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function timer(){
        try{
            $timerID = Timer::add(2000, [$this, 'tick'], ['xyz', 'abc', '123']);
            $this->response->write('Timer has been set, id is => '.$timerID);
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function tick($timerID, $args){
        try{
            Logger::log('Args in '.__METHOD__.' => '.JSON($args));
            Timer::clear($timerID);
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function task(){
        try{
            $args   = [];
            $args['callback'] = ['Importer', 'Run'];
            $args['param']    = ['Lakers', 'Swoole', 'Westlife'];
            $taskID = Task::add($args);
            $this->response->write('Task has been set, id is => '.$taskID);
        }catch (Throwable $e){
			$this->error($e);
		}
    }
}