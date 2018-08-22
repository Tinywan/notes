<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 依赖注入
 * '------------------------------------------------------------------------------------------------------------------*/
namespace patterns\di;


class Comment
{
    // 用于引用发送邮件的库
    private $_eMailSender;

    /**
     * 构造方法注入
     * 1.注册一个接口
       2.当一个类依赖这个接口时，
       3.相应的类会被初始化作为依赖对象。
     * Comment constructor.
     * @param EmailSenderInterface $emailSender
     */
    public function __construct(EmailSenderInterface $emailSender)
    {
        $this->_eMailSender = $emailSender;
    }

    // 保存评论
    public function save()
    {
        $this->afterInsert();
    }

    // 当有新的评价，即 save() 方法被调用之后中，会触发以下方法
    protected function afterInsert()
    {
        // 发送邮件
        $this->_eMailSender->send();
    }
}