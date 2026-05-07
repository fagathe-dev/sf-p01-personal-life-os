<?php

namespace App\Enum\Task;
enum TaskStateEnum: string
{
    case Todo = 'todo'; // Default value for new tasks
    case InProgress = 'in-progress';
    case Done = 'done';

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return array_reduce(static::cases(), fn($carry, $i) => [...$carry, $i->value => $i->value], []);
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_reduce(static::cases(), fn($carry, $i) => [...$carry, $i->value], []);
    }
}