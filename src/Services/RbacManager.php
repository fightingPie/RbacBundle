<?php

namespace SymfonyRbac\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use SymfonyRbac\Entity\AuthAssignment;
use SymfonyRbac\Entity\AuthItem;
use SymfonyRbac\Entity\AuthItemChild;
use SymfonyRbac\Entity\AuthRule;
use SymfonyRbac\Rbac\Assignment;
use SymfonyRbac\Rbac\BaseManager;
use SymfonyRbac\Rbac\Item;
use SymfonyRbac\Rbac\Permission;
use SymfonyRbac\Rbac\Role;
use SymfonyRbac\Rbac\Rule;
use Symfony\Component\Uid\Ulid;

/**
 * Class RbacManager
 * @package SymfonyRbac\Services
 */
class RbacManager extends BaseManager
{

    /**
     * @var string the name of the table storing authorization items. Defaults to "auth_item".
     */
    public $itemTable = AuthItem::class;
    /**
     * @var string the name of the table storing authorization item hierarchy. Defaults to "auth_item_child".
     */
    public $itemChildTable = AuthItemChild::class;
    /**
     * @var string the name of the table storing authorization item assignments. Defaults to "auth_assignment".
     */
    public $assignmentTable = AuthAssignment::class;
    /**
     * @var string the name of the table storing rules. Defaults to "auth_rule".
     */
    public $ruleTable = AuthRule::class;

    public $cache;
    /**
     * @var string the key used to store RBAC data in cache
     * @see cache
     * @since 2.0.3
     */
    public $cacheKey = 'rbac';

    /**
     * @var Item[] all auth items (name => Item)
     */
    protected $items;
    /**
     * @var Rule[] all auth rules (name => Rule)
     */
    protected $rules;
    /**
     * @var array auth item parent-child relationships (childName => list of parents)
     */
    protected $parents;


    private $_checkAccessAssignments = [];


    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * RbacManager constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {

        $this->entityManager = $entityManager;

    }

    /**
     * @return EntityManager
     */
    public function getEm()
    {
        return $this->entityManager;
    }


    /**
     * @param int|string $userId
     * @param string $permissionName
     * @param array $params
     * @return bool
     */
    public function checkAccess($userId, string $permissionName, $params = [])
    {
        if (isset($this->_checkAccessAssignments[(string)$userId])) {
            $assignments = $this->_checkAccessAssignments[(string)$userId];
        } else {
            $assignments = $this->getAssignments($userId);
            $this->_checkAccessAssignments[(string)$userId] = $assignments;
        }

        if ($this->hasNoAssignments($assignments)) {
            return false;
        }

        $this->loadFromCache();
        if ($this->items !== null) {
            return $this->checkAccessFromCache($userId, $permissionName, $params, $assignments);
        }

        return $this->checkAccessRecursive($userId, $permissionName, $params, $assignments);
    }

    /**
     * @param $user
     * @param $itemName
     * @param $params
     * @param $assignments
     * @return bool
     */
    protected function checkAccessFromCache($user, $itemName, $params, $assignments)
    {
        if (!isset($this->items[$itemName])) {
            return false;
        }

        $item = $this->items[$itemName];

        if ($item->status == Item::STATUS_PASSIVE) {
            return false;
        }

        /*
         * log
         */


        if (!$this->executeRule($user, $item, $params)) {
            return false;
        }

        if (isset($assignments[$itemName]) || in_array($itemName, $this->defaultRoles)) {
            return true;
        }

        if (!empty($this->parents[$itemName])) {
            foreach ($this->parents[$itemName] as $parent) {
                if ($this->checkAccessFromCache($user, $parent, $params, $assignments)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $user
     * @param $itemName
     * @param $params
     * @param $assignments
     * @return bool
     */
    protected function checkAccessRecursive($user, $itemName, $params, $assignments)
    {
        if (($item = $this->getItem($itemName)) === null) {
            return false;
        }

        if ($item->status == Item::STATUS_PASSIVE) {
            return false;
        }

        /**
         * log
         *
         */


        if (!$this->executeRule($user, $item, $params)) {
            return false;
        }

        if (isset($assignments[$itemName]) || in_array($itemName, $this->defaultRoles)) {
            return true;
        }

        $parents = $this->entityManager->getRepository($this->itemChildTable)
            ->createQueryBuilder('p')
            ->select('p.parent')
            ->where('p.child =:itemName')
            ->setParameter('itemName', $itemName)
            ->getQuery()
            ->getResult();

        foreach ($parents as $parent) {
            if ($this->checkAccessRecursive($user, $parent['parent'], $params, $assignments)) {
                return true;
            }
        }


        return false;
    }

    /**
     * @param string $name
     * @return Item|null
     */
    protected function getItem($name)
    {
        if (empty($name)) {
            return null;
        }

        if (!empty($this->items[$name])) {
            return $this->items[$name];
        }


        $row = $this->entityManager
            ->getRepository($this->itemTable)
            ->createQueryBuilder('p')
            ->where('p.name = :name')
            ->setParameter('name', $name)
            ->getQuery()->getResult();
        if (empty($row)) {
            return null;
        }

        return $this->populateItem($row[0]);
    }

    /**
     * Returns a value indicating whether the database supports cascading update and delete.
     * The default implementation will return false for SQLite database and true for all other databases.
     * @return bool whether the database supports cascading update and delete.
     */
    protected function supportsCascadeUpdate()
    {
        return false;
    }

    /**
     * @param Item $item
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function addItem($item)
    {
        $time = time();
        if ($item->createdAt === null) {
            $item->createdAt = $time;
        }
        if ($item->updatedAt === null) {
            $item->updatedAt = $time;
        }

        if ($item->type == Item::TYPE_ROLE && $item->name === null) {
            $item->name = new Ulid();
        }

        $rule = null;
        if ($item->ruleName) {
            $rule = $this->entityManager->getRepository($this->ruleTable)->findOneBy(['name' => $item->ruleName]);
        }
        /**
         * @var AuthItem $itemObject
         */
        $itemObject = new $this->itemTable();
        $itemObject->setName($item->name);
        $itemObject->setAlias($item->alias);
        $itemObject->setType($item->type);
        $itemObject->setDescription($item->description);
        $itemObject->setRuleName($rule);
        $itemObject->setData($item->data === null ? null : serialize($item->data));
        if (isset($item->category)){
            $itemObject->setCategory($item->category);
        }
        $itemObject->setCreatedAt($item->createdAt);
        $itemObject->setUpdatedAt($item->updatedAt);

        $this->entityManager->persist($itemObject);
        $this->entityManager->flush();


        $this->invalidateCache();

        return true;
    }

    /**
     * @param Item $item
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function removeItem($item)
    {

        /**
         * delete itemChild parent = {$item->name} or child = {$item->name}
         * delete assignmentTable item_name = {$item->name}
         * delete itemTable name = {$item->name}
         * delete itemOriginTable item_name = {$item->name}
         */
        $item = $this->entityManager->getRepository($this->itemTable)->findOneBy(['name' => $item->name]);
        $this->entityManager->remove($item);
        $this->entityManager->flush();

        $this->invalidateCache();

        return true;
    }

    /**
     * @param string $name
     * @param Item $item
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function updateItem($name, $item)
    {
        $item->updatedAt = time();
        /**
         * update name of auth_item  and  name of auth_rule
         */
        $itemObject = $this->entityManager->getRepository($this->itemTable)->findOneBy(['name' => $name]);
        $itemObject->setName($item->name ?? $itemObject->getName());
        $itemObject->setAlias($item->alias ?? $itemObject->getAlias());
        $itemObject->setDescription($item->description ?? $itemObject->getDescription());

        if ($item->ruleName) {
            $rule = $this->entityManager->getRepository($this->ruleTable)->findOneBy(['name' => $item->ruleName]);
            $itemObject->setRuleName($rule);
        }

//        $itemObject->setData($item->data === null ? null : serialize($item->data));
        $itemObject->setUpdatedAt($item->updatedAt);
        $itemObject->setStatus($item->status ?? $itemObject->getStatus());
        $this->entityManager->flush();

        $this->invalidateCache();

        return true;
    }


    /**
     * @param Rule $rule
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function addRule($rule)
    {
        $time = time();
        if ($rule->createdAt === null) {
            $rule->createdAt = $time;
        }
        if ($rule->updatedAt === null) {
            $rule->updatedAt = $time;
        }

        /**
         * @var AuthRule $ruleObject
         */
        $ruleObject = new $this->ruleTable();
        $ruleObject->setName($rule->name);
        $ruleObject->setData(serialize($rule));
        $ruleObject->setCreatedAt($rule->createdAt);
        $ruleObject->setUpdatedAt($rule->updatedAt);

        $this->entityManager->persist($ruleObject);
        $this->entityManager->flush();

        $this->invalidateCache();

        return true;
    }

    /**
     * @param string $name
     * @param Rule $rule
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function updateRule($name, $rule)
    {

        /**
         * update rule_name of auth_item  and  name of auth_rule
         */
        $rule->updatedAt = time();
        $ruleObject = $this->entityManager->getRepository($this->ruleTable)->findOneBy(['name' => $name]);
        $ruleObject->setName($rule->name);
        $ruleObject->setData(serialize($rule));
        $ruleObject->setUpdatedAt($rule->updatedAt);
        $this->entityManager->flush();

        $this->invalidateCache();

        return true;
    }

    /**
     * @param Rule $rule
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function removeRule($rule)
    {

        /**
         * itemTable :rule_name -> null
         * delete  rule->name  of  ruleTable
         */
        $object = $this->entityManager->getRepository($this->ruleTable)->findOneBy(['name' => $rule->name]);
        $this->entityManager->remove($object);
        $this->entityManager->flush();


        $this->invalidateCache();

        return true;
    }

    /**
     * @param int $type
     * @return array|Item[]
     */
    protected function getItems($type)
    {
        $rows = $this->entityManager->getRepository($this->itemTable)->findBy(['type' => $type]);
        $items = [];
        foreach ($rows as $row) {
            $items[$row->getName()] = $this->populateItem($row);
        }

        return $items;
    }

    /**
     * Populates an auth item with the data fetched from database.
     * @param array $row the data from the auth item table
     * @return Item the populated auth item instance (either Role or Permission)
     */
    protected function populateItem($row)
    {
        //check item
        $this->ternItem($row);

        $class = $row['type'] == Item::TYPE_PERMISSION ? Permission::className() : Role::className();

        if (!isset($row['data']) || ($data = @unserialize(is_resource($row['data']) ? stream_get_contents($row['data']) : $row['data'])) === false) {
            $data = null;
        }

        return new $class([
            'name' => $row['name'],
            'alias' => $row['alias'],
            'type' => $row['type'],
            'category' => $row['category'],
            'description' => $row['description'],
            'ruleName' => $row['rule_name'] ?: null,
            'data' => $data,
            'status' => $row['status'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ]);
    }

    /**
     * @param $row
     */
    protected function ternItem(&$row)
    {
        if (is_object($row) && $row instanceof AuthItem) {
            $row = [
                'name' => $row->getName(),
                'type' => $row->getType(),
                'description' => $row->getDescription(),
                'rule_name' => $row->getRuleName() ? $row->getRuleName()->getName() : null,
                'data' => $row->getData(),
                'created_at' => $row->getCreatedAt(),
                'updated_at' => $row->getUpdatedAt(),
            ];
        }
    }

    /**
     * @param int|string $userId
     * @return array|Role[]
     */
    public function getRolesByUser($userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return [];
        }

        $rows = $this->entityManager->getRepository($this->assignmentTable)
            ->createQueryBuilder("a")
            ->Join('a.itemName', 't')
            ->andWhere(' a.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('t.type = :type')
            ->setParameter('type', Item::TYPE_ROLE)
            ->getQuery()->getResult();

        $roles = $this->getDefaultRoleInstances();
        foreach ($rows as $row) {
            $item = $row->getItemName();
            $roles[$item->getName()] = $this->populateItem($item);
        }

        return $roles;
    }

    /**
     * @param string $roleName
     * @return array|Item[]|Role[]
     */
    public function getChildRoles(string $roleName)
    {
        $role = $this->getRole($roleName);

        if ($role === null) {
            throw new \InvalidArgumentException("Role \"$roleName\" not found.");
        }

        $result = [];
        $this->getChildrenRecursive($roleName, $this->getChildrenList(), $result);

        $roles = [$roleName => $role];

        $roles += array_filter($this->getRoles(), function (Role $roleItem) use ($result) {
            return array_key_exists($roleItem->name, $result);
        });

        return $roles;
    }

    /**
     * @param string $roleName
     * @return array|Permission[]
     */
    public function getPermissionsByRole(string $roleName)
    {
        $childrenList = $this->getChildrenList();
        $result = [];
        $this->getChildrenRecursive($roleName, $childrenList, $result);
        if (empty($result)) {
            return [];
        }

        $rows = $this->entityManager->getRepository($this->itemTable)->findBy([
            'type' => Item::TYPE_PERMISSION,
            'name' => array_keys($result),
        ]);
        $permissions = [];
        foreach ($rows as $row) {
            $permissions[$row->getName()] = $this->populateItem($row);
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionsByUser($userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return [];
        }

        $directPermission = $this->getDirectPermissionsByUser($userId);
        $inheritedPermission = $this->getInheritedPermissionsByUser($userId);

        return array_merge($directPermission, $inheritedPermission);
    }

    /**
     * @param $userId
     * @return array
     */
    protected function getDirectPermissionsByUser($userId)
    {
        $rows = $this->entityManager->getRepository($this->assignmentTable)
            ->createQueryBuilder("a")
            ->Join('a.itemName', 't')
            ->andWhere(' a.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('t.type = :type')
            ->setParameter('type', Item::TYPE_PERMISSION)
            ->getQuery()->getResult();

        $permissions = [];
        foreach ($rows as $row) {
            $item = $row->getItemName();
            $permissions[$item->getName()] = $this->populateItem($item);
        }

        return $permissions;
    }

    /**
     * @param $userId
     * @return array
     */
    protected function getInheritedPermissionsByUser($userId)
    {

        $rows = $this->entityManager->getRepository($this->assignmentTable)->findBy(['userId' => (string)$userId]);

        $childrenList = $this->getChildrenList();
        $result = [];
        foreach ($rows as $row) {
            $roleName = $row->getItemName()->getName();
            $this->getChildrenRecursive($roleName, $childrenList, $result);
        }

        if (empty($result)) {
            return [];
        }

        $rows = $this->entityManager->getRepository($this->itemTable)->findBy([
            'type' => Item::TYPE_PERMISSION,
            'name' => array_keys($result),
        ]);


        $permissions = [];
        foreach ($rows as $row) {
            $permissions[$row->getName()] = $this->populateItem($row);
        }

        return $permissions;
    }

    /**
     * @return array
     */
    protected function getChildrenList()
    {
        $rows = $this->entityManager->getRepository($this->itemChildTable)->findAll();

        $parents = [];
        foreach ($rows as $row) {
            $parents[$row->getParent()][] = $row->getChild();
        }

        return $parents;
    }

    /**
     * @param $name
     * @param $childrenList
     * @param $result
     */
    protected function getChildrenRecursive($name, $childrenList, &$result)
    {
        if (isset($childrenList[$name])) {
            foreach ($childrenList[$name] as $child) {
                $result[$child] = true;
                $this->getChildrenRecursive($child, $childrenList, $result);
            }
        }
    }

    /**
     * @param string $name
     * @return mixed|Rule|null
     */
    public function getRule(string $name)
    {
        if ($this->rules !== null) {
            return isset($this->rules[$name]) ? $this->rules[$name] : null;
        }

        $row = $this->entityManager->getRepository($this->ruleTable)->findOneBy(['name' => $name]);
        if (!$row) {
            return null;
        }
        $data = $row->getData();
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        return unserialize($data);
    }

    /**
     * @return array|Rule[]
     */
    public function getRules()
    {
        if ($this->rules !== null) {
            return $this->rules;
        }

        $rows = $this->entityManager->getRepository($this->ruleTable)->findAll();

        $rules = [];
        foreach ($rows as $row) {
            $data = $row->getData();
            $rules[$row->getName()] = unserialize($data);
        }

        return $rules;
    }

    /**
     * @param string $roleName
     * @param int|string $userId
     * @return Assignment|null
     */
    public function getAssignment($roleName, $userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return null;
        }

        $row = $this->entityManager
            ->getRepository($this->assignmentTable)
            ->findOneBy(['itemName' => $roleName, 'userId' => (string)$userId]);

        if (empty($row)) {
            return null;
        }

        return new Assignment([
            'userId' => $row->getUserId(),
            'roleName' => $row->getItemName()->getName(),
            'createdAt' => $row->getCreatedAt(),
        ]);
    }

    /**
     * @param int|string $userId
     * @return array|Assignment[]
     */
    public function getAssignments($userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return [];
        }

        $rows = $this->entityManager
            ->getRepository($this->assignmentTable)
            ->createQueryBuilder('p')
            ->where('p.userId=:userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        $assignments = [];
        foreach ($rows as $row) {
            $roleName = $row->getItemName()->getName();
            $assignments[$roleName] = new Assignment([
                'userId' => $row->getUserId(),
                'roleName' => $roleName,
                'createdAt' => $row->getCreatedAt(),
            ]);
        }

        return $assignments;
    }

    /**
     * @param string $roleName
     * @return array|Assignment[]
     */
    public function getAssignmentsByRole($roleName)
    {

        $rows = $this->entityManager
            ->getRepository($this->assignmentTable)
            ->createQueryBuilder('p')
            ->where('p.itemName=:itemName')
            ->setParameter('itemName', $roleName)
            ->getQuery()
            ->getResult();

        $assignments = [];
        foreach ($rows as $row) {
            $roleName = $row->getItemName()->getName();
            $assignments[$row->getUserId()] = new Assignment([
                'userId' => $row->getUserId(),
                'roleName' => $roleName,
                'createdAt' => $row->getCreatedAt(),
            ]);
        }

        return $assignments;
    }

    /**
     * @param Item $parent
     * @param Item $child
     * @return bool
     */
    public function canAddChild($parent, $child)
    {
        return !$this->detectLoop($parent, $child);
    }

    /**
     * @param Item $parent
     * @param Item $child
     * @return bool
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function addChild($parent, $child)
    {
        if ($parent->name === $child->name) {
            throw new \InvalidArgumentException("Cannot add '{$parent->name}' as a child of itself.");
        }

        if ($parent instanceof Permission && $child instanceof Role) {
            throw new \InvalidArgumentException('Cannot add a role as a child of a permission.');
        }

        if ($this->detectLoop($parent, $child)) {
            throw new \InvalidArgumentException("Cannot add '{$child->name}' as a child of '{$parent->name}'. A loop has been detected.");
        }

        /**
         * @var AuthItemChild $childObject
         */
        $childObject = new $this->itemChildTable();
        $childObject->setParent($parent->name);
        $childObject->setChild($child->name);

        $this->entityManager->persist($childObject);
        $this->entityManager->flush();

        $this->invalidateCache();

        return true;
    }

    /**
     * @param Item $parent
     * @param Item $child
     * @return bool|void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeChild($parent, $child)
    {
        $object = $this->entityManager->getRepository($this->itemChildTable)->findBy(['parent' => $parent->name, 'child' => $child->name]);
        $this->entityManager->remove($object);
        $this->entityManager->flush();

        $this->invalidateCache();
    }

    /**
     * @param Item $parent
     * @return bool|void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeChildren($parent)
    {
        $objects = $this->entityManager->getRepository($this->itemChildTable)->findBy(['parent' => $parent->name]);

        foreach ($objects as $object) {
            $this->entityManager->remove($object);
            $this->entityManager->flush();
        }

        $this->invalidateCache();

    }

    /**
     * @param Item $parent
     * @param Item $child
     * @return bool
     */
    public function hasChild($parent, $child)
    {
        $res = $this->entityManager->getRepository($this->itemChildTable)->findOneBy(['parent' => $parent->name, 'child' => $child->name]);

        return !empty($res);
    }

    /**
     * @param string $name
     * @return array|Item[]
     */
    public function getChildren($name)
    {
        $row = $this->entityManager->getRepository($this->itemTable)->findOneBy(['name' => $name]);
        $childrenCollection = $row->getChild()->toArray();

        $children = [];
        foreach ($childrenCollection as $child) {
            $children[$child->getName()] = $this->populateItem($child);
        }

        return $children;
    }

    /**
     * Checks whether there is a loop in the authorization item hierarchy.
     * @param Item $parent the parent item
     * @param Item $child the child item to be added to the hierarchy
     * @return bool whether a loop exists
     */
    protected function detectLoop($parent, $child)
    {
        if ($child->name === $parent->name) {
            return true;
        }
        foreach ($this->getChildren($child->name) as $grandchild) {
            if ($this->detectLoop($parent, $grandchild)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Permission|Role $role
     * @param int|string $userId
     * @return Assignment
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function assign($role, $userId)
    {
        $assignment = new Assignment([
            'userId' => $userId,
            'roleName' => $role->name,
            'createdAt' => time(),
        ]);


        /**
         * @var AuthAssignment $assignObject
         */
        $assignObject = new $this->assignmentTable();

        $item = $this->entityManager->getRepository($this->itemTable)->findOneBy(['name' => $assignment->roleName]);
        $assignObject->setItemName($item);
        $assignObject->setCreatedAt($assignment->createdAt);
        $assignObject->setUserId($assignment->userId);
        $this->entityManager->persist($assignObject);
        $this->entityManager->flush();

        unset($this->_checkAccessAssignments[(string)$userId]);
        return $assignment;
    }

    /**
     * @param Permission|Role $role
     * @param int|string $userId
     * @return bool
     */
    public function revoke($role, $userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return false;
        }

        unset($this->_checkAccessAssignments[(string)$userId]);
        return $this->entityManager->getRepository($this->assignmentTable)
                ->createQueryBuilder('a')
                ->delete()
                ->where('a.userId = :userId')
                ->setParameter('userId', (string)$userId)
                ->andWhere('a.itemName = :itemName')
                ->setParameter('itemName', $role->name)
                ->getQuery()
                ->execute() > 0;
    }

    /**
     * @param mixed $userId
     * @return bool
     */
    public function revokeAll($userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return false;
        }

        unset($this->_checkAccessAssignments[(string)$userId]);
        return $this->entityManager->getRepository($this->assignmentTable)
                ->createQueryBuilder('a')
                ->delete()
                ->where('a.userId = :userId')
                ->setParameter('userId', (string)$userId)
                ->getQuery()
                ->execute() > 0;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeAll()
    {
        $this->removeAllAssignments();
        $this->entityManager->getRepository($this->itemChildTable)->createQueryBuilder('a')->delete()->getQuery()->execute();
        $this->entityManager->getRepository($this->itemTable)->createQueryBuilder('a')->delete()->getQuery()->execute();
        $this->entityManager->getRepository($this->ruleTable)->createQueryBuilder('a')->delete()->getQuery()->execute();
        $this->invalidateCache();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeAllPermissions()
    {
        $this->removeAllItems(Item::TYPE_PERMISSION);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeAllRoles()
    {
        $this->removeAllItems(Item::TYPE_ROLE);
    }

    /**
     * @param $type
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function removeAllItems($type)
    {
        $names = $this->entityManager->getRepository($this->itemTable)->findBy(['type' => $type]);
        if (empty($names)) {
            return;
        }
        foreach ($names as $name) {
            $this->entityManager->remove($name);
            $this->entityManager->flush();
        }
        $this->invalidateCache();
    }

    public function removeAllRules()
    {
        if (!$this->supportsCascadeUpdate()) {
            $this->db->createCommand()
                ->update($this->itemTable, ['rule_name' => null])
                ->execute();
        }

        $this->db->createCommand()->delete($this->ruleTable)->execute();

        $this->invalidateCache();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeAllAssignments()
    {
        $this->_checkAccessAssignments = [];
        $this->entityManager->getRepository($this->assignmentTable)->createQueryBuilder('a')->delete()->getQuery()->execute();
    }

    public function invalidateCache()
    {
        if ($this->cache !== null) {
            $this->cache->delete($this->cacheKey);
            $this->items = null;
            $this->rules = null;
            $this->parents = null;
        }
        $this->_checkAccessAssignments = [];
    }

    public function loadFromCache()
    {
//        if ($this->items !== null || !$this->cache instanceof CacheInterface) {
//            return;
//        }
//
//        $data = $this->cache->get($this->cacheKey);
//        if (is_array($data) && isset($data[0], $data[1], $data[2])) {
//            [$this->items, $this->rules, $this->parents] = $data;
//            return;
//        }


        $rows = $this->entityManager->getRepository($this->itemTable)->findAll();
        $this->items = [];
        foreach ($rows as $row) {
            $item = [
                'name' => $row->getName(),
                'type' => $row->getType(),
                'description' => $row->getDescription(),
                'rule_name' => $row->getRuleName() ? $row->getRuleName()->getName() : null,
                'data' => $row->getData(),
                'status' => $row->getStatus(),
                'created_at' => $row->getCreatedAt(),
                'updated_at' => $row->getUpdatedAt(),
            ];
            $this->items[$row->getName()] = $this->populateItem($item);
        }

        $rows = $this->entityManager->getRepository($this->ruleTable)->findAll();
        $this->rules = [];
        foreach ($rows as $row) {
            $data = $row->getData();
            if (is_resource($data)) {
                $data = stream_get_contents($data);
            }
            $this->rules[$row->getName()] = unserialize($data);
        }

        $rows = $this->entityManager->getRepository($this->itemChildTable)->findAll();
        $this->parents = [];
        foreach ($rows as $row) {
            if (isset($this->items[$row->getChild()])) {
                $this->parents[$row->getChild()][] = $row->getParent();
            }
        }

//        $this->cache->set($this->cacheKey, [$this->items, $this->rules, $this->parents]);
    }


    /**
     * @param string $roleName
     * @return array|null
     */
    public function getUserIdsByRole($roleName)
    {
        if (empty($roleName)) {
            return [];
        }

        $users = $this->entityManager->getRepository($this->assignmentTable)
            ->createQueryBuilder('p')
            ->select('p.userId')
            ->where('p.itemName = :itemName')
            ->setParameter('itemName', $roleName)
            ->getQuery()
            ->getArrayResult();

        return array_map(fn($user) => $user['userId'], $users) ?: null;
    }

    /**
     * @param $userId
     * @return bool
     */
    protected function isEmptyUserId($userId)
    {
        return !isset($userId) || $userId === '';
    }
}
