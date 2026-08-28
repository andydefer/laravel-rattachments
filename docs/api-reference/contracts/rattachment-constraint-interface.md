# RattachmentInterface - Référence Technique

## Description

Interface optionnelle pour les modèles Eloquent qui définissent des contraintes de rattachement. Permet de contrôler quels types de modèles peuvent être rattachés, avec quels rôles, et d'imposer des restrictions uniques ou des interdictions.

## Hiérarchie / Implémentations

```
RattachmentInterface
    └── Model (Eloquent)
```

**Implémentations typiques :**
- `App\Models\User`
- `App\Models\Hospital`
- `App\Models\Pharmacy`

## Rôle principal

L'interface définit trois types de contraintes :

1. **`allowedTargets()`** : Types de cibles autorisées avec leurs rôles
2. **`uniqueTargets()`** : Types de cibles uniques (un seul rattachement par type)
3. **`disallowedTargets()`** : Types de cibles interdites (prioritaire sur `allowedTargets()`)

---

## API

### `allowedTargets(): array`

Définit les types de modèles autorisés à être rattachés et les rôles possibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `array<string, array<int, EnumerableInterface>>` - Tableau associatif où la clé est le FQCN de la cible et la valeur un tableau de rôles autorisés

**Exemple :**
```php
public function allowedTargets(): array
{
    return [
        Hospital::class => [Role::DOCTOR, Role::STAFF, Role::ADMIN],
        Pharmacy::class => [Role::PHARMACIST, Role::STAFF],
    ];
}
```

---

### `uniqueTargets(): array`

Définit les types de cibles pour lesquels un modèle ne peut avoir qu'un seul rattachement.

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `array<int, string>` - Tableau de FQCNs des cibles uniques

**Exemple :**
```php
public function uniqueTargets(): array
{
    return [
        User::class,     // Un hôpital ne peut avoir qu'un seul directeur
        Pharmacy::class, // Un hôpital ne peut avoir qu'une seule pharmacie principale
    ];
}
```

---

### `disallowedTargets(): array`

Définit les types de cibles interdites. **Cette restriction est prioritaire sur `allowedTargets()`.**

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `array<int, string>` - Tableau de FQCNs des cibles interdites

**Exemple :**
```php
public function disallowedTargets(): array
{
    return [
        Specialty::class, // Un hôpital ne peut pas être rattaché à une spécialité
        Clinic::class,    // Un hôpital ne peut pas être rattaché à une clinique
    ];
}
```

---

## Cas d'utilisation

### Cas 1 : Hôpital avec contraintes complètes

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class Hospital extends Model implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            User::class => [Role::DOCTOR, Role::STAFF, Role::ADMIN],
            Pharmacy::class => [Role::SUPPLIER],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            User::class,     // Un seul directeur
            Pharmacy::class, // Une seule pharmacie principale
        ];
    }

    public function disallowedTargets(): array
    {
        return [
            Specialty::class, // Pas de rattachement direct aux spécialités
            Clinic::class,    // Pas de rattachement aux cliniques
        ];
    }
}
```

**Résultat :**
```php
// ✅ OK - Rattachement à un User avec rôle DOCTOR
$service->attach($hospital, $user, Role::DOCTOR);

// ❌ ERREUR - Rattachement à une Specialty (interdit)
$service->attach($hospital, $specialty, Role::HAS_SPECIALTY);
// RuntimeException: Hospital cannot be attached to Specialty. This target is disallowed.

// ✅ OK - Premier rattachement à un User
$service->attach($hospital, $user1, Role::ADMIN);

// ❌ ERREUR - Deuxième rattachement à un User (contrainte unique)
$service->attach($hospital, $user2, Role::ADMIN);
// RuntimeException: Hospital already has a unique attachment to User. Only one User is allowed.
```

### Cas 2 : Utilisateur avec contraintes

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class User extends Model implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Hospital::class => [Role::DOCTOR, Role::STAFF],
            Pharmacy::class => [Role::PHARMACIST],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            Hospital::class, // Un utilisateur ne peut être dans qu'un seul hôpital principal
        ];
    }

    public function disallowedTargets(): array
    {
        return [
            Specialty::class,
        ];
    }
}
```

### Cas 3 : Médecin avec contraintes spécifiques

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class Doctor extends Model implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Hospital::class => [Role::DOCTOR, Role::SPECIALIST],
            Patient::class => [Role::DOCTOR_OF],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            Hospital::class, // Un médecin ne peut être attaché qu'à un seul hôpital principal
        ];
    }

    public function disallowedTargets(): array
    {
        return [
            Pharmacy::class, // Un médecin ne peut pas être rattaché à une pharmacie
            Specialty::class,
        ];
    }
}
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Target non autorisé | `RuntimeException` | `{rattachable} cannot be attached to {target}. Allowed targets: {allowed_targets}` |
| Target interdit | `RuntimeException` | `{rattachable} cannot be attached to {target}. This target is disallowed.` |
| Conflit de contraintes | `RuntimeException` | `Constraint conflict in {rattachable}: The following targets are both allowed and disallowed: {conflicts}` |
| Rôle non autorisé | `RuntimeException` | `Role "{role}" is not allowed for {rattachable} -> {target}. Allowed roles: {allowed_roles}` |
| Contrainte unique violée | `RuntimeException` | `{rattachable} already has a unique attachment to {targetClass}. Only one {targetClass} is allowed.` |

---

## Ordre de priorité des contraintes

```
1. disallowedTargets()  ← PRIORITÉ MAXIMALE
2. allowedTargets()
3. uniqueTargets()
```

**Règle :** Si un target est dans `disallowedTargets()`, il est bloqué même s'il est aussi dans `allowedTargets()`.

---

## Performance

- **Toutes les méthodes** : O(1) - retournent simplement un tableau pré-défini
- **Validation des disallowed targets** : O(n) - vérification dans le tableau
- **Aucun impact sur les performances** : Les contraintes sont vérifiées avant l'écriture en base

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.2+ | ✅ Complet |
| PHP 8.1 | ✅ Complet |

## Exemple complet

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class User extends Model implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Hospital::class => [Role::DOCTOR, Role::STAFF, Role::ADMIN],
            Pharmacy::class => [Role::PHARMACIST, Role::STAFF],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            Hospital::class, // Un utilisateur ne peut être dans qu'un seul hôpital principal
        ];
    }

    public function disallowedTargets(): array
    {
        return [
            Specialty::class,
            Clinic::class,
        ];
    }
}

// Utilisation avec le service
$user = User::find(1);
$hospital = Hospital::find(1);
$specialty = Specialty::find(1);

// ✅ OK
$service->attach($user, $hospital, Role::DOCTOR);

// ❌ ERREUR - Target interdit
$service->attach($user, $specialty, Role::HAS_SPECIALTY);
// RuntimeException: User cannot be attached to Specialty. This target is disallowed.

// ❌ ERREUR - Rôle non autorisé
$service->attach($user, $hospital, Role::PHARMACIST);
// RuntimeException: Role "pharmacist" is not allowed for User -> Hospital. Allowed roles: doctor, staff, admin

// ❌ ERREUR - Contrainte unique
$service->attach($user, $hospital2, Role::DOCTOR);
// RuntimeException: User already has a unique attachment to Hospital. Only one Hospital is allowed.
```

---

## Voir aussi

- `RattachmentService` - Service de rattachement
- `Rattachment` - Modèle du rattachement
- `Role` - Énumération des rôles par défaut
- `HasRattachments` - Trait pour les modèles