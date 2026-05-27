<?php

namespace App\Controller\App;

use App\Entity\DriveDocument;
use App\Entity\Folder;
use App\Service\DriveService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/app/drive', name: 'app_drive_')]
final class DriveController extends AbstractController
{
    public function __construct(private readonly DriveService $driveService)
    {
    }

    #[Route(path: '', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        // Géré automatiquement par le service (Racine)
        $context = $this->driveService->manageContent(null);

        return $this->render('app/drive/index.html.twig', $context);
    }

    #[Route(path: '/{id}', name: 'folder', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function folder(#[MapEntity(mapping: ['id' => 'id'])] Folder $folder): Response
    {
        if ($folder->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Géré automatiquement par le service (Dans un dossier)
        $context = $this->driveService->manageContent($folder);

        return $this->render('app/drive/index.html.twig', $context);
    }
}