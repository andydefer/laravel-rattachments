<?php

declare(strict_types=1);

namespace AndyDefer\LaravelRattachments\Tests\Fixtures\Enums;

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
    case CHIEF = 'chief';
    case FRIEND = 'friend';
    case BEST_FRIEND = 'best_friend';
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case AUTHOR = 'author';
    case EDITOR = 'editor';
    case REVIEWER = 'reviewer';
    case CONTRIBUTOR = 'contributor';
    case GUEST = 'guest';
    case MEMBER = 'member';
    case MODERATOR = 'moderator';
    case OWNER = 'owner';
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case BLOCKED = 'blocked';
    case FOLLOWER = 'follower';
    case FOLLOWING = 'following';
    case REPORTED = 'reported';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

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
            self::CHIEF => 'Chef',
            self::FRIEND => 'Ami',
            self::BEST_FRIEND => 'Meilleur ami',
            self::PRIMARY => 'Primaire',
            self::SECONDARY => 'Secondaire',
            self::AUTHOR => 'Auteur',
            self::EDITOR => 'Éditeur',
            self::REVIEWER => 'Relecteur',
            self::CONTRIBUTOR => 'Contributeur',
            self::GUEST => 'Invité',
            self::MEMBER => 'Membre',
            self::MODERATOR => 'Modérateur',
            self::OWNER => 'Propriétaire',
            self::PENDING => 'En attente',
            self::ACCEPTED => 'Accepté',
            self::DECLINED => 'Refusé',
            self::BLOCKED => 'Bloqué',
            self::FOLLOWER => 'Abonné',
            self::FOLLOWING => 'Abonnement',
            self::REPORTED => 'Signalé',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté',
        };
    }
}
