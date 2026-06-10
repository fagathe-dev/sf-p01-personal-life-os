<?php

namespace App\Repository;

use App\Entity\DriveDocument;
use App\Enum\ContentStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Fagathe\CorePhp\Trait\DatetimeTrait;

/**
 * @extends ServiceEntityRepository<DriveDocument>
 */
class DriveDocumentRepository extends ServiceEntityRepository
{
    use DatetimeTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DriveDocument::class);
    }

    /**
     * Sauvegarde un document.
     */
    public function save(DriveDocument $file, bool $flush = true, bool $isCreation = false): bool
    {
        // try {
            $now = $this->now();
            if ($isCreation) {
                $file->setCreatedAt($now)
                    ->setIsPinned(false) // Par défaut, un nouveau fichier n'est pas épinglé
                    ->setState(ContentStateEnum::Open)
                ;
            } else {
                $file->setUpdatedAt($now);
            }

            $this->getEntityManager()->persist($file);

            if ($flush) {
                $this->getEntityManager()->flush();
            }

            return true;
        // } catch (ORMException $ormException) {
        //     return false;
        // }
    }

    /**
     * Supprime un document de la BDD.
     */
    public function remove(DriveDocument $file, bool $flush = true): bool
    {
        try {
            $this->getEntityManager()->remove($file);

            if ($flush) {
                $this->getEntityManager()->flush();
            }

            return true;
        } catch (ORMException $ormException) {
            return false;
        }
    }
}
