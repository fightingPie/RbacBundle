<?php

namespace SymfonyRbac\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthRule
 *
 * @ORM\Table(name="auth_rule", indexes={@ORM\Index(name="name", columns={"name"}), @ORM\Index(name="updated_at", columns={"updated_at"}), @ORM\Index(name="created_at", columns={"created_at"})})
 * @ORM\Entity
 */
class AuthRule
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false, options={"comment"="规则名称"})
     * @ORM\Id
     */
    private $name;

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @var string|null
     *
     * @ORM\Column(name="data", type="text", length=65535, nullable=true, options={"comment"="存的是一个序列化的实现了rbacRule接口的类的一个对象实例"})
     */
    private $data;

    /**
     * @var int|null
     *
     * @ORM\Column(name="created_at", type="integer", nullable=true, options={"comment"="创建时间"})
     */
    private $createdAt;

    /**
     * @var int|null
     *
     * @ORM\Column(name="updated_at", type="integer", nullable=true, options={"comment"="更新时间"})
     */
    private $updatedAt;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?int $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?int $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }


}
