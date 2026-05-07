<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractTextEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $content = null;

    #[ORM\Column]
    protected ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    protected ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(nullable: true)]
    protected ?bool $is_pinned = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isPinned(): ?bool
    {
        return $this->is_pinned;
    }

    public function setIsPinned(?bool $is_pinned): static
    {
        $this->is_pinned = $is_pinned;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

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
}
