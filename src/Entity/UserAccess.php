<?php

namespace App\Entity;

use App\Repository\UserAccessRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserAccessRepository::class)
 */
class UserAccess
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $hash;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="userAccesses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $loginDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $lastUsageDate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $refreshToken;


     /* CUSTOM METHODS */

     public function __construct()
     {
         $this->loginDate = new \DateTime('now');
         $this->lastUsageDate = new \DateTime('now');
     }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getLoginDate(): ?\DateTimeInterface
    {
        return $this->loginDate;
    }

    // public function setLoginDate(\DateTimeInterface $loginDate): self
    // {
    //     $this->loginDate = $loginDate;

    //     return $this;
    // }

    public function getLastUsageDate(): ?\DateTimeInterface
    {
        return $this->lastUsageDate;
    }

    public function setLastUsageDate(): self
    {
        $this->lastUsageDate = new \DateTime('now');

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }
}
