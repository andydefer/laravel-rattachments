# Laravel Rattachments

> Système de rattachement polymorphique double pour applications Laravel

Un package Laravel complet pour gérer des relations polymorphiques doubles entre n'importe quels modèles Eloquent, avec des rôles configurables, des métadonnées, un système de contraintes avancé et une validation centralisée.

---

## 📋 Table des matières

- [Fonctionnalités](#-fonctionnalités)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Concept fondamental](#-concept-fondamental)
- [Les contraintes](#-les-contraintes)
- [Les rôles contextuels](#-les-rôles-contextuels)
- [Utilisation avec le service](#-utilisation-avec-le-service)
- [Utilisation avec le trait](#-utilisation-avec-le-trait)
- [Référence complète du service](#-référence-complète-du-service)
- [Validation centralisée](#-validation-centralisée)
- [Inspection CLI](#-inspection-cli)
- [Structure de la base de données](#-structure-de-la-base-de-données)
- [Exemples complets](#-exemples-complets)
- [Dépendances](#-dépendances)
- [Licence](#-licence)

---

## ✨ Fonctionnalités

- ✅ **Double polymorphisme** - Rattachez n'importe quel modèle à n'importe quel autre modèle
- ✅ **Rôles contextuels** - Chaque modèle définit ses propres rôles via des enums
- ✅ **Système de contraintes complet** - Autorisation, unicité et interdiction
- ✅ **Validation centralisée** - `ConstraintValidator` pour une validation cohérente
- ✅ **Résolution dynamique des rôles** - Pas d'enum global, résolution basée sur le contexte
- ✅ **Métadonnées flexibles** - Stockez des données supplémentaires au format JSON
- ✅ **Trait HasRattachments** - API fluide directement dans vos modèles
- ✅ **Filtrage avancé** - Par type, rôle, ou combinaison
- ✅ **Opérations en masse** - Rattachement et détachement multiples
- ✅ **Synchronisation** - Synchronisez tous les rattachements d'un modèle en une seule opération
- ✅ **Pagination** - Récupérez les résultats paginés
- ✅ **Inspection CLI** - Directive `rattachments:inspect` pour analyser les contraintes
- ✅ **Découverte automatique** - Scan des modèles implémentant l'interface
- ✅ **Rétrocompatibilité** - Support d'`UnknownRole` pour les rôles supprimés
- ✅ **Tests complets** - Couverture complète des tests d'intégration

---

## 🚀 Prérequis

- PHP 8.2 ou supérieur
- Laravel 12.0, 13.0, 14.0 ou 15.0

---

## 📦 Installation

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

## 🎯 Concept fondamental

### Qu'est-ce qu'un rattachement ?

Un rattachement est une relation polymorphique double entre deux modèles Eloquent. Il est représenté par un enregistrement dans la table `rattachments` qui stocke :

- **Le modèle source** (`rattachable`) - Celui qui attache
- **Le modèle cible** (`target`) - Celui qui est attaché
- **Un rôle** - Définit la nature de la relation
- **Des métadonnées** - Données supplémentaires au format JSON

### Exemple concret

```php
// Un médecin (rattachable) est rattaché à un hôpital (target)
// avec le rôle "doctor" et des métadonnées sur ses horaires

$attachment = $service->attach(
    $doctor,                    // rattachable
    $hospital,                  // target
    HospitalUserRole::DOCTOR,   // rôle
    [                           // métadonnées
        'consultation_days' => ['monday', 'wednesday', 'friday'],
        'consultation_hours' => '09:00-17:00',
    ]
);
```

---

## 🔒 Les contraintes

L'interface `RattachmentConstraintsInterface` permet à vos modèles de définir trois types de contraintes.

### 1. Cibles autorisées (`allowedTargets`)

Définit quels modèles peuvent être attachés et avec quels rôles.

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use App\Enums\HospitalUserRole;
use App\Enums\PharmacyUserRole;
use Illuminate\Database\Eloquent\Model;

final class User extends Model implements RattachmentConstraintsInterface
{
    public function allowedTargets(): array
    {
        return [
            Hospital::class => [
                HospitalUserRole::DOCTOR,
                HospitalUserRole::STAFF,
                HospitalUserRole::ADMIN,
            ],
            Pharmacy::class => [
                PharmacyUserRole::PHARMACIST,
                PharmacyUserRole::STAFF,
            ],
        ];
    }

    // Les autres méthodes obligatoires...
}
```

**Comportement :**
```php
// ✅ OK
$service->attach($user, $hospital, HospitalUserRole::DOCTOR);

// ❌ ERREUR - Rôle non autorisé
$service->attach($user, $pharmacy, HospitalUserRole::DOCTOR);
// "Role 'doctor' is not allowed for User -> Pharmacy. Allowed roles: pharmacist, staff"

// ❌ ERREUR - Cible non autorisée
$service->attach($user, $specialty, SpecialtyRole::SPECIALIST);
// "User cannot be attached to Specialty. Allowed targets: Hospital, Pharmacy"
```

---

### 2. Cibles uniques (`uniqueTargets`)

Limite un modèle à un seul rattachement par type de cible.

```php
public function uniqueTargets(): array
{
    return [
        Hospital::class,  // Un utilisateur ne peut être rattaché qu'à un seul hôpital
        Pharmacy::class,  // Un utilisateur ne peut être rattaché qu'à une seule pharmacie
    ];
}
```

**Comportement :**
```php
// ✅ OK - Premier rattachement
$service->attach($user, $hospital1, HospitalUserRole::DOCTOR);

// ❌ ERREUR - Deuxième rattachement du même type
$service->attach($user, $hospital2, HospitalUserRole::DOCTOR);
// "User already has a unique attachment to Hospital. Only one Hospital is allowed."

// ✅ OK - Type différent
$service->attach($user, $pharmacy, PharmacyUserRole::PHARMACIST);
```

---

### 3. Cibles interdites (`disallowedTargets`)

Bloque des cibles ou des rôles spécifiques. Cette contrainte a **priorité maximale**.

```php
public function disallowedTargets(): array
{
    return [
        // Bloque TOUS les rattachements à Specialty
        Specialty::class => [],

        // Bloque uniquement le rôle REVIEWER pour Post
        Post::class => [PostUserRole::REVIEWER],

        // Bloque uniquement le rôle ADMIN pour Hospital
        Hospital::class => [HospitalUserRole::ADMIN],
    ];
}
```

**Comportement :**
```php
// ❌ ERREUR - Cible complètement bloquée
$service->attach($user, $specialty, SpecialtyRole::SPECIALIST);
// "User cannot be attached to Specialty. This target is disallowed."

// ❌ ERREUR - Rôle bloqué
$service->attach($user, $post, PostUserRole::REVIEWER);
// "Role 'reviewer' is disallowed for User -> Post. Disallowed roles: reviewer"

// ✅ OK - Rôle autorisé
$service->attach($user, $post, PostUserRole::AUTHOR);
```

> **⚠️ Règle de priorité :** `disallowedTargets` a priorité sur `allowedTargets`. Si une cible est dans les deux tableaux, `disallowedTargets` l'emporte.

---

## 🏷️ Les rôles contextuels

### Pourquoi des rôles contextuels ?

Dans une application, le même terme ("admin") peut avoir des significations différentes selon le contexte :

- Un administrateur d'hôpital n'est pas la même chose qu'un administrateur de site
- Un médecin peut avoir des rôles différents selon l'hôpital où il travaille

Les rôles contextuels permettent de définir des enums spécifiques à chaque contexte.

### Créer ses rôles

```php
<?php

declare(strict_types=1);

namespace App\Enums;

use AndyDefer\Repository\Contracts\EnumerableInterface;

// Rôles pour un utilisateur attaché à un hôpital
enum HospitalUserRole: string implements EnumerableInterface
{
    case DOCTOR = 'doctor';
    case NURSE = 'nurse';
    case ADMIN = 'admin';
    case STAFF = 'staff';

    public function getValue(): string
    {
        return $this->value;
    }
}

// Rôles pour un utilisateur attaché à un post
enum PostUserRole: string implements EnumerableInterface
{
    case AUTHOR = 'author';
    case EDITOR = 'editor';
    case REVIEWER = 'reviewer';
    case CONTRIBUTOR = 'contributor';

    public function getValue(): string
    {
        return $this->value;
    }
}
```

### Résolution dynamique

Le package résout automatiquement le bon enum en fonction du contexte :

```php
// En base de données
[
    'rattachable_type' => 'App\Models\User',
    'target_type' => 'App\Models\Hospital',
    'role' => 'doctor',
]

// Lecture - résolution automatique
$attachment->role; // HospitalUserRole::DOCTOR

// En base de données
[
    'rattachable_type' => 'App\Models\User',
    'target_type' => 'App\Models\Post',
    'role' => 'author',
]

// Lecture - résolution automatique
$attachment->role; // PostUserRole::AUTHOR
```

---

## 📖 Utilisation avec le service

### Injection du service

```php
use AndyDefer\LaravelRattachments\Services\RattachmentService;

class DoctorController extends Controller
{
    public function __construct(
        private readonly RattachmentService $rattachmentService
    ) {}
}
```

### Créer un rattachement

```php
public function attachToHospital(Hospital $hospital)
{
    $doctor = auth()->user();

    $attachment = $this->rattachmentService->attach(
        $doctor,                    // Modèle à rattacher
        $hospital,                  // Modèle cible
        HospitalUserRole::DOCTOR,   // Rôle (obligatoire)
        [                           // Métadonnées (optionnel)
            'consultation_days' => ['monday', 'wednesday', 'friday'],
            'consultation_hours' => '09:00-17:00',
            'department' => 'Cardiology',
        ]
    );

    return response()->json([
        'message' => 'Docteur rattaché à l\'hôpital',
        'role' => $attachment->role->getValue(),
        'metadata' => $attachment->metadata,
    ]);
}
```

### Rattachement multiple

```php
// Rattacher plusieurs modèles à une même cible
$attachments = $this->rattachmentService->attachMultiple(
    collect([$doctor1, $doctor2, $doctor3]),
    $hospital,
    HospitalUserRole::DOCTOR,
    ['department' => 'Cardiology']
);

// Rattacher un modèle à plusieurs cibles
$attachments = $this->rattachmentService->attachToMultiple(
    $doctor,
    collect([$hospital1, $hospital2, $hospital3]),
    HospitalUserRole::DOCTOR
);
```

### Supprimer un rattachement

```php
// Supprimer un rattachement spécifique
$this->rattachmentService->detach($doctor, $hospital);

// Supprimer plusieurs rattachements
$this->rattachmentService->detachMultiple(
    collect([$doctor1, $doctor2]),
    $hospital
);

// Supprimer tous les rattachements d'un modèle
$this->rattachmentService->detachAll($doctor);
```

### Vérifier l'existence

```php
// Vérifier si un modèle est rattaché à un autre
if ($this->rattachmentService->isAttached($doctor, $hospital)) {
    // ...
}

// Vérifier si un rôle existe pour une cible
if ($this->rattachmentService->hasRoleAttached($hospital, HospitalUserRole::DOCTOR)) {
    // L'hôpital a au moins un médecin
}

// Récupérer un attachement spécifique
$attachment = $this->rattachmentService->getAttachment($doctor, $hospital);
```

### Récupérer les rattachements

```php
// Récupérer tous les modèles rattachés à une cible
$doctors = $this->rattachmentService->getRattachables($hospital);

// Récupérer les modèles rattachés avec un rôle spécifique
$doctors = $this->rattachmentService->getRattachablesByRole($hospital, HospitalUserRole::DOCTOR);

// Récupérer toutes les cibles d'un modèle
$hospitals = $this->rattachmentService->getTargets($doctor);

// Récupérer les cibles d'un type spécifique
$hospitals = $this->rattachmentService->getTargetsByType($doctor, Hospital::class);

// Récupérer les cibles par type et rôle
$hospitals = $this->rattachmentService->getTargetsByTypeAndRole(
    $doctor,
    Hospital::class,
    HospitalUserRole::DOCTOR
);

// Récupérer les cibles de plusieurs types avec plusieurs rôles
$targets = $this->rattachmentService->getTargetsByTypesAndRoles(
    $doctor,
    [Hospital::class, Pharmacy::class],
    [HospitalUserRole::DOCTOR, PharmacyUserRole::PHARMACIST]
);
```

### Pagination

```php
// Paginer les cibles
$hospitals = $this->rattachmentService->getTargetsPaginated($doctor, 10, 2);

// Paginer les cibles par rôle
$hospitals = $this->rattachmentService->getTargetsByRolePaginated(
    $doctor,
    HospitalUserRole::DOCTOR,
    10,
    2
);

// Paginer les rattachables
$doctors = $this->rattachmentService->getRattachablesPaginated($hospital, 10, 2);

// Paginer les rattachables par rôle
$doctors = $this->rattachmentService->getRattachablesByRolePaginated(
    $hospital,
    HospitalUserRole::DOCTOR,
    10,
    2
);
```

### Compter

```php
// Compter les cibles
$total = $this->rattachmentService->countTargets($doctor);

// Compter les cibles par rôle
$total = $this->rattachmentService->countTargetsByRole($doctor, HospitalUserRole::DOCTOR);

// Compter les rattachables
$total = $this->rattachmentService->countRattachables($hospital);

// Compter les rattachables par rôle
$total = $this->rattachmentService->countRattachablesByRole($hospital, HospitalUserRole::DOCTOR);
```

### Mettre à jour

```php
// Mettre à jour le rôle
$this->rattachmentService->updateRole($doctor, $hospital, HospitalUserRole::ADMIN);

// Mettre à jour plusieurs rôles
$this->rattachmentService->updateRoleForMultiple(
    collect([$doctor1, $doctor2]),
    $hospital,
    HospitalUserRole::ADMIN
);

// Mettre à jour les métadonnées (remplacement)
$this->rattachmentService->updateMetadata($doctor, $hospital, [
    'consultation_hours' => '10:00-18:00',
]);

// Fusionner les métadonnées (conserve les existantes)
$this->rattachmentService->mergeMetadata($doctor, $hospital, [
    'availability' => 'Monday-Friday',
]);
```

### Synchroniser

```php
// Synchronise tous les rattachements d'un modèle
$attachments = $this->rattachmentService->syncAttachments($doctor, [
    [
        'target' => $hospital1,
        'role' => HospitalUserRole::DOCTOR,
        'metadata' => ['primary' => true],
    ],
    [
        'target' => $hospital2,
        'role' => HospitalUserRole::DOCTOR,
    ],
    [
        'target' => $pharmacy,
        'role' => PharmacyUserRole::PHARMACIST,
        'metadata' => ['supplier' => true],
    ],
]);

// Les rattachements précédents qui ne sont pas dans la liste sont supprimés
```

### Rôles distincts

```php
// Rôles distincts pour ce modèle comme rattachable
$roles = $this->rattachmentService->getDistinctRolesForRattachable($doctor);

// Rôles distincts pour ce modèle comme cible
$roles = $this->rattachmentService->getDistinctRolesForTarget($hospital);
```

---

## 📖 Utilisation avec le trait

Le trait `HasRattachments` offre une API fluide directement sur vos modèles.

### Ajouter le trait

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

### Créer et supprimer

```php
$doctor = Doctor::find(1);
$hospital = Hospital::find(1);

// Rattacher
$doctor->attachTo($hospital, HospitalUserRole::DOCTOR, [
    'consultation_days' => ['monday', 'wednesday'],
]);

// Détacher
$doctor->detachFrom($hospital);
$doctor->detachAll();
```

### Vérifier

```php
// Vérifier si attaché
if ($doctor->isAttachedTo($hospital)) {
    // ...
}

// Vérifier si un rôle existe
if ($doctor->hasRoleAttachedTo($hospital, HospitalUserRole::DOCTOR)) {
    // ...
}

// Récupérer un attachement spécifique
$attachment = $doctor->getAttachment($hospital);
```

### Lire les cibles

```php
// Toutes les cibles
$hospitals = $doctor->getTargets();

// Paginé
$hospitals = $doctor->getTargetsPaginated(10, 2);

// Par rôle
$hospitals = $doctor->getTargetsByRole(HospitalUserRole::DOCTOR);

// Par type
$hospitals = $doctor->getTargetsByType(Hospital::class);

// Par type et rôle
$hospitals = $doctor->getTargetsByTypeAndRole(Hospital::class, HospitalUserRole::DOCTOR);

// Par plusieurs types et rôles
$targets = $doctor->getTargetsByTypesAndRoles(
    [Hospital::class, Pharmacy::class],
    [HospitalUserRole::DOCTOR, PharmacyUserRole::PHARMACIST]
);
```

### Lire les rattachables

```php
// Tous les rattachables
$users = $hospital->getRattachables();

// Paginé
$users = $hospital->getRattachablesPaginated(10, 2);

// Par rôle
$users = $hospital->getRattachablesByRole(HospitalUserRole::DOCTOR);
```

### Compter

```php
// Compter les cibles
$count = $doctor->countTargets();
$count = $doctor->countTargetsByRole(HospitalUserRole::DOCTOR);

// Compter les rattachables
$count = $hospital->countRattachables();
$count = $hospital->countRattachablesByRole(HospitalUserRole::DOCTOR);
```

### Rôles distincts

```php
$roles = $doctor->getDistinctRoles();
$roles = $hospital->getDistinctRolesForTarget();
```

### Mettre à jour

```php
$doctor->updateRoleFor($hospital, HospitalUserRole::ADMIN);
$doctor->updateMetadataFor($hospital, ['hours' => '10:00-18:00']);
$doctor->mergeMetadataFor($hospital, ['availability' => 'Monday-Friday']);
```

### Synchroniser

```php
$doctor->syncAttachments([
    ['target' => $hospital1, 'role' => HospitalUserRole::DOCTOR],
    ['target' => $hospital2, 'role' => HospitalUserRole::DOCTOR],
]);
```

---

## 📚 Référence complète du service

### Méthodes de création

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `attach(Model $rattachable, Model $target, EnumerableInterface $role, array $metadata = []): Model` | Crée un rattachement | `Model` |
| `attachMultiple(Collection $rattachables, Model $target, EnumerableInterface $role, array $metadata = []): Collection` | Rattache plusieurs modèles à une cible | `Collection` |
| `attachToMultiple(Model $rattachable, Collection $targets, EnumerableInterface $role, array $metadata = []): Collection` | Rattache un modèle à plusieurs cibles | `Collection` |

### Méthodes de suppression

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `detach(Model $rattachable, Model $target): void` | Supprime un rattachement | `void` |
| `detachMultiple(Collection $rattachables, Model $target): void` | Supprime plusieurs rattachements | `void` |
| `detachFromMultiple(Model $rattachable, Collection $targets): void` | Supprime les rattachements d'un modèle vers plusieurs cibles | `void` |
| `detachAll(Model $model): void` | Supprime tous les rattachements d'un modèle | `void` |

### Méthodes de vérification

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `isAttached(Model $rattachable, Model $target): bool` | Vérifie si un modèle est rattaché | `bool` |
| `hasRoleAttached(Model $target, EnumerableInterface $role): bool` | Vérifie si un rôle existe pour une cible | `bool` |
| `getAttachment(Model $rattachable, Model $target): ?Model` | Récupère un attachement spécifique | `?Model` |
| `hasAttachmentsBetween(Model $rattachable, Model $target): bool` | Vérifie l'existence d'un attachement | `bool` |
| `hasAttachmentsBetweenTypes(string $rattachableType, string $targetType): bool` | Vérifie l'existence de rattachements entre types | `bool` |
| `getAttachmentsBetweenTypes(string $rattachableType, string $targetType): Collection` | Récupère les rattachements entre types | `Collection` |

### Méthodes de lecture (cibles)

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `getTargets(Model $rattachable): Collection` | Toutes les cibles | `Collection` |
| `getTargetsPaginated(Model $rattachable, int $perPage, int $page): LengthAwarePaginator` | Cibles paginées | `LengthAwarePaginator` |
| `getTargetsByRole(Model $rattachable, EnumerableInterface $role): Collection` | Cibles par rôle | `Collection` |
| `getTargetsByRolePaginated(Model $rattachable, EnumerableInterface $role, int $perPage, int $page): LengthAwarePaginator` | Cibles par rôle paginées | `LengthAwarePaginator` |
| `getTargetsByType(Model $rattachable, string $targetClass): Collection` | Cibles par type | `Collection` |
| `getTargetsByTypePaginated(Model $rattachable, string $targetClass, int $perPage, int $page): LengthAwarePaginator` | Cibles par type paginées | `LengthAwarePaginator` |
| `getTargetsByTypeAndRole(Model $rattachable, string $targetClass, EnumerableInterface $role): Collection` | Cibles par type et rôle | `Collection` |
| `getTargetsByTypeAndRoles(Model $rattachable, string $targetClass, array $roles): Collection` | Cibles par type et plusieurs rôles | `Collection` |
| `getTargetsByTypesAndRoles(Model $rattachable, array $targetClasses, array $roles): Collection` | Cibles par plusieurs types et rôles | `Collection` |

### Méthodes de lecture (rattachables)

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `getRattachables(Model $target): Collection` | Tous les modèles attachés | `Collection` |
| `getRattachablesPaginated(Model $target, int $perPage, int $page): LengthAwarePaginator` | Modèles attachés paginés | `LengthAwarePaginator` |
| `getRattachablesByRole(Model $target, EnumerableInterface $role): Collection` | Modèles attachés par rôle | `Collection` |
| `getRattachablesByRolePaginated(Model $target, EnumerableInterface $role, int $perPage, int $page): LengthAwarePaginator` | Modèles attachés par rôle paginés | `LengthAwarePaginator` |

### Méthodes de comptage

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `countTargets(Model $rattachable): int` | Nombre de cibles | `int` |
| `countTargetsByRole(Model $rattachable, EnumerableInterface $role): int` | Nombre de cibles par rôle | `int` |
| `countRattachables(Model $target): int` | Nombre de modèles attachés | `int` |
| `countRattachablesByRole(Model $target, EnumerableInterface $role): int` | Nombre de modèles attachés par rôle | `int` |

### Méthodes de mise à jour

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `updateRole(Model $rattachable, Model $target, EnumerableInterface $role): void` | Met à jour le rôle | `void` |
| `updateRoleForMultiple(Collection $rattachables, Model $target, EnumerableInterface $role): void` | Met à jour le rôle de plusieurs rattachements | `void` |
| `updateMetadata(Model $rattachable, Model $target, array $metadata): void` | Met à jour les métadonnées | `void` |
| `mergeMetadata(Model $rattachable, Model $target, array $metadata): void` | Fusionne les métadonnées | `void` |
| `deleteAllAttachmentsBetweenTypes(string $rattachableType, string $targetType): int` | Supprime tous les rattachements entre types | `int` |

### Méthodes de synchronisation

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `syncAttachments(Model $rattachable, array $targets): Collection` | Synchronise les rattachements | `Collection` |

### Méthodes de rôles distincts

| Méthode | Description | Retourne |
|---------|-------------|----------|
| `getDistinctRolesForRattachable(Model $rattachable): Collection` | Rôles distincts pour un modèle comme rattachable | `Collection` |
| `getDistinctRolesForTarget(Model $target): Collection` | Rôles distincts pour un modèle comme cible | `Collection` |

---

## 🛡️ Validation centralisée

Le package utilise un `ConstraintValidator` centralisé pour toutes les validations.

### Rôle du `ConstraintValidator`

- Valide les contraintes de cibles autorisées
- Valide les contraintes uniques
- Valide les contraintes d'interdiction
- Valide les rôles
- Résout les rôles dynamiquement

### Utilisation dans le service

```php
// Le service utilise automatiquement le validator
$service->attach($user, $hospital, HospitalUserRole::DOCTOR);
// La validation est effectuée avant la création
```

### Utilisation directe (avancé)

```php
use AndyDefer\LaravelRattachments\Validation\ConstraintValidator;

$validator = app(ConstraintValidator::class);

// Valider les contraintes
$validator->validateConstraints($user, $hospital, HospitalUserRole::DOCTOR);

// Valider les contraintes uniques
$validator->validateUniqueConstraints($user, $hospital);

// Valider un rôle
$validator->validateRoleValue(User::class, Hospital::class, 'doctor');

// Résoudre un rôle
$role = $validator->resolveRole(User::class, Hospital::class, 'doctor');
```

---

## 🔍 Inspection CLI

La directive `rattachments:inspect` permet d'inspecter les contraintes et les connexions existantes.

### Commandes

```bash
# Inspecter un modèle spécifique
php artisan rattachments:inspect [App.Models.User] --constraints

# Inspecter plusieurs modèles
php artisan rattachments:inspect [App.Models.User, App.Models.Hospital]

# Afficher uniquement les connexions
php artisan rattachments:inspect [App.Models.User] --connections

# Afficher uniquement les contraintes
php artisan rattachments:inspect [App.Models.User] --constraints

# Découverte automatique
php artisan rattachments:inspect --constraints

# Utiliser des alias
php artisan ri [App.Models.User] --constraints
```

### Exemple de sortie

```
🔍 Inspecting rattachments...

═════════════════════════════════════════════════════════════
  🔒 CONSTRAINTS
═════════════════════════════════════════════════════════════

📦 User
   FQCN: App\Models\User
   ✅ Allowed targets:
Hospital                                                     : doctor, staff, admin
Pharmacy                                                     : pharmacist, staff
   🔒 Unique targets:
Hospital                                                     : one-to-one
   🚫 Disallowed targets:
Specialty                                                    : 🚫 All roles disallowed
Post                                                         : 🚫 Roles: reviewer
   ⚠️  CONFLICT DETECTED: The following targets appear in both allowed and disallowed:
Post                                                         : ⚠️ Allowed: author, editor | Disallowed: reviewer → DISALLOW WINS

═════════════════════════════════════════════════════════════
  🔗 EXISTING CONNECTIONS
═════════════════════════════════════════════════════════════

📊 Found 3 connection types:

User → Hospital                                                : 5x
User → Pharmacy                                                : 3x
User → Post                                                    : 12x

📋 Roles by connection:

   User → Hospital:
doctor                                                       : 3
staff                                                        : 2

   User → Pharmacy:
pharmacist                                                   : 3

   User → Post:
author                                                       : 8
editor                                                       : 4

💡 Possible missing connections (based on constraints):

User → Specialty                                               : ⚠️ Constraint defined but no connections found

✅ Inspection completed
```

---

## 📝 Structure de la base de données

```sql
CREATE TABLE rattachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rattachable_type VARCHAR(255) NOT NULL,
    rattachable_id BIGINT UNSIGNED NOT NULL,
    target_type VARCHAR(255) NOT NULL,
    target_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(255) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE INDEX idx_unique_rattachment (rattachable_type, rattachable_id, target_type, target_id),
    INDEX idx_rattachable (rattachable_type, rattachable_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_role (role)
);
```

> **⚠️ IMPORTANT :** La colonne `role` est **NON NULLABLE**. Tous les rattachements doivent avoir un rôle.

---

## 💡 Exemples complets

### Exemple 1 : Gestion des médecins et hôpitaux

```php
<?php

declare(strict_types=1);

namespace App\Services;

use AndyDefer\LaravelRattachments\Services\RattachmentService;
use App\Enums\HospitalUserRole;
use App\Models\Doctor;
use App\Models\Hospital;
use Illuminate\Support\Collection;

final class HospitalManagementService
{
    public function __construct(
        private readonly RattachmentService $rattachmentService,
    ) {}

    public function assignDoctor(Hospital $hospital, Doctor $doctor, array $schedule): void
    {
        $this->rattachmentService->attach(
            $doctor,
            $hospital,
            HospitalUserRole::DOCTOR,
            [
                'schedule' => $schedule,
                'assigned_at' => now()->toDateTimeString(),
            ]
        );
    }

    public function getHospitalDoctors(Hospital $hospital): Collection
    {
        return $this->rattachmentService->getRattachablesByRole($hospital, HospitalUserRole::DOCTOR);
    }

    public function getDoctorHospitals(Doctor $doctor): Collection
    {
        return $this->rattachmentService->getTargetsByRole($doctor, HospitalUserRole::DOCTOR);
    }

    public function promoteToChief(Doctor $doctor, Hospital $hospital): void
    {
        $this->rattachmentService->updateRole($doctor, $hospital, HospitalUserRole::ADMIN);
    }

    public function removeDoctor(Doctor $doctor, Hospital $hospital): void
    {
        $this->rattachmentService->detach($doctor, $hospital);
    }

    public function syncHospitalDoctors(Hospital $hospital, array $doctorData): Collection
    {
        $targets = [];
        foreach ($doctorData as $data) {
            $targets[] = [
                'target' => $data['doctor'],
                'role' => $data['role'] ?? HospitalUserRole::DOCTOR,
                'metadata' => $data['metadata'] ?? [],
            ];
        }

        return $this->rattachmentService->syncAttachments($hospital, $targets);
    }
}
```

### Exemple 2 : Gestion des tags d'un article

```php
<?php

declare(strict_types=1);

namespace App\Services;

use AndyDefer\LaravelRattachments\Services\RattachmentService;
use App\Enums\PostTagRole;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Collection;

final class PostTagService
{
    public function __construct(
        private readonly RattachmentService $rattachmentService,
    ) {}

    public function addTag(Post $post, Tag $tag, PostTagRole $role, array $metadata = []): void
    {
        $this->rattachmentService->attach($post, $tag, $role, $metadata);
    }

    public function removeTag(Post $post, Tag $tag): void
    {
        $this->rattachmentService->detach($post, $tag);
    }

    public function getPostTags(Post $post): Collection
    {
        return $this->rattachmentService->getTargetsByType($post, Tag::class);
    }

    public function getPostTagsByRole(Post $post, PostTagRole $role): Collection
    {
        return $this->rattachmentService->getTargetsByTypeAndRole($post, Tag::class, $role);
    }

    public function syncPostTags(Post $post, array $tagData): Collection
    {
        $targets = [];
        foreach ($tagData as $data) {
            $targets[] = [
                'target' => $data['tag'],
                'role' => $data['role'] ?? PostTagRole::TAG,
                'metadata' => $data['metadata'] ?? [],
            ];
        }

        return $this->rattachmentService->syncAttachments($post, $targets);
    }

    public function getTagCount(Post $post): int
    {
        return $this->rattachmentService->countTargets($post);
    }

    public function getPrimaryTags(Post $post): Collection
    {
        return $this->rattachmentService->getTargetsByTypeAndRole($post, Tag::class, PostTagRole::PRIMARY);
    }
}
```

### Exemple 3 : Pipeline de validation avec contraintes

```php
<?php

declare(strict_types=1);

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentConstraintsInterface;
use App\Enums\DocumentRole;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;

final class Document extends Model implements RattachmentConstraintsInterface
{
    public function allowedTargets(): array
    {
        return [
            User::class => [
                DocumentRole::AUTHOR,
                DocumentRole::REVIEWER,
                DocumentRole::APPROVER,
                DocumentRole::EDITOR,
            ],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            User::class, // Un document ne peut avoir qu'un seul auteur principal
        ];
    }

    public function disallowedTargets(): array
    {
        return [
            // Les utilisateurs avec le rôle "guest" ne peuvent pas être attachés
            User::class => [UserRole::GUEST],
        ];
    }
}
```

### Exemple 4 : Utilisation complète avec le trait

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Enums\HospitalUserRole;
use Illuminate\Http\JsonResponse;

final class DoctorController extends Controller
{
    public function show(Doctor $doctor): JsonResponse
    {
        return response()->json([
            'doctor' => $doctor,
            'hospitals' => $doctor->getTargetsByRole(HospitalUserRole::DOCTOR),
            'total_hospitals' => $doctor->countTargets(),
            'roles' => $doctor->getDistinctRoles(),
        ]);
    }

    public function attachToHospital(Doctor $doctor, Hospital $hospital): JsonResponse
    {
        if ($doctor->isAttachedTo($hospital)) {
            return response()->json([
                'message' => 'Doctor already attached to this hospital',
            ], 422);
        }

        $attachment = $doctor->attachTo($hospital, HospitalUserRole::DOCTOR, [
            'assigned_at' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'message' => 'Doctor attached successfully',
            'attachment' => $attachment,
        ]);
    }

    public function detachFromHospital(Doctor $doctor, Hospital $hospital): JsonResponse
    {
        if (!$doctor->isAttachedTo($hospital)) {
            return response()->json([
                'message' => 'Doctor is not attached to this hospital',
            ], 422);
        }

        $doctor->detachFrom($hospital);

        return response()->json([
            'message' => 'Doctor detached successfully',
        ]);
    }

    public function syncHospitals(Doctor $doctor, array $hospitalData): JsonResponse
    {
        $targets = [];
        foreach ($hospitalData as $data) {
            $targets[] = [
                'target' => Hospital::find($data['hospital_id']),
                'role' => HospitalUserRole::from($data['role']),
                'metadata' => $data['metadata'] ?? [],
            ];
        }

        $attachments = $doctor->syncAttachments($targets);

        return response()->json([
            'message' => 'Hospitals synchronized',
            'attachments' => $attachments,
        ]);
    }

    public function stats(Doctor $doctor): JsonResponse
    {
        return response()->json([
            'total_hospitals' => $doctor->countTargets(),
            'total_doctors' => $doctor->countRattachables(),
            'hospitals_by_role' => [
                'doctor' => $doctor->countTargetsByRole(HospitalUserRole::DOCTOR),
                'admin' => $doctor->countTargetsByRole(HospitalUserRole::ADMIN),
                'staff' => $doctor->countTargetsByRole(HospitalUserRole::STAFF),
            ],
            'distinct_roles' => $doctor->getDistinctRoles(),
        ]);
    }
}
```

---

## 📦 Dépendances

- [`andydefer/domain-structures`](https://github.com/andydefer/domain-structures) - Structures de domaine (Value Objects, Records)
- [`andydefer/laravel-repository`](https://github.com/andydefer/laravel-repository) - Pattern Repository
- [`andydefer/laravel-directive`](https://github.com/andydefer/laravel-directive) - Framework CLI pour la directive d'inspection
- [`andydefer/php-services`](https://github.com/andydefer/php-services) - Services PHP (FileSystem)

---

## 📄 Licence

MIT © [Andy Defer](https://github.com/andydefer)

---

**Construit avec ❤️ pour la communauté Laravel**