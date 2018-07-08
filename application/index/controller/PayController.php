<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/14 20:59
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use app\common\model\Order;
use think\Controller;
use think\facade\Log;
use Yansongda\Pay\Pay;

class PayController extends Controller
{
    public function index()
    {
        $order = [
            'out_trade_no' => rand(11111, 99999) . time(),
            'total_amount' => rand(11, 99),
            'subject' => '商品测试001',
        ];
        Log::error('-----------' . json_encode($order));
        $alipay = Pay::alipay(config('pay.alipay'))->web($order);
        return $alipay->send();
    }

    /**
     * 支付信息
     * @return mixed
     */
    public function test()
    {
        $order_no = 'S' . date('ymdHis', time()) . rand(1000, 9999);
        $insertData = [
            'mch_id' => '2025801203065130',
            'order_no' => $order_no,
            'total_fee' => rand(11, 99),
            'goods' => '商品测试00' . rand(1111, 9999),
        ];
        $res = Order::create($insertData);
        if ($res) {
            $payOrder = [
                'out_trade_no' => $insertData['order_no'],
                'total_amount' => $insertData['total_fee'],
                'subject' => $insertData['goods'],
            ];
            $alipay = Pay::alipay(config('pay.alipay'))->web($payOrder);
            return $alipay->send();
        }
        halt($res);
    }

    public function tt(){
        $res = curl_request('http://openapi.tinywan.com/v1/gateway.do',['age'=>123]);
        halt($res);
    }

    public function testRedis()
    {
        var_dump(config('redis.message'));
        $redis = location_redis();
        $redis->set("UserName", 'Tinywan11111');
        halt($redis);
    }

    /**
     * 签名
     */
    public function sign($data){
        //解码
        foreach ($data as $key => &$value) {
            $value = urldecode($value);
        }
        unset($value);

        //如果有sign，去除sign
        if (isset($data['sign'])){
            unset($data['sign']);
        }

        //商户秘钥
        $key = '121111as2d12a2s1da1das';

        //数组正序排序
        ksort($data);

        //拼接
        $params_str = urldecode(http_build_query($data));

        //拼接商户秘钥在最后面
        $params_str =  $params_str.'&key='.$key;

        //返回md5结果
        return md5($params_str);
    }

    public function request($api_name, $data)
    {
        $gate_way_url = 'http://openapi.tinywan.com/v1/gateway.do'; //网关
        $mch_id = '12306';

        $data = [
            'mch_id' => $mch_id,
            'method' => $api_name,
            'version' => '1.0',
            'timestamp' => time(),
            'content' => json_encode($data)
        ];

        $sign = $this->sign($data);
        if (!$sign) {
            exit('签名失败');
        }
        $data['sign'] = $sign;

        //将所有参数urlencode编码，防止中文乱码
        foreach ($data as &$item) {
            $item = urlencode($item);
        }
        unset($item);

        $result = curl_request($gate_way_url, $data); //post请求
        return json_decode($result, true);
    }

    public function testCurl()
    {
        $result = $this->request('shop.payment.gateWay', [
            'total_fee' => 1,
            'goods' => '银联测试',
            'order_sn' => 1233211234567,
            'bank_code' => '03080000',
            'client' => 'web',
            'client_ip' => '127.0.0.1',
            'notify_url' => 'http://baidu.com',
            'return_url' => 'http://baidu.com',
        ]);
        halt($result);
    }
}