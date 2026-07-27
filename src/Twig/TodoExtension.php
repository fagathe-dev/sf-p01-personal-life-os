<?php

namespace App\Twig;

use App\Enum\Task\TaskPriorityEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TodoExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // is_safe permet de renvoyer du HTML interprétable
            new TwigFilter('todo_priority_badge', [$this, 'getPriorityBadge'], ['is_safe' => ['html']]),
        ];
    }

    public function getPriorityBadge(TaskPriorityEnum|string|null $priority): string
    {
        if (!$priority) {
            return '';
        }

        // Si la priorité est passée sous forme de string, on la convertit en Enum
        if (is_string($priority)) {
            $priority = TaskPriorityEnum::tryFrom($priority);
        }

        if (!$priority instanceof TaskPriorityEnum) {
            return '';
        }

        $color = match ($priority) {
            TaskPriorityEnum::Low => 'success',
            TaskPriorityEnum::Medium => 'info', // J'ai choisi Info pour bien différencier du Primary global
            TaskPriorityEnum::High => 'warning',
            TaskPriorityEnum::Critical => 'danger',
        };

        $label = TaskPriorityEnum::getMap($priority);

        return sprintf(
            '<span class="badge bg-%s-subtle text-%s ms-2" style="font-size: 10px;">%s</span>',
            $color,
            $color,
            $label
        );
    }
}