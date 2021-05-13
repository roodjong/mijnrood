<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table("admin_division")
 */
class Division {

    /**
     * @ORM\Column(type="integer", options={ "unsigned": false })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $name = '';

    /** @ORM\ManyToOne(targetEntity="Member")
     *  @ORM\JoinColumn(nullable=true)
     */
    private ?Member $contact = null;

    /**
     * @ORM\OneToMany(targetEntity="Member", mappedBy="division")
     */
    private Collection $members;

    /**
     * @ORM\ManyToOne(targetEntity="Email")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Email $email = null;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $phone = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $city;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $address = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Regex(pattern="/^[1-9][0-9]{3}[A-Z]{2}$/")
     */
    private ?string $postCode;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\Url
     */
    private string $facebook;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\Url
     */
    private string $instagram;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\Url
     */
    private string $twitter;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="division")
     */
    private Collection $events;

    public function __construct() {
        $this->members = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function __toString() {
        return $this->name;
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getMembers(): Collection { return $this->members; }

    public function getContact(): ?Member { return $this->contact; }
    public function setContact(?Member $contact): void { $this->contact = $contact; }

    public function getPostCode(): ?string { return $this->postCode; }
    public function setPostCode(?string $postCode): void { $this->postCode = $postCode; }

    public function getEmail(): ?Email { return $this->email; }
    public function setEmail(?Email $email): void { $this->email = $email; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): void { $this->phone = $phone; }

    public function getAddress(): string { return $this->address; }
    public function setAddress(string $address): void { $this->address = $address; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): void { $this->city = $city; }

    public function getTwitter(): ?string { return $this->twitter; }
    public function setTwitter(?string $twitter): void { $this->twitter = $twitter; }

    public function getFacebook(): ?string { return $this->facebook; }
    public function setFacebook(?string $facebook): void { $this->facebook = $facebook; }

    public function getInstagram(): ?string { return $this->instagram; }
    public function setInstagram(?string $instagram): void { $this->instagram = $instagram; }

}
