# RattachmentService - Référence Technique

## Description

Service central de gestion des rattachements polymorphiques (rattachments) entre modèles Eloquent. Orchestre les opérations CRUD avec validation des contraintes, gestion des rôles et des métadonnées.

## Hiérarchie / Implémentations

```
RattachmentServiceInterface
    └── RattachmentService
```

**Dépendances :**
- `RattachmentRepository` - Accès aux données
- `RattachmentConstraintsInterface` - Validation des contraintes (optionnelle)

## Rôle principal

Le service orchestre toutes les opérations liées aux rattachements :
- Création, mise à jour et suppression de rattachements
- Validation des contraintes (`RattachmentConstraintsInterface`)
- Validation des contraintes uniques (`uniqueTargets()`)
- Gestion des rôles (via `EnumerableInterface`, nullable)
- Gestion des métadonnées (`StrictDataObject`)
- Requêtes paginées et filtrées
- Synchronisation en masse

## API

### `attach(Model $rattachable, Model $target, ?EnumerableInterface $role = null, array $metadata = []): Model`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à rattacher (ex: User, Doctor) |
| `$target` | `Model` | Modèle cible du rattachement (ex: Hospital, Pharmacy) |
| `$role` | `EnumerableInterface|null` | Rôle du rattachement (peut être null) |
| `$metadata` | `array` | Métadonnées supplémentaires (ex: ['priority' => 'high']) |

**Retourne :** `Model` - L'instance de `Rattachment` créée

**Exceptions :**
- `RuntimeException` - Si le rattachement existe déjà
- `RuntimeException` - Si les contraintes sont violées (si `RattachmentConstraintsInterface` est implémenté)
- `RuntimeException` - Si une contrainte unique est violée

**Exemple :**
```php
use AndyDefer\LaravelRattachments\Enums\Role;

$attachment = $service->attach(
    $doctor,
    $hospital,
    Role::DOCTOR,
    ['consultation_days' => ['monday', 'wednesday']]
);
```

---

### `attachMultiple(Collection $rattachables, Model $target, ?EnumerableInterface $role = null, array $metadata = []): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<Model>` | Collection de modèles à rattacher |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface|null` | Rôle pour tous les rattachements |
| `$metadata` | `array` | Métadonnées communes |

**Retourne :** `Collection<Model>` - Collection des rattachements créés

**Exceptions :** `RuntimeException` - Si un rattachement existe déjà ou contraintes violées

**Exemple :**
```php
$attachments = $service->attachMultiple(
    collect([$doctor1, $doctor2, $doctor3]),
    $hospital,
    Role::DOCTOR
);
```

---

### `attachToMultiple(Model $rattachable, Collection $targets, ?EnumerableInterface $role = null, array $metadata = []): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à rattacher |
| `$targets` | `Collection<Model>` | Collection de modèles cibles |
| `$role` | `EnumerableInterface|null` | Rôle pour tous les rattachements |
| `$metadata` | `array` | Métadonnées communes |

**Retourne :** `Collection<Model>` - Collection des rattachements créés

**Exemple :**
```php
$attachments = $service->attachToMultiple(
    $doctor,
    collect([$hospital1, $hospital2, $hospital3]),
    Role::DOCTOR
);
```

---

### `detach(Model $rattachable, Model $target): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à détacher |
| `$target` | `Model` | Modèle cible |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

---

### `detachMultiple(Collection $rattachables, Model $target): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<Model>` | Collection de modèles à détacher |
| `$target` | `Model` | Modèle cible |

---

### `detachFromMultiple(Model $rattachable, Collection $targets): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à détacher |
| `$targets` | `Collection<Model>` | Collection de modèles cibles |

---

### `detachAll(Model $model): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle à détacher de tous ses rattachements (comme rattachable ET target) |

---

### `isAttached(Model $rattachable, Model $target): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à vérifier |
| `$target` | `Model` | Modèle cible |

**Retourne :** `bool` - `true` si le rattachement existe

---

### `hasRoleAttached(Model $target, EnumerableInterface $role): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à vérifier |

**Retourne :** `bool` - `true` si un rattachement avec ce rôle existe

---

### `getRattachables(Model $target): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `Collection<Model>` - Tous les modèles rattachés à la cible

---

### `getRattachablesPaginated(Model $target, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargets(Model $rattachable): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |

**Retourne :** `Collection<Model>` - Toutes les cibles du modèle

---

### `getTargetsPaginated(Model $rattachable, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getRattachablesByRole(Model $target, EnumerableInterface $role): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<Model>` - Modèles rattachés avec le rôle spécifié

---

### `getRattachablesByRolePaginated(Model $target, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargetsByRole(Model $rattachable, EnumerableInterface $role): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<Model>` - Cibles avec le rôle spécifié

---

### `getTargetsByRolePaginated(Model $rattachable, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$role` | `EnumerableInterface` | Rôle à filtrer |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `countRattachables(Model $target): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `int` - Nombre de rattachements

---

### `countTargets(Model $rattachable): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |

**Retourne :** `int` - Nombre de cibles

---

### `countRattachablesByRole(Model $target, EnumerableInterface $role): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre de rattachements avec ce rôle

---

### `countTargetsByRole(Model $rattachable, EnumerableInterface $role): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre de cibles avec ce rôle

---

### `getDistinctRolesForTarget(Model $target): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `Collection` - Rôles distincts pour cette cible

---

### `getDistinctRolesForRattachable(Model $rattachable): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |

**Retourne :** `Collection` - Rôles distincts pour ce modèle

---

### `updateRole(Model $rattachable, Model $target, EnumerableInterface $role): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas ou contraintes violées

---

### `updateRoleForMultiple(Collection $rattachables, Model $target, EnumerableInterface $role): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<Model>` | Collection de modèles rattachés |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

---

### `updateMetadata(Model $rattachable, Model $target, array $metadata): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$target` | `Model` | Modèle cible |
| `$metadata` | `array` | Nouvelles métadonnées |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

---

### `mergeMetadata(Model $rattachable, Model $target, array $metadata): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$target` | `Model` | Modèle cible |
| `$metadata` | `array` | Métadonnées à fusionner |

**Fonctionnement :** Fusionne les nouvelles métadonnées avec les existantes (via `StrictDataObject::merge()`)

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

---

### `getAttachment(Model $rattachable, Model $target): ?Model`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$target` | `Model` | Modèle cible |

**Retourne :** `?Model` - Le rattachement ou null

---

### `hasAttachmentsBetween(Model $rattachable, Model $target): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$target` | `Model` | Modèle cible |

**Retourne :** `bool` - `true` si un rattachement existe entre ces deux modèles spécifiques

---

### `hasAttachmentsBetweenTypes(string $rattachableType, string $targetType): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | Type du modèle rattaché (morph class) |
| `$targetType` | `string` | Type du modèle cible (morph class) |

**Retourne :** `bool` - `true` si un rattachement existe entre ces deux types

---

### `getAttachmentsBetweenTypes(string $rattachableType, string $targetType): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | Type du modèle rattaché (morph class) |
| `$targetType` | `string` | Type du modèle cible (morph class) |

**Retourne :** `Collection<Model>` - Tous les rattachements entre ces deux types

---

### `deleteAllAttachmentsBetweenTypes(string $rattachableType, string $targetType): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | Type du modèle rattaché (morph class) |
| `$targetType` | `string` | Type du modèle cible (morph class) |

**Retourne :** `int` - Nombre de rattachements supprimés

---

### `syncAttachments(Model $rattachable, array $targets): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à synchroniser |
| `$targets` | `array` | Tableau des cibles avec rôles et métadonnées |

**Format de `$targets` :**
```php
[
    [
        'target' => $hospital,        // Model (requis)
        'role' => Role::DOCTOR,       // EnumerableInterface (optionnel)
        'metadata' => ['key' => 'value'], // array (optionnel)
    ],
    // ...
]
```

**Retourne :** `Collection<Model>` - Rattachements créés ou mis à jour

**Fonctionnement :**
1. Crée les nouveaux rattachements
2. Met à jour les rattachements existants
3. Supprime les rattachements non inclus

**Exceptions :** 
- `RuntimeException` - Si un target n'a pas la clé "target"
- `RuntimeException` - Si les contraintes sont violées

---

## Cas d'utilisation

### Cas 1 : Rattachement simple avec rôle

```php
$rattachmentService->attach($doctor, $hospital, ApplicationRole::DOCTOR, [
    'consultation_days' => ['monday', 'wednesday', 'friday'],
    'consultation_hours' => '09:00-17:00',
]);
```

### Cas 2 : Rattachement sans rôle

```php
$rattachmentService->attach($user, $post, null, [
    'relationship' => 'author',
]);
```

### Cas 3 : Contrainte unique

```php
// Dans Hospital.php
public function uniqueTargets(): array
{
    return [User::class]; // Un hôpital ne peut avoir qu'un seul directeur
}

// Utilisation
$rattachmentService->attach($hospital, $user1, Role::ADMIN); // ✅ OK
$rattachmentService->attach($hospital, $user2, Role::ADMIN); // ❌ Exception
```

### Cas 4 : Synchronisation en masse

```php
$rattachmentService->syncAttachments($doctor, [
    ['target' => $hospital1, 'role' => Role::DOCTOR, 'metadata' => ['primary' => true]],
    ['target' => $hospital2, 'role' => Role::DOCTOR],
    ['target' => $pharmacy, 'role' => Role::PHARMACIST],
]);
```

### Cas 5 : Pagination des rattachements

```php
$paginator = $rattachmentService->getRattachablesPaginated($hospital, 15, 1);
foreach ($paginator as $rattachable) {
    echo $rattachable->name;
}
```

### Cas 6 : Récupération des rôles distincts

```php
$roles = $rattachmentService->getDistinctRolesForTarget($hospital);
// Collection ['doctor', 'staff', 'admin']
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Rattachement déjà existant | `RuntimeException` | `{rattachable} {rattachable_id} is already attached to {target} {target_id}` |
| Rattachement inexistant (detach) | `RuntimeException` | `{rattachable} {rattachable_id} is not attached to {target} {target_id}` |
| Target non autorisé (contraintes) | `RuntimeException` | `{rattachable} cannot be attached to {target}. Allowed targets: {allowed_targets}` |
| Rôle non autorisé (contraintes) | `RuntimeException` | `Role "{role}" is not allowed for {rattachable} -> {target}. Allowed roles: {allowed_roles}` |
| Contrainte unique violée | `RuntimeException` | `{rattachable} already has a unique attachment to {targetClass}. Only one {targetClass} is allowed.` |
| Données `syncAttachments` invalides | `RuntimeException` | `Each target must have "target" key` |

## Intégration

Le service s'intègre avec :

- **`RattachmentRepository`** - Pour les opérations d'accès aux données
- **`RattachmentConstraintsInterface`** - Pour les contraintes de rattachement
- **`EnumerableInterface`** - Pour les rôles
- **`RattachmentFilterRecord`** - Pour les filtres
- **`RattachmentRecord`** - Pour les DTOs

## Performance

- **Toutes les opérations CRUD** : O(1) - requêtes SQL uniques
- **`syncAttachments`** : O(n) - une requête pour récupérer les existants + n requêtes pour créer/mettre à jour
- **Validation des contraintes uniques** : O(1) - une requête avec `limit: 1`
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

use AndyDefer\LaravelRattachments\Services\RattachmentService;
use App\Enums\ApplicationRole;

$service = new RattachmentService(
    new RattachmentRepository()
);

$doctor = User::find(1);
$hospital = Hospital::find(1);
$specialty = Specialty::find(1);

// 1. Rattacher un docteur à un hôpital
$service->attach($doctor, $hospital, ApplicationRole::DOCTOR);

// 2. Rattacher un docteur à une spécialité (unique)
$service->attach($doctor, $specialty, ApplicationRole::SPECIALIST);

// 3. Récupérer tous les hôpitaux d'un docteur
$hospitals = $service->getTargetsByRole($doctor, ApplicationRole::DOCTOR);
foreach ($hospitals as $hospital) {
    echo $hospital->name . "\n";
}

// 4. Récupérer tous les docteurs d'un hôpital
$doctors = $service->getRattachablesByRole($hospital, ApplicationRole::DOCTOR);

// 5. Synchroniser tous les rattachements
$service->syncAttachments($doctor, [
    ['target' => $hospital1, 'role' => ApplicationRole::DOCTOR],
    ['target' => $hospital2, 'role' => ApplicationRole::DOCTOR],
]);

// 6. Compter les rattachements
$count = $service->countTargetsByRole($doctor, ApplicationRole::DOCTOR);

// 7. Supprimer un rattachement
$service->detach($doctor, $hospital);

// 8. Supprimer tous les rattachements d'un modèle
$service->detachAll($doctor);
```

## Voir aussi

- `Rattachment` - Modèle Eloquent du rattachement
- `RattachmentRepository` - Accès aux données
- `RattachmentConstraintsInterface` - Interface pour les contraintes
- `ApplicationRole` - Énumération des rôles
- `RattachmentFilterRecord` - DTO de filtrage
- `RattachmentRecord` - DTO de création/mise à jour