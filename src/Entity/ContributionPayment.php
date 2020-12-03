<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use DateTime;
use RangeException;

/**
 * @ORM\Entity
 * @ORM\Table("admin_contribution_payment")
 */
class ContributionPayment
{
    public const STATUS_PENDING = 0;
    public const STATUS_PAID = 1;
    public const STATUS_FAILED = 2;
    public const STATUS_REFUNDED = 3;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Member", inversedBy="contributionPayments")
     */
    private Member $member;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $amountInCents = 0;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private DateTime $paymentTime;

    /**
     * @ORM\Column(type="boolean")
     */
    private int $status = 0;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $molliePaymentId = null;

    public function __construct() {
        $this->time = new DateTime();
    }

    public function getAmountInCents(): int { return $this->amountInCents; }
    public function setAmountInCents(int $amountInCents): void { $this->amountInCents = $amountInCents; }

    public function getAmountInEuros(): float { return $this->amountInCents / 100; }
    public function setAmountInEuros(float $amountInEuros): void { $this->amountInCents = $amountInEuros * 100; }

    public function getPaymentTime(): DateTime { return $this->paymentTime; }
    public function setPaymentTime(DateTime $paymentTime): void { $this->paymentTime = $paymentTime; }

    public function getMember(): Member { return $this->member; }
    public function setMember(Member $member): void { $this->member = $member; }

    public function getStatus(): int { return $this->status; }
    public function setStatus(int $status): void {
        if ($status < 0 || $status > 3)
            throw new RangeException('$status must be STATUS_PENDING, STATUS_PAID, STATUS_FAILED or STATUS_REFUNDED');

        $this->status = $status;
    }

    public function getMolliePaymentId(): ?string { return $this->molliePaymentId; }
    public function setMolliePaymentId(?string $molliePaymentId): void { $this->molliePaymentId = $molliePaymentId; }

}
