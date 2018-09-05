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
use redis\BaseRedis;
use think\facade\Log;
use think\worker\Application;
use Workerman\Lib\Timer;
use Workerman\Worker;

class WorkerEvents
{
    /**
     * onWorkerStart 事件回调
     * 当businessWorker进程启动时触发。每个进程生命周期内都只会触发一次
     * @access public
     * @param  \Workerman\Worker $businessWorker
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
     * @param  int $client_id
     * @return void
     */
    public static function onConnect($client_id)
    {
        Gateway::sendToCurrentClient("Your client_id is $client_id");
        // 每10s 检查客户端是否有uid 属性,需要30秒内发认证并删除定时器阻止关闭连接的执行
        $_SESSION['uid'] = Timer::add(3, function ($client_id) {
            Gateway::closeClient($client_id);
        }, [$client_id], false);
    }

    /**
     * onWebSocketConnect 事件回调
     * 当客户端连接上gateway完成websocket握手时触发
     * @param  integer $client_id 断开连接的客户端client_id
     * @param  mixed $data
     * @return void
     */
    public static function onWebSocketConnect($client_id, $data)
    {
        Log::info('[WebSocket connect]:' . $client_id);
        var_export($data);
    }

    /**
     * onMessage 事件回调，当客户端发来数据(Gateway进程收到数据)后触发
     * @param $client_id
     * @param $data
     * @return bool
     */
    public static function onMessage($client_id, $data)
    {
        echo 'onMessage 事件回调: ' . $client_id;
        $data = json_decode($data, true);
        switch ($data['type']) {
            case 'auth':
                self::auth($data, $client_id);
                break;
            case 'ping':
                self::ping($client_id);
                break;

            default:
                return false;
        }
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
     * @param  \Workerman\Worker $businessWorker
     * @return void
     */
    public static function onWorkerStop(Worker $businessWorker)
    {
        echo "WorkerStop\n";
    }

    /**
     * 发送消息
     * @param $message
     * @return bool
     */
    private static function send($type, $code, $message, $data = [], $is_object = true)
    {
        if (empty($data) && $is_object) {
            $data = (object)$data;
        }
        $msg = json_encode([
            'code' => $code,
            'msg' => $message,
            'type' => $type,
            'data' => $data
        ]);

        Log::info('[Send client message][' . $_SESSION['client_id'] . ']:' . $msg);
        return Gateway::sendToCurrentClient($msg);
    }

    /**
     * 用户认证
     * @param $data
     * @return bool|void
     */
    private static function auth($data, $client_id)
    {
        var_dump($data);
        if (empty($data['token'])) {
            self::send('error', -1, 'Missing parameters token');
            return Gateway::closeClient($client_id);
        }

        //判断登录状态
        $token = rsa_decode($data['token']);
        $hashKey = "APP_LOGIN:" . $token;
        $redis = BaseRedis::location();
        $is_exists = $redis->exists($hashKey);
        if (!$is_exists) {
            self::send('error', -1, '登录状态失效');
            return Gateway::closeClient($client_id);
        }
        $tokenData = $redis->hGetAll($hashKey);
        //绑定用户
        $uid = $tokenData['app_id'] ?? '';
        Gateway::bindUid($client_id, $uid);
        //写入Session
        $_SESSION['uid'] = $uid;
        //$_SESSION['timestamp'] = $tokenData->timestamp;
        $_SESSION['login_token'] = $hashKey;
        return self::send('auth', 0, '账号认证通过');
    }

    /**
     * ping检测
     * @param $client_id
     */
    private static function ping($client_id)
    {
        if (empty($_SESSION['uid'])) {
            self::send('error', -1, '请求未认证，请先认证');
            return Gateway::closeClient($client_id);
        }
        $redis_token = $_SESSION['login_token'] ?? '';
        $is_exists = BaseRedis::location()->exists($redis_token);
        if (!$is_exists) {
            self::send('error', -1, '登录状态失效');
            return Gateway::closeClient($client_id);
        }
    }
}