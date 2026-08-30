<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $is_completed = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completed_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    // 1. On rebascule sur datetime_immutable pour la base de données
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $due_date = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: Tag::class, inversedBy: 'tasks')]
    private ?Tag $tag = null;

    #[ORM\ManyToOne(inversedBy: 'task')]
    private ?SubTask $subTask = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
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
    public function isCompleted(): bool
    {
        return $this->is_completed;
    }
    public function setIsCompleted(bool $is_completed): static
    {
        $this->is_completed = $is_completed;
        return $this;
    }
    public function toggleCompleted(): static
    {
        $this->is_completed = !$this->is_completed;
        return $this;
    }
    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completed_at;
    }
    public function setCompletedAt(?\DateTimeImmutable $completed_at): static
    {
        $this->completed_at = $completed_at;
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

    // 2. Les Getters/Setters natifs manipulent uniquement du DateTimeImmutable
    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->due_date;
    }

    public function setDueDate(?\DateTimeImmutable $due_date): static
    {
        $this->due_date = $due_date;
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
    public function getTag(): ?Tag
    {
        return $this->tag;
    }
    public function setTag(?Tag $tag): static
    {
        $this->tag = $tag;
        return $this;
    }

    public function getSubTask(): ?SubTask
    {
        return $this->subTask;
    }

    public function setSubTask(?SubTask $subTask): static
    {
        $this->subTask = $subTask;

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
}