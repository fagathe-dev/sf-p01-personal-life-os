<?php

namespace App\Controller\Ajax;

use App\Entity\DriveDocument; 
use App\Entity\Note;
use App\Service\ArchivesTrashService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;

#[Route(path: '/ajax/archive-trash', name: 'ajax_archive_trash_')]
final class AjaxArchivesTrashController extends AbstractController
{
    public function __construct(
        private readonly ArchivesTrashService $archiveTrashService
    ) {
    }

    /**
     * Restaure un fichier individuel (le remet à la racine de l'espace actif).
     */
    #[Route(path: '/file/{id}/restore', name: 'file_restore', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function restoreFile(#[MapEntity(mapping: ['id' => 'id'])] DriveDocument $file): JsonResponse
    {
        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Accès interdit.'], Response::HTTP_FORBIDDEN);
        }

        $this->archiveTrashService->restoreFile($file);

        return $this->json(['success' => true, 'message' => 'Fichier restauré à la racine du Drive.']);
    }

    /**
     * Restaure une note individuelle.
     */
    #[Route(path: '/note/{id}/restore', name: 'note_restore', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function restoreNote(#[MapEntity(mapping: ['id' => 'id'])] Note $note): JsonResponse
    {
        if ($note->getOwner() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Accès interdit.'], Response::HTTP_FORBIDDEN);
        }

        $this->archiveTrashService->restoreNote($note);

        return $this->json(['success' => true, 'message' => 'Note restaurée avec succès.']);
    }

    /**
     * Détruit définitivement un fichier (BDD + Disque local).
     */
    #[Route(path: '/file/{id}/purge', name: 'file_purge', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function purgeFile(#[MapEntity(mapping: ['id' => 'id'])] DriveDocument $file): JsonResponse
    {
        if ($file->getOwner() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Accès interdit.'], Response::HTTP_FORBIDDEN);
        }

        $this->archiveTrashService->purgeFile($file);

        return $this->json(['success' => true, 'message' => 'Fichier détruit définitivement du disque.']);
    }

    /**
     * Détruit définitivement une note.
     */
    #[Route(path: '/note/{id}/purge', name: 'note_purge', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function purgeNote(#[MapEntity(mapping: ['id' => 'id'])] Note $note): JsonResponse
    {
        if ($note->getOwner() !== $this->getUser()) {
            return $this->json(['success' => false, 'message' => 'Accès interdit.'], Response::HTTP_FORBIDDEN);
        }

        $this->archiveTrashService->purgeNote($note);

        return $this->json(['success' => true, 'message' => 'Note supprimée de manière définitive.']);
    }

    /**
     * Action de masse : restaure l'intégralité du contexte ciblé (archives ou corbeille).
     */
    #[Route(path: '/{context}/restore-all', name: 'restore_all', methods: ['POST'], requirements: ['context' => 'archive|trash'])]
    public function restoreAll(string $context): JsonResponse
    {
        $this->archiveTrashService->restoreAll($context);

        return $this->json(['success' => true, 'message' => 'Tous les éléments ont été remis en espace actif.']);
    }

    /**
     * Vile la totalité de la corbeille de l'utilisateur connecté.
     */
    #[Route(path: '/empty-trash', name: 'empty_trash', methods: ['DELETE'])]
    public function emptyTrash(): JsonResponse
    {
        $this->archiveTrashService->emptyTrash();

        return $this->json(['success' => true, 'message' => 'La corbeille a été entièrement vidée.']);
    }
}