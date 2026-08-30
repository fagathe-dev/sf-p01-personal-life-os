<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TaskExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('task_due_status', [$this, 'getTaskDueStatus']),
        ];
    }

    public function getTaskDueStatus(?\DateTimeInterface $dueDate): ?array
    {
        if (!$dueDate) {
            return null;
        }

        $todayStr     = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $tomorrowStr  = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');
        $endOfWeekStr = (new \DateTimeImmutable('sunday this week'))->format('Y-m-d');

        $dueStr = $dueDate->format('Y-m-d');

        if ($dueStr < $todayStr) {
            return ['label' => 'En retard', 'color' => 'danger', 'icon' => 'bx-error-circle'];
        }
        if ($dueStr === $todayStr) {
            return ['label' => 'Aujourd\'hui', 'color' => 'warning', 'icon' => 'bx-sun'];
        }
        if ($dueStr === $tomorrowStr) {
            return ['label' => 'Demain', 'color' => 'info', 'icon' => 'bx-calendar-event'];
        }
        if ($dueStr <= $endOfWeekStr) {
            return ['label' => 'Cette semaine', 'color' => 'primary', 'icon' => 'bx-calendar-week'];
        }
        
        return ['label' => 'Plus tard', 'color' => 'secondary', 'icon' => 'bx-archive'];
    }
}