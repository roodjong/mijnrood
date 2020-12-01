<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\MemberRepository;

/**
 * @ORM\Enttiy
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
     * @ORM\ManyToOne(targetEntity="Member")
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

    /** @ORM\Column(type="string", length=200) */
    private string $email;

    /** @ORM\Column(type="string", length=20) */
    private string $phone;

    /** @ORM\Column(type="string", length=34) */
    private string $iban;

    /** @ORM\Column(type="string", length=100) */
    private string $address;

    /** @ORM\Column(type="string", length=100) */
    private string $city;

    /** @ORM\Column(type="string", length=6) */
    private string $postCode;

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
        $htis->postCode = $member->getPostCode();
    }

    public function getId(): ?int { return $this->id; }
    public function getMember(): Member { $this->member = $member; }
    public function isOwn(): bool { return $this->own; }

    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getAddress(): string { return $this->address; }
    public function getCity(): string { return $this->city; }
    public function getPhone(): string { return $this->phone; }
    public function getIban(): string { return $this->iban; }
    public function getEmail(): string { return $this->email; }
    public function getPostCode(): string { return $this->postCode; }

}
