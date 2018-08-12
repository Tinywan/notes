<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/1 20:01
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;

class JwtController
{
    /**
     * 官方demo
     * @auther Tinywan 756684177@qq.com
     * @DateTime 2018/8/1 20:55
     */
    public function createNewJwtTokens()
    {
        $builder = new Builder();
        // 设置发行人
        $builder->setIssuer('https://www.tinywan.com/');
        // 设置接收人
        $builder->setAudience('http://example.org');
        // 设置id
        $builder->setId('4f1g23a12aa', true);
        // 设置生成token的时间
        $builder->setIssuedAt(time());
        // 设置在60秒内该token无法使用
        $builder->setNotBefore(time() + 60);
        // 设置过期时间
        $builder->setExpiration(time() + 3600);
        // 给token设置一个id
        $builder->set('uid', 1);

        // 获取结果令牌
        $token = $builder->getToken();
        halt($token);

        // 获取 token headers
        $token->getHeaders();
        // 获取 token claims
        $token->getClaims();

        // 获取单条信息
        echo $token->getHeader('jti')."<br/>"; // will print "4f1g23a12aa"
        echo $token->getClaim('iss')."<br/>"; // will print "http://example.com"
        echo $token->getClaim('uid')."<br/>"; // will print "1"
        echo $token."<br/>"; // The string representation of the object is a JWT string (pretty easy, right?)
    }

    /**
     * 返回一个token
     * @auther Tinywan 756684177@qq.com
     * @DateTime 2018/8/1 20:56
     * @return \Lcobucci\JWT\Token
     */
    public function createJwtTokens()
    {
        $builder = new Builder();
        // 设置发行人
        $builder->setIssuer('https://www.tinywan.com/');
        // 设置接收人
        $builder->setAudience('http://example.org');
        // 设置id
        $builder->setId('4f1g23a12aa', true);
        // 设置生成token的时间
        $builder->setIssuedAt(time());
        // 设置在60秒内该token无法使用
        $builder->setNotBefore(time() + 60);
        // 设置过期时间
        $builder->setExpiration(time() + 3600);
        // 给token设置一个id
        $builder->set('uid', 1);
        $signer = new Sha256();
        // creates a signature using "testing" as key
        $builder ->sign($signer, 'testing');
        // 获取生成的token
        $token = $builder->getToken();
        return $token;
    }

    public function verifyToken()
    {
        $token = $this->createJwtTokens();
        $token = (new Parser())->parse((string) $token); // Parses from a string
        $token->getHeaders(); // Retrieves the token header
        $token->getClaims(); // Retrieves the token claims

        // 获取单条信息
        echo $token->getHeader('jti')."<br/>"; // will print "4f1g23a12aa"
        echo $token->getClaim('iss')."<br/>"; // will print "http://example.com"
        echo $token->getClaim('uid')."<br/>"; // will print "1"
        echo $token."<br/>"; // The string representation of the object is a JWT string (pretty easy, right?)
    }
}