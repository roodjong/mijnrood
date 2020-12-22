<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as  ORM;
use Doctrine\Common\Collections\{ Collection, ArrayCollection };

use DateTime;

/**
 * @ORM\Entity
 * @ORM\Table(name="admin_document")
 */
class Document {

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
     * @ORM\ManyToOne(targetEntity="DocumentFolder", inversedBy="documents")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?DocumentFolder $folder = null;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $sizeInBytes = 0;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $uploadFileName = '';

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private DateTime $dateUploaded;

    /**
     * @ORM\ManyToOne(targetEntity="Member")
     */
    private ?Member $memberUploaded = null;

    public function __construct() {
        $this->dateUploaded = new DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getFileName(): string { return $this->name; }
    public function setFileName(string $name): void { $this->name = $name; }

    public function getUploadFileName(): string { return $this->uploadFileName; }
    public function setUploadFileName(string $uploadFileName): void { $this->uploadFileName = $uploadFileName; }

    public function getFolder(): ?DocumentFolder { return $this->folder; }
    public function setFolder(?DocumentFolder $folder): void { $this->folder = $folder; }

    public function getMemberUploaded(): ?Member { return $this->memberUploaded; }
    public function setMemberUploaded(?Member $memberUploaded): void { $this->memberUploaded = $memberUploaded; }

    public function getSizeInBytes(): int { return $this->sizeInBytes; }
    public function setSizeInBytes(int $sizeInBytes): void { $this->sizeInBytes = $sizeInBytes; }

    public function getDateUploaded(): DateTime { return $this->dateUploaded; }
    public function setDateUploaded(DateTime $dateUploaded): void { $this->dateUploaded = $dateUploaded; }

    public function getDocuments(): Collection { return $this->documents; }

}
