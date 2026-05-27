<?php

namespace App\Twig;

use DateTime;
use DateTimeInterface;
use Fagathe\CorePhp\Trait\DatetimeTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateExtension extends AbstractExtension
{
    // On importe toute l'intelligence de ton trait ici
    use DatetimeTrait;

    public function getFilters(): array
    {
        return [
            // On pointe vers des méthodes "wrapper" pour gérer la conversion
            new TwigFilter('ago', [$this, 'formatAgo']),
            new TwigFilter('human_datetime', [$this, 'formatHumanDatetime']),
        ];
    }

    /**
     * @param DateTimeInterface|null $dateTime
     * 
     * @return string|null
     */
    public function formatAgo(?DateTimeInterface $dateTime): ?string
    {
        if (!$dateTime) {
            return null;
        }

        // On s'assure d'avoir un objet Immutable pour ton trait
        $immutable = $dateTime instanceof DateTime ? $this->createFromMutable($dateTime) : $dateTime;

        // Appel direct à la méthode ago() du DatetimeTrait
        return $this->ago($immutable);
    }

    /**
     * @param DateTimeInterface|null $dateTime
     * 
     * @return string|null
     */
    public function formatHumanDatetime(?DateTimeInterface $dateTime): ?string
    {
        if (!$dateTime) {
            return null;
        }

        // On s'assure d'avoir un objet Immutable pour ton trait
        $immutable = $dateTime instanceof DateTime ? $this->createFromMutable($dateTime) : $dateTime;

        // Appel direct à la méthode formatHumanDueDate() du DatetimeTrait
        return \IntlDateFormatter::formatObject($immutable, 'EEE d MMM', 'fr_FR');
    }
}
