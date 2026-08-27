# Laravel Rattachments

> Système de rattachement polymorphique double pour applications Laravel

Un package Laravel complet pour gérer des relations polymorphiques doubles entre n'importe quels modèles Eloquent, avec des rôles configurables, des métadonnées et un système de contraintes.

---

## 📋 Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Extensibilité](#extensibilité)
- [Utilisation](#utilisation)
  - [Créer un rattachement](#créer-un-rattachement)
  - [Supprimer un rattachement](#supprimer-un-rattachement)
  - [Vérifier un rattachement](#vérifier-un-rattachement)
  - [Récupérer les rattachements](#récupérer-les-rattachements)
  - [Mettre à jour un rattachement](#mettre-à-jour-un-rattachement)
  - [Synchroniser les rattachements](#synchroniser-les-rattachements)
  - [Filtrer et compter](#filtrer-et-compter)
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
- ✅ **Métadonnées flexibles** - Stockez des données supplémentaires au format JSON
- ✅ **Pattern Repository** - Séparation propre de la logique d'accès aux données
- ✅ **Support des DTOs** - Objets de transfert de données typés
- ✅ **Value Objects** - Métadonnées typées avec `StrictDataObject`
- ✅ **Enum Casts** - Conversion automatique entre base de données et énumérations PHP
- ✅ **Opérations en masse** - Rattachement et détachement multiples
- ✅ **Synchronisation** - Synchronisez tous les rattachements d'un modèle en une seule opération
- ✅ **Pagination** - Récupérez les résultats paginés
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

// Récupérer par rôle
$doctors = $service->getRattachablesByRole($hospital, CustomRole::DOCTOR);

// Mettre à jour le rôle
$service->updateRole($user, $hospital, CustomRole::ADMIN);
```

---

### Système de contraintes

Le package permet de définir des contraintes pour limiter les rattachements possibles.

#### 1. Implémenter l'interface

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

#### 2. Résultat

```php
// ✅ OK - User peut être rattaché à Hospital avec rôle DOCTOR
$service->attach($user, $hospital, Role::DOCTOR);

// ❌ ERREUR - User ne peut pas être rattaché à Pharmacy avec rôle DOCTOR
$service->attach($user, $pharmacy, Role::DOCTOR);
// RuntimeException: Role "Médecin" is not allowed for User -> Pharmacy. Allowed roles: Pharmacien, Personnel

// ❌ ERREUR - User ne peut pas être rattaché à Specialty (non autorisé)
$service->attach($user, $specialty, Role::HAS_SPECIALTY);
// RuntimeException: User cannot be attached to Specialty. Allowed targets: Hospital, Pharmacy
```

---

## 📖 Utilisation

### Créer un rattachement

```php
use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Enums\Role;

class DoctorController extends Controller
{
    public function attachToHospital(RattachmentService $service, Hospital $hospital)
    {
        $doctor = auth()->user();

        // Rattachement simple
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

// Vérifier si un rattachement existe entre deux modèles spécifiques
$exists = $service->hasAttachmentsBetween($doctor, $hospital);
```

### Récupérer les rattachements

```php
// Récupérer tous les modèles rattachés à une cible
$doctors = $service->getRattachables($hospital);

// Récupérer les modèles rattachés avec un rôle spécifique
$doctors = $service->getRattachablesByRole($hospital, Role::DOCTOR);

// Récupérer toutes les cibles d'un modèle
$hospitals = $service->getTargets($doctor);

// Récupérer les cibles avec un rôle spécifique
$hospitals = $service->getTargetsByRole($doctor, Role::DOCTOR);
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

// Les rattachements précédents (ex: hospital3) seront automatiquement supprimés
```

### Filtrer et compter

```php
// Compter les rattachements
$total = $service->countRattachables($hospital);
$doctorsCount = $service->countRattachablesByRole($hospital, Role::DOCTOR);

// Récupérer les rôles distincts
$roles = $service->getDistinctRolesForTarget($hospital);

// Pagination
$paginator = $service->getRattachablesPaginated($hospital, 15, 1);
$paginator = $service->getTargetsPaginated($doctor, 15, 1);
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

## 📚 Référence de l'API

### RattachmentService

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `attach(Model $rattachable, Model $target, EnumerableInterface $role, array $metadata = [])` | Crée un rattachement | `Model` |
| `attachMultiple(Collection $rattachables, Model $target, EnumerableInterface $role, array $metadata = [])` | Rattache plusieurs modèles à une cible | `Collection` |
| `attachToMultiple(Model $rattachable, Collection $targets, EnumerableInterface $role, array $metadata = [])` | Rattache un modèle à plusieurs cibles | `Collection` |
| `detach(Model $rattachable, Model $target)` | Supprime un rattachement | `void` |
| `detachMultiple(Collection $rattachables, Model $target)` | Supprime plusieurs rattachements | `void` |
| `detachFromMultiple(Model $rattachable, Collection $targets)` | Supprime les rattachements d'un modèle vers plusieurs cibles | `void` |
| `detachAll(Model $model)` | Supprime tous les rattachements d'un modèle | `void` |
| `isAttached(Model $rattachable, Model $target)` | Vérifie si un modèle est rattaché | `bool` |
| `hasRoleAttached(Model $target, EnumerableInterface $role)` | Vérifie si un rôle existe pour une cible | `bool` |
| `getRattachables(Model $target)` | Récupère tous les modèles rattachés à une cible | `Collection` |
| `getRattachablesPaginated(Model $target, int $perPage = 15, int $page = 1)` | Récupère les modèles rattachés paginés | `LengthAwarePaginator` |
| `getTargets(Model $rattachable)` | Récupère toutes les cibles d'un modèle | `Collection` |
| `getTargetsPaginated(Model $rattachable, int $perPage = 15, int $page = 1)` | Récupère les cibles paginées | `LengthAwarePaginator` |
| `getRattachablesByRole(Model $target, EnumerableInterface $role)` | Récupère les modèles rattachés par rôle | `Collection` |
| `getRattachablesByRolePaginated(Model $target, EnumerableInterface $role, int $perPage = 15, int $page = 1)` | Récupère les modèles rattachés par rôle paginés | `LengthAwarePaginator` |
| `getTargetsByRole(Model $rattachable, EnumerableInterface $role)` | Récupère les cibles par rôle | `Collection` |
| `getTargetsByRolePaginated(Model $rattachable, EnumerableInterface $role, int $perPage = 15, int $page = 1)` | Récupère les cibles par rôle paginées | `LengthAwarePaginator` |
| `countRattachables(Model $target)` | Compte les rattachements d'une cible | `int` |
| `countTargets(Model $rattachable)` | Compte les cibles d'un modèle | `int` |
| `countRattachablesByRole(Model $target, EnumerableInterface $role)` | Compte les rattachements par rôle | `int` |
| `countTargetsByRole(Model $rattachable, EnumerableInterface $role)` | Compte les cibles par rôle | `int` |
| `getDistinctRolesForTarget(Model $target)` | Récupère les rôles distincts d'une cible | `Collection` |
| `getDistinctRolesForRattachable(Model $rattachable)` | Récupère les rôles distincts d'un modèle | `Collection` |
| `updateRole(Model $rattachable, Model $target, EnumerableInterface $role)` | Met à jour le rôle | `void` |
| `updateRoleForMultiple(Collection $rattachables, Model $target, EnumerableInterface $role)` | Met à jour le rôle de plusieurs rattachements | `void` |
| `updateMetadata(Model $rattachable, Model $target, array $metadata)` | Met à jour les métadonnées | `void` |
| `mergeMetadata(Model $rattachable, Model $target, array $metadata)` | Fusionne les métadonnées | `void` |
| `getAttachment(Model $rattachable, Model $target)` | Récupère un rattachement spécifique | `?Model` |
| `hasAttachmentsBetween(Model $rattachable, Model $target)` | Vérifie l'existence d'un rattachement | `bool` |
| `hasAttachmentsBetweenTypes(string $rattachableType, string $targetType)` | Vérifie l'existence de rattachements entre types | `bool` |
| `getAttachmentsBetweenTypes(string $rattachableType, string $targetType)` | Récupère les rattachements entre types | `Collection` |
| `deleteAllAttachmentsBetweenTypes(string $rattachableType, string $targetType)` | Supprime tous les rattachements entre types | `int` |
| `syncAttachments(Model $rattachable, array $targets)` | Synchronise les rattachements | `Collection` |

---

## 🎯 Value Objects

Le package supporte les Value Objects suivants :

| Value Object | Description | Exemple |
|--------------|-------------|---------|
| `StrictDataObject` | Métadonnées typées | `StrictDataObject::from(['key' => 'value'])` |

### Accesseurs dans le modèle Rattachment

```php
$attachment = Rattachment::find(1);

// ✅ Accès via les accesseurs Eloquent (propriétés directement)
$createdAt = $attachment->created_at;       // Carbon
$updatedAt = $attachment->updated_at;       // Carbon
$metadata = $attachment->metadata;          // StrictDataObject|null
$role = $attachment->role;                  // EnumerableInterface (votre enum)

// ✅ Relations
$rattachable = $attachment->rattachable;    // Modèle rattaché (User, Doctor, etc.)
$target = $attachment->target;              // Modèle cible (Hospital, Pharmacy, etc.)
```

---

## 📝 Structure de la base de données

```sql
CREATE TABLE rattachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rattachable_type VARCHAR(255) NOT NULL, -- Type du modèle rattaché
    rattachable_id BIGINT UNSIGNED NOT NULL,-- ID du modèle rattaché
    target_type VARCHAR(255) NOT NULL,      -- Type du modèle cible
    target_id BIGINT UNSIGNED NOT NULL,     -- ID du modèle cible
    role VARCHAR(50) NOT NULL,              -- Rôle (valeur de votre enum)
    metadata JSON NULL,                     -- Métadonnées
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE INDEX idx_unique_rattachment (rattachable_type, rattachable_id, target_type, target_id),
    INDEX idx_rattachable (rattachable_type, rattachable_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_role (role)
);
```

---

## 🔍 Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Enums\Role;
use App\Models\User;
use App\Models\Hospital;
use App\Models\Pharmacy;

class HospitalController extends Controller
{
    public function __construct(
        private readonly RattachmentService $service
    ) {}

    public function attachDoctor(Request $request, Hospital $hospital)
    {
        $doctor = User::find($request->input('doctor_id'));

        // Rattacher le médecin
        $attachment = $this->service->attach(
            $doctor,
            $hospital,
            Role::DOCTOR,
            [
                'consultation_days' => $request->input('days', ['monday', 'wednesday']),
                'consultation_hours' => $request->input('hours', '09:00-17:00'),
                'department' => $request->input('department'),
            ]
        );

        return response()->json([
            'message' => 'Médecin rattaché avec succès',
            'attachment' => $attachment,
        ]);
    }

    public function getDoctors(Hospital $hospital)
    {
        // Récupérer tous les médecins avec pagination
        $doctors = $this->service->getRattachablesByRolePaginated(
            $hospital,
            Role::DOCTOR,
            15,
            $request->input('page', 1)
        );

        return response()->json($doctors);
    }

    public function syncDoctor(Request $request, Hospital $hospital)
    {
        $doctor = User::find($request->input('doctor_id'));

        // Synchroniser les rattachements du médecin
        $attachments = $this->service->syncAttachments($doctor, [
            [
                'target' => $hospital,
                'role' => Role::DOCTOR,
                'metadata' => ['primary' => true],
            ],
            [
                'target' => Pharmacy::find($request->input('pharmacy_id')),
                'role' => Role::PHARMACIST,
            ],
        ]);

        return response()->json([
            'message' => 'Rattachements synchronisés',
            'attachments' => $attachments,
        ]);
    }

    public function stats(Hospital $hospital)
    {
        return response()->json([
            'total_doctors' => $this->service->countRattachablesByRole($hospital, Role::DOCTOR),
            'total_staff' => $this->service->countRattachablesByRole($hospital, Role::STAFF),
            'distinct_roles' => $this->service->getDistinctRolesForTarget($hospital),
        ]);
    }

    public function detachDoctor(Request $request, Hospital $hospital)
    {
        $doctor = User::find($request->input('doctor_id'));

        $this->service->detach($doctor, $hospital);

        return response()->json([
            'message' => 'Médecin détaché avec succès',
        ]);
    }
}
```

---

## 🧪 Tests

### Exécuter les tests

```bash
composer test
```

### Exécuter uniquement les tests d'intégration

```bash
composer test-integration
```

### Configuration des tests

Le package utilise `orchestra/testbench` pour les tests d'intégration avec une base de données SQLite en mémoire.

---

## 🔧 Développement

### Style de code

```bash
./vendor/bin/pint
```

### Analyse statique

```bash
./vendor/bin/phpstan analyse
./vendor/bin/psalm
```

---

## 📦 Dépendances

- [`andydefer/php-vo`](https://github.com/andydefer/php-vo) - Value Objects
- [`andydefer/laravel-repository`](https://github.com/andydefer/laravel-repository) - Pattern Repository et Enum Casts
- [`andydefer/domain-structures`](https://github.com/andydefer/domain-structures) - Structures de domaine (AbstractRecord, AbstractData, StrictDataObject)

---

## 👨‍💻 Auteur

**Andy Kani**
- GitHub: [@andydefer](https://github.com/andydefer)
- Email: andykanidimbu@gmail.com

---

## 📄 Licence

Ce package est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus d'informations.

---

## ⭐ Support

Si vous trouvez ce package utile, n'hésitez pas à lui donner une ⭐ sur GitHub !

---

**Construit avec ❤️ pour la communauté Laravel**