<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Fagathe\CorePhp\Trait\DatetimeTrait;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{

    use DatetimeTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Sauvegarde un Tag en base de données.
     * * @param Tag  $tag   L'entité à sauvegarder
     * @param bool $flush Faut-il exécuter la requête tout de suite ?
     * * @return bool Succès de l'opération
     */
    public function save(Tag $tag, bool $flush = true, bool $isCreation = false): bool
    {
        try {
            $now = $this->now();
            if ($isCreation) {
                $tag->setCreatedAt($now);
            } else {
                $tag->setUpdatedAt($now);
            }
            
            $this->getEntityManager()->persist($tag);

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
     * Supprime un Tag de la base de données.
     * * @param Tag  $tag   L'entité à supprimer
     * @param bool $flush Faut-il exécuter la requête tout de suite ?
     * * @return bool Succès de l'opération
     */
    public function remove(Tag $tag, bool $flush = true): bool
    {
        try {
            $this->getEntityManager()->remove($tag);

            if ($flush) {
                $this->getEntityManager()->flush();
            }

            return true;
        } catch (ORMException $ormException) {
            return false;
        }
    }

    //    /**
    //     * @return Tag[] Returns an array of Tag objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tag
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
