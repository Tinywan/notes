<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\AdminSidebar;
use app\common\model\SystemConfig;
use think\facade\Cache;

class System extends AdminController
{
    public function sideBar()
    {
        $side = AdminSidebar::where(['tid' => 0,'status' => 1])->order('sort')->select();
        $side_child = AdminSidebar::where('status','=',1)
          ->where('tid','neq', 0)->order('sort')->select();
        if (!empty($side)){
            foreach ($side as $key => &$value) {
                $child = array();
                if (!empty($side_child)){
                    foreach ($side_child as $_key => $_value) {
                        if($_value['tid'] == $value['id']) {
                            $child[] = $_value;
                        }
                    }
                }
                $value['child'] = $child;
            }
            unset($value);
        }
        $this->assign('function_rule', [
            'create' => check_role('admin/system/addsidebar'),
            'edit' => check_role('admin/system/editsidebar'),
            'delete' => check_role('admin/system/delsidebar'),
            'rule' => check_role('admin/auth/rule'),
        ]);
        $this->assign('sidebar', $side);
        return view();
    }

    /**
     * 添加侧边栏
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function addSidebar(){
        if($this->request->isPost()){
            $data = $this->request->post();
            if (empty($data['name'])){
                return responseJson(false, -1, '菜单名称不能为空');
            }
            $result = AdminSidebar::create($data);
            if($result){
                add_operateLogs('添加侧边栏['.$data['name'].']');
                return responseJson(true, 0, '添加成功');
            }else{
                return responseJson(false, -1, '添加失败');
            }
        }
        $this->assign('top_sidebar', AdminSidebar::getList(['tid' => 0]));
        return view();
    }

    /**
     * 编辑侧边栏
     * @param $id
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     * @throws \think\exception\DbException
     */
    public function editSidebar($id){
        $sidebar = AdminSidebar::get($id);
        $name = $sidebar->name;
        if($this->request->isPost()){
            $data = $this->request->post();
            if(empty($sidebar)){
                return responseJson(false, -1, '记录不存在');
            }
            $rows = $sidebar->save($data);
            if($rows >= 0){
                add_operateLogs('编辑侧边栏['.$name.']为['.$data['name'].']');

                return responseJson(true, 0, '更新成功');
            }else{
                return responseJson(false, -1, '更新失败');
            }
        }
        $this->assign('top_sidebar', AdminSidebar::getList(['tid' => 0]));
        $this->assign('info', $sidebar);
        $this->assign('id', $id);
        return view();
    }

    /**
     * 删除侧边栏
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     * @throws \think\exception\DbException
     */
    public function delSidebar(){
        $id = $this->request->post('id');
        if(empty($id)){
            return responseJson(false, -1, '缺少参数');
        }
        $rows = AdminSidebar::where(['tid' => $id])->count();
        if($rows > 0){
            return responseJson(false, -2, '存在下级菜单，请先处理下级菜单！');
        }
        $data =  AdminSidebar::get($id);
        $res = AdminSidebar::destroy($id);

        if($res){
            add_operateLogs('删除侧边栏['.$data['name'].']');
            return responseJson(true, 0, '删除成功');
        }else{
            return responseJson(false, -3, '删除失败');
        }
    }

    /**
     * 清除缓存
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function cleanCache(){
        if ($this->rmCache()){
            return responseJson(true, 0, '缓存清除成功！');
        }else{
            return responseJson(false, -1, '缓存清除失败！');
        }
    }

    /**
     * 系统配置
     */
    public function config(){
        $group_id = $this->request->param('group_id', 1);

        $list = SystemConfig::all(['group_id' => $group_id, 'is_show' => 1]);

        $this->assign([
            'group' => config('config_group'),
            'group_id' => $group_id,
            'list' => $list
        ]);

        return view();
    }

    /**
     * 更新系统配置
     */
    public function updateConfig(){
        $post = $this->request->post();

        $list = [];
        foreach ($post as $key => $item) {
            $list[] = ['id' => $key, 'config_value' => $item];
        }

        $model = new SystemConfig();
        $result = $model->isUpdate()->saveAll($list);
        if ($result){
            Cache::rm('system_config');
            add_operateLogs('更新了系统配置');
            return responseJson(true, 0, '保存成功！');
        }else{
            return responseJson(false, -1, '保存失败！');
        }
    }

    /**
     * 修改密码
     */
    public function updatePassword(){

        $admin_id = Session::get('admin_info')['id'];

        $data = request()->post('password');
        if (empty($data)) responseJson(false, -1, '请输入新密码！');
        $salt = rand_char();
        $password = md5(md5($data).md5($salt));

        $admin = \app\common\model\Admin::get($admin_id);
        if (empty($admin)){
            return responseJson(false, -1, '用户不存在！');
        }
        $result = $admin->isUpdate(true)->save([
            'password' => $password,
            'salt' => $salt
        ]);

        if ($result){
            responseJson(true, 1, '修改成功！');
        }else{
            responseJson(false, -1, '修改失败！');
        }
    }

    /**
     * 上传文件
     */
    public function upLoad(){
        $dir = request()->post('type', 'file');
        $file = request()->file('file');
        if (!empty($file)){
            $info = $file->validate(['size' => 20000000, 'ext'=>'jpg,png,gif,pfx'])->move(ROOT_PATH . 'public/uploads/'.$dir);
            if($info){
                $file_path = str_replace("\\", "/", '/uploads/'.$dir.'/'.$info->getSaveName());
                return ['status' => 1, 'msg' => '上传成功！', 'path' => $file_path, 'url' => $file_path];
            }else{
                return ['status' => -1, 'msg' => $file->getError()];
            }
        }else{
            return ['status' => -1, 'msg' => '未选择文件'];
        }
    }
}
