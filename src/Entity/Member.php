<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Repository\MemberRepository;

/**
 * @ORM\Entity
 * @ORM\Table("admin_member")
 */
class Member implements UserInterface {

    const PERIOD_MONTHLY = 0;
    const PERIOD_QUARTERLY = 1;
    const PERIOD_ANNUALLY = 2;

    /**
     * @ORM\Column(type="integer", options={ "unsigned": false })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $firstName = '';

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $lastName = '';

    /**
     * @ORM\Column(type="string", length=200)
     * @Assert\Email
     */
    private string $email = '';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $phone = '';

    /**
     * @ORM\Column(type="string", length=34)
     * @Assert\Regex(
     *   pattern="/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/i"
     * )
     */
    private string $iban = '';

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $address = '';

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $city = '';

    /**
     * @ORM\Column(type="string", length=6)
     * @Assert\Regex(pattern="/^[1-9][0-9]{3}[A-Z]{2}$/", htmlPattern="\d{4}[A-Z]{2}")
     */
    private string $postCode = '';

    /**
     * @ORM\ManyToOne(targetEntity="Division", inversedBy="members")
     */
    private ?Division $division = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $registrationTime = null;

    /**
     * @ORM\OneToMany(targetEntity="ContributionPayment", mappedBy="member", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private Collection $contributionPayments;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $mollieSubscriptionId;

    /**
     * @ORM\Column(type="integer")
     */
    private int $contributionPeriod = self::PERIOD_MONTHLY;

    /**
     * @ORM\Column(type="json", options={ "default": "[]" })
     */
    private array $roles = [];

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $passwordHash = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $newPasswordTokenGeneratedTime = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $newPasswordToken = null;

    public function __construct() {
        $this->timeRegistered = new DateTime();
        $this->contributionPayments = new ArrayCollection();
    }

    public function __toString() {
        return $this->lastName .', '. $this->firstName;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): void { $this->firstName = $firstName; }

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): void { $this->lastName = $lastName; }

    public function getAddress(): string { return $this->address; }
    public function setAddress(string $address): void { $this->address = $address; }

    public function getCity(): string { return $this->city; }
    public function setCity(string $city): void { $this->city = $city; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): void { $this->phone = $phone; }

    public function getIban(): string { return $this->iban; }
    public function setIban(string $iban): void { $this->iban = $iban; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getPostCode(): string { return $this->postCode; }
    public function setPostCode(string $postCode): void { $this->postCode = $postCode; }

    public function getDivision(): ?Division { return $this->division; }
    public function setDivision(?Division $division): void { $this->division = $division; }

    public function getSpMembershipId(): int { return $this->spMembershipId; }
    public function setSpMembershipId(int $spMembershipId): void { $this->spMembershipId = $spMembershipId; }

    public function getRegistrationTime(): ?DateTime { return $this->registrationTime; }
    public function setRegistrationTime(?DateTime $registrationTime): void { $this->registrationTime = $registrationTime; }

    public function getContributionPayments(): Collection { return $this->contributionPayments; }

    public function addContributionPayment(ContributionPayment $payment) {
        $payment->setMember($this);
        $this->contributionPayments->add($payment);
    }

    public function removeContributionPayment(ContributionPayment $payment) {
        $this->contributionPayments->removeElement($payment);
    }

    public function getMollieSubscriptionId(): ?string { return $this->mollieSubscriptionId; }
    public function setMollieSubscriptionId(?string $mollieSubscriptionId): void { $this->mollieSubscriptionId = $mollieSubscriptionId; }

    public function getContributionPeriod(): int { return $this->contributionPeriod; }
    public function setContributionPeriod(int $contributionPeriod): void {
        if (!in_array($contributionPeriod, [self::PERIOD_MONTHLY, self::PERIOD_QUARTERLY, self::PERIOD_ANNUALLY]))
            throw new \Exception('Period must be PERIOD_MONTHLY, PERIOD_QUARTERLY or PERIOD_ANNUALLY');
        $this->contributionPeriod = $contributionPeriod;
    }

    public function isContributionPaidAutomatically(): bool {
        return $this->mollieSubscriptionId !== null;
    }

    /** @see UserInterface */
    public function getUsername(): string { return $this->id; }

    /** @see UserInterface */
    public function getRoles(): array {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    /** @see UserInterface */
    public function getPassword(): string { return (string) $this->passwordHash; }

    public function setPasswordHash(?string $passwordHash): void { $this->passwordHash = $passwordHash; }

    /** @see UserInterface */
    public function getSalt() { }

    /** @see UserInterface */
    public function eraseCredentials() { }

    public function getNewPasswordToken(): ?string { return $this->newPasswordToken; }
    public function setNewPasswordToken(?string $newPasswordToken): void {
        $this->newPasswordToken = $newPasswordToken;
        $this->newPasswordTokenGeneratedTime = $newPasswordToken === null ? null : new DateTime();
    }
    public function getNewPasswordTokenGeneratedTime(): ?DateTime { return $this->newPasswordTokenGeneratedTime; }
}
