<?php
namespace App\Entity\Membership;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Member;

/**
 * @ORM\Entity
 * @ORM\Table("admin_membershipstatus")
 */
class MembershipStatus {

    /**
     * @ORM\Column(type="integer", options={ "unsigned": false })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private string $name = '';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Member", mappedBy="currentMembershipStatus")
     */
    private Collection $members;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default": false})
     */
    private bool $allowedAccess = false;

    public function __toString() {
        return $this->name;
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getAllowedAccess(): bool { return $this->allowedAccess; }
    public function setAllowedAccess(bool $allowedAccess) { $this->allowedAccess = $allowedAccess; }

    public function getMembers(): Collection { return $this->members; }
}
