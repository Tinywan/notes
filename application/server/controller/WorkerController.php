<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/3 14:52
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace  app\server\controller;

use think\facade\Config;
use think\facade\Log;
use think\worker\Server;
use Workerman\Lib\Timer;

class WorkerController extends Server
{
    // 配置信息
    protected $config;

    protected $socket = 'websocket://127.0.0.1:12306';

    /**
     * Workerman 启动的回调,这里传递的是Worker对象
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
        echo "onWorkerStart success\n";
        // 初始化一些参数
        $this->config = Config::get('msg.workerman');
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
        Log::error('-------onConnect-----------'.json_encode($connection));
        // 每10s 检查客户端是否有name属性
        Timer::add(10, function () use ($connection) {
            if (!isset($connection->user_name)) {
                $connection->close("auth timeout and close");
            }
        }, null, false);
    }

    /**
     * 接受发送消息
     * @param $connection
     * @param $message
     */
    public function onMessage($connection, $message)
    {
        Log::error('-------onConnect22-----------'.json_encode($connection));
        Log::error('-------message22-----------'.json_encode($message));
        $clientData = json_decode($message, true);
        if (!isset($connection->user_name)) {
            if (!isset($clientData['user_name']) || !isset($clientData['password'])) {
                return $connection->close("auth fail and close");
            }
            // 如果客户端user_name存在，mysql，这里使用动态给对象赋值属性user_name,标记该对象已经通过验证
            $connection->user_name = $clientData['user_name'];
//            $room_id = $clientData['room_id'];
            //统计客户端的信息等业务,进入房间的人数增长，自增
//            MsgRedis::saveLateComments('L80001',json_encode($clientData['content']));
            // 广播给所有用户，该用户加入
            $sendData = json_encode(['type' => $clientData['type'],
                'data' => $connection->user_name,
                'create_time' => date('Y-m-d H:i:s', time()),
                'content' => "加入房间",
                'errcode' => 0,
                'errmsg' => ''
            ]);
            return $this->broadcast($sendData);
        }
        /**
         * 根据消息类型分发消息
         */
        if ($clientData['type'] == 'say') {
            $sendData = json_encode(['type' => 'say',
                'data' => $connection->user_name,
                'create_time' => date('Y-m-d H:i:s', time()),
                'content' => $clientData['content'],
                'errcode' => 0,
                'errmsg' => ''
            ]);
            return $this->broadcast($sendData);
        } elseif ($clientData['type'] == 'left') {
            $sendData = json_encode(['type' => 'left',
                'data' => $connection->user_name,
                'create_time' => date('Y-m-d H:i:s', time()),
                'content' => $clientData['content'],
                'errcode' => 0,
                'errmsg' => ''
            ]);
            return $this->broadcast($sendData);
        } else {
            $sendData = json_encode(['type' => '123',
                'data' => $connection->user_name,
                'create_time' => date('Y-m-d H:i:s', time()),
                'content' => "Redis数据",
                'errcode' => 0,
                'errmsg' => ''
            ]);
            return $this->broadcast($sendData);
        }
    }

    /**
     * 发送信息
     * @param $msg
     */
    public function broadcast($msg)
    {
        /**
         * 引入$worker 对象 在这里直接这样子使用 $this->worker 就可以了
         *  $worker->connections 为客户端连接的所有对象
         */
        foreach ($this->worker->connections as $connection) {
            if (!isset($connection->user_name)) {
                //忽略掉
                continue;
            }
            $connection->send($msg);
        }
    }

    /**
     * 返回客户端json格式信息
     * @param $data
     * @param int $errCode
     * @param string $errMsg
     * @return string
     */
    public static function returnJson($data, $errCode = 0, $errMsg = '')
    {
        return json_encode(['data' => $data, 'errcode' => $errCode, 'errmsg' => $errMsg]);
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
        $connection->send("connection close \n");
    }

    /**
     * Workerman 停止回调
     * @param $worker
     */
    public function onWorkerStop($worker)
    {
        echo "onWorkerStop success\n";
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }
}