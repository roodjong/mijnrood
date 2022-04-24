<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table("admin_workgroup")
 */
class WorkGroup {

    /**
     * @ORM\Column(type="integer", options={ "unsigned": false })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $name = '';

    /** @ORM\ManyToOne(targetEntity="Member")
     *  @ORM\JoinColumn(nullable=true)
     */
    private ?Member $contact = null;

    /**
     * @ORM\ManyToMany(targetEntity="Member", mappedBy="workGroups")
     */
    private Collection $members;

    /**
     * @ORM\ManyToOne(targetEntity="Email")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Email $email = null;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private bool $canBeSelectedOnApplication = false;

    public function __construct() {
        $this->members = new ArrayCollection();
    }

    public function __toString() {
        return $this->name;
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getCanBeSelectedOnApplication(): bool { return $this->canBeSelectedOnApplication; }
    public function setCanBeSelectedOnApplication(bool $canBeSelectedOnApplication): void { $this->canBeSelectedOnApplication = $canBeSelectedOnApplication; }

    public function getMembers(): Collection { return $this->members; }

    public function addMember(Member $member): self {
        if (!$this->members->contains($member)) {
            $this->members[] = $member;
        }
        return $this;
    }
    public function removeMember(Member $member): self {
        if ($this->members->contains($member)) {
            $this->members->removeElement($member);
        }
        return $this;
    }

    public function getContact(): ?Member { return $this->contact; }
    public function setContact(?Member $contact): void { $this->contact = $contact; }

    public function getEmail(): ?Email { return $this->email; }
    public function setEmail(?Email $email): void { $this->email = $email; }
}
