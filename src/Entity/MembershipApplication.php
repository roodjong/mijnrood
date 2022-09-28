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
 * @ORM\Table("admin_membership_application")
 */
class MembershipApplication {

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
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $middleName = null;

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
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $registrationTime = null;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $contributionPeriod = Member::PERIOD_MONTHLY;

    /**
     * @ORM\ManyToOne(targetEntity="Division")
     */
    private ?Division $preferredDivision = null;

    /**
     * @ORM\ManyToMany(targetEntity="WorkGroup")
     */
    private Collection $preferredWorkGroups;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $contributionPerPeriodInCents = 750;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $mollieCustomerId = null;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private ?bool $paid = false;

    public function __construct() {
        $this->registrationTime = new DateTime();
    }

    public function __toString() {
        return $this->lastName .', '. $this->firstName;
    }

    public function createMember(?string $mollieSubscriptionId): Member {
        $member = new Member();
        $member->setFirstName($this->getFirstName());
        $member->setMiddleName($this->getMiddleName());
        $member->setLastName($this->getLastName());
        $member->setAddress($this->getAddress());
        $member->setCity($this->getCity());
        $member->setPhone($this->getPhone());
        $member->setIban($this->getIban());
        $member->setEmail($this->getEmail());
        $member->setPostCode($this->getPostCode());
        $member->setCountry($this->getCountry());
        $member->setDateOfBirth($this->getDateOfBirth());
        $member->setRegistrationTime($this->getRegistrationTime());
        $member->setContributionPerPeriodInCents($this->getContributionPerPeriodInCents());
        $member->setContributionPeriod($this->getContributionPeriod());
        $member->setDivision($this->getPreferredDivision());
        $member->setMollieCustomerId($this->getMollieCustomerId());
        $member->setMollieSubscriptionId($mollieSubscriptionId);
        $member->setWorkGroups($this->getPreferredWorkGroups());
        return $member;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): void { $this->firstName = $firstName; }

    public function getMiddleName(): ?string { return $this->middleName; }
    public function setMiddleName(?string $middleName): void { $this->middleName = $middleName; }

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $lastName): void { $this->lastName = $lastName; }

    public function getAddress(): string { return $this->address; }
    public function setAddress(string $address): void { $this->address = $address; }

    public function getCity(): string { return $this->city; }
    public function setCity(string $city): void { $this->city = $city; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): void { $this->phone = $phone; }

    public function getIban(): ?string { return $this->iban; }
    public function setIban(?string $iban): void { $this->iban = $iban; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getPostCode(): string { return $this->postCode; }
    public function setPostCode(string $postCode): void { $this->postCode = $postCode; }

    public function getCountry(): string { return $this->country; }
    public function setCountry(string $country): void { $this->country = $country; }

    public function getDateOfBirth(): ?DateTime { return $this->dateOfBirth; }
    public function setDateOfBirth(?DateTime $dateOfBirth): void { $this->dateOfBirth = $dateOfBirth; }

    public function getRegistrationTime(): ?DateTime { return $this->registrationTime; }
    public function setRegistrationTime(?DateTime $registrationTime): void { $this->registrationTime = $registrationTime; }

    public function getContributionPerPeriodInCents(): int { return $this->contributionPerPeriodInCents; }
    public function setContributionPerPeriodInCents(int $contributionPerPeriodInCents): void { $this->contributionPerPeriodInCents = $contributionPerPeriodInCents; }

    public function getContributionPerPeriodInEuros(): float { return $this->contributionPerPeriodInCents / 100; }
    public function setContributionPerPeriodInEuros(float $contributionPerPeriodInEuros): void { $this->contributionPerPeriodInCents = round($contributionPerPeriodInEuros * 100); }

    public function getMollieCustomerId(): ?string { return $this->mollieCustomerId; }
    public function setMollieCustomerId(?string $mollieCustomerId): void { $this->mollieCustomerId = $mollieCustomerId; }

    public function getPaid(): bool { return $this->paid; }
    public function setPaid(bool $paid): void { $this->paid = $paid; }

    public function getPreferredDivision(): ?Division { return $this->preferredDivision; }
    public function setPreferredDivision(?Division $preferredDivision): void { $this->preferredDivision = $preferredDivision; }

    public function getPreferredWorkGroups(): ?Collection { return $this->preferredWorkGroups; }
    public function setPreferredWorkGroups(?Collection $preferredWorkGroups): void { $this->preferredWorkGroups = $preferredWorkGroups; }

    public function getContributionPeriod(): int { return $this->contributionPeriod; }
    public function setContributionPeriod(int $contributionPeriod): void {
        if (!in_array($contributionPeriod, [Member::PERIOD_MONTHLY, Member::PERIOD_QUARTERLY, Member::PERIOD_ANNUALLY]))
            throw new \Exception('Period must be PERIOD_MONTHLY, PERIOD_QUARTERLY or PERIOD_ANNUALLY');
        $this->contributionPeriod = $contributionPeriod;
    }

}
