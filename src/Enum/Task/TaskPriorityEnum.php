<?php

namespace App\Enum\Task;
enum TaskPriorityEnum: string
{
    case Low = 'low'; // Default value for new tasks
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

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