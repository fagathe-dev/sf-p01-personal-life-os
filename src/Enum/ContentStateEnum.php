<?php

namespace App\Enum;
enum ContentStateEnum: string
{
    case Open = 'open'; // Default value for new content (drive & note only)
    case Archive = 'archive';
    case Trash = 'trash'; // 

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