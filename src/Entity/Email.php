<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table("admin_email", uniqueConstraints={
 *  @ORM\UniqueConstraint(name="email", columns={"user", "domain_id"})
 * })
 * @UniqueEntity({"user", "domain"})
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
     * @ORM\ManyToOne(targetEntity="EmailDomain", inversedBy="emails")
     * @ORM\JoinColumn(nullable=false)
     */
    private EmailDomain $domain;

    /**
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private ?Member $manager = null;

    private string $password = '';
    private ?string $changePassword = null;

    public function __construct() {
    }

    public function __toString() {
        return $this->user .'@'. $this->domain;
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): string { return $this->user; }
    public function setUser(string $user): void { $this->user = $user; }

    public function getDomain(): EmailDomain { return $this->domain; }
    public function setDomain(EmailDomain $domain): void { $this->domain = $domain; }

    public function getDomainName(): string { return $this->domain->getDomain(); }

    public function getChangePassword(): ?string { return $this->changePassword; }
    public function setChangePassword(?string $changePassword): void { $this->changePassword = $changePassword; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): void { $this->password = $password; }

    public function getManager(): ?Member { return $this->manager; }
    public function setManager(?Member $manager): void { $this->manager = $manager; }

}
