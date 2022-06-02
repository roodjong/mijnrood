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

    /**
     * @ORM\ManyToMany(targetEntity="Member")
     * @ORM\JoinTable(name="division_member")
     */
    private Collection $contacts;

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

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private bool $canBeSelectedOnApplication = false;

    public function __construct() {
        $this->members = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function __toString() {
        return $this->name;
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getCanBeSelectedOnApplication(): bool { return $this->canBeSelectedOnApplication; }
    public function setCanBeSelectedOnApplication(bool $canBeSelectedOnApplication): void { $this->canBeSelectedOnApplication = $canBeSelectedOnApplication; }

    public function getMembers(): Collection { return $this->members; }

    public function getContacts(): Collection { return $this->contacts; }
    public function addContact(Member $contact) : self {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
        }
        return $this;
    }

    public function removeContact(Member $contact): self {
        $this->contacts->removeElement($contact);
        return $this;
    }

    public function getPostCode(): ?string { return $this->postCode; }
    public function setPostCode(?string $postCode): void { $this->postCode = $postCode; }

    public function getEmail(): ?Email { return $this->email; }
    public function setEmail(?Email $email): void { $this->email = $email; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): void { $this->phone = $phone; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): void { $this->address = $address; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): void { $this->city = $city; }

    public function getTwitter(): ?string { return $this->twitter; }
    public function setTwitter(?string $twitter): void { $this->twitter = $twitter; }

    public function getFacebook(): ?string { return $this->facebook; }
    public function setFacebook(?string $facebook): void { $this->facebook = $facebook; }

    public function getInstagram(): ?string { return $this->instagram; }
    public function setInstagram(?string $instagram): void { $this->instagram = $instagram; }

}
