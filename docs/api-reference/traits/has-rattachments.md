# HasRattachments - Référence Technique

## Description

Trait PHP pour les modèles Eloquent qui peuvent avoir des attachements polymorphiques. Fournit une API fluide pour gérer les attachements directement depuis le modèle.

## Hiérarchie / Implémentations

```
Trait
    └── HasRattachments
```

## Rôle principal

Ce trait expose toutes les fonctionnalités du `RattachmentService` directement sur les modèles Eloquent. Il permet de :

- Créer et supprimer des attachements
- Lire et filtrer les relations
- Mettre à jour les rôles et métadonnées
- Synchroniser les attachements en masse
- Surcharger les hooks de cycle de vie

L'utilisateur n'a pas besoin d'injecter le service manuellement.

---

## API / Méthodes publiques

### Méthodes d'attachement

#### `attachTo(Model $target, EnumerableInterface $role, array $metadata = []): Model`

Attache le modèle courant à une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle de l'attachement |
| `$metadata` | `array<string, mixed>` | Métadonnées optionnelles |

**Retourne :** `Model` - L'attachement créé

**Exceptions :** `RuntimeException` - Si contrainte violée

**Exemple :**
```php
$user = User::find(1);
$hospital = Hospital::find(1);

$attachment = $user->attachTo($hospital, HospitalRole::DOCTOR, [
    'department' => 'Cardiology'
]);
```

---

#### `attachToMultiple(Collection $targets, EnumerableInterface $role, array $metadata = []): Collection`

Attache le modèle courant à plusieurs cibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targets` | `Collection<int, Model>` | Cibles |
| `$role` | `EnumerableInterface` | Rôle commun |
| `$metadata` | `array<string, mixed>` | Métadonnées communes |

**Retourne :** `Collection<int, Model>` - Attachements créés

**Exemple :**
```php
$hospitals = Hospital::where('city', 'Paris')->get();
$attachments = $user->attachToMultiple($hospitals, HospitalRole::DOCTOR);
```

---

#### `attachMany(Collection $rattachables, EnumerableInterface $role, array $metadata = []): Collection`

Attache plusieurs modèles au modèle courant (comme target).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Modèles à attacher |
| `$role` | `EnumerableInterface` | Rôle commun |
| `$metadata` | `array<string, mixed>` | Métadonnées communes |

**Retourne :** `Collection<int, Model>` - Attachements créés

**Exemple :**
```php
$users = User::where('role', 'doctor')->get();
$attachments = $hospital->attachMany($users, HospitalRole::DOCTOR);
```

---

### Méthodes de détachement

#### `detachFrom(Model $target): void`

Détache le modèle courant d'une cible.

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

Détache le modèle courant de plusieurs cibles.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targets` | `Collection<int, Model>` | Cibles à détacher |

---

#### `detachMany(Collection $rattachables): void`

Détache plusieurs modèles du modèle courant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Modèles à détacher |

---

#### `detachAll(): void`

Supprime tous les attachements du modèle courant (comme rattachable et comme target).

**Exemple :**
```php
$user->detachAll(); // Supprime tous les attachements de l'utilisateur
```

---

### Méthodes de vérification

#### `isAttachedTo(Model $target): bool`

Vérifie si le modèle courant est attaché à une cible.

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

Vérifie si une cible a un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible |
| `$role` | `EnumerableInterface` | Rôle à vérifier |

**Retourne :** `bool` - `true` si le rôle existe

**Exemple :**
```php
if ($user->hasRoleAttachedTo($hospital, HospitalRole::DOCTOR)) {
    // ...
}
```

---

#### `getAttachment(Model $target): ?Model`

Récupère l'attachement entre le modèle courant et une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible |

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

Vérifie si un attachement existe entre le modèle courant et une cible.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible |

**Retourne :** `bool` - `true` si l'attachement existe

---

### Méthodes de lecture (Targets)

#### `getTargets(): Collection`

Récupère toutes les cibles attachées au modèle courant.

**Retourne :** `Collection<int, Model>` - Cibles

**Exemple :**
```php
$hospitals = $user->getTargets();
```

---

#### `getTargetsPaginated(int $perPage = 15, int $page = 1): LengthAwarePaginator`

Version paginée de `getTargets()`.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

#### `getTargetsByRole(EnumerableInterface $role): Collection`

Récupère les cibles avec un rôle spécifique.

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

**Exemple :**
```php
$targets = $user->getTargetsByTypesAndRoles(
    [Hospital::class, Pharmacy::class],
    [HospitalRole::DOCTOR, PharmacyRole::PHARMACIST]
);
```

---

### Méthodes de lecture (Rattachables)

#### `getRattachables(): Collection`

Récupère tous les modèles attachés au modèle courant (quand il est utilisé comme target).

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

### Méthodes de comptage

#### `countTargets(): int`

Compte toutes les cibles attachées au modèle courant.

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

**Retourne :** `int` - Nombre correspondant

---

#### `countRattachables(): int`

Compte tous les modèles attachés au modèle courant.

**Retourne :** `int` - Nombre total

---

#### `countRattachablesByRole(EnumerableInterface $role): int`

Compte les modèles attachés avec un rôle spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre correspondant

---

### Méthodes de rôles distincts

#### `getDistinctRoles(): Collection`

Récupère les rôles distincts pour le modèle courant comme rattachable.

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

**Exemple :**
```php
$roles = $user->getDistinctRoles();
// ['doctor', 'admin', 'staff']
```

---

#### `getDistinctRolesForTarget(): Collection`

Récupère les rôles distincts pour le modèle courant comme target.

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

**Exemple :**
```php
$roles = $hospital->getDistinctRolesForTarget();
```

---

### Méthodes de mise à jour

#### `updateRoleFor(Model $target, EnumerableInterface $role): void`

Met à jour le rôle d'un attachement existant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas

**Exemple :**
```php
$user->updateRoleFor($hospital, HospitalRole::CHIEF);
```

---

#### `updateRoleForMany(Collection $rattachables, EnumerableInterface $role): void`

Met à jour le rôle de plusieurs attachements.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Modèles à mettre à jour |
| `$role` | `EnumerableInterface` | Nouveau rôle |

---

#### `updateMetadataFor(Model $target, array $metadata): void`

Met à jour les métadonnées d'un attachement.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible |
| `$metadata` | `array<string, mixed>` | Nouvelles métadonnées |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas

**Exemple :**
```php
$user->updateMetadataFor($hospital, [
    'department' => 'Neurology'
]);
```

---

#### `mergeMetadataFor(Model $target, array $metadata): void`

Fusionne des métadonnées (conserve les existantes).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Cible |
| `$metadata` | `array<string, mixed>` | Métadonnées à fusionner |

**Exemple :**
```php
$user->mergeMetadataFor($hospital, [
    'availability' => 'Monday-Friday'
]);
```

---

### Méthode de synchronisation

#### `syncAttachments(array $targets): Collection`

Synchronise tous les attachements du modèle courant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targets` | `array<array{target: Model, role: EnumerableInterface, metadata?: array<string, mixed>}>` | Cibles avec rôles |

**Retourne :** `Collection<int, Model>` - Attachements créés/mis à jour

**Exceptions :** `RuntimeException` - Si un target est invalide

**Exemple :**
```php
$user->syncAttachments([
    ['target' => $hospital1, 'role' => HospitalRole::DOCTOR],
    ['target' => $hospital2, 'role' => HospitalRole::DOCTOR],
]);
// Les hôpitaux précédents non inclus sont supprimés
```

---

### Méthodes de hook (surchargeables)

Toutes les méthodes de hook sont publiques et peuvent être surchargées dans le modèle.

#### `beforeAttach(Model $other, EnumerableInterface $role, array $metadata, HookPosition $position): void`

Appelé avant la création d'un attachement.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$other` | `Model` | L'autre modèle |
| `$role` | `EnumerableInterface` | Rôle |
| `$metadata` | `array<string, mixed>` | Métadonnées |
| `$position` | `HookPosition` | Position du modèle courant |

**Exemple :**
```php
public function beforeAttach(Model $other, EnumerableInterface $role, array $metadata, HookPosition $position): void
{
    Log::info('Attaching...', [
        'position' => $position->value,
        'role' => $role->getValue(),
    ]);
}
```

---

#### `afterAttach(Model $other, EnumerableInterface $role, Model $attachment, HookPosition $position): void`

Appelé après la création d'un attachement.

**Exemple :**
```php
public function afterAttach(Model $other, EnumerableInterface $role, Model $attachment, HookPosition $position): void
{
    event(new AttachmentCreated($this, $other, $role, $attachment));
}
```

---

#### `beforeDetach(Model $other, Model $attachment, HookPosition $position): void`

Appelé avant la suppression d'un attachement.

---

#### `afterDetach(Model $other, Model $attachment, HookPosition $position): void`

Appelé après la suppression d'un attachement.

---

#### `beforeUpdateRole(Model $other, Model $attachment, EnumerableInterface $oldRole, EnumerableInterface $newRole, HookPosition $position): void`

Appelé avant la mise à jour d'un rôle.

---

#### `afterUpdateRole(Model $other, Model $attachment, EnumerableInterface $oldRole, EnumerableInterface $newRole, HookPosition $position): void`

Appelé après la mise à jour d'un rôle.

---

#### `beforeUpdateMetadata(Model $other, Model $attachment, StrictDataObject $oldMetadata, StrictDataObject $newMetadata, HookPosition $position): void`

Appelé avant la mise à jour des métadonnées.

---

#### `afterUpdateMetadata(Model $other, Model $attachment, StrictDataObject $oldMetadata, StrictDataObject $newMetadata, HookPosition $position): void`

Appelé après la mise à jour des métadonnées.

---

#### `beforeDetachAll(): void`

Appelé avant la suppression de tous les attachements.

**Exemple :**
```php
public function beforeDetachAll(): void
{
    Log::info('Detaching all attachments for user ' . $this->id);
}
```

---

#### `afterDetachAll(): void`

Appelé après la suppression de tous les attachements.

---

## Cas d'utilisation

### Cas 1 : Gestion des hôpitaux d'un médecin

```php
class Doctor extends Model
{
    use HasRattachments;
}

$doctor = Doctor::find(1);

// Attacher à un hôpital
$doctor->attachTo($hospital, HospitalRole::DOCTOR, [
    'start_date' => '2024-01-01'
]);

// Récupérer tous les hôpitaux
$hospitals = $doctor->getTargets();

// Récupérer les hôpitaux où il est chef
$chiefHospitals = $doctor->getTargetsByRole(HospitalRole::CHIEF);

// Compter les hôpitaux
$count = $doctor->countTargets();

// Promouvoir en chef
$doctor->updateRoleFor($hospital, HospitalRole::CHIEF);
```

### Cas 2 : Gestion des tags d'un article

```php
class Post extends Model
{
    use HasRattachments;
}

$post = Post::find(1);

// Ajouter des tags
$post->attachTo($tag1, TagRole::PRIMARY);
$post->attachTo($tag2, TagRole::SECONDARY);

// Récupérer les tags
$tags = $post->getTargets();

// Récupérer les tags primaires
$primaryTags = $post->getTargetsByTypeAndRole(Tag::class, TagRole::PRIMARY);

// Synchroniser les tags (remplace tous)
$post->syncAttachments([
    ['target' => $tag1, 'role' => TagRole::PRIMARY],
    ['target' => $tag3, 'role' => TagRole::TAG],
]);
```

### Cas 3 : Relations sociales

```php
class User extends Model
{
    use HasRattachments;

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
        return $this->getTargetsByType(User::class);
    }

    public function getBestFriends(): Collection
    {
        return $this->getTargetsByTypeAndRole(User::class, FriendRole::BEST_FRIEND);
    }
}
```

### Cas 4 : Utilisation des hooks pour l'audit

```php
class User extends Model
{
    use HasRattachments;

    public function beforeAttach(Model $other, EnumerableInterface $role, array $metadata, HookPosition $position): void
    {
        Log::info('User ' . $this->id . ' is attaching to ' . get_class($other), [
            'role' => $role->getValue(),
            'position' => $position->value,
        ]);
    }

    public function afterAttach(Model $other, EnumerableInterface $role, Model $attachment, HookPosition $position): void
    {
        AuditLog::create([
            'user_id' => $this->id,
            'action' => 'attach',
            'target_type' => get_class($other),
            'target_id' => $other->getKey(),
            'role' => $role->getValue(),
            'attachment_id' => $attachment->id,
        ]);
    }
}
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Attachement inexistant | `RuntimeException` | `{rattachable} {id} is not attached to {target} {id}` |
| Contrainte violée | `RuntimeException` | Variable selon la contrainte |
| Sync sans target | `RuntimeException` | `Each target must have "target" key` |
| Sync sans role | `RuntimeException` | `Each target must have "role" key` |

---

## Performance

- Les méthodes de lecture utilisent le repository avec des requêtes optimisées
- Les méthodes de pagination limitent les résultats
- `syncAttachments()` effectue plusieurs opérations en une seule transaction

### Recommandations

```php
// ⚠️ Éviter - Charge tout en mémoire
$allTargets = $user->getTargets();

// ✅ Recommandé - Utiliser la pagination
$targets = $user->getTargetsPaginated(20);

// ✅ Recommandé - Utiliser le comptage
$count = $user->countTargets();
```

---

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| Laravel 10+ | ✅ Complet |

---

## Voir aussi

- `RattachmentService` - Service central
- `AttachmentHookInterface` - Interface des hooks
- `HookPosition` - Position du modèle
- `Rattachment` - Modèle Eloquent