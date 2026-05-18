<?php

namespace App\Entity;

use App\Enum\MoodEnum;
use App\Repository\JournalEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JournalEntryRepository::class)]
#[ORM\Index(name: 'idx_ft_note', columns: ['title', 'content'], flags: ['fulltext'])]
class JournalEntry extends AbstractTextEntry
{
    #[ORM\Column]
    private ?\DateTimeImmutable $entryDate = null;

    #[ORM\Column(length: 50, nullable: true, enumType: MoodEnum::class)]
    private MoodEnum|string|null $mood = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Journal $journal = null;

    #[ORM\ManyToOne]
    private ?Location $location = null;

    /**
     * @var Collection<int, JournalMedia>
     */
    #[ORM\OneToMany(targetEntity: JournalMedia::class, mappedBy: 'journalEntry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $medias;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $title = null;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
    }

    public function getEntryDate(): ?\DateTimeImmutable
    {
        return $this->entryDate;
    }

    public function setEntryDate(\DateTimeImmutable $entryDate): static
    {
        $this->entryDate = $entryDate;

        return $this;
    }

    public function getMood(): MoodEnum|string|null
    {
        return $this->mood;
    }

    public function setMood(MoodEnum|string|null $mood): static
    {
        if (is_string($mood)) {
            $mood = MoodEnum::tryFrom($mood);
        }

        $this->mood = $mood;

        return $this;
    }

    public function getJournal(): ?Journal
    {
        return $this->journal;
    }

    public function setJournal(?Journal $journal): static
    {
        $this->journal = $journal;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection<int, JournalMedia>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(JournalMedia $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setJournalEntry($this);
        }

        return $this;
    }

    public function removeMedia(JournalMedia $media): static
    {
        if ($this->medias->removeElement($media)) {
            if ($media->getJournalEntry() === $this) {
                $media->setJournalEntry(null);
            }
        }

        return $this;
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
}
