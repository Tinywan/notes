<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/11/17 16:41
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use Delz\XFYun\ApiException;
use Delz\XFYun\Client;
use think\Controller;
use think\facade\Env;

class XfYun extends Controller
{
    const config = [
        'APPID'=>'5befd3dd',
        'APIKEY'=>'432e499711c544b590435bab8e953531',
    ];

    public function index()
    {
        $client = new Client(self::config['APPID'],self::config['APIKEY']);
        try {
            $content = $client->createTTS()->setText('您好')->send();
            $file = fopen( Env::get('ROOT_PATH') . '/public/test.wav',"w");
            fwrite($file, $content);
            fclose($file);
        } catch(ApiException $e) {
            return $e->getMessage();
        }
    }
}