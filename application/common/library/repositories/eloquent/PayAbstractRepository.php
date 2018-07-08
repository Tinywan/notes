<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/8 10:47
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library\repositories\eloquent;

use app\common\library\repositories\contracts\RepositoryInterface;
use app\common\library\repositories\exceptions\RepositoryException;
use think\Exception;
use think\facade\App;
use think\Model;

abstract class PayAbstractRepository implements RepositoryInterface
{
    /**
     * 错误信息
     * @var array
     */
    protected $error = [
      'success' => false,
      'code' => 0,
      'msg' => '未知错误',
      'data' => []
    ];

    /**
     * 模型对象
     * @var
     */
    protected $model;

    /**
     * Repository constructor.
     */
    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * 该抽象类中定义了一个抽象方法 model()，强制在实现类中实现该方法已获取当前实现类对应的模型
     * Specify Model class name
     * @return mixed
     */
    abstract function model();

    /**
     * 实现类对应的模型
     * 针对不适用模型
     * @return mixed|Model
     * @throws Exception
     */
    public function makeModel()
    {
        if ($this->model()) {
            $model = App::invokeClass($this->model());
            if (!$model instanceof Model) {
                throw new RepositoryException("Class {$this->model()} must be an instance of think\\Model");
            }
            return $this->model = $model;
        }
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function all($columns = array('*'))
    {
        return $this->model->all($columns);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * @param array $data
     * @param $id
     * @param string $attribute
     * @return mixed
     */
    public function update(array $data, $id, $attribute = "id")
    {
        return $this->model->where($attribute, '=', $id)->update($data);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    /**
     * @param $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = array('*'))
    {
        return $this->model->find($id, $columns);
    }

    /**
     * @param $attribute
     * @param $value
     * @param array $columns
     * @return mixed
     */
    public function findBy($attribute, $value, $columns = array('*'))
    {
        return $this->model->where($attribute, '=', $value)->first($columns);
    }

    /**
     * 设置错误信息
     * @param $success
     * @param $msg
     * @param int $errorCode
     * @return mixed
     */
    protected function setError($success, $msg, $errorCode = 0, array $data = [])
    {
        $this->error = [
          'success' => $success,
          'msg' => $msg,
          'errorCode' => $errorCode,
          'data' => $data,
        ];

        return $success;
    }

    /**
     * 获取错误结果
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }
}