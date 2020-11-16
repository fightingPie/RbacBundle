<?php
namespace SymfonyRbac\Rbac\Rules;

use SymfonyRbac\Rbac\Item;
use SymfonyRbac\Rbac\Rule;

class TestRule extends Rule {

  public $name = 'testing24'; //规则的名称


  /**
   * @param string|integer $user 用户 ID.
   * @param Item $item 该规则相关的角色或者权限
   * @param array $params 传给 ManagerInterface::checkAccess() 的参数
   * @return boolean 代表该规则相关的角色或者权限是否被允许
   */
  public function execute($user, $item, $params){
    return true;
  }
}