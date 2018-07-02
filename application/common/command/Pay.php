<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/4 19:38
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付命令行
 * |  按照国际清算银行支付结算委员会的定义，所有涉及到资金转移的行为，都可视作支付行为，支付的概念最大，清算和结算属于支付过程中的特定环节，
 *    其中，清算是发生在结算前的支付环节，该环节的功能主要是为了提高结算的标准化水平和结算的效率。
 *    help:
 *      1、http://www.sohu.com/a/148256569_679243
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\command;

use app\pay\controller\OrderController;
use app\pay\service\AccountsService;
use app\pay\service\RedisSubscribe;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

class Pay extends Command
{
    // 清算服务
    const TYPE_CLEARING = 'clearing';

    // 结算服务
    const TYPE_SETTLEMENT = 'settlement';

    // 发布订阅任务
    const TYPE_ORDER_SUBSCRIBE = 'orderSubscribe';

    // 订单延迟消息
    const TYPE_ORDER_DELAY_MESSAGE = 'orderDelayMessage';

    // 配置指令
    public function configure()
    {
        $this->setName('pay')
          ->addArgument('type', Argument::REQUIRED, "the type of the task that pay needs to run")
          ->setDescription('this is payment system command line tools');
    }

    // 执行指令
    public function execute(Input $input, Output $output)
    {
        $type = $input->getArgument('type');
        switch ($type) {
            case static::TYPE_CLEARING: // 清算服务
                $this->accountClearing();
                break;
            case static::TYPE_SETTLEMENT: // 结算服务
                $this->accountSettlement();
                break;
            case static::TYPE_ORDER_SUBSCRIBE: // 发布订阅任务
                $this->orderSubscribe();
                break;
            case static::TYPE_ORDER_DELAY_MESSAGE: // 订单延迟消息
                $this->orderDelayMessage();
                break;
            default:
                Log::error('未知的执行指令' . $type);
        }
    }

    /**
     * 每日账户金额清算
     * 1、清算是发生在结算前的支付环节，该环节的功能主要是为了提高结算的标准化水平和结算的效率
     * 2、包含了在收付款人金融机构之间交换支付工具以及计算金融机构之间待结算的债权，支付工具的交换也包括交易撮合、交易清分、数据收集等
     */
    private function accountClearing()
    {
        $service = new AccountsService();
        $service->accountClearing();
    }

    /**
     * 每日账户金额结算
     * 1、该过程是完成债权最终转移的过程，包括收集待结算的债权并进行完整性检验、保证结算资金具有可用性、结清金融机构之间的债券债务以及记录和通知各方。
     */
    private function accountSettlement()
    {
        $service = new AccountsService();
        $service->accountSettlement();
    }

    /**
     * 通过 Redis 发布订阅模式实现 延时任务
     * key设置规范: type   : orderId : dealyTime
     *            业务类型 :  订单ID : 延迟时间 (eg：PAY:S120012018040414374458006:10)
     */
    private function orderSubscribe()
    {
        $service = new RedisSubscribe();
        $service->orderDelayMessage();
    }

    /**
     * 通过 Redis 有序集合实现延时任务
     */
    private function orderDelayMessage()
    {
        $order = new OrderController();
        $order->consumerDelayMessage();
        //$order->messageTest();
    }
}