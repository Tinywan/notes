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

use app\common\library\QrCodeComponent;
use app\common\library\Rsa;
use app\common\model\Merchant;
use app\common\model\MerchantSubmch;
use app\common\model\Order;
use app\common\model\User;
use app\common\presenter\DateFormatPresenter_tw;
use app\common\presenter\DateFormatPresenter_uk;
use app\common\queue\MultiTask;
use app\common\queue\Worker;
use Endroid\QrCode\QrCode;
use Medz\IdentityCard\China\Identity;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use redis\BaseRedis;
use redis\lock\RedisLock;
use think\Controller;
use think\Db;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Env;
use think\facade\Log;
use think\facade\Session;
use think\helper\Time;
use think\Queue;
use Yansongda\Pay\Pay;

class DemoController extends Controller
{
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
        halt(send_email_qq($data['email'], $data['title'], $data['content']));
        //$res = send_email_qq($data['email'], $data['title'], $data['content']);
        $res = multi_task_Queue($taskType, $data);
        if ($res !== false) {
            return "Job is Pushed to the MQ Success";
        } else {
            return 'Pushed to the MQ is Error';
        }
    }

    /**
     * 订单过期通知
     */
    public function orderExpireNotice()
    {
        $redis = BaseRedis::location();
        $res = $redis->setex('S120012018033016125053041', 3, time());
        halt($res);
    }

    public function sendEmail()
    {
        $res = send_email_qq('756684177@qq.com', 'test', 'content');
        var_dump($res);
    }

    public function fastCgi()
    {
        echo "program start...\r\n";
        $file = Env::get('ROOT_PATH') . '/logs/aliPay.log';
        file_put_contents($file, 'start-time:' . get_current_date() . "\r\n", FILE_APPEND);
        fastcgi_finish_request();

        sleep(1);
        echo 'debug...' . "\r\n";
        file_put_contents($file, 'start-proceed:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);

        sleep(10);
        file_put_contents($file, 'end-time:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);
    }

    /**
     * http://notes.frp.tinywan.top/index/demo/aliPay
     *  网关支付demo
     * @auther Tinywan 756684177@qq.com
     * @DateTime 2018/8/12 15:10
     * @return mixed
     */
    public function aliPay()
    {
        $order_no = 'S' . date('ymdHis', time()) . rand(1000, 9999);
        $insertData = [
          'mch_id' => '2025801203065130',
          'order_no' => $order_no,
          'total_amount' => rand(11, 99),
          'goods' => '商品测试00' . rand(1111, 9999),
        ];
        $res = Order::create($insertData);
        if ($res) {
            $payOrder = [
              'out_trade_no' => $insertData['order_no'],
              'total_amount' => $insertData['total_amount'],
              'subject' => $insertData['goods'],
            ];
            $alipay = Pay::alipay(config('pay.alipay'))->web($payOrder);
            return $alipay->send();
        }
        halt($res);
    }

    /**
     * 渠道支付
     * @auther Tinywan 756684177@qq.com
     * @DateTime 2018/8/12 15:23
     * @return mixed
     */
    public function channelPay()
    {
        $order_no = 'S' . date('ymdHis', time()) . rand(1000, 9999);
        $insertData = [
          'mch_id' => '2025801203065130',
          'order_no' => $order_no,
          'total_amount' => rand(11, 99),
          'goods' => '商品测试00' . rand(1111, 9999),
        ];
        $res = Order::create($insertData);
        if ($res) {
            $payOrder = [
              'out_trade_no' => $insertData['order_no'],
              'total_amount' => $insertData['total_amount'],
              'subject' => $insertData['goods'],
            ];
            $alipay = Pay::alipay(config('pay.alipay'))->web($payOrder);
            return $alipay->send();
        }
        halt($res);
    }


    public function presenterDate()
    {
        $locale = 'uk';
        if ($locale === 'uk') {
            $presenter = new DateFormatPresenter_uk();
        } elseif ($locale === 'tw') {
            $presenter = new DateFormatPresenter_tw();
        } else {
            $presenter = new DateFormatPresenter_tw();
        }
        return view('users.index', compact('users'));
    }

    public function Uuid()
    {
        try {
            // Generate a version 1 (time-based) UUID object
            //$uuid1 = Uuid::uuid1();
            //echo $uuid1->toString() . "\n"; // i.e. e4eaaaf2-d142-11e1-b3e4-080027620cdd

            // Generate a version 3 (name-based and hashed with MD5) UUID object
            $uuid3 = Uuid::uuid3(Uuid::NAMESPACE_DNS, 'php.net');
            echo $uuid3->toString() . "\r\n"; // i.e. 11a38b9a-b3da-360f-9353-a5a725514269

            // Generate a version 4 (random) UUID object
            $uuid4 = Uuid::uuid4();
            echo $uuid4->toString() . "\r\n"; // i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a

            // Generate a version 5 (name-based and hashed with SHA1) UUID object
            $uuid5 = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'php.net');
            echo $uuid5->toString() . "\r\n"; // i.e. c4a760a8-dbcf-5254-a0d9-6a4474bd1b62

        } catch (UnsatisfiedDependencyException $e) {
            // Some dependency was not met. Either the method cannot be called on a
            // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
            echo 'Caught exception: ' . $e->getMessage() . "\r\n";

        }
    }

    public function mongo()
    {
        // 查询操作
        $user = Db::name('order')
          ->where('order_no','=', 'S1807081342018949')
          ->lock(true)
          ->fetchSql()
          ->sequence()
          ->find();
        halt($user);
    }

    public function testFun(int ...$ints)
    {
        return array_sum($ints);
    }

    public function arraysSum(array ...$arrays): array
    {
        return array_map(function (array $arr): int {
            return array_sum($arr);
        }, $arrays);
    }

    public function php7()
    {
        var_dump($this->testFun(2, 33, 4.5));
        var_dump($this->arraysSum([2, 33, 4.5]));
    }

    public function afterUpdate()
    {
        $data = [
          'id' => 11,
          'open_id' => time(),
          'realname' => 'Tinywan' . rand(1111, 999),
        ];
        $res = User::update($data);
        halt($res);
    }

    public function findData()
    {
        $id = 11;
        $tableName = 'open_user';
        $cacheKey = static::getCacheKey($tableName, $id);
        echo $cacheKey;
        $res = Db::name($tableName)->where($id)->cache($cacheKey, 60)->find();
        halt($res);
    }

    /**
     * 获取缓存ID
     * @param $tableName
     * @param $id
     * @return string
     */
    public static function getCacheKey($tableName, $id)
    {
        return Config::get('database.prefix') . $tableName . PATH_SEPARATOR . $id;
    }

    public function rmData()
    {
        Cache::rm('OPEN_USER:11');
        $res = Cache::get('OPEN_USER:11');
        halt($res);
    }

    public function qrCode()
    {
        $qrCode = new QrCode('HTTPS://QR.ALIPAY.COM/FKX04086ZBHBWY1JVZ92BB');
        header('Content-Type: ' . $qrCode->getContentType());
        echo $qrCode->writeString();
        exit;
    }

    public function qrCode2()
    {
        $config = [
          'generate' => 'writefile',
        ];
        $qr_url = 'HTTPS://QR.ALIPAY.COM/FKX04086ZBHBWY1JVZ92BB';
        $fileName = Env::get('ROOT_PATH') . '/public/static';
        $qr_code = new QrCodeComponent($config);
        $qr_code->create($qr_url);
        $rs = $qr_code->generateImg($fileName);
        print_r($rs);
    }

    public function rsaDemo()
    {
        $rsa = new Rsa();
        $origin_data = '123456';
        $encrypt_data = $rsa->privateEncrypt($origin_data);
        $decrypt_data = $rsa->publicDecrypt($encrypt_data);

        echo '私钥加密后的数据为：' . $encrypt_data;
        echo "<hr>";
        echo '公钥解密后的数据为: ' . $decrypt_data;
    }

    public function rsaDemo2()
    {
        $rsa = new Rsa();
        $origin_data = '123456';
        $encrypt_data = rsa_encode($origin_data);
        $decrypt_data = rsa_decode($encrypt_data);

        echo '私钥加密后的数据为：' . $encrypt_data;
        echo "<hr>";
        echo '公钥解密后的数据为: ' . $decrypt_data;
    }

    public function configDemo2()
    {
        if ($rsa = config('security.rsa')) {
            var_dump($rsa);
        }
    }

    public function configDemo3()
    {
        var_dump(User::all());
    }

    public function redisLockTest()
    {
        var_dump($_SERVER['REQUEST_TIME']);
        var_dump($lock_timeout = intval(ceil(10)));
        $id = 13669361192;
        $res = RedisLock::acquireLock($id); // 7f62708bb826c034850783efdba127b3
        var_dump($res);
    }

    public function redisLuaTest()
    {
        $script = <<<luascript
                local result = redis.call('setnx',KEYS[1],ARGV[1]);
                if result == 1 then
                    redis.call('expire',KEYS[1],ARGV[2])
                    return 1
                elseif redis.call('ttl',KEYS[1]) == -1 then
                    redis.call('expire',KEYS[1],ARGV[2])
                    return 0
                end
                return 0
luascript;
        $res = location_redis()->evaluate($script, ['name', 'Tinywan', 360], 1);
        halt($res);
    }

    public function redisLockHttp()
    {
        // 获取锁
        $order_no = 'D183781809141217317557';
        $orderLock = RedisLock::acquireLock($order_no); // 7f62708bb826c034850783efdba127b3
        if (!$orderLock) {
            exit('获取锁失败');
        } else {
            echo "获取锁成功 " . $orderLock . PHP_EOL;
        }
        // 处理业务逻辑
        // 处理业务逻辑
        // ............
        sleep(10);
        // 释放锁
        $orderUnLock = RedisLock::releaseLock($order_no, $orderLock); // 7f62708bb826c034850783efdba127b3
        if (!$orderLock) {
            echo "释放锁失败 ";
        } else {
            echo "释放锁成功 " . $orderUnLock . PHP_EOL;
        }
        var_dump($orderUnLock);
    }

    public function redisLockCli()
    {
        // 获取锁
        $order_no = 'D183781809141217317557';
        $orderLock = RedisLock::acquireLock($order_no); // 7f62708bb826c034850783efdba127b3
        if (!$orderLock) {
            exit('获取锁失败');
        } else {
            echo "获取锁成功 " . $orderLock . PHP_EOL;
        }
        // 处理业务逻辑
        // 处理业务逻辑
        // ............
        sleep(5);
        // 释放锁
        $orderUnLock = RedisLock::releaseLock($order_no, $orderLock); // 7f62708bb826c034850783efdba127b3
        if (!$orderLock) {
            echo "释放锁失败 ";
        } else {
            echo "释放锁成功 " . $orderUnLock . PHP_EOL;
        }
        var_dump($orderUnLock);
    }

    public function dbLock01()
    {
        // 查询操作
        $orderInfo = Db::name('order')
          ->where('order_no','=', 'S1807081342018949')
          ->lock(true)
          ->find();
//        Db::startTrans();
        $status = 22;
        $update = Db::name('order')->update([
          'id'=>$orderInfo['id'],
          'status' => $status
        ]);
        sleep(8);
//        if($update){
//            Db::commit();
//        }else{
//            Db::rollback();
//        }
        var_dump($status);
        $orderInfo1 = Db::name('order')
          ->where('order_no','=', 'S1807081342018949')
          ->find();
        halt($orderInfo1);
    }

    public function dbLock02()
    {
        // 查询操作
        $orderInfo = Db::name('order')
          ->where('order_no','=', 'S1807081342018949')
          ->lock(true)
          ->find();
//        if($orderInfo['status'] == 22){
//            exit('11111111');
//        }
//        Db::startTrans();
        $status = 11;
        $update = Db::name('order')->update([
          'id'=>$orderInfo['id'],
          'status' => $status
        ]);
//        sleep(3);
//        if($update){
//            Db::commit();
//        }else{
//            Db::rollback();
//        }
        var_dump($status);
        $orderInfo1 = Db::name('order')
          ->where('order_no','=', 'S1807081342018949')
          ->find();
        halt($orderInfo1);
    }

    /**
     * 如果>1（不能获得锁）: 说明有操作在进行，删除。
     * 如果=1（获得锁）: 可以操作。
     */
    public static function preventRepeatedSubmit()
    {
        $lock_name = 'LOCK:S120012018040414374458006';
        if(!RedisLock::preventRepeatedSubmit($lock_name)){
            exit('不能获得锁,说明有操作在进行');
        }
        return "（获得锁）: 可以操作";
    }

    public static function preventRepeatedSubmit2()
    {
        $lock_name = 'LOCK:S120012018040414374458006';
        if(RedisLock::preventRepeatedSubmit($lock_name,true)){
            exit('删除成功');
        }
        return "删除失败";
    }

    public function unsetFun()
    {
        //产生由255个0组成的字符串
        $str = 'Tinywan';
        $name = &$str;
        //unset($str);
        echo $str."<br/>"; // Tinywan
        echo $name."<br/>"; // Tinywan
        $name = 'Tinyaiai';
        echo $str."<br/>"; // Tinyaiai
    }

    /**
     * 支付
     */
    public function pay()
    {
        if (request()->get('debug') != 'true') {
            $this->redirect('http://www.tinywan.com/');
            exit();
        }

        $is_pwd = Session::get('is_pwd');
        if ($is_pwd != 'ok') {
            $this->redirect('checkPwd');
            exit();
        }

        $mch_id = request()->get('mch_id') ? request()->get('mch_id') : 12001;
        $list = Db::name('test_order')
            ->order('id', 'desc')
            ->order('created_time', 'desc')
            ->limit(0, 15)
            ->select();
        $this->assign('list', $list);
        $this->assign('mch_id', $mch_id);
        return $this->fetch();
    }

    /**
     * 体验密码
     */
    public function checkPwd()
    {
        if (request()->isPost()) {
            $pwd = request()->post('pwd');
            if (empty($pwd) && $pwd == '') {
                return json([
                    'err_code' => -1,
                    'err_msg' => '密码不能为空'
                ]);
            }
            $db_pwd = '123456778';
            if ($pwd == $db_pwd) {
                Session::set('is_pwd', 'ok');
                return json([
                    'err_code' => 1,
                    'err_msg' => '密码验证成功'
                ]);
            } else {
                return json([
                    'err_code' => -1,
                    'err_msg' => '密码错误'
                ]);
            }
        }
        $is_pwd = Session::get('is_pwd');
        if ($is_pwd == 'ok') {
            echo "<script type='application/javascript'>window.location.href='http://notes.frp.tinywan.top/?debug=true'</script>";
            exit();
        }
        return $this->fetch();
    }

    public function payDo(){
        $params = request()->param();
        if (!is_numeric($params['price'])) {
            $this->error('支付金额必须为数字');
        }
        if (empty($params['price']) || $params['price'] <= 0) {
            $this->error('支付金额必须大于0');
        }
        if (empty($params['pay_type'])) {
            $this->error('请选择支付方式');
        }
        $mch_id = $params['mch_id'];
        $params['third_order_id'] = rand(1000, 9999) . time();

        $notify_url = config('server_url') . '/index/demo/notify';
        $return_url = config('server_url') . '/index/demo/returnUrl';

        if ($params['pay_type'] == 1) {
            //支付宝支付(pc)(企业)
            $result = $this->request('shop.company.pay', [
                'total_fee' => $params['price'],
                'order_sn' => $params['third_order_id'],
                'goods' => '支付宝企业接口支付',
                'pay_type' => 1,
                'notify_url' => $notify_url,
                'return_url' => $return_url,
            ], $mch_id, $params['sub_mch_id']);

        } elseif ($params['pay_type'] == 4) {
            //支付宝支付(wap)(企业)
            $result = $this->request('shop.company.pay', [
                'total_fee' => $params['price'],
                'order_sn' => $params['third_order_id'],
                'goods' => '支付宝企业wap接口支付',
                'pay_type' => 1,
                'client' => 'wap',
                'notify_url' => $notify_url,
                'return_url' => $return_url,
            ], $mch_id, $params['sub_mch_id']);

        }elseif ($params['pay_type'] == 2) {
            //支付宝转账(企业)
            $result = $this->request('shop.company.transfer', [
                'amount' => $params['price'],
                'order_sn' => $params['third_order_id'],
                'account' => $params['alipay_account'],
                'realname' => $params['realname'],
            ], $mch_id, $params['sub_mch_id']);

        } elseif ($params['pay_type'] == 3) {
            //支付宝转账查询(企业)
            $result = $this->request('shop.company.transferQuery', [
                'mch_order_no' => $params['order_sn'],
                'order_no' => $params['order_sn'],
            ], $mch_id, $params['sub_mch_id']);
        }elseif ($params['pay_type'] == 5) {
            //支付宝app转账支付
            $result = $this->request('shop.transfer.pay', [
                'total_fee' => $params['price'],
                'order_sn' => $params['third_order_id'],
                'goods' => '支付宝app转账',
                'pay_type' => 1,
                'user_id' => rand(10000, 99999),
                'notify_url' => $notify_url,
                'return_url' => $return_url,
            ], $mch_id, $params['sub_mch_id']);
        }elseif ($params['pay_type'] == 6) {
            //微信app转账支付
            $result = $this->request('shop.transfer.pay', [
                'total_fee' => $params['price'],
                'order_sn' => $params['third_order_id'],
                'goods' => '微信app转账',
                'pay_type' => 2,
                'user_id' => rand(10000, 99999),
                'notify_url' => $notify_url,
                'return_url' => $return_url,
            ], $mch_id, $params['sub_mch_id']);
        }elseif ($params['pay_type'] == 7) {
            //支付宝当面付
            $result = $this->request('shop.f2f.pay', [
                'total_fee' => $params['price'],
                'order_sn' => $params['third_order_id'],
                'goods' => '支付宝当面付',
                'pay_type' => 1,
                'notify_url' => $notify_url,
                'return_url' => $return_url,
            ], $mch_id, $params['sub_mch_id']);
        } else {
            //其他
            $this->error('未知的支付方式');
        }
        if (isset($result['code']) && $result['code'] == 0) {
            if (in_array($params['pay_type'], [1, 4, 5, 6, 7])) {
                $this->redirect($result['data']['pay_url']);
            } else {
                echo "返回结果：<br/><hr>";
                dump($result);
            }
        } else {
            $this->error($result['msg']);
        }
    }

    /**
     * Api公共请求
     * @param $api_name
     * @param $data
     * @return array|mixed
     */
    private function request($api_name, $data, $mch_id = 12001, $sub_mch_id = '')
    {
        $gate_way_url = config('server_url') . '/api/v1/gateway.do';
        $data = [
            'mch_id' => $mch_id,
            'sub_mch_id' => $sub_mch_id,
            'method' => $api_name,
            'version' => '1.0',
            'timestamp' => time(),
            'content' => json_encode($data)
        ];
        $sign = $this->sign($data);
        if (!$sign) {
            return ['success' => false, 'message' => '签名失败', 'code' => -1, 'data' => []];
        }
        $data['sign'] = $sign;
        //将所有参数urlcode编码，防止中文乱码
        foreach ($data as &$item) {
            $item = urlencode($item);
        }
        unset($item);
        $result = curl_post($gate_way_url, $data); //post请求
        return json_decode($result, true);
    }


    /**
     * RSA签名
     * @param $data
     * @return bool|string
     */
    private function sign($data)
    {
        //解码
        foreach ($data as $key => &$value) {
            $value = urldecode($value);
        }
        unset($value);

        if (isset($data['sign'])) {
            unset($data['sign']);
        }
        if (!empty($data['sub_mch_id'])) {
            $key = MerchantSubmch::where('id', '=', $data['sub_mch_id'])->value('key');
        } else {
            $key = Merchant::where('id', '=', $data['mch_id'])->value('key');
        }
        ksort($data);
        $params_str = urldecode(http_build_query($data));
        $params_str = $params_str . '&key=' . $key;
        Log::debug('[客户端] 签名字符串 ' . $params_str);
        return md5($params_str);
    }

    public function notify(){
        echo 'success';
    }

    public function returnUrl(){
        echo "同步通知数据：<br><hr/>";
        dump($this->request->param());
    }

}
