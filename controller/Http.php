<?php

class C_Http extends Controller {

	private $m_user;
	private $m_news;
    
    function __construct(){
    	$this->m_user = $this->load('User');
        $this->m_news = $this->load('News');
    }

    public function log(){
        Logger::debug('This is a debug msg');
        Logger::info('This is an info msg');
        Logger::warn('This is a warn msg');
        Logger::error('This is an error msg');
        Logger::fatal('This is a fatal msg');
        Logger::log('This is a log msg');

        $config = Config::getConfig();
        $this->response->end('Current error_level => '.$config['common']['error_level']);
    }

    // 测试onError事件
    // 为了避免由于exception, error 导致worker 退出后客户端一直收不回复的问题
    // 使用 try...catch(Throwable) 来处理
	public function onError(){
		try{
			$result = $this->m_player->SelectOne();
			$this->response->end('Result is => '.$result);
		}catch (Throwable $e){
			$this->error($e);
		}
	}

    // Pong
    public function ping(){
        $this->response->end('PONG');
    }

    // Get all users
    public function users(){
        try{
            $users = $this->m_user->SelectAll();
            $this->response->end(JSON($users));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Get all news
    public function news(){
        try{
            $news = $this->m_news->Select();
            $this->response->end(JSON($news));
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // MySQL 压力测试
    public function stress(){
        $max = 100000;
        $start_time = Logger::getMicrotime();
        for($i = 1; $i <= $max; $i++){
            $news = $this->m_news->Select();
        }
        $end_time = Logger::getMicrotime();
        $cost = $end_time - $start_time;
        $this->response->end('Time => '.$cost.', TPS => '.$max/$cost);
    }

    // tcp SelectAll
    public function all(){
        try{
            $users = $this->m_user->SelectAll();
            $this->response->write(JSON($users));

            $news = $this->m_news->Select();
            $this->response->write(JSON($news));

            $one_news = $this->m_news->SelectOne();
            $this->response->write(JSON($one_news));
            $this->response->end();
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
                $this->response->write('ERRORRRRRRRRR');
            }

            $this->response->end();
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
                $this->response->write('ERRORRRRRRRRR');
            }

            $field = ['id', 'username', 'password'];
            $where = ['id' => 2];
            $user = $this->m_user->SetDB('SLAVE')->ClearSuffix()->Field($field)->Where($where)->SelectOne();
            $this->response->write('Slave => '.JSON($user)."<br />");

            $where = ['status' => 1];
            $order = ['id' => 'DESC'];
            $user = $this->m_user->SetDB('SLAVE')->Suffix(38)->Field($field)->Where($where)->Order($order)->Limit(10)->Select();
            $this->response->write('Slave with suffix => '.JSON($user)."<br />");
            $this->response->end();
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Security
    public function security(){
        $this->response->end(JSON($this->request));
    }

    // Autoload
    public function rabbit(){
        try{
            $rabbit = new RabbitMQ();
            $this->response->end('A Rabbit is running happily now');
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

            $this->response->end();
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    public function pool(){
        $this->response->end(JSON(Pool::$pool[Pool::TYPE_MYSQL]));
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

            $this->response->end();
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
            $this->response->end();
        }catch (Throwable $e){
			$this->error($e);
		}
    }

    // Suffix
    public function suffix(){
        try{
            $user = $this->load('User')->Suffix(38)->ClearSuffix()->Suffix(52)->SelectOne();
            $this->response->end('Suffix user => '.JSON($user));
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

            $this->response->end();
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

            $this->response->end();
        }catch (Throwable $e){
			$this->error($e);
		}
    }
}