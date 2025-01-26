<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

/**
 * @ORM\Entity(repositoryClass=App\Repository\SupportMemberRepository::class)
 * @ORM\Table("admin_support_member")
 */
class SupportMember
{

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
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $registrationTime = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $mollieCustomerId = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $mollieSubscriptionId = null;

    /**
     * @ORM\Column(type="integer", options={"default": 2})
     */
    private int $contributionPeriod = self::PERIOD_ANNUALLY;

    /**
     * @ORM\Column(type="integer", options={"default": 500})
     */
    private int $contributionPerPeriodInCents;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $originalId;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $originalRegistrationTime = null;

    // /**
    //  * @ORM\OneToMany(targetEntity="SupportMemberDetailsRevision", mappedBy="member")
    //  */
    // private Collection $detailRevisions;

    public function __construct() {
        $this->registrationTime = new DateTime;
        $this->contributionPayments = new ArrayCollection;
        // $this->detailRevisions = new ArrayCollection;
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

    public function getRegistrationTime(): ?DateTime { return $this->registrationTime; }
    public function setRegistrationTime(?DateTime $registrationTime): void { $this->registrationTime = $registrationTime; }

    public function getContributionPerPeriodInCents(): int { return $this->contributionPerPeriodInCents; }
    public function setContributionPerPeriodInCents(int $contributionPerPeriodInCents): void { $this->contributionPerPeriodInCents = $contributionPerPeriodInCents; }

    public function getContributionPerPeriodInEuros(): float { return $this->contributionPerPeriodInCents / 100; }
    public function setContributionPerPeriodInEuros(float $contributionPerPeriodInEuros): void { $this->contributionPerPeriodInCents = round($contributionPerPeriodInEuros * 100); }

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

    public function getOriginalId(): ?int { return $this->originalId; }
    public function setOriginalId(int $originalId): void { $this->originalId = $originalId; }

    public function getOriginalRegistrationTime(): ?DateTime { return $this->originalRegistrationTime; }
    public function setOriginalRegistrationTime(?DateTime $originalRegistrationTime): void { $this->originalRegistrationTime = $originalRegistrationTime; }

    // public function getDetailRevisions(): Collection { return $this->detailRevisions; }
    // public function getLastDetailRevision(): ?SupportMemberDetailsRevision {
    //     return $this->detailRevisions->getIterator()->uasort(function(SupportMemberDetailsRevision $a, SupportMemberDetailsRevision $b) {
    //         return $b->getId() - $a->getId();
    //     })[0] ?? null;
    // }
}
