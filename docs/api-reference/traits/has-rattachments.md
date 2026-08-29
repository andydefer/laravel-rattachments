# HasRattachments - Référence Technique

## Description

Trait PHP qui ajoute une API fluide aux modèles Eloquent pour gérer les attachements polymorphiques. Permet d'attacher, détacher, interroger et synchroniser des relations directement depuis le modèle.

## Hiérarchie / Implémentations

```
HasRattachments (Trait)
    └── Utilisé par les modèles implémentant RattachmentInterface
```

## Rôle principal

Ce trait est le point d'entrée principal pour les développeurs. Il expose une API intuitive sur les modèles, similaire aux relations Eloquent natives. Chaque méthode est un proxy vers le service `RattachmentService`, avec le modèle courant automatiquement passé comme premier paramètre.

### Analogie avec Eloquent

| Eloquent | HasRattachments | Description |
|----------|-----------------|-------------|
| `$user->posts()` | `$user->getTargetsByType(Post::class)` | Récupère les cibles |
| `$post->user()` | `$post->getRattachablesByType(User::class)` | Récupère les rattachables |
| `$user->posts()->attach($post)` | `$user->attachTo($post, Role::AUTHOR)` | Attache une cible |
| `$user->posts()->detach($post)` | `$user->detachFrom($post)` | Détache une cible |
| `$user->posts()->sync([1,2,3])` | `$user->syncAttachments([...])` | Synchronise les cibles |
| `$user->posts()->count()` | `$user->countTargets()` | Compte les cibles |

---

## API / Méthodes publiques

### Méthodes d'écriture

#### `attachTo(Model $target, EnumerableInterface $role, array $metadata = []): Model`

Attache ce modèle à une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible à attacher |
| `$role` | `EnumerableInterface` | Rôle de l'attachement |
| `$metadata` | `array<string, mixed>` | Métadonnées optionnelles |

**Retourne :** `Model` - L'attachement créé

**Exceptions :** `RuntimeException` - Si les contraintes sont violées

**Exemple :**
```php
$user->attachTo($hospital, HospitalRole::DOCTOR);
$user->attachTo($profile, ProfileRole::USER, ['active' => true]);
```

---

#### `attachToMultiple(Collection $targets, EnumerableInterface $role, array $metadata = []): Collection`

Attache ce modèle à plusieurs cibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targets` | `Collection<int, Model>` | Cibles à attacher |
| `$role` | `EnumerableInterface` | Rôle pour tous les attachements |
| `$metadata` | `array<string, mixed>` | Métadonnées communes |

**Retourne :** `Collection<int, Model>` - Attachements créés

**Exemple :**
```php
$hospitals = Hospital::where('city', 'Paris')->get();
$attachments = $user->attachToMultiple($hospitals, HospitalRole::DOCTOR);
```

---

#### `detachFrom(Model $target): void`

Détache ce modèle d'une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible à détacher |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas

**Exemple :**
```php
$user->detachFrom($hospital);
```

---

#### `detachFromMultiple(Collection $targets): void`

Détache ce modèle de plusieurs cibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targets` | `Collection<int, Model>` | Cibles à détacher |

---

#### `detachAll(): void`

Détache ce modèle de tous ses attachements (comme rattachable et comme target).

**Exemple :**
```php
$user->detachAll(); // Supprime toutes les relations de User
```

---

#### `attachMany(Collection $rattachables, EnumerableInterface $role, array $metadata = []): Collection`

Attache plusieurs modèles à ce modèle (comme target).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Modèles à attacher |
| `$role` | `EnumerableInterface` | Rôle pour tous les attachements |
| `$metadata` | `array<string, mixed>` | Métadonnées communes |

**Retourne :** `Collection<int, Model>` - Attachements créés

**Exemple :**
```php
$users = User::where('active', true)->get();
$attachments = $hospital->attachMany($users, HospitalRole::DOCTOR);
```

---

#### `detachMany(Collection $rattachables): void`

Détache plusieurs modèles de ce modèle (comme target).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Modèles à détacher |

---

#### `syncAttachments(array $targets): Collection`

Synchronise les attachements de ce modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targets` | `array<array{target: Model, role: EnumerableInterface, metadata?: array<string, mixed>}>` | Cibles avec rôles |

**Retourne :** `Collection<int, Model>` - Attachements créés/mis à jour

**Exemple :**
```php
$attachments = $user->syncAttachments([
    ['target' => $hospital1, 'role' => HospitalRole::DOCTOR],
    ['target' => $hospital2, 'role' => HospitalRole::CHIEF, 'metadata' => ['primary' => true]],
]);
// Les hôpitaux non listés sont supprimés
```

---

#### `updateRoleFor(Model $target, EnumerableInterface $role): void`

Met à jour le rôle d'un attachement existant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible concernée |
| `$role` | `EnumerableInterface` | Nouveau rôle |

**Exemple :**
```php
$user->updateRoleFor($hospital, HospitalRole::CHIEF);
```

---

#### `updateRoleForMany(Collection $rattachables, EnumerableInterface $role): void`

Met à jour le rôle de plusieurs rattachables attachés à ce modèle.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Modèles concernés |
| `$role` | `EnumerableInterface` | Nouveau rôle |

---

#### `updateMetadataFor(Model $target, array $metadata): void`

Met à jour les métadonnées d'un attachement.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible concernée |
| `$metadata` | `array<string, mixed>` | Nouvelles métadonnées |

**Exemple :**
```php
$user->updateMetadataFor($hospital, ['department' => 'cardiology', 'end_date' => '2025-12-31']);
```

---

#### `mergeMetadataFor(Model $target, array $metadata): void`

Fusionne des métadonnées (conserve les existantes).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible concernée |
| `$metadata` | `array<string, mixed>` | Métadonnées à fusionner |

**Exemple :**
```php
$user->mergeMetadataFor($hospital, ['availability' => 'Monday-Friday']);
```

---

### Méthodes de lecture

#### `isAttachedTo(Model $target): bool`

Vérifie si ce modèle est attaché à une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible à vérifier |

**Retourne :** `bool` - `true` si attaché

**Exemple :**
```php
if ($user->isAttachedTo($hospital)) {
    // ...
}
```

---

#### `hasRoleAttachedTo(Model $target, EnumerableInterface $role): bool`

Vérifie si une cible a un rôle spécifique attaché.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible concernée |
| `$role` | `EnumerableInterface` | Rôle à vérifier |

**Retourne :** `bool` - `true` si le rôle existe

---

#### `getAttachment(Model $target): ?Model`

Récupère un attachement spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible concernée |

**Retourne :** `Model|null` - L'attachement ou `null`

**Exemple :**
```php
$attachment = $user->getAttachment($hospital);
if ($attachment) {
    echo $attachment->role->getValue();
}
```

---

#### `hasAttachmentsBetween(Model $target): bool`

Vérifie si un attachement existe entre ce modèle et une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible à vérifier |

**Retourne :** `bool` - `true` si l'attachement existe

---

### Méthodes de récupération des targets

#### `getTargets(): Collection`

Récupère toutes les cibles attachées à ce modèle.

**Retourne :** `Collection<int, Model>` - Cibles attachées

**Exemple :**
```php
$hospitals = $user->getTargets();
```

---

#### `getTargetsPaginated(int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getTargets()`.

---

#### `getTargetsByRole(EnumerableInterface $role): Collection`

Récupère les cibles attachées avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles filtrées

**Exemple :**
```php
$hospitals = $user->getTargetsByRole(HospitalRole::DOCTOR);
```

---

#### `getTargetsByRolePaginated(EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getTargetsByRole()`.

---

#### `getTargetsByType(string $targetClass): Collection`

Récupère les cibles d'un type spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |

**Retourne :** `Collection<int, Model>` - Cibles du type

**Exemple :**
```php
$hospitals = $user->getTargetsByType(Hospital::class);
```

---

#### `getTargetsByTypePaginated(string $targetClass, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getTargetsByType()`.

---

#### `getTargetsByTypeAndRole(string $targetClass, EnumerableInterface $role): Collection`

Récupère les cibles d'un type et rôle spécifiques.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles filtrées

**Exemple :**
```php
$hospitals = $user->getTargetsByTypeAndRole(Hospital::class, HospitalRole::DOCTOR);
```

---

#### `getTargetsByTypeAndRoles(string $targetClass, array $roles): Collection`

Récupère les cibles d'un type avec plusieurs rôles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles correspondantes

---

#### `getTargetsByTypesAndRoles(array $targetClasses, array $roles): Collection`

Récupère les cibles de plusieurs types avec plusieurs rôles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClasses` | `array<int, string>` | FQCN des classes cibles |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles correspondantes

---

### Méthodes de récupération des rattachables

#### `getRattachables(): Collection`

Récupère tous les modèles attachés à ce modèle (quand ce modèle est target).

**Retourne :** `Collection<int, Model>` - Modèles attachés

**Exemple :**
```php
$doctors = $hospital->getRattachables();
```

---

#### `getRattachablesPaginated(int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getRattachables()`.

---

#### `getRattachablesByRole(EnumerableInterface $role): Collection`

Récupère les modèles attachés avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Modèles filtrés

**Exemple :**
```php
$chiefs = $hospital->getRattachablesByRole(HospitalRole::CHIEF);
```

---

#### `getRattachablesByRolePaginated(EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getRattachablesByRole()`.

---

#### `getRattachablesByType(string $rattachableClass): Collection`

Récupère les modèles attachés d'un type spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableClass` | `string` | FQCN de la classe source |

**Retourne :** `Collection<int, Model>` - Modèles du type

**Exemple :**
```php
$doctors = $hospital->getRattachablesByType(User::class);
```

---

#### `getRattachablesByTypePaginated(string $rattachableClass, int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getRattachablesByType()`.

---

#### `getRattachablesByTypeAndRole(string $rattachableClass, EnumerableInterface $role): Collection`

Récupère les modèles attachés d'un type et rôle spécifiques.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableClass` | `string` | FQCN de la classe source |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Modèles filtrés

**Exemple :**
```php
$chiefs = $hospital->getRattachablesByTypeAndRole(User::class, HospitalRole::CHIEF);
```

---

#### `getRattachablesByTypeAndRoles(string $rattachableClass, array $roles): Collection`

Récupère les modèles attachés d'un type avec plusieurs rôles.

---

#### `getRattachablesByTypesAndRoles(array $rattachableClasses, array $roles): Collection`

Récupère les modèles attachés de plusieurs types avec plusieurs rôles.

---

### Méthodes de comptage et distincts

#### `countTargets(): int`

Compte toutes les cibles attachées.

**Retourne :** `int` - Nombre total

**Exemple :**
```php
$count = $user->countTargets();
```

---

#### `countTargetsByRole(EnumerableInterface $role): int`

Compte les cibles avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre de cibles avec ce rôle

---

#### `countRattachables(): int`

Compte les modèles attachés à ce modèle.

**Retourne :** `int` - Nombre total

---

#### `countRattachablesByRole(EnumerableInterface $role): int`

Compte les modèles attachés avec un rôle spécifique.

---

#### `getDistinctRoles(): Collection`

Récupère les rôles distincts pour ce modèle comme rattachable.

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

---

#### `getDistinctRolesForTarget(): Collection`

Récupère les rôles distincts pour ce modèle comme target.

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

---

## Hooks

Les hooks sont des méthodes vides que vous pouvez surcharger dans votre modèle.

| Hook | Déclenchement | Description |
|------|---------------|-------------|
| `beforeAttach()` | Avant la création | Validation supplémentaire |
| `afterAttach()` | Après la création | Logging, notifications, cache |
| `beforeDetach()` | Avant la suppression | Vérifications |
| `afterDetach()` | Après la suppression | Cleanup, cache |
| `beforeUpdateRole()` | Avant mise à jour du rôle | Validation |
| `afterUpdateRole()` | Après mise à jour du rôle | Logging |
| `beforeUpdateMetadata()` | Avant mise à jour des métadonnées | Validation |
| `afterUpdateMetadata()` | Après mise à jour des métadonnées | Logging |
| `beforeDetachAll()` | Avant suppression de tous | Vérifications |
| `afterDetachAll()` | Après suppression de tous | Cleanup |

### Exemple d'utilisation des hooks

```php
class User extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function afterAttach(
        Model $other,
        EnumerableInterface $role,
        Model $attachment,
        HookPosition $position
    ): void {
        if ($position === HookPosition::RATTACHABLE) {
            Log::info('User attached', [
                'user_id' => $this->id,
                'target_type' => get_class($other),
                'target_id' => $other->getKey(),
                'role' => $role->getValue(),
            ]);

            Cache::forget("user_{$this->id}_attachments");
        }
    }
}
```

---

## Cas d'utilisation

### Cas 1 : Gestion du profil utilisateur

```php
class User extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function attachProfile(Profile $profile): void
    {
        // Un User ne peut avoir qu'un seul Profile (uniqueTargets)
        $this->attachTo($profile, ProfileRole::USER);
    }

    public function getProfile(): ?Profile
    {
        return $this->getTargetsByType(Profile::class)->first();
    }

    public function hasProfile(): bool
    {
        return $this->getTargetsByType(Profile::class)->isNotEmpty();
    }

    // Attribut comme une relation Eloquent
    protected function profile(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Profile => $this->getProfile()
        );
    }
}

// Utilisation
$user->attachProfile($profile);
$profile = $user->profile;
```

---

### Cas 2 : Gestion des médicaments d'un fabricant

```php
class Manufacturer extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function addDrug(Drug $drug): void
    {
        $this->attachTo($drug, DrugRole::MANUFACTURER);
    }

    public function getDrugs(): Collection
    {
        return $this->getTargetsByType(Drug::class);
    }

    public function getActiveDrugs(): Collection
    {
        return $this->getTargetsByType(Drug::class)
            ->filter(fn($drug) => $drug->is_active->isYes());
    }

    public function hasDrugs(): bool
    {
        return $this->getTargetsByType(Drug::class)->isNotEmpty();
    }

    public function countDrugs(): int
    {
        return $this->countTargetsByType(Drug::class);
    }

    protected function drugs(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getDrugs()
        );
    }
}
```

---

### Cas 3 : Réseau social complet

```php
class User extends Model implements RattachmentInterface
{
    use HasRattachments;

    // Amitiés
    public function becomeFriendWith(User $friend): void
    {
        $this->attachTo($friend, FriendRole::FRIEND);
        $friend->attachTo($this, FriendRole::FRIEND);
    }

    public function unfriend(User $friend): void
    {
        $this->detachFrom($friend);
        $friend->detachFrom($this);
    }

    public function getFriends(): Collection
    {
        return $this->getTargetsByTypeAndRole(User::class, FriendRole::FRIEND);
    }

    // Abonnements
    public function follow(User $user): void
    {
        $this->attachTo($user, FollowRole::FOLLOWER);
    }

    public function unfollow(User $user): void
    {
        $this->detachFrom($user);
    }

    public function getFollowers(): Collection
    {
        return $this->getRattachablesByTypeAndRole(User::class, FollowRole::FOLLOWER);
    }

    public function getFollowing(): Collection
    {
        return $this->getTargetsByTypeAndRole(User::class, FollowRole::FOLLOWER);
    }

    // Blocage
    public function block(User $user): void
    {
        $this->attachTo($user, BlockRole::BLOCKED);
        $user->attachTo($this, BlockRole::BLOCKED);
    }

    public function unblock(User $user): void
    {
        $this->detachFrom($user);
        $user->detachFrom($this);
    }

    public function getBlockedUsers(): Collection
    {
        return $this->getTargetsByTypeAndRole(User::class, BlockRole::BLOCKED);
    }

    // Statistiques
    public function getSocialStats(): array
    {
        return [
            'friends' => $this->countTargetsByRole(FriendRole::FRIEND),
            'followers' => $this->countRattachablesByRole(FollowRole::FOLLOWER),
            'following' => $this->countTargetsByRole(FollowRole::FOLLOWER),
            'blocked' => $this->countTargetsByRole(BlockRole::BLOCKED),
        ];
    }
}

// Utilisation
$user->becomeFriendWith($friend);
$friends = $user->friends;
$followers = $user->getFollowers();
$stats = $user->getSocialStats();
```

---

### Cas 4 : Hôpital avec médecins et spécialités

```php
class Hospital extends Model implements RattachmentInterface
{
    use HasRattachments;

    // Gestion des médecins
    public function addDoctor(User $doctor): void
    {
        $this->attachTo($doctor, HospitalRole::DOCTOR);
    }

    public function removeDoctor(User $doctor): void
    {
        $this->detachFrom($doctor);
    }

    public function getDoctors(): Collection
    {
        return $this->getTargetsByTypeAndRole(User::class, HospitalRole::DOCTOR);
    }

    public function getChiefs(): Collection
    {
        return $this->getTargetsByTypeAndRole(User::class, HospitalRole::CHIEF);
    }

    // Gestion des spécialités
    public function addSpecialty(Specialty $specialty): void
    {
        $this->attachTo($specialty, HospitalRole::SPECIALTY);
    }

    public function getSpecialties(): Collection
    {
        return $this->getTargetsByType(Specialty::class);
    }

    public function countDoctors(): int
    {
        return $this->countTargetsByRole(HospitalRole::DOCTOR);
    }

    public function getHospitalStats(): array
    {
        return [
            'doctors' => $this->countTargetsByRole(HospitalRole::DOCTOR),
            'chiefs' => $this->countTargetsByRole(HospitalRole::CHIEF),
            'specialties' => $this->countTargetsByType(Specialty::class),
        ];
    }

    // Attributs
    protected function doctors(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getDoctors()
        );
    }

    protected function specialties(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getSpecialties()
        );
    }

    // Hook pour journaliser
    public function afterAttach(
        Model $other,
        EnumerableInterface $role,
        Model $attachment,
        HookPosition $position
    ): void {
        Log::info('Doctor attached to hospital', [
            'hospital_id' => $this->id,
            'doctor_id' => $other->id,
            'role' => $role->getValue(),
        ]);
    }
}
```

---

### Cas 5 : Offres avec pharmacies et médicaments

```php
class Offer extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function setProvider(Pharmacy $pharmacy): void
    {
        $this->attachTo($pharmacy, OfferRole::PROVIDER);
    }

    public function setTarget(Drug $drug): void
    {
        $this->attachTo($drug, OfferRole::TARGET);
    }

    public function getProvider(): ?Pharmacy
    {
        return $this->getTargetsByTypeAndRole(Pharmacy::class, OfferRole::PROVIDER)->first();
    }

    public function getTarget(): ?Drug
    {
        return $this->getTargetsByTypeAndRole(Drug::class, OfferRole::TARGET)->first();
    }

    public function hasProvider(): bool
    {
        return $this->getTargetsByTypeAndRole(Pharmacy::class, OfferRole::PROVIDER)->isNotEmpty();
    }

    public function hasTarget(): bool
    {
        return $this->getTargetsByTypeAndRole(Drug::class, OfferRole::TARGET)->isNotEmpty();
    }

    // Attributs comme des relations Eloquent
    protected function pharmacy(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Pharmacy => $this->getProvider()
        );
    }

    protected function drug(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Drug => $this->getTarget()
        );
    }
}
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Target non autorisé | `RuntimeException` | `{model} cannot be attached to {target}. Allowed targets: {allowed}` |
| Rôle non autorisé | `RuntimeException` | `Role "{role}" is not allowed for {model} -> {target}. Allowed roles: {allowed}` |
| Rôle interdit | `RuntimeException` | `Role "{role}" is disallowed for {model} -> {target}. Disallowed roles: {disallowed}` |
| Attachement existe déjà | `RuntimeException` | `{model} {id} is already attached to {target} {id}` |
| Contrainte unique violée | `RuntimeException` | `{model} already has a unique attachment to {target} with role "{role}". Only one {target} with role {role} is allowed.` |
| Attachement inexistant | `RuntimeException` | `{model} {id} is not attached to {target} {id}` |
| Circularité détectée | `RuntimeException` | `Circular relationship detected: ...` |

---

## Intégration

Ce trait s'intègre avec :

- **RattachmentService** - Service central
- **RattachmentInterface** - Interface que le modèle doit implémenter
- **ConstraintValidator** - Validation des contraintes
- **Hooks** - Points d'extension

---

## Performance

- Toutes les méthodes sont des proxies vers `RattachmentService`
- Les méthodes de lecture utilisent des requêtes optimisées
- Les hooks sont optionnels (méthodes vides par défaut)

### Optimisation recommandée

```php
// ✅ Bon - Requête unique avec filtrage
$doctors = $hospital->getRattachablesByTypeAndRole(User::class, HospitalRole::DOCTOR);

// ❌ Mauvais - Charge puis filtre en mémoire
$doctors = $hospital->getRattachables()
    ->filter(fn($user) => $user->role === HospitalRole::DOCTOR);
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

namespace App\Models;

use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;
use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use App\Enums\HospitalRole;
use App\Enums\ProfileRole;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements RattachmentInterface
{
    use HasRattachments;

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    public function attachProfile(Profile $profile): void
    {
        $this->attachTo($profile, ProfileRole::USER, [
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function getProfile(): ?Profile
    {
        return $this->getTargetsByType(Profile::class)->first();
    }

    public function addHospital(Hospital $hospital, HospitalRole $role): void
    {
        $this->attachTo($hospital, $role, [
            'assigned_at' => now()->toDateTimeString(),
        ]);
    }

    public function getHospitals(): Collection
    {
        return $this->getTargetsByType(Hospital::class);
    }

    public function getHospitalsWhereDoctor(): Collection
    {
        return $this->getTargetsByTypeAndRole(Hospital::class, HospitalRole::DOCTOR);
    }

    public function isHospitalAdmin(Hospital $hospital): bool
    {
        return $this->getTargetsByTypeAndRole(Hospital::class, HospitalRole::ADMIN)
            ->contains($hospital);
    }

    // ============================================================
    // STATISTIQUES
    // ============================================================

    public function getStats(): array
    {
        return [
            'profile' => $this->hasProfile(),
            'hospitals' => $this->countTargetsByType(Hospital::class),
            'hospitals_doctor' => $this->countTargetsByTypeAndRole(Hospital::class, HospitalRole::DOCTOR),
            'hospitals_admin' => $this->countTargetsByTypeAndRole(Hospital::class, HospitalRole::ADMIN),
        ];
    }

    // ============================================================
    // ATTRIBUTS
    // ============================================================

    protected function profile(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Profile => $this->getProfile()
        );
    }

    protected function hospitals(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getHospitals()
        );
    }

    // ============================================================
    // HOOKS
    // ============================================================

    public function afterAttach(
        Model $other,
        EnumerableInterface $role,
        Model $attachment,
        HookPosition $position
    ): void {
        if ($position === HookPosition::RATTACHABLE) {
            Cache::forget("user_{$this->id}_attachments");
            Log::info('User attached', [
                'user_id' => $this->id,
                'target_type' => get_class($other),
                'target_id' => $other->getKey(),
                'role' => $role->getValue(),
            ]);
        }
    }

    public function afterDetach(
        Model $other,
        Model $attachment,
        HookPosition $position
    ): void {
        if ($position === HookPosition::RATTACHABLE) {
            Cache::forget("user_{$this->id}_attachments");
        }
    }
}
```

---

## Voir aussi

- `RattachmentService` - Service central
- `RattachmentInterface` - Interface du modèle
- `ConstraintValidator` - Validation des contraintes
- `HookPosition` - Position du hook
- `RattachmentFilterRecord` - Filtrage des attachements