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

final class TaskService
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

    public function getTasksByTag(Tag $tag): array
    {
        return $tag->getTasks()->toArray();
    }

    public function tagTasks(Tag $tag): array
    {
        $tasks = $this->getTasksByTag($tag);

        return [
            'activeGroups' => $this->getGroupedTasks($tasks),
            'completedTasks' => $this->getCompletedTasks($tasks),
            'userTags' => $this->getCurrentUserTags(),
            'breadcrumb' => $this->breadcrumb([new BreadcrumbItem(name: 'Étiquette : ' . $tag->getName())]),
            'currentTag' => $tag,
        ];
    }

    public function manage(): array
    {
        $tasks = $this->getCurrentUserTasks();

        return [
            'activeGroups' => $this->getGroupedTasks($tasks),
            'completedTasks' => $this->getCompletedTasks($tasks),
            'userTags' => $this->getCurrentUserTags(),
            'breadcrumb' => $this->breadcrumb(),
            'currentTag' => null,
        ];
    }

    public function saveTask(Task $task, bool $isCreation = false): bool
    {
        try {
            if ($isCreation && $user = $this->security->getUser()) {
                $task->setOwner($user);
            }

            $this->repository->save(task: $task, flush: true, isCreation: $isCreation);

            $this->generateLog(
                LoggerLevelEnum::Info,
                ['message' => 'Task sauvegardée', 'task_title' => $task->getTitle()],
                ['action' => $isCreation ? 'task.create.success' : 'task.update.success']
            );

            return true;
        } catch (Throwable $th) {
            return false;
        }
    }

    public function deleteTask(Task $task): bool
    {
        try {
            $this->repository->remove($task, true);
            return true;
        } catch (Throwable $th) {
            return false;
        }
    }

/**
     * Regroupe les tâches actives par sections chronologiques
     */
    private function getGroupedTasks(array $tasks): array
    {
        $groups = [
            'overdue'   => ['label' => 'En retard', 'bg' => 'bg-danger-subtle', 'text' => 'text-danger', 'icon' => 'bx-error-circle', 'tasks' => []],
            'today'     => ['label' => 'Aujourd\'hui', 'bg' => 'bg-slate-800', 'text' => 'text-white', 'icon' => 'bx-sun', 'tasks' => []],
            'tomorrow'  => ['label' => 'Demain', 'bg' => 'bg-slate-800', 'text' => 'text-white', 'icon' => 'bx-calendar-event', 'tasks' => []],
            'this_week' => ['label' => 'Cette semaine', 'bg' => 'bg-slate-800', 'text' => 'text-white', 'icon' => 'bx-calendar-week', 'tasks' => []],
            'later'     => ['label' => 'Plus tard', 'bg' => 'bg-slate-800', 'text' => 'text-white', 'icon' => 'bx-archive', 'tasks' => []],
        ];

        // Format de référence 'Y-m-d' pour ignorer totalement l'heure
        $todayStr     = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $tomorrowStr  = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');
        $endOfWeekStr = (new \DateTimeImmutable('sunday this week'))->format('Y-m-d');

        foreach ($tasks as $task) {
            if ($task->isCompleted()) {
                continue; // Les terminées sont exclues de ce groupement
            }

            $dueDate = $task->getDueDate();
            if (!$dueDate) {
                $groups['later']['tasks'][] = $task;
                continue;
            }

            // On formate la date de la tâche pour la comparaison
            $dueStr = $dueDate->format('Y-m-d');

            if ($dueStr < $todayStr) {
                $groups['overdue']['tasks'][] = $task;
            } elseif ($dueStr === $todayStr) {
                $groups['today']['tasks'][] = $task;
            } elseif ($dueStr === $tomorrowStr) {
                $groups['tomorrow']['tasks'][] = $task;
            } elseif ($dueStr <= $endOfWeekStr) {
                $groups['this_week']['tasks'][] = $task;
            } else {
                $groups['later']['tasks'][] = $task;
            }
        }

        // Tri interne de chaque groupe par échéance (le plus urgent en haut)
        // Ici on conserve l'objet DateTime complet pour trier précisément si deux tâches sont le même jour
        foreach ($groups as &$group) {
            usort($group['tasks'], function (Task $a, Task $b) {
                $dateA = $a->getDueDate();
                $dateB = $b->getDueDate();
                if ($dateA && $dateB) {
                    return $dateA <=> $dateB;
                }
                return 0;
            });
        }

        // On ne retourne que les sections qui ont au moins 1 tâche
        return array_filter($groups, fn($g) => count($g['tasks']) > 0);
    }
    /**
     * Récupère uniquement les tâches terminées (à part)
     */
    private function getCompletedTasks(array $tasks): array
    {
        $completed = array_filter($tasks, fn(Task $task) => $task->isCompleted());

        // Optionnel : on peut trier les tâches terminées de la plus récemment achevée à la plus ancienne
        usort($completed, function (Task $a, Task $b) {
            if (method_exists($a, 'getCompletedAt') && method_exists($b, 'getCompletedAt')) {
                return $b->getCompletedAt() <=> $a->getCompletedAt();
            }
            return 0;
        });

        return $completed;
    }

    public function getUserTasks(User $user): array
    {
        return $this->repository->findBy(['owner' => $user], ['title' => 'ASC']);
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    public function getCurrentUserTasks(): array
    {
        $user = $this->getCurrentUser();
        return $user ? $this->getUserTasks($user) : [];
    }

    public function breadcrumb(array $items = []): Breadcrumb
    {
        return new Breadcrumb([
            new BreadcrumbItem(name: 'Task List', link: $this->urlGenerator->generate('app_task_manage')),
            ...$items
        ]);
    }
}