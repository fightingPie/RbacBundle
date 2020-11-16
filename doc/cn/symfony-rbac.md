# Symfony-RBAC实现原理详解

## 一、简介

RBAC（Role-Based Access Control ）基于角色的访问控制。

## 二、对rbac的实现

1.实现通用的分成RBAC，遵循模型也是NIST RBAC model.

2.增加 auth 来源：用来扩展其他业务部门或者其他组织的权限接入。

3.角色（权限）增加rule规则

## 三、表设计

> ER模型

![Diagram-rbac](../image/Diagram-rbac.bmp)



#### 1)存储角色或权限的表：auth_item

```sql
CREATE TABLE auth_item (
  name varchar(64) NOT NULL COMMENT '角色（权限）唯一标识',
  alias varchar(64) DEFAULT NULL COMMENT '角色 （权限）名称',
  type int(11) NOT NULL COMMENT 'type:1表示 角色；2表示权限',
  category int(11) DEFAULT NULL COMMENT '菜单分类',
  description text DEFAULT NULL COMMENT '描述',
  rule_name varchar(64) DEFAULT NULL COMMENT 'rule规则',
  data text DEFAULT NULL COMMENT '额外数据，serialize存入',
  status int(2) NOT NULL DEFAULT 1 COMMENT '启用1 禁用0',
  created_at int(11) DEFAULT NULL COMMENT '创建时间',
  updated_at int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (name)
)
ENGINE = INNODB,
AVG_ROW_LENGTH = 585,
CHARACTER SET utf8,
COLLATE utf8_general_ci,
COMMENT = '角色权限表';

ALTER TABLE auth_item
ADD INDEX type (type);

ALTER TABLE auth_item
ADD CONSTRAINT auth_item_ibfk_1 FOREIGN KEY (rule_name)
REFERENCES auth_rule (name) ON DELETE SET NULL ON UPDATE CASCADE;
```

> 1. `type` : 1 表示角色、2 表示权限
>
> 2. status: 1启用、0 禁用
>
> 3.  `name`  的格式可以自定义，这样具有扩展性 ,但是需统一格式且唯一性。
>
>    例如：取决于控制器/方法作为`name`（  **{controller}/{function}**  ）： user/index



#### 2) 权限和角色的上下级关联表:auth_item_child 

> 包含关系：角色 可以包含 角色、角色 可以包含 权限、权限 可以包含 权限，但 权限 不可包含 角色

```sql
CREATE TABLE auth_item_child (
  parent varchar(64) NOT NULL COMMENT '角色（权限）名称',
  child varchar(64) NOT NULL COMMENT '角色（权限）名称',
  PRIMARY KEY (parent, child)
)
ENGINE = INNODB,
AVG_ROW_LENGTH = 712,
CHARACTER SET utf8,
COLLATE utf8_general_ci,
COMMENT = '角色权限关系表';

ALTER TABLE auth_item_child
ADD CONSTRAINT auth_item_child_ibfk_1 FOREIGN KEY (parent)
REFERENCES auth_item (name) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE auth_item_child
ADD CONSTRAINT auth_item_child_ibfk_2 FOREIGN KEY (child)
REFERENCES auth_item (name) ON DELETE CASCADE ON UPDATE CASCADE;
```



#### 3)用户与权限（角色）的分配表:auth_assignment

```sql
CREATE TABLE auth_assignment (
  item_name varchar(64) NOT NULL COMMENT '角色（权限）名称',
  user_id varchar(64) NOT NULL COMMENT '用户id',
  created_at int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (item_name, user_id)
)
ENGINE = INNODB,
AVG_ROW_LENGTH = 8192,
CHARACTER SET utf8,
COLLATE utf8_general_ci,
COMMENT = '用户与角色权限关系表';

ALTER TABLE auth_assignment
ADD CONSTRAINT auth_assignment_ibfk_1 FOREIGN KEY (item_name)
REFERENCES auth_item (name) ON DELETE CASCADE ON UPDATE CASCADE;
```



#### 4)规则表：auth_rule

```sql
CREATE TABLE auth_rule (
  name varchar(64) NOT NULL COMMENT '规则名称',
  data text DEFAULT NULL COMMENT '存的是一个序列化的实现了rbacRule接口的类的一个对象实例',
  created_at int(11) DEFAULT NULL COMMENT '创建时间',
  updated_at int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (name)
)
ENGINE = INNODB,
AVG_ROW_LENGTH = 16384,
CHARACTER SET utf8,
COLLATE utf8_general_ci,
COMMENT = '权限规则表';

ALTER TABLE auth_rule
ADD INDEX created_at (created_at);

ALTER TABLE auth_rule
ADD INDEX name (name);

ALTER TABLE auth_rule
ADD INDEX updated_at (updated_at);
```



## 四、用户权限检查流程（伪代码）

>场景用例 ：
>
>对于个人资料而言，我们有管理员和员工，允许管理员对任何员工的个人资料进行任何操作，但是只允许员工修改自己个人资料，也就是说员工是有修改个人资料的权限的，但是额加的限制条件是只能修改自己的个人资料，这个额加的验证工作就是rule规则所要负责的事情。

#### 1)定义权限`name`的生成规则 

> 可自定义设计 

​	例子：可使用控制器访问方式：控制器/方法作为`name`（  **{controller}/{function}**  ）：user/update

#### 2)调用用户检查方法 

​	*调用控制器前的前置方法中调用 can() 方法*

```php
$user->can($permissionName, $params = []);
```

> $permissionName ： 权限 user/update
>
> $params = [] ：业务数据

```php
//检查该用户是否有这个操作的权限
public function can($permissionName, $params = [])
{
    $access = checkAccess($this->loginUserId, $permissionName, $params);
    
    return $access;
}

//检查前置获取权限数据
public function checkAccess($userId,$permissionName, $params = [])
{
    //getAssignments该方法所做的工作就是去用户与权限（角色）的分配表:auth_assignment中找出有关该用户的所有权限和角色，具体实现见下：
    $assignments = $this->getAssignments($userId);
    
    //一系列检验是否命中权限
    $res = $this->checkAccessRecursive($userId, $permissionName, $params, $assignments)
    
    return $res;
}


//关键方法：决策递归是否命中权限
protected function checkAccessRecursive($user, $itemName, $params, $assignments)
{
    //从存储角色或权限的表：auth_item中找出 user/update 这个权限的记录并封装成对象在executeRule方法中使用
    if (($item = $this->getItem($itemName)) === null) {
        return false;
    }
 
    //通过$item->ruleName在规则表：auth_rule中找到该权限(user/update)对应的rule规则，如果有的话就对该用户执行该权限操作的规则验证，如果验证不通过直接返回false
    if (!$this->executeRule($user, $item, $params)) {
        return false;
    }
 
    //如果在用户与权限（角色）的分配表中匹配到了该权限且规则验证也ok了就说明该用户有权限了呗
    if (isset($assignments[$itemName]) || in_array($itemName, $this->defaultRoles)) {
        return true;
    }
 
    //如果目前来看该用户没有该操作的权限那么就从权限和角色的上下级关联表中找到该权限的父级，然后递归的检查该用户在该父级里有没有权限，如果父级有权限了那么该权限也有了 （伪sql）
    $parents = (new Query)
        ->select(['parent'])
        ->from('auth_item_child')
        ->where(['child' => $itemName])
        ->asArray();
 
    foreach ($parents as $parent) {
        if ($this->checkAccessRecursive($user, $parent, $params, $assignments)) {
            return true;
        }
    }
 
    return false;
}

//执行规则
protected function executeRule($user, $item, $params)
{
    if ($item->ruleName === null) {
        return true;
    }
    
    //根据该Item 权限记录对应的rule_name去规则表：auth_rule中找到该条规则记录
    //并对该data字段(前面说过存的是序列化的对象)进行反序列化得到rbac\Rule实现类的对象
    $rule = $this->getRule($item->ruleName);
    if ($rule instanceof Rule) {
        //执行该对象的execute方法去验证该用户是否有该权限的使用权
        return $rule->execute($user, $item, $params);
    } else {
        throw new Exception("Rule not found: {$item->ruleName}");
    }
}
```

> 父级是权限和角色是越来越严谨，如果一个用户权限的最大尺度权限查找不到，往越严格父级查找，若无

#### 3)规则验证方法实现 （伪代码）

> 实现抽象类 Rule 中 execute 方法

```php
<?php
abstract class Rule 
{
    public $name;

    public $createdAt;

    public $updatedAt;
    
    abstract public function execute($user, $item, $params);
}

```



```php
<?php
/**
 * 检查该个人中心的所属user_id是否和该用户的id一样
 */
 class UserUpdateRule extends Rule {      
    public function execute($user, $item, $params)
    {      
        //查找资料的user_id
        $userInfo = UserInfo::findOne(['user_id'=>$params['user_id']]);
        return $userInfo && $userInfo->user_id == $user  ? true : false;
    }
}
```

