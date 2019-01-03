<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;

use app\common\controller\FrontendController;
use app\common\library\exception\UserException;
use app\common\library\Oss;
use app\common\library\Rsa;
use app\common\model\User;
use app\common\queue\MultiTask;
use app\common\queue\Worker;
use app\common\repositories\channel\SandPay;
use app\common\services\payment\PaymentService;
use app\common\traits\LogRecord;
use GeoIp2\Database\Reader;
use patterns\di\Comment;
use patterns\di\FileCache;
use patterns\di\GmailSender;
use patterns\di\SendEmail163;
use patterns\di\SendEmailQq;
use patterns\di\TencentSender;
use patterns\di\UserDi;
use redis\RedisLock;
use think\Controller;
use think\Db;
use think\Exception;
use think\facade\Cache;
use think\facade\Env;
use think\facade\Log;
use think\facade\Session;
use think\helper\Time;
use think\Queue;
use Tinywan\CPay\Gateways\Pay;

class Index extends FrontendController
{
    use LogRecord;

    public function ossUploadFile()
    {
        $bucket = 'tinywan-live0104';
        $filePath = env('ROOT_PATH') . 'public' . DIRECTORY_SEPARATOR . 'uploads/qq.png';
        $fileName = time() . '.png';
        $oss = Oss::uploadFile($bucket, $filePath, $fileName);
        if ($oss['success']) {
            echo $oss['msg'];
        } else {
            echo $oss['msg'];
        }
    }

    public function sendSms()
    {
//        $option['code'] = rand(111,444);
        $option['status'] = '已发货';
        $option['remark'] = '甘肃省天水市麦积区';
        $response = \app\common\library\DySms::sendSms('13669361192', $option, 'SMS_139785103');
        halt($response);
    }

    public function last_insert_id()
    {
        var_dump(Env::get('APP_PATH') .'common'.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'tpl'.DIRECTORY_SEPARATOR.'404.html');
        halt(Env::get('APP_PATH'));
        var_dump(get_next_id());
    }


    public function lastError($id = 1)
    {
        if ($id = 1) {
            throw new UserException();
        }
    }

    public function log()
    {
        $this->startLog();
        $this->endLog();
        echo session_save_path();
        return "Hi";
    }

    public function index()
    {
        $res = Db::name('order')->count();
        Log::error("88888888");
        Log::debug("2222222");
        Log::error(get_current_date() . "--------------error 这是一条错误日志------------");
        Log::warning(get_current_date() . "--------------error 这是一条错误日志------------");
        return "Hi";
    }

    public function cache()
    {
        // 默认使用文件缓存
        Cache::set("username", "Tinywan");
        echo Cache::get("username");

        // 使用Redis缓存
        Cache::store('redis')->set("RedisUserName", "Tinywan11");
        echo Cache::store('redis')->get("RedisUserName");
    }

    public function testIndex()
    {
        echo 11111111;
        // 赋值（当前作用域）
        Session::set('name', 'thinkphp-Tinywan');
        // 赋值think作用域
        //Session::set('name','thinkphp','think');
    }


    public function env()
    {
        print_r(Env::get('root_path'));
        return "Hi";
    }

    public function redis()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        var_dump($redis->keys('*'));
    }

    public function redisOrder()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $second3later = time() + 3;
        for ($i = 0; $i < 5; $i++) {
            //延迟3秒
            $redis->zAdd('OrderId', $second3later, "OID0000001" . $i);
            print_r("ms:redis生成了一个订单任务：订单ID为" . "OID0000001" . $i . "\r\n");
        }
    }

    public function sendEmail()
    {
        $res = send_email_qq('756684177@qq.com', 'test', 'content');
        var_dump($res);
    }

    // 一个使用了队列的 action
    public function queue()
    {
        //当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
        $data = [
            'email' => '28456049@qq.com',
            'username' => 'Tinywan' . rand(1111, 9999)
        ];
        // 当前任务归属的队列名称，如果为新队列，会自动创建
        $queueName = 'workerQueue';
        // 将该任务推送到消息队列，等待对应的消费者去执行
        $isPushed = Queue::push(Worker::class, $data, $queueName);
        // database 驱动时，返回值为 1|false; redis驱动时，返回值为 随机字符串|false
        if ($isPushed !== false) {
            echo '[' . $queueName . ']' . " Job is Pushed to the MQ Success";
        } else {
            echo 'Pushed to the MQ is Error';
        }
    }

    /**
     * 测试多任务队列
     * @return string
     */
    public function testMultiTaskQueue()
    {
        $taskType = MultiTask::EMAIL;
        $data = [
            'email' => '756684177@qq.com',
            'title' => "把保存在内存中的日志信息",
            'content' => "把保存在内存中的日志信息（用指定的记录方式）写入，并清空内存中的日志" . rand(11111, 999999)
        ];
        //$res = send_email_qq($data['email'], $data['title'], $data['content']);
        $res = multi_task_Queue($taskType, $data);
        if ($res !== false) {
            return "Job is Pushed to the MQ Success";
        } else {
            return 'Pushed to the MQ is Error';
        }
    }

    // table 标签输出
    public function echoTable()
    {
        $html = '<table border="1">';
        $html .= '<tr>';
        $html .= '<th>Month</th>';
        $html .= '<th>Savings</th>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>January</td>';
        $html .= '<td>$100</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $this->assign('html', $html);
        return $this->fetch();
    }


    /**
     * 读取markdown 显示为html文件
     */
    public function readMarkdown()
    {
        $inputFileName = Env::get('ROOT_PATH') . '/public/static/Readme.md';
        $content = file_get_contents($inputFileName);
        $Parsedown = new \Parsedown();
        echo $Parsedown->text($content);
    }

    public function highlight()
    {
        $inputFileName = Env::get('ROOT_PATH') . '/public/static/Readme.md';
        $markdown = file_get_contents($inputFileName); #读取指定目录下的README.md文件
        $Parsedown = new \Parsedown();
        $this->assign("html", $Parsedown->text($markdown)); #传到前台
        return $this->fetch();
    }

    public function bankCity()
    {
        echo __FUNCTION__;
        die;
        $sql = "INSERT INTO tinywan_admin (username,password,status) VALUES ('tinywan11','121111','1')";
        $res = Db::query($sql);
        halt($res);
    }


    public function genTree9($items)
    {
        $tree = array(); //格式化好的树
        foreach ($items as $item) {
            if (isset($items[$item['pid']])) {
                $items[$item['pid']]['son'][] = &$items[$item['id']];
            } else {
                $tree[] = &$items[$item['id']];
            }
        }
        return $tree;
    }

    //无限极分类
    /*
    * @ $pk 当前 id
    * @ $pid 父级 id
    * @ $child定义下级开始 的K
    * @ 下级开始坐标
    **/
    public function make_tree($list, $pk = 'id', $pid = 'sjdl', $child = '_child', $root = 0)
    {
        $tree = array();
        $packData = array();
        foreach ($list as $data) {
            //转换为带有主键id的数组
            $packData[$data[$pk]] = $data; //$packData[1]=$data; $packData[2]=$data
        }
        foreach ($packData as $key => $val) {
            if ($val[$pid] == $root) { //代表跟节点
                $tree[] = &$packData[$key];
            } else {
                //找到其父类
                $packData[$val[$pid]][$child][] = &$packData[$key];
            }
        }
        return $tree;
    }

    /*
     * 根据银行卡账号获取所属银行信息
     * @ param $card
     * @ return string|void
     */
    public function bankInfo($card)
    {
        $bankList = config('aliyun.bankList');
        $card_8 = substr($card, 0, 8);
        if (isset($bankList[$card_8])) {
            return $bankList[$card_8];
        }
        $card_6 = substr($card, 0, 6);
        if (isset($bankList[$card_6])) {
            return $bankList[$card_6];
        }
        $card_5 = substr($card, 0, 5);
        if (isset($bankList[$card_5])) {
            return $bankList[$card_5];
        }
        $card_4 = substr($card, 0, 4);
        if (isset($bankList[$card_4])) {
            return $bankList[$card_4];
        }
        return '该卡号信息暂未录入';
    }

    /**
     * 测试依赖注入
     */
    public function testDi()
    {
        $comment1 = new Comment(new GmailSender());
        $comment1->save(); // GmailSender

        $comment2 = new Comment(new TencentSender());
        $comment2->save(); // TencentSender
        die;
        $data = [
            'name'=>'Tinywan',
            'age'=>24
        ];
        $cache = new FileCache();
        var_dump($cache->cacheData("index_demo"));
    }

    public function Payment()
    {
        $pay = new PaymentService(new SandPay());
        $res = $pay->gateWay(['11111111']); // GmailSender\
        halt($res);
        die;
        $data = [
          'name'=>'Tinywan',
          'age'=>24
        ];
        $cache = new FileCache();
        var_dump($cache->cacheData("index_demo"));
    }

    public function payTest()
    {
        $pay = new \Tinywan\CPay\Gateways\Pay();
        $sign = $pay->sign(['mch_id'=>12001]);
        $result = $pay->request('cpay.shop.pay',$sign);
        var_dump($result);
    }

    public function payTest01()
    {
        Log::debug('【新】开始请求接口 ');
        $daiFuResult['status'] = 'fail';
        if ($daiFuResult['status'] == 'fail') {
            $prepaid_data = [
              'mch_id' => 12001,
              'order_no' => time(),
              'mch_order_no' => $daiFuResult['time'],
            ];
            Log::debug('【新】创建一个预代付订单表记录 ' . json_encode($prepaid_data));
            Db::name('order')->insert($prepaid_data);
            return '接口调用失败';
        }
        Log::debug('【新】请求接口结束1 ');
        Log::debug('【新】请求接口结束2 ');
        Log::debug('【新】请求接口结束3 ');
        return 1111111;
    }

    public function payTest02()
    {
        Log::debug('【新】开始请求接口 ');
        $daiFuResult['status'] = 'fail';
        if(isset($daiFuResult['err_code']) && $daiFuResult['err_code'] == -1){
            Log::error('【新】 请求数据验证失败 ' . json_encode($daiFuResult));
            return '请求数据验证失败';
        }
        if ($daiFuResult['status'] == 'fail') {
            try{
                $prepaid_data = [
                  'mch_id' => 12001,
                  'order_no' => time(),
                  'mch_order_no' => $daiFuResult['time'],
                ];
                Log::debug('【新】创建一个预代付订单表记录 ' . json_encode($prepaid_data));
                Db::name('order')->insert($prepaid_data);
            }catch (Exception $e){
                Log::debug('【新】发生异常 '.$e->getMessage());
                return '【新】发生异常 ';
            }
            Log::debug('【新】接口调用失败 ');
            return '接口调用失败';
        }
        Log::debug('【新】请求接口结束 ');
    }

    /**
     * Redis 缓存
     */
    public function redisCache()
    {
        $key = 'NAME';
        Cache::store('redis')->set($key,'Tinywan');
        halt(Cache::store('redis')->get($key));
    }

    /**
     * 私钥加密、公钥解密
     */
    public function rsaTest()
    {
//        var_dump(openssl_get_cipher_methods());
        $rsa = new Rsa();
        // 加密明文
        $crypt_text = '私钥加密、公钥解密';
        echo '加密明文：' . $crypt_text.PHP_EOL;

        $private_encrypt_data = $rsa->privateEncrypt($crypt_text);
        echo '私钥加密后数据：' . $private_encrypt_data.PHP_EOL; // 不变

        $public_decrypt_data = $rsa->publicDecrypt($private_encrypt_data);
        echo '公钥解密数据: ' . $public_decrypt_data.PHP_EOL;
    }

    /**
     * 公钥加密、私钥解密
     */
    public function rsaTest2()
    {
        $rsa = new Rsa();
        // 加密明文
        $crypt_text = '公钥加密、私钥解密';
        echo '加密明文：' . $crypt_text.PHP_EOL;

        $public_encrypt_data = $rsa->publicEncrypt($crypt_text);
        echo '公钥加密后数据：' . $public_encrypt_data.PHP_EOL; // 一直在变化

        $private_decrypt_data = $rsa->privateDecrypt($public_encrypt_data);
        echo '私钥解密数据: ' . $private_decrypt_data.PHP_EOL;
    }

    public function rsaTest3()
    {
        $_config = [
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
        $pi_key = openssl_pkey_get_private($_config['private_key']);
        var_dump($pi_key);
        $crypttext = '9D151AA3AE9C61F8AC6EDFC03E7A348C';
        // 私钥加密
        $encrypted = '';
        openssl_private_encrypt($crypttext, $encrypted, $pi_key);
        // 转码，这里的$encrypted就是私钥加密的字符串
        $encrypted = base64_encode($encrypted);
        var_dump($encrypted);
    }

    function encrypt_RSA($plainData, $privatePEMKey)
    {
        $encrypted = '';
        $plainData = str_split($plainData, $this->ENCRYPT_BLOCK_SIZE);
        foreach($plainData as $chunk)
        {
            $partialEncrypted = '';

            //using for example OPENSSL_PKCS1_PADDING as padding
            $encryptionOk = openssl_private_encrypt($chunk, $partialEncrypted, $privatePEMKey, OPENSSL_PKCS1_PADDING);

            if($encryptionOk === false){return false;}//also you can return and error. If too big this will be false
            $encrypted .= $partialEncrypted;
        }
        return base64_encode($encrypted);//encoding the whole binary String as MIME base 64
    }

    public function clientIp()
    {
        $ip = RedisLock::getRemoteIp();
        $reader = new Reader(Env::get('ROOT_PATH') . 'public/GeoLite2-City.mmdb',$locales = ['zh-CN']);
        $record = $reader->city($ip);
        print("<h1>当前IP地址：".$ip . "</h1>");
        echo "<br/>";
        print("<h1>所属城市：".$record->country->name .'、'.$record->mostSpecificSubdivision->name.'、'.$record->city->name."</h1>");
        echo "<br/>";
        print("<h1>经度纬度：".$record->location->latitude . '-'.$record->location->longitude."</h1>"); // 44.9733
        echo "<br/>";
        var_dump($record->location->accuracyRadius); // 100
        var_dump($record->location->timeZone); // 'Asia/Shanghai'

        echo "<br/>";
        var_dump($record->country);
        var_dump($record->mostSpecificSubdivision);
        var_dump($record->city);
    }
}

