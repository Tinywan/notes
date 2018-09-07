<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/9/7 22:34
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use app\common\controller\FrontendController;
use think\facade\Log;

class OrderController extends FrontendController
{
    // 解决订单重复提交问题
    public function pay()
    {
        $postData = [
          'mch_id'=>12001,
          'card_no'=>6222021607004965795,
          'total_fee'=>995.0,
        ];
        $redis = location_redis();
        // 防止订单重复提交问题
        $lock = 'ORDER_LOCK:'.md5($postData['mch_id'].$postData['card_no'].$postData['total_fee']);
        // 检测是否被锁
        if($redis->get($lock)){
            return "该卡号金额正在在进行，订单重复了";
        }
        // 针对当前请求添枷锁
        if(!$redis->incr($lock)){
            return "枷锁失败";
        }
        $order_no = 'D' . $postData['mch_id'] . date('ymdHis', time()) . rand(1000, 9999);
        return '创建订单 '.$order_no;
    }

    public function notify()
    {
        $postData = [
          'mch_id'=>12001,
          'card_no'=>6222021607004965795,
          'total_fee'=>995.0,
        ];
        $redis = location_redis();
        $order_no = 'D' . $postData['mch_id'] . date('ymdHis', time()) . rand(1000, 9999);

        // 更具订单号查询mch_id、卡号、金额
        $lock = 'ORDER_LOCK:'.md5($postData['mch_id'].$postData['card_no'].$postData['total_fee']);
        var_dump($lock);
        $unlock = $redis->del($lock);
        Log::debug('释放锁结果：'.$unlock);
        return '异步回调成功';
    }

}