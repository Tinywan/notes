<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/20 13:22
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\presenters;

class UserPresenter
{
    /**
     * 性別欄位為M，就顯示Mr.，若性別欄位為F，就顯示Mrs.
     * @param string $gender
     * @param string $name
     * @return string
     */
    public function getFullName($gender, $name)
    {
        if ($gender == 'M')
            $fullName = 'Mr. ' . $name;
        else
            $fullName = 'Mrs. ' . $name;

        return $fullName;
    }

    /**
     * 是否顯示email
     * @param User $user
     * @return string
     */
    public function showEmail(User $user)
    {
        if ($user->show_email == 'Y')
            return '<h2>' . $user->email . '</h2>';
        else
            return '';
    }
}