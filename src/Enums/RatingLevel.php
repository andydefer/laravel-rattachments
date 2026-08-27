<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Enums;

enum RatingLevel: int
{
    case ONE = 1;
    case TWO = 2;
    case THREE = 3;
    case FOUR = 4;
    case FIVE = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::ONE => 'Très mauvais',
            self::TWO => 'Mauvais',
            self::THREE => 'Moyen',
            self::FOUR => 'Bien',
            self::FIVE => 'Excellent',
        };
    }

    public function getStars(): string
    {
        return str_repeat('⭐', $this->value);
    }

    public function getPercentage(): int
    {
        return (int) round(($this->value / 5) * 100);
    }
}
