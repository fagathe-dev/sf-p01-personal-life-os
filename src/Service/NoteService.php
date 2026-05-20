<?php

namespace App\Service;

use App\Entity\Note;
use App\Entity\Tag;
use App\Entity\User;
use App\Enum\ContentStateEnum;
use App\Repository\NoteRepository;
use App\Repository\TagRepository;
use Fagathe\CorePhp\Breadcrumb\Breadcrumb;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Http\ResponseTrait;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Throwable;

final class NoteService
{
    # Service de gestion des notes (CRUD, validation, etc.)

    use LoggerTrait, DatetimeTrait, ResponseTrait;

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
        $note->setState(ContentStateEnum::Trash)
            ->setDeletedAt($this->now());
        return $this->saveNote($note); // Ta méthode de sauvegarde existante
    }

    /**
     * @param Note $note
     * 
     * @return bool
     */
    public function restoreNote(Note $note): bool
    {
        $note->setState(ContentStateEnum::Open)
            ->setDeletedAt(null);
        return $this->saveNote($note, false);
    }


    /**
     * Génère le fil d'Ariane pour la gestion des tâches.
     * @param BreadcrumbItem[] $items Les éléments du fil d'Ariane
     * * @return Breadcrumb
     */
    public function breadcrumb(array $items = []): Breadcrumb
    {
        return new Breadcrumb([
            new BreadcrumbItem(name: 'Notes', link: $this->urlGenerator->generate('app_note_manage')),
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
        // On utilise la nouvelle méthode du repository avec les filtres
        $currentUserNotes = $this->repository->findFilteredNotes($filters);

        // On sépare les notes épinglées des autres pour un affichage différencié
        [$pinnedNotes, $unpinnedNotes] = $this->filterPinnedNotes($currentUserNotes);

        return [
            'notes' => compact('pinnedNotes', 'unpinnedNotes'),
            'userTags' => $this->getCurrentUserTags(),
            'breadcrumb' => $this->breadcrumb(),
            'currentTag' => null, // Permet à la vue de savoir qu'aucun filtre strict de navigation n'est actif
        ];
    }

    /**
     * @param array $notes
     * 
     * @return array
     */
    private function filterPinnedNotes(array $notes): array
    {
        $pinned = [];
        $unpinned = [];

        foreach ($notes as $note) {
            if ($note->isPinned()) {
                $pinned[] = $note;
            } else {
                $unpinned[] = $note;
            }
        }

        return [$pinned, $unpinned];
    }

    /**
     * Centralise et traite les actions rapides (Archive, Trash, Pin) issues d'AJAX
     * * @param Note $note
     * @param string $action 'pinned'|'archive'|'trash'
     * @return object Un objet contenant data, status, headers pour NoteQuickActionsController
     */
    public function handleQuickAction(Note $note, string $action): object
    {
        try {
            switch ($action) {
                case 'pinned':
                    // On bascule l'état booléen d'épinglage
                    $note->setIsPinned(!$note->isPinned());
                    break;

                case 'archive':
                    // ⚠️ Attention à l'écriture "Archieved" présente dans l'Enum
                    $note->setState(ContentStateEnum::Archieved);
                    break;

                case 'trash':
                    $note->setState(ContentStateEnum::Trash) // On applique le soft delete avec l'horodatage courant du DatetimeTrait
                        ->setDeletedAt($this->now());
                    break;

                default:
                    return $this->sendJson(['success' => false, 'error' => 'Action non supportée'], 400);
            }

            // Sauvegarde via le repository
            if ($this->saveNote($note, false)) {
                return $this->sendJson([
                    'success' => true,
                    'id' => $note->getId(),
                    'is_pinned' => $note->isPinned(),
                    'state' => $note->getState()->value
                ]);
            }

            return $this->sendJson(['success' => false, 'error' => 'Erreur lors de la sauvegarde'], 500);

        } catch (Throwable $e) {
            $this->generateLog(
                LoggerLevelEnum::Error,
                [
                    'message' => 'Erreur lors de l\'action rapide sur la note',
                    'note_id' => $note->getId(),
                    'action' => $action,
                    'error' => $e->getMessage()
                ],
                ['action' => 'note.quick_action.error']
            );
            return $this->sendJson(['success' => false, 'error' => 'Une erreur système est survenue'], 500);
        }
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