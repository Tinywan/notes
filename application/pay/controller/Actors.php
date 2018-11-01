<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/8 12:15
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\controller;


use app\common\controller\PayController;
use app\common\library\repositories\eloquent\ActorRepository;

class Actors extends PayController
{
    /**
     * @var $actor
     */
    private $actor;

    public function __construct(ActorRepository $actorRepository)
    {
        $this->actor = $actorRepository;
    }

    public function index()
    {
        $data = [
          'status'=>1,
          'username'=>'Tinywan'.rand(1111,3333),
          'password'=>123456,
        ];
        return json($this->actor->delete(10));
    }

    public function postre(){
        $url = "http://openapi.tinywan.com/v1/gateway.do";
        $res = curl_request($url,[]);
        halt($res);
    }
}