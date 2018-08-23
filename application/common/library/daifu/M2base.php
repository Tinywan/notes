<?php

namespace app\common\library\daifu;

use Memcache;
use think\Log;

class M2base
{
    private $config;
    private $sign_arr; // 签名数组
    public $memcache;
    private $access_token;
    private $api_token;
    private $timestamp;
    private $nonce;

    public function __construct($config)
    {
        $this->config = config('new_daifu_config');
        $this->config = array_merge($this->config, [
          'aid' => $config['aid'],
          'key' => $config['key']
        ]);

        if (empty ($this->config ['api_id'])) {
            $this->config['api_id'] = $this->api_token ['api_server_code'];
        }

        if ($this->config ['mode'] == 1) {//4步模式
            if ($this->config ['memcache_open']) {
                $this->memcache = new Memcache ();
                $this->memcache->connect($this->config ['memcached_server']);
            }
        }
        $this->nonce = rand(000000, 999999);//随机数
        $this->timestamp = $this->java_timestamp();
    }

    /**
     *
     * @param json $post_data
     *            要发送的数据
     * @param string $method
     * @param string $forward
     * @param string $error_url
     *            请求错误时跳转的url
     * @return Ambigous <string, mixed>
     */
    public function url_data($api = 'T288', $post, $method = 'post', $forward = '', $error_url = '')
    {
        $url = $this->create_m2_url($api, $post, $method, $forward, $error_url);
        $post = json_encode($post);
        Log::debug(' 美付宝代付请求数据 =》' . $url . '(' . $post . ')');
        $data = $this->http_curl($url, $post);
        Log::debug(' 美付宝代付请求返回 =》' . $data);
        return $data;
    }


    /**
     *
     * @param json $post_data
     * @param string $method
     * @param string $forward
     * @param string $error_url
     * @return string
     */
    public function create_m2_url($api, $post_data, $method = 'POST', $forward = '', $error_url = '')
    {
        if ($this->config ['mode'] == '0') {//10步请求
            $params['aid'] = $this->config['aid'];
            $params['timestamp'] = $this->timestamp;
            $params['nonce'] = $this->nonce;
            $params['signature'] = 'SIGNATURE';
            $params ['api_id'] = $this->config ['api_id'][$api];
            // 有跳转请求
            if (!empty ($forward)) {
                $params['forward'] = $forward;
            }
            if (!empty ($error_url)) {
                $params['error_url'] = $error_url;
            }
            if ($this->m_arr ['is_data_sign'] == 1) {
                $params['data_sign'] = $this->data_signature($post_data);
            }

            if (strtoupper($method) == 'GET') {
                $params['data'] = $post_data;
                $params['method'] = $method;
            }
            $sign_str = $this->get_signature($params);
            $url = $this->create_url($this->config ['url'], $params, $sign_str);
        } elseif ($this->config['mode'] == 1) {//4步请求
            $access_token = $this->is_access_token();
            // 判断access_token是否失效
            if ($access_token && !$this->do_filter_apiid($access_token ['lost_api_ids'])) {
                // 未失效，直接使用
                $params = array(
                  'aid' => $this->config['aid'],
                  'api_id' => $this->config['api_id'],
                  'access_token' => $access_token ['access_token'],
                );
                // 有跳转请求时
                if (!empty ($forward)) {
                    $params ['forward'] = $forward;
                }
                if (!empty ($error_url)) {
                    $params ['error_url'] = $error_url;
                }
                // GET请求时
                if (strtoupper($method) == 'GET') {
                    $params ['data'] = $post_data;
                    $params ['method'] = $method;
                }

                $url = $this->create_url($this->config['url_ac'], $params);
            } else {//开始登陆获取token
                $token = $this->get_access_token($api);

                if ($token && !$this->do_filter_apiid($token ['lost_api_ids'])) {
                    $params = array(
                      'aid' => $this->config ['aid'],
                      'api_id' => $this->config['api_id'][$api],
                      'access_token' => $token ['access_token']
                    );
                    // 有跳转请求时
                    if (!empty ($forward)) {
                        $params ['forward'] = $forward;
                    }
                    if (!empty ($error_url)) {
                        $params['error_url'] = $error_url;
                    }
                    // GET请求时
                    if (strtoupper($method) == 'GET') {
                        $params ['data'] = $post_data;
                        $params ['method'] = $method;
                    }
                    //var_dump($this->m_arr ['mode']);die;
                    $url = $this->create_url($this->config['url_ac'], $params);

                } else {
                    // 如果没有激活token
                    $this->m_arr ['mode'] = 0;
                    $url = $this->create_m2_url($api, $post_data);
                }
            }
        }

        return $url;
    }

    /**
     * 根据给定的参数组合成url
     *
     * @param str $url
     *            网关地址
     * @param arr $creat_url_arr
     *            需要的参数数组
     * @param string $sign_str
     *            加密串
     * @return string
     */
    public function create_url($url, $params, $sign_str = '')
    {
        $str = http_build_query($params);
        if (!empty ($sign_str)) {
            $str .= "&signature=" . $sign_str;
        }
        $url = $url . "?" . $str;
        return $url;
    }


    /**
     * 加密函数
     *
     * @return string
     */
    public function get_signature($params)
    {
        if (!empty ($this->config['key'])) {
            $params['key'] = $this->config['key'];
        }
        usort($params, 'nextpermu');
        $sign_str = sha1(implode('', $params));
        return strtoupper($sign_str);
    }

    /**
     * 对数据包进行签名
     * @param $data
     * @return string
     */
    public function data_signature($data)
    {
        return strtoupper(sha1($data));
    }

    /**
     * 生成java格式的时间数据
     * @return string
     */
    function java_timestamp()
    {
        $time = time();
        return $time . '000';
    }

    /**
     * token是否有效
     * @return mixed|boolean
     */
    public function is_access_token()
    {
        date_default_timezone_set('PRC');
        if ($this->config ['memcache_open']) {
            $access_token = $this->memcache->get('access_token');
        } else {
            $_SESSION['token'] = $this->access_token;
            $access_token = $this->access_token;
        }
        $now_time = time();
        $start_time = strtotime($access_token ['start_time']);
        if ($now_time - $start_time < $access_token ['expire']) {
            return $access_token;
        } else {
            return false;
        }
    }

    /**
     * 判断api_id是否在忽略的数组集里
     *
     * @param array $lost_api_ids
     * @return boolean
     */
    public function do_filter_apiid($lost_api_ids)
    {
        if (in_array($this->config['api_id'], $lost_api_ids)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取token
     *
     * @return boolean mixed
     */
    public function get_access_token($api)
    {
        $this->sign_arr = array(
          'aid' => $this->config['aid'],
          'nonce' => $this->nonce,
          'timestamp' => $this->timestamp
        );
        $creat_url_arr = $this->sign_arr;
        $sign_str = $this->get_signature($creat_url_arr);
        $url = $this->create_url($this->config['url_ac_token'], $creat_url_arr, $sign_str);


        $data = $this->get_curl($url, '');

        $data_arr = json_decode($data, true);
        if (isset ($data_arr ['err_code'])) {
            $this->config ['max_token'] -= 1;
            if ($this->config ['max_token'] >= 0) {
                $this->get_access_token($api);
            } else {
                return false;
            }
        } else {
            if ($this->config['memcache_open']) {
                $this->memcache->add('access_token', $data_arr);
            }
            $_SESSION['token'] = $data_arr;    //给session赋值token值
            $this->access_token = $data_arr;
            return $data_arr;
        }
    }


    /**
     * http的curl 方法post请求接口
     * @param string $url
     * @param string $post_data
     * @return string
     */
    public function http_curl($url, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($post_data))
        );
        $output = curl_exec($ch);
        curl_close($ch);
        //返回数据
        return $output;
    }

    public function is_json($string)
    {
        if (version_compare(PHP_VERSION, '5.3.0', 'ge')) {
            json_decode($string);
            return (json_last_error() == JSON_ERROR_NONE);
        } else {
            return is_null(json_decode($string));
        }
    }

    public function get_curl($url, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        $output = curl_exec($ch);
        curl_close($ch);
        //返回数据
        //var_dump($output);die;
        return $output;
    }

}


