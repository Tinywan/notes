<?php

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\common\model\Merchant;
use app\common\model\MerchantApp;
use Ramsey\Uuid\Uuid;
use think\facade\Log;
use think\facade\Validate;

class AuthController extends BaseController
{
    /**
     * APP 子账户登录
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login(){
        $params = request()->param();
        Log::debug('APP 子账户登录'.json_encode($params));
        $validate = Validate::make([
            'app_id' => 'require',
            'app_key' => 'require',
        ], [], [
            'app_id' => '应用Id',
            'app_key' => '应用key',
        ]);
        if (!$validate->check($params)){
           return jsonResponse(401, $validate->getError());
        }
        $app_id = $params['app_id'];
        //解密密码
        $app_key = rsa_decode($params['app_key']); // 123456
        Log::debug('A解密密码 '.json_encode($app_key));
        if (empty($app_key)){
            return jsonResponse(401, '应用app_key错误'.$app_key);
        }
//        $salt = MerchantApp::where(['id' => $app_id])->value('salt');
//        if (empty($salt)){
//            return jsonResponse(401, '应用salt错误');
//        }
//
//        $merchantApp = MerchantApp::where(['id' => $app_id, 'app_key' => md5(md5($app_key).md5($salt))])->find();
//        if (empty($merchantApp)){
//            return jsonResponse(401, '密码格式错误1');
//        }

//        if ($merchantApp->status == 0){
//            return jsonResponse(401, '应用已被禁用');
//        }
//
//        // 主账户
//        $merchant = Merchant::get($merchantApp['mch_id']);
//        if ($merchant->status == 0){
//            return jsonResponse(401, '该商户已被禁用');
//        }
//
//        // App 配置
//        if ($merchantApp->alipay_account_id == 0 && $merchantApp->wechat_account_id == 0){
//            return jsonResponse(401, '应用支付模式未配置');
//        }

        $redis = location_redis();
        //删除以前的登录状态
        $login_list = $redis->keys('APP_TOKEN:'.$app_id.':*');
        if($login_list){
            foreach ($login_list as $value) {
                $redis->delete($value);
            }
        }
        //记录登录状态
        $uuid = Uuid::uuid5(Uuid::NAMESPACE_DNS,$app_id);
        $token = $app_id.':'.$uuid;
        $hashKey = "APP_TOKEN:" . $token;
        $redis->hMset($hashKey, [
            'app_id' => $app_id,
            'status' => 1 // APP在线状态
        ]);

        $token = rsa_encode($token);
        if (!$token){
            return jsonResponse(401, 'token生成失败');
        }
        $res = [
            'app_id' => $app_id,
            'name' => $app_id.'Tinywan',
            'token' => $token,
        ];
        return jsonResponse(200, '登录成功',$res);
    }
}