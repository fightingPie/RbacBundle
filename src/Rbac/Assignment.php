<?php
namespace SymfonyRbac\Rbac;

class Assignment extends BaseObject
{
    public $userId;
    /**
     * @var string the role name
     */
    public $roleName;
    /**
     * @var int UNIX timestamp representing the assignment creation time
     */
    public $createdAt;
}
