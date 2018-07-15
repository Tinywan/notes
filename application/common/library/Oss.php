<?php

/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/3/20 10:47
 * |  Mail: Overcome.wan@Gmail.com
 * |  Fun:  简单的OSS上传封装
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library;

use OSS\Core\OssException;
use OSS\OssClient;

class Oss
{
    /**
     * 类对象实例数组,共有静态变量
     * @var null
     */
    private static $_oss_instance;

    /**
     * 私有化构造函数，防止类外实例化
     */
    private function __construct()
    {
    }

    /**
     * OSS 单例模式
     * @return bool|null|OssClient
     * @static
     */
    public static function Instance()
    {
        if (is_object(self::$_oss_instance)) return self::$_oss_instance;
        try {
            self::$_oss_instance = new OssClient(config('aliyun.accessKeyId'), config('aliyun.accessKeySecret'), config('aliyun.oss.endpoint'));
        } catch (OssException $e) {
            return false;
        }
        return self::$_oss_instance;
    }

    /**
     * 创建bucket
     * @param $bucket
     * @return array|string
     * @static
     */
    public static function createBucket($bucket)
    {
        try {
            //判断bucket是否存在
            if (self::Instance()->doesBucketExist($bucket)) return ['code' => false, 'msg' => $bucket . " 已经存在"];
            self::Instance()->createBucket($bucket);
        } catch (OssException $e) {
            return ['success' => false, 'code' => $e->getHTTPStatus(), 'msg' => $e->getMessage()];
        }
        return ['success' => true, 'code' => 200, 'msg' => "create success", 'url' => "http://" . $bucket . '.' . config('aliyun.oss.endpoint')];
    }

    /**
     * 创建虚拟目录object
     * @param $bucket
     * @param $object
     * @param null $options
     * @return array
     * @static
     */
    public static function createObjectDir($bucket, $object, $options = NULL)
    {
        try {
            //检测Object是否存在
            if (self::Instance()->doesObjectExist($bucket, $object, $options)) return ['code' => false, 'msg' => $object . " 已经存在"];
            self::Instance()->createObjectDir($bucket, $object, $options);
        } catch (OssException $e) {
            return ['success' => true,'code' => $e->getHTTPStatus(), 'msg' => json_encode($e->getMessage())];
        }
        return ['success' => true, 'code' => 200, 'msg' => "OK"];
    }

    /**
     * 上传本地单个文件
     * @param $bucket bucket名称
     * @param $filePath 本地文件路径
     * @param $fileName 上传到OSS存储的文件名
     * @param string $objectName object名称 默认为 data
     * @return array
     */
    public static function uploadFile($bucket, $filePath, $fileName, $options = NULL)
    {
        //DIRECTORY_SEPARATOR 是php的内置变量，显示系统分隔符的，在win下 \  在linux下 /
        $object =  $fileName;
        try {
            //判断bucket是否存在
            if (!self::Instance()->doesBucketExist($bucket)) self::createBucket($bucket);
            self::Instance()->uploadFile($bucket, $object, $filePath,$options);
        } catch (OssException $e) {
            return ['success' => false, 'code' => $e->getHTTPStatus(), 'msg' => json_encode($e->getMessage())];
        }
        return ['success' => true, 'code' => 200,'msg' => "OK", 'url' => "http://" . $bucket . '.' . config('aliyun.oss.endpoint') . DIRECTORY_SEPARATOR . $object];
    }

    /**
     * 上传本地目录内的文件或者目录到指定bucket的指定prefix的object中
     * @param string $bucket bucket名称
     * @param string $prefix 需要上传到的object的key前缀，可以理解成bucket中的子目录，结尾不能是'/'，接口中会补充'/'
     * @param string $localDirectory 需要上传的本地目录
     * @param string $exclude 需要排除的目录
     * @param bool $recursive 是否递归的上传localDirectory下的子目录内容
     * @param bool $checkMd5
     * @return bool
     * @static
     */
    public static function uploadDir($bucket, $prefix, $localDirectory, $exclude = '.|..|.svn|.git', $recursive = false, $checkMd5 = true)
    {
        try {
            self::Instance()->uploadDir($bucket, $prefix, $localDirectory, $exclude, $recursive, $checkMd5);
        } catch (OssException $e) {
            return ['code' => false, 'msg' => json_encode($e->getMessage())];
        }
        return ['code' => true, 'msg' => "OK", 'url' => "http://" . $bucket . '.' . config('aliyun.oss.endpoint') . DIRECTORY_SEPARATOR];
    }


    /**
     * 上传字符串作为object的内容
     * @param string $bucket 存储空间名称
     * @return null
     */
    public static function putObject($bucket, $object, $content)
    {
        try {
            self::Instance()->putObject($bucket, $object, $content);
        } catch (OssException $e) {
            return json_encode($e->getMessage());
        }
        return true;
    }

    /**
     * 下载文件
     * @param string $bucket 存储空间名称
     * @return null
     */
    public static function getObject($bucket, $object, $options = NULL)
    {
        try {
            self::Instance()->getObject($bucket, $object, $options);
        } catch (OssException $e) {
            return json_encode($e->getMessage());
        }
        return true;
    }

    /**
     * 删除操作
     * @param string $bucket 存储空间名称
     * @param string $object 获取的对象
     * @return null
     */
    public static function deleteObject($bucket, $object)
    {
        try {
            self::Instance()->deleteObject($bucket, $object);
        } catch (OssException $e) {
            return false;
        }
        return true;
    }

    /**
     * 私有化克隆函数，防止类外克隆对象
     */
    private function __clone()
    {
        // TODO: Implement __clone() method.
    }
}