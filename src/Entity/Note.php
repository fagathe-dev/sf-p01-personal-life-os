<?php

namespace App\Entity;

use App\Enum\ContentStateEnum;
use App\Enum\Note\NoteColorEnum;
use App\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Index(name: 'idx_ft_note', columns: ['title', 'content'], flags: ['fulltext'])]
class Note extends AbstractTextEntry
{
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 30, enumType: ContentStateEnum::class)]
    private ContentStateEnum|string|null $state = ContentStateEnum::Open;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Tag|null
     */
    #[ORM\ManyToOne(targetEntity: Tag::class, inversedBy: 'notes', cascade: ['persist', 'remove'])]
    private ?Tag $tag = null;

    #[ORM\Column(length: 30, enumType: NoteColorEnum::class)]
    private NoteColorEnum|string|null $color = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deleted_at = null;

    public function __construct()
    {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getState(): ContentStateEnum
    {
        return $this->state;
    }

    public function setState(ContentStateEnum|string $state): static
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

    /**
     * @return Tag|null
     */
    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    /**
     * @param Tag|null $tag
     * 
     * @return static
     */
    public function setTag(?Tag $tag = null): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getColor(): ?NoteColorEnum
    {
        return $this->color;
    }

    public function setColor(NoteColorEnum|string|null $color): static
    {
        if (is_string($color)) {
            $color = NoteColorEnum::tryFrom($color);
        }

        $this->color = $color;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?\DateTimeImmutable $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }
}