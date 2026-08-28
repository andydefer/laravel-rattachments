# RattachmentService - Référence Technique

## Description

Service central de gestion des attachements polymorphiques entre modèles Eloquent. Orchestre toutes les opérations de création, lecture, mise à jour et suppression des relations d'attachement.

## Hiérarchie / Implémentations

```
RattachmentServiceInterface
    └── RattachmentService
```

## Rôle principal

Ce service est le point d'entrée unique pour toutes les opérations liées aux attachements. Il garantit l'intégrité des données en validant les contraintes, exécute les hooks de cycle de vie, et fournit une API complète pour interroger les relations.

### Composants intégrés

- **ConstraintValidator** : Validation des contraintes (allowed, unique, disallowed)
- **Hooks** : Points d'extension avant/après chaque opération
- **Repository** : Accès aux données via le pattern Repository
- **Records** : DTOs pour les opérations de création et filtrage

---

## API / Méthodes publiques

### `attach(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, EnumerableInterface $role, array $metadata = []): Model`

Crée un attachement entre deux modèles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source qui attache |
| `$target` | `Model&RattachmentInterface` | Modèle cible à attacher |
| `$role` | `EnumerableInterface` | Rôle de l'attachement |
| `$metadata` | `array<string, mixed>` | Métadonnées optionnelles |

**Retourne :** `Model` - L'attachement créé

**Exceptions :** `RuntimeException` - Si les contraintes sont violées ou l'attachement existe déjà

**Exemple :**
```php
$service = app(RattachmentService::class);

$attachment = $service->attach(
    $user,
    $hospital,
    HospitalRole::DOCTOR,
    ['department' => 'cardiology']
);
```

---

### `attachMultiple(Collection $rattachables, Model&RattachmentInterface $target, EnumerableInterface $role, array $metadata = []): Collection`

Attache plusieurs modèles à une même cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model&RattachmentInterface>` | Modèles sources |
| `$target` | `Model&RattachmentInterface` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle pour tous les attachements |
| `$metadata` | `array<string, mixed>` | Métadonnées communes |

**Retourne :** `Collection<int, Model>` - Collection des attachements créés

**Exemple :**
```php
$users = User::where('department', 'cardiology')->get();
$attachments = $service->attachMultiple($users, $hospital, HospitalRole::DOCTOR);
```

---

### `attachToMultiple(Model&RattachmentInterface $rattachable, Collection $targets, EnumerableInterface $role, array $metadata = []): Collection`

Attache un modèle à plusieurs cibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$targets` | `Collection<int, Model&RattachmentInterface>` | Cibles |
| `$role` | `EnumerableInterface` | Rôle pour tous les attachements |
| `$metadata` | `array<string, mixed>` | Métadonnées communes |

**Retourne :** `Collection<int, Model>` - Collection des attachements créés

---

### `detach(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target): void`

Supprime un attachement entre deux modèles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$target` | `Model&RattachmentInterface` | Modèle cible |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas

**Exemple :**
```php
$service->detach($user, $hospital);
```

---

### `detachMultiple(Collection $rattachables, Model&RattachmentInterface $target): void`

Supprime plusieurs attachements vers une même cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model&RattachmentInterface>` | Modèles sources |
| `$target` | `Model&RattachmentInterface` | Modèle cible |

---

### `detachFromMultiple(Model&RattachmentInterface $rattachable, Collection $targets): void`

Supprime les attachements d'un modèle vers plusieurs cibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$targets` | `Collection<int, Model&RattachmentInterface>` | Cibles |

---

### `detachAll(Model&RattachmentInterface $model): void`

Supprime tous les attachements d'un modèle (comme rattachable et comme target).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model&RattachmentInterface` | Modèle à détacher |

**Exemple :**
```php
$service->detachAll($user);
// Supprime tous les attachements où User est rattachable OU target
```

---

### `isAttached(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target): bool`

Vérifie si un attachement exact existe.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$target` | `Model&RattachmentInterface` | Modèle cible |

**Retourne :** `bool` - `true` si l'attachement existe

**Exemple :**
```php
if ($service->isAttached($user, $hospital)) {
    // ...
}
```

---

### `hasRoleAttached(Model&RattachmentInterface $target, EnumerableInterface $role): bool`

Vérifie si une cible a un attachement avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model&RattachmentInterface` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à vérifier |

**Retourne :** `bool` - `true` si un attachement avec ce rôle existe

---

### `getRattachables(Model&RattachmentInterface $target): Collection`

Récupère tous les modèles attachés à une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model&RattachmentInterface` | Modèle cible |

**Retourne :** `Collection<int, Model&RattachmentInterface>` - Modèles attachés

**Exemple :**
```php
$doctors = $service->getRattachables($hospital);
```

---

### `getRattachablesPaginated(Model&RattachmentInterface $target, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getRattachables()`.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model&RattachmentInterface` | Modèle cible |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargets(Model&RattachmentInterface $rattachable): Collection`

Récupère toutes les cibles attachées à un modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |

**Retourne :** `Collection<int, Model&RattachmentInterface>` - Cibles attachées

**Exemple :**
```php
$hospitals = $service->getTargets($user);
```

---

### `getTargetsPaginated(Model&RattachmentInterface $rattachable, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getTargets()`.

---

### `getRattachablesByRole(Model&RattachmentInterface $target, EnumerableInterface $role): Collection`

Récupère les modèles attachés à une cible avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model&RattachmentInterface` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model&RattachmentInterface>` - Modèles filtrés

**Exemple :**
```php
$chiefs = $service->getRattachablesByRole($hospital, HospitalRole::CHIEF);
```

---

### `getRattachablesByRolePaginated(Model&RattachmentInterface $target, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getRattachablesByRole()`.

---

### `getTargetsByRole(Model&RattachmentInterface $rattachable, EnumerableInterface $role): Collection`

Récupère les cibles attachées à un modèle avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model&RattachmentInterface>` - Cibles filtrées

**Exemple :**
```php
$hospitals = $service->getTargetsByRole($user, HospitalRole::DOCTOR);
```

---

### `getTargetsByRolePaginated(Model&RattachmentInterface $rattachable, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getTargetsByRole()`.

---

### `getTargetsByType(Model&RattachmentInterface $rattachable, string $targetClass): Collection`

Récupère les cibles d'un type spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$targetClass` | `string` | FQCN de la classe cible |

**Retourne :** `Collection<int, Model&RattachmentInterface>` - Cibles du type

**Exemple :**
```php
$hospitals = $service->getTargetsByType($user, Hospital::class);
```

---

### `getTargetsByTypePaginated(Model&RattachmentInterface $rattachable, string $targetClass, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getTargetsByType()`.

---

### `getTargetsByTypeAndRole(Model&RattachmentInterface $rattachable, string $targetClass, EnumerableInterface $role): Collection`

Récupère les cibles d'un type et rôle spécifiques.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$targetClass` | `string` | FQCN de la classe cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model&RattachmentInterface>` - Cibles filtrées

**Exemple :**
```php
$hospitals = $service->getTargetsByTypeAndRole($user, Hospital::class, HospitalRole::DOCTOR);
```

---

### `getTargetsByTypeAndRoles(Model&RattachmentInterface $rattachable, string $targetClass, array $roles): Collection`

Récupère les cibles d'un type avec plusieurs rôles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$targetClass` | `string` | FQCN de la classe cible |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model&RattachmentInterface>` - Cibles correspondantes

---

### `getTargetsByTypesAndRoles(Model&RattachmentInterface $rattachable, array $targetClasses, array $roles): Collection`

Récupère les cibles de plusieurs types avec plusieurs rôles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$targetClasses` | `array<int, string>` | FQCN des classes cibles |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model&RattachmentInterface>` - Cibles correspondantes

---

### `countRattachables(Model&RattachmentInterface $target): int`

Compte les modèles attachés à une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model&RattachmentInterface` | Modèle cible |

**Retourne :** `int` - Nombre total

---

### `countTargets(Model&RattachmentInterface $rattachable): int`

Compte les cibles attachées à un modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |

**Retourne :** `int` - Nombre total

---

### `countRattachablesByRole(Model&RattachmentInterface $target, EnumerableInterface $role): int`

Compte les modèles attachés avec un rôle spécifique.

---

### `countTargetsByRole(Model&RattachmentInterface $rattachable, EnumerableInterface $role): int`

Compte les cibles avec un rôle spécifique.

---

### `getDistinctRolesForTarget(Model&RattachmentInterface $target): Collection`

Récupère les rôles distincts pour un modèle comme cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model&RattachmentInterface` | Modèle cible |

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

---

### `getDistinctRolesForRattachable(Model&RattachmentInterface $rattachable): Collection`

Récupère les rôles distincts pour un modèle comme rattachable.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

---

### `updateRole(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, EnumerableInterface $role): void`

Met à jour le rôle d'un attachement existant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$target` | `Model&RattachmentInterface` | Modèle cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas ou contrainte violée

**Exemple :**
```php
$service->updateRole($user, $hospital, HospitalRole::CHIEF);
```

---

### `updateRoleForMultiple(Collection $rattachables, Model&RattachmentInterface $target, EnumerableInterface $role): void`

Met à jour le rôle de plusieurs attachements vers une même cible.

---

### `updateMetadata(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, array|StrictDataObject $metadata): void`

Met à jour les métadonnées d'un attachement.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$target` | `Model&RattachmentInterface` | Modèle cible |
| `$metadata` | `array\|StrictDataObject` | Nouvelles métadonnées |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas

**Exemple :**
```php
$service->updateMetadata($user, $hospital, [
    'department' => 'neurology',
    'end_date' => '2025-12-31'
]);
```

---

### `mergeMetadata(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, array|StrictDataObject $metadata): void`

Fusionne des métadonnées (conserve les existantes).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$target` | `Model&RattachmentInterface` | Modèle cible |
| `$metadata` | `array\|StrictDataObject` | Métadonnées à fusionner |

**Exemple :**
```php
$service->mergeMetadata($user, $hospital, [
    'availability' => 'Monday-Friday'
]);
```

---

### `getAttachment(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target): ?Model`

Récupère un attachement spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$target` | `Model&RattachmentInterface` | Modèle cible |

**Retourne :** `Model|null` - L'attachement ou `null`

**Exemple :**
```php
$attachment = $service->getAttachment($user, $hospital);
if ($attachment) {
    echo $attachment->role->getValue();
}
```

---

### `hasAttachmentsBetween(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target): bool`

Vérifie si un attachement existe entre deux modèles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$target` | `Model&RattachmentInterface` | Modèle cible |

**Retourne :** `bool` - `true` si l'attachement existe

---

### `hasAttachmentsBetweenTypes(string $rattachableType, string $targetType): bool`

Vérifie si des attachements existent entre deux types.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | FQCN du modèle source |
| `$targetType` | `string` | FQCN du modèle cible |

**Retourne :** `bool` - `true` si des attachements existent

---

### `getAttachmentsBetweenTypes(string $rattachableType, string $targetType): Collection`

Récupère tous les attachements entre deux types.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | FQCN du modèle source |
| `$targetType` | `string` | FQCN du modèle cible |

**Retourne :** `Collection<int, Model>` - Attachements trouvés

---

### `deleteAllAttachmentsBetweenTypes(string $rattachableType, string $targetType): int`

Supprime tous les attachements entre deux types.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | FQCN du modèle source |
| `$targetType` | `string` | FQCN du modèle cible |

**Retourne :** `int` - Nombre d'attachements supprimés

---

### `syncAttachments(Model&RattachmentInterface $rattachable, array $targets): Collection`

Synchronise les attachements d'un modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$targets` | `array<array{target: Model&RattachmentInterface, role: EnumerableInterface, metadata?: array<string, mixed>}>` | Cibles avec rôles |

**Retourne :** `Collection<int, Model>` - Attachements créés/mis à jour

**Exceptions :** `RuntimeException` - Si un target est invalide

**Exemple :**
```php
$attachments = $service->syncAttachments($user, [
    ['target' => $hospital1, 'role' => HospitalRole::DOCTOR, 'metadata' => ['primary' => true]],
    ['target' => $hospital2, 'role' => HospitalRole::DOCTOR],
]);
// Les hôpitaux précédents non inclus sont supprimés
```

---

## Cas d'utilisation

### Cas 1 : Gestion des médecins d'un hôpital

```php
class HospitalService
{
    public function assignDoctor(Doctor $doctor, Hospital $hospital): void
    {
        $this->rattachmentService->attach(
            $doctor,
            $hospital,
            HospitalRole::DOCTOR,
            ['assigned_at' => now()->toDateTimeString()]
        );
    }

    public function getDoctors(Hospital $hospital): Collection
    {
        return $this->rattachmentService->getRattachablesByRole($hospital, HospitalRole::DOCTOR);
    }

    public function promoteToChief(Doctor $doctor, Hospital $hospital): void
    {
        $this->rattachmentService->updateRole($doctor, $hospital, HospitalRole::CHIEF);
    }
}
```

### Cas 2 : Gestion des tags d'un article

```php
class PostService
{
    public function syncTags(Post $post, array $tagData): Collection
    {
        $targets = [];
        foreach ($tagData as $data) {
            $targets[] = [
                'target' => $data['tag'],
                'role' => $data['role'] ?? TagRole::TAG,
                'metadata' => ['added_by' => auth()->id()],
            ];
        }

        return $this->rattachmentService->syncAttachments($post, $targets);
    }
}
```

### Cas 3 : Relations sociales

```php
class UserService
{
    public function follow(User $follower, User $followee): void
    {
        $this->rattachmentService->attach($follower, $followee, FollowRole::FOLLOWER);
    }

    public function unfollow(User $follower, User $followee): void
    {
        $this->rattachmentService->detach($follower, $followee);
    }

    public function getFollowers(User $user): Collection
    {
        return $this->rattachmentService->getRattachablesByRole($user, FollowRole::FOLLOWER);
    }
}
```

### Cas 4 : Gestion d'équipe

```php
class TeamService
{
    public function addMember(Team $team, User $user, TeamRole $role): void
    {
        $this->rattachmentService->attach(
            $team,
            $user,
            $role,
            ['joined_at' => now()->toDateTimeString()]
        );
    }

    public function removeMember(Team $team, User $user): void
    {
        $this->rattachmentService->detach($team, $user);
    }

    public function getMembers(Team $team): Collection
    {
        return $this->rattachmentService->getRattachables($team);
    }
}
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Rattachable n'implémente pas l'interface | `RuntimeException` | `Model {class} must implement RattachmentInterface to be attachable.` |
| Target n'implémente pas l'interface | `RuntimeException` | `Model {class} must implement RattachmentInterface to be a target.` |
| Target non autorisé | `RuntimeException` | `{rattachable} cannot be attached to {target}. Allowed targets: {allowed}` |
| Rôle non autorisé | `RuntimeException` | `Role "{role}" is not allowed for {rattachable} -> {target}. Allowed roles: {allowed}` |
| Rôle interdit | `RuntimeException` | `Role "{role}" is disallowed for {rattachable} -> {target}. Disallowed roles: {disallowed}` |
| Attachement existe déjà | `RuntimeException` | `{rattachable} {id} is already attached to {target} {id}` |
| Contrainte unique | `RuntimeException` | `{rattachable} already has a unique attachment to {target} with role "{role}". Only one {target} with role {role} is allowed.` |
| Attachement inexistant | `RuntimeException` | `{rattachable} {id} is not attached to {target} {id}` |
| Sync sans `target` | `RuntimeException` | `Each target must have "target" key` |
| Sync sans `role` | `RuntimeException` | `Each target must have "role" key` |

---

## Intégration

Ce service s'intègre avec :

- **RattachmentRepositoryInterface** - Accès aux données
- **ConstraintValidatorInterface** - Validation des contraintes
- **AttachmentHookInterface** - Hooks de cycle de vie
- **RattachmentRecord** - DTO de création
- **RattachmentFilterRecord** - DTO de filtrage
- **FindByRecord / PaginateRecord** - Requêtage avancé

---

## Performance

- Les méthodes de lecture utilisent `exists()` pour les vérifications rapides
- Les requêtes de recherche utilisent `FindByRecord` avec des filtres optimisés
- `syncAttachments()` effectue plusieurs opérations en une seule transaction
- Le chargement des relations est à la charge de l'utilisateur

### Optimisation recommandée

```php
// ⚠️ Éviter - N+1
$targets = $service->getTargets($user);
foreach ($targets as $target) {
    echo $target->name; // Chaque accès charge la relation
}

// ✅ Recommandé - Eager loading via le repository
$rattachments = $repository->findBy(new FindByRecord(...));
$rattachments->load('target');
```

---

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| Laravel 10+ | ✅ Complet |

---

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelRattachments\Services\RattachmentService;
use App\Enums\HospitalRole;
use App\Models\Doctor;
use App\Models\Hospital;

$service = app(RattachmentService::class);

$doctor = Doctor::find(1);
$hospital = Hospital::find(1);

// 1. Attacher
$attachment = $service->attach(
    $doctor,
    $hospital,
    HospitalRole::DOCTOR,
    ['department' => 'Cardiology']
);

// 2. Vérifier
if ($service->isAttached($doctor, $hospital)) {
    echo "Doctor is attached\n";
}

// 3. Récupérer les hôpitaux
$hospitals = $service->getTargetsByRole($doctor, HospitalRole::DOCTOR);

// 4. Compter
$count = $service->countTargets($doctor);
echo "Total hospitals: $count\n";

// 5. Mettre à jour le rôle
$service->updateRole($doctor, $hospital, HospitalRole::CHIEF);

// 6. Récupérer l'attachement
$attachment = $service->getAttachment($doctor, $hospital);
echo $attachment->role->getValue(); // 'chief'

// 7. Synchroniser
$attachments = $service->syncAttachments($doctor, [
    ['target' => Hospital::find(2), 'role' => HospitalRole::DOCTOR],
    ['target' => Hospital::find(3), 'role' => HospitalRole::DOCTOR],
]);

// 8. Détacher
$service->detach($doctor, $hospital);
```

---

## Voir aussi

- `RattachmentServiceInterface` - Interface du service
- `ConstraintValidator` - Validation des contraintes
- `AttachmentHookInterface` - Hooks de cycle de vie
- `RattachmentRecord` - DTO de création
- `RattachmentFilterRecord` - DTO de filtrage
- `FindByRecord` - DTO de recherche
- `PaginateRecord` - DTO de pagination