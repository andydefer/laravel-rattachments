# ConstraintValidator - Référence Technique

## Description

Validateur centralisé pour les contraintes d'attachement. Vérifie les cibles autorisées, les cibles uniques, les cibles interdites et les rôles.

## Hiérarchie / Implémentations

```
ConstraintValidatorInterface
    └── ConstraintValidator
```

## Rôle principal

Ce validateur garantit l'intégrité des données avant toute opération d'attachement. Il est utilisé par le service pour valider que :

- Les modèles implémentent l'interface `RattachmentInterface`
- Les cibles sont autorisées par les contraintes du modèle
- Les rôles sont valides pour le contexte
- Les contraintes uniques sont respectées
- Les cibles ou rôles interdits ne sont pas utilisés

---

## API / Méthodes publiques

### `validateConstraints(Model $rattachable, Model $target, EnumerableInterface $role): void`

Valide toutes les contraintes pour un attachement.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle de l'attachement |

**Exceptions :** `RuntimeException` - Si une contrainte est violée

**Exemple :**
```php
$validator->validateConstraints($user, $hospital, HospitalRole::DOCTOR);
```

---

### `validateUniqueConstraints(Model $rattachable, Model $target, EnumerableInterface $role): void`

Valide les contraintes uniques pour un attachement.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle source |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle de l'attachement |

**Exceptions :** `RuntimeException` - Si une contrainte unique est violée

**Exemple :**
```php
$validator->validateUniqueConstraints($user, $hospital, HospitalRole::CHIEF);
```

---

### `validateRoleValue(string $rattachableClass, string $targetClass, string $roleValue): void`

Valide qu'un rôle est autorisé pour un contexte donné.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableClass` | `string` | FQCN du modèle source |
| `$targetClass` | `string` | FQCN du modèle cible |
| `$roleValue` | `string` | Valeur brute du rôle |

**Exceptions :** `RuntimeException` - Si le rôle n'est pas autorisé

**Exemple :**
```php
$validator->validateRoleValue(User::class, Hospital::class, 'doctor');
```

---

### `resolveRole(string $rattachableClass, string $targetClass, string $roleValue): EnumerableInterface`

Résout une valeur brute vers son enum correspondant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachableClass` | `string` | FQCN du modèle source |
| `$targetClass` | `string` | FQCN du modèle cible |
| `$roleValue` | `string` | Valeur brute du rôle |

**Retourne :** `EnumerableInterface` - L'enum résolu ou `UnknownRole`

**Exemple :**
```php
$role = $validator->resolveRole(User::class, Hospital::class, 'doctor');
// HospitalUserRole::DOCTOR

$role = $validator->resolveRole(User::class, Hospital::class, 'unknown');
// UnknownRole::from('unknown')
```

---

## Fonctionnement interne

### Flux de validation

```
validateConstraints()
    │
    ├─► Vérifier RattachmentInterface sur rattachable
    ├─► Vérifier RattachmentInterface sur target
    ├─► validateDisallowedTargets()
    │       ├─► Vérifier si target dans disallowedTargets()
    │       ├─► Si tableau vide → TOUS les rôles bloqués
    │       └─► Si rôle dans le tableau → ERREUR
    │
    └─► validateAllowedTargets()
            ├─► Vérifier si target dans allowedTargets()
            │       └─► Sinon → ERREUR
            └─► Vérifier si rôle dans allowedRoles()
                    └─► Sinon → ERREUR
```

### Validation des contraintes uniques

```
validateUniqueConstraints()
    │
    ├─► Vérifier RattachmentInterface sur rattachable
    ├─► Récupérer uniqueTargets()
    ├─► Vérifier si targetClass existe dans uniqueTargets
    │       └─► Sinon → OK (pas de contrainte)
    │
    ├─► Récupérer uniqueRoles = uniqueTargets[$targetClass]
    │
    ├─► Si uniqueRoles est vide
    │       ├─► Vérifier l'existence d'UN attachement (n'importe quel rôle)
    │       └─► Si existe → ERREUR "Only one {target} is allowed"
    │
    └─► Si uniqueRoles contient des rôles
            ├─► Vérifier si le rôle actuel est dans uniqueRoles
            │       └─► Sinon → OK (pas concerné)
            ├─► Vérifier l'existence d'un attachement AVEC CE RÔLE
            └─► Si existe → ERREUR "Only one {target} with role {role} is allowed"
```

### Exemples de contraintes uniques granulaires

```php
// Un seul Hospital (n'importe quel rôle)
public function uniqueTargets(): array
{
    return [
        Hospital::class => [],
    ];
}

// Un seul CHIEF par Hospital
public function uniqueTargets(): array
{
    return [
        Hospital::class => [Role::CHIEF],
    ];
}

// Un seul BEST_FRIEND
public function uniqueTargets(): array
{
    return [
        User::class => [FriendRole::BEST_FRIEND],
    ];
}

// Mix : un seul Hospital (any role) et une seule PRIMARY specialty
public function uniqueTargets(): array
{
    return [
        Hospital::class => [],
        Specialty::class => [Role::PRIMARY],
    ];
}
```

---

## Cas d'utilisation

### Cas 1 : Validation avant attachement

```php
try {
    $validator->validateConstraints($user, $hospital, HospitalRole::DOCTOR);
    $validator->validateUniqueConstraints($user, $hospital, HospitalRole::DOCTOR);
    // Tout est valide, on peut créer l'attachement
} catch (RuntimeException $e) {
    // Gérer l'erreur
    Log::warning('Attachment validation failed', [
        'error' => $e->getMessage(),
        'user' => $user->id,
        'hospital' => $hospital->id,
    ]);
}
```

### Cas 2 : Résolution dynamique des rôles

```php
// En base de données, le rôle est stocké comme string
$attachment = Rattachment::find(1);
$roleValue = $attachment->role; // 'doctor'

// Résoudre le rôle en fonction du contexte
$role = $validator->resolveRole(
    $attachment->rattachable_type,
    $attachment->target_type,
    $roleValue
);

// Utiliser l'enum résolu
if ($role instanceof UnknownRole) {
    Log::warning('Unknown role detected', [
        'value' => $roleValue,
        'attachment_id' => $attachment->id,
    ]);
}
```

### Cas 3 : Validation des rôles multiples

```php
// Vérifier qu'un utilisateur peut avoir plusieurs rôles
$validator->validateRoleValue(User::class, Hospital::class, 'doctor'); // ✅ OK
$validator->validateRoleValue(User::class, Hospital::class, 'admin');  // ✅ OK

// Mais une seule contrainte unique sur CHIEF
try {
    $validator->validateUniqueConstraints($user, $hospital1, Role::CHIEF);
    $validator->validateUniqueConstraints($user, $hospital2, Role::CHIEF);
} catch (RuntimeException $e) {
    // "User already has a unique attachment to Hospital with role 'chief'"
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
| Target complètement interdite | `RuntimeException` | `{rattachable} cannot be attached to {target}. This target is disallowed.` |
| Rôle interdit | `RuntimeException` | `Role "{role}" is disallowed for {rattachable} -> {target}. Disallowed roles: {disallowed}` |
| Contrainte unique (any role) | `RuntimeException` | `{rattachable} already has a unique attachment to {target}. Only one {target} is allowed.` |
| Contrainte unique (rôle spécifique) | `RuntimeException` | `{rattachable} already has a unique attachment to {target} with role "{role}". Only one {target} with role {role} is allowed.` |
| Classe inexistante | `RuntimeException` | `Rattachable class {class} does not exist.` |

---

## Intégration

Ce validateur s'intègre avec :

- **RattachmentInterface** - Interface des modèles
- **RattachmentRepository** - Accès aux données
- **FindByRecord** - Requêtage pour les contraintes uniques
- **UnknownRole** - Fallback pour les rôles inconnus

---

## Performance

- Les validations effectuent des requêtes en base pour les contraintes uniques
- `validateUniqueConstraints()` utilise `FindByRecord` avec `limit: 1`
- Les vérifications d'interface utilisent `instanceof` (O(1))
- `isRoleInArray()` est O(n) où n est le nombre de rôles
- Pas de cache : chaque validation est à jour

### Optimisation

```php
// ✅ Le validator utilise FindByRecord avec limit: 1
$findByRecord = new FindByRecord(
    filters: $filter,
    limit: 1,  // Optimisé pour la vérification d'existence
);
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

use AndyDefer\LaravelRattachments\Validation\ConstraintValidator;
use AndyDefer\LaravelRattachments\Enums\UnknownRole;

$validator = app(ConstraintValidator::class);

// 1. Valider les contraintes
try {
    $validator->validateConstraints($user, $hospital, HospitalRole::DOCTOR);
} catch (RuntimeException $e) {
    echo "Validation failed: " . $e->getMessage();
}

// 2. Valider les contraintes uniques
try {
    $validator->validateUniqueConstraints($user, $hospital, HospitalRole::CHIEF);
} catch (RuntimeException $e) {
    echo "Unique constraint violated: " . $e->getMessage();
}

// 3. Résoudre un rôle
$role = $validator->resolveRole(User::class, Hospital::class, 'doctor');
if ($role instanceof UnknownRole) {
    echo "Unknown role: " . $role->getValue();
} else {
    echo "Resolved role: " . $role->getValue();
}

// 4. Valider un rôle
try {
    $validator->validateRoleValue(User::class, Hospital::class, 'doctor');
} catch (RuntimeException $e) {
    echo "Invalid role: " . $e->getMessage();
}
```

---

## Voir aussi

- `ConstraintValidatorInterface` - Interface du validateur
- `RattachmentInterface` - Interface des modèles
- `UnknownRole` - Fallback pour les rôles inconnus
- `RattachmentRepository` - Accès aux données