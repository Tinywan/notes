<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/26 15:24
 * |  Mail: 756684177@qq.com
 * |  Desc: 策略模式显示类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\strategy;


class ShowPage
{
    /**
     * 用户接口类
     * @var
     */
    protected $userStrategy;

    /**
     * 设置策略接口
     * @param UserStrategy $userStrategy
     */
    public function setStrategy(UserStrategy $userStrategy)
    {
        $this->userStrategy = $userStrategy;
    }

    /**
     * 展示页面
     */
    public function show()
    {
        echo "AD:";
        $this->userStrategy->showAd();
        echo "<br/>";
        echo "Category:";
        $this->userStrategy->showCategory();
    }
}
