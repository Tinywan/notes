学习Notes
===============

## 功能列表
* [x] 如何使用命令行（带参数）
* [x] 如何使用多任务队列发送邮件
* [x] 缓存支持文件缓存和Redis缓存
* [x] 通过redis实现session共享存储 (不需要修改`php.ini`配置文件) 
* [x] 符合REST架构设计的API，提供便利的API的版本号访问地址
* [x] 引入Trait，实现了代码的复用

## 5.1 版本注意点

* 记录日志，由`use think\Log;`修改为`use think\facade\Log;`
* 队列配置文件的不同
    * 5.0版本：`\application\extra\queue.php`
    * 5.1版本：`\application\config\queue.php`
    * [thinkphp-queue 笔记](https://github.com/coolseven/notes/blob/master/thinkphp-queue/README.md)

## 路由

定义接口（api）路由
```php
Route::get("api/:version/token/user","api/:version.Token/getToken");
// 或者 \think\facade\Route::get("api/:version/token/user","api/:version.Token/getToken");
```
>定义路由前访问地址：`http://tp51.env/api/v1.token/getToken`
>定义路由后访问地址：`http://tp51.env/api/v1/token/user`

## 控制台命令

#### 创建一个命令

```php
namespace app\common\console;

use app\common\components\test\SystemUser;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
class CreateUser extends Command
{
    protected function configure()
    {
        $this->setName('hello')
            ->addArgument('name', Argument::REQUIRED, "your name")
            ->addOption('city', null, Option::VALUE_REQUIRED, 'city name')
            ->setDescription('Say Hello');
    }

    protected function execute(Input $input, Output $output)
    {
        $name = trim($input->getArgument('name'));
        $name = $name ?: 'thinkphp';

        if ($input->hasOption('city')) {
            $city = PHP_EOL . 'From ' . $input->getOption('city');
        } else {
            $city = '';
        }

        if ($name == 'systemUser'){
            $res = PHP_EOL.$this->systemUser();
        }
        $res = $res ?: 'Default User';

        $output->writeln("Hello," . $name . '!' . $city."\r\n".$res);
    }

    /**
     * 创建系统用户
     * @return string
     */
    private function systemUser()
    {
        $model = new SystemUser();
        return $model->create();
    }
```
> 你必须在 configure() 方法中配置命令的名称，然后可选地定义一个帮助信息 和 输入选项及输入参数  
> `app\common\components\test\SystemUser`为一个需要运行的组件

#### 配置命令

配置命令之后，然后在 application 目录下面的 command.php（如果不存在则创建）文件中添加如下内容：

```php
return [
    \app\common\console\CreateUser::class
];
```
#### 执行命令

在终端（terminal）中执行

* 方式一：

    ```php
    >php think hello Tinywan
    Hello,Tinywan!
    Default User
    ```

* 方式二：

    ```php
    >php think hello Tinywan --city shanghai
    Hello,Tinywan!
    From shanghai
    Default User
    ```

* 方式三：
    ```php
    >php think hello systemUser
     Hello,systemUser!
     
     createapp\common\components\test\SystemUser::create
    ```

* 方式四：
    ```php
    php think hello systemUser --city GanSu
    Hello,systemUser!
    From GanSu
    
    createapp\common\components\test\SystemUser::create
    ```
#### 创建类库文件
 
* 快速生成控制器：`php think make:controller live/Blog` 
* 快速生成模型：`php think make:model index/Blog` 
* 快速生成中间件：`php think make:middleware Auth` 
* 创建验证器类：`php think make:validate index/User` 

## Trait

trait有两个功能 :
* 提供如`interface`的合约
* 提供如`class`的实做
* 类成员优先级为：当前类 > Trait > 父类
* [PHP中Trait详解及其应用](https://segmentfault.com/a/1190000008009455)

所以trait是一個看起來像interface，但用起來像class的東西。


## 队列

#### 创建队列

队列任务代码:`application\common\queue\Worker.php`  

`workerQueue` 任务队列功能

* 检查数据库状态，如果数据库为链接成功则直接**删除任务**
* 否则，给数据库插入一条记录
* 如果记录插入成功，则直接**删除任务**
* 否则延迟发送4次后不再发送
  
#### 配置队列

配置文件路径：`application\config\queue`，配置如下所示：

```php
return [
    // Redis 驱动
    'connector'  => 'Redis',
    // 任务的过期时间，默认为60秒; 若要禁用，则设置为 null
    'expire'     => 60,
    // 默认的队列名称
    'default'    => 'default',
    // redis 主机ip
    'host'       => '127.0.0.1',
    // redis 端口
    'port'       => 6379,
    // redis 密码
    'password'   => '',
    // 使用哪一个 db，默认为 db0
    'select'     => 0,
    // redis连接的超时时间
    'timeout'    => 0,
    // 是否是长连接
    'persistent' => false,
];
```

#### 创建与推送消息

```php
public function queue()
{
    //当前任务所需的业务数据，不能为 resource 类型，其他类型最终将转化为json形式的字符串
    $data = [
        'email' => '28456049@qq.com',
        'username' => 'Tinywan' . rand(1111, 9999)
    ];

    // 当前任务归属的队列名称，如果为新队列，会自动创建
    $queueName = 'workerQueue';

    // 将该任务推送到消息队列，等待对应的消费者去执行
    $isPushed = Queue::push(Worker::class, $data, $queueName);

    // database 驱动时，返回值为 1|false; redis驱动时，返回值为 随机字符串|false
    if ($isPushed !== false) {
        echo '['.$queueName.']'." Job is Pushed to the MQ Success";
    } else {
        echo 'Pushed to the MQ is Error';
    }
}
```
> 浏览器访问推送消息：`http://tp51.env/index/index/queue`

#### 队列的消费与删除

* 终端（terminal）执行命令：`> php think queue:work --daemon --queue workerQueue` 

* 执行（消费）结果：
    ```php
    > php think queue:work --daemon --queue workerQueue
    Processed: app\common\queue\Worker
    Processed: app\common\queue\Worker
    Processed: app\common\queue\Worke
    ```

#### 多任务队列使用
以下为发送邮件队列测试：
```php
/**
 * 测试多任务队列
 * @return string
 */
public function testMultiTaskQueue()
{
    $taskType = MultiTask::EMAIL;
    $data = [
        'email' => 'tinywan@aliyun.com',
        'title' => "邮件标题".rand(111111,999999),
        'content' => "邮件内容".rand(11111,999999)
    ];
    $res = multi_task_Queue($taskType, $data);
    if ($res !== false) {
        return "Job is Pushed to the MQ Success";
    } else {
        return 'Pushed to the MQ is Error';
    }
}
```    

# 服务器

##　nginx、php-fpm、mysql用户权限解析

* 先来做个说明：`nginx`本身不能处理PHP，它只是个web服务器。当接收到客户端请求后，如果是php请求，则转发给php解释器处理，并把结果返回给客户端。如果是静态页面的话，`nginx`自身处理，然后把结果返回给客户端。

* `nginx`下php解释器使用最多的就是`fastcgi`。一般情况`nginx`把`php`请求转发给`fastcgi`（即 php-fpm）管理进程处理，`fastcgi`管理进程选择`cgi`子进程进行处理，然后把处理结果返回给`nginx`。

* 在这个过程中就牵涉到两个用户，一个是`nginx`运行的用户，一个是`php-fpm`运行的用户。如果访问的是一个静态文件的话，则只需要`nginx`运行的用户对文件具有读权限或者读写权限。

* 而如果访问的是一个php文件的话，则首先需要`nginx`运行的用户对文件有读取权限，读取到文件后发现是一个php文件，则转发给`php-fpm`，此时则需要`php-fpm`用户对文件具有有读权限或者读写权限。

## Linux

* 统计8801端口连接数：`	netstat -nat | grep -i "8801" | wc -l`  
* [查看TCP网络连接情况](http://php-note.com/article/detail/143d148cbc86442c91d2554014c8b231):`netstat -n | awk '/^tcp/ {++S[$NF]} END {for(a in S) print a, S[a]}'`
* 查看 Mac/Linux 某端口占用情况
  * 1、lsof -i:端口号
  * 2、netstat -untlp|grep 端口号 
* 软连接：`ln -s a b`
  > a 就是源文件，b是链接文件名，其作用是当进入b目录，实际上是链接进入了a目录
  > 删除软链接：`rm -rf  b  注意不是 rm -rf  b/`
*   

