<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/14 13:21
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\Agents as AgentsModel;
use app\common\model\AgentsAccount;
use app\common\model\AgentsChannelConfig;
use app\common\model\AgentsChannelPayment;
use app\common\model\AgentsPayment;
use app\common\model\PayChannel;
use app\common\model\PayChannelConfig;
use app\common\model\PayPayment;
use app\common\traits\controller\Curd;
use think\Db;
use think\Log;

class Agents extends AdminController
{
    use Curd;

    public function model()
    {
        return AgentsModel::class;
    }

    public function init()
    {
        $this->route = 'admin/agents';
        $this->label = '支付代理商';
        $this->function['delete'] = 0;
        $this->translations = [
            'id' => ['text' => ''],
            'agents_name' => ['text' => '用户名'],
            'password' => [
                'text' => '密码',
                'type' => 'password'
            ],
            'phone' => ['text' => '手机号'],
            'qq' => ['text' => 'qq'],
            'email' => ['text' => '邮箱'],
            'is_auth' => [
                'text' => '是否认证',
                'type' => 'radio',
                'list' => [
                    1 => ['label label-info', '已认证', true],
                    0 => ['label label-default', '未认证'],
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
            'last_login_ip' => ['text' => '最后登录ip'],
            'last_login_time' => ['text' => '最后登录时间'],
            'agent_rate' => ['text' => '代理费率'],
            'created_at' => ['text' => '注册时间'],
            'updated_at' => ['text' => '更新时间'],
        ];
        $this->listFields = ['id', 'agents_name', 'phone', 'email', 'is_auth', 'status', 'created_at'
        ];

        $this->addFormFields = ['agents_name', 'phone', 'email', 'is_auth', 'status',
        ];

        // 更多按钮
        $this->moreFunction = $this->getMoreFunction();
    }

    // 代理商列表
    public function index()
    {
        if (request()->isPost()) {
            $agentType = input('post.agent_type');
            $agentsName= input('post.agents_name');
            $status= input('post.status');
            $model = Db::name('agents');
            if (!empty($agentType) && $agentType == 1) {
                $model->where('parent_id','=',0);
            }elseif(!empty($agentType) && $agentType == 2){
                $model->where('parent_id','<>',0);
            }

            if(!empty($agentsName)){
                $model->whereLike('agents_name',$agentsName,'or');
            }

            if($status == 0 || $status == 1){
                $model->where('status','=',$status);
            }

            $res = $model->order('created_at desc')
                ->paginate(20, false, [
                    'var_page' => 'page',
                    'query' => request()->param()
                ]);
        } else {
            $res = $model = Db::name('agents')
                ->order('created_at desc')
                ->paginate(20, false, [
                    'var_page' => 'page',
                    'query' => request()->param()
                ]);
        }

        return view()->assign([
            'list' => $res
        ]);
    }

    // 添加重写
    public function create()
    {
        return view()->assign([
            'agents_list' => $this->getAgentsParentList()
        ]);
    }

    /**
     * 新建保存
     * 应该限制最多只能添加2级
     */
    public function save()
    {
        $data = request()->post();

        $_data = [
            'agents_name' => $data['agents_name'],
            'parent_id' => $data['parent_id'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'is_auth' => $data['is_auth'],
            'cash_status' => $data['cash_status'],
            'status' => $data['status'],
        ];

        $_data['salt'] = rand_char();
        $_data['password'] = md5(md5('123456') . md5($_data['salt']));
        $model = new AgentsModel;
        $result = $model->validate(true)->save($_data);

        if (false === $result) {
            responseJson(false, -1, $model->getError());
        }
        responseJson(true, 0, '添加成功');
    }

    /**
     * 编辑显示
     * @param $id 代理商ID
     * @return $this
     * @throws \think\exception\DbException
     */
    public function edit($id)
    {
        $list = AgentsModel::get($id);
        return view()->assign([
            'agents_list'=>$this->getAgentsParentList(),
            'list'=>$list,
            'id'=>$id
        ]);
    }

    /**
     * 更新操作
     * @param $id
     * @throws \think\exception\DbException
     */
    public function update($id)
    {
        $data = request()->post();
        $model = new AgentsModel();
        $result = $model->allowField(true)->save($data,['id'=>$id]);
        if (false === $result) {
            responseJson(false, -1, $model->getError());
        }
        responseJson(true, 0, '更新成功');
    }

    public function delete()
    {
        $id = request()->post('id');
        if (empty($id)) {
            responseJson(false, -1, 'request parameter error');
        }
        $parentIds = AgentsModel::where('parent_id', '=', $id)->count();
        if ($parentIds > 0) {
            responseJson(false, -1, '存在下级代理商，请先处理下级代理商！');
        }
        $res = AgentsModel::destroy($id);
        if ($res) {
            add_operateLogs('删除代理商id [' . $id . ']');
            return responseJson(true, 0, '删除成功');
        } else {
            return responseJson(false, -3, '删除失败');
        }
    }

    /**
     * 支付开通配置
     */
    public function channelSelect($ids)
    {
        //查询代理商已配置的支付方式
        $paymentList = AgentsChannelPayment::alias('a')
            ->field(['a.id','a.agents_id','a.payment_id','a.channel_id','a.agents_rate','a.status',
                'b.payment_name','c.name_remark','c.name'])
            ->join('pay_payment b', 'a.payment_id = b.id', 'left')
            ->join('pay_channel_config c', 'a.channel_id = c.id', 'left')
            ->where(['agents_id' => $ids])
            ->select();
        return view()->assign([
            'payment_list' => $paymentList,
            'agents_id' => $ids
        ]);
    }

    /**
     * 添加支付方式
     */
    public function addPayment($agents_id)
    {
        $agentsInfo = AgentsModel::get($agents_id);
        //所有的渠道
        $channel = PayChannelConfig::where('status', '=', 1)
            ->field('id,name_remark,name,class_namespace')
            ->select();
        $payment = PayPayment::where('status', '=', 1)
            ->select();

        // POST 提交参数
        if (request()->isPost()) {
            $post = request()->post();
            if (empty($post['payment_id'])) {
                responseJson(false, -1, '请选择支付方式');
            }

            if (empty($post['channel_id'])) {
                responseJson(false, -1, '请选择支付通道');
            }

            if (empty($post['agents_rate'])) {
                responseJson(false, -1, '费率不能为空');
            }

            // 是否重复
            $res = AgentsChannelPayment::get([
                'agents_id' => $agents_id,
                'payment_id' => $post['payment_id'],
                'channel_id' => $post['channel_id']
            ]);

            if (!empty($res)) {
                responseJson(false, -1, '支付方式已存在');
            }

            // 查找渠道对应的费率
            $payChannelInfo = PayChannel::get([
                'channel_config_id'=>$post['channel_id'],
                'payment'=>$post['payment_id']
            ]);

            if(empty($payChannelInfo)){
                responseJson(false, -1, '该渠道没有对应的支付方式');
            }

            // 平台费率
            $rate = $payChannelInfo['rate'];
            // 代理费率必须大于成本费率，$rate 为成本费率
            if (empty($post['agents_rate']) || $post['agents_rate'] < $rate) {
                responseJson(false, -1, '[代理]费率至少为' . $rate);
            }

            $insertData = [
                'agents_id' => $agents_id,
                'payment_id' => $post['payment_id'],
                'agents_rate' => $post['agents_rate'],
                'channel_id' => $post['channel_id'],
                'status' => $post['status']
            ];

            $model = new AgentsChannelPayment();
            $result = $model->validate(true)->save($insertData);
            if (!$result) {
                responseJson(false, -1, '添加失败');
            }

            $classNamespace = PayChannelConfig::where('id','=',$post['channel_id'])->value('class_namespace');
            $agentsAccount = AgentsAccount::get(['agents_id' => $agents_id, 'channel' => $classNamespace]);
            if(!$agentsAccount){
                // 如果代理商账户信息，则创建一个新的
                AgentsAccount::create([
                    'agents_id' => $agents_id,
                    'channel' => $classNamespace
                ]);
            }
            responseJson(true, 0, '添加成功');
        }

        return view()->assign([
            'agentsInfo' => $agentsInfo,
            'channel_list' => $channel,
            'payment_list' => $payment,
        ]);
    }

    /**
     * 编辑支付方式
     * @add Tinywan
     * @param $id 代理商id
     * @return $this
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editPayment($id){
        $request = request();
        $agentsPayment = AgentsChannelPayment::get($id);
        if (empty($agentsPayment)){
            if ($request->isPost()){
                responseJson(false, -1, '记录不存在');
            }else{
                $this->error('记录不存在');
            }
        }
        $agentsId = $agentsPayment->agents_id;
        $agentsInfo = AgentsModel::get($agentsId);

        //支付方式对应通道
        $channelList = PayChannelConfig::where('status', '=', 1)
            ->field('id,name_remark,name,class_namespace')
            ->select();
        //支付方式
        $paymentList = PayPayment::where('status', '=', 1)
            ->select();

        // post 提交数据
        if ($request->isPost()){
            $post = $request->post();
            Log::error('---isPost----'.json_encode($post));
            if (empty($post['payment_id'])) {
                responseJson(false, -1, '请选择支付方式');
            }

            if (empty($post['channel_id'])) {
                responseJson(false, -1, '请选择支付通道');
            }

            if (empty($post['agents_rate'])) {
                responseJson(false, -1, '费率不能为空');
            }

            // 查找渠道对应的费率
            $payChannelInfo = PayChannel::get([
                'channel_config_id'=>$post['channel_id'],
                'payment'=>$post['payment_id']
            ]);

            if(empty($payChannelInfo)){
                responseJson(false, -1, '该渠道没有对应的支付方式');
            }

            // 平台费率
            $rate = $payChannelInfo['rate'];
            // 代理费率必须大于成本费率，$rate 为成本费率
            if (empty($post['agents_rate']) || $post['agents_rate'] <= $rate) {
                responseJson(false, -1, '[代理]费率不能少于' . $rate);
            }

            $result = AgentsChannelPayment::update([
                'id' => $id,
                'agents_id' => $agentsId,
                'payment_id' => $post['payment_id'],
                'agents_rate' => $post['agents_rate'],
                'channel_id' => $post['channel_id'],
                'status' => $post['status']
            ]);

            if (!$result) responseJson(false, -1, '添加失败');

            $classNamespace = PayChannelConfig::where(['id'=>$post['channel_id']])->value('class_namespace');
            $agentsAccount = AgentsAccount::get(['agents_id' => $agentsId, 'channel' => $classNamespace]);
            if(!$agentsAccount){
                // 如果代理商账户信息，则创建一个新的
                AgentsAccount::create([
                    'agents_id' => $agentsId,
                    'channel' => $classNamespace
                ]);
            }
            responseJson(true, 0, '修改成功');
        }

        return view()->assign([
            'agents_info' => $agentsInfo,
            'payment_list' => $paymentList,
            'channel_list' => $channelList,
            'agents_payment' => $agentsPayment
        ]);
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
                'text' => '代理费率',
                'route' => 'admin/agents/channelSelect',
                'model_x' => '550px',
                'model_y' => '85%',
                'type' => 'href'
            ]
        ];
    }
}