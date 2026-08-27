<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Enums;

use AndyDefer\Repository\Contracts\EnumerableInterface;

enum Role: string implements EnumerableInterface
{
    case DOCTOR = 'doctor';
    case PHARMACIST = 'pharmacist';
    case STAFF = 'staff';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case NURSE = 'nurse';
    case SPECIALIST = 'specialist';
    case VOLUNTEER = 'volunteer';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DOCTOR => 'Médecin',
            self::PHARMACIST => 'Pharmacien',
            self::STAFF => 'Personnel',
            self::ADMIN => 'Administrateur',
            self::MANAGER => 'Gestionnaire',
            self::NURSE => 'Infirmier',
            self::SPECIALIST => 'Spécialiste',
            self::VOLUNTEER => 'Bénévole',
        };
    }
}
