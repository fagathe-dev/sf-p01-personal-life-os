<?php

namespace App\Service;

use App\Entity\Note;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\NoteRepository;
use App\Repository\TagRepository;
use Fagathe\CorePhp\Breadcrumb\Breadcrumb;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Throwable;

final class NoteService
{
    # Service de gestion des notes (CRUD, validation, etc.)

    use LoggerTrait, DatetimeTrait;

    public function __construct(
        private readonly NoteRepository $repository,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TagRepository $tagRepository,
        private readonly DenormalizerInterface $denormalizer,
    ) {
    }

    /**
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            return $user;
        }

        return null;
    }


    // Ce service est actuellement vide, mais il peut être utilisé pour centraliser la logique métier liée aux notes à l'avenir.
    public function searchNotesAsObjects(string $term): array
    {
        $user = $this->getCurrentUser();
        $rawResults = $this->repository->searchNotesRaw($term, $user);
        $notes = [];

        foreach ($rawResults as $row) {
            // Le Denormalizer va transformer le tableau associatif en objet Note.
            // Si tes colonnes SQL sont en snake_case (ex: created_at) et tes propriétés en camelCase (createdAt), 
            // assure-toi que le CamelCaseToSnakeCaseNameConverter est bien actif dans ta config Symfony !
            $notes[] = $this->denormalizer->denormalize($row, Note::class, 'array');
        }

        return $notes;
    }

    /**
     * @param Note $note
     * @param bool $isCreation True pour une création, false pour une mise à jour
     * 
     * @return bool
     */
    public function saveNote(Note $note, bool $isCreation = false): bool
    {
        return $this->repository->save($note, true, $isCreation);
    }

    /**
     * @param Note $note
     * 
     * @return bool
     */
    public function trashNote(Note $note): bool
    {
        $note->setDeletedAt($this->now());
        return $this->saveNote($note); // Ta méthode de sauvegarde existante
    }

    /**
     * @param Note $note
     * 
     * @return bool
     */
    public function restoreNote(Note $note): bool
    {
        $note->setDeletedAt(null);
        return $this->saveNote($note);
    }


    /**
     * Génère le fil d'Ariane pour la gestion des tâches.
     * @param BreadcrumbItem[] $items Les éléments du fil d'Ariane
     * * @return Breadcrumb
     */
    public function breadcrumb(array $items = []): Breadcrumb
    {
        return new Breadcrumb([
            new BreadcrumbItem(name: 'Todo List', link: $this->urlGenerator->generate('app_todo_manage')),
            ...$items
        ]);
    }

    /**
     * Supprime une Note.
     * * @param Note $note La note à supprimer
     * * @return bool True en cas de succès
     */
    public function deleteNote(Note $note): bool
    {
        try {
            $noteId = $note->getId();

            $this->repository->remove($note, true);

            $this->generateLog(
                LoggerLevelEnum::Info,
                [
                    'message' => 'Note supprimée avec succès',
                    'note_id' => $noteId
                ],
                ['action' => 'note.delete.success']
            );

            return true;
        } catch (Throwable $th) {
            $this->generateLog(
                LoggerLevelEnum::Error,
                [
                    'message' => 'Erreur lors de la suppression de la note',
                    'note_id' => $note->getId(),
                    'error' => $th->getMessage()
                ],
                ['action' => 'note.delete.error']
            );
            return false;
        }
    }

    /**
     * Récupère tous les tags de l'utilisateur connecté
     * * @return Tag[]
     */
    public function getCurrentUserTags(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [];
        }

        // On suppose que ton entité Tag a une relation 'owner' comme Task
        return $this->tagRepository->findBy(['owner' => $user], ['name' => 'ASC']);
    }

    /**
     * Récupère toutes les notes de l'utilisateur connecté
     * * @return Note[]
     */
    public function getCurrentUserNotes(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [];
        }

        return $this->repository->findBy(['owner' => $user, 'deleted_at' => null], ['created_at' => 'DESC']);
    }

    /**
     * Prépare les données pour la page principale (Toutes les tâches)
     * * @param array $filters Les filtres appliqués (ex: par couleur, par tag, etc.)
     * * @return array Les données à passer à la vue
     */
    public function manage(array $filters = []): array
    {
        return [
            'notes' => $this->getCurrentUserNotes(),
            'userTags' => $this->getCurrentUserTags(),
            'breadcrumb' => $this->breadcrumb(),
            'currentTag' => null, // Permet à la vue de savoir qu'aucun filtre n'est actif
        ];
    }

    /**
     * @param Tag $tag
     * 
     * @return array
     */
    public function getNotesByTag(Tag $tag): array
    {
        return $tag->getNotes()->toArray();
    }

    /**
     * Prépare les données pour la page filtrée par Tag
     */
    public function tagNotes(Tag $tag): array
    {
        $notes = $this->getCurrentUserNotes();

        return [
            'notes' => $notes,
            'userTags' => $this->getCurrentUserTags(),
            'breadcrumb' => $this->breadcrumb([
                new BreadcrumbItem(name: 'Étiquette : ' . $tag->getName())
            ]),
            'currentTag' => $tag, // Le tag actif pour pré-sélectionner l'option
        ];
    }
}