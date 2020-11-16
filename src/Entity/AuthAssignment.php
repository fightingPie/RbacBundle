<?php

namespace SymfonyRbac\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthAssignment
 *
 * @ORM\Table(name="auth_assignment", indexes={@ORM\Index(name="IDX_2EC0490E96133AFD", columns={"item_name"})})
 * @ORM\Entity
 */
class AuthAssignment
{
    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=64, nullable=false, options={"comment"="用户id"})
     * @ORM\Id
     */
    private $userId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="created_at", type="integer", nullable=true, options={"comment"="创建时间"})
     */
    private $createdAt;

    /**
     * @var \AuthItem
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="AuthItem")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="item_name", referencedColumnName="name")
     * })
     */
    private $itemName;

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
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

    public function getItemName(): ?AuthItem
    {
        return $this->itemName;
    }

    public function setItemName(?AuthItem $itemName): self
    {
        $this->itemName = $itemName;

        return $this;
    }


}
