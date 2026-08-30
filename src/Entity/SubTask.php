<?php

namespace App\Entity;

use App\Repository\SubTaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubTaskRepository::class)]
class SubTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_completed = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completed_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'subTask')]
    private Collection $task;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'subTask')]
    private Collection $owner;

    public function __construct()
    {
        $this->task = new ArrayCollection();
        $this->owner = new ArrayCollection();
    }

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

    public function isCompleted(): ?bool
    {
        return $this->is_completed;
    }

    public function setIsCompleted(?bool $is_completed): static
    {
        $this->is_completed = $is_completed;

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

    /**
     * @return Collection<int, Task>
     */
    public function getTask(): Collection
    {
        return $this->task;
    }

    public function addTask(Task $task): static
    {
        if (!$this->task->contains($task)) {
            $this->task->add($task);
            $task->setSubTask($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->task->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getSubTask() === $this) {
                $task->setSubTask(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getOwner(): Collection
    {
        return $this->owner;
    }

    public function addOwner(User $owner): static
    {
        if (!$this->owner->contains($owner)) {
            $this->owner->add($owner);
            $owner->setSubTask($this);
        }

        return $this;
    }

    public function removeOwner(User $owner): static
    {
        if ($this->owner->removeElement($owner)) {
            // set the owning side to null (unless already changed)
            if ($owner->getSubTask() === $this) {
                $owner->setSubTask(null);
            }
        }

        return $this;
    }
}
