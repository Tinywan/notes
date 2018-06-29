<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/30 5:28
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc:
 *     如果小明和老熊公司的银行账户不是同一个开户行，而是在不同的银行中， 这就需要进行跨行清算。在上述例子中，如果小明的账户开户行是A行， 而老熊公司的账户的开户行是B行。小明通过A行来交钱。
 * 1、A行检查小明的账户是否足以支付这一笔支出，如果足够，会首先从小明账户上扣款。
 * 2、A行通知B行，老熊账户会增加一笔钱。B行按照这个指令在老熊账户上登记一笔收入。
 * 3、这过程中，A行的资金并不会直接打到B行，而是到了一定时间（每天凌晨），开始执行清分，计算应该付给B行的钱，并扣除应该从B行这边来接收的钱，最后计算出来出来支付（收到）给B行的资金，完成清分。
 * 4、A行将清分结果对交易数据进行净额轧差，提交并完成资金划拨给B行，这就完成了清算。
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\service;


use app\common\queue\MultiTask;
use think\facade\Log;

class AccountsService
{
    /**
     * 每日账户金额清算
     * 每天凌晨,按照订单表去清算
     */
    public function accountClearing()
    {
        Log::error(get_current_date().' 每天凌晨,按照订单表去清算... '.__METHOD__);
        $taskType = MultiTask::EMAIL;
        $data = [
          'email' => '756684177@qq.com',
          'title' => "邮件标题".rand(111111,999999),
          'content' => "邮件内容".rand(11111,999999)
        ];
        $res = multi_task_Queue($taskType, $data);
        if ($res !== false) {
            return "Job is Pushed to the MQ Success";
        } else {
            return 'Pushed to the MQ is Error';
        }
    }

    /**
     * 每日账户金额结算
     */
    public function accountSettlement()
    {
        Log::error(get_current_date().' 每日账户金额结算... '.__METHOD__);
    }
}