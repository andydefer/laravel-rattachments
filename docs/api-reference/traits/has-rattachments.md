# HasRattachments - Référence Technique

## Description

Trait Eloquent qui fournit une API fluide pour gérer les rattachements polymorphiques directement depuis les modèles, sans avoir à injecter le service manuellement.

## Hiérarchie / Implémentations

```
Model (Eloquent)
    └── use HasRattachments
```

**Dépendances :**
- `RattachmentService` - Service de rattachement
- `EnumerableInterface` - Interface pour les rôles

## Rôle principal

Le trait agit comme un **wrapper fluide** autour du `RattachmentService`. Il permet d'utiliser les fonctionnalités de rattachement directement sur les modèles, rendant le code plus lisible et plus proche du langage naturel.

## API

### `attachTo(Model $target, ?EnumerableInterface $role = null, array $metadata = []): Model`

Attache le modèle courant à un autre modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible du rattachement |
| `$role` | `EnumerableInterface|null` | Rôle du rattachement (optionnel) |
| `$metadata` | `array<string, mixed>` | Métadonnées supplémentaires |

**Retourne :** `Model` - L'instance de `Rattachment` créée

**Exceptions :**
- `RuntimeException` - Si le rattachement existe déjà
- `RuntimeException` - Si les contraintes sont violées

**Exemple :**
```php
$user->attachTo($hospital, Role::DOCTOR, [
    'consultation_days' => ['monday', 'wednesday'],
]);
```

---

### `detachFrom(Model $target): void`

Détache le modèle courant d'un autre modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible à détacher |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

**Exemple :**
```php
$user->detachFrom($hospital);
```

---

### `detachAll(): void`

Supprime tous les rattachements du modèle (à la fois comme `rattachable` ET comme `target`).

**Exemple :**
```php
$user->detachAll();
```

---

### `isAttachedTo(Model $target): bool`

Vérifie si le modèle courant est attaché à un autre modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible à vérifier |

**Retourne :** `bool` - `true` si le modèle est rattaché à la cible

**Exemple :**
```php
if ($user->isAttachedTo($hospital)) {
    // ...
}
```

---

### `getTargets(): Collection`

Retourne toutes les cibles du modèle courant.

**Retourne :** `Collection<int, Model>` - Tous les modèles cibles rattachés

**Exemple :**
```php
$hospitals = $user->getTargets();
```

---

### `getTargetsByRole(EnumerableInterface $role): Collection`

Retourne les cibles du modèle courant avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles avec le rôle spécifié

**Exemple :**
```php
$hospitals = $user->getTargetsByRole(Role::DOCTOR);
```

---

### `getTargetsByType(string $targetClass): Collection`

Retourne les cibles du modèle courant d'un type spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible (ex: `Hospital::class`) |

**Retourne :** `Collection<int, Model>` - Cibles du type spécifié

**Exemple :**
```php
$hospitals = $user->getTargetsByType(Hospital::class);
```

---

### `getTargetsByTypePaginated(string $targetClass, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Retourne les cibles d'un type spécifique avec pagination.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

**Exemple :**
```php
$hospitals = $user->getTargetsByTypePaginated(Hospital::class, 10, 1);
```

---

### `getTargetsPaginated(int $perPage = 15, int $page = 1): LengthAwarePaginator`

Retourne toutes les cibles avec pagination.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

**Exemple :**
```php
$hospitals = $user->getTargetsPaginated(10, 1);
```

---

### `getTargetsByTypeAndRole(string $targetClass, EnumerableInterface $role): Collection`

Retourne les cibles d'un type spécifique avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles filtrées par type et rôle

**Exemple :**
```php
$hospitals = $user->getTargetsByTypeAndRole(Hospital::class, Role::DOCTOR);
```

---

### `getTargetsByTypeAndRoles(string $targetClass, array $roles): Collection`

Retourne les cibles d'un type spécifique avec plusieurs rôles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles avec l'un des rôles

**Exemple :**
```php
$hospitals = $user->getTargetsByTypeAndRoles(
    Hospital::class, 
    [Role::DOCTOR, Role::ADMIN]
);
```

---

### `getTargetsByTypesAndRoles(array $targetClasses, array $roles): Collection`

Retourne les cibles de plusieurs types avec plusieurs rôles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClasses` | `array<int, string>` | FQCNs des classes cibles |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles filtrées

**Exemple :**
```php
$targets = $user->getTargetsByTypesAndRoles(
    [Hospital::class, Pharmacy::class], 
    [Role::DOCTOR, Role::PHARMACIST]
);
```

---

### `countTargets(): int`

Compte toutes les cibles du modèle courant.

**Retourne :** `int` - Nombre total de cibles

**Exemple :**
```php
$count = $user->countTargets();
```

---

### `countTargetsByRole(EnumerableInterface $role): int`

Compte les cibles du modèle courant avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre de cibles avec ce rôle

**Exemple :**
```php
$doctorCount = $user->countTargetsByRole(Role::DOCTOR);
```

---

### `updateRoleFor(Model $target, EnumerableInterface $role): void`

Met à jour le rôle pour une cible spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

**Exemple :**
```php
$user->updateRoleFor($hospital, Role::ADMIN);
```

---

### `updateMetadataFor(Model $target, array $metadata): void`

Met à jour les métadonnées pour une cible spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$metadata` | `array<string, mixed>` | Nouvelles métadonnées |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

**Exemple :**
```php
$user->updateMetadataFor($hospital, [
    'consultation_hours' => '10:00-18:00',
]);
```

---

### `mergeMetadataFor(Model $target, array $metadata): void`

Fusionne les métadonnées pour une cible spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$metadata` | `array<string, mixed>` | Métadonnées à fusionner |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

**Exemple :**
```php
$user->mergeMetadataFor($hospital, [
    'availability' => 'Monday-Friday',
]);
```

---

### `getDistinctRoles(): Collection`

Retourne tous les rôles distincts du modèle courant.

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

**Exemple :**
```php
$roles = $user->getDistinctRoles();
// Collection [Role::DOCTOR, Role::ADMIN, Role::PHARMACIST]
```

---

### `syncAttachments(array $targets): Collection`

Synchronise les rattachements du modèle courant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targets` | `array<array{target: Model, role?: EnumerableInterface, metadata?: array<string, mixed>}>` | Cibles avec rôles optionnels |

**Retourne :** `Collection<int, Model>` - Rattachements créés ou mis à jour

**Exceptions :** `RuntimeException` - Si une cible est invalide

**Exemple :**
```php
$user->syncAttachments([
    ['target' => $hospital1, 'role' => Role::DOCTOR],
    ['target' => $hospital2, 'role' => Role::DOCTOR],
]);
```

---

## Cas d'utilisation

### Cas 1 : Gestion des médecins et hôpitaux

```php
class Doctor extends Model
{
    use HasRattachments;
}

$doctor = Doctor::find(1);
$hospital = Hospital::find(1);

// Rattacher le médecin à l'hôpital
$doctor->attachTo($hospital, Role::DOCTOR, [
    'consultation_days' => ['monday', 'wednesday', 'friday'],
    'department' => 'Cardiology',
]);

// Récupérer tous les hôpitaux du médecin
$hospitals = $doctor->getTargetsByRole(Role::DOCTOR);

// Récupérer les hôpitaux avec pagination
$hospitals = $doctor->getTargetsByTypePaginated(Hospital::class, 10, 1);

// Mettre à jour le rôle
$doctor->updateRoleFor($hospital, Role::STAFF);

// Détacher le médecin
$doctor->detachFrom($hospital);
```

### Cas 2 : Relations patient-médecin

```php
class Patient extends Model
{
    use HasRattachments;
}

$patient = Patient::find(1);
$doctor = Doctor::find(1);

// Un patient est suivi par un médecin
$patient->attachTo($doctor, Role::PATIENT_OF, [
    'since' => '2024-01-15',
    'referral' => 'general_practitioner',
]);

// Récupérer les médecins d'un patient
$doctors = $patient->getTargetsByType(Doctor::class);

// Récupérer les patients d'un médecin
$patients = $doctor->getTargetsByType(Patient::class);
```

### Cas 3 : Synchronisation des rattachements

```php
// Synchroniser tous les rattachements d'un médecin
$doctor->syncAttachments([
    ['target' => $hospital1, 'role' => Role::DOCTOR, 'metadata' => ['primary' => true]],
    ['target' => $hospital2, 'role' => Role::DOCTOR],
    ['target' => $pharmacy, 'role' => Role::PHARMACIST],
]);

// Les rattachements précédents sont automatiquement supprimés
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Rattachement déjà existant | `RuntimeException` | `{rattachable} {rattachable_id} is already attached to {target} {target_id}` |
| Rattachement inexistant | `RuntimeException` | `{rattachable} {rattachable_id} is not attached to {target} {target_id}` |
| Target non autorisé | `RuntimeException` | `{rattachable} cannot be attached to {target}. Allowed targets: ...` |
| Rôle non autorisé | `RuntimeException` | `Role "{role}" is not allowed for {rattachable} -> {target}. Allowed roles: ...` |

## Intégration

Le trait s'intègre avec :

- **`RattachmentService`** - Service sous-jacent
- **`RattachmentConstraintsInterface`** - Contraintes de rattachement
- **`EnumerableInterface`** - Rôles

## Performance

- **Toutes les méthodes** : Délèguent au service, donc O(1) ou O(n)
- **Aucune surcharge** : Le trait ajoute juste une couche d'abstraction
- **Pagination** : Gérée par Eloquent avec des requêtes optimisées

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

use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

final class Doctor extends Model
{
    use HasRattachments;
}

// Utilisation
$doctor = Doctor::find(1);

// 1. Rattacher à plusieurs entités
$doctor->attachTo(Hospital::find(1), Role::DOCTOR, ['department' => 'Cardiology']);
$doctor->attachTo(Hospital::find(2), Role::DOCTOR);
$doctor->attachTo(Pharmacy::find(1), Role::PHARMACIST);

// 2. Récupérer les cibles
$hospitals = $doctor->getTargetsByRole(Role::DOCTOR);
$pharmacies = $doctor->getTargetsByType(Pharmacy::class);
$allTargets = $doctor->getTargets();

// 3. Récupérer avec pagination
$hospitalsPaginated = $doctor->getTargetsByTypePaginated(Hospital::class, 10, 1);

// 4. Compter
echo "Hôpitaux: " . $doctor->countTargetsByRole(Role::DOCTOR);
echo "Pharmacies: " . $doctor->countTargetsByType(Pharmacy::class);

// 5. Vérifier
if ($doctor->isAttachedTo($hospital)) {
    echo "Attached to hospital";
}

// 6. Mettre à jour
$doctor->updateRoleFor($hospital, Role::ADMIN);
$doctor->mergeMetadataFor($hospital, ['primary' => true]);

// 7. Synchroniser
$doctor->syncAttachments([
    ['target' => Hospital::find(1), 'role' => Role::DOCTOR],
    ['target' => Hospital::find(2), 'role' => Role::DOCTOR],
]);

// 8. Nettoyer
$doctor->detachFrom($hospital);
$doctor->detachAll(); // Supprime tout
```

## Voir aussi

- `RattachmentService` - Service de rattachement
- `Rattachment` - Modèle du rattachement
- `RattachmentConstraintsInterface` - Interface des contraintes
- `Role` - Énumération des rôles par défaut