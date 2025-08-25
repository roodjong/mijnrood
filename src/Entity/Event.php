<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ Collection, ArrayCollection };
use DateTime;

/**
 * @ORM\Entity
 * @ORM\Table("admin_event")
 */
class Event {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", options={"unsigned": true})
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private string $name = '';

    /**
     * @ORM\Column(type="string", length=2000)
     */
    private string $description = '';

    /**
     * @ORM\ManyToOne(targetEntity="Division", inversedBy="events")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Division $division = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $timeStart;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $timeEnd;

    /**
     * @ORM\OneToMany(targetEntity="EventAttendant", mappedBy="event")
     */
    private Collection $attendants;

    public function __construct() {

    }

    public function getId(): ?int { return $this->id; }

    public function getDivision(): ?Division { return $this->division; }
    public function setDivision(?Division $division): void { $this->division = $division; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): void { $this->description = $description; }

    public function getTimeStart(): DateTime { return $this->timeStart; }
    public function setTimeStart(DateTime $timeStart): void { $this->timeStart = $timeStart; }

    public function getTimeEnd(): DateTime { return $this->timeEnd; }
    public function setTimeEnd(DateTime $timeEnd): void { $this->timeEnd = $timeEnd; }

    public function startsAndEndsOnSameDay(): bool {
        return $this->timeStart->format('Ymd') == $this->timeEnd->format('Ymd');
    }

    public function getReservedAttendants() {
        $items = [];
        foreach ($this->attendants as $attendant) {
            if ($attendant->getReserved() != null)
            {
                $items[] = $attendant;
            }    
        }
        return $items;
    }

    public function getCheckedInWithReservation() {
        $items = [];
        foreach ($this->attendants as $attendant) {
            if ($attendant->getReserved() != null && $attendant->isCheckedIn())
            {
                $items[] = $attendant;
            }    
        }
        return $items;
    }

    public function getCheckedInWithoutReservation() {
        $items = [];
        foreach ($this->attendants as $attendant) {
            if ($attendant->getReserved() == null && $attendant->isCheckedIn())
            {
                $items[] = $attendant;
            }    
        }
        return $items;
    }
}
