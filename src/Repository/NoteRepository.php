<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }


    /**
     * Sauvegarde un Note en base de données.
     * * @param Note  $note   L'entité à sauvegarder
     * @param bool $flush Faut-il exécuter la requête tout de suite ?
     * * @return bool Succès de l'opération
     */
    public function save(Note $note, bool $flush = true, bool $isCreation = false): bool
    {
        try {
            $now = $this->now();
            if ($isCreation) {
                $note->setCreatedAt($now);
            } else {
                $note->setUpdatedAt($now);
            }

            $this->getEntityManager()->persist($note);

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
     * Supprime une note
     * @param Note $note L'entité à supprimer
     * @param bool $flush Faut-il exécuter la requête tout de suite ?
     * @return bool Succès de l'opération
     */
    public function remove(Note $note, bool $flush = true): bool
    {
        try {
            $this->getEntityManager()->remove($note);

            if ($flush) {
                $this->getEntityManager()->flush();
            }

            return true;
        } catch (ORMException $ormException) {
            return false;
        }
    }

    public function searchNotesRaw(string $term, User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // On sécurise l'entrée pour le mode booléen de MySQL
        $searchTerm = $term . '*';

        $sql = 'CALL search_user_notes(:term, :userId)';

        // Utilisation directe de executeQuery sur la Connection (Standard DBAL 3 / 4)
        $resultSet = $conn->executeQuery($sql, [
            'term' => $searchTerm,
            'userId' => $user->getId(),
        ]);

        // Retourne un tableau associatif propre
        return $resultSet->fetchAllAssociative();
    }

    /**
     * @param int $daysRetention
     * @return Note[]
     */
    public function findExpiredTrash(int $daysRetention = 30): array
    {
        $limitDate = new \DateTimeImmutable("-{$daysRetention} days");

        return $this->createQueryBuilder('n')
            ->where('n.deleted_at IS NOT NULL')
            ->andWhere('n.deleted_at <= :limitDate')
            ->setParameter('limitDate', $limitDate)
            ->getQuery()
            ->getResult();
    }
}
