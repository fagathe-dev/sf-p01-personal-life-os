<?php

namespace App\Enum\Task;

use DateTimeImmutable;

enum TaskDueDateEnum: string
{
    case Today = 'today';
    case Tomorrow = 'tomorrow';
    case ThisWeek = 'this_week';
    case Later = 'later';

    /**
     * Retourne la map des valeurs de l'enum ou une valeur spécifique si fournie
     * 
     * @param self|string|null $enum Valeur de l'enum ou string à convertir (optionnel)
     * @return array|string|null La map complète ou la valeur correspondante à l'enum fourni, ou null si non trouvé
     */
    public static function getMap(self|string|null $enum = null): array|string|null
    {
        $options = [
            self::Today->value => 'Aujourd\'hui',
            self::Tomorrow->value => 'Demain',
            self::ThisWeek->value => 'Cette semaine',
            self::Later->value => 'Plus tard',
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

    /**
     * @param string|self|null $dueDate
     * * @return DateTimeImmutable|null
     */
    public static function generateDatetimeFromEnum(string|self|null $dueDate): ?DateTimeImmutable
    {
        if (is_null($dueDate)) {
            return null;
        }

        if (is_string($dueDate)) {
            $dueDate = static::tryFrom($dueDate);
            if ($dueDate === null) {
                return null;
            }
        }

        // Utilisation du langage naturel PHP pour cibler la fin de la semaine
        $mutator = match ($dueDate) {
            self::Today => 'now',
            self::Tomorrow => '+1 day',
            self::ThisWeek => 'sunday this week', // Dimanche de la semaine en cours
            self::Later => '+2 days',
        };

        $dateTime = new DateTimeImmutable($mutator, new \DateTimeZone('Europe/Paris'));

        return $dateTime ? $dateTime->setTime(23, 59, 59) : null;
    }
}