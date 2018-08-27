<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/27 9:18
 * |  Mail: 756684177@qq.com
 * |  Desc: 单例模式是最常见的模式之一，在Web应用的开发中，常常用于允许在运行时为某个特定的类创建仅有一个可访问的实例。
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\singleton;

class RedisTest
{
    /**
     * @var self[该属性用来保存实例]
     */
    private static $instance;

    /**
     * @var self host
     */
    public  $host;

    /**
     * 构造函数为private,防止创建对象
     * RedisTest constructor.
     */
    private function __construct(){}

    /**
     * Return self instance[创建一个用来实例化对象的方法]
     * @return RedisTest
     */
    public static function getInstance(){
        if(!(self::$instance instanceof self)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 防止对象被复制
     */
    private function __clone()
    {
        trigger_error('Clone is not allowed');
    }
}

$redis1 = RedisTest::getInstance();
var_dump($redis1); // object(patterns\singleton\RedisTest)#47 (1) { ["host"]=> NULL }
$redis2 = RedisTest::getInstance();
var_dump($redis2); // object(patterns\singleton\RedisTest)#47 (1) { ["host"]=> NULL }
if($redis1 === $redis2){  // 是否为同一个对象
    echo '同一个对象实例';
}
$redis1->host = '127.0.0.1';
$redis2->host = '192.168.1.12';
var_dump($redis1->host); //  192.168.1.12
var_dump($redis2->host); // 192.168.1.12
