<?php

namespace App\Service;

use App\Entity\DriveDocument;
use App\Entity\Folder;
use App\Entity\User;
use App\Enum\ContentStateEnum;
use App\Repository\DriveDocumentRepository;
use App\Repository\FolderRepository;
use Fagathe\CorePhp\Breadcrumb\Breadcrumb;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

/**
 * Service central du module Drive.
 * Gère le requêtage, la structuration des vues et la persistance des fichiers et dossiers.
 */
final class DriveService
{
    use LoggerTrait, DatetimeTrait;

    public function __construct(
        private readonly DriveDocumentRepository $documentRepository,
        private readonly FolderRepository $folderRepository,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $environment,
    ) {
    }

    /**
     * Récupère l'utilisateur connecté de manière sécurisée et typée.
     */
    public function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Prépare et rassemble toutes les données nécessaires pour l'affichage du Drive (Racine ou Dossier).
     * * @param Folder|null $folder Le dossier courant (null si on est à la racine)
     * @return array<string, mixed> Les données formatées pour la vue Twig
     */
    public function manageContent(?Folder $folder = null): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [
                'folders' => [],
                'files' => [],
                'currentFolder' => null,
                'breadcrumb' => new Breadcrumb([]),
            ];
        }

        // 1. Récupération des sous-dossiers
        $folders = $this->folderRepository->findBy(
            ['owner' => $user, 'parent' => $folder],
            ['name' => 'ASC']
        );

        // 2. Récupération des fichiers actifs (uniquement à l'état Open)
        $files = $this->documentRepository->findBy(
            ['owner' => $user, 'folder' => $folder, 'state' => ContentStateEnum::Open],
            ['updated_at' => 'DESC', 'created_at' => 'DESC']
        );

        if ($this->environment === 'dev') {
            $folders = [...$folders, ...$this->generateFoldersMock()];
            $files = [...$files, ...$this->generateFilesMock()];
        }

        return [
            'folders' => $folders,
            'files' => $files,
            'userTags' => $this->getCurrentUser()->getTags(),
            'currentFolder' => $folder,
            'breadcrumb' => $this->getFolderBreadcrumb($folder),
        ];
    }

    /**
     * @return Folder[]
     */
    private function generateFoldersMock(): array
    {
        return [
            (new Folder())
                ->setName('Développement B.A.S.E')
                ->setId(991)
                ->setCreatedAt(new \DateTimeImmutable('-10 days'))
                ->setUpdatedAt(new \DateTimeImmutable('-1 day')),
            (new Folder())
                ->setName('Projets Immobiliers')
                ->setId(992)
                ->setCreatedAt(new \DateTimeImmutable('-5 days'))
                ->setUpdatedAt(new \DateTimeImmutable('-2 days')),
        ];
    }

    /**
     * @return DriveDocument[]
     */
    private function generateFilesMock(): array
    {
        $file1 = new DriveDocument();
        $file1->setOriginalName('roadmap_2026.pdf')->setNiceName('Roadmap Data Developer 2026')
            ->setExtension('pdf')->setMimeType('application/pdf')->setSize(2540000)
            ->setIsPinned(true)->setUpdatedAt(new \DateTimeImmutable('-2 hours'));
        // On force un ID fictif via la réflexion (car setId n'existe pas) pour que les data-id HTML fonctionnent
        $this->forceEntityId($file1, 101);

        $file2 = new DriveDocument();
        $file2->setOriginalName('maquette_ui.png')->setNiceName('Maquette UI Base')
            ->setExtension('png')->setMimeType('image/png')->setSize(850000)
            ->setUpdatedAt(new \DateTimeImmutable('-1 day'));
        $this->forceEntityId($file2, 102);

        return [$file1, $file2];
    }

    /**
     * Hack utile uniquement pour les mocks : injecte un ID dans une entité sans setter.
     */
    private function forceEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    /**
     * Sauvegarde un Dossier (Création ou Édition).
     */
    public function saveFolder(Folder $folder, bool $isCreation = false): bool
    {
        try {
            if ($isCreation && $this->getCurrentUser()) {
                $folder->setOwner($this->getCurrentUser());
            }

            $this->folderRepository->save($folder, true);

            $this->generateLog(
                LoggerLevelEnum::Info,
                [
                    'message' => 'Dossier sauvegardé avec succès',
                    'folder_id' => $folder->getId(),
                    'folder_name' => $folder->getName()
                ],
                ['action' => $isCreation ? 'drive.folder.create.success' : 'drive.folder.update.success']
            );

            return true;
        } catch (Throwable $th) {
            $this->generateLog(
                LoggerLevelEnum::Error,
                [
                    'message' => 'Erreur lors de la sauvegarde du dossier',
                    'error' => $th->getMessage()
                ],
                ['action' => 'drive.folder.save.error']
            );
            return false;
        }
    }

    /**
     * Sauvegarde un Fichier / Document (Création ou Édition).
     */
    public function saveFile(DriveDocument $file, bool $isCreation = false): bool
    {
        try {
            if ($isCreation && $this->getCurrentUser()) {
                $file->setOwner($this->getCurrentUser());
                $file->setCreatedAt($this->now());
            }

            $file->setUpdatedAt($this->now());
            $this->documentRepository->save($file, true);

            $this->generateLog(
                LoggerLevelEnum::Info,
                [
                    'message' => 'Document sauvegardé avec succès',
                    'file_id' => $file->getId(),
                    'file_name' => $file->getOriginalName()
                ],
                ['action' => $isCreation ? 'drive.file.create.success' : 'drive.file.update.success']
            );

            return true;
        } catch (Throwable $th) {
            $this->generateLog(
                LoggerLevelEnum::Error,
                [
                    'message' => 'Erreur lors de la sauvegarde du fichier',
                    'error' => $th->getMessage()
                ],
                ['action' => 'drive.file.save.error']
            );
            return false;
        }
    }

    /**
     * Génère récursivement le fil d'Ariane en remontant l'arborescence des dossiers.
     */
    public function getFolderBreadcrumb(?Folder $currentFolder = null): Breadcrumb
    {
        $items = [];
        $folder = $currentFolder;

        // On remonte la chaîne des parents
        while ($folder !== null) {
            array_unshift($items, new BreadcrumbItem(
                name: $folder->getName(),
                link: $this->urlGenerator->generate('app_drive_folder', ['id' => $folder->getId()])
            ));
            $folder = $folder->getParent();
        }

        // On ancre la racine "Mon Drive" au tout début
        array_unshift($items, new BreadcrumbItem(
            name: 'Mon Drive',
            link: $this->urlGenerator->generate('app_drive_index')
        ));

        return new Breadcrumb($items);
    }
}