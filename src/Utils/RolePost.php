<?php
namespace SymfonyRbac\Utils;

use SymfonyRbac\Rbac\Item;

class RolePost extends Item
{
  /**
   * {@inheritdoc}
   */
  public $type = self::TYPE_ROLE;

  public $child;

}
