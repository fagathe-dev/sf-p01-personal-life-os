<?php

namespace App\Service;

use App\Entity\DriveDocument; //
use App\Entity\Note;
use App\Enum\ContentStateEnum; //
use App\Repository\DriveDocumentRepository; //
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security; //
use Symfony\Component\Filesystem\Filesystem; //

final class ArchivesTrashService
{
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly DriveDocumentRepository $documentRepository, //
        private readonly NoteRepository $noteRepository,
        private readonly Security $security, //
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir //
    ) {
        $this->filesystem = new Filesystem(); //
    }

    public function getArchiveContent(): array
    {
        $user = $this->security->getUser();
        if (!$user) return ['files' => [], 'notes' => []];

        return [
            'files' => $this->documentRepository->findBy(['owner' => $user, 'state' => ContentStateEnum::Archive], ['created_at' => 'DESC']),
            'notes' => $this->noteRepository->findBy(['owner' => $user, 'state' => ContentStateEnum::Archive], ['created_at' => 'DESC']),
        ];
    }

    public function getTrashContent(): array
    {
        $user = $this->security->getUser();
        if (!$user) return ['files' => [], 'notes' => []];

        return [
            'files' => $this->documentRepository->findBy(['owner' => $user, 'state' => ContentStateEnum::Trash], ['created_at' => 'DESC']),
            'notes' => $this->noteRepository->findBy(['owner' => $user, 'state' => ContentStateEnum::Trash], ['created_at' => 'DESC']),
        ];
    }

    public function restoreFile(DriveDocument $file): void
    {
        $file->setState(ContentStateEnum::Open); //
        $file->setFolder(null); // Conforme à tes specs : mise à plat à la racine
        $file->setDeletedAt(null); // Réinitialisation de la date de suppression
        $this->documentRepository->save($file, true);
    }

    public function restoreNote(Note $note): void
    {
        $note->setState(ContentStateEnum::Open);
        $note->setDeletedAt(null); // Réinitialisation de la date de suppression
        $this->entityManager->flush();
    }

    public function purgeFile(DriveDocument $file): void
    {
        // 1. Suppression physique du fichier sur ton espace serveur / PC
        if ($file->getFilePath()) {
            $absolutePath = $this->projectDir . '/public/' . $file->getFilePath();
            if ($this->filesystem->exists($absolutePath)) {
                $this->filesystem->remove($absolutePath);
            }
        }

        // 2. Suppression définitive de la ligne SQL
        $this->entityManager->remove($file);
        $this->entityManager->flush();
    }

    public function purgeNote(Note $note): void
    {
        $this->entityManager->remove($note);
        $this->entityManager->flush();
    }

    public function restoreAll(string $context): void
    {
        $user = $this->security->getUser();
        if (!$user) return;

        $targetState = $context === 'archive' ? ContentStateEnum::Archive : ContentStateEnum::Trash;

        $files = $this->documentRepository->findBy(['owner' => $user, 'state' => $targetState]);
        foreach ($files as $file) {
            $file->setState(ContentStateEnum::Open); //
            $file->setFolder(null); // Retour racine
        }

        $notes = $this->noteRepository->findBy(['owner' => $user, 'state' => $targetState]);
        foreach ($notes as $note) {
            $note->setState(ContentStateEnum::Open);
        }

        $this->entityManager->flush();
    }

    public function emptyTrash(): void
    {
        $user = $this->security->getUser();
        if (!$user) return;

        // Purge en masse des fichiers de la corbeille
        $files = $this->documentRepository->findBy(['owner' => $user, 'state' => ContentStateEnum::Trash]);
        foreach ($files as $file) {
            if ($file->getFilePath()) {
                $absolutePath = $this->projectDir . '/public/' . $file->getFilePath();
                if ($this->filesystem->exists($absolutePath)) {
                    $this->filesystem->remove($absolutePath);
                }
            }
            $this->entityManager->remove($file);
        }

        // Purge en masse des notes de la corbeille
        $notes = $this->noteRepository->findBy(['owner' => $user, 'state' => ContentStateEnum::Trash]);
        foreach ($notes as $note) {
            $this->entityManager->remove($note);
        }

        // Un seul flush global pour optimiser les requêtes SQL
        $this->entityManager->flush();
    }
}