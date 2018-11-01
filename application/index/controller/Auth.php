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

use app\common\model\Admin;
use app\common\model\Merchant;
use think\Controller;
use think\facade\Session;

/**
 * 登录模块
 * Class Auth
 * @package app\index\controller
 */
class Auth extends Controller
{
    /**
     * 管理员后台登陆
     */
    public function adminLogin()
    {
        if (Session::has('admin_info')) {
            $this->redirect('admin/index/index');
        }
        if ($this->request->isPost()) {
            $data = request()->post();
            if (empty($data['username']) || empty($data['password'])) {
                return responseJson(false, -1, '请输入用户名或密码');
            }
            if (empty($data['captcha']) || !captcha_check($data['captcha'])) {
                return responseJson(false, -1, '验证码错误');
            };
            $admin = Admin::where(['username' => $data['username']])->find();
            if (empty($admin)) {
                return responseJson(false, -1, '账号或密码错误');
            }
            $user = Admin::where([
              'username' => $data['username'],
              'password' => md5(md5($data['password']) . md5($admin->salt))
            ])->find();

            if (empty($user)) {
                return responseJson(false, -1, '账号或密码错误');
            }
            $ip = request()->ip();

            $user->login_ip = $ip;
            $user->login_time = time();
            $user->save();
            Session::set('admin_info', $user->toArray());
            return responseJson(true, 0, '登录成功');
        }
        return view('admin_login');
    }

    /**
     * 后台管理退出
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function logout()
    {
        $type = $this->request->param('type', 'admin');
        if ($type == 'admin') {
            Session::delete('admin_info');
            if (!Session::has('admin_info')) {
                return responseJson(true, 0, '退出成功！');
            } else {
                return responseJson(false, -1, '退出失败！');
            }
        } elseif ($type == 'merchant') {
            Session::delete('merchant_info');
            if (!Session::has('merchant_info')) {
                return responseJson(true, 0, '退出成功！');
            } else {
                return responseJson(false, -1, '退出失败！');
            }
        }
    }

    /**
     * 商户登录
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchantLogin()
    {
        if (Session::has('merchant_info')) {
            $this->redirect('merchant/index/index');
        }
        if ($this->request->isPost()) {
            $data = request()->post();
            if (empty($data['mch_id']) || empty($data['password'])) {
                return responseJson(false, -1, '请输入商户号或密码');
            }
            if (empty($data['captcha']) || !captcha_check($data['captcha'])) {
                return responseJson(false, -1, '验证码错误');
            };
            $merchant = Merchant::get($data['mch_id']);
            if (empty($merchant)) {
                return responseJson(false, -1, '账号或密码错误');
            }
            $user = Merchant::where([
              'id' => $data['mch_id'],
              'password' => md5(md5($data['password']) . md5($merchant->salt))
            ])->find();

            if (empty($user)) {
                return responseJson(false, -1, '账号或密码错误');
            }
            $ip = request()->ip();

            $user->last_login_ip = $ip;
            $user->last_login_time = time();
            $user->save();

            Session::set('merchant_info', $user->toArray());
            return responseJson(true, 0, '登录成功');
        }
        return view('merchant_login');
    }
}
