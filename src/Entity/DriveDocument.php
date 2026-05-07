<?php

namespace App\Entity;

use App\Enum\ContentStateEnum;
use App\Repository\DriveDocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DriveDocumentRepository::class)]
class DriveDocument extends AbstractFile
{
    #[ORM\Column(length: 30, nullable: true, enumType: ContentStateEnum::class, options: ['default' => ContentStateEnum::Open->value])]
    private ?ContentStateEnum $state = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    private ?Folder $folder = null;

    public function getState(): ?ContentStateEnum
    {
        return $this->state;
    }

    public function setState(ContentStateEnum|string|null $state): static
    {
        if (is_string($state)) {
            $state = ContentStateEnum::tryFrom($state);
        }

        $this->state = $state;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): static
    {
        $this->folder = $folder;

        return $this;
    }
}
