<?php

namespace app\backend\controller;

use app\common\controller\BaseBackendController;

class SystemController extends BaseBackendController
{
    public function config()
    {
       return $this->fetch();
    }
}
