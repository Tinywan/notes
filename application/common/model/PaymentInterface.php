<?php

namespace app\common\model;

use think\Db;
use think\Log;
use think\Model;
use traits\model\SoftDelete;

class PaymentInterface extends Model
{
    use SoftDelete;

    protected $autoWriteTimestamp = true;

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = "delete_time";

    /**
     * 自动对账任务
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function autoReconcileJob()
    {
        Log::debug('[自动对账] 开始...');
        // 查询所有商户
        $merchantList = MerchantPaymentInterface::where([
            'status'=>1,
            'is_master'=>1
        ])->select();
        Log::debug('[自动对账] 所有商户'.json_encode($merchantList));
        foreach ($merchantList as $v)
        {
            $mch_id = $v['mch_id'];
            $order = Db::name('interface_order')->where([
                'mch_id'=>$mch_id,
                'type'=>1,
                'status'=>2
            ])->find();

            $content = [
                'mch_order_no' => '82131534210515',
                'mch_id' => $mch_id,
            ];

            $data = [
                'mch_id' => $mch_id,
                'method' => 'shop.agent.query',
                'version' => '1.0',
                'timestamp' => time(),
                'content' => json_encode($content)
            ];

            $sign = merchant_sign($data, $mch_id);
            if (!$sign) {
                Log::error('[自动对账] 签名失败');
            }
            $data['sign'] = $sign;

            foreach ($data as &$item) {
                $item = urlencode($item);
            }
            unset($item);

            $result = curl_post($url = 'https://pay.hongnaga.com/api/gateway',$data);
            Log::debug('[自动对账] 订单查询结果'.$result);
            $arr = json_decode($result,true);
            if(!$arr['success']){
                Log::error('[自动对账] 订单查询异常');
                return false;
            }

            $paymentInfo = $this->where(['id'=>$v['payment_interface_id']])
                ->field('total_payment_reserve,total_profit,cash_profit')
                ->find();
            Log::debug('[自动对账] 代付商户信息'.json_encode($paymentInfo));
            $allAmount = $paymentInfo['total_payment_reserve'] + $paymentInfo['total_profit'] + $paymentInfo['cash_profit'];
            $apiAllAmount = $arr['data']['balance'];
            Log::debug('[自动对账] 代付商户总备付金余额为：'.$allAmount);
            Log::debug('[自动对账] 接口查询备付金余额为：'.$apiAllAmount);
            if($allAmount != $apiAllAmount){
                // 自动锁定账户不可以继续交易
                $res = $this->where(['id'=>$v['payment_interface_id']])->setField('status',0);
                Log::debug('[自动对账] 自动锁定账户结果 '.json_encode($res));
            }else{
                Log::debug('[自动对账] 交易平衡 ');
            }
            return;
        }
    }
}
