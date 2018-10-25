<?php

namespace app\admin\controller;

use app\admin\validate\AgentsChannelPayment;
use app\common\controller\Admin;
use app\common\model\AgentsChannelConfig;
use app\common\model\AgentsPayment;
use app\common\model\ChannelMerchantAccount;
use app\common\model\MerchantAccount;
use app\common\model\MerchantBalanceRecord;
use app\common\model\MerchantChannelConfig;
use app\common\model\MerchantPayment;
use app\common\model\MerchantTransferAccount;
use app\common\model\MerchantTransferConfig;
use app\common\repositories\PayRepository;
use think\Log;
use think\Validate;
use app\common\model\Merchant as MerchantModel;
use app\common\model\Agents as AgentsModel;

class Merchant extends AdminController
{

    use \app\common\library\traits\controller\Curd;

    public function model()
    {
        return \app\common\model\Merchant::class;
    }

    public function init()
    {
        $this->route = 'admin/merchant';
        $this->label = '商户';
        $this->translations = [
            'id' => ['text' => '商户号'],
            'username' => ['text' => '用户名'],
            'phone' => ['text' => '手机号'],
            'password' => ['text' => '密码', 'type' => 'password'],
            'email' => ['text' => '邮箱'],
            'deducting_fee' => ['text' => '手续费余额'],
            'key' => ['text' => '秘钥', 'default' => md5(rand_char(6))],
            'notify_url' => ['text' => '异步通知地址'],
            'return_url' => ['text' => '同步通知地址'],
            'agents_id' => [
                'text' => '所属代理商',
                'type' => 'join',
                'data' => [
                    'table' => 'agents',
                    'alias' => 'a',
                    'show_field' => 'agents_name',
                    'value_field' => 'id'
                ]
            ],
            'is_auth' => [
                'text' => '是否认证',
                'type' => 'radio',
                'list' => [
                    1 => ['label label-info', '已认证', true],
                    0 => ['label label-default', '未认证'],
                ]
            ],
            'agent_pay' => [
                'text' => '代付接口',
                'type' => 'radio',
                'list' => [
                    1 => ['label label-info', '开启', true],
                    0 => ['label label-default', '关闭'],
                ]
            ],
            'cash_status' => [
                'text' => '提现状态',
                'type' => 'radio',
                'list' => [
                    1 => ['label label-info', '开启', true],
                    0 => ['label label-default', '关闭'],
                ]
            ],
            'status' => [
                'text' => '用户状态',
                'type' => 'radio',
                'list' => [
                    1 => ['label label-info', '正常', true],
                    0 => ['label label-default', '禁用'],
                ]
            ],
            'created_at' => ['text' => '注册时间', 'type' => 'time'],
            'updated_at' => ['text' => '更新时间', 'type' => 'time'],
            'last_login_ip' => ['text' => '最后登录ip'],
            'last_login_time' => ['text' => '最后登录时间', 'type' => 'time'],
        ];

        $this->listFields = ['id', 'username', 'phone', 'agents_id', 'deducting_fee', 'is_auth', 'cash_status', 'status', 'created_at'];
        $this->addFormFields = $this->updateFormFields = ['username', 'phone', 'email', 'password', 'key', 'agents_id', 'is_auth', 'agent_pay', 'cash_status', 'status'];

        $this->searchFields = ['id', 'username', 'phone', 'email', 'is_auth', 'agent_pay', 'cash_status', 'status'];
        $this->readFields = ['id', 'username', 'phone', 'agents_id', 'is_auth', 'cash_status', 'agent_pay', 'status', 'last_login_ip', 'last_login_time', 'created_at', 'updated_at'];

        // 更多按钮
        $this->moreFunction = $this->getMoreFunction();
    }

    /**
     * 重写更新验证规则
     * @param $id
     * @return array
     */
    protected function getEditValidateRule($id)
    {
        return [
            'username' => 'require|unique:merchant,username,' . $id,
            'phone' => 'require|unique:merchant,phone,' . $id,
//            'password' => 'require',
//            'email' => 'require',
            'key' => 'require',
            'status' => 'require',
        ];
    }

    /**
     * 重写添加验证规则
     * @param $id
     * @return array
     */
    protected function getValidateRule()
    {
        return [
            'username' => 'require|unique:merchant',
            'phone' => 'require|unique:merchant',
            'password' => 'require',
            'email' => 'require',
            'key' => 'require',
            'status' => 'require',
        ];
    }

    /**
     * 更多功能按钮
     * @return array
     */
    private function getMoreFunction()
    {
        return [
            [
                'btn' => 'success',
                'icon' => 'fa fa-book',
                'text' => '渠道',
                'route' => 'admin/merchant/channelConfig',
                'model_x' => '80%',
                'model_y' => '90%',
//                'type' => 'href'
            ],
            [
                'btn' => 'primary',
                'icon' => 'fa fa-bullseye',
                'text' => '支付',
                'route' => 'admin/merchant/channelSelect',
                'model_x' => '80%',
                'model_y' => '90%',
//                'type' => 'href'
            ],
            [
                'btn' => 'success',
                'icon' => 'fa fa-sticky-note-o',
                'text' => '账户',
                'route' => 'admin/merchant/channelAccount',
                'model_x' => '80%',
                'model_y' => '90%',
//                'type' => 'href'
            ],
            [
                'btn' => 'info',
                'icon' => 'fa fa-sticky-note-o',
                'text' => '转账',
                'route' => 'admin/merchant/transferConfig',
                'model_x' => '550px',
                'model_y' => '90%',
            ],
            [
                'btn' => 'warning',
                'icon' => 'fa fa-copy',
                'text' => '代付',
                'route' => 'admin/payment_interface/merchantConfig',
                'model_x' => '80%',
                'model_y' => '90%',
            ],
        ];
    }

    protected function saveBeforeData($data)
    {
        $data = [
            'pay_password' => md5(md5('123456') . md5($data['salt']))
        ];

        return $data;
    }

    // 添加重写
    public function create()
    {
        return view()->assign([
            'agents_list' => $this->getAgentsList()
        ]);
    }

    /**
     * 新增保存
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save()
    {
        $data = request()->post();
        $_data = [
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'status' => $data['status'],
            'is_auth' => $data['is_auth'],
            'cash_status' => $data['cash_status'],
            'key' => $data['key'],
            'created_at' => time(),
            'updated_at' => time(),
            'agents_id' => $data['agents_id'],
            'salesman_id' => $data['agents_id']
        ];

        if (!empty($data['agents_id'])) {
            $agentsInfo = AgentsModel::where('id', '=', $data['agents_id'])->find();
            if ($agentsInfo->is_auth == 0) {
                responseJson(false, -1, '该业务员没有认证，请认证后添加');
            }

            if ($agentsInfo->status == 0) {
                responseJson(false, -1, '该代理商已被冻结');
            }

            // 如果没有业务员
            if ($agentsInfo->parent_id == 0) {
                $_data['agents_id'] = $data['agents_id'];
            } else {
                // 无代理商也不会有上级
                $_data['agents_id'] = $agentsInfo->parent_id ? $agentsInfo->parent_id : 0;
            }
        }

        $_data['salt'] = rand_char();
        $_data['password'] = md5(md5('123456') . md5($_data['salt']));

        $model = new MerchantModel;
        $result = $model->validate(true)->save($_data);

        if (false === $result) {
            responseJson(false, -1, $model->getError());
        }
        responseJson(true, 0, '添加成功');
    }

    public function edit($id)
    {
        $list = MerchantModel::get($id);
        return view()->assign([
            'agents_list' => $this->getAgentsList(),
            'list' => $list,
            'id' => $id
        ]);
    }

    /**
     * 修改
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update($id)
    {
        if (request()->isPost()) {
            $mchId = $id;
            $postData = request()->post();
            $agentsInfo = AgentsModel::where('id', '=', $postData['salesman_id'])->find();
            if ($agentsInfo) {
                if($agentsInfo->parent_id == 0){
                    $postData['agents_id'] = $agentsInfo->id;
                }else{
                    $postData['agents_id'] = $agentsInfo->parent_id;
                }
            }
            if (!empty($postData['password'])) {
                $postData['salt'] = rand_char();
                $postData['password'] = md5(md5($postData['password']) . md5($postData['salt']));
            } else {
                unset($postData['password']);
            }
            $merchantModel = MerchantModel::get($mchId);
            $res = $merchantModel->isUpdate()->save($postData);
            if (request()->isAjax()) {
                if ($res >= 0) {
                    add_operateLogs('编辑[' . $this->label . ']序号为[' . $id . ']的记录');
                    return responseJson(true, 0, '修改成功，请检查代理商和商户之间的费率问题');
                } else {
                    return responseJson(false, -1, '保存失败！');
                }
            } else {
                if ($res >= 0) {
                    add_operateLogs('编辑[' . $this->label . ']序号为[' . $id . ']的记录');
                    $this->success('修改成功，请检查代理商和商户之间的费率问题！');
                } else {
                    $this->error('保存失败！');
                }
            }
//            // 1、代理商费率查询判断
//            $agentsRates = \app\common\model\AgentsChannelPayment::where('agents_id','=',$agentsId)
//                ->field('agents_rate as rate,payment_id,channel_id')
//                ->select();
//            $agentsArr = [];
//            foreach ($agentsRates as $v){
//                $agentsArr[] = [
//                  $v['channel_id'] =>[
//                      $v['payment_id'] =>$v['rate']
//                  ]
//                ];
//            }
//            Log::error('--代理费率--'.json_encode($agentsArr));
//            // 2、查询商户开通的所有支付方式的费率
//            $merchantRates = MerchantPayment::where('merchant_id','=',$mchId)
//                ->field('rate,payment payment_id,channel_config_ids channel_id')
//                ->select();
//            $merchantArr = [];
//            foreach ($merchantRates as $v){
//                $merchantArr[] = [
//                    $v['channel_id'] =>[
//                        $v['payment_id'] =>$v['rate']
//                    ]
//                ];
//            }
        }
    }

    // 软删除 @add Tinywan
    public function delete()
    {
        $id = request()->post('id');
        if (empty($id)) {
            responseJson(false, -1, 'request parameter error');
        }

        $res = \app\common\model\Merchant::destroy($id);
        if ($res) {
            add_operateLogs('删除商户id [' . $id . ']');
            return responseJson(true, 0, '删除成功');
        } else {
            return responseJson(false, -3, '删除失败');
        }
    }

    /**
     * 渠道账户121
     * @return $this
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function channelAccount()
    {

        $id = request()->get('ids');
        if (empty($id)) $this->error('参数错误');

        $list = MerchantAccount::alias('a')
            ->field(['a.*', 'b.name'])
            ->join('pay_channel_config b', 'a.channel = b.class_namespace', 'left')
            ->where(['a.mch_id' => $id])
            ->select();

        return view()->assign([
            'list' => $list
        ]);
    }

    /**
     * 余额查询
     * @return $this
     * @throws \think\exception\DbException
     */
    public function balance(PayRepository $payRepository)
    {
        $channel = request()->get('channel');
        $mch_id = request()->get('mch_id');
        if (empty($channel) || empty($mch_id)) {
            $this->error('参数错误');
        }
        $channel_config = \app\common\model\PayChannelConfig::get(['class_namespace' => $channel]);
        if (empty($channel_config)) {
            $this->error('渠道不存在');
        }
        //查询渠道账户

        $channel_balance = $payRepository->balance($channel_config->class_namespace, $mch_id);
        if (!$channel_balance) {
            $error = $payRepository->getError();
            $channel_count = [
                'success' => false,
                'message' => $error['message'],
                'data' => $error['data']
            ];
        } else {
            $channel_count = [
                'success' => true,
                'message' => '',
                'data' => $channel_balance
            ];
        }

        return view()->assign([
            'channel_count' => $channel_count
        ]);
    }

    /**
     * 渠道商户配置
     */
    public function channelConfig()
    {
        $id = request()->get('ids');
        if (empty($id)) $this->error('参数错误');

        $channel_config_list = MerchantChannelConfig::alias('a')
            ->field(['a.id', 'a.mch_id', 'a.status', 'c.channel_mch_id', 'c.company_name', 'b.name_remark as name'])
            ->join('channel_merchant c', 'a.channel_merchant_id = c.id', 'left')
            ->join('pay_channel_config b', 'a.channel = b.class_namespace', 'left')
            ->where(['a.mch_id' => $id])
            ->select();

        return view()->assign([
            'channel_config_list' => $channel_config_list,
            'mch_id' => $id
        ]);
    }

    /**
     * 添加渠道商户配置
     * @edit Tinywan 商户是否为代理商
     * 1、是，是否已经开通相应的渠道
     * 2、否，直接不让添加渠道，支付方式同理
     */
    public function addChannelConfig($mch_id)
    {
        $request = request();
        if ($request->isPost()) {
            $data = $request->post();
            if (empty($data['channel'])) {
                responseJson(false, -1, '请选择渠道');
            }
            if (empty($data['channel_merchant_id'])) {
                responseJson(false, -1, '请选择渠道商户号');
            }

            $merchant = \app\common\model\Merchant::get($mch_id);
            if (!$merchant) {
                responseJson(false, -1, '商户不存在');
            }

            $mch_channel_config = MerchantChannelConfig::where(['mch_id' => $mch_id, 'channel' => $data['channel']])->select();
            if ($mch_channel_config) {
                responseJson(false, -1, '该渠道商户配置已存在');
            }

            $insert_data = [
                'mch_id' => $mch_id,
                'channel' => $data['channel'],
                'channel_merchant_id' => $data['channel_merchant_id'],
                'status' => $data['status'],
            ];

            $res = MerchantChannelConfig::create($insert_data);
            if (!$res) {
                responseJson(false, -1, '添加失败');
            }

            //查询是否已存在该渠道商户
            $merchant_account = MerchantAccount::get(['mch_id' => $mch_id, 'channel' => $data['channel']]);
            if (empty($merchant_account)) {
                //如果没有该渠道账户，则创建
                MerchantAccount::create([
                    'mch_id' => $mch_id,
                    'channel' => $data['channel']
                ]);
            }
            responseJson(true, 0, '添加成功');

        } else {
            return view()->assign([
                'channel_list' => $this->getChannelList(),
                'channel_merchant_list' => $this->getChannelMerchantList(),
                'mch_id' => $mch_id
            ]);
        }
    }

    /**
     * 编辑渠道商户配置
     * @param $mch_id
     * @param $mch_config_id
     * @return $this
     * @throws \think\exception\DbException
     */
    public function editChannelConfig($mch_id, $mch_config_id)
    {
        $mch_config = MerchantChannelConfig::get(['mch_id' => $mch_id, 'id' => $mch_config_id]);
        if (empty($mch_config)) {
            if (request()->isPost()) {
                responseJson(false, -1, '配置不存在');
            } else {
                $this->error('配置不存在');
            }
        }

        $request = request();
        if ($request->isPost()) {
            $data = $request->post();
            if (empty($data['channel_merchant_id'])) {
                responseJson(false, -1, '请选择渠道商户号');
            }
            $merchant = \app\common\model\Merchant::get($mch_id);
            if (!$merchant) {
                responseJson(false, -1, '商户不存在');
            }

            $update_data = [
                'channel_merchant_id' => $data['channel_merchant_id'],
                'status' => $data['status'],
            ];

            $merchant_channel_config = MerchantChannelConfig::get($mch_config_id);
            $res = $merchant_channel_config->save($update_data);
            if ($res < 0) {
                responseJson(false, -1, '保存失败');
            }
            //查询是否已存在该渠道商户
            $merchant_account = MerchantAccount::get(['mch_id' => $mch_id, 'channel' => $mch_config->channel]);
            if (empty($merchant_account)) {
                //如果没有该渠道账户，则创建
                MerchantAccount::create([
                    'mch_id' => $mch_id,
                    'channel' => $mch_config->channel
                ]);
            }
            responseJson(true, 0, '保存成功');
        } else {
            return view()->assign([
                'channel_merchant_list' => $this->getChannelMerchantList(),
                'mch_id' => $mch_id,
                'mch_config' => $mch_config
            ]);
        }

    }

    /**
     * @remove Tinywan 2018/6/15 13:21 该方法已经写到父类Admin
     * 获取渠道列表
     */
//    private function getChannelList(){
//        return \app\common\model\PayChannelConfig::all();
//    }

    /**
     * @remove Tinywan 2018/6/15 13:21 该方法已经写到父类Admin
     * 获取渠道商户号列表
     */
//    private function getChannelMerchantList(){
//
//        $result = [];
//        $list = \app\common\model\ChannelMerchant::field(['id', 'channel', 'channel_mch_id', 'company_name', 'status'])->where(['status' => 1])->select();
//        foreach ($list as $item) {
//            $item = $item->toArray();
//            //查询商户号下的客户号
//            $account = ChannelMerchantAccount::field(['id', 'account', 'username', 'mobie_phone_no'])->where([
//                'channel' => $item['channel'],
//                'channel_mch_id' => $item['channel_mch_id'],
//                'status' => 1
//            ])->select();
//            $account_list = [];
//            foreach ($account as $_item) {
//                $_item = $_item->toArray();
//                $account_list[] = $_item;
//            }
//            $item['account_list'] = $account_list;
//
//            $result[$item['channel']][$item['id']] = $item;
//        }
//
//        return $result;
//    }

    //=======================2018-4-21新增===========================

    /**
     * 支付方式通道选择
     * @param $ids
     * @return $this
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function channelSelect($ids)
    {

        //获取商家已配置的支付方式
        $payment_list = MerchantPayment::alias('a')
            ->field(['a.*', 'b.payment_name'])
            ->join('pay_payment b', 'a.payment = b.id', 'left')
            ->where(['merchant_id' => $ids])
            ->select();
        $list = [];
        foreach ($payment_list as $key => $value) {
            $_data = $value->toArray();
            $_channel_array = [];
            if (!empty($_data['channel_ids'])) {
                $_channel = \app\common\model\PayChannel::all($_data['channel_ids']);
                foreach ($_channel as $_item) {
                    $_channel_array[] = \app\common\model\PayChannelConfig::where(['id' => $_item->channel_config_id])->value('name_remark');
                }
            }
            $list[] = array_merge($_data, [
                'channel_name' => $_channel_array
            ]);
        }

        return view()->assign([
            'payment_list' => $list,
            'mch_id' => $ids
        ]);
    }

    /**
     * 添加支付方式
     * @param $mch_id
     * @return $this
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addPayment($mch_id)
    {
        $request = request();
        $mch_info = \app\common\model\Merchant::get($mch_id);
        if (empty($mch_info)) {
            if ($request->isPost()) {
                responseJson(false, -1, '商户不存在');
            } else {
                $this->error('商户不存在');
            }
        }

        //支付方式
        $payment = \app\common\model\PayPayment::where(['status' => 1])->select();
        //支付方式对应通道
        $channel = [];
        $paychannel = \app\common\model\PayChannel::alias('a')
            ->field(['a.*', 'b.name_remark', 'b.class_namespace'])
            ->join('pay_channel_config b', 'a.channel_config_id = b.id', 'left')
            ->where(['a.status' => 1])
            ->select();
        foreach ($paychannel as $item) {
            $item = $item->toArray();
            //查询商户添加的渠道商户号
//            jd_merchant_channel_config
            $merchant_channel_config = MerchantChannelConfig::where(['mch_id' => $mch_id, 'channel' => $item['class_namespace'], 'status' => 1])->select();
            $merchant_channel_config_array = [];
            foreach ($merchant_channel_config as $_item) {
                $merchant_channel_config_array[] = $_item->channel;
            }
            //筛选已配置渠道商户号的支付方式
            if (in_array($item['class_namespace'], $merchant_channel_config_array)) {
                $channel[$item['payment']][$item['id']] = $item;
            }
        }

        if ($request->isPost()) {
            $post = $request->post();

            if (empty($post['payment'])) {
                responseJson(false, -1, '请选择支付方式');
            }
            if (empty($post['channel'])) {
                responseJson(false, -1, '请选择支付通道');
            }
            $ret = MerchantPayment::get(['merchant_id' => $mch_id, 'payment' => $post['payment']]);
            if (!empty($ret)) {
                responseJson(false, -1, '支付方式已存在');
            }
            $_channel = \app\common\model\PayChannel::get($post['channel']);
            if (empty($_channel)) {
                responseJson(false, -1, '渠道不存在');
            }
            // ------------代理支付方式检测-------------------------------------------------------------------------------
            $merchantInfo = MerchantModel::where('id', '=', $mch_id)->find();
            $agents_rate = 0;
            if (!empty($merchantInfo->agents_id)) {
                // 渠道配置
                $channel_id = \app\common\model\PayChannel::where('id', '=', $post['channel'])
                    ->value('channel_config_id');
                // 检测代理商是否已经开通该渠道的支付方式
                $agentsConfig = \app\common\model\AgentsChannelPayment::where([
                    'agents_id' => $merchantInfo->agents_id,
                    'payment_id' => $post['payment'],
                    'channel_id' => $channel_id
                ])->find();
                if (empty($agentsConfig)) {
                    responseJson(false, -2, '请先开通[代理商]该支付方式');
                }
                $agents_rate = $agentsConfig->agents_rate;
            }

            if (!empty($agents_rate)) {
                // 代理
                if (empty($post['rate']) || $post['rate'] < $agents_rate) {
                    responseJson(false, -1, '代理费率至少为' . $agents_rate);
                }
            } else {
                // 非代理
                $rate = $channel[$post['payment']][$post['channel']]['rate'];
                if (empty($post['rate']) || $post['rate'] < $rate) {
                    responseJson(false, -1, '费率至少为' . $rate);
                }
            }
            // ------------代理支付方式检测-------------------------------------------------------------------------------

            if (empty($post['day_max_money']) || !is_numeric($post['day_max_money']) || $post['day_max_money'] <= 0) {
                responseJson(false, -1, '当天交易最大金额必须大于0');
            }
            if (empty($post['single_pen_max_money']) || !is_numeric($post['single_pen_max_money']) || $post['single_pen_max_money'] <= 0) {
                responseJson(false, -1, '单笔交易最大金额必须大于0');
            }
            //支付方式标识
            $payment_key = \app\common\model\PayPayment::where(['id' => $post['payment']])->value('key');
            //渠道标识
            $channel_key = \app\common\model\PayChannelConfig::where(['id' => $_channel->channel_config_id])->value('class_namespace');

            //查询渠道商户号
            $merchant_channel_config_info = MerchantChannelConfig::alias('a')
                ->field(['a.*', 'b.channel_mch_id'])
                ->join('channel_merchant b', 'a.channel_merchant_id = b.id', 'left')
                ->where(['a.mch_id' => $mch_id, 'a.channel' => $channel_key, 'a.status' => 1])
                ->find();
            if (empty($merchant_channel_config_info)) {
                responseJson(false, -1, '未配置渠道商户号，请先配置');
            }
            $channel_mch_id = $merchant_channel_config_info->channel_mch_id;

            //查询渠道风控规则
            $risk_control = \app\common\model\ChannelRiskControl::get([
                'channel' => $channel_key,
                'payment' => $payment_key,
                'channel_mch_id' => $channel_mch_id,
            ]);
            if ($risk_control && $risk_control->status == 1) {
                //查询同一渠道，同一支付方式已分配的额度总和
                //查询使用该渠道商户号的所有商户
                $_merchant_channel_config = MerchantChannelConfig::where([
                    'channel_merchant_id' => $merchant_channel_config_info->channel_merchant_id,
                    'channel' => $channel_key
                ])->select();
                $_merchant_list = [];
                foreach ($_merchant_channel_config as $item) {
                    $_merchant_list[] = $item->mch_id;
                }
                //查询同一渠道，同一支付方式已分配的额度总和
                //已分配的单日总额
                $day_max_money_sum = MerchantPayment::where([
                    'payment' => $post['payment'],
                    'channel_ids' => $post['channel'],
                    'merchant_id' => ['in', $_merchant_list]
                ])->sum('day_max_money');
                //限制总额度
                if (($day_max_money_sum + $post['day_max_money']) > $risk_control->day_max_money) {
                    responseJson(false, -1, '当前商户号支付方式剩余额度不足(剩余' . ($risk_control->day_max_money - $day_max_money_sum) . '元)');
                }
                //限制单笔最大交易额
                if ($post['single_pen_max_money'] > $risk_control->single_pen_max_money) {
                    responseJson(false, -1, '单笔最大交易额最大为' . $risk_control->single_pen_max_money . '元');
                }
            }

            $ret = MerchantPayment::create([
                'merchant_id' => $mch_id,
                'payment' => $post['payment'],
                'rate' => $post['rate'],
                'channel_ids' => $post['channel'],
                'channel_config_ids' => $_channel['channel_config_id'],
                'day_max_money' => $post['day_max_money'],
                'single_pen_max_money' => $post['single_pen_max_money'],
                'min_cash' => $post['min_cash'],
                'status' => $post['status']
            ]);
            if ($ret) {
                responseJson(true, 0, '添加成功');
            } else {
                responseJson(false, -1, '添加失败');
            }
        }

        return view()->assign([
            'mch_info' => $mch_info,
            'payment' => $payment,
            'channel' => $channel,
        ]);
    }

    /**
     * 编辑支付方式
     * @return $this
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editPayment($id)
    {
        $request = request();
        $mch_payment = MerchantPayment::get($id);
        if (empty($mch_payment)) {
            if ($request->isPost()) {
                responseJson(false, -1, '记录不存在');
            } else {
                $this->error('记录不存在');
            }
        }
        $mch_id = $mch_payment->merchant_id;
        $mch_info = \app\common\model\Merchant::get($mch_id);

        //支付方式
        $payment = \app\common\model\PayPayment::where(['status' => 1])->select();
        //支付方式对应通道
        $channel = [];
        $paychannel = \app\common\model\PayChannel::alias('a')
            ->field(['a.*', 'b.name_remark', 'b.class_namespace'])
            ->join('pay_channel_config b', 'a.channel_config_id = b.id', 'left')
            ->where(['a.payment' => $mch_payment->payment])
            ->select();
        foreach ($paychannel as $item) {
            $item = $item->toArray();
            $merchant_channel_config = MerchantChannelConfig::where(['mch_id' => $mch_id, 'channel' => $item['class_namespace'], 'status' => 1])->select();
            $merchant_channel_config_array = [];
            foreach ($merchant_channel_config as $_item) {
                $merchant_channel_config_array[] = $_item->channel;
            }
            //筛选已配置渠道商户号的支付方式
            if (in_array($item['class_namespace'], $merchant_channel_config_array)) {
                $channel[$item['id']] = $item;
            }
        }

        if ($request->isPost()) {
            $post = $request->post();
            if (empty($post['channel'])) {
                responseJson(false, -1, '请选择支付渠道');
            }
            $_channel = \app\common\model\PayChannel::get($post['channel']);
            if (empty($_channel)) {
                responseJson(false, -1, '渠道不存在');
            }

            // 费率不能大于1
            if ($post['rate'] >= 1) {
                responseJson(false, -2, '非法的费率');
            }

            // ------------代理支付方式检测-------------------------------------------------------------------------------
            $agentsId = MerchantModel::where('id', '=', $mch_id)->value('agents_id');
            $agents_rate = 0;
            if (!empty($agentsId)) {
                // 渠道配置信息
                $channelConfig = $channel[$post['channel']];
                // 检测代理商是否已经开通该渠道的支付方式
                $agentsConfig = \app\common\model\AgentsChannelPayment::where([
                    'agents_id' => $agentsId,
                    'payment_id' => $channelConfig['payment'],
                    'channel_id' => $channelConfig['channel_config_id']
                ])->find();
                if (empty($agentsConfig)) {
                    responseJson(false, -2, '请先开通[代理商]该支付方式');
                }
                $agents_rate = $agentsConfig->agents_rate;
            }

            if (!empty($agents_rate)) {
                if (!is_numeric($post['rate']) || $post['rate'] <= $agents_rate) {
                    responseJson(false, -1, '(代理商) 费率不能少于' . $agents_rate);
                }
            } else {
                $rate = $channel[$post['channel']]['rate'];
                if (!is_numeric($post['rate']) || $post['rate'] < $rate) {
                    responseJson(false, -1, '费率至少为 ' . $rate);
                }
            }
            // ------------代理支付方式检测-------------------------------------------------------------------------------

            if (empty($post['day_max_money']) || !is_numeric($post['day_max_money']) || $post['day_max_money'] <= 0) {
                responseJson(false, -1, '当天交易最大金额必须大于0');
            }
            if (empty($post['single_pen_max_money']) || !is_numeric($post['single_pen_max_money']) || $post['single_pen_max_money'] <= 0) {
                responseJson(false, -1, '单笔交易最大金额必须大于0');
            }

            //支付方式标识
            $payment_key = \app\common\model\PayPayment::where(['id' => $mch_payment->payment])->value('key');
            //渠道标识
            $channel_key = \app\common\model\PayChannelConfig::where(['id' => $_channel->channel_config_id])->value('class_namespace');

            //查询渠道商户号
            $merchant_channel_config_info = MerchantChannelConfig::alias('a')
                ->field(['a.*', 'b.channel_mch_id'])
                ->join('channel_merchant b', 'a.channel_merchant_id = b.id', 'left')
                ->where(['a.mch_id' => $mch_id, 'a.channel' => $channel_key, 'a.status' => 1])
                ->find();
            if (empty($merchant_channel_config_info)) {
                responseJson(false, -1, '未配置渠道商户号，请先配置');
            }
            $channel_mch_id = $merchant_channel_config_info->channel_mch_id;
            //查询渠道风控规则
            $risk_control = \app\common\model\ChannelRiskControl::get([
                'channel' => $channel_key,
                'payment' => $payment_key,
                'channel_mch_id' => $channel_mch_id,
            ]);
            if ($risk_control && $risk_control->status == 1) {
                //查询使用该渠道商户号的所有商户
                $_merchant_channel_config = MerchantChannelConfig::where([
                    'channel_merchant_id' => $merchant_channel_config_info->channel_merchant_id,
                    'channel' => $channel_key
                ])->select();
                $_merchant_list = [];
                foreach ($_merchant_channel_config as $item) {
                    $_merchant_list[] = $item->mch_id;
                }
                //查询同一渠道，同一支付方式已分配的额度总和
                //已分配的单日总额
                $day_max_money_sum = MerchantPayment::where([
                    'payment' => $mch_payment->payment,
                    'channel_ids' => $post['channel'],
                    'merchant_id' => ['in', $_merchant_list]
                ])->whereNotIn('id', [$id])->sum('day_max_money');
                //限制总额度
                if (($day_max_money_sum + $post['day_max_money']) > $risk_control->day_max_money) {
                    responseJson(false, -1, '当前商户号支付方式剩余额度不足(剩余' . ($risk_control->day_max_money - $day_max_money_sum) . '元)');
                }
                //限制单笔最大交易额
                if ($post['single_pen_max_money'] > $risk_control->single_pen_max_money) {
                    responseJson(false, -1, '单笔最大交易额最大为' . $risk_control->single_pen_max_money . '元');
                }
            }
            $ret = MerchantPayment::where(['id' => $id])->update([
                'channel_ids' => $post['channel'],
                'channel_config_ids' => $_channel['channel_config_id'],
                'rate' => $post['rate'],
                'day_max_money' => $post['day_max_money'],
                'single_pen_max_money' => $post['single_pen_max_money'],
                'min_cash' => $post['min_cash'],
                'status' => $post['status']
            ]);
            if ($ret >= 0) {
                responseJson(true, 0, '保存成功');
            } else {
                responseJson(false, -1, '保存失败');
            }
        }
        return view()->assign([
            'mch_info' => $mch_info,
            'payment' => $payment,
            'channel' => $channel,
            'mch_payment' => $mch_payment
        ]);
    }

    /**
     * 转账支付接口配置
     * @return $this
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function transferConfig()
    {
        if (request()->isPost()) {
            $post = request()->post();
            $vaildate = new Validate([
                'mch_id' => 'require',
                'diff_price' => 'require|gt:0',
                'wait_time' => 'require|egt:420',
                'rate' => 'require|number|egt:0',
                'hang_type' => 'require|number',
                'status' => 'require',
            ], [], [
                'mch_id' => '商户号',
                'diff_price' => '左右浮动差价',
                'wait_time' => '订单超时时间',
                'rate' => '费率',
                'hang_type' => '挂机类型',
                'status' => '状态',
            ]);
            if (!$vaildate->check($post)) {
                responseJson(false, -1, $vaildate->getError());
            }

            // 代理商模式限制
            $merchantInfo = MerchantModel::get($post['mch_id']);
            if (!empty($merchantInfo->agents_id)) {
                $agentsInfo = \app\common\model\Agents::get($merchantInfo->agents_id);
                $merchantRate = round($post['rate'], 4);
                $agentsRate = round($agentsInfo->transfer_rate, 4);
                if ($merchantRate <= $agentsRate) {
                    responseJson(false, -1, '费率必须大于' . $agentsRate);
                }
            }

            $data = [
                'diff_price' => $post['diff_price'],
                'wait_time' => $post['wait_time'],
                'rate' => $post['rate'],
                'hang_type' => $post['hang_type'],
                'status' => $post['status'],
                'alipay_account_id' => $post['alipay_account_id'],
                'wechat_account_id' => $post['wechat_account_id'],
            ];

            if ($post['hang_type'] == 2) {
                if (empty($post['zfbjk_mch_id'])) {
                    responseJson(false, -1, '免签约工具商户号 不能为空');
                }
                if (empty($post['zfbjk_mch_key'])) {
                    responseJson(false, -1, '免签约工具商户key 不能为空');
                }
                if (empty($post['zfbjk_username'])) {
                    responseJson(false, -1, '免签约工具账号 不能为空');
                }
                if (empty($post['zfbjk_pwd'])) {
                    responseJson(false, -1, '免签约工具密码 不能为空');
                }
                $data['zfbjk_mch_id'] = $post['zfbjk_mch_id'];
                $data['zfbjk_mch_key'] = $post['zfbjk_mch_key'];
                $data['zfbjk_username'] = $post['zfbjk_username'];
                $data['zfbjk_pwd'] = $post['zfbjk_pwd'];
            }else{
                $data['allow_not_online'] = $post['allow_not_online'];
            }

            $mch_config = MerchantTransferConfig::get(['mch_id' => $post['mch_id']]);
            if (empty($mch_config)) {
                //查询商户号是否重复
                $rows = MerchantTransferConfig::where(['zfbjk_mch_id' => $post['zfbjk_mch_id']])->find();
                if ($rows) {
                    responseJson(false, -1, '免签工具商户号已存在');
                }

                $update_data = array_merge($data, ['mch_id' => $post['mch_id']]);
                $rows = MerchantTransferConfig::create($update_data);
            } else {
                //查询商户号是否重复
                $rows = MerchantTransferConfig::where([
                    'zfbjk_mch_id' => $post['zfbjk_mch_id'],
                    'mch_id' => ['neq', $post['mch_id']]
                ])->find();
                if ($rows) {
                    responseJson(false, -1, '免签工具商户号已存在');
                }

                $rows = $mch_config->save($data);
            }

            if ($rows || $rows >= 0) {
                responseJson(true, 0, '保存成功');
            } else {
                responseJson(false, -1, '保存失败');
            }
        } else {
            $mch_id = request()->param('ids');
            $mch_config = MerchantTransferConfig::get(['mch_id' => $mch_id]);
            $mch_info = \app\common\model\Merchant::get($mch_id);

            $account = MerchantTransferAccount::all(['mch_id' => $mch_id, 'status' => 1]);
            $account_alipay = [];
            $account_wechant = [];
            foreach ($account as $item) {
                $item = $item->toArray();
                if ($item['account_type'] == 1) {
                    $account_alipay[] = $item;
                } elseif ($item['account_type'] == 2) {
                    $account_wechant[] = $item;
                }
            }

            return view()->assign([
                'mch_id' => $mch_id,
                'mch_info' => $mch_info,
                'mch_config' => $mch_config,
                'account_alipay' => $account_alipay,
                'account_wechant' => $account_wechant
            ]);
        }
    }

    /**
     * 冻结资金
     */
    public function freeze()
    {
        $id = request()->param('id');
        $merchant_account = MerchantAccount::get($id);

        if (request()->isPost()) {
            $post = request()->post();
            $vaildate = new Validate([
                'type' => 'require',
                'money' => 'require|number|gt:0',
                'captcha' => 'require|captcha',
            ], [], [
                'type' => '操作类型',
                'money' => '金额',
                'captcha' => '验证码',
            ]);
            if (!$vaildate->check($post)) {
                responseJson(false, 0, $vaildate->getError());
            }

            try {
                if ($post['type'] == 'balance') {
                    //转入余额
                    if ($merchant_account->freeze_money < $post['money']) {
                        responseJson(false, 0, '冻结账户余额不足');
                    }
                    $merchant_account->withdraw_cash_balance = bcadd($merchant_account->withdraw_cash_balance, $post['money'], 2);
                    $merchant_account->freeze_money = bcsub($merchant_account->freeze_money, $post['money'], 2);
                    $merchant_account->save();

                } elseif ($post['type'] == 'freeze') {
                    //转入冻结账户
                    if ($merchant_account->withdraw_cash_balance < $post['money']) {
                        responseJson(false, 0, '可提现金额不足');
                    }
                    $merchant_account->withdraw_cash_balance = bcsub($merchant_account->withdraw_cash_balance, $post['money'], 2);
                    $merchant_account->freeze_money = bcadd($merchant_account->freeze_money, $post['money'], 2);
                    $merchant_account->save();

                } else {
                    responseJson(false, 0, '类型错误');
                }

                responseJson(true, 0, '操作成功！');

            } catch (\Exception $e) {
                Log::error('操作失败=》' . $e->getTraceAsString());
                responseJson(false, 0, '系统异常');
            }
        }

        return view()->assign([
            'merchant_account' => $merchant_account,
            'id' => $id
        ]);
    }

    /**
     * 资金变动
     * @return $this
     * @throws \think\exception\DbException
     */
    public function capitalChange()
    {
        $id = request()->param('id');
        $merchant_account = MerchantAccount::get($id);

        if (request()->isPost()) {
            $post = request()->post();
            $vaildate = new Validate([
                'type' => 'require',
                'money' => 'require|number|gt:0',
                'remark' => 'require',
                'captcha' => 'require|captcha',
            ], [], [
                'type' => '操作类型',
                'money' => '金额',
                'remark' => '操作备注',
                'captcha' => '验证码',
            ]);
            if (!$vaildate->check($post)) {
                responseJson(false, 0, $vaildate->getError());
            }

            $balance_record = new MerchantBalanceRecord();
            $merchant_account->startTrans();
            $balance_record->startTrans();

            try {
                if ($post['type'] == 1) {
                    //减少余额
                    if ($merchant_account->withdraw_cash_balance < $post['money']) {
                        responseJson(false, 0, '余额不足');
                    }
                    $balance = $merchant_account->balance;
                    //扣除总余额
                    $merchant_account->sum_balance = bcsub($merchant_account->sum_balance, $post['money'], 2);
                    //扣除可提现金额
                    $merchant_account->withdraw_cash_balance = bcsub($merchant_account->withdraw_cash_balance, $post['money'], 2);
                    //扣除余额
                    $merchant_account->balance = bcsub($merchant_account->balance, $post['money'], 2);
                    $merchant_account->save();

                    //增加资金变动日志
                    $balance_record->insert([
                        'mch_id' => $merchant_account->mch_id,
                        'channel' => $merchant_account->channel,
                        'record_type' => 1,
                        'type' => 2,
                        'money' => $post['money'],
                        'after_money' => $merchant_account->balance,
                        'befor_money' => $balance,
                        'remark' => $post['remark'],
                        'created_at' => time(),
                    ]);

                } elseif ($post['type'] == 2) {
                    //增加余额
                    $balance = $merchant_account->balance;

                    //增加总金额
                    $merchant_account->sum_balance = $merchant_account->sum_balance + $post['money'];

                    //增加可提现金额
                    $merchant_account->withdraw_cash_balance = $merchant_account->withdraw_cash_balance + $post['money'];
                    //增加余额
                    $merchant_account->balance = $merchant_account->balance + $post['money'];
                    $merchant_account->save();

                    //增加资金变动日志
                    $balance_record->insert([
                        'mch_id' => $merchant_account->mch_id,
                        'channel' => $merchant_account->channel,
                        'record_type' => 1,
                        'type' => 1,
                        'money' => $post['money'],
                        'after_money' => $merchant_account->balance,
                        'befor_money' => $balance,
                        'remark' => $post['remark'],
                        'created_at' => time(),
                    ]);

                } else {
                    responseJson(false, 0, '类型错误');
                }

                $merchant_account->commit();
                $balance_record->commit();

                responseJson(true, 0, '操作成功！');

            } catch (\Exception $e) {
                $merchant_account->rollback();
                $balance_record->rollback();
                Log::error('操作失败=》' . $e->getTraceAsString());
                responseJson(false, 0, '系统异常');
            }
        }

        return view()->assign([
            'merchant_account' => $merchant_account,
            'id' => $id
        ]);
    }
}