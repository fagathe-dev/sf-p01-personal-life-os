<?php

namespace App\Repository;

use App\Entity\JournalEntry;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalEntry>
 */
class JournalEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalEntry::class);
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
}
