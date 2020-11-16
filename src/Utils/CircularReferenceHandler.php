<?php


namespace SymfonyRbac\Utils;


use SymfonyRbac\Entity\AuthItem;
use SymfonyRbac\Rbac\Item;
use SymfonyRbac\Services\RbacManager;

class CircularReferenceHandler
{
    /**
     * @var RbacManager
     */
    private $rbacManager;

    /**
     * CircularReferenceHandler constructor.
     * @param RbacManager $rbacManager
     */
    public function __construct(RbacManager $rbacManager)
    {
        $this->rbacManager = $rbacManager;
    }

    public function __invoke($object)
    {
        $handle = null;
        if ($object instanceof AuthItem){
            if ($object->getType() == Item::TYPE_ROLE){
                $handle = $this->rbacManager->createRole($object->getName());
            }else{
                $handle = $this->rbacManager->createPermission($object->getName());
            }
            $handle->description = $object->getDescription();
            $handle->ruleName = $object->getRuleName() ? $object->getRuleName()->getName() : null;
            $handle->data = $object->getData();
            $handle->alias = $object->getAlias();
            $handle->category = $object->getCategory();
            $handle->status = $object->getStatus();
            $handle->createdAt = $object->getCreatedAt();
            $handle->updatedAt = $object->getUpdatedAt();
        }
        return $handle;
    }
}