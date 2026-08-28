# HasRattachments - Référence Technique

## Description

Trait PHP pour les modèles Eloquent qui peuvent avoir des attachements polymorphes. Fournit une API fluide pour lire et interroger les attachements directement depuis le modèle.

## Hiérarchie / Implémentations

```
Trait
    └── HasRattachments
```

## Rôle principal

Ce trait permet à n'importe quel modèle Eloquent de bénéficier des fonctionnalités d'attachement polymorphe sans avoir à injecter et utiliser manuellement le `RattachmentService`. Il expose une API intuitive directement sur le modèle, améliorant la lisibilité et la fluidité du code.

## API / Méthodes publiques

### `isAttachedTo(Model $target): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible à vérifier |

**Retourne :** `bool` - `true` si le modèle est attaché à la cible

**Exemple :**
```php
$user = User::find(1);
$hospital = Hospital::find(5);

if ($user->isAttachedTo($hospital)) {
    echo "L'utilisateur est attaché à l'hôpital";
}
```

---

### `hasRoleAttachedTo(Model $target, EnumerableInterface $role): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à vérifier |

**Retourne :** `bool` - `true` si la cible a le rôle spécifié

**Exemple :**
```php
$user = User::find(1);
$hospital = Hospital::find(5);

if ($user->hasRoleAttachedTo($hospital, Role::CHIEF)) {
    echo "L'utilisateur est médecin chef de cet hôpital";
}
```

---

### `getAttachment(Model $target): ?Model`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `Model|null` - L'attachement ou `null`

**Exemple :**
```php
$user = User::find(1);
$hospital = Hospital::find(5);

$attachment = $user->getAttachment($hospital);
if ($attachment) {
    echo $attachment->role->getValue();
    print_r($attachment->metadata);
}
```

---

### `hasAttachmentsBetween(Model $target): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `bool` - `true` si un attachement existe

**Exemple :**
```php
$user = User::find(1);
$hospital = Hospital::find(5);

if ($user->hasAttachmentsBetween($hospital)) {
    echo "Un attachement existe entre l'utilisateur et l'hôpital";
}
```

---

### `getTargets(): Collection`

**Retourne :** `Collection<int, Model>` - Collection des cibles attachées

**Exemple :**
```php
$user = User::find(1);
$hospitals = $user->getTargets();

foreach ($hospitals as $hospital) {
    echo $hospital->name . "\n";
}
```

---

### `getTargetsPaginated(int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

**Exemple :**
```php
$user = User::find(1);
$hospitals = $user->getTargetsPaginated(10, 2);
```

---

### `getTargetsByRole(EnumerableInterface $role): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles avec le rôle spécifié

**Exemple :**
```php
$user = User::find(1);
$hospitals = $user->getTargetsByRole(Role::DOCTOR);
```

---

### `getTargetsByRolePaginated(EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargetsByType(string $targetClass): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |

**Retourne :** `Collection<int, Model>` - Cibles du type spécifié

**Exemple :**
```php
$user = User::find(1);
$posts = $user->getTargetsByType(Post::class);
```

---

### `getTargetsByTypePaginated(string $targetClass, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargetsByTypeAndRole(string $targetClass, EnumerableInterface $role): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles du type et rôle spécifiés

**Exemple :**
```php
$user = User::find(1);
$adminPosts = $user->getTargetsByTypeAndRole(Post::class, PostRole::ADMIN);
```

---

### `getTargetsByTypeAndRoles(string $targetClass, array $roles): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClass` | `string` | FQCN de la classe cible |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles du type avec un des rôles

---

### `getTargetsByTypesAndRoles(array $targetClasses, array $roles): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$targetClasses` | `array<int, string>` | FQCN des classes cibles |
| `$roles` | `array<int, EnumerableInterface>` | Rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Cibles des types avec un des rôles

**Exemple :**
```php
$user = User::find(1);
$targets = $user->getTargetsByTypesAndRoles(
    [Post::class, Comment::class],
    [Role::ADMIN, Role::EDITOR]
);
```

---

### `countTargets(): int`

**Retourne :** `int` - Nombre total de cibles

**Exemple :**
```php
$user = User::find(1);
$total = $user->countTargets();
echo "Total d'attachements: $total";
```

---

### `countTargetsByRole(EnumerableInterface $role): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre de cibles avec ce rôle

---

### `getDistinctRoles(): Collection`

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts

**Exemple :**
```php
$user = User::find(1);
$roles = $user->getDistinctRoles();
// ['doctor', 'admin', 'staff']
```

---

### `getRattachables(): Collection`

**Retourne :** `Collection<int, Model>` - Modèles attachés à ce modèle (quand il est utilisé comme cible)

**Exemple :**
```php
$hospital = Hospital::find(1);
$users = $hospital->getRattachables();
```

---

### `getRattachablesPaginated(int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getRattachablesByRole(EnumerableInterface $role): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Modèles attachés avec ce rôle

**Exemple :**
```php
$hospital = Hospital::find(1);
$chiefs = $hospital->getRattachablesByRole(Role::CHIEF);
```

---

### `getRattachablesByRolePaginated(EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |
| `$perPage` | `int` | Éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `countRattachables(): int`

**Retourne :** `int` - Nombre total de modèles attachés

---

### `countRattachablesByRole(EnumerableInterface $role): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre de modèles attachés avec ce rôle

---

### `getDistinctRolesForTarget(): Collection`

**Retourne :** `Collection<int, EnumerableInterface>` - Rôles distincts pour ce modèle comme cible

**Exemple :**
```php
$hospital = Hospital::find(1);
$roles = $hospital->getDistinctRolesForTarget();
// ['doctor', 'nurse', 'admin']
```

## Cas d'utilisation

### Cas 1 : Gestion des hôpitaux d'un médecin

**Problème :** Un médecin travaille dans plusieurs hôpitaux avec des rôles différents.

**Solution :** Utiliser les méthodes du trait pour interroger et afficher les relations.

```php
$doctor = User::find(1);

// Récupérer tous les hôpitaux
$hospitals = $doctor->getTargets();

// Récupérer les hôpitaux où il est chef
$chiefHospitals = $doctor->getTargetsByRole(Role::CHIEF);

// Afficher les détails
foreach ($hospitals as $hospital) {
    $attachment = $doctor->getAttachment($hospital);
    echo $hospital->name . ': ' . $attachment->role->getValue();
    if ($attachment->metadata) {
        echo ' (' . $attachment->metadata['department'] . ')';
    }
}
```

### Cas 2 : Dashboard d'un hôpital

**Problème :** Afficher les statistiques d'un hôpital.

**Solution :** Utiliser les méthodes de comptage.

```php
$hospital = Hospital::find(1);

$totalStaff = $hospital->countRattachables();
$doctors = $hospital->countRattachablesByRole(Role::DOCTOR);
$nurses = $hospital->countRattachablesByRole(Role::NURSE);
$chiefs = $hospital->countRattachablesByRole(Role::CHIEF);

echo "Total staff: $totalStaff\n";
echo "Doctors: $doctors\n";
echo "Nurses: $nurses\n";
echo "Chiefs: $chiefs\n";
```

### Cas 3 : Gestion des tags d'un article

**Problème :** Afficher et filtrer les tags d'un article.

**Solution :** Utiliser les méthodes de filtrage par type et rôle.

```php
$post = Post::find(1);

// Tous les tags
$allTags = $post->getTargets();

// Tags principaux
$primaryTags = $post->getTargetsByTypeAndRole(Tag::class, TagRole::PRIMARY);

// Tags par catégorie
$tagCategories = $post->getTargetsByTypeAndRole(TagCategory::class, TagRole::CATEGORY);

// Afficher les tags groupés
echo "Primary tags:\n";
foreach ($primaryTags as $tag) {
    echo "  - " . $tag->name . "\n";
}
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Aucune (lecture seule) | Aucune | - |

**Note :** Ce trait ne contient que des méthodes de lecture. Les méthodes d'écriture (`attachTo`, `detachFrom`, etc.) sont disponibles dans le trait mais ne sont pas documentées ici car le prompt demande uniquement les méthodes de lecture.

## Intégration

Ce trait s'intègre avec :

- **RattachmentService** - Service central
- **Rattachment** - Modèle Eloquent
- **Eloquent** - ORM Laravel

## Performance

- Toutes les méthodes délèguent au `RattachmentService`
- Les requêtes sont optimisées via le repository
- Les méthodes de pagination limitent le nombre de résultats
- Les méthodes de comptage utilisent `COUNT()` en base de données
- **Attention :** Les méthodes `getTargets()` et `getRattachables()` chargent toutes les relations en mémoire

### Optimisation recommandée

```php
// ⚠️ Éviter - Charge tout en mémoire
$allTargets = $user->getTargets();

// ✅ Recommandé - Utiliser la pagination
$targets = $user->getTargetsPaginated(20);

// ✅ Recommandé - Utiliser le comptage
$count = $user->countTargets();
```

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| Laravel 10+ | ✅ Complet |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelRattachments\Traits\HasRattachments;
use AndyDefer\LaravelRattachments\Enums\Role;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasRattachments;
}

class Hospital extends Model
{
    use HasRattachments;
}

// 1. Récupérer un utilisateur
$user = User::find(1);

// 2. Vérifier les attachements
if ($user->isAttachedTo($hospital)) {
    echo "L'utilisateur est attaché\n";
}

// 3. Récupérer les cibles
$hospitals = $user->getTargets();
foreach ($hospitals as $hospital) {
    echo $hospital->name . "\n";
}

// 4. Filtrer par rôle
$chiefs = $user->getTargetsByRole(Role::CHIEF);

// 5. Compter
$total = $user->countTargets();
echo "Total: $total\n";

// 6. Récupérer un attachement spécifique
$attachment = $user->getAttachment($hospital);
if ($attachment) {
    echo $attachment->role->getValue() . "\n";
}

// 7. Rôles distincts
$roles = $user->getDistinctRoles();

// 8. Utiliser comme cible
$hospital = Hospital::find(1);
$doctors = $hospital->getRattachablesByRole(Role::DOCTOR);
```

## Voir aussi

- `RattachmentService` - Service central
- `Rattachment` - Modèle Eloquent
- `RattachmentServiceInterface` - Interface du service
- `RattachmentConstraintsInterface` - Interface des contraintes