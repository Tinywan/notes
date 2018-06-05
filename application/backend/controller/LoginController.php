<?php

namespace app\backend\controller;

use think\Controller;
use think\facade\Session;
use think\Request;

class LoginController extends Controller
{
    public function index()
    {
        if (Session::has('admin_info')){
            $this->redirect('backend/login/index');
        }
        if ($this->request->isPost()){
            $data = request()->post();
            if (empty($data['username']) || empty($data['password'])){
                return responseJson(false, -1, '请输入用户名或密码');
            }
            if (empty($data['captcha']) || !captcha_check($data['captcha'])){
                return responseJson(false, -1, '验证码错误');
            };
            $admin = Admin::where(['username'  => $data['username']])->find();
            if (empty($admin)){
                return responseJson(false, -1, '账号或密码错误');
            }
            $user = Admin::where([
                'username'  => $data['username'],
                'password'  => md5(md5($data['password']).md5($admin->salt))
            ])->find();

            if (empty($user)){
                return responseJson(false, -1, '账号或密码错误');
            }
            $ip = request()->ip();

            $user->login_ip = $ip;
            $user->login_time = time();
            $user->save();

            AdminOperateLogs::create([
                'uid' => $user->id,
                'remark' => '用户['.$user->username.']登录',
                'ip' => $ip,
                'created_at' => date('Y-m-d H:i:s', time()),
                'type' => 2,
                'from' => 'admin'
            ]);
            Session::set('admin_info', $user->toArray());
            return responseJson(true, 0, '登录成功');
        }

        return view('admin_login');
    }

    /**
     * 后台管理退出
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function logout(){
        $type = $this->request->param('type', 'admin');
        if ($type == 'admin'){
            Session::delete('admin_info');
            if (!Session::has('admin_info')){
                return responseJson(true, 0, '退出成功！');
            }else{
                return responseJson(false, -1, '退出失败！');
            }
        }elseif($type == 'merchant'){
            Session::delete('merchant_info');
            if (!Session::has('merchant_info')){
                return responseJson(true, 0, '退出成功！');
            }else{
                return responseJson(false, -1, '退出失败！');
            }
        }
    }
}
