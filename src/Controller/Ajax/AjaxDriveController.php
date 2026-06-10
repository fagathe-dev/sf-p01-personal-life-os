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

    #[Route(path: '/file/upload', name: 'file_upload', methods: ['POST'])]
    public function uploadFile(Request $request): JsonResponse
    {
        // 1. Appel du service d'upload
        $result = $this->driveService->uploadFile($request);

        // 2. Si le service a échoué et retourné un objet d'erreur standard (ResponseTrait)
        if (!($result instanceof DriveDocument)) {
            return $this->json($result->data, $result->status, $result->headers);
        }

        // 3. Succès : On génère le HTML du composant _file avec le nouveau fichier
        $html = $this->renderView('app/drive/components/_file.html.twig', [
            'file' => $result
        ]);

        return $this->json([
            'success' => true,
            'html' => $html, // Le HTML compilé prêt à rejoindre le DOM
            'message' => 'Fichier importé avec succès.'
        ], 201);
    }

    /**
     * Dispatcher unifié pour toutes les actions rapides associées aux fichiers.
     * 👈 Le chemin correspond exactement à ROUTES.DRIVE.FILE.ACTION : /file/edit/{id}/{action}
     */
    #[Route(path: '/file/edit/{id}/{action}', name: 'file_action', methods: ['POST'], requirements: ['id' => '\d+', 'action' => 'rename|move|trash|archive|pin|tag|download'])]
    public function editFileActions(Request $request, #[MapEntity(mapping: ['id' => 'id'])] DriveDocument $file, string $action): JsonResponse
    {
        $result = $this->driveService->fileQuickActions($file, $action, $request);

        return $this->json($result->data, $result->status, $result->headers);
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