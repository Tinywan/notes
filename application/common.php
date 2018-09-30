<?php

/**
 * 根据数据表获取最新的插入值
 * @param string $model 数据库自增表
 * @param int $increase
 * @return string
 */
function get_next_id($model = 'order', $increase = 1)
{
    \think\Db::execute("update _sequence_" . $model . " set value = last_insert_id(value + $increase)");
    $id = \think\Db::getLastInsID();
    return $id;
    //return sprintf('L%05d', $id);
}

/**
 * 获取指定月份的第一天开始和最后一天结束的时间戳
 * @param int $y 年份 $m 月份
 * @return array(本月开始时间，本月结束时间)
 */
function month_frist_to_last($y = "", $m = "")
{
    if ($y == "") $y = date("Y");
    if ($m == "") $m = date("m");
    $m = sprintf("%02d", intval($m));
    $y = str_pad(intval($y), 4, "0", STR_PAD_RIGHT);

    $m > 12 || $m < 1 ? $m = 1 : $m = $m;
    $firstDay = strtotime($y . $m . "01000000");
    $firstDayStr = date("Y-m-01", $firstDay);
    $lastDay = strtotime(date('Y-m-d 23:59:59', strtotime("$firstDayStr +1 month -1 day")));

    return array(
      "firstday" => $firstDay,
      "lastday" => $lastDay
    );
}

/**
 * 发送HTTP请求
 *
 * @param string $url 请求地址
 * @param string $method 请求方式 GET/POST
 * @param string $refererUrl 请求来源地址
 * @param array $data 发送数据
 * @param string $contentType
 * @param string $timeout
 * @param string $proxy
 * @return boolean
 */
function send_request($url, $data, $refererUrl = '', $method = 'GET', $contentType = 'application/json', $timeout = 30, $proxy = false)
{
    $ch = null;
    if ('POST' === strtoupper($method)) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if ($refererUrl) {
            curl_setopt($ch, CURLOPT_REFERER, $refererUrl);
        }
        if ($contentType) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:' . $contentType));
        }
        if (is_string($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    } else if ('GET' === strtoupper($method)) {
        if (is_string($data)) {
            $real_url = $url . (strpos($url, '?') === false ? '?' : '') . $data;
        } else {
            $real_url = $url . (strpos($url, '?') === false ? '?' : '') . http_build_query($data);
        }

        $ch = curl_init($real_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:' . $contentType));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if ($refererUrl) {
            curl_setopt($ch, CURLOPT_REFERER, $refererUrl);
        }
    } else {
        $args = func_get_args();
        return false;
    }

    if ($proxy) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
    }
    $ret = curl_exec($ch);
    $info = curl_getinfo($ch);
    $contents = array(
      'httpInfo' => array(
        'send' => $data,
        'url' => $url,
        'ret' => $ret,
        'http' => $info,
      )
    );
    curl_close($ch);
    return $ret;
}

/**
 * 格式化容量大小
 * @auther Tinywan 756684177@qq.com
 * @DateTime 2018/7/29 15:57
 * @param $size 文件大小
 * @return string
 */
function format_size($size)
{
    if ($size >= 1073741824) {
        $size = round($size / 1073741824 * 100) / 100 . ' GB';
    } elseif ($size >= 1048576) {
        $size = round($size / 1048576 * 100) / 100 . ' MB';
    } elseif ($size >= 1024) {
        $size = round($size / 1024 * 100) / 100 . ' KB';
    } else {
        $size = $size . ' Bytes';
    }

    return $size;
}

// 应用公共文件

function curl_request($url, $post = '', $cookie = '', $returnCookie = 0)
{
    // 设置头部信息返回为json 格式
    $headers = ["Accept: application/json"];
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_REFERER, "http://XXX");
    if ($post) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if ($cookie) {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    // 使用curl_exec()之前跳过ssl检查项
    //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($curl);
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    if ($returnCookie) {
        list($header, $body) = explode("\r\n\r\n", $data, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
        $info['cookie'] = substr($matches[1][0], 1);
        $info['content'] = $body;
        return $info;
    } else {
        return $data;
    }
}

/**
 * QQ服务器发送邮件
 * @param array|string $address 需要发送的邮箱地址 发送给多个地址需要写成数组形式
 * @param string $subject 标题
 * @param string $content 内容
 * @return array 返回状态吗和提示信息
 * @throws \PHPMailer\PHPMailer\Exception
 */
function send_email_qq($address, $subject, $content)
{
    $email_smtp_host = \think\facade\Config::get('email.qq.smtp_host');
    $email_username = \think\facade\Config::get('email.qq.username');
    $email_password = \think\facade\Config::get('email.qq.password');
    $email_from_name = \think\facade\Config::get('email.qq.from_name');
    $email_host = \think\facade\Config::get('email.qq.domain');
    if (empty($email_smtp_host) || empty($email_username) || empty($email_password) || empty($email_from_name)) {
        return ["errorCode" => 1, "msg" => '邮箱请求参数不全，请检测邮箱的合法性'];
    }
    $phpMailer = new \PHPMailer\PHPMailer\PHPMailer();
    $phpMailer->SMTPDebug = 0;
    $phpMailer->IsSMTP();
    $phpMailer->SMTPAuth = true;
    $phpMailer->SMTPSecure = 'ssl';
    $phpMailer->Host = $email_smtp_host;
    $phpMailer->Port = 465;
    $phpMailer->Hostname = $email_host;
    $phpMailer->CharSet = 'UTF-8';
    $phpMailer->FromName = $email_username;
    $phpMailer->Username = $email_username;
    $phpMailer->Password = $email_password;
    $phpMailer->From = $email_username;
    $phpMailer->IsHTML(true);
    if (is_array($address)) {
        foreach ($address as $addressv) {
            if (false === filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return ["errorCode" => 1, "msg" => '邮箱格式错误'];
            }
            $phpMailer->AddAddress($addressv, $address . '的[' . $subject . ']');
        }
    } else {
        if (false === filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return ["errorCode" => 1, "msg" => '邮箱格式错误'];
        }
        $phpMailer->AddAddress($address, $address . '的[' . $subject . ']');
    }
    $phpMailer->Subject = $subject;
    $phpMailer->Body = $content;
    if (!$phpMailer->Send()) {
        return ["errorCode" => 1, "msg" => $phpMailer->ErrorInfo];
    }
    return ["errorCode" => 0];
}

/**
 * 多任务队列
 * @param $taskType
 * @param $data
 * @return string
 */
function multi_task_Queue($taskType, $data)
{
    if (empty($taskType) || !is_numeric($taskType) || empty($data)) {
        return ["errorCode" => 10002, "msg" => '请求参数错误'];
    }
    switch ($taskType) {
        case \app\common\queue\MultiTask::EMAIL: // 发送邮件
            $className = \app\common\queue\MultiTask::class . "@sendEmail";
            $queueName = "multiTaskQueue";
            break;
        case \app\common\queue\MultiTask::SMS:
            $className = \app\common\queue\MultiTask::class . "@sendSms";
            $queueName = "multiTaskQueue";
            break;
        case \app\common\queue\MultiTask::MSG:
            $className = \app\common\queue\MultiTask::class . "@sendMsg";
            $queueName = "multiTaskQueue";
            break;
    }
    $isPushed = \think\Queue::push($className, $data, $queueName);
    if ($isPushed) return true;
    return false;
}


/**
 * 获取后台用户登录信息
 * @return mixed
 */
function get_admin_info()
{
    return \think\facade\Session::get('admin_info');
}

/**
 * 驼峰法转下划线
 * @param $str
 * @return string
 */
function tf_to_xhx($str)
{
    return trim(preg_replace_callback('/([A-Z]{1})/', function ($matches) {
        return '_' . strtolower($matches[0]);
    }, $str), '_');
}

// 格式化时间戳
function get_current_date()
{
    return date('Y-m-d H:i:s', time());
}

/**
 * 返回json数据
 * @param $success
 * @param $code
 * @param string $message
 * @param array $data
 * @return Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
 */
function responseJson($success, $code = 0, $message = '', $data = [])
{

    if (empty($message)) {
        $message = '未知信息';
    }
    if (empty($data)) {
        $data = '';
    }

    $result = [
      'success' => $success,
      'message' => $message,
      'code' => $code,
      'data' => $data,
    ];
    \think\facade\Log::info('前台输出：' . json_encode($result));

    $response = \think\facade\Response::create($result, 'json');
    $response->send();
    exit();
}

/**
 * 添加操作日志
 * @param string $remark 备注
 * @param string $type admin 后台  shop 商户
 */
function add_operateLogs($remark, $type = 'admin')
{
    if ($type == 'admin') {
        $user = \think\facade\Session::get('admin_info');
    } elseif ($type == 'shop') {
        $user = \think\facade\Session::get('shop_info');
    }

    $data = [
      'uid' => $user['id'],
      'remark' => $remark,
      'ip' => request()->ip(),
      'created_at' => date('Y-m-d H:i:s', time()),
      'type' => 1,
      'content' => json_encode(request()->param())
    ];

    if ($type == 'admin') {
        $data['from'] = 'admin';
    } else {
        $data['from'] = 'shop';
    }
    \app\common\model\AdminOperateLogs::create($data);
}


/**
 * 验证权限
 * @param string $role
 * @return bool
 */
function check_role($role = '')
{
    $admin_info = get_admin_info();
    if ($admin_info['id'] == 1) {
        return true;
    }
    $auth = new \app\common\library\Auth();
    return $auth->check($role, $admin_info['id']);
}


/**
 * 获取加密盐
 * @param int $length
 * @return string
 */
function get_salt($length = 8)
{
    // 密码字符集，可任意添加你需要的字符
    $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
      'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
      't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
      'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
      'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
      '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '!',
      '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_',
      '[', ']', '{', '}', '<', '>', '~', '`', '+', '=', ',',
      '.', ';', ':', '/', '?', '|');
    $keys = array_rand($chars, $length);
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        // 将 $length 个数组元素连接成字符串
        $password .= $chars[$keys[$i]];
    }
    return $password;
}

// 本地Redis
function location_redis()
{
    return \redis\BaseRedis::location();
}

function message_redis()
{
    return \redis\BaseRedis::message();
}

/**
 * 获取毫秒数
 * @return float
 */
function get_millisecond()
{
    list($msec, $sec) = explode(' ', microtime());
    $millisecond = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $millisecond;
}

/**
 * 生成随机字符
 * @param int $length
 * @return string
 */
function rand_char($length = 6)
{
    // 密码字符集，可任意添加你需要的字符
    $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
      'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
      't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
      'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
      'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
      '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '!',
      '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_',
      '[', ']', '{', '}', '<', '>', '~', '`', '+', '=', ',',
      '.', ';', ':', '/', '?', '|');
    $keys = array_rand($chars, $length);
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        // 将 $length 个数组元素连接成字符串
        $password .= $chars[$keys[$i]];
    }
    return $password;
}


/**
 *  RSA加密
 * @param mixed $data
 * @return string
 */
function rsa_encode($data)
{
    $rsa_public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC9FEUt1wc+HnTqYx6+sG0E0Szq
ubnLFePfvlOHUXuMUr2sgsQITIE75iushfK9K4R0r/Kn8Gui6q76czqfF9aonCxf
RBkDOgRmqecrbftNQ0OrhIH6OJzORXSU04kQaJgbViw/gqNmJloKKvPkMyzjiwt8
iUogyQGO8hYwyotx/wIDAQAB
-----END PUBLIC KEY-----';
    // 从证书中解析公钥，以供使用
    $public_key = openssl_pkey_get_public($rsa_public_key);
    // 使用公钥加密数据
    openssl_public_encrypt($data, $crypted, $public_key);
    return base64_encode($crypted);
}

/**
 * RSA解密
 * @param $data
 * @return mixed
 */
function rsa_decode($data)
{
    $rsa_private_key = '-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAL0URS3XBz4edOpj
Hr6wbQTRLOq5ucsV49++U4dRe4xSvayCxAhMgTvmK6yF8r0rhHSv8qfwa6Lqrvpz
Op8X1qicLF9EGQM6BGap5ytt+01DQ6uEgfo4nM5FdJTTiRBomBtWLD+Co2YmWgoq
8+QzLOOLC3yJSiDJAY7yFjDKi3H/AgMBAAECgYEAgMCT1wIsoWU18gFrByi2I+iY
cIHl/V+7mzlMQcH/om8ZT6Z//LKz8ejrZoCT6bL/cEH7t9YkRX0Ph+X9TiZ6eYod
ET/gkiuN4S7bCmyKrC9D4umQXc1yppMVl9WtBuY86rq+kzU6/ULLtA14BKGNjoIh
bACL9iRayZsAcS51llECQQD67c4OLguBHaxxxXGoSAq851+kINBnpWQlkaMTw5W3
HRADUB1tSxSIq2ZLOHhJYBpl0BCF8j5GB52yqGpjvoqnAkEAwOZ7bf+PqxZwWkkD
T1/KYCHr3+bduq78dPT3+6qVY577sxY+V5mHHvVwq2XvwxPyJULd97LTix9bC8zz
8w7A6QJALwKaVgG+WgQrKG1rK7HDgTx/qIoVQTW1G2y7dppv1Ax30YcS3ETypeAm
m/UKZATDLUvbrJyDmi8XFj+DHwi1hQJBAKDSx89ChRYfxBYRz1eqxj/1qADpKq1M
3J/p6KICa0A+ORzrC5jfIB84g/HyL74VcAmOwR6VEfdocfDZs/1NrJkCQQC3nhdX
IVwCfpdEUgWqlivl6LMzGOqwEe2gkn7bYJXB1L0ecrlfrRd3Oble7Okh0VVD5EnB
PopB7i04RiwfcYdT
-----END PRIVATE KEY-----';
    //  从证书中解析私钥，以供使用
    $private_key = openssl_pkey_get_private($rsa_private_key);
    // 使用私钥解密数据
    openssl_private_decrypt(base64_decode($data), $decrypted, $private_key);
    return $decrypted;
}

/**
 * 返回json数据
 * @param int $code
 * @param string $msg
 * @param array $data
 * @param int $http_code
 * @param bool $is_object
 * @return \think\response\Json
 */
function jsonResponse($code = 0, $msg = '', $data = [], $http_code = 200, $is_object = true)
{
    if (empty($data) && $is_object) {
        $data = (object)$data;
    }
    $result = [
      'code' => $code,
      'msg' => $msg,
      'data' => $data,
    ];
    \think\facade\Log::debug('Api Return：' . json_encode($result));
    return json($result, $http_code);
}

/**
 * 后台json输出
 * @param $success
 * @param $message
 * @param array $data
 * @param int $code
 * @param int $http_code
 * @return \think\response\Json
 */
function rJson($success, $message, $data = [], $code = 0, $http_code = 200)
{
    if (empty($message)) {
        $message = '未知信息';
    }
    if (empty($data)) {
        $data = (object)[];
    }

    $result = [
      'success' => $success,
      'message' => $message,
      'code' => $code,
      'data' => $data,
    ];

    \think\facade\Log::debug('前台输出：' . json_encode($result));

    return json($result, $http_code);
}

// 其他格式字符串装换为UTF-8
function gbk_utf8($str)
{
    return mb_convert_encoding(urldecode($str), 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
}

function utf8_gb2312($str)
{
    return iconv("utf-8", "gbk//IGNORE", $str);
}