<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/14 20:59
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use redis\BaseRedis;
use think\Config;
use think\Controller;
use think\Env;
use Yansongda\Pay\Pay;

class PayController extends Controller
{
    protected $config = [
      'app_id' => '2016090900470841',
      'notify_url' => 'http://yansongda.cn/api/notify',
      'return_url' => 'http://yansongda.cn/api/notify',
      'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArZGRS0dVo9HvAD2EBWQ9w1DOfc+DIS3tssyTksHDOVW+q1ECaYgaEqGC4xJXN/dh5HUP5kYKr0BofE2ECiGdLmY/gOP9en8Wyqz7NYjGUrSfN8hvsaEl/utsmv8/0Ov7TZmdiQycw3Zi30X4Gvd0hiGjXWRFPmMTrNM3u5roYL0jdF4bmCNbc2Mh1TGVENJhdqK7vwnFR5qvTqdmPHWI5oBLZdf6Y607YPIcIkCPYaGISH6Mt9hFD2FWkZIIWj3PHU9JQCJgMzP7NU+T9lis/wqJ3301pevbs3rZC1fcWCUAFktlWEZ4HdmllGf8eCiVqUqwPbWcOHFHMxwoFnPtDwIDAQAB',
        // 加密方式： **RSA2**
      'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCtkZFLR1Wj0e8APYQFZD3DUM59z4MhLe2yzJOSwcM5Vb6rUQJpiBoSoYLjElc392HkdQ/mRgqvQGh8TYQKIZ0uZj+A4/16fxbKrPs1iMZStJ83yG+xoSX+62ya/z/Q6/tNmZ2JDJzDdmLfRfga93SGIaNdZEU+YxOs0ze7muhgvSN0XhuYI1tzYyHVMZUQ0mF2oru/CcVHmq9Op2Y8dYjmgEtl1/pjrTtg8hwiQI9hoYhIfoy32EUPYVaRkghaPc8dT0lAImAzM/s1T5P2WKz/ConffTWl69uzetkLV9xYJQAWS2VYRngd2aWUZ/x4KJWpSrA9tZw4cUczHCgWc+0PAgMBAAECggEAAtjXbEewRO6ln/hiWQBK7xA9Qt0LhpjNRFiMtySMgj1A0miSxI5h9xpFHlpnqdhZ97hf2WQeur8wt5FB8DSa8m7k7cVPnBnHku2BdWBX+HVUA0M7act95w+PiX9UWaX1SlGRvvTBAYfImWb5ad/TPnwAxQBnDl3rrbMNP/uLgkKxDKZ3YBa3YupbZsILDND7ZL90lPRbCMFY6dUZXkOqlzoTPaw820gu6iBg+wLRyPiGKR32MsYK+wIH2IMP+/zTvnZffLX44I6jJUBRFkrmmKPY/cauJfMeJ2AUsALACis9Jv7B8OmCBYJPGk7r09eiaMm++w5anNgBIU8Ny3dMKQKBgQDdl6BmxQ6VS4YzjGXTKa9hk2r7dIl+wr/pXJzgv/8EeZPVdD8iMJekmfjXn3jh4p//pCbSTbJ73LSyRaJXb3P7ommyjhLeXmtTgtT7muJOXga99h41B2H/Nt1NSWsUYXeEX+nz1ONKb15T5ppkVJi4WbqfIKrt83Q9rHXVluO3LQKBgQDIhPud1LnuA0Ao1tv/I2DdqOjZMrO3XuZIZzPo8SShvfh+k6f1mudX1ash40YtqkYWLqwxTy4t6zrYjuAYBQdUe5VY/1i2z2wW71UgbZBac6mhItdH6UBAY1EWkjFacEz10oCnpkl5pOCe7WFyVL8xv+14/Uoa0GQoaNaj0XYaqwKBgBsti6jPJni9KJqN77c0d6Q6Fnb81hhL/om6qCsQoVCFMNKPTWb+Grs/fzvC/WqHByStl21XxjpW2Xq7+6tJqioEw3342uuXHQbDFyg82ODPu3f1BcNvQl+w9PeTt6RqR+Redy1GwRHSEvmrYOhJT+ncZ3043n4MzAb8bf9iYE+JAoGAPNgNOXEJEe2ulDXN/3cOt3O/Y9h8q9GB8spN+Arj4KgyNMY9GstsEzzkdp3t94FJTwXOfg/WpVxRONDxspgkB0CQqraghqgd1j+9Bt/4A1pBSIG37TwboO4B9uVZUGXvFFBRdY2BkgWzmsXQ4c5RTZk9R48j40sEeTaGmqK+QY0CgYEAr7fPjLFJUEwVKB0RYrfqd4nXNTUHfEvjvoKg+bvGNI2QL9MqdZu9qDdYZ6hDbdlojrjZKftvygqOE+LElZTX1I2VPIVkNPgcbU7h+M1AkZ51FJ1QmwOFoiclCsuTkjl6ITKMq/d9Hf6cDf+9CXrWk9tU22tp/8r2B3TXQxc527o=',
      'logs' => [ // optional
        'file' => '../logs/alipay.logs',
        'level' => 'debug'
      ],
      'mode' => 'dev', // optional,设置此参数，将进入沙箱模式
    ];

    public function index()
    {
        $order = [
          'out_trade_no' => rand(11111,99999).time(),
          'total_amount' => '0.01',
          'subject' => '商品测试001',
        ];
        $alipay = Pay::alipay($this->config)->web($order);
        return $alipay->send();
    }

    public function test()
    {
        var_dump(file_get_contents('../logs/123.txt'));
    }

    public function testRedis()
    {
        var_dump(config('redis.message'));
        $redis = location_redis();
        $redis->set("UserName",'Tinywan11111');
        halt($redis);
    }
}