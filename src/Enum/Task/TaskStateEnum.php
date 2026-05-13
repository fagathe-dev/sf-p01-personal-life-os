<?php

namespace App\Enum\Task;
enum TaskStateEnum: string
{
    case Todo = 'todo'; // Default value for new tasks
    case InProgress = 'in-progress';
    case Done = 'done';

    /**
     * Retourne la map des valeurs de l'enum ou une valeur spécifique si fournie
     * 
     * @param self|string|null $enum Valeur de l'enum ou string à convertir (optionnel)
     * @return array|string|null La map complète ou la valeur correspondante à l'enum fourni, ou null si non trouvé
     */
    public static function getMap(self|string|null $enum = null): array|string|null
    {
        $options = [
            self::Todo->value => 'À faire',
            self::InProgress->value => 'En cours',
            self::Done->value => 'Terminé',
        ];

        if (!is_null($enum)) {
            if (is_string($enum)) {
                $enum = self::tryFrom($enum);
            }

            return $options[$enum->value] ?? null;
        }


        return $options;
    }

    public static function label(self $enum): string
    {
        return self::getMap($enum) ?? $enum->value;
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return array_reduce(static::cases(), fn($carry, $i) => [...$carry, $i->value => self::getMap($i)[$i->value] ?? null] ?? null, []);
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_reduce(static::cases(), fn($carry, $i) => [...$carry, $i->value], []);
    }
}