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
use patterns\di\Comment;
use patterns\di\EmailSenderInterface;
use patterns\di\GmailSender;
use patterns\di\TencentSender;
use think\Container;

class DependencyInjectController
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
}
