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
    public $userId = 12;

    public $groupId = 1;

    //
    public function index()
    {
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

    // mvc后端发消息 利用GatewayClient发送 Events.php
    // {"type":"say","roomId":"1002","userId":"88","content":"Welcome Live Room"}
    public function sendMessage()
    {
        // stream_socket_client(): unable to connect to tcp://127.0.0.1:1236
        $uid = $this->userId;
        $group = $this->groupId;
        $message = json_encode([
          'type'=>'say',
          'msg'=>'mvc后端发消息 Hello ThinkPHP5'
        ]);
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
        Gateway::$registerAddress = '127.0.0.1:1238';
        // 向任意uid的网站页面发送数据
        Gateway::sendToUid($uid, $message);
        json_encode($uid,JSON_FORCE_OBJECT);
        // 向任意群组的网站页面发送数据，如果开启，则会向页面发送两条一样的消息
        //Gateway::sendToGroup($group, $message);
    }
}