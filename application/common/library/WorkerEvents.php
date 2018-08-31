<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/31 22:28
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library;


use GatewayWorker\Lib\Gateway;
use think\facade\Log;
use think\worker\Application;
use Workerman\Worker;

class WorkerEvents
{
    /**
     * onWorkerStart 事件回调
     * 当businessWorker进程启动时触发。每个进程生命周期内都只会触发一次
     * @access public
     * @param  \Workerman\Worker    $businessWorker
     * @return void
     */
    public static function onWorkerStart(Worker $businessWorker)
    {
        $app = new Application();
        $app->initialize();
    }

    /**
     * onConnect 事件回调
     * 当客户端连接上gateway进程时(TCP三次握手完毕时)触发
     * @access public
     * @param  int       $client_id
     * @return void
     */
    public static function onConnect($client_id)
    {
        Gateway::sendToCurrentClient("Your client_id is $client_id");
    }

    /**
     * onWebSocketConnect 事件回调
     * 当客户端连接上gateway完成websocket握手时触发
     * @param  integer  $client_id 断开连接的客户端client_id
     * @param  mixed    $data
     * @return void
     */
    public static function onWebSocketConnect($client_id, $data)
    {
        Log::info('[WebSocket connect]:'.$client_id);
        var_export($data);
    }

    /**
     * onMessage 事件回调，当客户端发来数据(Gateway进程收到数据)后触发
     * @param $client_id
     * @param $data
     * @throws \Exception
     */
    public static function onMessage($client_id, $data)
    {
        echo 'onMessage 事件回调: '.$client_id;
        Gateway::sendToAll($data);
    }

    /**
     * onClose 事件回调 当用户断开连接时触发的方法
     * @param integer $client_id 断开连接的客户端client_id
     * @throws \Exception
     */
    public static function onClose($client_id)
    {
        GateWay::sendToAll("client[$client_id] logout\n");
    }

    /**
     * onWorkerStop 事件回调
     * 当businessWorker进程退出时触发。每个进程生命周期内都只会触发一次。
     * @param  \Workerman\Worker    $businessWorker
     * @return void
     */
    public static function onWorkerStop(Worker $businessWorker)
    {
        echo "WorkerStop\n";
    }
}