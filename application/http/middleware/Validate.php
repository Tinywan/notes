<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/11/7 9:11
 * |  Mail: 756684177@qq.com
 * |  Desc: 公用中间件验证器
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\http\middleware;


use think\Controller;

class Validate extends Controller
{
    /**
     * 默认返回资源类型
     * @param $request
     * @param \Closure $next
     * @param $name
     * @return mixed|\think\response\Json
     */
    public function handle($request, \Closure $next, $name)
    {
        //获取当前参数
        $params = $request->param();
        //获取访问模块
        $module = $request->module();
        //获取访问控制器
        $controller = ucfirst($request->controller());
        //获取操作名,用于验证场景scene
        $scene    = $request->action();
        $validate = "app\\" . $module . "\\validate\\" . $controller;
        // 仅当验证器存在时 进行校验
        if (class_exists($validate)) {
            $v = $this->app->validate($validate);
            if ($v->hasScene($scene)) {
                //仅当存在验证场景才校验
                $result = $this->validate($params, $validate . '.' . $scene);
                if (true !== $result) {
                    //校验不通过则直接返回错误信息
                    return json(['code' => -1, 'msg' => $result]);
                }
            }
        }
        return $next($request);
    }
}