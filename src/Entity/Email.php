<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table("admin_email")
 */
class Email {

    /**
     * @ORM\Column(type="integer", options={ "unsigned": false })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $user = '';

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $domain = '';

    public function __construct() {
    }

    public function __toString() {
        return $this->user .'@'. $this->domain;
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): string { return $this->user; }
    public function setUser(string $user): void { $this->user = $user; }

    public function getDomain(): string { return $this->domain; }
    public function setDomain(string $domain): void { $this->domain = $domain; }
    
}
