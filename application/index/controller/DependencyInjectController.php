<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/23
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use demo\Car;
use demo\DriverInterface;
use demo\MyDrive;
use patterns\decorator\Canvas;
use patterns\decorator\ColorDrawDecorator;
use patterns\decorator\SizeDrawDecorator;
use patterns\di\Comment;
use patterns\di\EmailSenderInterface;
use patterns\di\GmailSender;
use patterns\di\TencentSender;
use patterns\singleton\FirstProduct;
use patterns\singleton\RedisTest;
use patterns\singleton\SecondProduct;
use patterns\strategy\FemaleUserStrategy;
use patterns\strategy\MaleUserStrategy;
use patterns\strategy\ShowPage;
use think\Container;
use think\Controller;

class DependencyInjectController extends Controller
{
    /**
     * 使用容器
     */
    public function index(){
        Container::getInstance()->bindTo('car',Car::class);
        $car = Container::get('car');
        $res = $car->test();
        halt($res);
    }

    /**
     * 使用非静态方法实例化一个容器
     */
    public function index1(){
        $container = new Container();
        $res = $container->instance('car',new Car());
        $car = $container->get();
        halt($res);
    }

    public function index2(){
        bind('car',Car::class); // Cannot instantiate interface demo\Driver
        // 1 $driver = new ManDriver
        // 2 $car = new Car($driver)
        $car = app('car');
        $res = $car->run();
        halt($res);
    }

    public function index3(){
        Container::getInstance()->bindTo('car',Car::class);
        Container::set(DriverInterface::class,MyDrive::class);
        $car = app('car');
        $res = $car->run();
        halt($res);
    }

    /**
     * 注册依赖关系
     * 如果注入一个接口的话，如果实例化一个具体的类，需要注册依赖关系
     */
    public function index4(){
        Container::getInstance()->bindTo('comment',Comment::class);
        Container::set(EmailSenderInterface::class,GmailSender::class);
        $car = app('comment');
        $res = $car->save();
        halt($res);
    }

    public function index5()
    {
        Container::set(EmailSenderInterface::class,TencentSender::class);
        $comment = Container::get('comment');
        $res = $comment->save();
        halt($res);
    }

    public function yafConf()
    {
        $movie = \Yaconf::get('movie');
        print_r($movie);
    }

    /**
     * 策略模式
     * http://notes.env/index/dependency_inject/strategyDemo
     */
    public function strategyDemo()
    {
        $show = new ShowPage();
        if(isset($_GET['female'])){
            $show->setStrategy(new FemaleUserStrategy());
        }else{
            $show->setStrategy(new MaleUserStrategy());
        }
        $show->show();
    }

    /**
     * http://notes.env/index/dependency_inject/CanvasDemo
     */
    public function CanvasDemo()
    {
        $canvas = new Canvas();
        $canvas->init();
        $canvas->rect(3,6,4,12);
        $canvas->addDecorator(new ColorDrawDecorator('green'));
        $canvas->addDecorator(new SizeDrawDecorator('40px'));
        $canvas->draw();
    }

    public function containerDemo01()
    {
        var_dump(get_class(Container::getInstance()));
        echo '---------------';
        var_dump(get_class(Container::getInstance()));
    }

    public function singletonDemo01()
    {
        $redis1 = RedisTest::getInstance();
        var_dump($redis1);
        $redis2 = RedisTest::getInstance();
        var_dump($redis2);
        if($redis1 === $redis2){
            echo '同一个对象实例';
        }
        $redis1->host = '127.0.0.1';
        $redis2->host = '192.168.1.12';
        var_dump($redis1->host); //  192.168.1.12
        var_dump($redis2->host); // 192.168.1.12
    }

    public function singletonDemo02()
    {
        FirstProduct::getInstance()->a[] = 1;
        SecondProduct::getInstance()->a[] = 2;
        FirstProduct::getInstance()->a[] = 11;
        SecondProduct::getInstance()->a[] = 22;

        print_r(FirstProduct::getInstance()->a);
        // Array ( [0] => 1 [1] => 11 )
        print_r(SecondProduct::getInstance()->a);
        // Array ( [0] => 2 [1] => 22 )
    }
}
