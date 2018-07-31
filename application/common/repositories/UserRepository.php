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
    private $name;
    protected $age;

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
     * 回傳大於?年紀的資料
     * @param integer $score
     * @return Collection
     */
    public function getScoreLargerThan($score)
    {
        return $this->user->where('score', '>', $score)->order('create_time')->select();
    }
}