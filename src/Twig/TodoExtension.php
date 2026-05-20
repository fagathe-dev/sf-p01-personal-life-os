<?php

namespace App\Twig;

use App\Enum\Task\TaskPriorityEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Fagathe\CorePhp\Enum\HumanDueDateEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TodoExtension extends AbstractExtension
{
    use DatetimeTrait;
    public function getFilters(): array
    {
        return [
            // is_safe permet de renvoyer du HTML interprétable
            new TwigFilter('todo_priority_badge', [$this, 'getPriorityBadge'], ['is_safe' => ['html']]),
            new TwigFilter('todo_due_date_badge', [$this, 'getDueDateBadge'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param TaskPriorityEnum|string|null $priority
     * 
     * @return string
     */
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

    /**
     * @param DateTimeInterface|DateTimeImmutable|null $dueDate
     * 
     * @return string
     */
    public function getDueDateBadge(DateTimeInterface|DateTimeImmutable|null $dueDate): string
    {
        if (!$dueDate) {
            return '';
        }

        $humanReadableDueDate = $this->formatHumanDueDate($dueDate);

        $labelText = match ($humanReadableDueDate) {
            HumanDueDateEnum::Later => \IntlDateFormatter::formatObject($dueDate, 'EEE d MMM', 'fr_FR'),
            default => HumanDueDateEnum::label($humanReadableDueDate) ?? 'Inconnu',
        };

        $textColor = match ($humanReadableDueDate) {
            HumanDueDateEnum::Overdue,HumanDueDateEnum::Today => 'danger',
            HumanDueDateEnum::Tomorrow => 'warning',
            default => 'muted',
        };

        return sprintf(
            '<span class="text-%s" style="font-size: 12px;"><i class="ri-calendar-event-line align-bottom me-1"></i>%s</span></span>',
            $textColor,
            $labelText
        );
    }
}