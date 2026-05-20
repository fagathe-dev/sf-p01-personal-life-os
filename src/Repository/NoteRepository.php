<?php

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use App\Enum\ContentStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    use DatetimeTrait;
    public function __construct(ManagerRegistry $registry, private readonly Security $security)
    {
        parent::__construct($registry, Note::class);
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * Recherche et filtre les notes actives d'un utilisateur
     * * @param User $user
     * @param array $filters ['query' => ?string, 'tag' => ?int, 'color' => ?string]
     * @return Note[]
     */
    public function findFilteredNotes(array $filters, ?User $user = null): array
    {
        $user = $user ?? $this->getCurrentUser();
        if (!$user) {
            return [];
        }

        $qb = $this->createQueryBuilder('n')
            ->where('n.owner = :user')
            ->andWhere('n.state = :state')
            ->andWhere('n.deleted_at IS NULL') // Sécurité supplémentaire
            ->setParameter('user', $user)
            ->setParameter('state', ContentStateEnum::Open);

        // Filtre Fulltext (Recherche dans le titre ou le contenu)
        if (!empty($filters['query'])) {
            $qb->andWhere('n.title LIKE :query OR n.content LIKE :query')
               ->setParameter('query', '%' . $filters['query'] . '%');
        }

        // Filtre par Étiquette
        if (!empty($filters['tag'])) {
            $qb->andWhere('n.tag = :tag')
               ->setParameter('tag', $filters['tag']);
        }

        // Filtre par Couleur
        if (!empty($filters['color'])) {
            $qb->andWhere('n.color = :color')
               ->setParameter('color', $filters['color']);
        }

        // Tri : Notes épinglées en premier, puis par date de création décroissante
        $qb->orderBy('n.is_pinned', 'DESC')
           ->addOrderBy('n.created_at', 'DESC');

        return $qb->getQuery()->getResult();
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
                $note->setCreatedAt($now)
                    ->setOwner($this->getCurrentUser())
                    ->setState(ContentStateEnum::Open)
                ;
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
