<?php

namespace App\Repository;

use App\Entity\Todo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Fagathe\CorePhp\Trait\DatetimeTrait;

/**
 * @extends ServiceEntityRepository<Todo>
 */
class TaskRepository extends ServiceEntityRepository
{

    use DatetimeTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }

    /**
     * Supprime une tâche
     * @param Todo $todo L'entité à supprimer
     * @param bool $flush Faut-il exécuter la requête tout de suite ?
     * @return bool Succès de l'opération
     */
    public function remove(Todo $todo, bool $flush = true): bool
    {
        try {
            $this->getEntityManager()->remove($todo);

            if ($flush) {
                $this->getEntityManager()->flush();
            }

            return true;
        } catch (ORMException $ormException) {
            return false;
        }
    }

    /**
     * Sauvegarde une tâche (Création ou Mise à jour)
     * @param Todo $todo L'entité à sauvegarder
     * @param bool $flush Faut-il envoyer en base tout de suite ?
     * @return bool Succès de l'opération
     */
    public function save(Todo $todo, bool $flush = true, bool $isCreation = false): bool
    {
        $now = $this->now();
        if ($isCreation) {
            $todo->setCreatedAt($now);
        } else {
            $todo->setUpdatedAt($now);
        }

        try {
            $this->getEntityManager()->persist($todo);

            if ($flush) {
                $this->getEntityManager()->flush();
            }

            return true;
        } catch (ORMException $ormException) {
            return false;
        }
    }

    //    /**
    //     * @return Task[] Returns an array of Task objects
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

    //    public function findOneBySomeField($value): ?Task
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
