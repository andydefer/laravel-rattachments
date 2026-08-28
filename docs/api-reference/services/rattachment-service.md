# RattachmentService - Référence Technique

## Description

Service central de gestion des rattachements polymorphiques entre modèles Eloquent. Orchestre les opérations CRUD avec validation des contraintes, gestion des rôles et des métadonnées.

## Hiérarchie / Implémentations

```
RattachmentServiceInterface
    └── RattachmentService
```

**Dépendances :**
- `RattachmentRepositoryInterface` - Accès aux données
- `RattachmentConstraintsInterface` - Validation des contraintes (optionnelle)

## Rôle principal

Le service orchestre toutes les opérations liées aux rattachements :
- Création, mise à jour et suppression de rattachements
- Validation des contraintes (`allowedTargets`, `disallowedTargets`, `uniqueTargets`)
- Gestion des rôles (via `EnumerableInterface`, nullable)
- Gestion des métadonnées (`StrictDataObject`)
- Requêtes paginées et filtrées
- Synchronisation en masse

---

## API

### `attach(Model $rattachable, Model $target, ?EnumerableInterface $role = null, array $metadata = []): Model`

Crée un rattachement entre deux modèles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à rattacher (ex: User, Doctor) |
| `$target` | `Model` | Modèle cible (ex: Hospital, Pharmacy) |
| `$role` | `EnumerableInterface|null` | Rôle du rattachement (optionnel) |
| `$metadata` | `array<string, mixed>` | Métadonnées supplémentaires |

**Retourne :** `Model` - L'instance de `Rattachment` créée

**Exceptions :**
- `RuntimeException` - Si le rattachement existe déjà
- `RuntimeException` - Si les contraintes sont violées

**Exemple :**
```php
$attachment = $service->attach(
    $doctor,
    $hospital,
    Role::DOCTOR,
    ['consultation_days' => ['monday', 'wednesday']]
);
```

---

### `attachMultiple(Collection $rattachables, Model $target, ?EnumerableInterface $role = null, array $metadata = []): Collection`

Attache plusieurs modèles à une même cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Collection de modèles à rattacher |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface|null` | Rôle pour tous les rattachements |
| `$metadata` | `array<string, mixed>` | Métadonnées communes |

**Retourne :** `Collection<int, Model>` - Collection des rattachements créés

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

Attache un modèle à plusieurs cibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à rattacher |
| `$targets` | `Collection<int, Model>` | Collection de modèles cibles |
| `$role` | `EnumerableInterface|null` | Rôle pour tous les rattachements |
| `$metadata` | `array<string, mixed>` | Métadonnées communes |

**Retourne :** `Collection<int, Model>` - Collection des rattachements créés

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

Supprime un rattachement entre deux modèles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à détacher |
| `$target` | `Model` | Modèle cible |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

---

### `detachMultiple(Collection $rattachables, Model $target): void`

Supprime plusieurs modèles d'une même cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Collection de modèles à détacher |
| `$target` | `Model` | Modèle cible |

---

### `detachFromMultiple(Model $rattachable, Collection $targets): void`

Supprime un modèle de plusieurs cibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à détacher |
| `$targets` | `Collection<int, Model>` | Collection de modèles cibles |

---

### `detachAll(Model $model): void`

Supprime tous les rattachements d'un modèle (comme rattachable ET target).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle à détacher |

---

### `isAttached(Model $rattachable, Model $target): bool`

Vérifie si un modèle est attaché à un autre.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à vérifier |
| `$target` | `Model` | Modèle cible |

**Retourne :** `bool` - `true` si le rattachement existe

---

### `hasRoleAttached(Model $target, EnumerableInterface $role): bool`

Vérifie si un rôle existe pour une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à vérifier |

**Retourne :** `bool` - `true` si un rattachement avec ce rôle existe

---

### `getRattachables(Model $target): Collection`

Récupère tous les modèles attachés à une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `Collection<int, Model>` - Modèles attachés

---

### `getRattachablesPaginated(Model $target, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Récupère les modèles attachés à une cible avec pagination.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargets(Model $rattachable): Collection`

Récupère toutes les cibles d'un modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |

**Retourne :** `Collection<int, Model>` - Cibles du modèle

---

### `getTargetsPaginated(Model $rattachable, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Récupère les cibles d'un modèle avec pagination.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getRattachablesByRole(Model $target, EnumerableInterface $role): Collection`

Récupère les modèles attachés à une cible avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Modèles avec le rôle spécifié

---

### `getRattachablesByRolePaginated(Model $target, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Récupère les modèles attachés par rôle avec pagination.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargetsByRole(Model $rattachable, EnumerableInterface $role): Collection`

Récupère les cibles d'un modèle avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles avec le rôle spécifié

---

### `getTargetsByRolePaginated(Model $rattachable, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Récupère les cibles par rôle avec pagination.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$role` | `EnumerableInterface` | Rôle à filtrer |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargetsByType(Model $rattachable, string $targetClass): Collection`

Récupère les cibles d'un type spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$targetClass` | `string` | FQCN de la classe cible |

**Retourne :** `Collection<int, Model>` - Cibles du type spécifié

**Exemple :**
```php
$hospitals = $service->getTargetsByType($doctor, Hospital::class);
```

---

### `getTargetsByTypePaginated(Model $rattachable, string $targetClass, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Récupère les cibles d'un type spécifique avec pagination.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$targetClass` | `string` | FQCN de la classe cible |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargetsByTypeAndRole(Model $rattachable, string $targetClass, EnumerableInterface $role): Collection`

Récupère les cibles d'un type spécifique avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$targetClass` | `string` | FQCN de la classe cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles filtrées par type et rôle

**Exemple :**
```php
$hospitals = $service->getTargetsByTypeAndRole(
    $doctor, 
    Hospital::class, 
    Role::DOCTOR
);
```

---

### `getTargetsByTypeAndRoles(Model $rattachable, string $targetClass, array $roles): Collection`

Récupère les cibles d'un type spécifique avec plusieurs rôles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$targetClass` | `string` | FQCN de la classe cible |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles avec l'un des rôles

**Exemple :**
```php
$hospitals = $service->getTargetsByTypeAndRoles(
    $doctor, 
    Hospital::class, 
    [Role::DOCTOR, Role::ADMIN]
);
```

---

### `getTargetsByTypesAndRoles(Model $rattachable, array $targetClasses, array $roles): Collection`

Récupère les cibles de plusieurs types avec plusieurs rôles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$targetClasses` | `array<int, string>` | FQCNs des classes cibles |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles filtrées

**Exemple :**
```php
$targets = $service->getTargetsByTypesAndRoles(
    $doctor, 
    [Hospital::class, Pharmacy::class], 
    [Role::DOCTOR, Role::PHARMACIST]
);
```

---

### `countRattachables(Model $target): int`

Compte tous les modèles attachés à une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `int` - Nombre de rattachements

---

### `countTargets(Model $rattachable): int`

Compte toutes les cibles d'un modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |

**Retourne :** `int` - Nombre de cibles

---

### `countRattachablesByRole(Model $target, EnumerableInterface $role): int`

Compte les modèles attachés à une cible avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre de rattachements avec ce rôle

---

### `countTargetsByRole(Model $rattachable, EnumerableInterface $role): int`

Compte les cibles d'un modèle avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre de cibles avec ce rôle

---

### `getDistinctRolesForTarget(Model $target): Collection`

Récupère tous les rôles distincts pour une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

---

### `getDistinctRolesForRattachable(Model $rattachable): Collection`

Récupère tous les rôles distincts pour un modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

---

### `updateRole(Model $rattachable, Model $target, EnumerableInterface $role): void`

Met à jour le rôle d'un rattachement existant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

**Exceptions :** 
- `RuntimeException` - Si le rattachement n'existe pas
- `RuntimeException` - Si les contraintes sont violées

---

### `updateRoleForMultiple(Collection $rattachables, Model $target, EnumerableInterface $role): void`

Met à jour le rôle de plusieurs rattachements.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Collection de modèles attachés |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

---

### `updateMetadata(Model $rattachable, Model $target, array $metadata): void`

Met à jour les métadonnées d'un rattachement existant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$target` | `Model` | Modèle cible |
| `$metadata` | `array<string, mixed>` | Nouvelles métadonnées |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

---

### `mergeMetadata(Model $rattachable, Model $target, array $metadata): void`

Fusionne les métadonnées d'un rattachement existant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$target` | `Model` | Modèle cible |
| `$metadata` | `array<string, mixed>` | Métadonnées à fusionner |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

---

### `getAttachment(Model $rattachable, Model $target): ?Model`

Récupère un rattachement spécifique entre deux modèles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$target` | `Model` | Modèle cible |

**Retourne :** `?Model` - Le rattachement ou `null`

---

### `hasAttachmentsBetween(Model $rattachable, Model $target): bool`

Vérifie si un rattachement existe entre deux modèles spécifiques.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle attaché |
| `$target` | `Model` | Modèle cible |

**Retourne :** `bool` - `true` si le rattachement existe

---

### `hasAttachmentsBetweenTypes(string $rattachableType, string $targetType): bool`

Vérifie si des rattachements existent entre deux types de modèles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | Morph class du modèle attaché |
| `$targetType` | `string` | Morph class du modèle cible |

**Retourne :** `bool` - `true` si un rattachement existe

---

### `getAttachmentsBetweenTypes(string $rattachableType, string $targetType): Collection`

Récupère tous les rattachements entre deux types de modèles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | Morph class du modèle attaché |
| `$targetType` | `string` | Morph class du modèle cible |

**Retourne :** `Collection<int, Model>` - Rattachements trouvés

---

### `deleteAllAttachmentsBetweenTypes(string $rattachableType, string $targetType): int`

Supprime tous les rattachements entre deux types de modèles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | Morph class du modèle attaché |
| `$targetType` | `string` | Morph class du modèle cible |

**Retourne :** `int` - Nombre de rattachements supprimés

---

### `syncAttachments(Model $rattachable, array $targets): Collection`

Synchronise les rattachements d'un modèle avec une liste de cibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à synchroniser |
| `$targets` | `array<array{target: Model, role?: EnumerableInterface, metadata?: array<string, mixed>}>` | Cibles avec rôles optionnels |

**Retourne :** `Collection<int, Model>` - Rattachements créés ou mis à jour

**Exceptions :** 
- `RuntimeException` - Si une cible est invalide
- `RuntimeException` - Si les contraintes sont violées

**Fonctionnement :**
1. Crée les nouveaux rattachements
2. Met à jour les rattachements existants
3. Supprime les rattachements non inclus

---

## Cas d'utilisation

### Cas 1 : Gestion des médecins et hôpitaux

```php
$rattachmentService->attach($doctor, $hospital, Role::DOCTOR, [
    'consultation_days' => ['monday', 'wednesday', 'friday'],
    'consultation_hours' => '09:00-17:00',
]);

$hospitals = $rattachmentService->getTargetsByRole($doctor, Role::DOCTOR);
$doctors = $rattachmentService->getRattachablesByRole($hospital, Role::DOCTOR);
```

### Cas 2 : Rattachement sans rôle

```php
$rattachmentService->attach($user, $post, null, [
    'relationship' => 'author',
]);
```

### Cas 3 : Synchronisation en masse

```php
$rattachmentService->syncAttachments($doctor, [
    ['target' => $hospital1, 'role' => Role::DOCTOR, 'metadata' => ['primary' => true]],
    ['target' => $hospital2, 'role' => Role::DOCTOR],
    ['target' => $pharmacy, 'role' => Role::PHARMACIST],
]);
```

### Cas 4 : Filtrage par type et rôle

```php
$hospitals = $rattachmentService->getTargetsByTypeAndRole(
    $doctor, 
    Hospital::class, 
    Role::DOCTOR
);

$targets = $rattachmentService->getTargetsByTypesAndRoles(
    $doctor, 
    [Hospital::class, Pharmacy::class], 
    [Role::DOCTOR, Role::PHARMACIST]
);
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Rattachement déjà existant | `RuntimeException` | `{rattachable} {rattachable_id} is already attached to {target} {target_id}` |
| Rattachement inexistant (detach) | `RuntimeException` | `{rattachable} {rattachable_id} is not attached to {target} {target_id}` |
| Target non autorisé | `RuntimeException` | `{rattachable} cannot be attached to {target}. Allowed targets: {allowed_targets}` |
| Target interdit | `RuntimeException` | `{rattachable} cannot be attached to {target}. This target is disallowed.` |
| Conflit de contraintes | `RuntimeException` | `Constraint conflict in {rattachable}: The following targets are both allowed and disallowed: {conflicts}` |
| Rôle non autorisé | `RuntimeException` | `Role "{role}" is not allowed for {rattachable} -> {target}. Allowed roles: {allowed_roles}` |
| Contrainte unique violée | `RuntimeException` | `{rattachable} already has a unique attachment to {targetClass}. Only one {targetClass} is allowed.` |
| Données `syncAttachments` invalides | `RuntimeException` | `Each target must have "target" key` |

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
$hospitals = $service->getTargetsByType($doctor, Hospital::class);

// 4. Récupérer les hôpitaux où le docteur est médecin
$hospitals = $service->getTargetsByTypeAndRole(
    $doctor, 
    Hospital::class, 
    ApplicationRole::DOCTOR
);

// 5. Synchroniser tous les rattachements
$service->syncAttachments($doctor, [
    ['target' => $hospital1, 'role' => ApplicationRole::DOCTOR],
    ['target' => $hospital2, 'role' => ApplicationRole::DOCTOR],
]);

// 6. Compter les rattachements
$count = $service->countTargets($doctor);

// 7. Supprimer un rattachement
$service->detach($doctor, $hospital);

// 8. Supprimer tous les rattachements d'un modèle
$service->detachAll($doctor);
```

## Voir aussi

- `Rattachment` - Modèle Eloquent du rattachement
- `RattachmentRepository` - Accès aux données
- `RattachmentConstraintsInterface` - Interface des contraintes
- `ApplicationRole` - Énumération des rôles
- `HasRattachments` - Trait pour les modèles