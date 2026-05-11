<?php

namespace App\Enum\Task;
enum TaskPriorityEnum: string
{
    case Low = 'low'; // Default value for new tasks , aucun
    case Medium = 'medium'; // bg-info text-white
    case High = 'high'; // bg-warning text-white
    case Critical = 'critical'; // vz-danger

    /**
     * @param self|string|null $enum Valeur de l'enum ou string à convertir (optionnel)
     * @return array|string|null La map complète ou la valeur correspondante à l'enum fourni, ou null si non trouvé
     */
    public static function getMap(self|string|null $enum = null): array|string|null
    {
        $options = [
            self::Low->value => 'Faible',
            self::Medium->value => 'Moyenne',
            self::High->value => 'Haute',
            self::Critical->value => 'Critique',
        ];

        if (!is_null($enum)) {
            if (is_string($enum)) {
                $enum = self::tryFrom($enum);
            }

            return $options[$enum->value] ?? null;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return array_reduce(static::cases(), fn($carry, $i) => [...$carry, $i->value => self::getMap($i)[$i->value] ?? null], []);
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_reduce(static::cases(), fn($carry, $i) => [...$carry, $i->value], []);
    }
}