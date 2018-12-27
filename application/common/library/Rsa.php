<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/30 22:48
 * |  Mail: 756684177@qq.com
 * |  Desc: php-rsa 加密解密 https://segmentfault.com/a/1190000012083428
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library;


class Rsa
{
    /**
     * @var array 默认配置
     */
    private $_config = [
        'public_key' => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCbtLA7lMfUvpBgfgzouiPgcnbL
DnEcuCK0gMub/EAEqmr82sl+9tH1iQb1w/hgQLptVRxAuUOa03XqlnG3wkAegtQt
4Q5ZtHSSomE8/5FXJvQfGTCz5RARyM0MiLTMZJGhLdVT6O8uCYIrPRQq7u6NVLs9
6YDmtzX2do/sTsWCAwIDAQAB
-----END PUBLIC KEY-----',
        'private_key' => '-----BEGIN RSA PRIVATE KEY-----
MIICWwIBAAKBgQCbtLA7lMfUvpBgfgzouiPgcnbLDnEcuCK0gMub/EAEqmr82sl+
9tH1iQb1w/hgQLptVRxAuUOa03XqlnG3wkAegtQt4Q5ZtHSSomE8/5FXJvQfGTCz
5RARyM0MiLTMZJGhLdVT6O8uCYIrPRQq7u6NVLs96YDmtzX2do/sTsWCAwIDAQAB
AoGAfnO3zCuAPp6k0jiMc1T4XgeXwkDwS8qfJMiUkxHBTAi66q8khSAeU7H9HQsS
Y9ktji1YzJeo98xULzgPEpWHS/uhA8VZa16TLy9Yfadn2t+wpWpEJ9ZA4jjEqfQj
DDxcUc/pEv5siaE/bU8uls4o2nAiuWnI2n5FGrQa2OziGUECQQDPOh3KD2AOZtEF
p7i0yxYXe4dCKwenfw5q7l933RgqMXsVR1EAGzAUdIs71hTye6ibhva+eJRfndoV
Jq2IHjOdAkEAwFpOZR8j3Cl4zEk/9D9WEnSa8VWLe76vb7DfgfwkSAhs/f2MNF1I
zy9W5tPHRiMzaHNgPBFX9tw2u5QzsgOqHwJAPl3zUTjHZA41okoUIPVuNKsMzjE9
IH/wyuXq/ZwhBbHWpVTNYAbOtZlNvjh0HXZyDDzWTgTkQtKzK+J0H59XUQJARukD
vYOdVKx1O9pFGWW/9U3HUPCYWyYQxrwNqX2qYmO4ymmOJj+9d6OcBbxM2i5f5UGj
WIGMTBUimEQqSpXPQQJAIkHC2GknUv8HaBRLXxYTIAjj78a0pQT2bYlI6R04AwUZ
ljBaUGvvdYJ3CGZ32Xk12Te2fMJj5h/yLyEr8uzpzw==
-----END RSA PRIVATE KEY-----',
    ];

    /**
     * 构造函数
     * Rsa constructor.
     * @param $private_key_filepath
     * @param $public_key_filepath
     */
    public function __construct($private_key_filepath = null, $public_key_filepath = null)
    {
        if(!empty($private_key_filepath) && !empty($public_key_filepath)){
            $this->_config['private_key'] = $this->_getContents($private_key_filepath);
            $this->_config['public_key'] = $this->_getContents($public_key_filepath);
        }
    }

    /**
     * @uses 获取文件内容
     * @param $file_path string
     * @return bool|string
     */
    private function _getContents($file_path)
    {
        file_exists($file_path) or die ('密钥或公钥的文件路径错误');
        return file_get_contents($file_path);
    }

    /**
     * @uses 获取私钥
     * @return bool|resource
     */
    private function _getPrivateKey()
    {
        $private_key = $this->_config['private_key'];
        return openssl_pkey_get_private($private_key);
    }

    /**
     * @uses 获取公钥
     * @return bool|resource
     */
    private function _getPublicKey()
    {
        $public_key = $this->_config['public_key'];
        return openssl_pkey_get_public($public_key);
    }

    /**
     * 私钥加密 （使用公钥解密）
     * @param string $data
     * @return null|string
     */
    public function privateEncrypt($data = '', $padding = OPENSSL_PKCS1_PADDING)
    {
        if (!is_string($data)) return null;
        $encrypted = '';
        $chunks = str_split($data, 117);
        foreach ($chunks as $chunk) {
            $partialEncrypted = '';
            $encryptionOk = openssl_private_encrypt($chunk, $partialEncrypted, $this->_getPrivateKey(), $padding);
            if ($encryptionOk === false) {
                return null;
            }
            $encrypted .= $partialEncrypted;
        }
        $encrypted = base64_encode($encrypted);
        return $encrypted;
    }

    /**
     * 公钥加密（使用私钥解密）
     * @param string $data 加密字符串
     * @param int $padding
     * @return null|string
     */
    public function publicEncrypt($data = '', $padding = OPENSSL_PKCS1_PADDING)
    {
        if (!is_string($data)) return null;
        $encrypted = '';
        $chunks = str_split($data, 117);
        foreach ($chunks as $chunk) {
            $partialEncrypted = '';
            $encryptionOk = openssl_public_encrypt($chunk, $partialEncrypted, $this->_getPublicKey(), $padding);
            if ($encryptionOk === false) {
                return null;
            }
            $encrypted .= $partialEncrypted;
        }
        $encrypted = base64_encode($encrypted);
        return $encrypted;
    }

    /**
     * @uses 私钥解密 （使用公钥加密）
     * @param string $encrypted
     * @return null
     */
    public function privateDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) return null;
        $decrypted = '';
        $chunks = str_split(base64_decode($encrypted), 128);
        foreach ($chunks as $chunk) {
            $partial = '';
            $decryptIsTrue = openssl_private_decrypt($chunk, $partial, $this->_getPrivateKey());
            if ($decryptIsTrue === false) {
                return null;
            }
            $decrypted .= $partial;
        }
        return $decrypted;
    }

    /**
     * 公钥解密 （使用私钥解密）
     * @param string $encrypted 被解密字符串
     * @return null
     */
    public function publicDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) return null;
        $decrypted = '';
        $chunks = str_split(base64_decode($encrypted), 128);
        foreach ($chunks as $chunk) {
            $partial = '';
            $decryptIsTrue = openssl_public_decrypt($chunk, $partial, $this->_getPublicKey());
            if ($decryptIsTrue === false) {
                return null;
            }
            $decrypted .= $partial;
        }
        return $decrypted;
    }

    /**
     * 私钥验签
     * @param $data string 验签内容
     * @param $signature string 签名字符串
     * @param int $signature_alg
     * @return bool
     */
    public function privateSign($data, $signature, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        $result = openssl_sign($data, base64_decode($signature), $this->_getPrivateKey(), $signature_alg);
        openssl_free_key($this->_getPrivateKey());
        return $result === 1 ? true : false;
    }

    /**
     * 公钥验签
     * @param $data string 验签内容
     * @param $signature string 签名字符串
     * @param int $signature_alg
     * @return bool
     */
    public function publicSign($data, $signature, $signature_alg = OPENSSL_ALGO_SHA1)
    {
        $result = openssl_verify($data, base64_decode($signature), $this->_getPublicKey(), $signature_alg);
        openssl_free_key($this->_getPublicKey());
        return $result === 1 ? true : false;
    }
}