<?php

namespace App\Repository;

use App\Entity\Folder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Fagathe\CorePhp\Trait\DatetimeTrait;

/**
 * @extends ServiceEntityRepository<Folder>
 */
class FolderRepository extends ServiceEntityRepository
{
    use DatetimeTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Folder::class);
    }

    /**
     * Sauvegarde un dossier en base de données.
     * * @param Folder  $folder   L'entité à sauvegarder
     * @param bool $flush Faut-il exécuter la requête tout de suite ?
     * * @return bool Succès de l'opération
     */
    public function save(Folder $folder, bool $flush = true, bool $isCreation = false): bool
    {
        try {
            $now = $this->now();
            if ($isCreation) {
                $folder->setCreatedAt($now);
            } else {
                $folder->setUpdatedAt($now);
            }
            
            $this->getEntityManager()->persist($folder);

            if ($flush) {
                $this->getEntityManager()->flush();
            }

            return true;
        } catch (ORMException $ormException) {
            // Dans une version plus avancée, tu pourrais vouloir logger l'exception ici
            return false;
        }
    }

    /**
     * Supprime un dossier de la base de données.
     * @param Folder $folder L'entité à supprimer
     * @param bool $flush Faut-il exécuter la requête immédiatement ?
     * @return bool Succès de l'opération
     */
    public function remove(Folder $folder, bool $flush = true): bool
    {
        try {
            $this->getEntityManager()->remove($folder);

            if ($flush) {
                $this->getEntityManager()->flush();
            }

            return true;
        } catch (ORMException $ormException) {
            return false;
        }
    }

    //    /**
    //     * @return Folder[] Returns an array of Folder objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Folder
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
