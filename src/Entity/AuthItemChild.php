<?php

namespace SymfonyRbac\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="auth_item_child")
 * @ORM\Entity
 */
class AuthItemChild
{

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=64)
     */
    private $parent;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=64)
     */
    private $child;


    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function setParent(string $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChild(): ?string
    {
        return $this->child;
    }

    public function setChild(string $child): self
    {
        $this->child = $child;

        return $this;
    }
}
