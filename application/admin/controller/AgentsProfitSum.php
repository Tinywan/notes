<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/20 11:29
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\controller;

use app\common\controller\AdminController;
use think\Db;

class AgentsProfitSum extends AdminController
{
    public function index()
    {
        $getData = request()->param();
        $subSql = Db::name('order')
            ->field('id,agents_id,salesman_id,
            SUM(total_fee) AS total_fee,
            SUM(net_profit) AS net_profit,
            SUM(agents_income) AS agents_income,
            SUM(platform_income) AS platform_income')
            ->where('agents_id', '<>', 0)
            ->where('salesman_id', '<>', 0)
            ->where('status', '=', 1)
            ->group('agents_id')
            ->buildSql();
        $agentsModel = Db::name('agents')->alias('m');
        if (!empty($getData['agents_name'])) {
            $agentsModel->where('m.agents_name', '=', $getData['agents_name']);
        }
        $list = $agentsModel->field('m.id,m.agents_name,a.total_fee,a.net_profit,
            a.agents_income,a.platform_income,m.total_amount,m.total_cash')
            ->join([$subSql => 'a'], 'a.agents_id = m.id')
            ->paginate(16, false, [
                'var_page' => 'page',
                'query' => request()->param(),
            ]);
        return $this->fetch('', [
            'list' => $list,
        ]);
    }

    // 明细
    public function detail($id)
    {
        $listRows = 16;
        $getData = request()->param();
        $orderModel = Db::name('order')->alias('o')
            ->where('o.status', '>', 0)
            ->where('o.agents_id', '=', $id);
        if (!empty($getData['start_time']) && !empty($getData['end_time'])) {
            $orderModel->whereBetween('o.create_time', [strtotime($getData['start_time']), strtotime($getData['end_time'])]);
        }
        $subSql = $orderModel->field('o.channel,o.salesman_id,o.total_fee,o.cost_rate,o.cost_service_charge,o.rate,
        o.net_profit,o.service_charge,o.agents_rate,o.agents_income,o.agents_id,o.platform_income,o.create_time,o.order_no')
            ->buildSql();
        $agentsModel = Db::name('agents')->alias('m');
        if (!empty($getData['agents_name'])) {
            $agentsModel->where('m.agents_name', '=', $getData['agents_name']);
        }
        $list = $agentsModel
            ->field('m.agents_name,a.salesman_id,a.total_fee,a.cost_rate,a.cost_service_charge,a.rate,a.order_no,
        a.net_profit,a.service_charge,a.agents_rate,a.agents_income,a.platform_income,a.create_time,p.name_remark,p.name')
            ->join([$subSql => 'a'], 'a.salesman_id = m.id', 'RIGHT')
            ->join('pay_channel_config p', 'p.class_namespace = a.channel')
            ->order('a.create_time desc')
            ->paginate($listRows, false, [
                'var_page' => 'page',
                'query' => request()->param(),
            ]);
        return $this->fetch('', [
            'list' => $list,
            'id' => $id
        ]);
    }
}