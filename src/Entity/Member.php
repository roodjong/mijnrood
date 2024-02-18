<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Repository\MemberRepository;

/**
 * @ORM\Entity
 * @ORM\Table("admin_member")
 */
class Member implements UserInterface, PasswordAuthenticatedUserInterface {

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
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\Email
     */
    private ?string $email = null;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $phone = '';

    /**
     * @ORM\Column(type="string", length=34, nullable=true)
     * @Assert\Regex(
     *   pattern="/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/i"
     * )
     */
    private ?string $iban = null;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $address = '';

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $city = '';

    /**
     * @ORM\Column(type="string", length=14)
     */
    private string $postCode = '';

    /**
     * @ORM\Column(type="string", length=2)
     * @Assert\Regex(pattern="/^[A-Z]{2}$/")
     */
    private string $country = 'NL';

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $dateOfBirth = null;

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
    private ?string $mollieCustomerId = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $mollieSubscriptionId = null;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    private bool $createSubscriptionAfterPayment = false;

    /**
     * @ORM\Column(type="integer", options={"default": 2})
     */
    private int $contributionPeriod = self::PERIOD_ANNUALLY;

    /**
     * @ORM\Column(type="integer", options={"default": 500})
     */
    private int $contributionPerPeriodInCents;

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

    /**
     * @ORM\OneToMany(targetEntity="MemberDetailsRevision", mappedBy="member", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private Collection $detailRevisions;

    /**
     * @ORM\OneToMany(targetEntity="Email", mappedBy="manager")
     */
    private Collection $managingEmails;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    private bool $acceptUsePersonalInformation = true;

    public function __construct() {
        $this->registrationTime = new DateTime;
        $this->contributionPayments = new ArrayCollection;
        $this->detailRevisions = new ArrayCollection;
        $this->managingEmails = new ArrayCollection;
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

    public function getFullName(): string {
        return $this->firstName. ' ' . $this->lastName;
    }

    public function getAddress(): string { return $this->address; }
    public function setAddress(string $address): void { $this->address = $address; }

    public function getCity(): string { return $this->city; }
    public function setCity(string $city): void { $this->city = $city; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): void { $this->phone = $phone; }

    public function getIban(): ?string { return $this->iban; }
    public function setIban(?string $iban): void { $this->iban = $iban; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): void { $this->email = $email; }

    public function getPostCode(): string { return $this->postCode; }
    public function setPostCode(string $postCode): void { $this->postCode = $postCode; }

    public function getCountry(): string { return $this->country; }
    public function setCountry(string $country): void { $this->country = $country; }

    public function getDateOfBirth(): ?DateTime { return $this->dateOfBirth; }
    public function setDateOfBirth(?DateTime $dateOfBirth): void { $this->dateOfBirth = $dateOfBirth; }

    public function getDivision(): ?Division { return $this->division; }
    public function setDivision(?Division $division): void { $this->division = $division; }

    public function getRegistrationTime(): ?DateTime { return $this->registrationTime; }
    public function setRegistrationTime(?DateTime $registrationTime): void { $this->registrationTime = $registrationTime; }

    public function getAcceptUsePersonalInformation(): bool { return $this->acceptUsePersonalInformation; }
    public function setAcceptUsePersonalInformation(bool $acceptUsePersonalInformation): void { $this->acceptUsePersonalInformation = $acceptUsePersonalInformation; }

    public function getContributionPerPeriodInCents(): int { return $this->contributionPerPeriodInCents; }
    public function setContributionPerPeriodInCents(int $contributionPerPeriodInCents): void { $this->contributionPerPeriodInCents = $contributionPerPeriodInCents; }

    public function getContributionPerPeriodInEuros(): float { return $this->contributionPerPeriodInCents / 100; }
    public function setContributionPerPeriodInEuros(float $contributionPerPeriodInEuros): void { $this->contributionPerPeriodInCents = round($contributionPerPeriodInEuros * 100); }

    public function getCreateSubscriptionAfterPayment(): bool { return $this->createSubscriptionAfterPayment; }
    public function setCreateSubscriptionAfterPayment(bool $createSubscriptionAfterPayment): void { $this->createSubscriptionAfterPayment = $createSubscriptionAfterPayment; }

    public function getPaidContributionPayments(): Collection { return $this->contributionPayments->filter(fn($payment) => $payment->getStatus() == ContributionPayment::STATUS_PAID); }

    public function getContributionPayments(): Collection { return $this->contributionPayments; }

    public function isAdmin(): bool { return in_array('ROLE_ADMIN', $this->getRoles()); }
    public function setIsAdmin(bool $isAdmin): void {
        if ($isAdmin)
            $this->roles = array_merge($this->roles, ['ROLE_ADMIN']);
        else
            $this->roles = array_diff($this->roles, ['ROLE_ADMIN']);
    }

    public function isContributionCompleted(DateTime $when) {
        $year = $when->format('Y');
        $month = $when->format('n');
        switch ($this->getContributionPeriod()) {
            case self::PERIOD_MONTHLY:
                $payments = $this->contributionPayments->filter(fn($payment) => $payment->getPeriodYear() == $year && $payment->getPeriodMonthStart() == $month);
                break;
            case self::PERIOD_QUARTERLY:
                $quarter = ceil($month / 3);
                $payments = $this->contributionPayments->filter(fn($payment) => $payment->getPeriodYear() == $year && $payment->getPeriodMonthStart() <= $month && $payment->getPeriodMonthEnd() >= $month);
                break;
            case self::PERIOD_ANNUALLY:
                $payments = $this->contributionPayments->filter(fn($payment) => $payment->getPeriodYear() == $year);
                break;
        }
        $payments = $payments->filter(fn($payment) => $payment->getStatus() == ContributionPayment::STATUS_PAID);
        return count($payments) > 0;
    }

    public function addContributionPayment(ContributionPayment $payment) {
        $payment->setMember($this);
        $this->contributionPayments->add($payment);
    }

    public function removeContributionPayment(ContributionPayment $payment) {
        $this->contributionPayments->removeElement($payment);
    }

    public function getMollieCustomerId(): ?string { return $this->mollieCustomerId; }
    public function setMollieCustomerId(?string $mollieCustomerId): void { $this->mollieCustomerId = $mollieCustomerId; }

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

    public function getDetailRevisions(): Collection { return $this->detailRevisions; }
    public function getLastDetailRevision(): ?MemberDetailsRevision {
        return $this->detailRevisions->getIterator()->uasort(function(MemberDetailsRevision $a, MemberDetailsRevision $b) {
            return $b->getId() - $a->getId();
        })[0] ?? null;
    }

    public function getManagingEmails(): Collection {
        return $this->managingEmails;
    }

    /** @see UserInterface */
    public function getUsername(): string { return $this->id; }

    /** @see UserInterface */
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    /** @see UserInterface */
    public function getRoles(): array {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        if (!is_null($this->getDivision())) {
            $isContactOfAnyDivision = $this->getDivision()->getContacts()->exists(
                function ($key, $division) {
                    return $division->getId() === $this->getId();
                }
            );
            if ($isContactOfAnyDivision) {
                $roles[] = 'ROLE_DIVISION_CONTACT';
            }
        }
        return array_unique($roles);
    }

    /** @see UserInterface */
    public function getPassword(): string { return (string) $this->passwordHash; }

    public function hasPassword(): bool { return $this->passwordHash !== null; }

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
    public function generateNewPasswordToken() {
        // Urlsafe base64 encode some random bytes as token
        $this->newPasswordToken = rtrim(strtr(base64_encode(random_bytes(36)), '+/', '-_'), '=');
        $this->newPasswordTokenGeneratedTime = new DateTime();
    }
    public function getNewPasswordTokenGeneratedTime(): ?DateTime { return $this->newPasswordTokenGeneratedTime; }
}
