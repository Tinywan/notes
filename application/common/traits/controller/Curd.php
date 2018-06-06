<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/5 13:15
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\traits\controller;

use think\Db;
use think\facade\App;
use think\facade\Env;
use think\facade\Validate;
use think\facade\View;
use traits\controller\Jump;

trait Curd
{
    use Jump;

    /**
     * @var mixed 模型
     */
    protected $model;

    /**
     * @var string 默认模型对应表别名
     */
    protected $alias = '_a';

    /**
     * @var array 视图文件
     */
    protected $views = [
        'index' => 'curd/index',
        'create' => 'curd/create',
        'edit' => 'curd/edit',
    ];

    /**
     * @var object 自定义视图
     */
    protected $view;

    /**
     * @var int 每页多少条
     */
    protected $perPage  = 20;

    /**
     * @var string 页面名称
     */
    protected $label = '';

    /**
     * @var string 路由前缀
     */
    protected $route = '';

    /**
     * @var array 表单验证规则
     */
    protected $validateRules = [];

    /**
     * @var array 表单验证字段注释
     */
    protected $validateRulesField = [];

    /**
     * @var array 列表查询条件
     */
    protected $where = [];

    /**
     * @var array 列表排序
     */
    protected $order = ['id' => 'desc'];

    /**
     * @var array 查询字段
     */
    protected $fields       = ['*'];

    /**
     * @var array 列表显示字段
     */
    protected $listFields   = [];

    /**
     * @var array 添加表单显示字段
     */
    protected $addFormFields   = [];

    /**
     * @var array 更新表单显示字段
     */
    protected $updateFormFields   = [];

    /**
     * @var array 搜索显示字段
     */
    protected $searchFields = [];

    /**
     * @var string 表前缀
     */
    protected $dbPre = '';

    /**
     * @var array 添加和编辑弹框大小
     */
    protected $modelSize = ['x' => '550px', 'y' => '85%'];

    /**
     * 数据字典
     * @var array
     * type
     * text  文本输入
     * textarea 文本框
     * status 状态
     * guanlian 关联
     *
     */
    protected $translations = [];

    /**
     * @var array 操作按钮的显示与否
     */
    public $function = [
        'create'                 => 1,
        'edit'                   => 1,
        'delete'                 => 1,
        'search'                 => 0
    ];

    /**
     * 更多操作
     * @var array
     */
    public $moreFunction = [];


    public function __construct()
    {
        // 初始化模型
        if ($this->model()) {
            $this->model = App::invokeClass($this->model());
        }
        // 自定义视图路径 app\common\traits\controller
        $this->view = View::config(['view_path' => Env::get('root_path') . 'application/common/traits/view/']);
        $this->init();
        $this->initField();
        $this->dbPre = config('database.prefix');

        if (tf_to_xhx(request()->module()) == 'admin'){
            $admin_info = get_admin_info();
            if ($admin_info['id'] != 1){
                $this->checkButtonRole($admin_info['id']);
            }
        }
    }

    /**
     * 模型
     * @return mixed
     */
    abstract function model();

    /**
     * 初始化
     * @return mixed
     */
    abstract function init();

    /**
     * 初始化列表和表单显示字段
     */
    private function initField(){
        // 如果没有设置字段，则默认显示所有
        if (empty($this->listFields)){
            foreach ($this->translations as $key => $value) {
                $this->listFields[] = $key;
            }
        }
        if (empty($this->addFormFields)){
            foreach ($this->translations as $key => $value) {
                if ($key != 'id'){
                    $this->addFormFields[] = $key;
                }
            }
        }
        if (empty($this->updateFormFields)){
            $this->updateFormFields = $this->addFormFields;
        }
    }

    /**
     * 列表
     * @return string
     * @throws \think\Exception
     */
    public function index()
    {
        $list = $this->getList();
        if ($this->views['index'] != 'curd/index') $this->view = new \think\facade\View();
        $this->view->assign([
            'list' => $list,
            'list_array' => $list->toArray(),
            'table' => $this->getListHtml($list),
            'search_html' => $this->getSearchHtml(),
            'function' => $this->function,
            'label' => $this->label,
            'route' => $this->route,
            'model_size' => $this->modelSize
        ]);

        return $this->view->fetch($this->views['index']);
    }

    /**
     * 获取列表数据
     * @return mixed
     */
    protected function getList(){

        $get = request()->get();

        $where = [];
        foreach ($get as $key => $value) {
            if ($key == 'page'){
                continue;
            }
            if ($value != ''){
                if (!empty($this->translations[$key]['type']) && $this->translations[$key]['type'] == 'join'){
                    $key = $this->translations[$key]['data']['alias'].'.'.$this->translations[$key]['data']['show_field'];
                }else{
                    $key = '_a.'.$key;
                }
                if (is_numeric($value)){
                    $where[$key] = $value;
                }else{
                    $where[$key] = ['like', '%'.$value.'%'];
                }
            }
        }
        $this->where = array_merge($where, $this->where);

        $field = [$this->alias.'.*'];
        $join = [];

        foreach ($this->translations as $key => $value) {
            if (!empty($value['type'])){
                switch ($value['type']){
                    case 'join':
                        $field[] = $value['data']['alias'].'.'.$value['data']['show_field'];
                        $join[] = [
                            'table' => $this->dbPre.$value['data']['table'].' '.$value['data']['alias'],
                            'where' => $this->alias.'.'.$key.' = '.$value['data']['alias'].'.'.$value['data']['value_field']
                        ];
                        break;
                    case 'alias':
                        $field[] = $this->alias.'.'.$key.' as '.$value['alias'];
                        break;
                }
            }
        }
        $list = $this->model->alias($this->alias)->field($field)->where($this->where);
        if (!empty($join)){
            foreach ($join as $value) {
                $list->join($value['table'], $value['where'], 'left');
            }
        }
        return $list->order($this->order)->paginate($this->perPage, false, ['query' => $get]);

    }

    /**
     * 添加
     * @return string|\think\response\View
     * @throws \think\Exception
     */
    public function create(){
        if ($this->function['create'] == 0){ $this->error('页面不存在！'); }

        if ($this->views['create'] != 'curd/create') $this->view = new View();

        $this->view->assign([
            'form_html' => $this->getCreateHtml(),
            'label' => $this->label,
            'route' => $this->route
        ]);
        return $this->view->fetch($this->views['create']);
    }

    /**
     * 获取联查表单数据列表
     */
    protected function getJoinDateList($data){
        $list = Db::table($this->dbPre.$data['table']);
        if (!empty($data['where'])){
            $list->where($data['where']);
        }

        return $list->select();
    }

    /**
     * 添加数据
     */
    public function save(){
        $data = request()->post();
        $validate = new Validate($this->getValidateRule(), $this->getValidateMessage(), $this->getValidateFieldName());
        $result   = $validate->check($data);
        if(!$result){
            if (request()->isAjax()){
                return responseJson(false, -1, $validate->getError());
            }else{
                $this->error($validate->getError());
            }
        }
        $vali   = $this->saveBeforeValidate($data);
        if($vali['err_code'] != '0'){
            if (request()->isAjax()){
                return responseJson(false, -1, $vali['err_msg']);
            }else{
                $this->error($vali['err_msg']);
            }
        }

        $data['created_at'] = time();
        $data['updated_at'] = time();
        $data = $this->disposeData($data);

        // 保存默认数据
        $before_data = $this->saveBeforeData($data);
        if ($before_data){
            $data = array_merge($data, $before_data);
        }
        $ret = $this->saveOtherValidate($data);
        if ($ret['err_code'] != 0){
            if (request()->isAjax()){
                return responseJson(false, -1, $ret['err_msg']);
            }else{
                $this->error($ret['err_msg']);
            }
        }

        $res = $this->model->allowField(true)->save($data);
        if (request()->isAjax()){
            if ($res){
                add_operateLogs('添加['.$this->label.']记录');
                return responseJson(true, 0, '添加成功！');
            }else{
                return responseJson(false, -1, '添加失败！');
            }
        }else{
            if ($res){
                add_operateLogs('添加['.$this->label.']记录');
                $this->success('添加成功！');
            }else{
                $this->error('添加失败！');
            }
        }
    }

    /**
     * 添加前额外验证
     * @param $data
     * @return array
     */
    protected function saveOtherValidate($data){
        return [
            'err_code' => 0,
            'err_msg' => 'ok'
        ];
    }

    /**
     * 更新前额外验证
     * @param $data
     * @return array
     */
    protected function updateOtherValidate($data){
        return [
            'err_code' => 0,
            'err_msg' => 'ok'
        ];
    }

    /**
     * 处理进入数据库的数据
     * @param $data
     * @return array
     */
    protected function disposeData($data){

        $result = [];
        foreach ($data as $key => $value) {
            if (!empty($this->translations[$key]['type'])){
                switch ($this->translations[$key]['type']){
                    case 'password':
                        if (!empty($value)){
                            $result['salt'] = rand_char();
                            $result[$key] = md5(md5($value).md5($result['salt']));
                        }else{
                            unset($data[$key]);
                        }
                        break;
                    case 'time':
                        if (!is_numeric($value)){
                            $result[$key] = strtotime($value);
                        }else{
                            $result[$key] = $value;
                        }
                        break;
                    case 'checkbox':
                        if (!empty($value) && is_array($value)){
                            $result[$key] = trim(implode(',', array_unique($value)), ',');
                        }else{
                            $result[$key] = '';
                        }
                        break;
                    default:
                        $result[$key] = $value;
                        break;
                }
            }else{
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 获取添加表单验证规则
     * @return array
     */
    protected function getValidateRule(){
        $rule = [];
        foreach ($this->addFormFields as $value){
            if (isset($this->translations[$value]['validate_rule'])){
                if ($this->translations[$value]['validate_rule'] != false){
                    $rule[$value] = $this->translations[$value]['validate_rule'];
                }
            }else{
                $rule[$value] = 'require';
            }
        }
        return $rule;
    }

    /**
     * 获取编辑表单验证规则
     * @return array
     */
    protected function getEditValidateRule($id){
        $rule = [];
        foreach ($this->updateFormFields as $value){
            if (isset($this->translations[$value]['validate_rule'])){
                if ($this->translations[$value]['validate_rule'] != false){
                    $rule[$value] = $this->translations[$value]['validate_rule'];
                }
            }else{
                $rule[$value] = 'require';
            }
        }
        return $rule;
    }

    /**
     * 获取表单验证字段名称
     * @return array
     */
    protected function getValidateFieldName(){
        $filed = [];
        foreach ($this->addFormFields as $value){
            $filed[$value] = $this->translations[$value]['text'];
        }
        return $filed;
    }

    /**
     * 编辑
     * @return string|\think\response\View
     * @throws \think\Exception
     */
    public function edit($id){
        if ($this->function['edit'] == 0){ $this->error('页面不存在！'); }

        $info = $this->model->get($id);
        if (empty($info)) $this->error('记录不存在！');

        if ($this->views['edit'] != 'curd/edit') $this->view = new View();

        $this->view->assign([
            'form_html' => $this->getEditHtml($info->toArray()),
            'id' => $id,
            'label' => $this->label,
            'route' => $this->route
        ]);
        return $this->view->fetch($this->views['edit']);
    }

    /**
     * 添加数据
     */
    public function update($id){
        $data = request()->post();

        $validate = Validate::make($this->getEditValidateRule($id), $this->getValidateMessage(), $this->getValidateFieldName());
        $result   = $validate->check($data);
        if(!$result){
            if (request()->isAjax()){
                return responseJson(false, -1, $validate->getError());
            }else{
                $this->error($validate->getError());
            }
        }

        $vali   = $this->updateBeforeValidate($id, $data);
        if($vali['err_code'] != '0'){
            if (request()->isAjax()){
                return responseJson(false, -1, $vali['err_msg']);
            }else{
                $this->error($vali['err_msg']);
            }
        }

        $data['updated_at'] = time();

        $info = $this->model->get($id);

        $data = $this->disposeData($data);

        // 保存默认数据
        $before_data = $this->updateBeforeData();
        if ($before_data){
            $data = array_merge($data, $before_data);
        }

        $ret = $this->updateOtherValidate($data);
        if ($ret['err_code'] != 0){
            if (request()->isAjax()){
                return responseJson(false, -1, $ret['err_msg']);
            }else{
                $this->error($ret['err_msg']);
            }
        }

        $res = $info->allowField(true)->save($data);
        if (request()->isAjax()){
            if ($res >= 0){
                add_operateLogs('编辑['.$this->label.']序号为['.$id.']的记录');
                return responseJson(true, 0, '保存成功！');
            }else{
                return responseJson(false, -1, '保存失败！');
            }
        }else{
            if ($res >= 0){
                add_operateLogs('编辑['.$this->label.']序号为['.$id.']的记录');
                $this->success('保存成功！');
            }else{
                $this->error('保存失败！');
            }
        }
    }

    /**
     * 获取验证提示信息
     */
    protected function getValidateMessage(){
        return [];
    }

    /**
     * 记录删除
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function delete(){
        $id = request()->param('id');
        if (empty($id)){
            return responseJson(false, -1, '请选择要删除的记录!');
        }
        $id = trim($id, ',');
        $id_array = explode(',', $id);
        $res = $this->model->destroy($id_array);
        if ($res){
            add_operateLogs('删除['.$this->label.']序列号为['.$id.']的记录');
            return responseJson(true, 0, '删除成功!');
        }else{
            return responseJson(false, -1, '删除失败!');
        }
    }

    /**
     * 获取列表页表格html
     * @param $list
     * @return string
     */
    private function getListHtml($list){

        $table = '<table class="table table-hover" id="list">';
        $table .= '<thead><tr class="text-c">';
        if ($this->function['delete'] == 1){
            $table .= '<th class="text-center" style="width: 50px;">选择</th>';
        }
        foreach ($this->listFields as $value) {
            $table .= '<th>';
            $table .= $this->translations[$value]['text'];
            $table .= '</th>';
        }
        if ($this->function['edit'] == 1 || $this->function['delete'] == 1){
            $table .= '<th>操作</th>';
        }
        $table .= '</tr></thead>';
        $table .= '<tbody>';
        foreach ($list as $key => $value) {
            $table .= '<tr class="text-c">';
            if ($this->function['delete'] == 1){
                $table .= '<td class="text-center"><input type="checkbox" class="ids" value="'.$value['id'].'"></td>';
            }
            foreach ($this->listFields as $v) {
                if (empty($this->translations[$v]['type'])){
                    $table .= '<td>';
                    $table .= $value[$v];
                    $table .= '</td>';
                }else{
                    $table .= '<td>';
                    switch ($this->translations[$v]['type']){
                        case 'radio':
                            //radio
                            $table .= '<span class="'.$this->translations[$v]['list'][$value[$v]][0].'">'.$this->translations[$v]['list'][$value[$v]][1].'</span>';
                            break;
                        case 'checkbox':
                            // 多选框
                            $__value = explode(',', trim($value[$v], ','));
                            if (is_array($__value)){
                                foreach ($this->translations[$v]['list'] as $_k => $_x) {
                                    if (in_array($_k, $__value)){
                                        $table .= '<span class="'.$_x[0].'">'.$_x[1].'</span>&nbsp;';
                                    }
                                }
                            }else{
                                $table .= $value[$v];
                            }
                            break;
                        case 'time':
                            //时间
                            if (is_numeric($value[$v])){
                                $table .= date('Y-m-d H:i:s', $value[$v]);
                            }else{
                                $table .= $value[$v];
                            }
                            break;
                        case 'url':
                            // 超链接
                            $table .= '<a href="'.$value[$v].'" target="blank">'.$value[$v].'</a>';
                            break;
                        case 'ip':
                            // IP
                            $table .= '<a href="https://www.baidu.com/s?wd='.$value[$v].'"  target="blank">'.$value[$v].'</a>';
                            break;
                        case 'qrcode':
                            // 二维码
                            $table .= '<a href="https://pan.baidu.com/share/qrcode?w=322&h=319&url='.$value[$v].'"  target="blank">'.$value[$v].'</a>';
                            break;
                        case 'join':
                            $table .= $value[$this->translations[$v]['data']['show_field']];
                            break;
                        case 'alias':
                            $table .= $value[$this->translations[$v]['alias']];
                            break;
                        default :
                            //其它
                            $table .= $value[$v];
                            break;
                    }
                    $table .= '</td>';
                }
            }
            $table .= '<td>';
            if ($this->function['edit'] == 1){
                $route = url($this->route.'/edit', ['id' => $value['id']]);
                $table .= '<button onclick="layeropen(\''.$route.'\', \'编辑'.$this->label.'\', \''.$this->modelSize['x'].'\', \''.$this->modelSize['y'].'\')" class="btn btn-info btn-xs" style="margin-bottom: 0;"><i class="fa fa-edit"></i> 编辑</button>&nbsp;&nbsp;';
            }
            // 更多按钮
            $model = tf_to_xhx(request()->module());
            if (!empty($this->moreFunction)){
                foreach ($this->moreFunction as $item) {
                    if ($model == 'admin'){
                        if (check_role($item['route'])){
                            $str = 'onclick="layeropen(\''.url($item['route']).'?ids='.$value['id'].'\', \''.$item['text'].'\', \''.$item['model_x'].'\', \''.$item['model_y'].'\')"';
                            if (!empty($item['type']) && $item['type'] == 'href'){
                                $str = 'href = "'.url($item['route']).'?ids='.$value['id'].'"';
                            }
                            if (!isset($item['where']) || (isset($item['where']) && $value[$item['where']['key']] == $item['where']['value'])){
                                $table .= '<a '.$str.' class="btn btn-'.$item['btn'].' btn-xs" style="margin-bottom: 0;"><i class="'.$item['icon'].'"></i> '.$item['text'].'</a>&nbsp;&nbsp;';
                            }else{
                                $table .= '<button class="btn btn-default btn-xs disabled" style="margin-bottom: 0;" disabled><i class="'.$item['icon'].'"></i> '.$item['text'].'</button>&nbsp;&nbsp;';
                            }
                        }
                    }else{
                        $str = 'onclick="layeropen(\''.url($item['route']).'?ids='.$value['id'].'\', \''.$item['text'].'\', \''.$item['model_x'].'\', \''.$item['model_y'].'\')"';
                        if (!empty($item['type']) && $item['type'] == 'href'){
                            $str = 'href = "'.url($item['route']).'?ids='.$value['id'].'"';
                        }
                        if (!isset($item['where']) || (isset($item['where']) && $value[$item['where']['key']] == $item['where']['value'])){
                            $table .= '<a '.$str.' class="btn btn-'.$item['btn'].' btn-xs" style="margin-bottom: 0;"><i class="'.$item['icon'].'"></i> '.$item['text'].'</a>&nbsp;&nbsp;';
                        }else{
                            $table .= '<button class="btn btn-default btn-xs disabled" style="margin-bottom: 0;" disabled><i class="'.$item['icon'].'"></i> '.$item['text'].'</button>&nbsp;&nbsp;';
                        }
                    }
                }
            }

            if ($this->function['delete'] == 1){
                $table .= '<button onclick="del('.$value['id'].')" class="btn btn-danger btn-xs" style="margin-bottom: 0;"><i class="fa fa-trash-o"></i> 删除</button>';
            }

            $table .= '</td>';
            $table .= '</tr>';
        }
        $table .= '</tbody>';

        $table .= '</table>';

        return $table;
    }

    /**
     * 获取添加页面html
     * @return string
     */
    private function getCreateHtml(){

        $html = '<form method="post" class="form-horizontal" id="form">';
        foreach ($this->addFormFields as $key => $value) {
            $default = '';
            if (isset($this->translations[$value]['default'])){
                $default = $this->translations[$value]['default'];
            }
            $html .= '<div class="form-group">';
            $html .= '<label class="col-sm-2 control-label">'.$this->translations[$value]['text'].'</label>';
            $html .= '<div class="col-sm-10">';
            if (empty($this->translations[$value]['type'])){
                $html .= '<input class="form-control" name="'.$value.'" type="text" value="'.$default.'" AUTOCOMPLETE="off">';
            }else{
                switch ($this->translations[$value]['type']){
                    case 'input':
                        // 表单输入
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$default.'"  type="text" AUTOCOMPLETE="off">';
                        break;
                    case 'disabled_input':
                        // 禁用表单
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$default.'"  type="text" AUTOCOMPLETE="off" disabled>';
                        break;
                    case 'textarea':
                        // 文本框
                        $html .= '<textarea class="form-control" name="'.$value.'" value="'.$default.'"  type="text"></textarea>';
                        break;
                    case 'password':
                        // 密码框
                        $html .= '<input class="form-control" name="'.$value.'" type="password" AUTOCOMPLETE="off">';
                        break;
                    case 'ip':
                        // ip地址
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$default.'"  type="text" AUTOCOMPLETE="off">';
                        break;
                    case 'time':
                        // 时间
                        $html .= '<div class="input-group m-b"><input class="form-control" id="'.$value.'" name="'.$value.'" value="'.$default.'" type="text" AUTOCOMPLETE="off"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div>';
                        $html .= '<script>laydate({elem: \'#'.$value.'\', format:   \'YYYY-MM-DD hh:mm:ss\',istime: true});</script>';
                        break;
                    case 'radio':
                        // 单选
                        foreach ($this->translations[$value]['list'] as $_k => $_x) {
                            $html .= '<div class="radio i-checks" style="float:left;"><label><input type="radio" value="'.$_k.'" name="'.$value.'" ';
                            if (!empty($_x[2])) $html .= 'checked';
                            $html .= '> <i></i> '.$_x[1].'</label></div>';
                        }
                        break;
                    case 'checkbox':
                        // 多选
                        foreach ($this->translations[$value]['list'] as $_k => $_x) {
                            $html .= '<div class="checkbox i-checks" style="float:left;"><label><input type="checkbox" value="'.$_k.'" name="'.$value.'[]" > <i></i> '.$_x[1].'</label></div>';
                        }
                        break;
                    case 'join':
                        $list = $this->getJoinDateList($this->translations[$value]['data']);
                        $html .= '<select class="form-control" name="'.$value.'" id="'.$value.'"><option value="">请选择...</option>';
                        foreach ($list as $_k => $_x) {
                            $html .= '<option value="'.$_x[$this->translations[$value]['data']['value_field']].'">'.$_x[$this->translations[$value]['data']['show_field']].'</option>';
                        }
                        $html .= '</select>';
                        $html .= '<script>$("#'.$value.'").chosen({no_results_text: "未找到匹配项！",search_contains: true});</script>';
                        break;
                    case 'ueditor':
                        // 百度富文本编辑器
                        $html .= '<script id="'.$value.'" name="'.$value.'" type="text/plain" style="width:100%;min-height:350px;">'.$default.'</script>';
                        $html .= '<script type="text/javascript"> var ue = UM.getEditor(\''.$value.'\'); </script>';
                        break;
                    default :
                        // 其他
                        $html .= '未定义该类型';
                        break;
                }
            }
            if (!empty($this->translations[$value]['info'])){
                $html .= '<span class="help-block m-b-none"><i class="fa fa-info-circle"></i> '.$this->translations[$value]['info'].'</span>';
            }
            $html .= '</div></div>';
        }
        $html .= '<div style="clear: both;"></div><div class="form-group"><label class="col-sm-2 control-label"></label><div class="col-sm-10"><button class="btn btn-primary btn-block" type="button" id="sub">确定添加</button></div></div>';
        $html .= '</form>';

        return $html;
    }

    /**
     * 获取编辑页面html
     * @return string
     */
    private function getEditHtml($data){

        $html = '<form method="post" class="form-horizontal" id="form">';
        foreach ($this->addFormFields as $key => $value) {
            $data[$value] = htmlspecialchars($data[$value]);

            $html .= '<div class="form-group">';
            $html .= '<label class="col-sm-2 control-label">'.$this->translations[$value]['text'].'</label>';
            $html .= '<div class="col-sm-10">';
            if (empty($this->translations[$value]['type'])){
                $html .= '<input class="form-control" name="'.$value.'" type="text" value="'.$data[$value].'" AUTOCOMPLETE="off">';
            }else{
                switch ($this->translations[$value]['type']){
                    case 'input':
                        // 表单输入
                        $html .= '<input class="form-control" name="'.$value.'" value="'.$data[$value].'" type="text" AUTOCOMPLETE="off">';
                        break;
                    case 'disabled_input':
                        // 禁用表单
                        $html .= '<input class="form-control" name="'.$value.'" type="text" value = "'.$data[$value].'" AUTOCOMPLETE="off" disabled>';
                        break;
                    case 'textarea':
                        // 文本框
                        $html .= '<textarea class="form-control" name="'.$value.'" type="text">'.$data[$value].'</textarea>';
                        break;
                    case 'password':
                        // 密码框
                        $html .= '<input class="form-control" name="'.$value.'" type="password" AUTOCOMPLETE="off" >';
                        break;
                    case 'ip':
                        // ip地址
                        $html .= '<input class="form-control" name="'.$value.'" type="text" value="'.$data[$value].'" AUTOCOMPLETE="off">';
                        break;
                    case 'time':
                        // 时间
                        if (is_numeric($data[$value])){
                            $data[$value] = date('Y-m-d H:i:s', $data[$value]);
                        }
                        $html .= '<div class="input-group m-b"><input class="form-control" id="'.$value.'" name="'.$value.'" value="'.$data[$value].'" type="text" AUTOCOMPLETE="off"><span class="input-group-addon"><i class="fa fa-calendar"></i></span></div>';
                        $html .= '<script>laydate({elem: \'#'.$value.'\', format:   \'YYYY-MM-DD hh:mm:ss\',istime: true});</script>';
                        break;
                    case 'radio':
                        // 状态
                        foreach ($this->translations[$value]['list'] as $_k => $_x) {
                            $html .= '<div class="radio i-checks" style="float:left;"><label><input type="radio" value="'.$_k.'" name="'.$value.'" ';
                            if ($_k == $data[$value]) $html .= 'checked';
                            $html .= '> <i></i> '.$_x[1].'</label></div>';
                        }
                        break;
                    case 'checkbox':
                        // 多选
                        $__value = explode(',', trim($data[$value], ','));
                        if (is_array($__value)){
                            foreach ($this->translations[$value]['list'] as $_k => $_x) {
                                $html .= '<div class="checkbox i-checks" style="float:left;"><label><input type="checkbox" value="'.$_k.'" name="'.$value.'[]" ';
                                if (in_array($_k, $__value)) $html .= 'checked';
                                $html .= '> <i></i> '.$_x[1].'</label></div>';
                            }
                        }
                        break;
                    case 'join':
                        $list = $this->getJoinDateList($this->translations[$value]['data']);
                        $html .= '<select class="form-control" name="'.$value.'" id="'.$value.'"><option value="">请选择...</option>';
                        foreach ($list as $_k => $_x) {
                            $html .= '<option value="'.$_x[$this->translations[$value]['data']['value_field']].'"';
                            if ($data[$value] == $_x[$this->translations[$value]['data']['value_field']]){
                                $html .= ' selected';
                            }
                            $html .='>'.$_x[$this->translations[$value]['data']['show_field']].'</option>';
                        }
                        $html .= '</select>';
                        $html .= '<script>$("#'.$value.'").chosen({no_results_text: "未找到匹配项！",search_contains: true});</script>';
                        break;
                    case 'ueditor':
                        // 百度富文本编辑器
                        $html .= '<script id="'.$value.'" name="'.$value.'" type="text/plain" style="width: 100%;min-height:350px; ">'.htmlspecialchars_decode($data[$value]).'</script>';
                        $html .= '<script type="text/javascript"> var ue = UM.getEditor(\''.$value.'\'); </script>';
                        break;
                    default :
                        // 其他
                        $html .= '未定义该类型';
                        break;
                }
            }
            if (!empty($this->translations[$value]['info'])){
                $html .= '<span class="help-block m-b-none"><i class="fa fa-info-circle"></i> '.$this->translations[$value]['info'].'</span>';
            }
            $html .= '</div></div>';
        }
        $html .= '<div class="form-group"><label class="col-sm-2 control-label"></label><div class="col-sm-10"><button class="btn btn-primary btn-block" type="button" id="sub">确定保存</button></div></div>';
        $html .= '</form>';


        return $html;
    }

    /**
     * 获取搜索html
     */
    public function getSearchHtml(){
        $html = '';
        if (!empty($this->searchFields)){
            $html .= '<form role="form" class="form-inline">';
            foreach ($this->searchFields as $key => $v) {
                $html .= '<div class="form-group">';
                if (empty($this->translations[$v]['type'])){
                    $html .= '<input type="text" name="'.$v.'" class="form-control" placeholder="'.$this->translations[$v]['text'].'" style="width: 130px; margin-left: 8px" value="'.request()->get($v).'">';
                }else{
                    switch ($this->translations[$v]['type']){
                        case 'input':
                            $html .= '<input type="text" name="'.$v.'" class="form-control" placeholder="'.$this->translations[$v]['text'].'" style="width: 130px; margin-left: 8px" value="'.request()->get($v).'">';
                            break;
                        case 'radio':
                            $html .= '<select class="form-control" name="'.$v.'" style="width: 130px; margin-left: 8px">';
                            $html .= '<option value="">'.$this->translations[$v]['text'].'</option>';
                            foreach ($this->translations[$v]['list'] as $_k => $_v) {
                                $_selected = '';
                                if (request()->get($v) != '' && request()->get($v) == $_k){
                                    $_selected = 'selected';
                                }
                                $html .= '<option value="'.$_k.'" '.$_selected.'>'.$_v[1].'</option>';
                            }
                            $html .= '</select>';
                            break;
                        default:
                            $html .= '<input type="text" name="'.$v.'" class="form-control" placeholder="'.$this->translations[$v]['text'].'" value="'.request()->get($v).'" style="width: 130px; margin-left: 8px">';
                            break;
                    }
                }
                $html .= '</div>';
            }

            $html .= '<div class="form-group" style="margin-left: 10px; margin-top: 5px;"><input type="submit" class="btn btn-success" value="搜索"> <a class="btn btn-default btn-outline" href="'.url($this->route.'/index').'">重置</a></div>';
            $html .= '</form>';
        }

        return $html;
    }

    /**
     * 验证权限按钮显示
     */
    protected function checkButtonRole($uid){
        $role = tf_to_xhx(request()->module()).'/'.tf_to_xhx(request()->controller());
        if (!$this->auth->check($role.'/create', $uid)){
            $this->function['create'] = 0;
        }
        if (!$this->auth->check($role.'/edit', $uid)){
            $this->function['edit'] = 0;
        }
        if (!$this->auth->check($role.'/delete', $uid)){
            $this->function['delete'] = 0;
        }
    }

    /**
     * 添加时默认保存数据
     * @return bool
     */
    protected function saveBeforeData($data){
        return false;
    }

    /**
     * 更新时默认保存数据
     * @return bool
     */
    protected function updateBeforeData(){
        return false;
    }

    /**
     * 保存前额外验证数据
     */
    protected function saveBeforeValidate($data){
        return [
            'err_code' => '0',
            'err_msg' => 'ok'
        ];
    }

    /**
     * 更新前额外验证数据
     */
    protected function updateBeforeValidate($id, $data){
        return [
            'err_code' => '0',
            'err_msg' => 'ok'
        ];
    }
}