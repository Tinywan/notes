<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/10 6:52
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use GatewayClient\Gateway;
use think\Controller;
use think\facade\Request;
use think\Log;

class ChatRoomController extends Controller
{
    public $userId = 2589272172487;

    public $userName = 'Tinywan';

    public $roomId = 'L06777';

    //
    public function index()
    {
        setcookie('username',md5('Tinywan'),36000);
        return $this->fetch();
    }

    /*
     * 用户登录后初始化以及绑定client_id
     */
    public function bind()
    {
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
        Gateway::$registerAddress = '127.0.0.1:1238';
        $uid = $this->userId;
        $group_id = $this->groupId;
        $client_id = request()->param('client_id');
        // client_id与uid绑定
        Gateway::bindUid($client_id, $uid);
        // 加入某个群组（可调用多次加入多个群组）
        Gateway::joinGroup($client_id, $group_id);
    }

    /**
     * 利用GatewayClient发送 系统通告信息
     * curl http://notes.env/index/chat_room/sendMessage
     */
    public function sendMessage()
    {
        // stream_socket_client(): unable to connect to tcp://127.0.0.1:1236
        $uid = $this->userId;
        $roomId = $this->roomId;
        $serverTime = date('Y-m-d H:i:s', time());
        $resData = [
          'type' => 'notice',
          'roomId' => $roomId,
          'userName' => $this->userName,
          'msg' => "这是系统公告信息！发送给客户端的消息", // 发送给客户端的消息，而不是聊天发送的内容
          'joinTime' => $serverTime // 加入时间
        ];
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
        Gateway::$registerAddress = '127.0.0.1:1238';
        // 向任意群组的网站页面发送数据，如果开启，则会向页面发送两条一样的消息
        Gateway::sendToGroup($roomId, json_encode($resData));
    }
}