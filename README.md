ThinkPHP 5.1
===============

## 5.1 版本注意点

* 记录日志，由`use think\Log;`修改为`use think\facade\Log;`
* 队列配置文件的不同
    * 5.0版本：`\application\extra\queue.php`
    * 5.1版本：`\application\config\queue.php`
    * [thinkphp-queue 笔记](https://github.com/coolseven/notes/blob/master/thinkphp-queue/README.md)

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

你就能在终端（terminal）中执行它

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

## 队列

#### 创建队列

队列任务代码:`application\common\queue\Worker.php`  

`workerQueue` 任务队列功能

* 检查数据库状态，如果数据库为链接成功则直接**删除任务**
* 否则，给数据库插入一条记录
* 如果记录插入成功，则直接**删除任务**
* 否则延迟发送4次后不再发送
  
#### 配置队列

配置文件路径：`application\common\queue`，配置如下所示：
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
#### 加入/消费 队列

* 加入队列：`$host/index/index/queue` 
* 消费队列：`php think queue:work --daemon --queue workerQueue` 
