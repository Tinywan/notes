<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/20 13:44
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\repositories;


use app\common\model\User;
use think\Collection;

class UserRepository
{
    /** @var User 注入的User model */
    protected $user;

    /**
     * UserRepository constructor.
     * 將相依的 User model 依賴注入到 UserRepository
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param $score
     * @return array|\PDOStatement|string|Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getScoreLargerThan($score)
    {
        return $this->user->where('score', '>', $score)->order('create_time')->select();
    }
}