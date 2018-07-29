<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/29 15:37
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


class Index
{
    /**
     * @auther Tinywan
     * @date 2018/7/29 15:37
     */

    /**
     * @return int
     * @auther Tinywan
     * @date datetime
     */
    public function test(){
        return 111;
    }

    /**
     * @desc
     * @auther Tinywan
     * @date 2018/7/29 15:42
     * @param $name
     * @param $age
     */
    public function test001($name,$age){
        return $name;
    }

    /**
     *
     * @auther Tinywan 756684177@qq.com
     * @DateTime datetime
     * @param array $arr
     * @param int $int
     * @return array
     */
    public static function tt(array  $arr,$int = 1){
        return $arr;
    }
}