ThinkPHP 5.1
===============

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
