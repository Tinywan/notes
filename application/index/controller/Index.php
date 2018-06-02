<?php
namespace app\index\controller;

use think\facade\Log;

class Index
{
    public function index()
    {
        Log::error("1111111111111111111111111");
        return "Hi";
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }
}
