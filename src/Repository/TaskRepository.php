<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Fagathe\CorePhp\Trait\DatetimeTrait;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{

    use DatetimeTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Supprime une tâche
     * @param Task $task L'entité à supprimer
     * @param bool $flush Faut-il exécuter la requête tout de suite ?
     * @return bool Succès de l'opération
     */
    public function remove(Task $task, bool $flush = true): bool
    {
        try {
            $this->getEntityManager()->remove($task);

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
     * @param Task $task L'entité à sauvegarder
     * @param bool $flush Faut-il envoyer en base tout de suite ?
     * @return bool Succès de l'opération
     */
    public function save(Task $task, bool $flush = true, bool $isCreation = false): bool
    {
        $now = $this->now();
        if ($isCreation) {
            $task->setCreatedAt($now);
        } else {
            $task->setUpdatedAt($now);
        }

        try {
            $this->getEntityManager()->persist($task);

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
