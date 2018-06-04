<?php

namespace app\api\validate;

use think\Validate;

class BaseValidate extends Validate
{
  /**
   * [自定义验证规则] 是否为整数
   * @param $value
   * @param string $rule
   * @param string $data
   * @param string $field
   * @return bool
   */
  protected function isPositiveInteger($value, $rule = '', $data = '', $field = '')
  {
    if (is_numeric($value) && is_int($value + 0) && ($value + 0) > 0) {
      return true;
    }
    return false;
  }

  /**
   * 不为空
   * @param $value
   * @param string $rule
   * @param string $data
   * @param string $field
   * @return bool
   */
  protected function isNotEmpty($value, $rule = '', $data = '', $field = '')
  {
    if (empty($value)) return false;
    return true;
  }

  /**
   * 手机号码验证
   * @param $value
   * @return bool
   */
  protected function isMobile($value)
  {
    $rule = '^1(3|4|5|7|8)[0-9]\d{8}$^';
    $result = preg_match($rule, $value);
    if ($result) return true;
    return false;
  }
}
