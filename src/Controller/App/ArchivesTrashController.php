<?php

namespace App\Controller\App;

use App\Service\ArchivesTrashService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/app')]
#[IsGranted('ROLE_USER')]
final class ArchivesTrashController extends AbstractController
{
    public function __construct(
        private readonly ArchivesTrashService $archiveTrashService
    ) {
    }

    /**
     * Affiche la liste plate et cloisonnée des fichiers et notes archivés.
     */
    #[Route(path: '/archives', name: 'app_archive_index', methods: ['GET'])]
    public function archiveIndex(): Response
    {
        $content = $this->archiveTrashService->getArchiveContent();

        return $this->render('app/archive/index.html.twig', [
            'files' => $content['files'],
            'notes' => $content['notes'],
        ]);
    }

    /**
     * Affiche la liste plate et cloisonnée des éléments envoyés à la corbeille.
     */
    #[Route(path: '/corbeille', name: 'app_trash_index', methods: ['GET'])]
    public function trashIndex(): Response
    {
        $content = $this->archiveTrashService->getTrashContent();

        return $this->render('app/trash/index.html.twig', [
            'files' => $content['files'],
            'notes' => $content['notes'],
        ]);
    }
}