# RattachmentService - Référence Technique

## Description

Service principal de gestion des attachements polymorphes entre modèles Eloquent. Orchestre toutes les opérations de création, mise à jour, suppression et requêtage des relations d'attachement.

## Hiérarchie / Implémentations

```
RattachmentServiceInterface
    └── RattachmentService
```

## Rôle principal

Ce service est le point d'entrée unique pour toutes les opérations liées aux attachements. Il garantit l'intégrité des données en validant les contraintes, gère les rôles et métadonnées, et fournit une API complète pour interroger les relations.

## API / Méthodes publiques

### `attach(Model $rattachable, Model $target, EnumerableInterface $role, array $metadata = []): Model`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source qui attache |
| `$target` | `Model` | Modèle cible à attacher |
| `$role` | `EnumerableInterface` | Rôle de l'attachement |
| `$metadata` | `array<string, mixed>` | Métadonnées optionnelles |

**Retourne :** `Model` - L'attachement créé

**Exceptions :** `RuntimeException` - Si les contraintes sont violées ou si l'attachement existe déjà

**Exemple :**
```php
$service = app(RattachmentService::class);

$user = User::find(1);
$hospital = Hospital::find(5);

$attachment = $service->attach($user, $hospital, Role::ADMIN, [
    'department' => 'cardiology',
    'start_date' => '2024-01-01'
]);
```

---

### `attachMultiple(Collection $rattachables, Model $target, EnumerableInterface $role, array $metadata = []): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Collection de modèles sources |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle pour tous les attachements |
| `$metadata` | `array<string, mixed>` | Métadonnées pour tous les attachements |

**Retourne :** `Collection<int, Model>` - Collection des attachements créés

**Exceptions :** `RuntimeException` - Si une contrainte est violée

**Exemple :**
```php
$users = User::where('department', 'cardiology')->get();
$hospital = Hospital::find(1);

$attachments = $service->attachMultiple($users, $hospital, Role::DOCTOR);
```

---

### `attachToMultiple(Model $rattachable, Collection $targets, EnumerableInterface $role, array $metadata = []): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$targets` | `Collection<int, Model>` | Collection de modèles cibles |
| `$role` | `EnumerableInterface` | Rôle pour tous les attachements |
| `$metadata` | `array<string, mixed>` | Métadonnées pour tous les attachements |

**Retourne :** `Collection<int, Model>` - Collection des attachements créés

**Exceptions :** `RuntimeException` - Si une contrainte est violée

---

### `detach(Model $rattachable, Model $target): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$target` | `Model` | Modèle cible |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas

---

### `detachMultiple(Collection $rattachables, Model $target): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Collection de modèles sources |
| `$target` | `Model` | Modèle cible |

---

### `detachFromMultiple(Model $rattachable, Collection $targets): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$targets` | `Collection<int, Model>` | Collection de modèles cibles |

---

### `detachAll(Model $model): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle à détacher de tous ses attachements |

**Exemple :**
```php
// Supprime tous les attachements liés à un utilisateur
$user = User::find(1);
$service->detachAll($user);
// Supprime les attachements où user est rattachable ET où user est target
```

---

### `isAttached(Model $rattachable, Model $target): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$target` | `Model` | Modèle cible |

**Retourne :** `bool` - `true` si l'attachement existe

**Exemple :**
```php
$user = User::find(1);
$hospital = Hospital::find(5);

if ($service->isAttached($user, $hospital)) {
    echo "L'utilisateur est déjà attaché à cet hôpital";
}
```

---

### `hasRoleAttached(Model $target, EnumerableInterface $role): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à vérifier |

**Retourne :** `bool` - `true` si un attachement avec ce rôle existe

**Exemple :**
```php
$hospital = Hospital::find(1);

if ($service->hasRoleAttached($hospital, Role::CHIEF)) {
    echo "L'hôpital a un médecin chef";
}
```

---

### `getRattachables(Model $target): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `Collection<int, Model>` - Collection des modèles attachés

**Exemple :**
```php
$hospital = Hospital::find(1);
$doctors = $service->getRattachables($hospital);

foreach ($doctors as $doctor) {
    echo $doctor->name . "\n";
}
```

---

### `getRattachablesPaginated(Model $target, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$perPage` | `int` | Nombre d'éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

**Exemple :**
```php
$hospital = Hospital::find(1);
$doctors = $service->getRattachablesPaginated($hospital, 10, 2);
```

---

### `getTargets(Model $rattachable): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |

**Retourne :** `Collection<int, Model>` - Collection des cibles attachées

**Exemple :**
```php
$user = User::find(1);
$hospitals = $service->getTargets($user);

foreach ($hospitals as $hospital) {
    echo $hospital->name . "\n";
}
```

---

### `getTargetsPaginated(Model $rattachable, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$perPage` | `int` | Nombre d'éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getRattachablesByRole(Model $target, EnumerableInterface $role): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Collection des modèles attachés avec ce rôle

**Exemple :**
```php
$hospital = Hospital::find(1);
$chiefs = $service->getRattachablesByRole($hospital, Role::CHIEF);
```

---

### `getRattachablesByRolePaginated(Model $target, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |
| `$perPage` | `int` | Nombre d'éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargetsByRole(Model $rattachable, EnumerableInterface $role): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Collection des cibles avec ce rôle

**Exemple :**
```php
$user = User::find(1);
$hospitals = $service->getTargetsByRole($user, Role::DOCTOR);
```

---

### `getTargetsByRolePaginated(Model $rattachable, EnumerableInterface $role, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$role` | `EnumerableInterface` | Rôle à filtrer |
| `$perPage` | `int` | Nombre d'éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargetsByType(Model $rattachable, string $targetClass): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$targetClass` | `string` | FQCN du modèle cible |

**Retourne :** `Collection<int, Model>` - Collection des cibles du type spécifié

**Exemple :**
```php
$user = User::find(1);
$posts = $service->getTargetsByType($user, Post::class);
```

---

### `getTargetsByTypePaginated(Model $rattachable, string $targetClass, int $perPage = 15, int $page = 1): LengthAwarePaginator`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$targetClass` | `string` | FQCN du modèle cible |
| `$perPage` | `int` | Nombre d'éléments par page |
| `$page` | `int` | Numéro de la page |

**Retourne :** `LengthAwarePaginator` - Résultats paginés

---

### `getTargetsByTypeAndRole(Model $rattachable, string $targetClass, EnumerableInterface $role): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$targetClass` | `string` | FQCN du modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<int, Model>` - Collection des cibles du type et rôle spécifiés

**Exemple :**
```php
$user = User::find(1);
$adminPosts = $service->getTargetsByTypeAndRole($user, Post::class, PostRole::ADMIN);
```

---

### `getTargetsByTypeAndRoles(Model $rattachable, string $targetClass, array $roles): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$targetClass` | `string` | FQCN du modèle cible |
| `$roles` | `array<int, EnumerableInterface>` | Tableau des rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Collection des cibles avec un des rôles spécifiés

**Exemple :**
```php
$user = User::find(1);
$posts = $service->getTargetsByTypeAndRoles($user, Post::class, [PostRole::ADMIN, PostRole::EDITOR]);
```

---

### `getTargetsByTypesAndRoles(Model $rattachable, array $targetClasses, array $roles): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$targetClasses` | `array<int, string>` | Tableau des FQCN des cibles |
| `$roles` | `array<int, EnumerableInterface>` | Tableau des rôles à filtrer |

**Retourne :** `Collection<int, Model>` - Collection des cibles des types spécifiés avec un des rôles

**Exemple :**
```php
$user = User::find(1);
$targets = $service->getTargetsByTypesAndRoles(
    $user, 
    [Post::class, Comment::class],
    [Role::ADMIN, Role::EDITOR]
);
```

---

### `countRattachables(Model $target): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `int` - Nombre total d'attachements

**Exemple :**
```php
$hospital = Hospital::find(1);
$total = $service->countRattachables($hospital);
echo "Total d'attachements: $total";
```

---

### `countTargets(Model $rattachable): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |

**Retourne :** `int` - Nombre total de cibles

---

### `countRattachablesByRole(Model $target, EnumerableInterface $role): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre d'attachements avec ce rôle

---

### `countTargetsByRole(Model $rattachable, EnumerableInterface $role): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `int` - Nombre de cibles avec ce rôle

---

### `getDistinctRolesForTarget(Model $target): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `Collection<int, EnumerableInterface>` - Collection des rôles distincts

**Exemple :**
```php
$hospital = Hospital::find(1);
$roles = $service->getDistinctRolesForTarget($hospital);
// ['doctor', 'nurse', 'admin']
```

---

### `getDistinctRolesForRattachable(Model $rattachable): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |

**Retourne :** `Collection<int, EnumerableInterface>` - Collection des rôles distincts

---

### `updateRole(Model $rattachable, Model $target, EnumerableInterface $role): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas ou contrainte violée

**Exemple :**
```php
$user = User::find(1);
$hospital = Hospital::find(5);

// Promouvoir un médecin en chef
$service->updateRole($user, $hospital, Role::CHIEF);
```

---

### `updateRoleForMultiple(Collection $rattachables, Model $target, EnumerableInterface $role): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<int, Model>` | Collection de modèles sources |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

---

### `updateMetadata(Model $rattachable, Model $target, array $metadata): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$target` | `Model` | Modèle cible |
| `$metadata` | `array<string, mixed>` | Nouvelles métadonnées |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas

**Exemple :**
```php
$user = User::find(1);
$hospital = Hospital::find(5);

$service->updateMetadata($user, $hospital, [
    'department' => 'neurology',
    'end_date' => '2025-12-31'
]);
```

---

### `mergeMetadata(Model $rattachable, Model $target, array $metadata): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$target` | `Model` | Modèle cible |
| `$metadata` | `array<string, mixed>` | Métadonnées à fusionner |

**Exceptions :** `RuntimeException` - Si l'attachement n'existe pas

**Exemple :**
```php
// Les métadonnées existantes sont conservées, les nouvelles sont ajoutées
$service->mergeMetadata($user, $hospital, [
    'new_field' => 'value',
]);
```

---

### `getAttachment(Model $rattachable, Model $target): ?Model`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$target` | `Model` | Modèle cible |

**Retourne :** `Model|null` - L'attachement ou null

**Exemple :**
```php
$user = User::find(1);
$hospital = Hospital::find(5);

$attachment = $service->getAttachment($user, $hospital);
if ($attachment) {
    echo $attachment->role->getValue();
    print_r($attachment->metadata);
}
```

---

### `hasAttachmentsBetween(Model $rattachable, Model $target): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$target` | `Model` | Modèle cible |

**Retourne :** `bool` - `true` si un attachement existe

---

### `hasAttachmentsBetweenTypes(string $rattachableType, string $targetType): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | FQCN du modèle source |
| `$targetType` | `string` | FQCN du modèle cible |

**Retourne :** `bool` - `true` si des attachements existent entre ces types

**Exemple :**
```php
if ($service->hasAttachmentsBetweenTypes(User::class, Hospital::class)) {
    echo "Des utilisateurs sont attachés à des hôpitaux";
}
```

---

### `getAttachmentsBetweenTypes(string $rattachableType, string $targetType): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | FQCN du modèle source |
| `$targetType` | `string` | FQCN du modèle cible |

**Retourne :** `Collection<int, Model>` - Collection des attachements

---

### `deleteAllAttachmentsBetweenTypes(string $rattachableType, string $targetType): int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableType` | `string` | FQCN du modèle source |
| `$targetType` | `string` | FQCN du modèle cible |

**Retourne :** `int` - Nombre d'attachements supprimés

**Exemple :**
```php
// Supprimer tous les attachements entre User et Hospital
$deleted = $service->deleteAllAttachmentsBetweenTypes(User::class, Hospital::class);
echo "$deleted attachments deleted";
```

---

### `syncAttachments(Model $rattachable, array $targets): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$targets` | `array<array{target: Model, role: EnumerableInterface, metadata?: array<string, mixed>}>` | Tableau des cibles avec rôles et métadonnées |

**Retourne :** `Collection<int, Model>` - Collection des attachements créés ou mis à jour

**Exceptions :** `RuntimeException` - Si un target n'a pas de clé `target` ou `role`

**Exemple :**
```php
$post = Post::find(1);
$tags = [
    ['target' => Tag::find(1), 'role' => TagRole::PRIMARY, 'metadata' => ['order' => 1]],
    ['target' => Tag::find(2), 'role' => TagRole::SECONDARY, 'metadata' => ['order' => 2]],
    ['target' => Tag::find(3), 'role' => TagRole::TAG],
];

$attachments = $service->syncAttachments($post, $tags);
// Les tags 4 et 5 (précédemment attachés) sont supprimés
```

## Cas d'utilisation complets

### Cas 1 : Gestion des utilisateurs d'un hôpital

```php
class HospitalManager
{
    private RattachmentService $service;

    public function __construct(RattachmentService $service)
    {
        $this->service = $service;
    }

    public function assignDoctor(Hospital $hospital, User $user, array $metadata = []): void
    {
        $this->service->attach($user, $hospital, Role::DOCTOR, $metadata);
    }

    public function getDoctors(Hospital $hospital): Collection
    {
        return $this->service->getRattachablesByRole($hospital, Role::DOCTOR);
    }

    public function getDoctorCount(Hospital $hospital): int
    {
        return $this->service->countRattachablesByRole($hospital, Role::DOCTOR);
    }

    public function removeDoctor(Hospital $hospital, User $user): void
    {
        $this->service->detach($user, $hospital);
    }

    public function promoteToChief(Hospital $hospital, User $user): void
    {
        $this->service->updateRole($user, $hospital, Role::CHIEF);
    }

    public function getChief(Hospital $hospital): ?User
    {
        return $this->service->getRattachablesByRole($hospital, Role::CHIEF)->first();
    }

    public function syncDoctors(Hospital $hospital, array $doctorData): Collection
    {
        $targets = [];
        foreach ($doctorData as $data) {
            $targets[] = [
                'target' => $data['user'],
                'role' => $data['role'] ?? Role::DOCTOR,
                'metadata' => $data['metadata'] ?? [],
            ];
        }

        return $this->service->syncAttachments($hospital, $targets);
    }
}
```

### Cas 2 : Gestion des tags d'un article (CRUD complet)

```php
class PostTagManager
{
    private RattachmentService $service;

    public function __construct(RattachmentService $service)
    {
        $this->service = $service;
    }

    public function addTag(Post $post, Tag $tag, TagRole $role, array $metadata = []): void
    {
        $this->service->attach($post, $tag, $role, $metadata);
    }

    public function removeTag(Post $post, Tag $tag): void
    {
        $this->service->detach($post, $tag);
    }

    public function updateTagRole(Post $post, Tag $tag, TagRole $newRole): void
    {
        $this->service->updateRole($post, $tag, $newRole);
    }

    public function getTags(Post $post): Collection
    {
        return $this->service->getTargetsByType($post, Tag::class);
    }

    public function getTagsByRole(Post $post, TagRole $role): Collection
    {
        return $this->service->getTargetsByTypeAndRole($post, Tag::class, $role);
    }

    public function syncTags(Post $post, array $tags): Collection
    {
        $targets = [];
        foreach ($tags as $tagData) {
            $targets[] = [
                'target' => $tagData['tag'],
                'role' => $tagData['role'] ?? TagRole::TAG,
                'metadata' => $tagData['metadata'] ?? [],
            ];
        }

        return $this->service->syncAttachments($post, $targets);
    }

    public function getTagCount(Post $post): int
    {
        return $this->service->countTargets($post);
    }
}
```

### Cas 3 : Pipeline de données avec attachements

```php
class DocumentPipeline
{
    private RattachmentService $service;

    public function __construct(RattachmentService $service)
    {
        $this->service = $service;
    }

    public function processDocument(Document $document, array $steps): void
    {
        // 1. Attacher tous les reviewers au document
        $reviewers = $steps['reviewers'] ?? [];
        foreach ($reviewers as $reviewer) {
            $this->service->attach($document, $reviewer, DocumentRole::REVIEWER);
        }

        // 2. Attacher l'approbateur
        if (isset($steps['approver'])) {
            $this->service->attach($document, $steps['approver'], DocumentRole::APPROVER);
        }

        // 3. Attacher le rédacteur
        if (isset($steps['editor'])) {
            $this->service->attach($document, $steps['editor'], DocumentRole::EDITOR);
        }

        // 4. Mettre à jour les métadonnées des reviewers
        $reviewersWithMetadata = $steps['reviewers_with_metadata'] ?? [];
        foreach ($reviewersWithMetadata as $data) {
            $this->service->mergeMetadata($document, $data['user'], $data['metadata']);
        }
    }

    public function getReviewers(Document $document): Collection
    {
        return $this->service->getTargetsByRole($document, DocumentRole::REVIEWER);
    }

    public function getApprover(Document $document): ?User
    {
        return $this->service->getTargetsByRole($document, DocumentRole::APPROVER)->first();
    }

    public function getWorkflowStatus(Document $document): array
    {
        $reviewers = $this->service->getTargetsByRole($document, DocumentRole::REVIEWER);
        $approver = $this->service->getTargetsByRole($document, DocumentRole::APPROVER);

        return [
            'has_reviewers' => $reviewers->isNotEmpty(),
            'reviewer_count' => $reviewers->count(),
            'has_approver' => $approver->isNotEmpty(),
            'is_approved' => $approver->isNotEmpty() && $this->hasRoleAttached($document, DocumentRole::APPROVED),
        ];
    }
}
```

### Cas 4 : Migration de données entre modèles

```php
class DataMigrationService
{
    private RattachmentService $service;

    public function __construct(RattachmentService $service)
    {
        $this->service = $service;
    }

    public function migrateAttachments(Model $source, Model $target): int
    {
        // 1. Récupérer tous les attachements du source
        $attachments = $this->service->getTargets($source);

        // 2. Les attacher au target avec les mêmes rôles
        $migrated = 0;
        foreach ($attachments as $attached) {
            $this->service->attach($target, $attached, $source->role);
            $migrated++;
        }

        // 3. Supprimer les attachements du source
        $this->service->detachAll($source);

        return $migrated;
    }

    public function copyAttachments(Model $source, Model $target): int
    {
        $attachments = $this->service->getTargets($source);
        $copied = 0;

        foreach ($attachments as $attached) {
            $this->service->attach($target, $attached, $source->role);
            $copied++;
        }

        return $copied;
    }
}
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Rattachable n'implémente pas l'interface | `RuntimeException` | `Model {class} must implement RattachmentConstraintsInterface to be attachable.` |
| Target n'implémente pas l'interface | `RuntimeException` | `Model {class} must implement RattachmentConstraintsInterface to be a target.` |
| Target non autorisé | `RuntimeException` | `{rattachable} cannot be attached to {target}. Allowed targets: {allowed}` |
| Rôle non autorisé | `RuntimeException` | `Role "{role}" is not allowed for {rattachable} -> {target}. Allowed roles: {allowed}` |
| Rôle interdit | `RuntimeException` | `Role "{role}" is disallowed for {rattachable} -> {target}. Disallowed roles: {disallowed}` |
| Target déjà attaché | `RuntimeException` | `{rattachable} {id} is already attached to {target} {id}` |
| Contrainte unique violée | `RuntimeException` | `{rattachable} already has a unique attachment to {target}. Only one {target} is allowed.` |
| Attachement inexistant pour détacher | `RuntimeException` | `{rattachable} {id} is not attached to {target} {id}` |
| Sync sans `target` key | `RuntimeException` | `Each target must have "target" key` |
| Sync sans `role` key | `RuntimeException` | `Each target must have "role" key` |

## Intégration

Ce service s'intègre avec :

- **RattachmentRepositoryInterface** - Accès aux données
- **ConstraintValidatorInterface** - Validation des contraintes
- **RattachmentRecord** - DTO de création
- **RattachmentFilterRecord** - DTO de filtrage
- **FindByRecord / PaginateRecord** - Requêtage avancé
- **SortColumns** - Tri multi-colonnes

## Performance

- Les requêtes de recherche utilisent `FindByRecord` avec les filtres appropriés
- Les relations sont chargées à la demande
- `syncAttachments()` effectue plusieurs opérations
- `deleteBulk()` pour les suppressions groupées
- `exists()` pour les vérifications rapides

### Optimisation recommandée

```php
// ⚠️ Éviter - N+1
$targets = $service->getTargets($post);
foreach ($targets as $target) {
    echo $target->name;
}

// ✅ Recommandé - Eager loading
$rattachments = $repository->findBy(new FindByRecord(...));
$rattachments->load('target');
```

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| Laravel 10+ | ✅ Complet |

## Voir aussi

- `RattachmentServiceInterface` - Interface du service
- `ConstraintValidator` - Validation des contraintes
- `RattachmentRepository` - Accès aux données
- `RattachmentRecord` - DTO de création
- `RattachmentFilterRecord` - DTO de filtrage
- `FindByRecord` - DTO de recherche
- `PaginateRecord` - DTO de pagination
- `SortColumns` - Tri multi-colonnes