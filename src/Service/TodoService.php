<?php

namespace App\Service;

use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\TaskRepository;
use Fagathe\CorePhp\Breadcrumb\Breadcrumb;
use Fagathe\CorePhp\Breadcrumb\BreadcrumbItem;
use Fagathe\CorePhp\Enum\LoggerLevelEnum;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Fagathe\CorePhp\Trait\LoggerTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

final class TodoService
{
    # Service de gestion des tâches (CRUD, validation, etc.)

    use LoggerTrait, DatetimeTrait;

    public function __construct(
        private readonly TaskRepository $repository,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TagRepository $tagRepository,
    ) {
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
     * @param Tag $tag
     * 
     * @return array
     */
    public function getTasksByTag(Tag $tag): array
    {
        return $tag->getTasks()->toArray();
    }

    /**
     * Prépare les données pour la page filtrée par Tag
     */
    public function tagTasks(Tag $tag): array
    {
        $tasks = $this->getSortedCurrentTasks($this->getTasksByTag($tag));

        return [
            'tasks' => $tasks,
            'userTags' => $this->getCurrentUserTags(),
            'breadcrumb' => $this->breadcrumb([
                new BreadcrumbItem(name: 'Étiquette : ' . $tag->getName())
            ]),
            'currentTag' => $tag, // Le tag actif pour pré-sélectionner l'option
        ];
    }

    /**
     * @param array $tasks
     * 
     * @return array
     */
    private function filterTasks(array $tasks): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [];
        }

        return array_filter($tasks, fn(Task $task) => $task->getOwner() === $user);
    }

    /**
     * Prépare les données pour la page principale (Toutes les tâches)
     */
    public function manage(): array
    {
        return [
            'tasks' => $this->getSortedCurrentTasks(),
            'userTags' => $this->getCurrentUserTags(),
            'breadcrumb' => $this->breadcrumb(),
            'currentTag' => null, // Permet à la vue de savoir qu'aucun filtre n'est actif
        ];
    }

    /**
     * Sauvegarde une tâche (Création ou Mise à jour).
     * * Lors d'une création, assigne automatiquement l'utilisateur connecté comme propriétaire.
     * * @param Task  $task        La tâche à sauvegarder
     * @param bool $isCreation True si c'est une nouvelle tâche
     * * @return bool True si l'opération a réussi
     */
    public function saveTask(Task $task, bool $isCreation = false): bool
    {
        try {
            if ($isCreation) {
                /** @var User $user */
                $user = $this->security->getUser();
                if ($user) {
                    $task->setOwner($user);
                }
            }

            // Appel de la méthode du repository (qui gère le flush)
            $this->repository->save(task: $task, flush: true, isCreation: $isCreation);

            $this->generateLog(
                LoggerLevelEnum::Info,
                [
                    'message' => 'Task sauvegardée avec succès',
                    'task_title' => $task->getTitle(),
                ],
                ['action' => $isCreation ? 'task.create.success' : 'task.update.success']
            );

            return true;
        } catch (Throwable $th) {
            $this->generateLog(
                LoggerLevelEnum::Error,
                [
                    'message' => 'Erreur lors de la sauvegarde de la tâche',
                    'task_title' => $task->getTitle(),
                    'error' => $th->getMessage()
                ],
                ['action' => 'task.save.error']
            );
            return false;
        }
    }

    /**
     * Récupère les tâches de l'utilisateur connecté avec un tri intelligent :
     * 1. Les tâches actives triées par urgence (échéance la plus proche).
     * 2. Les tâches terminées reléguées à la fin.
     * @param ?array $tasks Un tableau de tâches à trier (optionnel, sinon tri les tâches de l'utilisateur connecté)
     * 
     * @return Task[]
     */
    public function getSortedCurrentTasks(?array $tasks = null): array
    {
        $tasks = $tasks ?? $this->getCurrentUserTasks();

        // 1. On sépare les tâches actives des tâches terminées
        $activeTasks = array_filter($tasks, fn(Task $task) => !$task->isCompleted());
        $completedTasks = array_filter($tasks, fn(Task $task) => $task->isCompleted());

        // 2. On trie UNIQUEMENT les tâches actives par échéance
        usort($activeTasks, function (Task $a, Task $b) {
            $dateA = $a->getDueDate();
            $dateB = $b->getDueDate();

            if ($dateA && $dateB) {
                return $dateA <=> $dateB; // Du plus urgent au plus lointain
            }

            if ($dateA)
                return -1;
            if ($dateB)
                return 1;

            if (method_exists($a, 'getCreatedAt') && method_exists($b, 'getCreatedAt')) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            }

            return 0;
        });

        // 3. Optionnel : On trie les tâches terminées par date de création (les plus récentes en premier)
        usort($completedTasks, function (Task $a, Task $b) {
            if (method_exists($a, 'getCreatedAt') && method_exists($b, 'getCreatedAt')) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            }
            return 0;
        });

        // 4. On fusionne les deux tableaux (Actives d'abord, Terminées ensuite)
        return array_merge($activeTasks, $completedTasks);
    }

    /**
     * Supprime un Task.
     * * @param Task $task Le task à supprimer
     * * @return bool True en cas de succès
     */
    public function deleteTask(Task $task): bool
    {
        try {
            $taskId = $task->getId();

            $this->repository->remove($task, true);

            $this->generateLog(
                LoggerLevelEnum::Info,
                [
                    'message' => 'Task supprimé avec succès',
                    'task_id' => $taskId
                ],
                ['action' => 'task.delete.success']
            );

            return true;
        } catch (Throwable $th) {
            $this->generateLog(
                LoggerLevelEnum::Error,
                [
                    'message' => 'Erreur lors de la suppression de la tâche',
                    'task_id' => $task->getId(),
                    'error' => $th->getMessage()
                ],
                ['action' => 'task.delete.error']
            );
            return false;
        }
    }

    /**
     * Récupère tous les tâches appartenant à un utilisateur précis.
     * * @param User $user Le propriétaire des tâches
     * * @return Task[]
     */
    public function getUserTasks(User $user): array
    {
        return $this->repository->findBy(['owner' => $user], ['title' => 'ASC']);
    }

    /**
     * @return User|null
     */
    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            return $user;
        }

        return null;
    }

    /**
     * Raccourci pour récupérer les tâches de l'utilisateur actuellement connecté.
     * * @return Task[]
     */
    public function getCurrentUserTasks(): array
    {
        /** @var User|null $user */
        $user = $this->getCurrentUser();

        if (!$user) {
            return [];
        }

        return $this->getUserTasks($user);
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

}