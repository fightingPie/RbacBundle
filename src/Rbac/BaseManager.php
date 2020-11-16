<?php

namespace SymfonyRbac\Rbac;

use SymfonyRbac\Entity\AuthItem;
use SebastianBergmann\ObjectReflector\TestFixture\ChildClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class BaseManager  implements ManagerInterface
{
    /**
     * @var array a list of role names that are assigned to every user automatically without calling [[assign()]].
     * Note that these roles are applied to users, regardless of their state of authentication.
     */
    protected $defaultRoles = [];


    /**
     * Returns the named auth item.
     * @param string $name the auth item name.
     * @return Item the auth item corresponding to the specified name. Null is returned if no such item.
     */
    abstract protected function getItem($name);

    /**
     * Returns the items of the specified type.
     * @param int $type the auth item type (either [[Item::TYPE_ROLE]] or [[Item::TYPE_PERMISSION]]
     * @return Item[] the auth items of the specified type.
     */
    abstract protected function getItems($type);

    /**
     * Adds an auth item to the RBAC system.
     * @param Item $item the item to add
     * @return bool whether the auth item is successfully added to the system
     * @throws \Exception if data validation or saving fails (such as the name of the role or permission is not unique)
     */
    abstract protected function addItem($item);

    /**
     * Adds a rule to the RBAC system.
     * @param Rule $rule the rule to add
     * @return bool whether the rule is successfully added to the system
     * @throws \Exception if data validation or saving fails (such as the name of the rule is not unique)
     */
    abstract protected function addRule($rule);

    /**
     * Removes an auth item from the RBAC system.
     * @param Item $item the item to remove
     * @return bool whether the role or permission is successfully removed
     * @throws \Exception if data validation or saving fails (such as the name of the role or permission is not unique)
     */
    abstract protected function removeItem($item);

    /**
     * Removes a rule from the RBAC system.
     * @param Rule $rule the rule to remove
     * @return bool whether the rule is successfully removed
     * @throws \Exception if data validation or saving fails (such as the name of the rule is not unique)
     */
    abstract protected function removeRule($rule);

    /**
     * Updates an auth item in the RBAC system.
     * @param string $name the name of the item being updated
     * @param Item $item the updated item
     * @return bool whether the auth item is successfully updated
     * @throws \Exception if data validation or saving fails (such as the name of the role or permission is not unique)
     */
    abstract protected function updateItem($name, $item);

    /**
     * Updates a rule to the RBAC system.
     * @param string $name the name of the rule being updated
     * @param Rule $rule the updated rule
     * @return bool whether the rule is successfully updated
     * @throws \Exception if data validation or saving fails (such as the name of the rule is not unique)
     */
    abstract protected function updateRule($name, $rule);

    /**
     * @param string $name
     * @return Role
     */
    public function createRole(string $name)
    {
        $role = new Role();
        $role->name = $name;
        return $role;
    }

    /**
     * @param string $name
     * @return Permission
     */
    public function createPermission(string $name)
    {
        $permission = new Permission();
        $permission->name = $name;
        return $permission;
    }

    /**
     * @param Permission|Role|Rule $object
     * @return bool
     * @throws \Exception
     */
    public function add($object)
    {
        if ($object instanceof Item) {
            if ($object->ruleName && $this->getRule($object->ruleName) === null) {
                $rule = new $object->ruleName();
                $rule->name = $object->ruleName;
                $this->addRule($rule);
            }

            return $this->addItem($object);
        } elseif ($object instanceof Rule) {
            return $this->addRule($object);
        }

        throw new \InvalidArgumentException('Adding unsupported object type.');
    }

    /**
     * @param Permission|Role|Rule $object
     * @return bool
     * @throws \Exception
     */
    public function remove($object)
    {
        if ($object instanceof Item) {
            return $this->removeItem($object);
        } elseif ($object instanceof Rule) {
            return $this->removeRule($object);
        }

        throw new \InvalidArgumentException('Removing unsupported object type.');
    }

    /**
     * {@inheritdoc}
     */
    public function update($name, $object)
    {
        if ($object instanceof Item) {
            if ($object->ruleName && $this->getRule($object->ruleName) === null) {
              $rule = new $object->ruleName();
              $rule->name = $object->ruleName;
              $this->addRule($rule);
            }
            return $this->updateItem($name, $object);
        } elseif ($object instanceof Rule) {
            return $this->updateRule($name, $object);
        }

        throw new \InvalidArgumentException('Updating unsupported object type.');
    }

  /**
   * @param string $name
   * @return Item|Role|null
   */
    public function getRole(string $name)
    {
        $item = $this->getItem($name);
        return $item instanceof Item && $item->type == Item::TYPE_ROLE ? $item : null;
    }

    /**
     * @param string $name
     * @return Item|Permission|null
     */
    public function getPermission(string $name)
    {
        $item = $this->getItem($name);
        return $item instanceof Item && $item->type == Item::TYPE_PERMISSION ? $item : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->getItems(Item::TYPE_ROLE);
    }

  /**
   * @param $roles
   */
    public function setDefaultRoles($roles)
    {
        if (is_array($roles)) {
            $this->defaultRoles = $roles;
        } elseif ($roles instanceof \Closure) {
            $roles = call_user_func($roles);
            if (!is_array($roles)) {
                throw new \InvalidArgumentException('Default roles closure must return an array');
            }
            $this->defaultRoles = $roles;
        } else {
            throw new \InvalidArgumentException('Default roles must be either an array or a callable');
        }
    }

    public function getDefaultRoles()
    {
        return $this->defaultRoles;
    }

    public function getDefaultRoleInstances()
    {
        $result = [];
        foreach ($this->defaultRoles as $roleName) {
            $result[$roleName] = $this->createRole($roleName);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions()
    {
        return $this->getItems(Item::TYPE_PERMISSION);
    }

  /**
   * @param $user
   * @param $item
   * @param $params
   * @return bool
   */
    protected function executeRule($user, $item, $params)
    {
        if ($item->ruleName === null) {
            return true;
        }
        $rule = $this->getRule($item->ruleName);
        if ($rule instanceof Rule) {
            return $rule->execute($user, $item, $params);
        }

        throw new \InvalidArgumentException("Rule not found: {$item->ruleName}");
    }

    /**
     * @param array $assignments
     * @return bool
     */
    protected function hasNoAssignments(array $assignments)
    {
        return empty($assignments) && empty($this->defaultRoles);
    }

}
