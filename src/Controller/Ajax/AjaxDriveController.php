<?php

namespace App\Controller\Ajax;

use App\Entity\DriveDocument;
use App\Entity\Folder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route(path: '/ajax/drive', name: 'ajax_drive_')]
final class AjaxDriveController extends AbstractController
{
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

    #[Route(path: '/folder', name: 'folder_add', methods: ['POST'], requirements: ['action' => 'add|edit|trash'])]
    public function addFolder(string $action): JsonResponse
    {
        return new JsonResponse(['success' => true, 'message' => "Adding folder with action $action"]);
    }

    #[Route(path: '/folder/edit/{id}/{action}', name: 'folder_edit', methods: ['POST'], requirements: ['id' => '\d+', 'action' => 'rename|move'])]
    public function editFolderActions(#[MapEntity(mapping: ['id' => 'id'])] Folder $folder, string $action): JsonResponse
    {
        return new JsonResponse(['success' => true, 'message' => "Editing folder with ID $id and action $action"]);
    }

    #[Route(path: '/folder/delete/{id}', name: 'folder_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteFolder(int $id): JsonResponse
    {
        return new JsonResponse(['success' => true, 'message' => "Deleting folder with ID $id"]);
    }
}