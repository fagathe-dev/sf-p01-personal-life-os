<?php

namespace App\Entity;

use App\Repository\CapsuleMediaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CapsuleMediaRepository::class)]
class CapsuleMedia extends AbstractFile
{
    #[ORM\ManyToOne(inversedBy: 'medias')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Capsule $capsule = null;

    public function getCapsule(): ?Capsule
    {
        return $this->capsule;
    }

    public function setCapsule(?Capsule $capsule): static
    {
        $this->capsule = $capsule;

        return $this;
    }
}
