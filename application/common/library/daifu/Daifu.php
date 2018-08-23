<?php

namespace app\common\library\daifu;

use think\facade\Log;
use think\facade\Validate;
use traits\controller\Jump;

/**
 * 银盈通代付
 * Class Daifu
 * @package app\common\library
 */
class Daifu
{
    use Jump;

    private $config;
    private $m2;

    public function setConfig($config)
    {
        $this->config = $config;
        $this->m2 = new M2base($config);
    }

    /**
     * 获取联行号
     */
    public function getBankId($option)
    {
        if (empty($option['scene'])) {
            return $this->response(-1, '场景类型编码不能为空');
        }
        $data['scene'] = $option['scene'];

        if (isset($option['province_code'])) {
            $data['province_code'] = $option['province_code'];
        }
        if (isset($option['city_code'])) {
            $data['city_code'] = $option['city_code'];
        }
        if (isset($option['bank_id'])) {
            $data['bank_id'] = $option['bank_id'];
        }

        $D = $data;

        $D['app_code'] = 'apc_02000000041';            //应用号
        $D['app_version'] = '1.0.0';                    //应用版本
        $D['service_code'] = 'sne_00000000002';        //服务号
        $D['plat_form'] = '01';                        //平台
        $D['login_token'] = '';                        //登录令牌
        $D['req_no'] = date('YmdHis', time());            //请求流水号
        $D['payment_channel_list'] = array();

        $data = $this->m2->url_data('BANKID', $D, "POST");

        $res = json_decode($data, true);

        //此处处理同步返回的接口信息
        var_dump($res);
        die;
    }

    /**
     * 代付
     * @param $option
     * @return mixed
     */
    public function paying($option)
    {
        $vail_rule = [
            'order_no' => 'require',
            'pay_password' => 'require',
            'attr_type' => 'require',
            'username' => 'require|chsAlpha',
            'amount' => 'require|between:1,10000',
            'card_no' =>  'require|number',
            'bank_name' => 'require|chsAlpha',
        ];
        $vail_field = [
            'order_no' => '订单号',
            'pay_password' => '支付密码',
            'attr_type' => '收款人账户类型',
            'username' => '收款人姓名',
            'amount' => '代付金额',
            'card_no' => '收款人银行卡号',
            'bank_name' => '银行卡支行',
        ];
        $message = [
            'order_no.require'=>'订单号不能为空',
            'pay_password.require'=>'支付密码不能为空',
            'attr_type.require'=>'收款人账户类型不能为空',
            'username.require'=>'收款人姓名不能为空',
            'username.chsAlpha'=>'收款人姓名只能是汉字、字母',
            'amount.require'=>'收款人姓名只能是汉字、字母',
            'card_no.require'=>'收款人银行卡号不能为空',
            'card_no.number'=>'收款人银行卡号必须是数字',
            'bank_name.require'=>'银行卡支行不能为空',
        ];
        $validate = new Validate($vail_rule,$message,$vail_field);
        if (!$validate->check($option)) {
            return $this->response(-1, $validate->getError());
        }

        // 对公代付暂时不需用
        if ($option['attr_type'] == '02') {
            $vail_rule['headquarters_bank_id'] = 'require';
            $vail_rule['issue_bank_id'] = 'require';

            $vail_field['headquarters_bank_id'] = '收款银行卡总行联行号';
            $vail_field['issue_bank_id'] = '收款银行卡支行联行号';
        }

        $data['merchant_number'] = $option['merchant_number'];
        $data['order_number'] = $option['order_no'];
        $data['wallet_id'] = $option['wallet_id'];
        $data['asset_id'] = $option['asset_id'];
        $data['business_type'] = 1; // 业务类型
        $data['money_model'] = 1; // 资金模式
        $data['source'] = 0; // 代付渠道
        $data['password_type'] = $option['password_type'];
        $data['encrypt_type'] = $option['encrypt_type'];
        $data['pay_password'] = md5($option['pay_password']);
        $data['customer_type'] = $option['attr_type'];
        $data['customer_name'] = $option['username'];
        $data['currency'] = 'CNY';
        $data['amount'] = $option['amount'];
        $data['async_notification_addr'] = $option['notify'];
        $data['account_number'] = $option['card_no'];
        $data['issue_bank_name'] = $option['bank_name']; // 收款银行卡发卡行名称
        //$data['memo'] = '代付备注摘要'; // 备注摘要

        $data['app_code'] = 'apc_02000000041';            //应用号
        $data['app_version'] = '1.0.0';                    //应用版本
        $data['service_code'] = 'sne_00000000002';        //服务号
        $data['plat_form'] = '01';                        //平台
        $data['login_token'] = '';                        //登录令牌
        $data['req_no'] = date('YmdHis', time());            //请求流水号
        $data['payment_channel_list'] = array();
        $data = $this->m2->url_data('PAYING', $data, "POST");
        $res = json_decode($data, true);

        $result['order_no'] = $option['order_no'];
        Log::debug(' 银盈通支付接口返回数据 ' . json_encode($data));
        // 接口请求成功
        if ($res['op_ret_code'] == '000') {
            $result['status'] = 'success';
            $result['amount'] = $res['amount'];
            $result['req_no'] = $res['req_no'];
            return $result;
        } else {
            Log::error('美付宝-代付接口调用失败' . json_encode($res));
            $result['status'] = 'fail';
            $result['err_msg'] = '代付系统内部错误，请联系开发人员';
            return $result;
        }
    }

    /**
     * 新代付接口
     * @param $option
     * @return mixed
     */
    public function newPaying($option)
    {
        $vail_rule = [
            'order_no' => 'require',
            'pay_password' => 'require',
            'attr_type' => 'require',
            'username' => 'require|chsAlpha',
            'amount' => 'require|between:1,50000',
            'card_no' =>  'require|number',
            'bank_name' => 'require|chsAlpha',
        ];
        $vail_field = [
            'order_no' => '订单号',
            'pay_password' => '支付密码',
            'attr_type' => '收款人账户类型',
            'username' => '收款人姓名',
            'amount' => '代付金额',
            'card_no' => '收款人银行卡',
            'bank_name' => '收款银行卡发卡行名称',
        ];
        $message = [
            'order_no.require'=>'订单号不能为空',
            'pay_password.require'=>'支付密码不能为空',
            'attr_type.require'=>'收款人账户类型不能为空',
            'username.require'=>'收款人姓名不能为空',
            'username.chsAlpha'=>'收款人姓名只能是汉字、字母',
            'amount.require'=>'收款人姓名只能是汉字、字母',
            'card_no.require'=>'收款人银行卡号不能为空',
            'card_no.number'=>'收款人银行卡号必须是数字',
            'bank_name.require'=>'银行卡支行不能为空',
        ];
        $validate = new Validate($vail_rule,$message,$vail_field);
        if (!$validate->check($option)) {
            return $this->response(-1, $validate->getError());
        }
        $data['merchant_number'] = $option['merchant_number'];
        $data['order_number'] = $option['order_no'];
        $data['wallet_id'] = $option['wallet_id'];
        $data['asset_id'] = $option['asset_id'];
        $data['business_type'] = 1; // 业务类型
        $data['money_model'] = 1; // 资金模式
        $data['source'] = 0; // 代付渠道
        $data['password_type'] = $option['password_type'];
        $data['encrypt_type'] = $option['encrypt_type'];
        $data['pay_password'] = md5($option['pay_password']);
        $data['customer_type'] = $option['attr_type'];
        $data['customer_name'] = $option['username'];
        $data['currency'] = 'CNY';
        $data['amount'] = $option['amount'];
        $data['async_notification_addr'] = $option['notify'];
        $data['account_number'] = $option['card_no'];
        $data['issue_bank_name'] = $option['bank_name']; // 收款银行卡发卡行名称
        //$data['memo'] = '代付备注摘要'; // 备注摘要

        $data['app_code'] = 'apc_02000000041';            //应用号
        $data['app_version'] = '1.0.0';                    //应用版本
        $data['service_code'] = 'sne_00000000002';        //服务号
        $data['plat_form'] = '01';                        //平台
        $data['login_token'] = '';                        //登录令牌
        $data['req_no'] = date('YmdHis', time());            //请求流水号
        $data['payment_channel_list'] = array();
        Log::debug('【新】- 请求参数 ' . json_encode($data));
        $data = $this->m2->url_data('PAYING', $data, "POST");
        $res = json_decode($data, true);

        $result['order_no'] = $option['order_no'];
        Log::debug('【新】- 银盈通支付接口返回数据 ' . json_encode($res));
        // 接口请求成功
        if ($res['op_ret_code'] == '000') {
            $result['status'] = 'success';
            $result['amount'] = $res['amount'];
            $result['req_no'] = $res['req_no'];
            $result['order_id'] = $res['orderid'];
            return $result;
        } else {
            Log::error('美付宝-代付接口调用失败' . json_encode($res));
            $result['status'] = 'fail';
            return $result;
        }
    }

    /**
     * 代付订单查询
     * @param $option
     * @return array
     */
    public function orderQuery($option)
    {
        $vail_rule = [
            'order_no' => 'require'
        ];
        $vail_field = [
            'order_no' => '订单号'
        ];
        $vaildate = new Validate($vail_rule, [], $vail_field);
        if (!$vaildate->check($option)) {
            return $this->response(-1, $vaildate->getError());
        }

        $data['order_number'] = $option['order_no'];
        $data['merchant_number'] = $option['merchant_number'];
        $data['deal_type'] = '07';

        $data['app_code'] = 'apc_02000000041';            //应用号
        $data['app_version'] = '1.0.0';                    //应用版本
        $data['service_code'] = 'sne_00000000002';        //服务号
        $data['plat_form'] = '01';                        //平台
        $data['login_token'] = '';                        //登录令牌
        $data['req_no'] = date('YmdHis', time());            //请求流水号
        $data['payment_channel_list'] = array();
        $originResult = $this->m2->url_data('RESULT', $data, "POST");
        $resData = json_decode($originResult, true);
        Log::debug('[1] 代付订单查询结果： '.json_encode($resData));

        switch ($resData['op_ret_code']) {
            case 000:
                $resData['status'] = 2;
                $resData['op_err_msg'] = '代付成功';
                break;
            case 600:
                $resData['status'] = -1;
                break;
            case 610:
                $resData['status'] = -1;
                break;
            case 701:
                $resData['status'] = 1;
                break;
            case 702:
                $resData['status'] = -1;
                break;
            case 730:
                $resData['status'] = -1;
                break;
            case 500:
                $resData['status'] = -1;
                break;
            default:
                $resData['status'] = -1;
                $resData['op_err_msg'] = '接口查询没有对应的状态码'.$resData['op_ret_code'];
        }
        return $resData;
    }

    /**
     * 返回数据
     * @param $code
     * @param $msg
     * @return array
     */
    private function response($code, $msg, $data = [])
    {
        return array_merge(['err_code' => $code, 'err_msg' => $msg], $data);
    }
}