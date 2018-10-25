<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/10/25 9:51
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

/**
 * 返回json数据
 * @param int $code
 * @param string $msg
 * @param array $data
 * @param int $http_code
 * @param bool $is_object
 * @return \think\response\Json
 */
function jsonResponse($code = -1, $msg = '', $data = [], $http_code = 200, $is_object = true)
{
    if (empty($data) && $is_object){
        $data = (object)$data;
    }
    $result = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    ];
    \think\facade\Log::debug('[网关] 接口返回JSON数据：' . json_encode($result));
    return json($result,$http_code);
}

/**
 * @param string $modelName 模型名称
 * @param int $id 主键id
 * @return string
 */
function get_cache_key($modelName, $id)
{
    return strtolower($modelName) . ':' . $id;
}