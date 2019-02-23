# Mini_Swoole

#### 迷你Swoole
> 支持 TCP, UDP, HTTP, Websocket <br />
> Master-Worker 模式<br />
> Controller-Model 分层 <br />
> MySQL 断线自动重连 <br />
> Timer, Task 简易封装 <br />
> MySQL, Redis 连接池<br />
> MySQL 分表分库 <br />
> JSON 作数据通信格式<br />
> Shell 脚本控制服务<br />
> Autoload<br />
> 安全过滤<br />
> 日志收集<br />
> 心跳检测<br />
> 自动路由<br />
> Hooker 与 Worker <br />

#### 环境要求
> PHP >= 7.0 <br />
> swoole, 建议 2.2.0 <br />
> pdo <br />
> redis <br />
> pdo_mysql <br />

#### 安装
> Git clone 至任一目录

#### CLI 命令
> 启动: sh shell/socket.sh start <br />
> 状态: sh shell/socket.sh status <br />
> 停止: sh shell/socket.sh stop <br />
> 重启: sh shell/socket.sh restart <br />
> Reload: sh shell/socket.sh reload, 重启所有Worker/Task进程 <br />

### 心跳检测
> 利用Crond 定时运行 shell/heartbeat.sh 即可<br />

#### 配置
> 配置文件是 conf/config_ENV.php, ENV 区分为 DEV, UAT, PRODUCTION, 在 Boostrap 的最开始定义, 请自行根据运行环境调整 <br />
> common 为公共配置部分, 影响整体 <br />
> http, tcp, udp, websocket, mysql, redis 配置 <br />
> 配置文件的 key 务必使用小写字母 <br />
> EVN 的定义在 Boostrap.php 的第一句, 请升级脚本(deploy.py)自行根据环境修改<br />

#### 使用
> 采用 Controll-Model 模式, 所有的请求均转至 Controller下处理 <br />
> Controller 中加载 Model 操作数据库 <br />
> Worker.php 的 afterStart(), afterOpen(), afterClose(), afterConnect(), afterStop() 可在 worker start, onOpen, onClose, onConnect, work stop 后处理自定义业务

#### TCP 服务
> 将 tcp 段的enable 设置为 true, 其他服务设置为 false <br />
> sh shell/socket.sh restart 重启服务 <br />
> ps -ef | grep Mini 将看到 <br />
>> Mini_Swoole_tcp_master: 为 master 进程  <br />
>> Mini_Swoole_manager: 为 manager 进程<br />
>> Mini_Swoole_task: N 个 task 进程 <br />
>> Mini_Swoole_worker: M 个 worker 进程 <br />

#### UDP 服务
> 将 udp 段的enable 设置为 true, 其他服务设置为 false <br />
> sh shell/socket.sh restart 重启服务 <br />
> ps -ef | grep Mini 将看到 <br />
>> Mini_Swoole_udp_master: 为 master 进程  <br />
>> Mini_Swoole_manager: 为 manager 进程<br />
>> Mini_Swoole_task: N 个 task 进程 <br />
>> Mini_Swoole_worker: M 个 worker 进程 <br />

#### HTTP 服务
> 将 http 段的enable 设置为 true, 其他服务设置为 false <br />
> sh shell/socket.sh restart 重启服务 <br />
> ps -ef | grep Mini 将看到 <br />
>> Mini_Swoole_http_master: 为 master 进程  <br />
>> Mini_Swoole_manager: 为 manager 进程<br />
>> Mini_Swoole_task: N 个 task 进程 <br />
>> Mini_Swoole_worker: M 个 worker 进程 <br />

#### Websocket 服务
> 将 websocket 段的enable 设置为 true, 其他服务设置为 false <br />
> sh shell/socket.sh restart 重启服务 <br />
> ps -ef | grep Mini 将看到 <br />
>> Mini_Swoole_websocket_master: 为 master 进程  <br />
>> Mini_Swoole_manager: 为 manager 进程<br />
>> Mini_Swoole_task: N 个 task 进程 <br />
>> Mini_Swoole_worker: M 个 worker 进程 <br />

> 注: N 和 M 由 $config['common']['worker_num'] 与 $config['common']['task_worker_num'] 指定 <br />

#### TCP 服务之控制器
> A: library 目录的 Worker::afterConnect(), Worker::afterClose() 负责处理 tcp 的 onConnect, onClose 事件<br />
> B: 为了将控制权由 onReceive 转至控制器, 客户端发送的数据需要指定处理该请求的 controller 及 action, 比如要指定由 Tcp 控制器下的 login() 来处理, 则发送的数据中应该是这样的 json 格式:【参见client/tcp_client.php】
```
	$data = [];
	$data['controller'] = 'tcp';
    $data['action']     = 'login';
	$data['username']   = 'dym';
    $data['password']   = md5(123456);
	$cli->send(json_encode($data)."\r\n");
```
> 1: 如果 Controller 不存在, 客户端收到: Controller $controller not found<br />
> 2: 如果 action 不存在, 客户端收到: Method $action not found<br />
> 3: 请以 $config['common']['package_eof'] 指定的方式分包, 默认是 \r\n <br />
> 4: 控制器中的 $this->data 为客户端发过来的完整数据, 格式为数组 <br />
> 5: 控制器的方法中调用 $this->response($rep) 将数据发送至客户端<br />
> 6: 控制器的示例为 controller下的 Tcp.php<br />
> 7: 更多 tcp server 信息请参考 https://wiki.swoole.com/wiki/page/p-server.html

```
    // login 及参数过滤
    public function login(){
        // 过滤
        $username = $this->getParam('username');
        $password = $this->getParam('password');

        // 回复给客户端
        $this->response('Username => '.$username.', password => '.$password);

        // 不过滤
        $username = $this->getParam('username', FALSE);
        $this->response('Username => '.$username.', password => '.$password);
    }
```
> 8: 为了避免由于exception, error 导致worker 退出后客户端一直收不回复的问题, 使用 try...catch(Throwable) 来处理

```
    // 测试onError事件
	public function onError(){
		try{
			$result = $this->m_player->SelectOne();
			$this->response('Result is => '.$result);
		}catch (Throwable $e){
			$this->error($e->getMessage());
		}
	}
```

#### UDP 服务之控制器
> A: 为了将控制权由 onReceive 转至控制器, 客户端发送的数据需要指定处理该请求的 controller 及 action, 比如要指定由 Udp 控制器下的 login() 来处理, 则发送的数据中应该是这样的 json 格式:【参见client/udp_client.php】

```
    $client = new Swoole\Client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_ASYNC);

    $client->on("connect", function(swoole_client $cli) {
        $d = [];
        $d['controller']  = 'udp';
        $d['action']      = 'login';
        $d['username'] = 'fooDELETE FROM sl_table <script>dym</script>';
        $d['password'] = 'fooDELETE 123123</script>';
        $data = json_encode($d);

        $cli->send($data);
    });

    $client->on("receive", function(swoole_client $cli, $data){
        print_r($data);
    });

    $client->on("error", function(swoole_client $cli){
        
    });

    $client->on("close", function(swoole_client $cli){
        
    });

    $client->connect('192.168.1.31', 9502, 0.5);
```
> 1: 如果 Controller 不存在, 客户端收到: Controller $controller not found<br />
> 2: 如果 action 不存在, 客户端收到: Method $action not found<br />
> 4: 控制器中的 $this->data 为客户端发过来的完整数据, 格式为数组 <br />
> 5: 控制器的方法中调用 $this->response($rep) 将数据发送至客户端<br />
> 6: 控制器的示例为 controller下的 Udp.php<br />
> 7: 更多 udp server 信息请参考 https://wiki.swoole.com/wiki/page/p-server.html

```
    // udp
    public function udp(){
        $key = $this->getParam('key');
        $this->response('Your key is '.$key);
    }
```
> 8: 为了避免由于exception, error 导致worker 退出后客户端一直收不回复的问题, 使用 try...catch(Throwable) 来处理

```
    // 测试onError事件
	public function onError(){
		try{
			$result = $this->m_player->SelectOne();
			$this->response('Result is => '.$result);
		}catch (Throwable $e){
			$this->error($e->getMessage());
		}
	}
```

#### HTTP 服务之控制器
> A: controller 目录的 Index.php/index(), 负责处理 http 的 index 事件<br />
> B: 为了将控制权由 onRequest 路由至今控制器, 客户端应该在URL中指定处理该请求的 controller 及 action, 示例如下: 

``` 
    // ==== GET 的示例 ==== //
    // Index 控制器下的 index() 来处理, 也就是首页, 则URL
    http://192.168.1.31:9503

    // Http 控制器下的 index() 来处理, 并且带上GET参数, 则URL
    http://192.168.1.31:9503/http?username=dym&password=123456

    // Http 控制器下的 login() 来处理, 并且带上GET参数, 则URL
    http://192.168.1.31:9503/http/login?username=dym&password=123456

    // ==== POST 的示例 ==== //
    $url = 'http://192.168.1.31:9503/http/login';
    $postData = [];
    $postData['key'] = 'FOO';

    $retval = HttpClient::post($url, $postData);
    print_r($retval);
```
> C: 可自行在library 目录的 Worker::beforeRequest() 中处理在 http request 前的业务<br />

> 1: 如果 Controller 不存在, 客户端收到: Controller $controller not found<br />
> 2: 如果 action 不存在, 客户端收到: Method $action not found<br />
> 3: 暂时只支持 GET / POST 方法<br />
> 4: 控制器的方法中调用 $this->response->write($rep) 将数据发送至客户端, write()可以调用多次, 最后使用 $this->response->end() 来结束这个请求 <br />
> 5: 使用write分段发送数据后，end方法将不接受任何参数
> 6: 控制器的示例为 controller下的 Index.php 与 Http.php<br />
> 7: 为了避免由于exception, error 导致worker 退出后客户端一直收不回复的问题, 使用 try...catch(Throwable) 来处理

```
    // Get all users
    public function users(){
        $this->httpHeader();

        try{
            $users = $this->m_user->SelectAll();
            $this->response->end(JSON($users));
        }catch (Throwable $e){
			$this->error($e->getMessage());
		}
    }
```
> 8: 更多 http server 信息请参考 https://wiki.swoole.com/wiki/page/326.html

#### Websocket 服务之控制器
> A: library 目录的 Worker::afterOpen() 负责处理 websocket 的 onOpen 事件<br />
> B: 为了将控制权由 onMessage 转至控制器, 客户端发送的数据需要指定处理该请求的 controller 及 action, 比如要指定由 websocket 控制器下的 go() 来处理, 则发送的数据中应该是这样的 json 格式:【参见client/ws.html】

```
    var arr = {};
    arr.controller = 'websocket';
    arr.action     = 'go';
    arr.key        = $('#key').val();
    ws.send(JSON.stringify(arr));
```
> 1: 如果 Controller 不存在, 客户端收到: Controller $controller not found<br />
> 2: 如果 action 不存在, 客户端收到: Method $action not found<br />
> 4: 控制器中的 $this->data 为客户端发过来的完整数据,<br />
> 5: 控制器的方法中调用 $this->response($rep) 将数据发送至客户端<br />
> 6: 控制器的示例为 controller下的 Websocket.php<br />
> 7: 更多 websocket server 信息请参考 https://wiki.swoole.com/wiki/page/397.html

```
    // Select all users
    public function users(){
        $users = $this->m_user->SelectAll();
        $this->response(JSON($users));

        $key = $this->getParam('key');
        $this->response('Your key is '.$key);
    }
```
> 8: 为了避免由于exception, error 导致worker 退出后客户端一直收不回复的问题, 使用 try...catch(Throwable) 来处理

```
    // 测试onError事件
	public function error(){
		try{
			$result = $this->m_player->SelectOne();
			$this->response('Result is => '.$result);
		}catch (Throwable $e){
			$this->error($e->getMessage());
		}
	}
```

#### MySQL
> 1: 通过模型访问数据库<br />
> 2: 控制器中使用 $this->m_user = $this->load('User'); 加载 User 模型<br />
> 3: 使用链式操作 Filed($field)->Where($where)->Order($order)->Limit($limit) 构建 SQL<br />
> 4: Insert(), MultiInsert(), SelectOne(), Select(), UpdateOne(), Update(), UpdateByID(), DeleteOne(), Delete(), DeleteByID() <br />
> 5: 根据ID 查询: SelectByID(), SelectFieldByID()<br />
> 6: 执行复杂的 SQL: Query($sql), QueryOne($sql)<br />
> 7: BeginTransaction(), Commit(), Rollback() 操作事务<br />
> 8: 断线自动重连3次<br />
> 9: 通用模型(Default)减少复习性方法很少的模型文件<br />
> 10: 示例为 model 下的 User.php 和 Default.php, 其中 Default 为默认通用模型文件

```
<?php
// User 模型
class M_User extends Model {
    
    function __construct(){
        $this->table = 'user';
        parent::__construct();
    }

    public function SelectAll(){
        $field = ['id', 'username', 'password'];
        return $this->Field($field)->Select();
    }
}
```
``` 
    // 控制器中调用通用的方法
    $this->m_user = $this->load('User');
	$field = ['id', 'username'];
    $where = ['status' => 1];
    $order = ['id' => 'DESC'];
    $users = $this->m_user->Field($field)->Where($where)->Order($order)->Select();
    $this->response(JSON($users));
```
``` 
    // 调用可复用的 SelectAll();
    $users = $this->m_user->SelectAll();
    $this->response(JSON($users));
```
```
    // 默认的通用模型使用, model 目录下并没有 News.php, 但一样可以这样使用
    $this->m_news = $this->load('News');
    $where = ['status' => 1];
    $order = ['id' => 'DESC'];
    $news = $this->m_news->Where($where)->Order($order)->Select();
    $this->response(JSON($news));
```
```
    //复杂的SQL(join 等)使用原生的SQL来写，方便维护
    public function getOnlineUsers($roomID){
        $sql = 'SELECT u.id, u.username, c.roomName FROM '.$this->table.' AS u LEFT JOIN '.TB_PREFIX.'chatroom AS c ON u.roomID = c.id WHERE u.roomID = "'.$roomID.'" ORDER BY u.id DESC LIMIT 20';
        return $this->Query($sql);
    }
```

### 分表
> 调用 Suffix($tb_suffix) 即可, 如 customer 有 1 至 100 个表，分别是 customer_1, customer_2, .... customer_100, 模型有一个 M_Customer 即可, 访问分表 customer_38 像这样
```
    1: config_ENV 中设置 tb_suffix_sf 为 _
    2: 代码中: $customer = $this->load('Customer')->Suffix(38)->SelectOne();
```

### 分库
> 为了减轻MySQL 主库压力, 有些时候有必要做读写分离，如何支持和切换主从呢? (注: 仅支持 Select 语句读从库) <br />
> 1: config_ENV 中像 mysql 节点一样设置一个 mysql_slave <br />
```
    'mysql_slave' => [
		'db'   => 'slave',
		'host' => '192.168.1.34',
		'port' => 3306,
		'user' => 'root',
		'pwd'  => '123456',
	],
```
> 2: 代码中调用 SetDB('SLAVE') 后再 Select() <br />
```
    $user = $this->load('User')->SetDB('SLAVE')->SelectOne();
```
> 3: 调皮的你又想切换为 MASTER 呢
```
    $user = $this->load('User')->SetDB('MASTER')->SelectOne();
```
> 4: 还可以结合分表一起使用
```
    $customer = $this->load('Customer')->SetDB('SLAVE')->Suffix(38)->SelectOne();
```
> 5: 来个长的链式操作
``` 
    $field = ['id', 'mobile', 'summary', 'address'];
    $where = ['companyID' => 38];
    $order = ['id' => 'DESC'];
    $customer = $this->load('Customer_ref')->SetDB('SLAVE')->Suffix(38)->Field($field)->Where($where)->Order($order)->Limit(10)->Select();
    $this->response('Slave with suffix => '.JSON($customer));
```

#### Redis
> 1: Cache::get($key) <br />
> 2: Cache::del($key) <br />
> 3: Cache::set($key, $val) <br />
> 4: 任意地方均可调用

### Autoload
> 1: 框架设置了 autoload 的目录是 library, 因此只要将类位于此目录下, 就能实现自动加载<br />
> 2: 例如控制器中要实例化 RabbitMQ, 文件名是 /library/RabbitMQ.php
```
    // Autoload 自动加载 RabbitMQ
    public function rabbit(){
        $rabbit = new RabbitMQ();
        $this->response('A Rabbit runs happily');
    }
```

### 日志
> 1: 文件由 $config['common']['log_file'] 指定<br />
> 2: PHP 和 SQL 的报错均写入<br />
> 3: 任意地方调用 Logger::log($msg) 即可写入日志<br />

### 安全与过滤
> 1: 控制器中使用 $this->getParam($key) 来获取请求的参数，比如 $username = $this->getParam('username'), 默认会对数据进行过滤，若不过滤，将第二个参数设置为 FALSE: $username = $this->getParam('username', FALSE) <br />
> 2：getParam() 默认会进行 XSS 过滤, addslashes(), trim() <br />
> 3: 文件是 library/core/Security.php

### 普通方法
> 1: 将要增加的方法写入 library/core/Function.php 即可随处调用

#### 定时器 Timer
> 1: 控制器中想每2秒执行当前类的 tick() 方法, 并且传递 xyx 作为参数, 则这样做
```
	Timer::add(2000, [$this, 'tick'], 'xyz');
```
tick 方法则这样接收, 然后使用Timer::clear($timerID);来清除定时器
```
	public function tick(int $timerID, $args){
        $this->response('Time in tick '.date("Y-m-d H:i:s\n"));
        $this->response('Args in tick '.JSON($args));
        // Clear timer
        Timer::clear($timerID);
    }
```

> 2: 控制器中想5秒后执行当前类的 after() 方法, 则这样做。
```
    Timer::after(5000, [$this, 'after']);
```
after 方法
```
    // 注: after定时器不接收任何参数
	public function after(){
        $this->response('Execute '.__METHOD__.' in after timer');
    }
```

#### 任务投递 Task
> 1: 控制器user中要将数据投递到 task 且由当前类的 myTask() 来处理业务逻辑
```
	// Task
    $args = [];
    $args['controller']   = 'user';
    $args['action']       = 'myTask';
    $args['data']['line'] = __LINE__;
    $args['data']['type'] = Server::$type;
    Task::add($args);
```
2: myTask 方法则这样接收参数, $args 仅包括 $args['data'] 中的数据, 不包括 controller 与 action, 因为并不需要包括了
```
	public function myTask($args){
        Logger::log(__METHOD__);
        Logger::log(JSON($args));
    }
```
3：当任务完成后, onFinish回调函数就派上用场了。任务完成时，task进程会将结果发送给onFinish函数，在由onFinish函数返回给worker
```
    // 文件: Task.php
    // $data 即为 onTask $server->finish($data) 的参数, 根据参数进行业务处理
    public static function onFinish(swoole_server $server, int $taskID, string $data){
        Logger::log(__METHOD__.' taskID => '.$taskID);
        Logger::log(__METHOD__.' data => '.$data);
    }
```

#### Process 与 MySQL 连接池
> 1: 如果在 Worker 里启动一个 process, 在 process 里如何使用 MySQL 连接池及共用底层的 model 使用方式？请参见 controller/http/process 里的写法

#### TCP 客户端调用
> 1：初始化一个异步的tcp Swoole\Client
```
    $client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
```

> 2：设置回调, onReceive(), onConnect(), onClose(), onError()

```
    $client->on("connect", function($cli){
        // 指定处理该请求的 controller 和 action 
        $d = [];
        $d['controller']  = 'user';
        $d['action']      = 'all'

        // 参数都用 data 包起来
        $d['data']['username'] = 'DELETE FROM sl_table <script>dym</script>';
        $d['data']['password'] = md5(123456);

        // 以 "\r\n" 分包
        $cli->send(json_encode($d)."\r\n");
    });

    $client->on("receive", function(swoole_client $cli, $data){
        echo $data.PHP_EOL;
        // $cli->close();
    });

    $client->on("error", function(swoole_client $cli){
        echo "error".PHP_EOL;
        $cli->close();
    });

    $client->on("close", function(swoole_client $cli){
        echo "Connection close".PHP_EOL;
    });
```
> 3: 最后就是连接服务端了, 示例文件 client/tcp_client.php
```
    $client->connect('192.168.1.31', 9500);
```

#### UDP 客户端调用
> 1：初始化一个异步的 udp Swoole\Client
```
    $client = new Swoole\Client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_ASYNC);
```

> 2：设置 onConnect(), onError(), onReceive(), OnClose() <br />
> 3：onConnect() 中发送数据, onReceive() 中接收

```
    $client->on("connect", function($cli){
        $d = [];
        $d['controller']  = 'user';
        $d['action']      = 'all';
        $d['data']['key'] = 'foo';

        $d['data']['username'] = 'DELETE FROM sl_table <script>dym</script>';
        $d['data']['password'] = md5(123456);

        $cli->send(json_encode($d));
    });

    $client->on("receive", function(swoole_client $cli, $data){
        echo $data.PHP_EOL;
        // $cli->close();
    });

    $client->on("error", function(swoole_client $cli){
        echo "error".PHP_EOL;
        $cli->close();
    });

    $client->on("close", function(swoole_client $cli){
        echo "Connection close".PHP_EOL;
    });
```
4：最后就是连接

```
    $client->connect('192.168.1.31', 9501);
```

#### Websocket 客户端调用
> 1：支持 WebSocket 的现代浏览器 <br />
> 2：onOpen() 后同样以 JSON 构造处理请求的 controller 与 action

```
    ws.onopen = function () {
        //心跳检测重置
        // heartCheck.reset().start();
        var arr = {};
        arr.controller = 'user';
        arr.action     = 'websocket';
        ws.send(JSON.stringify(arr));
    };
```
> 3：send() 之后 onmessage() 中接收数据

```
    ws.onmessage = function (event){
        console.log(event.data);
    }
```
> 4：自行维持心跳机制, 示例文件 client/ws.html