<?php

namespace SymfonyRbac\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * AuthItem
 *
 * @ORM\Table(name="auth_item", indexes={@ORM\Index(name="rule_name", columns={"rule_name"}), @ORM\Index(name="type", columns={"type"})})
 * @ORM\Entity
 */
class AuthItem
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, nullable=false, options={"comment"="角色（权限）名称"})
     * @ORM\Id
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", length=64, nullable=true, options={"comment"="角色（权限）别称"})
     */
    private $alias;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer", nullable=false, options={"comment"="type:1表示 角色；2表示权限"})
     */
    private $type;


    /**
     * @var int
     *
     * @ORM\Column(name="category", type="integer", nullable=true, options={"comment"="菜单分类"})
     */
    private $category;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true, options={"comment"="描述"})
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="data", type="text", length=65535, nullable=true, options={"comment"="额外数据，serialize存入"})
     */
    private $data;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default"="1","comment"="状态：1开启 0关闭"})
     */
    private $status = '1';

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

    /**
     * @ORM\ManyToOne(targetEntity="AuthRule")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="rule_name", referencedColumnName="name")
     * })
     */
    private $ruleName;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AuthItem", inversedBy="parent")
     * @ORM\JoinTable(name="auth_item_child",
     *   joinColumns={
     *     @ORM\JoinColumn(name="parent", referencedColumnName="name")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="child", referencedColumnName="name")
     *   }
     * )
     */
    private $child;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AuthItem", inversedBy="child")
     * @ORM\JoinTable(name="auth_item_child",
     *   joinColumns={
     *     @ORM\JoinColumn(name="child", referencedColumnName="name")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="parent", referencedColumnName="name")
     *   }
     * )
     */
    private $parent;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->child = new ArrayCollection();
        $this->parent = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCategory(): ?int
    {
        return $this->category;
    }

    public function setCategory(int $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

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

    public function getRuleName(): ?AuthRule
    {
        return $this->ruleName;
    }

    public function setRuleName($ruleName): self
    {
        $this->ruleName = $ruleName;

        return $this;
    }

    /**
     * @return Collection|AuthItem[]
     */
    public function getChild(): Collection
    {
        return $this->child;
    }

    /**
     * @return Collection|AuthItem[]
     */
    public function getParent(): Collection
    {
        return $this->parent;
    }

    public function addChild(AuthItem $child): self
    {
        if (!$this->child->contains($child)) {
            $this->child[] = $child;
        }

        return $this;
    }

    public function removeChild(AuthItem $child): self
    {
        if ($this->child->contains($child)) {
            $this->child->removeElement($child);
        }

        return $this;
    }

}
