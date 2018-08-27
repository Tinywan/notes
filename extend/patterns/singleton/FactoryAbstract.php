<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/27 9:40
 * |  Mail: 756684177@qq.com
 * |  Desc: 在很多情况下，需要为系统中的多个类创建单例的构造方式，这样，可以建立一个通用的抽象父工厂方法
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\singleton;

abstract class FactoryAbstract
{
    protected static $instances = array();

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        $className = self::getClassName();
        if (!(isset(self::$instances[$className]) instanceof $className)) {
            self::$instances[$className] = new $className();
        }
        return self::$instances[$className];
    }

    public static function removeInstance()
    {
        $className = self::getClassName();
        if (array_key_exists($className, self::$instances)) {
            unset(self::$instances[$className]);
        }
    }

    // 获取静态方法调用的类名
    final protected static function getClassName()
    {
        return get_called_class();
    }

    final protected function __clone()
    {
    }
}