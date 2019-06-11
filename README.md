# Mini Swoole
### Write Less and Do More ###

```
 __  __ _       _   ____                     _      
|  \/  (_)_ __ (_) / ___|_      _____   ___ | | ___ 
| |\/| | | '_ \| | \___ \ \ /\ / / _ \ / _ \| |/ _ \
| |  | | | | | | |  ___) \ V  V / (_) | (_) | |  __/
|_|  |_|_|_| |_|_| |____/ \_/\_/ \___/ \___/|_|\___|
```
<hr />

- 支持 TCP, UDP, HTTP, Websocket <br />
- Master-Worker 模式<br />
- Controller-Model 分层 <br />
- MySQL 断线自动重连 <br />
- Timer, Task 简易封装 <br />
- MySQL, Redis 连接池<br />
- MySQL 分表分库 <br />
- JSON 作数据通信格式<br />
- Shell 脚本控制服务<br />
- Autoload<br />
- 安全过滤<br />
- 日志收集<br />
- 心跳检测<br />
- 自动路由<br />
- Hooker 与 Worker <br />
- 多模块划分
- 中间件
- Process 管理
<hr />

#### 环境要求
- PHP >= 7.0 <br />
- swoole, 建议 2.2.0 <br />
- pdo <br />
- redis <br />
- pdo_mysql <br />
<hr />

#### 安装
- Git clone 至任一目录
- 创建数据库并导入SQL 文件

<hr />

#### 配置
- EVN 的定义在 Boostrap.php 的第一句, 请升级脚本(deploy.py)自行根据环境修改<br />
- 配置文件是 conf/ENV.php。 ENV 区分为 DEV, UAT, PRODUCTION, 请自行根据运行环境调整 <br />
- common 为公共配置部分, 影响整体 <br />
- 七个 $section 分为: common, http, tcp, udp, websocket, mysql, redis 配置 <br />
- 配置文件的 key 务必使用小写字母 <br />
- Init.php 中可自由配置业务需要的参数和常量
- 任意地方均可使用 Config::get($section) 来获取配置文件中 $section 的参数
- 任意地方均可使用 Config::get($section, $key) 来获取配置文件中 $section 中的 $key 对应的参数
<hr />

#### CLI 命令
- 启动: sh shell/socket.sh start <br />
- 状态: sh shell/socket.sh status <br />
- 停止: sh shell/socket.sh stop <br />
- 重启: sh shell/socket.sh restart <br />
- Reload: sh shell/socket.sh reload, 重启所有Worker/Task进程 <br />
<hr />

#### 心跳检测
- 利用Crond 定时运行 shell/heartbeat.sh 即可<br />
<hr />

#### 使用
- 采用 Module-Controll-Model 模式, 所有的请求均转至 Module-Controller下处理 <br />
- 默认 Module 为index, 无须声明, 对应的控制器文件位是根目录的 controller<br />
- 在配置文件的 module 中声明新模块，以英文逗号分隔，如 'Api, Admin, Mall, Shop', 对应的控制器文件是 /module/$moduleName/controller<br />
- 中间件：很多时候，我们需要在请求前与后做一些前置与后置的统一业务，比如授权认证，日志与流量收集，中件间就派上用场了。

<hr />

#### 日志和错误处理
- 系统的错误文件由 $config['common']['log_file'] 指定<br />
- $config['common']['error_level'] 指定手动记录日志的级别, [1|2|3|4|5], 分别代表 DEBUG, INFO, WARN, ERROR, FATAL，低于规定的日志级别则不记录 <br />
- $config['common']['error_file'] 指定手动记录日志的文件<br />
- 任意地方调用 Logger::debug($msg); Logger::info($msg); Logger::warn($msg); Logger::error($msg); Logger::fatal($msg); 则将 $msg 以指定的级别写入 $config['common']['error_file']<br />
- SQL 的错误文件由 $config['common']['mysql_log_file'] 指定, 当执行SQL发生错误时，自动写入, 级别均为 ERROR<br />

```
public function log(){
    try{
        Logger::debug('This is a debug msg');
        Logger::info('This is an info msg');
        Logger::warn('This is a warn msg');
        Logger::error('This is an error msg');
        Logger::fatal('This is a fatal msg');
        Logger::log('This is a log msg');

        $level = Config::get('common', 'error_level');
        $this->response->write('Current error_level => '.$level);
    }catch (Throwable $e){
        $this->error($e);
    }
}
```
- log_method 指定保存日志的方式，默认是 file, 可选 redis. 若选择 redis, 则配置多一个 redis_log, 好处是啥？直接对接至 ELK ！
```
    'redis_log' => [
        'db'    => '0',
        'host'  => '192.168.1.50',
        'port'  => 6379,
        'pwd'   => '123456',
        'queue' => 'Queue_log',
    ],
```
- 为了避免由于exception, error 导致worker 退出后客户端一直收不回复的问题, 控制器中使用 try...catch(Throwable) 来处理

```
// 故意令 $this->m_player 为空
public function onError(){
    try{
        $result = $this->m_player->SelectOne();
        $this->response->write('Result is => '.$result);
    }catch (Throwable $e){
        $this->error($e);
    }
}
```
调用此方法，客户端将以 TAB 显示错误提示, 包括 General Eroor, Trace info, GET, POST, COOKIE, SERVER 的数据，如果是 SQL 报错，还将显示报错的 SQL 语句，给于 debug 最大的便利 

<hr />

#### TCP 服务
- 将 tcp 段的enable 设置为 true, 其他服务设置为 false <br />
- sh shell/socket.sh restart 重启服务 <br />
- ps -ef | grep Mini 将看到
    > Mini_Swoole_tcp_master: 为 master 进程  <br />
    > Mini_Swoole_manager: 为 manager 进程<br />
    > Mini_Swoole_task: N 个 task 进程 <br />
    > Mini_Swoole_worker: M 个 worker 进程 <br />

<hr />

#### UDP 服务
- 将 udp 段的enable 设置为 true, 其他服务设置为 false <br />
- sh shell/socket.sh restart 重启服务 <br />
- ps -ef | grep Mini 将看到 <br />
    > Mini_Swoole_udp_master: 为 master 进程  <br />
    > Mini_Swoole_manager: 为 manager 进程<br />
    > Mini_Swoole_task: N 个 task 进程 <br />
    > Mini_Swoole_worker: M 个 worker 进程 <br />

<hr />

#### HTTP 服务
- 将 http 段的enable 设置为 true, 其他服务设置为 false <br />
- sh shell/socket.sh restart 重启服务 <br />
- ps -ef | grep Mini 将看到 <br />
    > Mini_Swoole_http_master: 为 master 进程  <br />
    > Mini_Swoole_manager: 为 manager 进程<br />
    > Mini_Swoole_task: N 个 task 进程 <br />
    > Mini_Swoole_worker: M 个 worker 进程 <br />

<hr />

#### Websocket 服务
- 将 websocket 段的enable 设置为 true, 其他服务设置为 false <br />
- sh shell/socket.sh restart 重启服务 <br />
- ps -ef | grep Mini 将看到 <br />
    > Mini_Swoole_websocket_master: 为 master 进程  <br />
    > Mini_Swoole_manager: 为 manager 进程<br />
    > Mini_Swoole_task: N 个 task 进程 <br />
    > Mini_Swoole_worker: M 个 worker 进程 <br />

<hr />

##### 注: N 和 M 由 $config['common']['worker_num'] 与 $config['common']['task_worker_num'] 指定 <br />

<hr />

#### TCP 服务之控制器
- 为了将控制权由 onReceive 转至控制器, 客户端发送的数据需要指定处理该请求的 module (默认是index, 可以忽略), controller 及 action, 比如要指定由 Tcp 控制器下的 login() 来处理, 则发送的数据中应该是这样的 json 格式:【参见client/tcp_client.php】
```
$data = [];
$data['controller'] = 'tcp';
$data['action']     = 'login';
$data['username']   = 'dym';
$data['password']   = md5(123456);
$cli->send(json_encode($data)."\r\n");
```
- 如果 Controller 不存在, 客户端收到: Controller $controller not found<br />
- 如果 action 不存在, 客户端收到: Method $action not found<br />
- 请以 $config['common']['package_eof'] 指定的方式分包, 默认是 \r\n <br />
- 控制器中的 $this->data 为客户端发过来的完整数据, 格式为数组 <br />
- 控制器的方法中调用 $this->response($rep) 将数据发送至客户端<br />
- 控制器的示例为 controller下的 Tcp.php<br />
- 更多 tcp server 信息请参考 https://wiki.swoole.com/wiki/page/p-server.html

```
// login 及参数过滤
public function login(){
    try{
        // 过滤
        $username = $this->getParam('username');
        $password = $this->getParam('password');

        // 回复给客户端
        $this->response('Username => '.$username.', password => '.$password);

        // 不过滤
        $username = $this->getParam('username', FALSE);
        $this->response('Username => '.$username.', password => '.$password);
    }catch (Throwable $e){
        $this->error($e);
    }
}
```

<hr />

#### UDP 服务之控制器
- 为了将控制权由 onReceive 转至控制器, 客户端发送的数据需要指定处理该请求的 module(默认是index, 可以忽略), controller 及 action, 比如要指定由 Udp 控制器下的 login() 来处理, 则发送的数据中应该是这样的 json 格式:【参见client/udp_client.php】

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

$client->connect('127.0.0.1', 9502, 0.5);
```
- 如果 Controller 不存在, 客户端收到: Controller $controller not found<br />
- 如果 action 不存在, 客户端收到: Method $action not found<br />
- 控制器中的 $this->data 为客户端发过来的完整数据, 格式为数组 <br />
- 控制器的方法中调用 $this->response($rep) 将数据发送至客户端<br />
- 控制器的示例为 controller下的 Udp.php<br />
- 更多 udp server 信息请参考 https://wiki.swoole.com/wiki/page/p-server.html

```
public function udp(){
    try{
        $key = $this->getParam('key');
        $this->response('Your key is '.$key);
    }catch (Throwable $e){
        $this->error($e);
    }
}
```

<hr />

#### HTTP 服务之控制器
- 根目录的 controller 的 Index.php/index(), 负责处理 http 的 index 事件, 也就是首页<br />
- 为了将控制权由 onRequest 路由至控制器, 客户端应该在URL中指定处理该请求的 module (默认是index, 可以忽略), controller 及 action (默认是index, 可以忽略), 示例如下: 

``` 
// ==== GET 的示例 ==== //
// Index 控制器下的 index() 来处理, 也就是首页, 则URL
http://127.0.0.1:9100

// Http 控制器下的 index() 来处理, 并且带上GET参数, 则URL
http://127.0.0.1:9100/http?username=dym&password=123456

// Http 控制器下的 login() 来处理, 并且带上GET参数, 则URL
http://127.0.0.1:9100/http/login?username=dym&password=123456

// ==== POST 的示例 ==== //
$url = 'http://127.0.0.1:9100/http/login';
$postData = [];
$postData['key'] = 'FOO';

$retval = HttpClient::post($url, $postData);
print_r($retval);

// Api模块的Login控制器下的 logout() 来处理, 则URL
http://127.0.0.1:9100/api/login/logout

// Api模块的User控制器下的 index() 来处理, 则URL
http://127.0.0.1:9100/api/user  
```
- 暂时只支持 GET / POST 方法<br />
- 如果 Controller 不存在, 客户端收到: Controller $controller not found<br />
- 如果 action 不存在, 客户端收到: Method $action not found<br />
- 控制器的方法中调用 $this->response->write($rep) 将数据发送至客户端, 可以调用多次 <br />
- 控制器的示例为 controller下的 Index.php 与 Http.php 及 module/Api/controller 下的 Login.php 和 User.php <br />
- 更多 http server 信息请参考 https://wiki.swoole.com/wiki/page/326.html

<hr />

#### Websocket 服务之控制器
- 为了将控制权由 onMessage 转至控制器, 客户端发送的数据需要指定处理该请求的module (默认是index, 可以忽略), controller 及 action, 比如要指定由 websocket 控制器下的 go() 来处理, 则发送的数据中应该是这样的 json 格式:【参见client/ws.html】

```
var arr = {};
arr.controller = 'websocket';
arr.action     = 'go';
arr.key        = $('#key').val();
ws.send(JSON.stringify(arr));
```
- 如果 Controller 不存在, 客户端收到: Controller $controller not found<br />
- 如果 action 不存在, 客户端收到: Method $action not found<br />
- 控制器中的 $this->data 为客户端发过来的完整数据,<br />
- 控制器的方法中调用 $this->response($rep) 将数据发送至客户端<br />
- 控制器的示例为 controller下的 Websocket.php<br />
- 更多 websocket server 信息请参考 https://wiki.swoole.com/wiki/page/397.html

```
// Select all users
public function users(){
    try{
        $users = $this->m_user->SelectAll();
        $this->response(JSON($users));

        $key = $this->getParam('key');
        $this->response('Your key is '.$key);
    }catch (Throwable $e){
        $this->error($e);
    }
}
```

<hr />

#### MySQL
```
'mysql' => [
    'db'   => 'slave',
    'host' => '192.168.1.34',
    'port' => 3306,
    'user' => 'root',
    'pwd'  => '123456',
    'max'  => 3,
    'log_sql' => true,
],
```
- 断线自动重连3次<br />
- 配置文件中的 max 是指每一个 worker 有多少个连接对象组成一个连接池
- 控制器中使用 $this->m_user = $this->load('User'); 加载模型<br />
- 使用链式操作 Filed($field)->Where($where)->Order($order)->Limit() 构建 SQL<br />
- Insert(), MultiInsert(), SelectOne(), Select(), UpdateOne(), Update(), UpdateByID(), DeleteOne(), Delete(), DeleteByID() <br />
- 根据ID 查询: SelectByID(), SelectFieldByID()<br />
- 执行复杂的 SQL: Query($sql), QueryOne($sql)<br />
- BeginTransaction(), Commit(), Rollback() 操作事务<br />
- 通用模型(Default)减少复用性方法很少的模型文件<br />
- 将 log_sql 设置为 true 将在 $config['common']['mysql_log_file'] 中记录下每一条被执行的 SQL 语句, 级别为 INFO
- 分页？ 在 HTTP 为 GET 的情况下，调用 ->Limit() 方法, 就自动分页了，默认是一页 10 条记录
```
    查询10条： Filed($field)->Where($where)->Order($order)->Limit()->Select();
    一页20条： Filed($field)->Where($where)->Order($order)->Limit(20)->Select();
```
- 示例为 model 下的 User.php 和 Default.php, 其中 Default 为默认通用模型文件<br />

```
<?php
class M_User extends Model {
    
    function __construct(){
        $this->table = 'user';
        parent::__construct();
    }

    public function SelectAll(){
        try{
            $field = ['id', 'username', 'password'];
            return $this->Field($field)->Select();
        }catch (Throwable $e){
            $this->error($e);
        }
    }

    //复杂的SQL(join 等)使用原生的SQL来写，方便维护
    public function getOnlineUsers($roomID){
        $sql = 'SELECT u.id, u.username, c.roomName FROM '.$this->table.' AS u LEFT JOIN '.TB_PREFIX.'chatroom AS c ON u.roomID = c.id WHERE u.roomID = "'.$roomID.'" ORDER BY u.id DESC LIMIT 20';
        return $this->Query($sql);
    }
}
```

``` 
// 控制器中调用通用的方法
try{
    $this->m_user = $this->load('User');
    $field = ['id', 'username'];
    $where = ['status' => 1];
    $order = ['id' => 'DESC'];
    $users = $this->m_user->Field($field)->Where($where)->Order($order)->Limit()->Select();
    $this->response(JSON($users));
}catch (Throwable $e){
    $this->error($e);
}
```

``` 
// 调用可复用的 SelectAll();
try{
    $users = $this->m_user->SelectAll();
    $this->response(JSON($users));
}catch (Throwable $e){
    $this->error($e);
}
```
```
// 默认的通用模型使用, model 目录下并没有 News.php, 但一样可以这样使用
try{
    $this->m_news = $this->load('News');
    $where = ['status' => 1];
    $order = ['id' => 'DESC'];
    $news = $this->m_news->Where($where)->Order($order)->Select();
    $this->response(JSON($news));
}catch (Throwable $e){
    $this->error($e);
}
```
<hr />

#### 分表
- 调用 Suffix($tb_suffix) 即可, 如 customer 有 1 至 100 个表，分别是 customer_1, customer_2, .... customer_100, 模型有一个 M_Customer 即可, 访问分表 customer_38 像这样
```
1: 配置文件中设置 tb_suffix_sf 为 _
2: 代码中: $customer = $this->load('Customer')->Suffix(38)->SelectOne();
```
<hr />

#### 分库
- 为了减轻MySQL 主库压力, 有些时候有必要做读写分离，如何支持和切换主从呢? (注: 仅支持 Select 语句读从库, 因此从库的连接只有一个，并不像主库那样有连接池。当然，如果要实现从库也是连接池，也不难，改改即可) <br />
- 配置文件中像 mysql 节点一样设置一个 mysql_slave <br />

```
'mysql_slave' => [
    'db'   => 'slave',
    'host' => '192.168.1.34',
    'port' => 3306,
    'user' => 'root',
    'pwd'  => '123456',
],
```

- 代码中调用 SetDB('SLAVE') 后再 Select() <br />
```
$user = $this->load('User')->SetDB('SLAVE')->SelectOne();
```
- 调皮的你又想切换为 MASTER 呢
```
$user = $this->load('User')->SetDB('MASTER')->SelectOne();
```
- 还可以结合分表一起使用
```
$customer = $this->load('Customer')->SetDB('SLAVE')->Suffix(38)->SelectOne();
```
- 来个长的链式操作
```
try{
    $field = ['id', 'mobile', 'summary', 'address'];
    $where = ['companyID' => 38];
    $order = ['id' => 'DESC'];
    $customer = $this->load('Customer_ref')->SetDB('SLAVE')->Suffix(38)->Field($field)->Where($where)->Order($order)->Limit()->Select();
    $this->response('Slave with suffix => '.JSON($customer));
}catch (Throwable $e){
    $this->error($e);
}
```

<hr />

#### Redis
- Cache::get($key) <br />
- Cache::del($key) <br />
- Cache::set($key, $val) <br />
- 任意地方均可调用

<hr />

#### Autoload
- 框架设置了 autoload 的目录是 library, 因此只要将类位于此目录下, 就能实现自动加载<br />
- 例如控制器中要实例化 RabbitMQ, 文件名是 /library/RabbitMQ.php
```
public function rabbit(){
    try{
        $rabbit = new RabbitMQ();
        $this->response('A Rabbit is running happily now');
    }catch (Throwable $e){
        $this->error($e);
    }
}
```

<hr />

#### 安全与过滤
- 控制器中使用 $this->getParam($key) 来获取请求的参数，比如 $username = $this->getParam('username'), 默认会对数据进行过滤，若不过滤，将第二个参数设置为 FALSE: $username = $this->getParam('username', FALSE) <br />
- getParam() 默认会进行 XSS 过滤, addslashes(), trim() <br />
- 文件是 library/core/Security.php

<hr />

#### 普通方法
- 将要增加的方法写入 library/core/Function.php 即可随处调用

<hr />

#### 定时器 Timer
- 控制器中想每2秒执行当前类的 tick() 方法, 并且传递 xyx 作为参数, 则这样做
```
Timer::add(2000, [$this, 'tick'], 'xyz');
```
tick 方法则这样接收, 然后使用Timer::clear($timerID);来清除定时器

```
public function tick(int $timerID, $args){
    try{
        $this->response('Time in tick '.date("Y-m-d H:i:s\n"));
        $this->response('Args in tick '.JSON($args));

        // Clear timer
        Timer::clear($timerID);
    }catch (Throwable $e){
        $this->error($e);
    }
}
```

- 控制器中想5秒后执行当前类的 after() 方法, 则这样做。
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

<hr />

#### 任务投递 Task
- 以数组形式指定 callback 与 param, 调用 Task::add($args)
- 以下例子投递一个任务, 由 Importer 的 Run() 处理, 参数是 ['Lakers', 'Swoole', 'Westlife'];
```
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
```

```
class Importer {
    public static function run(...$param){
        Logger::log('Param in '.__METHOD__.' => '.JSON($param));
    }
}
```
2：任务完成时，task进程会将结果发送给onFinish函数，再由onFinish函数返回给worker
```
public static function onFinish(swoole_server $server, int $taskID, string $data){
    Logger::log('taskID => '.$taskID.' => finish');
}
```

<hr />

#### 中间件
> 基于 Pipeline 模式实现 <br />
> 实现该中间件的类必须实现 Middleware 中的 handle() 方法。 <br />
> 若要中止流程, throw 一个 Error 即可, 最后别忘了调用 $next(); <br />
> TCP, UDP, HTTP, WEBSOCKET 均可使用哦 <br />
> 请参考 Auth.php 与 Importer.php <br />
```
<?php
    interface Middleware {
        public static function handle(Closure $next);
    }
```
```
<?php
    class Auth implements Middleware {
        public static function handle(Closure $next){
            if(!Request::has('token') || empty(Request::get('token'))){
                throw new Error('Access denied !', 401);
                return;
            }else{
                Request::set('token', 'ABCDEFG');
                $next();
            }
        }
    }
```
```
<?php
    class Importer {
        public static function handle(Closure $next){
            if(!Request::has('file') || empty(Request::get('file'))){
                throw new Error('Empty file !', 402);
                return;
            }else{
                $next();
            }
        }
    }
```
> 调用方法: 控制器的构造函数中调用 $this->middleware([$pipe1, $pipe2, $pipeN]);
```
    // Auth中间件
    function __construct(){
        $this->middleware(['Auth']);
        $this->response->end($this->getParam('token'));
    }
    // Importer中间件
    function __construct(){
        $this->middleware(['Importer']);
        $this->response->end($this->getParam('token'));
    }
    // 多个中间件一起使用, 先执行 Auth::handle(), 再执行 Importer::handle();
    function __construct(){
        $this->middleware(['Auth', 'Importer']);
        $this->response->end($this->getParam('token'));
    }
```

#### 进程管理器
> 1：很多业务需要长驻内存不断的跑[while(true)]，worker 和 task 就不适合了，更好的方式是创建自己的进程来处理 <br />
> 2：配置文件的 process 中配置自己想要创建的进程, 格式为<br />

```
    'process' => [
        $进程名 => [        
            'num'   => 进程的数量, 数字 
            'mysql' => 是否连接 MySQL, 布尔,
            'redis' => 是否连接 Redis, 布尔,
            'param' => 要带入的参数, 索引数组,
            'callback' => 回调函数, 也就是进程创建后要执行的方法
        ],
    ],
```
```
    'process' => [
        'Tiny_Swoole_importer'=> [
            'num' => 1, 
            'mysql' => true,
            'redis' => true,
            'callback' => ['Importer', 'run'],
		],
	],
```
```
    class Importer {

        public static function run(...$param){
            Logger::log('Importer process is ready !');

            while (TRUE) {
                $key = 'Key_current_time';
                Cache::set($key, date('Y-m-d H:i:s'));
                $val = Cache::get($key);
                Logger::log('Time => '.$val);
                sleep(3);
            }
        }
    }
```

> 3: 定时调用 shell/heartbeat_process.sh 对进程作心跳检测 <br />
> 4: 调用 shell/restart_process.sh 重启所有的进程 <br />
> 5: 要单独停止一个 process 则需要编写单独的 shell 脚本, 参考 importer.sh <br />

<hr />

#### TCP 客户端
- 初始化一个异步的tcp Swoole\Client
```
$client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
```

- 设置回调, onReceive(), onConnect(), onClose(), onError()

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
- 最后就是连接服务端了, 示例文件 client/tcp_client.php
```
$client->connect('127.0.0.1', 9500);
```

<hr />

#### UDP 客户端
- 初始化一个异步的 udp Swoole\Client
```
$client = new Swoole\Client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_ASYNC);
```

- 设置 onConnect(), onError(), onReceive(), OnClose() <br />
- onConnect() 中发送数据, onReceive() 中接收

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
$client->connect('127.0.0.1', 9501);
```

<hr />

#### Websocket 客户端
- 支持 WebSocket 的现代浏览器 <br />
- onOpen() 后同样以 JSON 构造处理请求的 controller 与 action

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
- send() 之后 onmessage() 中接收数据

```
ws.onmessage = function (event){
    console.log(event.data);
}
```
- 自行维持心跳机制, 示例文件 client/ws.html