<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/20 11:29
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 提现申请
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\controller;

use app\common\controller\BaseAdminController;
use app\common\library\traits\controller\Curd;
use app\common\model\AgentsAccount;
use app\common\model\AgentsBalanceRecord;
use app\common\model\AgentsWithdrawCash;
use app\common\model\Agents as AgentsModel;
use think\Log;

class AgentsCashApplyController extends BaseAdminController
{
    use Curd;

    public function model()
    {
        return AgentsWithdrawCash::class;
    }

    public function init()
    {
        $this->route = 'admin/agents_cash_apply';
        $this->label = '代理商-提现-申请';
        $this->perPage = 12;
        $this->function['create'] = 0;
        $this->function['edit'] = 0;
        $this->function['delete'] = 0;

        $this->translations = [
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
            'acc_attr' => ['text' => '对公对私 1对公   2对私'],
            'acc_bankno' => ['text' => '开户行联行号'],
            'acc_bank' => ['text' => '银行名称'],
            'acc_card' => ['text' => '银行卡号'],
            'acc_province' => ['text' => '省份'],
            'acc_city' => ['text' => '开户行所在城市'],
            'acc_name' => ['text' => '持卡人姓名'],
            'acc_subbranch' => ['text' => '支行名称'],
            'acc_idcard' => ['text' => '身份证号'],
            'acc_mobile' => ['text' => '绑定手机号'],
            'amount' => ['text' => '提现金额'],
            'order_no' => ['text' => '订单号'],
            'type' => ['text' => '1提现  2代付'],
            'status' => [
                'text' => '状态',
                'type' => 'radio',
                'list' => [
                    0 => ['label label-warning', '待审核'],
                    1 => ['label label-primary', '打款中'],
                    2 => ['label label-danger', '已驳回'],
                    3 => ['label label-success', '已打款'],
                    4 => ['label label-danger', '打款失败']
                ]
            ],
            'cash_status' => ['text' => '状态 0未打款 1已打款'],
            'into_time' => ['text' => '到账时间'],
            'remark' => ['text' => '提现备注'],
            'fail_count' => ['text' => '任务失败次数，超过2次自动设为异常订单'],
            'channel_mch_id' => ['text' => '渠道商户号'],
            'channel_mch_account' => ['text' => '渠道商户客户号(环讯用）'],
            'created_at' => ['text' => '申请时间'],
            'updated_at' => ['text' => '最后更新'],
            'pay_remark' => ['text' => '打款失败原因'],
            'arrival_amount' => ['text' => '到账金额'],
            'cashing' => ['text' => '正在打款中'],
        ];
        $this->listFields = ['order_no', 'agents_id', 'amount', 'arrival_amount', 'acc_bank', 'acc_subbranch', 'acc_card', 'created_at', 'status'];

        $this->searchFields = ['order_no', 'acc_card', 'status'];

        // 更多按钮
        $this->moreFunction = $this->getMoreFunction();
    }

    public function getMoreFunction()
    {
        return [
            [
                'btn' => 'success',
                'icon' => 'fa fa-book',
                'text' => '审核',
                'route' => 'admin/agents_cash_apply/cashCheck',
                'model_x' => '40%',
                'model_y' => '75%',
            ],
            [
                'btn' => 'primary',
                'icon' => 'fa fa-bullseye',
                'text' => '已打款',
                'route' => 'admin/agents_cash_apply/hasPay',
                'model_x' => '40%',
                'model_y' => '75%',
            ]
        ];
    }

    // 审核
    public function cashCheck()
    {
        $ids = request()->get('ids');
        $cashList = AgentsWithdrawCash::get($ids);
        if (request()->isPost()) {
            $postData = request()->post();

            if (empty($postData['id'])) responseJson(false, 0, '参数错误！');
            $id = $postData['id'];
            if ($postData['status'] == 2 && empty($postData['remark'])) {
                responseJson(false, 0, '请填写驳回原因！');
            }

            $cashModel = AgentsWithdrawCash::get($id);
            $cashUpdate = [
                'id' => $id,
                'updated_at' => time(),
                'status' => $postData['status']
            ];
            if ($postData['status'] == 2) {
                $cashUpdate['remark'] = $postData['remark'];
            }

            // [1] 提现记录表修改
            $cashModel->startTrans();   // 模型事务
            $cashResult = $cashModel->isUpdate()->save($cashUpdate);
            $agentsId = $cashModel->agents_id;
            if (!$cashResult) {
                $cashModel->rollback();
                responseJson(false, 0, '[代理商提现数据表] 更新失败');
            }

            // 如果审核被驳回，修改资金账户平衡
            if ($postData['status'] == 2) {
                // [2] 代理商账户金额，扣除账户资金
                $accountModel = AgentsAccount::get(['agents_id' => $agentsId]);
                $beforeMoney = $accountModel->sum_balance; // 修改之前的余额

                // [3] 代理商账户金额记录表，记录扣除账户资金记录
                $recordModel = new AgentsBalanceRecord();
                $recordModel->startTrans();
                $recordResult = $recordModel->create([
                    'agents_id' => $agentsId,
                    'type' => 2,
                    'money' => $cashModel->amount,
                    'before_money' => $beforeMoney,
                    'after_money' => $accountModel->sum_balance, // 修改之后的余额
                    'remark' => '[代理商]-' . $agentsId . '-[入账]' . $cashModel->amount . '元，没有手续费的',
                    'created_at' => time()
                ]);
                if (!$recordResult) {
                    $cashModel->rollback();
                    $recordModel->rollback();
                    responseJson(false, 0, '[代理提现明细表] 更新异常');
                }

                // [4] 账户总金额
                $agentsModel = AgentsModel::where('id', '=', $agentsId)->find();
                $totalAmount = $agentsModel->total_amount;
                $agentsModel->startTrans();
                $agentsResult = $agentsModel->isUpdate()->save([
                    'id' => $agentsId,
                    'total_amount' => bcadd($totalAmount, $cashModel->amount, 2)
                ]);

                if (!$agentsResult) {
                    $cashModel->rollback();
                    $recordModel->rollback();
                    $agentsModel->rollback();
                    responseJson(false, 0, '[代理提现明细表] 更新异常');
                }
                $recordModel->commit();
                $agentsModel->commit();
            }
            // 提交事务
            $cashModel->commit();
            responseJson(true, 0, '操作成功');
        }
        return view()->assign([
            'cash' => $cashList
        ]);
    }

    // 已打款/打款失败操作
    public function hasPay()
    {
        $id = request()->get('ids');
        $cashList = AgentsWithdrawCash::get($id);
        if (request()->isPost()) {
            $postData = request()->post();
            if (empty($postData['id'])) {
                responseJson(false, 0, '参数错误！');
            }
            if ($postData['status'] == 4 && empty($postData['pay_remark'])) {
                responseJson(false, 0, '请填写驳回原因！');
            }

            $cashModel = AgentsWithdrawCash::get($postData['id']);
            $cashUpdate = [
                'id' => $postData['id'],
                'updated_at' => time(),
                'arrival_amount' => $cashModel->amount,
                'status' => $postData['status']
            ];

            if ($postData['status'] == 4) {
                $cashUpdate['pay_remark'] = $postData['pay_remark'];
            }

            $cashModel->startTrans();
            $cashResult = $cashModel->isUpdate()->save($cashUpdate);
            if (!$cashResult) {
                $cashModel->rollback();
                responseJson(false, 0, '[代理商提现数据表] 更新失败');
            }

            // 提现总余额增加
            $agentsModel = AgentsModel::where('id', '=', $cashModel->agents_id)->find();
            $totalCash = $agentsModel->total_cash;
            $totalCashUpdate = [
                'id' => $cashModel->agents_id,
                'total_cash' => bcadd($totalCash, $cashModel->amount, 2)
            ];
            $agentsModel->startTrans();
            $agentsRes = $agentsModel->isUpdate()->save($totalCashUpdate);
            if (!$agentsRes) {
                $cashModel->rollback();
                $agentsModel->rollback();
                responseJson(false, 0, '提现总余额增加失败11');
            }
            $cashModel->commit();
            $agentsModel->commit();
            responseJson(true, 0, '操作成功');
        }

        return view()->assign([
            'cash' => $cashList
        ]);
    }
}