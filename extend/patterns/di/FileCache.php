<?php
/**
 * Created by PhpStorm.
 * User: tinyw
 * Date: 2018/8/21
 * Time: 15:26
 */

namespace patterns\di;


class FileCache
{
    // 缓存文件后缀
    const EXT = ".txt";

    /*
     * @var string 缓存目录
     */
    public $dir;

    /**
     * FileCache constructor.
     */
    public function __construct()
    {
        $this->dir = env("ROOT_PATH") . '/public/cache/';
    }

    /**
     * 设置/读取缓存
     * @param $key string 文件名
     * @param string $value 缓存数据 不为空：则表示存储。为空：是获取。null：表示删除文件
     * @param string $path 缓存目录
     * @return bool|int
     */
    public function cacheData($key,$value='',$path='')
    {
        $fileName = $this->dir.$path.$key.self::EXT;
        if(!empty($value)){
            // 是否删除
            if(is_null($value)){
                return unlink($fileName);
            }
            $tmpDir = dirname($fileName);
            // 检查目录是否存在
            if(!is_dir($tmpDir)){
                mkdir($tmpDir,0777);
            }
            return file_put_contents($fileName,json_encode($value)); // 成功，返回字节数，失败，返回false
        }
        if(!is_file($fileName)){
            return false;
        }
        return json_decode(file_get_contents($fileName),true);
    }
}