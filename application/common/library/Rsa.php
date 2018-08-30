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

    public function __construct()
    {
        if ($rsa = config('security.rsa')) {
            // 可设置配置项 auth_config, 此配置项为数组。
            $this->_config['private_key'] = $this->_getContents($rsa['private_key_path']);
            $this->_config['public_key'] = $this->_getContents($rsa['public_key_path']);
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
     * @uses 私钥加密
     * @param string $data
     * @return null|string
     */
    public function privateEncrypt($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_private_encrypt($data, $encrypted, $this->_getPrivateKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * @uses 公钥加密
     * @param string $data
     * @return null|string
     */
    public function publicEncrypt($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_public_encrypt($data, $encrypted, $this->_getPublicKey()) ? base64_encode($encrypted) : null;
    }

    /**
     * @uses 私钥解密
     * @param string $encrypted
     * @return null
     */
    public function privateDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_private_decrypt(base64_decode($encrypted), $decrypted, $this->_getPrivateKey())) ? $decrypted : null;
    }

    /**
     * @uses 公钥解密
     * @param string $encrypted
     * @return null
     */
    public function publicDecrypt($encrypted = '')
    {
        if (!is_string($encrypted)) {
            return null;
        }
        return (openssl_public_decrypt(base64_decode($encrypted), $decrypted, $this->_getPublicKey())) ? $decrypted : null;
    }
}