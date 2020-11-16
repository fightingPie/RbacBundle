<?php
namespace SymfonyRbac\Rbac;


class Item extends BaseObject
{
    const TYPE_ROLE = 1;
    const TYPE_PERMISSION = 2;

    const STATUS_ACTIVE = 1;
    const STATUS_PASSIVE = 0;

    /**
     * @var int the type of the item. This should be either [[TYPE_ROLE]] or [[TYPE_PERMISSION]].
     */
    public $type;
    /**
     * @var int the category of the item.
     */
    public $category;
    /**
     * @var string the name of the item. This must be globally unique.
     */
    public $name;
    /**
     * @var string the alias of the item.
     */
    public $alias;
    /**
     * @var string the item description
     */
    public $description;
    /**
     * @var string name of the rule associated with this item
     */
    public $ruleName;
    /**
     * @var mixed the additional data associated with this item
     */
    public $data;
    /**
     * @var int the status associated with this item
     */
    public $status;
    /**
     * @var int UNIX timestamp representing the item creation time
     */
    public $createdAt;
    /**
     * @var int UNIX timestamp representing the item updating time
     */
    public $updatedAt;
}
