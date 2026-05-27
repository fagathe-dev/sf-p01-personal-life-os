<?php

namespace App\Twig;

use App\Enum\Task\TaskPriorityEnum;
use DateTimeImmutable;
use DateTimeInterface;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Fagathe\CorePhp\Enum\HumanDueDateEnum;
use Fagathe\CorePhp\File\FileSizeFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    use DatetimeTrait;
    public function getFilters(): array
    {
        return [
            // is_safe permet de renvoyer du HTML interprétable
            new TwigFilter('todo_priority_badge', [$this, 'getPriorityBadge'], ['is_safe' => ['html']]),
            new TwigFilter('todo_due_date_badge', [$this, 'getDueDateBadge'], ['is_safe' => ['html']]),
            new TwigFilter('file_formatted_size', [$this, 'getFileFormattedSize'], ['is_safe' => ['html']]),
            new TwigFilter('file_icon', [$this, 'getFileIcon']),
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
            HumanDueDateEnum::Overdue, HumanDueDateEnum::Today => 'danger',
            HumanDueDateEnum::Tomorrow => 'warning',
            default => 'muted',
        };

        return sprintf(
            '<span class="text-%s" style="font-size: 12px;"><i class="ri-calendar-event-line align-bottom me-1"></i>%s</span></span>',
            $textColor,
            $labelText
        );
    }

    public function getFileFormattedSize(?int $size): string
    {
        return FileSizeFormatter::formatFileSize($size);
    }
    
    /**
     * Retourne un tableau avec la classe de l'icône et sa couleur en fonction de l'extension
     * @param string|null $extension
     * @return array{icon: string, color: string}
     */
    public function getFileIcon(?string $extension): array
    {
        $extension = strtolower((string) $extension);

        $map = [
            'pdf'  => ['icon' => 'ri-file-pdf-line', 'color' => 'danger'],
            'doc'  => ['icon' => 'ri-file-word-line', 'color' => 'primary'],
            'docx' => ['icon' => 'ri-file-word-line', 'color' => 'primary'],
            'xls'  => ['icon' => 'ri-file-excel-line', 'color' => 'success'],
            'xlsx' => ['icon' => 'ri-file-excel-line', 'color' => 'success'],
            'csv'  => ['icon' => 'ri-file-list-3-line', 'color' => 'success'],
            'ppt'  => ['icon' => 'ri-file-ppt-line', 'color' => 'warning'],
            'pptx' => ['icon' => 'ri-file-ppt-line', 'color' => 'warning'],
            'jpg'  => ['icon' => 'ri-image-line', 'color' => 'info'],
            'jpeg' => ['icon' => 'ri-image-line', 'color' => 'info'],
            'png'  => ['icon' => 'ri-image-line', 'color' => 'info'],
            'gif'  => ['icon' => 'ri-file-gif-line', 'color' => 'info'],
            'svg'  => ['icon' => 'ri-image-line', 'color' => 'info'],
            'webp' => ['icon' => 'ri-image-line', 'color' => 'info'],
            'mp4'  => ['icon' => 'ri-video-line', 'color' => 'purple'],
            'avi'  => ['icon' => 'ri-video-line', 'color' => 'purple'],
            'mov'  => ['icon' => 'ri-video-line', 'color' => 'purple'],
            'mp3'  => ['icon' => 'ri-file-music-line', 'color' => 'secondary'],
            'wav'  => ['icon' => 'ri-file-music-line', 'color' => 'secondary'],
            'zip'  => ['icon' => 'ri-file-zip-line', 'color' => 'dark'],
            'rar'  => ['icon' => 'ri-file-zip-line', 'color' => 'dark'],
            '7z'   => ['icon' => 'ri-file-zip-line', 'color' => 'dark'],
            'txt'  => ['icon' => 'ri-file-text-line', 'color' => 'muted'],
            'json' => ['icon' => 'ri-file-code-line', 'color' => 'primary'],
            'js'   => ['icon' => 'ri-file-code-line', 'color' => 'warning'],
            'ts'   => ['icon' => 'ri-file-code-line', 'color' => 'primary'],
            'html' => ['icon' => 'ri-html5-line', 'color' => 'danger'],
            'css'  => ['icon' => 'ri-css3-line', 'color' => 'info'],
            'php'  => ['icon' => 'ri-file-code-line', 'color' => 'purple'],
        ];

        return $map[$extension] ?? ['icon' => 'ri-file-line', 'color' => 'muted'];
    }
}