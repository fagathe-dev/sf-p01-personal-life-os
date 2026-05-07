<?php

namespace App\Entity;

use App\Repository\JournalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JournalRepository::class)]
class Journal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(length: 255)]
    private ?string $secret = null;

    #[ORM\OneToOne(inversedBy: 'journal', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, JournalEntry>
     */
    #[ORM\OneToMany(targetEntity: JournalEntry::class, mappedBy: 'journal', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): static
    {
        $this->secret = $secret;

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
     * @return Collection<int, JournalEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(JournalEntry $entry): static
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setJournal($this);
        }

        return $this;
    }

    public function removeEntry(JournalEntry $entry): static
    {
        if ($this->entries->removeElement($entry)) {
            if ($entry->getJournal() === $this) {
                $entry->setJournal(null);
            }
        }

        return $this;
    }
}
