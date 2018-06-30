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

use app\common\queue\MultiTask;
use app\common\queue\Worker;
use app\common\traits\LogRecord;
use think\Controller;
use think\Db;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Env;
use think\facade\Log;
use think\facade\Session;
use think\Queue;

class IndexController extends Controller
{
    use LogRecord;

    public function last_insert_id()
    {
        var_dump(get_next_id());
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
    * */
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

}
