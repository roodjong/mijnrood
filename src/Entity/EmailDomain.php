<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="admin_email_domain", uniqueConstraints={
 *  @ORM\UniqueConstraint(name="domain", columns={"domain"})
 * })
 * @UniqueEntity("domain")
 */
class EmailDomain {

    /**
     * @ORM\Column(type="integer", options={ "unsigned": false })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $domain = '';

    public function __construct() {
    }

    public function __toString() {
        return $this->domain;
    }

    public function getId(): ?int { return $this->id; }

    public function getDomain(): string { return $this->domain; }
    public function setDomain(string $domain): void { $this->domain = $domain; }

}
