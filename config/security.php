<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/12 15:44
 * |  Mail: 756684177@qq.com
 * |  Desc: 安全配置文件
 * '------------------------------------------------------------------------------------------------------------------*/
return [
  'ip' => [
    'white_list' => [ // 白名单
      '127.0.0.1',
      '115.193.160.178',
      '13,132,145,167',
      '123,12,5,7',
    ],
    'black_list' => [ // 黑名单
      '23,112,45,67',
    ],
  ],
  'wechat' => [
    'appid' => 'wxb3fxxxxxxxxxxx', // APP APPID
  ],
  'rsa' => [
    'private_key_path' => \think\facade\Env::get('ROOT_PATH') . '/public/rsa/rsa_private_key.pem', // 私钥路径
    'public_key_path' => \think\facade\Env::get('ROOT_PATH') . '/public/rsa/rsa_public_key.pem', // 公钥路径
  ],
];