<?php

namespace App\Service;

use App\Entity\Folder;
use App\Repository\FolderRepository;
use Symfony\Component\Filesystem\Filesystem;

final class ArchiveTrashService
{
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly FolderRepository $folderRepository
    ) {
        $this->filesystem = new Filesystem();
    }

    /**
     * Cette méthode sera appelée uniquement lors du "Vider la corbeille"
     * ou d'un "Supprimer définitivement".
     */
    public function hardDeleteFolder(Folder $folder): bool
    {
        // 1. Collecte récursive des fichiers uniquement pour la suppression physique
        $filesToDelete = [];
        $this->collectFilesRecursive($folder, $filesToDelete);

        // 2. Nettoyage physique du disque
        foreach ($filesToDelete as $file) {
            $absolutePath = $this->getUploadRootDir() . '/' . $file->getOriginalName();
            if ($this->filesystem->exists($absolutePath)) {
                $this->filesystem->remove($absolutePath);
            }
        }

        // 3. Hard delete de la ligne en BDD (Doctrine gère la cascade remove sur les entités liées)
        $this->folderRepository->remove($folder, true);

        return true;
    }

    private function getUploadRootDir(): string
    {
        // À adapter selon ton arborescence réelle d'upload (ex: %kernel.project_dir%/public/uploads/...)
        return __DIR__ . '/../../public/uploads';
    }
}