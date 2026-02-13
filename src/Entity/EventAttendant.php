<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ Collection, ArrayCollection };
use DateTime;

/**
 * @ORM\Entity
 * @ORM\Table("admin_event_attendant") 
 */
class EventAttendant {
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="attendants")
     * @ORM\JoinColumn()
     */
    private Event $event;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Member", inversedBy="eventsAttended")
     * @ORM\JoinColumn()
     */
    private Member $member;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $reserved = null;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private bool $checkedIn = false;

    public function __construct() {

    }
    // Getter and Setter for Event
    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    // Getter and Setter for Member
    public function getMember(): Member
    {
        return $this->member;
    }

    public function setMember(Member $member): self
    {
        $this->member = $member;
        return $this;
    }

    // Getter and Setter for Reserved
    public function getReserved(): ?DateTime
    {
        return $this->reserved;
    }

    public function setReserved(?DateTime $reserved): self
    {
        $this->reserved = $reserved;
        return $this;
    }

    // Getter and Setter for CheckedIn
    public function isCheckedIn(): bool
    {
        return $this->checkedIn;
    }

    public function setCheckedIn(bool $checkedIn): self
    {
        $this->checkedIn = $checkedIn;
        return $this;
    }
}