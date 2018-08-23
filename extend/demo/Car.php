<?php
/**
 * Created by PhpStorm.
 * User: tinyw
 * Date: 2018/8/23
 * Time: 9:13
 */

namespace demo;


class Car
{
    private $driver = null;

    // 解决强关联，使用接口解决
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function run()
    {
        $this->driver->drive();
    }

    public function test()
    {
        return "tes11111111t";
    }
}