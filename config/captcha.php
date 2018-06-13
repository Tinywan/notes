<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/13 20:07
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

return [
    // 验证码字符集合
    'codeSet' => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
    // 验证码字体大小(px)
    'fontSize' => 12,
    // 是否画混淆曲线
    'useCurve' => false,
    // 是否开启杂点
    'useNoise' => false,
    // 验证码图片高度
    'imageH' => 25,
    // 验证码图片宽度
    'imageW' => 90,
    // 验证码位数
    'length' => 4,
    // 验证成功后是否重置
    'reset' => true
];