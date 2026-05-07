<?php

namespace App\Entity;

use App\Repository\JournalMediaRepository;
use Doctrine\ORM\Mapping as ORM;



#[ORM\Entity(repositoryClass: JournalMediaRepository::class)]
class JournalMedia extends AbstractFile
{
    #[ORM\ManyToOne(inversedBy: 'medias')]
    #[ORM\JoinColumn(nullable: false)]
    private ?JournalEntry $journalEntry = null;

    public function getJournalEntry(): ?JournalEntry
    {
        return $this->journalEntry;
    }

    public function setJournalEntry(?JournalEntry $journalEntry): static
    {
        $this->journalEntry = $journalEntry;

        return $this;
    }
}
