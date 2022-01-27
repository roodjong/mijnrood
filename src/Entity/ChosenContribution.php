<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class ChosenContribution
{
    private int $contributionAmount;

    private ?int $otherAmount;

    public function __construct()
    {
        $this->otherAmount = null;
    }

    public function getChosenAmount(): int
    {
        if ($this->contributionAmount === 0) {
            return $this->otherAmount;
        }
        return $this->contributionAmount;
    }

    public function getContributionAmount(): int
    {
        return $this->contributionAmount;
    }

    public function setContributionAmount(int $contributionAmount): void
    {
        $this->contributionAmount = $contributionAmount;
    }

    public function getOtherAmount(): int
    {
        return $this->otherAmount;
    }

    public function setOtherAmount(?float $otherAmount): void
    {
        if ($otherAmount === null) {
            return;
        }
        $this->otherAmount = round($otherAmount * 100);
    }

    /**
     * @Assert\IsFalse(message="Kies de optie voor hoger als je dit wil invullen")
     */
    public function hasDuplicateData(): bool
    {
        if ($this->otherAmount === null) {
            return false;
        }
        return $this->otherAmount !== 0 && $this->contributionAmount !== 0;
    }

    /**
     * @Assert\IsFalse(message="Bij 'hoger bedrag' moet dit meer dan €22,50 zijn")
     */
    public function hasTooLowOtherAmount(): bool
    {
        return $this->contributionAmount === 0 && $this->otherAmount <= 2250;
    }
}