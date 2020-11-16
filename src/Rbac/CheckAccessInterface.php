<?php

namespace SymfonyRbac\Rbac;

interface CheckAccessInterface
{
  /**
   * Checks if the user has the specified permission.
   * @param string|int $userId the user ID. This should be either an integer or a string representing
   * the unique identifier of a user.
   * @param string $permissionName the name of the permission to be checked against
   * @param array $params name-value pairs that will be passed to the rules associated
   * with the roles and permissions assigned to the user.
   * @return bool whether the user has the specified permission.
   */
    public function checkAccess($userId, string $permissionName, $params = []);
}
