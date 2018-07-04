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
 * @param  array $address 需要发送的邮箱地址 发送给多个地址需要写成数组形式
 * @param  string $subject 标题
 * @param  string $content 内容
 * @return array  放回状态吗和提示信息
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
    $phpmailer = new \PHPMailer\PHPMailer\PHPMailer();
    $phpmailer->SMTPDebug = 0;
    $phpmailer->IsSMTP();
    $phpmailer->SMTPAuth = true;
    $phpmailer->SMTPSecure = 'ssl';
    $phpmailer->Host = $email_smtp_host;
    $phpmailer->Port = 465;
    $phpmailer->Hostname = $email_host;
    $phpmailer->CharSet = 'UTF-8';
    $phpmailer->FromName = $email_username;
    $phpmailer->Username = $email_username;
    $phpmailer->Password = $email_password;
    $phpmailer->From = $email_username;
    $phpmailer->IsHTML(true);
    if (is_array($address)) {
        foreach ($address as $addressv) {
            if (false === filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return ["errorCode" => 1, "msg" => '邮箱格式错误'];
            }
            $phpmailer->AddAddress($addressv, $address . '的[' . $subject . ']');
        }
    } else {
        if (false === filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return ["errorCode" => 1, "msg" => '邮箱格式错误'];
        }
        $phpmailer->AddAddress($address, $address . '的[' . $subject . ']');
    }
    $phpmailer->Subject = $subject;
    $phpmailer->Body = $content;
    if (!$phpmailer->Send()) {
        return ["errorCode" => 1, "msg" => $phpmailer->ErrorInfo];
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

    //AdminOperateLogs::create($data);
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
    $auth = new Auth();
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