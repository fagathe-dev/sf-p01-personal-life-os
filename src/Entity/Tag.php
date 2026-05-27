<?php

namespace App\Entity;

use App\Enum\TagColorEnum;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, nullable: true, enumType: TagColorEnum::class)]
    private TagColorEnum|string|null $color = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\ManyToOne(inversedBy: 'tags')]
    private ?User $owner = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'tag')]
    private Collection $notes;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'tag')]
    private Collection $tasks;

    /**
     * @var Collection<int, DriveDocument>
     */
    #[ORM\OneToMany(targetEntity: DriveDocument::class, mappedBy: 'tag')]
    private Collection $driveDocuments;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->driveDocuments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): ?TagColorEnum
    {
        return $this->color;
    }

    public function setColor(TagColorEnum|string|null $color): static
    {
        if (is_string($color)) {
            $color = TagColorEnum::tryFrom($color);
        }

        $this->color = $color;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

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

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    /**
     * @return Collection<int, DriveDocument>
     */
    public function getDriveDocuments(): Collection
    {
        return $this->driveDocuments;
    }

    public function addDriveDocument(DriveDocument $driveDocument): static
    {
        if (!$this->driveDocuments->contains($driveDocument)) {
            $this->driveDocuments->add($driveDocument);
            $driveDocument->setTag($this);
        }

        return $this;
    }

    public function removeDriveDocument(DriveDocument $driveDocument): static
    {
        if ($this->driveDocuments->removeElement($driveDocument)) {
            // set the owning side to null (unless already changed)
            if ($driveDocument->getTag() === $this) {
                $driveDocument->setTag(null);
            }
        }

        return $this;
    }
}
