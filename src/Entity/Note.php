<?php

namespace App\Entity;

use App\Enum\ContentStateEnum;
use App\Repository\NoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
class Note extends AbstractTextEntry
{
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 30, enumType: ContentStateEnum::class)]
    private ContentStateEnum|string|null $state = ContentStateEnum::Open;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'notes')]
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
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
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}