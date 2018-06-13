<?php

namespace app\common\controller;

use think\Controller;
use think\Request;

class BaseController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initialize()
    {
        parent::initialize();
    }

    protected function sendSmsCode($mobile, $type)
    {

    }
}
