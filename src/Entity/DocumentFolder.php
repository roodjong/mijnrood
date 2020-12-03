<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as  ORM;
use Doctrine\Common\Collections\{ Collection, ArrayCollection };

/**
 * @ORM\Entity
 * @ORM\Table(name="admin_document_folder")
 */
class DocumentFolder {

    /**
     * @ORM\Column(type="integer", options={ "unsigned": false })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $name = '';

    /**
     * @ORM\ManyToOne(targetEntity="DocumentFolder", inversedBy="folders")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?DocumentFolder $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="DocumentFolder", mappedBy="parent")
     */
    private Collection $folders;

    /**
     * @ORM\OneToMany(targetEntity="Document", mappedBy="folder")
     */
    private Collection $documents;

    /**
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private ?Member $memberCreated = null;

    public function __construct() {
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getParent(): ?DocumentFolder { return $this->parent; }
    public function setParent(?DocumentFolder $parent): void { $this->parent = $parent; }

    public function getDocuments(): Collection { return $this->documents; }

    public function getMemberCreated(): ?Member { return $this->memebrCreated; }
    public function setMemberCreated(?Member $memebrCreated): void { $this->memebrCreated = $memebrCreated; }

}
