<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/2 16:42
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use think\Controller;

class Nsq extends Controller
{
    public function index()
    {
        $nsqdAddr = [
            "127.0.0.1:4151",
            "127.0.0.1:4150"
        ];

        $nsq = new \Nsq();
        $isTrue = $nsq->connectNsqd($nsqdAddr);

        for ($i = 0; $i < 6; $i++) {
            $nsq->publish("test", "Hi Tinywan");
        }
        $nsq->closeNsqdConnection();

        // Deferred publish
        //function : deferredPublish(string topic,string message, int millisecond);
        //millisecond default : [0 < millisecond < 3600000]

        $deferred = new \Nsq();
        $isTrue = $deferred->connectNsqd($nsqdAddr);
        for ($i = 0; $i < 20; $i++) {
            $deferred->deferredPublish("test", "message daly", 3000);
        }
        $deferred->closeNsqdConnection();
    }

    public function nsqSubMessage()
    {
        $nsq_lookupd = new \NsqLookupd("127.0.0.1:4161"); //the nsqlookupd http addr
        $nsq = new \Nsq();
        $config = array(
            "topic" => "test",
            "channel" => "struggle",
            "rdy" => 2,                //optional , default 1
            "connect_num" => 1,        //optional , default 1
            "retry_delay_time" => 5000,  //optional, default 0 , if run callback failed, after 5000 msec, message will be retried
            "auto_finish" => true, //default true
        );
        $nsq->subscribe($nsq_lookupd, $config, function ($msg, $bev) {
            echo $msg->payload . "\n";
            echo $msg->attempts . "\n";
            echo $msg->messageId . "\n";
            echo $msg->timestamp . "\n";
        });
    }
}