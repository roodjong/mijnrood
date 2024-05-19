<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\MemberRepository;

use App\Entity\Membership\MembershipStatus;

/**
 * @ORM\Entity
 * @ORM\Table("admin_member_revision")
 */
class MemberDetailsRevision {

    /**
     * @ORM\Column(type="integer", options={ "unsigned": false })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Member", inversedBy="detailRevisions")
     * @ORM\JoinColumn(nullable=false)
     */
    private Member $member;

    /** @ORM\Column(type="boolean") */
    private bool $own;

    /** @ORM\Column(type="datetime") */
    private DateTime $revisionTime;

    /** @ORM\Column(type="string", length=50) */
    private string $firstName;

    /** @ORM\Column(type="string", length=100) */
    private string $lastName;

    /** @ORM\Column(type="string", length=200, nullable=true) */
    private ?string $email = null;

    /** @ORM\Column(type="string", length=20) */
    private string $phone;

    /** @ORM\Column(type="string", length=34, nullable=true) */
    private ?string $iban;

    /** @ORM\Column(type="string", length=100) */
    private string $address;

    /** @ORM\Column(type="string", length=100) */
    private string $city;

    /** @ORM\Column(type="string", length=14) */
    private string $postCode;

    /** @ORM\Column(type="string", length=2) */
    private string $country = 'NL';

    /** @ORM\Column(type="date", nullable=true) */
    private ?DateTime $dateOfBirth = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Membership\MembershipStatus")
     */
    private ?MembershipStatus $currentMembershipStatus = null;

    public function __construct(Member $member, bool $own) {
        $this->member = $member;
        $this->own = $own;
        $this->revisionTime = new DateTime();

        $this->firstName = $member->getFirstName();
        $this->lastName = $member->getLastName();
        $this->email = $member->getEmail();
        $this->phone = $member->getPhone();
        $this->iban = $member->getIban();
        $this->address = $member->getAddress();
        $this->city = $member->getCity();
        $this->postCode = $member->getPostCode();
        $this->country = $member->getCountry();
        $this->dateOfBirth = $member->getDateOfBirth();
        $this->currentMembershipStatus = $member->getCurrentMembershipStatus();
    }

    public function hasChanged(Member $member) {
        return
            $this->getMember()->getId() !== $member->getId()
         || $this->getFirstName() !== $member->getFirstName()
         || $this->getLastName() !== $member->getLastName()
         || $this->getAddress() !== $member->getAddress()
         || $this->getCity() !== $member->getCity()
         || $this->getPhone() !== $member->getPhone()
         || $this->getIban() !== $member->getIban()
         || $this->getEmail() !== $member->getEmail()
         || $this->getPostCode() !== $member->getPostCode()
         || $this->getCountry() !== $member->getCountry()
         || $this->getCurrentMembershipStatus() !== $member->getCurrentMembershipStatus()
         || $this->getDateOfBirth() !== $member->getDateOfBirth();
    }

    public function getId(): ?int { return $this->id; }
    public function getMember(): Member { return $this->member; }
    public function isOwn(): bool { return $this->own; }

    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getAddress(): string { return $this->address; }
    public function getCity(): string { return $this->city; }
    public function getPhone(): string { return $this->phone; }
    public function getIban(): ?string { return $this->iban; }
    public function getEmail(): ?string { return $this->email; }
    public function getPostCode(): string { return $this->postCode; }
    public function getCountry(): string { return $this->country; }
    public function getDateOfBirth(): ?DateTime { return $this->dateOfBirth; }

    public function getCurrentMembershipStatus(): ?MembershipStatus {
        return $this->currentMembershipStatus;
    }

    public function setCurrentMembershipStatus(?MembershipStatus $membershipStatus): void {
        $this->currentMembershipStatus = $membershipStatus;
    }

}
