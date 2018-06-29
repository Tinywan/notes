<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/30 5:22
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\validate;


use app\common\validate\Base;
use think\facade\Validate;

class BaseValidate extends Base
{
    /**
     * 根据规则来获取数据
     * @param array $arrays 通常传入request.post变量数组
     * @return array 按照规则key过滤后的变量数组
     * @throws ParameterException
     */
    public function getDataByRule($arrays)
    {
        if (array_key_exists('user_id', $arrays) | array_key_exists('uid', $arrays)) {
            // 不允许包含user_id或者uid，防止恶意覆盖user_id外键
            throw new ParameterException([
              'msg' => '参数中包含有非法的参数名 user_id 或者 uid'
            ]);
        }
        $newArray = [];
        foreach (Validate::rule() as $key => $value) {
            $newArray[$key] = $arrays[$key];
        }
        return $newArray;
    }
}