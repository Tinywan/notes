<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/9 18:05
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 聊天室-处理逻辑接口
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\controller\v1;

// 使用Gatewayworker实现
// Redis里有三个表：
// chatting:historyUserIds:* 与会员聊天的历史uid，不会自动清空,
// chatting:historyUserInfo:* 与会员聊天的历史uid和个人姓名头像等信息，不会自动清空,
// chatting:message:* 聊天记录，*部分结构如:15666323771_15666323772（小数在前），聊天双方下线后清空并记录到db， 方法：handleOffline
// Db里两个表：
// chat_messages 聊天记录表，如果对不在线的聊天，会记录到这里
// chat_histories 聊天历史会员，暂未实现。

use app\api\service\UserService;
use GatewayClient\Gateway;
use think\Db;
use think\Exception;
use think\facade\Request;

class ChatController
{
    // 用户信息
    public $user;

    // 默认用户组
    public $default_group_name = 'default';

    public function __construct()
    {
        $user = UserService::getUserInfo();
        $this->user = [
          'username' => $user->nickname,
          'name' => $user->nickname,
          'avatar' => $user->avatar
        ];

        Gateway::$registerAddress = '127.0.0.1:1236';
    }

    // 用户登录后初始化
    public function bind(Request $request)
    {
        $uid = $this->user['username'];
        $client_id = $request->input('client_id');
        Gateway::bindUid($client_id, $uid);
        // Gateway::joinGroup($client_id, $this->default_group_name);
        Gateway::sendToUid($uid,'欢迎使用聊天系统'.$this->user['name']);
        Gateway::setSession($client_id,[
          'uid' =>  $this->user['username'],
          'client_id' =>  $client_id,
          'name' => $this->user['name'],
          'avatar' => $this->user['avatar'],
          'time' => date('Y-m-d H:i:s'),
        ]);
    }

    public function say(Request $request)
    {
        if($request->input('msg') == false) throw new Exception('请输入聊天内容');
        $message = htmlspecialchars($request->input('msg'));
        $from_uid = $request->input('from_uid');
        $to_uid = $request->input('to_uid');
        $to_name = $request->input('to_name');
        $chat_message = [
          'msg' => $message,
          'from_uid' => $from_uid,
          'to_uid' => $to_uid,
          'name' => $this->user['name'], // 发送者的名字
          'avatar' => $this->user['avatar'], // 发送者的头像
        ];
        if(Gateway::isUidOnline($to_uid)){
            Gateway::sendToUid($to_uid, ChatLogic::formatMessage($chat_message));
        }else{
            ChatLogic::saveMessageToDbIfUserOffline($from_uid,$this->user['name'],$to_uid,$to_name,$message);
        }
        ChatLogic::cacheChatMessage($from_uid,$this->user['name'],$this->user['avatar'],$to_uid,$to_name,$message);
    }
    // 获取聊天历史用户列表
    public function getHistoryList()
    {
        return ChatLogic::getHistoryList($this->user['username']);
    }
    // 获取所有课聊天的用户列表 用于选择后进行聊天
    public function getOnlineUsers()
    {
        $clients_ids = Gateway::getAllClientSessions();
        $users = [];
        if(!empty($clients_ids))
            foreach ($clients_ids as $cid => $v){
                if(isset($v['uid']))
                    $users[$v['uid']] = [
                      'name' => $v['name'],
                      'uid' => $v['uid'],
                      'avatar' => $v['avatar'],
                      'time' => $v['time'],
                    ];
            }
        return json_encode($users);
    }

    // 登录后获取未读记录
    public function fetchMessage()
    {
        $to_uid = UserService::getUserName();
        $list = DB::table('chat_messages')->where('user1',$to_uid)->where('unread',1)->orderBy('id','desc')->get();
        $res = [];
        foreach ($list as $v){
            $content = json_decode($v->content,true);
            $res[] = [
              'name' => $content['from_name'],
              'content' => $content['message'],
              'type' => 'text',
              'time' => $content['time'],
              'to_uid' => $content['to_uid'],
              'from_uid' => $content['from_uid'],
            ];
        }
        // 清空未读
        Db::name('chat_messages')->where('user1',$to_uid)->where('unread',1)->update(['unread'=> 0 ]);
        return json_encode($res);
    }

    // 收藏聊天记录
    public function collect(Request $request)
    {
        $content = $request->input('content');
        if($id = Db::name('chat_collections')->insertGetId([
          "to_uid" => $content['to_uid'],
          "from_uid" => $content['from_uid'],
          "from_name" => $content['name'],
          "content" => $content['content'],
          "created_at" => $content['time']
        ])) return $id;
        else return 'error';
    }

    // 删除收藏记录
    public function collectDel(Request $request)
    {
        $id = $request->input('id');
        if( Db::name('chat_collections')->where('to_uid',UserService::getUserName())->where('id',$id)->delete() ) return 'success';
        else return 'error';
    }

    public function getCollection(Request $request)
    {
        $from_uid = $request->input('uid');
        return Db::name('chat_collections')->where('to_uid',UserService::getUserName())->where('from_uid',$from_uid)->get();
    }
}