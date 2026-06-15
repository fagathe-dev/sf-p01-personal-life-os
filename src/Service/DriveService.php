<?php

namespace App\Service;

use App\Entity\DriveDocument;
use App\Entity\Folder;
use App\Entity\Tag;
use App\Entity\User;
use App\Enum\ContentStateEnum;
use App\Repository\DriveDocumentRepository;
use App\Repository\FolderRepository;
use Fagathe\CorePhp\Breadcrumb\Breadcrumb;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Http\ResponseTrait;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Fagathe\CorePhp\Trait\LoggerTrait;
use stdClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

/**
 * Service central du module Drive.
 * Gère le requêtage, la structuration des vues et la persistance des fichiers et dossiers.
 */
final class DriveService
{
    use LoggerTrait, DatetimeTrait, ResponseTrait;

    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly DriveDocumentRepository $documentRepository,
        private readonly FolderRepository $folderRepository,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $environment,
        private readonly SerializerInterface $serializer, // 👈 Injection
        private readonly ValidatorInterface $validator,    // 👈 Injection
        private readonly string $projectDir,             // 👈 Injection du chemin racine du projet pour la gestion des fichiers
    ) {
        $this->filesystem = new Filesystem();
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
     * @param Folder|null $folder Le dossier courant (null si on est à la racine)
     * @return array<string, mixed> Les données formatées pour la vue Twig
     */
    public function manageContent(?Folder $folder = null, ?Request $request = null): array
    {
        $mock = $request ? $request->query->getBoolean('mock', false) : false;
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
            ['updated_at' => 'DESC', 'created_at' => 'DESC', 'deleted_at' => null],
        );

        if ($this->environment === 'dev' && !is_null($request) && $mock === true) {
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

        /**
         * @var ?Tag $tag
         */
        $tag = random_picker($this->getCurrentUser()->getTags()->toArray());

        $file2 = new DriveDocument();
        $file2->setOriginalName('maquette_ui.png')->setNiceName('Maquette UI Base')
            ->setExtension('png')->setMimeType('image/png')->setSize(850000)
            ->setUpdatedAt(new \DateTimeImmutable('-1 day'));
        $file3 = new DriveDocument();
        $file3->setOriginalName('presentation.pptx')->setNiceName('Présentation commerciale')
            ->setExtension('pptx')->setMimeType('application/vnd.openxmlformats-officedocument.presentationml.presentation')->setSize(3200000)
            ->setUpdatedAt(new \DateTimeImmutable('-3 days'))
            ->setIsPinned(true)
            ->setTag($tag);
        ;

        $this->forceEntityId($file2, 102);

        return [$file1, $file2, $file3];
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

    /**
     * Supprime la structure d'un dossier (et ses sous-dossiers) en BDD, 
     * et envoie l'intégralité de ses fichiers "à plat" dans la corbeille.
     */
    public function deleteFolder(Folder $folder): bool
    {
        try {
            $folderId = $folder->getId();

            // 1. Collecter récursivement tous les fichiers enfants (tous niveaux confondus)
            /** @var DriveDocument[] $filesToTrash */
            $filesToTrash = [];
            $this->collectFilesRecursive($folder, $filesToTrash);

            // 2. Mettre à plat et envoyer chaque fichier dans la corbeille
            foreach ($filesToTrash as $file) {
                $file->setFolder(null); // 👈 Déconnexion du dossier (Mise à plat)
                $file->setState(ContentStateEnum::Trash); // 👈 Passage à l'état Corbeille

                // On prépare la sauvegarde sans forcer le flush immédiatement pour optimiser les performances
                $this->documentRepository->save($file, false);
            }

            // 3. Suppression de la structure du dossier en BDD
            // Le "true" va exécuter le flush global : les fichiers seront mis à jour et le dossier détruit en une seule transaction SQL.
            $this->folderRepository->remove($folder, true);

            $this->generateLog(
                LoggerLevelEnum::Info,
                [
                    'message' => 'Structure du dossier détruite. Fichiers envoyés à plat dans la corbeille.',
                    'folder_id' => $folderId,
                    'files_count' => count($filesToTrash)
                ],
                ['action' => 'drive.folder.trash_content.success']
            );

            return true;
        } catch (Throwable $th) {
            $this->generateLog(
                LoggerLevelEnum::Error,
                [
                    'message' => 'Erreur lors de la mise à la corbeille du contenu du dossier',
                    'error' => $th->getMessage()
                ],
                ['action' => 'drive.folder.trash_content.error']
            );
            return false;
        }
    }

    /**
     * Méthode utilitaire privée pour collecter récursivement tous les fichiers
     */
    private function collectFilesRecursive(Folder $folder, array &$files): void
    {
        // On récupère les fichiers du dossier courant
        foreach ($folder->getDocuments() as $document) {
            $files[] = $document;
        }

        // On descend récursivement dans chaque sous-dossier enfant
        foreach ($folder->getChildren() as $childFolder) {
            $this->collectFilesRecursive($childFolder, $files);
        }
    }

    /**
     * Traite la requête HTTP, déserialise, valide et crée le dossier.
     * Retourne l'entité Folder en cas de succès, ou un objet de réponse (ResponseTrait) en cas d'erreur.
     * @param Request $request La requête HTTP contenant les données du dossier à créer
     * @return Folder|stdClass Le dossier créé ou une réponse d'erreur formatée
     */
    public function createFolder(Request $request): object
    {
        $json = $request->getContent();
        $data = json_decode($json, true);

        try {
            /** @var Folder $folder */
            $folder = $this->serializer->deserialize($json, Folder::class, 'json');
        } catch (Throwable $th) {
            return $this->sendJson(['success' => false, 'message' => 'Format de données invalide.'], Response::HTTP_BAD_REQUEST);
        }

        // 👈 Association automatique du dossier parent s'il est fourni
        if (!empty($data['parent_id'])) {
            $parentFolder = $this->folderRepository->find((int) $data['parent_id']);
            if ($parentFolder) {
                $folder->setParent($parentFolder);
            }
        }

        $violations = $this->validator->validate($folder);
        if (count($violations) > 0) {
            return $this->sendViolations($violations);
        }

        $folder->setCreatedAt($this->now());
        $folder->setOwner($this->getCurrentUser());

        if ($this->saveFolder($folder, true)) {
            return $folder;
        }

        return $this->sendJson(['success' => false, 'message' => 'Erreur technique lors de la création.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Dispatcher pour les actions rapides des dossiers.
     */
    public function folderQuickActions(Folder $folder, string $action, Request $request): object
    {
        return match ($action) {
            'rename' => $this->renameFolder($folder, $request),
            'move' => $this->sendJson(
                ['success' => false, 'message' => 'L\'action de déplacement sera bientôt disponible.'],
                Response::HTTP_NOT_IMPLEMENTED
            ),
            default => $this->sendJson(
                ['success' => false, 'message' => 'Action inconnue ou non prise en charge.'],
                Response::HTTP_BAD_REQUEST
            )
        };
    }

    /**
     * Renomme un dossier existant en validant les données entrantes.
     */
    private function renameFolder(Folder $folder, Request $request): object
    {
        $json = $request->getContent();
        $data = json_decode($json, true);
        $newName = $data['name'] ?? '';

        $folder->setName(trim($newName));
        $folder->setUpdatedAt($this->now());

        $violations = $this->validator->validate($folder);

        if (count($violations) > 0) {
            return $this->sendViolations($violations);
        }

        if ($this->saveFolder($folder)) {
            return $this->sendJson(['success' => true, 'message' => 'Dossier renommé avec succès']);
        }

        return $this->sendJson(
            ['success' => false, 'message' => 'Erreur technique lors du renommage.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    private function getUploadRootDir(): string
    {
        // À adapter selon ton arborescence réelle d'upload (ex: %kernel.project_dir%/public/uploads/...)
        return 'drive/' . $this->getCurrentUser()->getId();
    }

    /**
     * 👈 Gère l'importation de fichier asynchrone et l'associe au dossier courant
     * Le fichier est déplacé physiquement sur le disque, et une entité DriveDocument est créée en base de données avec tous les métadonnées nécessaires.
     * @return DriveDocument|stdClass
     */
    public function uploadFile(Request $request): object
    {
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('file');
        $folderId = $request->request->get('folder_id');

        if (!$uploadedFile) {
            return $this->sendJson(['success' => false, 'message' => 'Aucun fichier reçu.'], Response::HTTP_BAD_REQUEST);
        }

        // Création et hydratation riche de l'entité de fichier
        $document = new DriveDocument();
        $document->setOriginalName($uploadedFile->getClientOriginalName());
        $document->setNiceName(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));
        $document->setSize($uploadedFile->getSize());
        $document->setExtension($uploadedFile->getClientOriginalExtension());
        $document->setState(ContentStateEnum::Open);
        $document->setCreatedAt($this->now());
        $document->setOwner($this->getCurrentUser());
        $document->setMimeType($uploadedFile->getMimeType());


        // 👈 Association au dossier en cours si l'on n'est pas à la racine
        if (!empty($folderId)) {
            $folder = $this->folderRepository->find((int) $folderId);
            if ($folder) {
                $document->setFolder($folder);
            }
        }

        $uploadPath = 'uploads/' . $this->getUploadRootDir();

        // Déplacement physique du fichier sur le disque local ou le serveur
        try {
            // Utilisation d'un sous-dossier propre à l'utilisateur connecté
            $targetDir = $this->projectDir . '/public/' . $uploadPath;
            $uploadedFile->move($targetDir, $uploadedFile->getClientOriginalName());
            $document->setFilePath($uploadPath . '/' . $uploadedFile->getClientOriginalName());
        } catch (Throwable $e) {
            return $this->sendJson(['success' => false, 'message' => 'Échec de la sauvegarde physique du fichier.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Sauvegarde de l'entité en base de données
        // try {
        $this->documentRepository->save($document, true);
        return $document;
        // } catch (Throwable $th) {
        //     return $this->sendJson(['success' => false, 'message' => 'Échec de l\'enregistrement en base de données.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        // }
    }

    /**
     * Dispatcher centralisé pour toutes les actions rapides des fichiers.
     */
    public function fileQuickActions(DriveDocument $file, string $action, Request $request): object
    {
        return match ($action) {
            'tag' => $this->manageFileTag($file, $request),
            'rename' => $this->renameFile($file, $request),
            'pin' => $this->togglePinFile($file),
            'archive' => $this->changeFileState($file, ContentStateEnum::Archive),
            'trash' => $this->changeFileState($file, ContentStateEnum::Trash),
            'download' => $this->downloadFile($file),
            default => $this->sendJson(
                ['success' => false, 'message' => 'Action de fichier inconnue ou non gérée.'],
                Response::HTTP_BAD_REQUEST
            ),
        };
    }

    /**
     * Prépare le téléchargement d'un fichier en retournant son URL publique relative.
     */
    private function downloadFile(DriveDocument $file): object
    {
        // On récupère le chemin relatif généré lors de l'upload (ex: uploads/drive/1/mon-fichier.pdf)
        $filePath = $file->getFilePath();

        if (empty($filePath)) {
            return $this->sendJson(['success' => false, 'message' => 'Chemin du fichier introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->sendJson([
            'success' => true,
            'url' => '/' . $filePath // On ajoute le slash initial pour garantir une URL absolue par rapport à la racine public/
        ]);
    }

    /**
     * Gère l'affectation ou le retrait d'une étiquette sur un document.
     */
    private function manageFileTag(DriveDocument $file, Request $request): object
    {
        $tagId = $request->request->get('tag_id');

        if (empty($tagId)) {
            $file->setTag(null);
        } else {
            $tagRepository = $this->folderRepository->getEntityManager()->getRepository(Tag::class);
            $tag = $tagRepository->find((int) $tagId);

            if (!$tag) {
                return $this->sendJson(['success' => false, 'message' => 'Étiquette introuvable.'], Response::HTTP_NOT_FOUND);
            }

            $file->setTag($tag);
        }

        if ($this->saveFile($file)) {
            $tagPayload = $file->getTag() ? [
                'id' => $file->getTag()->getId(),
                'name' => $file->getTag()->getName(),
                'color' => $file->getTag()->getColor() instanceof \BackedEnum ? $file->getTag()->getColor()->value : $file->getTag()->getColor()
            ] : null;

            return $this->sendJson([
                'success' => true,
                'message' => 'Étiquette mise à jour avec succès.',
                'tag' => $tagPayload
            ]);
        }

        return $this->sendJson(
            ['success' => false, 'message' => 'Erreur technique lors de la sauvegarde.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * Renomme un fichier.
     */
    private function renameFile(DriveDocument $file, Request $request): object
    {
        $newName = $request->request->get('name');
        if (empty($newName)) {
            return $this->sendJson(['success' => false, 'message' => 'Le nom est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $file->setNiceName(trim($newName));
        if ($this->saveFile($file)) {
            return $this->sendJson(['success' => true, 'message' => 'Fichier renommé avec succès.']);
        }

        return $this->sendJson(['success' => false, 'message' => 'Erreur lors du renommage.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Épingler / Désépingler un fichier.
     */
    private function togglePinFile(DriveDocument $file): object
    {
        $file->setIsPinned(!$file->isPinned());
        if ($this->saveFile($file)) {
            return $this->sendJson(['success' => true, 'is_pinned' => $file->isPinned()]);
        }

        return $this->sendJson(['success' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Modifie l'état d'un fichier (Archive / Trash).
     */
    private function changeFileState(DriveDocument $file, ContentStateEnum $state): object
    {
        $file->setState($state);
        if ($state === ContentStateEnum::Trash) {
            $file->setDeletedAt($this->now());
        }

        if ($this->saveFile($file)) {
            return $this->sendJson(['success' => true]);
        }

        return $this->sendJson(['success' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}