<?php

namespace App\Entity;

use App\Repository\CapsuleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CapsuleRepository::class)]
class Capsule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $unlockAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isOpened = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secret = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, CapsuleMedia>
     */
    #[ORM\OneToMany(targetEntity: CapsuleMedia::class, mappedBy: 'capsule', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $medias;

    public function __construct()
    {
        $this->medias = new ArrayCollection();
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

    public function getUnlockAt(): ?\DateTimeImmutable
    {
        return $this->unlockAt;
    }

    public function setUnlockAt(\DateTimeImmutable $unlockAt): static
    {
        $this->unlockAt = $unlockAt;

        return $this;
    }

    public function isOpened(): bool
    {
        return $this->isOpened;
    }

    public function setIsOpened(bool $isOpened): static
    {
        $this->isOpened = $isOpened;

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
     * @return Collection<int, CapsuleMedia>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(CapsuleMedia $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setCapsule($this);
        }

        return $this;
    }

    public function removeMedia(CapsuleMedia $media): static
    {
        if ($this->medias->removeElement($media)) {
            if ($media->getCapsule() === $this) {
                $media->setCapsule(null);
            }
        }

        return $this;
    }
}
