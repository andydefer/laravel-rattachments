# Laravel Rattachments

> Système de rattachement polymorphique double pour applications Laravel

Un package Laravel complet pour gérer des relations polymorphiques doubles entre n'importe quels modèles Eloquent, avec des rôles configurables, des métadonnées, un système de contraintes et des contraintes uniques.

---

## 📋 Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Extensibilité](#extensibilité)
- [Utilisation avec le service](#utilisation-avec-le-service)
- [Utilisation avec le trait](#utilisation-avec-le-trait)
- [Rôles par défaut](#rôles-par-défaut)
- [Système de contraintes](#système-de-contraintes)
- [Référence de l'API](#référence-de-lapi)
- [Value Objects](#value-objects)
- [Structure de la base de données](#structure-de-la-base-de-données)
- [Tests](#tests)
- [Contribuer](#contribuer)
- [Licence](#licence)

---

## ✨ Fonctionnalités

- ✅ **Double polymorphisme** - Rattachez n'importe quel modèle à n'importe quel autre modèle
- ✅ **Rôles configurables** - Définissez vos propres rôles via des énumérations
- ✅ **Système de contraintes** - Limitez les rattachements possibles par modèle et rôle
- ✅ **Contraintes uniques** - Limitez un modèle à un seul rattachement par type de cible
- ✅ **Métadonnées flexibles** - Stockez des données supplémentaires au format JSON
- ✅ **Trait HasRattachments** - API fluide directement dans vos modèles
- ✅ **Filtrage avancé** - Filtrer par type, rôle, ou combinaison des deux
- ✅ **Pattern Repository** - Séparation propre de la logique d'accès aux données
- ✅ **Support des DTOs** - Objets de transfert de données typés
- ✅ **Value Objects** - Métadonnées typées avec `StrictDataObject`
- ✅ **Enum Casts** - Conversion automatique entre base de données et énumérations PHP
- ✅ **Opérations en masse** - Rattachement et détachement multiples
- ✅ **Synchronisation** - Synchronisez tous les rattachements d'un modèle en une seule opération
- ✅ **Pagination** - Récupérez les résultats paginés
- ✅ **Rôle optionnel** - Les rattachements peuvent être créés sans rôle
- ✅ **Tests complets** - Couverture complète des tests d'intégration

---

## 🚀 Prérequis

- PHP 8.2 ou supérieur
- Laravel 12.0, 13.0, 14.0 ou 15.0

---

## 📦 Installation

Installez le package via Composer :

```bash
composer require andydefer/laravel-rattachments
```

### Publier les migrations

```bash
php artisan vendor:publish --tag=rattachments-migrations
```

### Exécuter les migrations

```bash
php artisan migrate
```

---

## ⚙️ Configuration

### Service Provider

Le package est automatiquement découvert par Laravel. Aucune configuration supplémentaire n'est requise.

### Configuration des Enum Casts

Le package utilise le système d'`EnumCast` du package `andydefer/laravel-repository` pour convertir automatiquement les valeurs en énumérations PHP.

Créez ou modifiez le fichier `config/repository.php` :

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enum Casts
    |--------------------------------------------------------------------------
    |
    | Define enum casts for specific tables and columns.
    | Each entry maps a table name and column to an enum class.
    |
    | The enum class must implement EnumerableInterface.
    |
    */
    'enum_casts' => [
        'rattachments' => [
            'role' => App\Enums\CustomRole::class, // Votre enum personnalisé
        ],
    ],
];
```

> **⚠️ Important** : 
> - Sans cette configuration, les énumérations ne seront pas automatiquement converties
> - L'énumération **DOIT** implémenter l'interface `AndyDefer\Repository\Contracts\EnumerableInterface`
> - La méthode `getValue()` est obligatoire pour l'interface

---

## 🔧 Extensibilité

### Créer vos rôles personnalisés

Le package est conçu pour être extensible. Vous devez créer votre propre enum qui implémente `EnumerableInterface`.

> **⚠️ OBLIGATOIRE :** Vos énumérations DOIVENT implémenter l'interface `EnumerableInterface`

#### 1. Créer votre enum

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use AndyDefer\Repository\Contracts\EnumerableInterface;

enum CustomRole: string implements EnumerableInterface  // ⚠️ Interface obligatoire
{
    case DOCTOR = 'doctor';
    case PHARMACIST = 'pharmacist';
    case STAFF = 'staff';
    case ADMIN = 'admin';
    case SUPPLIER = 'supplier';
    // Ajoutez autant de cas que vous voulez !

    /**
     * Obligatoire - Retourne la valeur brute de l'énumération
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Optionnel - Méthode utilitaire pour l'affichage
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DOCTOR => 'Médecin',
            self::PHARMACIST => 'Pharmacien',
            self::STAFF => 'Personnel',
            self::ADMIN => 'Administrateur',
            self::SUPPLIER => 'Fournisseur',
        };
    }
}
```

#### 2. Configurer l'enum dans le repository

```php
// config/repository.php
'enum_casts' => [
    'rattachments' => [
        'role' => App\Enums\CustomRole::class,
    ],
],
```

#### 3. Utiliser vos rôles

```php
use App\Enums\CustomRole;

// Rattacher avec votre rôle
$attachment = $service->attach($user, $hospital, CustomRole::DOCTOR);

// Rattacher sans rôle
$attachment = $service->attach($user, $post, null, ['type' => 'author']);

// Récupérer par rôle
$doctors = $service->getRattachablesByRole($hospital, CustomRole::DOCTOR);

// Mettre à jour le rôle
$service->updateRole($user, $hospital, CustomRole::ADMIN);
```

---

## 📖 Utilisation avec le service

### Créer un rattachement

```php
use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Enums\Role;

class DoctorController extends Controller
{
    public function attachToHospital(RattachmentService $service, Hospital $hospital)
    {
        $doctor = auth()->user();

        // Rattachement avec rôle
        $attachment = $service->attach(
            $doctor,                // Modèle à rattacher
            $hospital,              // Modèle cible
            Role::DOCTOR,           // Rôle du rattachement
            [                       // Métadonnées (optionnel)
                'consultation_days' => ['monday', 'wednesday', 'friday'],
                'consultation_hours' => '09:00-17:00',
            ]
        );

        return response()->json([
            'message' => 'Docteur rattaché à l\'hôpital',
            'attachment' => $attachment,
        ]);
    }
}
```

### Rattachement multiple

```php
// Rattacher plusieurs modèles à une même cible
$attachments = $service->attachMultiple(
    collect([$doctor1, $doctor2, $doctor3]),
    $hospital,
    Role::DOCTOR,
    ['department' => 'Cardiology']
);

// Rattacher un modèle à plusieurs cibles
$attachments = $service->attachToMultiple(
    $doctor,
    collect([$hospital1, $hospital2, $hospital3]),
    Role::DOCTOR
);
```

### Supprimer un rattachement

```php
// Supprimer un rattachement spécifique
$service->detach($doctor, $hospital);

// Supprimer tous les rattachements d'un modèle
$service->detachAll($doctor);
```

### Vérifier un rattachement

```php
// Vérifier si un modèle est rattaché à un autre
$isAttached = $service->isAttached($doctor, $hospital);

// Vérifier si un rôle existe pour une cible
$hasDoctors = $service->hasRoleAttached($hospital, Role::DOCTOR);
```

### Récupérer les rattachements

```php
// Récupérer tous les modèles rattachés à une cible
$doctors = $service->getRattachables($hospital);

// Récupérer les modèles rattachés avec un rôle spécifique
$doctors = $service->getRattachablesByRole($hospital, Role::DOCTOR);

// Récupérer toutes les cibles d'un modèle
$hospitals = $service->getTargets($doctor);

// Récupérer les cibles d'un type spécifique
$hospitals = $service->getTargetsByType($doctor, Hospital::class);

// Récupérer les cibles par type et rôle
$hospitals = $service->getTargetsByTypeAndRole($doctor, Hospital::class, Role::DOCTOR);

// Récupérer les cibles de plusieurs types avec plusieurs rôles
$targets = $service->getTargetsByTypesAndRoles(
    $doctor,
    [Hospital::class, Pharmacy::class],
    [Role::DOCTOR, Role::PHARMACIST]
);
```

### Mettre à jour un rattachement

```php
// Mettre à jour le rôle
$service->updateRole($doctor, $hospital, Role::ADMIN);

// Mettre à jour les métadonnées
$service->updateMetadata($doctor, $hospital, [
    'consultation_hours' => '10:00-18:00',
]);

// Fusionner les métadonnées (conserve les existantes)
$service->mergeMetadata($doctor, $hospital, [
    'availability' => 'Monday-Friday',
]);
```

### Synchroniser les rattachements

```php
// Synchronise tous les rattachements d'un médecin
$attachments = $service->syncAttachments($doctor, [
    [
        'target' => $hospital1,
        'role' => Role::DOCTOR,
        'metadata' => ['primary' => true],
    ],
    [
        'target' => $hospital2,
        'role' => Role::DOCTOR,
    ],
    [
        'target' => $pharmacy,
        'role' => Role::PHARMACIST,
    ],
]);
```

---

## 📖 Utilisation avec le trait

Le package fournit un trait `HasRattachments` qui permet une API fluide directement dans vos modèles.

### Ajouter le trait à votre modèle

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use Illuminate\Database\Eloquent\Model;

final class Doctor extends Model
{
    use HasRattachments;
}
```

### Utilisation

```php
$doctor = Doctor::find(1);
$hospital = Hospital::find(1);

// Rattacher
$doctor->attachTo($hospital, Role::DOCTOR, [
    'consultation_days' => ['monday', 'wednesday', 'friday'],
]);

// Vérifier
if ($doctor->isAttachedTo($hospital)) {
    // ...
}

// Récupérer les cibles
$hospitals = $doctor->getTargets();
$hospitals = $doctor->getTargetsByRole(Role::DOCTOR);
$hospitals = $doctor->getTargetsByType(Hospital::class);
$hospitals = $doctor->getTargetsByTypeAndRole(Hospital::class, Role::DOCTOR);

// Compter
$count = $doctor->countTargetsByRole(Role::DOCTOR);

// Mettre à jour
$doctor->updateRoleFor($hospital, Role::ADMIN);
$doctor->mergeMetadataFor($hospital, ['primary' => true]);

// Synchroniser
$doctor->syncAttachments([
    ['target' => Hospital::find(1), 'role' => Role::DOCTOR],
    ['target' => Hospital::find(2), 'role' => Role::DOCTOR],
]);

// Détacher
$doctor->detachFrom($hospital);
$doctor->detachAll();
```

---

## 🏷️ Rôles par défaut

Le package fournit un enum par défaut, mais vous êtes libre de le remplacer par le vôtre.

| Type | Valeur | Label |
|------|--------|-------|
| `Role::DOCTOR` | `'doctor'` | Médecin |
| `Role::PHARMACIST` | `'pharmacist'` | Pharmacien |
| `Role::STAFF` | `'staff'` | Personnel |
| `Role::ADMIN` | `'admin'` | Administrateur |
| `Role::MANAGER` | `'manager'` | Gestionnaire |
| `Role::NURSE` | `'nurse'` | Infirmier |
| `Role::SPECIALIST` | `'specialist'` | Spécialiste |
| `Role::VOLUNTEER` | `'volunteer'` | Bénévole |

> **💡 Conseil** : Vous pouvez ajouter, supprimer ou modifier ces rôles en créant votre propre enum comme expliqué dans la section [Extensibilité](#extensibilité).

---

## 🔒 Système de contraintes

Le package offre deux types de contraintes pour sécuriser les rattachements.

### Contraintes de cibles autorisées

Permet de définir quels types de modèles peuvent être rattachés et avec quels rôles.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class User extends Model implements RattachmentConstraintsInterface
{
    public function allowedTargets(): array
    {
        return [
            Hospital::class => [Role::DOCTOR, Role::STAFF, Role::ADMIN],
            Pharmacy::class => [Role::PHARMACIST, Role::STAFF],
        ];
    }
}
```

**Résultat :**
```php
// ✅ OK
$service->attach($user, $hospital, Role::DOCTOR);

// ❌ ERREUR - Rôle non autorisé
$service->attach($user, $pharmacy, Role::DOCTOR);
// RuntimeException: Role "Médecin" is not allowed for User -> Pharmacy. Allowed roles: Pharmacien, Personnel

// ❌ ERREUR - Target non autorisé
$service->attach($user, $specialty, Role::HAS_SPECIALTY);
// RuntimeException: User cannot be attached to Specialty. Allowed targets: Hospital, Pharmacy
```

### Contraintes uniques

Permet de limiter un modèle à un seul rattachement par type de cible.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class Hospital extends Model implements RattachmentConstraintsInterface
{
    public function allowedTargets(): array
    {
        return [
            User::class => [Role::ADMIN, Role::STAFF],
            Pharmacy::class => [Role::SUPPLIER],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            User::class,     // Un hôpital ne peut avoir qu'un seul directeur
            Pharmacy::class, // Un hôpital ne peut avoir qu'une seule pharmacie principale
        ];
    }
}
```

**Résultat :**
```php
// ✅ OK - Premier rattachement
$service->attach($hospital, $user1, Role::ADMIN);

// ❌ ERREUR - Deuxième rattachement (contrainte unique)
$service->attach($hospital, $user2, Role::ADMIN);
// RuntimeException: Hospital already has a unique attachment to User. Only one User is allowed.

// ✅ OK - Type de target différent
$service->attach($hospital, $pharmacy, Role::SUPPLIER);
```

---

## 📚 Référence de l'API

### RattachmentService

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `attach()` | Crée un rattachement | `Model` |
| `attachMultiple()` | Rattache plusieurs modèles à une cible | `Collection` |
| `attachToMultiple()` | Rattache un modèle à plusieurs cibles | `Collection` |
| `detach()` | Supprime un rattachement | `void` |
| `detachMultiple()` | Supprime plusieurs rattachements | `void` |
| `detachFromMultiple()` | Supprime les rattachements d'un modèle vers plusieurs cibles | `void` |
| `detachAll()` | Supprime tous les rattachements d'un modèle | `void` |
| `isAttached()` | Vérifie si un modèle est rattaché | `bool` |
| `hasRoleAttached()` | Vérifie si un rôle existe pour une cible | `bool` |
| `getRattachables()` | Récupère tous les modèles rattachés à une cible | `Collection` |
| `getRattachablesPaginated()` | Récupère les modèles rattachés paginés | `LengthAwarePaginator` |
| `getTargets()` | Récupère toutes les cibles d'un modèle | `Collection` |
| `getTargetsPaginated()` | Récupère les cibles paginées | `LengthAwarePaginator` |
| `getRattachablesByRole()` | Récupère les modèles rattachés par rôle | `Collection` |
| `getRattachablesByRolePaginated()` | Récupère les modèles rattachés par rôle paginés | `LengthAwarePaginator` |
| `getTargetsByRole()` | Récupère les cibles par rôle | `Collection` |
| `getTargetsByRolePaginated()` | Récupère les cibles par rôle paginées | `LengthAwarePaginator` |
| `getTargetsByType()` | Récupère les cibles d'un type spécifique | `Collection` |
| `getTargetsByTypePaginated()` | Récupère les cibles d'un type paginées | `LengthAwarePaginator` |
| `getTargetsByTypeAndRole()` | Récupère les cibles par type et rôle | `Collection` |
| `getTargetsByTypeAndRoles()` | Récupère les cibles par type et plusieurs rôles | `Collection` |
| `getTargetsByTypesAndRoles()` | Récupère les cibles par plusieurs types et rôles | `Collection` |
| `countRattachables()` | Compte les rattachements d'une cible | `int` |
| `countTargets()` | Compte les cibles d'un modèle | `int` |
| `countRattachablesByRole()` | Compte les rattachements par rôle | `int` |
| `countTargetsByRole()` | Compte les cibles par rôle | `int` |
| `getDistinctRolesForTarget()` | Récupère les rôles distincts d'une cible | `Collection` |
| `getDistinctRolesForRattachable()` | Récupère les rôles distincts d'un modèle | `Collection` |
| `updateRole()` | Met à jour le rôle | `void` |
| `updateRoleForMultiple()` | Met à jour le rôle de plusieurs rattachements | `void` |
| `updateMetadata()` | Met à jour les métadonnées | `void` |
| `mergeMetadata()` | Fusionne les métadonnées | `void` |
| `getAttachment()` | Récupère un rattachement spécifique | `?Model` |
| `hasAttachmentsBetween()` | Vérifie l'existence d'un rattachement entre deux modèles | `bool` |
| `hasAttachmentsBetweenTypes()` | Vérifie l'existence de rattachements entre types | `bool` |
| `getAttachmentsBetweenTypes()` | Récupère les rattachements entre types | `Collection` |
| `deleteAllAttachmentsBetweenTypes()` | Supprime tous les rattachements entre types | `int` |
| `syncAttachments()` | Synchronise les rattachements | `Collection` |

---

## 🎯 Value Objects

| Value Object | Description | Exemple |
|--------------|-------------|---------|
| `StrictDataObject` | Métadonnées typées | `StrictDataObject::from(['key' => 'value'])` |

---

## 📝 Structure de la base de données

```sql
CREATE TABLE rattachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rattachable_type VARCHAR(255) NOT NULL,
    rattachable_id BIGINT UNSIGNED NOT NULL,
    target_type VARCHAR(255) NOT NULL,
    target_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(50) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE INDEX idx_unique_rattachment (rattachable_type, rattachable_id, target_type, target_id),
    INDEX idx_rattachable (rattachable_type, rattachable_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_role (role)
);
```

> **Note :** La colonne `role` est nullable pour permettre des rattachements sans rôle.

---

## 🔍 Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Enums\Role;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\Pharmacy;

// Avec le service
$service = app(RattachmentService::class);
$doctor = Doctor::find(1);

$service->attach($doctor, Hospital::find(1), Role::DOCTOR);
$hospitals = $service->getTargetsByType($doctor, Hospital::class);

// Avec le trait
$doctor->attachTo(Hospital::find(2), Role::DOCTOR);
$allHospitals = $doctor->getTargetsByRole(Role::DOCTOR);

// Synchronisation
$doctor->syncAttachments([
    ['target' => Hospital::find(1), 'role' => Role::DOCTOR, 'metadata' => ['primary' => true]],
    ['target' => Pharmacy::find(1), 'role' => Role::PHARMACIST],
]);
```

---

## 🧪 Tests

```bash
composer test
composer test-integration
```

---

## 📦 Dépendances

- [`andydefer/php-vo`](https://github.com/andydefer/php-vo) - Value Objects
- [`andydefer/laravel-repository`](https://github.com/andydefer/laravel-repository) - Pattern Repository et Enum Casts
- [`andydefer/domain-structures`](https://github.com/andydefer/domain-structures) - Structures de domaine

---

## 👨‍💻 Auteur

**Andy Kani**
- GitHub: [@andydefer](https://github.com/andydefer)
- Email: andykanidimbu@gmail.com

---

## 📄 Licence

MIT © [Andy Defer](https://github.com/andydefer)

---

**Construit avec ❤️ pour la communauté Laravel**