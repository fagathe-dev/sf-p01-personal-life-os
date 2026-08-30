<?php

namespace App\Service;

use App\Entity\Tag;
use App\Entity\Todo;
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
    use LoggerTrait, DatetimeTrait;

    public function __construct(
        private readonly TaskRepository $repository,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TagRepository $tagRepository,
    ) {
    }

    public function getCurrentUserTags(): array
    {
        $user = $this->getCurrentUser();
        if (!$user)
            return [];
        return $this->tagRepository->findBy(['owner' => $user], ['name' => 'ASC']);
    }

    public function getTodosByTag(Tag $tag): array
    {
        return $tag->getTodos()->toArray();
    }

    public function tagTodos(Tag $tag): array
    {
        $tasks = $this->getTodosByTag($tag);

        return [
            'activeGroups' => $this->getGroupedTodos($tasks),
            'completedTasks' => $this->getCompletedTodos($tasks),
            'userTags' => $this->getCurrentUserTags(),
            'breadcrumb' => $this->breadcrumb([new BreadcrumbItem(name: 'Étiquette : ' . $tag->getName())]),
            'currentTag' => $tag,
        ];
    }

    public function manage(): array
    {
        $tasks = $this->getCurrentUserTodos();

        return [
            'activeGroups' => $this->getGroupedTodos($tasks),
            'completedTasks' => $this->getCompletedTodos($tasks),
            'userTags' => $this->getCurrentUserTags(),
            'breadcrumb' => $this->breadcrumb(),
            'currentTag' => null,
        ];
    }

    public function saveTodo(Todo $todo, bool $isCreation = false): bool
    {
        try {
            if ($isCreation && $user = $this->security->getUser()) {
                $todo->setOwner($user);
            }

            $this->repository->save(todo: $todo, flush: true, isCreation: $isCreation);

            $this->generateLog(
                LoggerLevelEnum::Info,
                ['message' => 'Todo sauvegardée', 'task_title' => $todo->getTitle()],
                ['action' => $isCreation ? 'todo.create.success' : 'todo.update.success']
            );

            return true;
        } catch (Throwable $th) {
            return false;
        }
    }

    public function deleteTodo(Todo $todo): bool
    {
        try {
            $this->repository->remove($todo, true);
            return true;
        } catch (Throwable $th) {
            return false;
        }
    }

/**
     * Regroupe les tâches actives par sections chronologiques
     */
    private function getGroupedTodos(array $tasks): array
    {
        $groups = [
            'overdue'   => ['label' => 'En retard', 'bg' => 'bg-danger-subtle', 'text' => 'text-danger', 'icon' => 'bx-error-circle', 'todos' => []],
            'today'     => ['label' => 'Aujourd\'hui', 'bg' => 'bg-slate-800', 'text' => 'text-white', 'icon' => 'bx-sun', 'todos' => []],
            'tomorrow'  => ['label' => 'Demain', 'bg' => 'bg-slate-800', 'text' => 'text-white', 'icon' => 'bx-calendar-event', 'todos' => []],
            'this_week' => ['label' => 'Cette semaine', 'bg' => 'bg-slate-800', 'text' => 'text-white', 'icon' => 'bx-calendar-week', 'todos' => []],
            'later'     => ['label' => 'Plus tard', 'bg' => 'bg-slate-800', 'text' => 'text-white', 'icon' => 'bx-archive', 'todos' => []],
        ];

        // Format de référence 'Y-m-d' pour ignorer totalement l'heure
        $todayStr     = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $tomorrowStr  = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');
        $endOfWeekStr = (new \DateTimeImmutable('sunday this week'))->format('Y-m-d');

        foreach ($tasks as $todo) {
            if ($todo->isCompleted()) {
                continue; // Les terminées sont exclues de ce groupement
            }

            $dueDate = $todo->getDueDate();
            if (!$dueDate) {
                $groups['later']['todos'][] = $todo;
                continue;
            }

            // On formate la date de la tâche pour la comparaison
            $dueStr = $dueDate->format('Y-m-d');

            if ($dueStr < $todayStr) {
                $groups['overdue']['todos'][] = $todo;
            } elseif ($dueStr === $todayStr) {
                $groups['today']['todos'][] = $todo;
            } elseif ($dueStr === $tomorrowStr) {
                $groups['tomorrow']['todos'][] = $todo;
            } elseif ($dueStr <= $endOfWeekStr) {
                $groups['this_week']['todos'][] = $todo;
            } else {
                $groups['later']['todos'][] = $todo;
            }
        }

        // Tri interne de chaque groupe par échéance (le plus urgent en haut)
        // Ici on conserve l'objet DateTime complet pour trier précisément si deux tâches sont le même jour
        foreach ($groups as &$group) {
            usort($group['todos'], function (Todo $a, Todo $b) {
                $dateA = $a->getDueDate();
                $dateB = $b->getDueDate();
                if ($dateA && $dateB) {
                    return $dateA <=> $dateB;
                }
                return 0;
            });
        }

        // On ne retourne que les sections qui ont au moins 1 tâche
        return array_filter($groups, fn($g) => count($g['todos']) > 0);
    }
    /**
     * Récupère uniquement les tâches terminées (à part)
     */
    private function getCompletedTodos(array $tasks): array
    {
        $completed = array_filter($tasks, fn(Todo $todo) => $todo->isCompleted());

        // Optionnel : on peut trier les tâches terminées de la plus récemment achevée à la plus ancienne
        usort($completed, function (Todo $a, Todo $b) {
            if (method_exists($a, 'getCompletedAt') && method_exists($b, 'getCompletedAt')) {
                return $b->getCompletedAt() <=> $a->getCompletedAt();
            }
            return 0;
        });

        return $completed;
    }

    public function getUserTodos(User $user): array
    {
        return $this->repository->findBy(['owner' => $user], ['title' => 'ASC']);
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    public function getCurrentUserTodos(): array
    {
        $user = $this->getCurrentUser();
        return $user ? $this->getUserTodos($user) : [];
    }

    public function breadcrumb(array $items = []): Breadcrumb
    {
        return new Breadcrumb([
            new BreadcrumbItem(name: 'Todo List', link: $this->urlGenerator->generate('app_todo_manage')),
            ...$items
        ]);
    }
}