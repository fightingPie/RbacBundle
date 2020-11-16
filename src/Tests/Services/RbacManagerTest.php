<?php


namespace SymfonyRbac\Tests\Services;

use SymfonyRbac\Rbac\Rules\TestRule;
use SymfonyRbac\Services\RbacManager;
use SymfonyRbac\Tests\TestCase;

class RbacManagerTest extends TestCase {

  private $em;
  private $rbac;

  /**
   * RbacManagerTest constructor.
   */
  public function setUp(): void {
    parent::setUp();
    $this->em = $this->container->get('doctrine')->getManager('rbac');
    $this->rbac = new RbacManager($this->em);
  }


  public function testIndex() {
//    $rule = new TestRule();
//    $this->rbac->update('testing2', $rule);
    $result = $this->rbac->checkAccess(31, 'admin_departments_delete');
  }

  public function testAddRole() {
    $rule = new TestRule();
    $className = TestRule::class;
    $role = $this->rbac->createRole('/defaultss');
    $role->description = 'test';
    $role->ruleName = null;
    $role->data = null;

    $this->rbac->add($role);
    $result = $this->rbac->checkAccess(31, '1');
  }


  public function testUpdateRole() {
    $name = 'admin';
    $role = $this->rbac->getRole($name);
//    $role->name = 'admins';
    $role->description = '21341241sdf';
    $role->status = 1;
    $role->data = '21341241sdf';
    $role->ruleName = null;
    $this->rbac->update($name, $role);
    $result = $this->rbac->checkAccess(31, '1');
  }

  public function testRemoveObject() {
    $name = '234';
    $object = $this->rbac->getRole($name);
    $object = $this->rbac->getRule($name);
    $this->rbac->remove($object);

  }

  public function testGetObject() {
    $object = $this->rbac->getAssignment('/rbac/default', 31);
  }

  public function testGetUserIdsByRole(){
    $res = $this->rbac->getUserIdsByRole('/rbac/default');
  }

  public function testRemove(){
    $res = $this->rbac->revokeAll(7);
  }

    public function testRule(){
        $res = $this->rbac->getRules();
    }

    public function testChildren(){
        $res = $this->rbac->getChildren('admin_user');
    }

    public function testItem(){
        $res = $this->rbac->getRoles();
    }

    public function testRole(){
        $res = $this->rbac->getChildRoles('admin_user');

    }

    public function testPermission(){
        $res = $this->rbac->getPermissionsByUser(31);

    }
}