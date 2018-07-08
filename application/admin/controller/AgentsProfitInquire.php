<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/20 11:13
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\controller;


use think\Db;
use app\common\controller\AdminController;

class AgentsProfitInquire extends AdminController
{
    public function index()
    {
        $getData = request()->param();
        $orderModel = Db::name('order');
        if (!empty($getData['start_time']) && !empty($getData['end_time'])) {
            $orderModel->whereBetween('create_time', [strtotime($getData['start_time']), strtotime($getData['end_time'])]);
        }

        $orderSubSql = $orderModel
            ->where('agents_id','not null')
            ->where('agents_id','<>',0)
            ->where('status','=',1)
            ->field('create_time,mch_id,goods,total_fee,cost_rate,cost_service_charge,rate,
        net_profit,service_charge,agents_rate,agents_id,platform_income,channel,agents_income')
            ->buildSql();

        $agentsModel = Db::name('agents')->alias('a');
        if (!empty($getData['agents_name'])) {
            $agentsModel->where('a.agents_name', '=', $getData['agents_name']);
        }
        $list = $agentsModel->join([$orderSubSql => 'o'], 'a.id = o.agents_id')
            ->join('pay_channel_config p','p.class_namespace = o.channel')
            ->field('o.create_time,o.mch_id,o.goods,o.total_fee,o.cost_rate,o.cost_service_charge,o.rate,
        o.net_profit,o.service_charge,o.agents_rate,o.agents_id,o.platform_income,a.agents_name,
        a.agent_rate,o.channel,o.agents_income,p.name_remark,p.name')
            ->order("o.create_time desc")
            ->paginate(16, false, [
                'var_page' => 'page',
                'query' => request()->param(),
            ]);

        return $this->fetch('',[
            'list'=>$list,
        ]);
    }

    public function indexDemo()
    {
        if(request()->isPost()) {
            $startTime = input('post.start_time');
            $endTime = input('post.end_time');
            $model = Db::name('order')->alias('o');
            if (!empty($startTime) && !empty($endTime)) {
                $model->whereBetween('o.create_time', [strtotime($startTime), strtotime($endTime)]);
            }
            $list = $model->where('o.agents_id','not null')
                ->where('o.agents_id','not null')
                ->where('o.status','=',1)
                ->field('o.id,o.create_time,o.mch_id,o.goods,o.total_fee,o.cost_rate,o.cost_service_charge,o.rate,
        o.net_profit,o.service_charge,o.agents_rate,o.agents_id,o.platform_income,a.agents_name,
        a.agent_rate,o.channel,o.agents_income,p.name_remark,p.name')
                ->order("o.create_time desc")
                ->paginate(12, false, [
                    'var_page' => 'page',
                    'query' => request()->param(),
                ]);
        }else{
            $startTime = input('get.start_time', date("Y-m-d H:i:s", strtotime('-1 week')));
            $endTime = input('get.end_time', date("Y-m-d H:i:s"));
            $list = Db::name('order')
                ->alias('o')
                ->join('agents a','a.id = o.agents_id')
                ->join('pay_channel_config p','p.class_namespace = o.channel')
                ->whereBetween('o.create_time',[strtotime($startTime), strtotime($endTime)])
                ->where('o.agents_id','not null')
                ->where('o.status','=',1)
                ->field('o.id,o.create_time,o.mch_id,o.goods,o.total_fee,o.cost_rate,o.cost_service_charge,o.rate,
        o.net_profit,o.service_charge,o.agents_rate,o.agents_id,o.platform_income,a.agents_name,
        a.agent_rate,o.channel,o.agents_income,p.name_remark,p.name')
                ->order("o.create_time desc")
                ->paginate(12, false, [
                    'var_page' => 'page',
                    'query' => request()->param(),
                ]);
        }
        return $this->fetch('',[
            'list'=>$list,
            'startTime' => $startTime,
            'endTime' => $endTime,
        ]);
    }
}