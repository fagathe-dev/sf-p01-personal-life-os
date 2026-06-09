<?php

namespace App\Controller\Ajax;

use App\Entity\DriveDocument;
use App\Entity\Folder;
use App\Service\DriveService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Route(path: '/ajax/drive', name: 'ajax_drive_')]
final class AjaxDriveController extends AbstractController
{
    public function __construct(
        private readonly DriveService $driveService
    ) {
    }

    #[Route(path: '/file/add', name: 'file_add', methods: ['POST'])]
    public function addFile(): JsonResponse
    {
        return new JsonResponse(['success' => true, 'message' => 'Hello from Drive AJAX!']);
    }

    #[Route(path: '/file/edit/{id}/{action}', name: 'file_edit', methods: ['POST'], requirements: ['id' => '\d+', 'action' => 'rename|move|trash|archive|pin'])]
    public function editFileActions(#[MapEntity(mapping: ['id' => 'id'])] DriveDocument $file, string $action): JsonResponse
    {
        return new JsonResponse(['success' => true, 'message' => "Editing file with ID {$file->getId()} and action $action"]);
    }

    #[Route(path: '/folder/add', name: 'folder_add', methods: ['POST'], requirements: ['action' => 'add|edit|trash'])]
    public function addFolder(Request $request): JsonResponse
    {
        // 1. On passe tout au service
        $result = $this->driveService->createFolder($request);

        // 2. Si le service retourne un objet généré par ResponseTrait (erreur ou violation)
        if (!($result instanceof Folder)) {
            // On convertit l'objet standard retourné par ResponseTrait en JsonResponse
            return $this->json($result->data, $result->status, $result->headers);
        }

        // 3. Succès : On génère le HTML avec le nouveau dossier
        $html = $this->renderView('app/drive/components/_folder.html.twig', [
            'folder' => $result
        ]);

        return $this->json([
            'success' => true,
            'html' => $html,
            'message' => 'Dossier créé'
        ], 201); // 201 Created
    }

    #[Route(path: '/folder/{id}/{action}', name: 'folder_action', methods: ['POST'], requirements: ['id' => '\d+', 'action' => 'rename|move'])]
    public function folderQuickActions(Request $request, #[MapEntity(mapping: ['id' => 'id'])] Folder $folder, string $action): JsonResponse
    {
        // 1. On délègue tout le routage interne et le traitement au DriveService
        $result = $this->driveService->folderQuickActions($folder, $action, $request);

        // 2. On transforme l'objet ResponseTrait en JsonResponse officielle
        return $this->json($result->data, $result->status, $result->headers);
    }

    #[Route(path: '/folder/{id}/delete', name: 'folder_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteFolder(#[MapEntity(mapping: ['id' => 'id'])] Folder $folder): JsonResponse
    {
        // 1. On appelle ton DriveService qui va vider le dossier et détruire sa structure
        if ($this->driveService->deleteFolder($folder)) {
            return $this->json([
                'success' => true,
                'message' => 'Dossier supprimé avec succès. Les fichiers ont été envoyés à la corbeille.'
            ]);
        }

        // 2. En cas d'échec technique (levé par le try/catch de ton service)
        return $this->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la suppression du dossier.'
        ], Response::HTTP_INTERNAL_SERVER_ERROR); // Erreur 500
    }
}