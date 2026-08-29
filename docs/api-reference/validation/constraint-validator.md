# ConstraintValidator - Référence Technique

## Description

Validateur centralisé pour les contraintes d'attachement. Vérifie les cibles autorisées, les cibles uniques, les cibles interdites, les rôles, et détecte les circularités.

## Hiérarchie / Implémentations

```
ConstraintValidatorInterface
    └── ConstraintValidator
```

## Rôle principal

Ce validateur garantit l'intégrité des données avant toute opération d'attachement en validant :

- Les cibles autorisées (`allowedTargets`)
- Les cibles uniques (`uniqueTargets`)
- Les cibles interdites (`disallowedTargets`)
- La validité des rôles
- L'absence d'auto-attachement
- L'absence de circularité (relations bidirectionnelles avec le même rôle)

---

## API / Méthodes publiques

### `validateConstraints(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, EnumerableInterface $role): void`

Valide toutes les contraintes pour un attachement.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source qui attache |
| `$target` | `Model&RattachmentInterface` | Modèle cible à attacher |
| `$role` | `EnumerableInterface` | Rôle de l'attachement |

**Retourne :** `void`

**Exceptions :** `RuntimeException` - Si une contrainte est violée

**Exemple :**
```php
$validator = app(ConstraintValidator::class);
$validator->validateConstraints($user, $hospital, HospitalRole::DOCTOR);
```

---

### `validateUniqueConstraints(Model&RattachmentInterface $rattachable, Model&RattachmentInterface $target, EnumerableInterface $role): void`

Valide les contraintes uniques pour un attachement.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model&RattachmentInterface` | Modèle source |
| `$target` | `Model&RattachmentInterface` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle de l'attachement |

**Retourne :** `void`

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

**Retourne :** `void`

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
```

---

## Validation de circularité

### `validateSelfAttachment()`

Empêche un modèle de s'attacher à lui-même.

**Déclenchement :** Quand `$rattachable` et `$target` sont le même modèle (même classe et même ID).

```php
// ❌ Erreur
$user->attachTo($user, FriendRole::FRIEND);
// Cannot attach a model to itself. App\Models\User 1 cannot be attached to itself.
```

---

### `validateCircularity()`

Détecte les relations circulaires dans `allowedTargets`.

**Déclenchement :** Quand deux modèles s'autorisent mutuellement avec le même rôle.

```php
// User
public function allowedTargets(): array
{
    return [
        Profile::class => [ProfileRole::USER],
    ];
}

// Profile
public function allowedTargets(): array
{
    return [
        User::class => [ProfileRole::USER],
    ];
}

// ❌ Erreur
$user->attachTo($profile, ProfileRole::USER);
// Circular relationship detected: User → Profile with role "user" and Profile → User with the same role.
```

**Exception :** Les relations entre modèles de même type (ex: `User → User`) ne déclenchent pas cette validation.

---

### `validateUniqueCircularity()`

Détecte les contraintes uniques circulaires dans `uniqueTargets`.

**Déclenchement :** Quand deux modèles ont des contraintes uniques l'un sur l'autre avec le même rôle.

```php
// TestSpecializedUser
public function uniqueTargets(): array
{
    return [
        TestHospital::class => [Role::CHIEF],
    ];
}

// TestHospital
public function uniqueTargets(): array
{
    return [
        TestSpecializedUser::class => [Role::CHIEF],
    ];
}

// ❌ Erreur
$validator->validateUniqueConstraints($specializedUser, $hospital, Role::CHIEF);
// Circular unique constraint detected: TestSpecializedUser → TestHospital with role "chief" and TestHospital → TestSpecializedUser with the same role.
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
    Log::warning('Attachment validation failed', [
        'error' => $e->getMessage(),
        'user' => $user->id,
        'hospital' => $hospital->id,
    ]);
}
```

### Cas 2 : Résolution dynamique des rôles

```php
$attachment = Rattachment::find(1);
$role = $validator->resolveRole(
    $attachment->rattachable_type,
    $attachment->target_type,
    $attachment->role
);

if ($role instanceof UnknownRole) {
    Log::warning('Unknown role detected', [
        'value' => $attachment->role,
        'attachment_id' => $attachment->id,
    ]);
}
```

### Cas 3 : Détection de circularité

```php
// Éviter les relations circulaires
class User implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Profile::class => [ProfileRole::USER],
        ];
    }
}

class Profile implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        // ❌ Ne pas autoriser l'inverse avec le même rôle
        // User::class => [ProfileRole::USER],
        return [];
    }
}
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Auto-attachement | `RuntimeException` | `Cannot attach a model to itself. {class} {id} cannot be attached to itself.` |
| Cible non autorisée | `RuntimeException` | `{rattachable} cannot be attached to {target}. Allowed targets: {allowed}` |
| Rôle non autorisé | `RuntimeException` | `Role "{role}" is not allowed for {rattachable} -> {target}. Allowed roles: {allowed}` |
| Cible interdite | `RuntimeException` | `{rattachable} cannot be attached to {target}. This target is disallowed.` |
| Rôle interdit | `RuntimeException` | `Role "{role}" is disallowed for {rattachable} -> {target}. Disallowed roles: {disallowed}` |
| Circularité allowedTargets | `RuntimeException` | `Circular relationship detected: {a} → {b} with role "{role}" and {b} → {a} with the same role.` |
| Circularité uniqueTargets | `RuntimeException` | `Circular unique constraint detected: {a} → {b} with role "{role}" and {b} → {a} with the same role.` |
| Contrainte unique (any role) | `RuntimeException` | `{rattachable} already has a unique attachment to {target}. Only one {target} is allowed.` |
| Contrainte unique (rôle spécifique) | `RuntimeException` | `{rattachable} already has a unique attachment to {target} with role "{role}". Only one {target} with role {role} is allowed.` |
| Classe inexistante | `RuntimeException` | `Rattachable class {class} does not exist.` |

---

## Intégration

Ce validateur s'intègre avec :

- **ConstraintValidatorInterface** - Interface du validateur
- **RattachmentInterface** - Interface des modèles
- **RattachmentRepository** - Accès aux données
- **FindByRecord** - Requêtage pour les contraintes uniques
- **UnknownRole** - Fallback pour les rôles inconnus

---

## Performance

- Les validations effectuent des requêtes en base pour les contraintes uniques
- `validateUniqueConstraints()` utilise `FindByRecord` avec `limit: 1`
- Les vérifications de circularité sont O(1) - accès aux tableaux de configuration
- Pas de cache : chaque validation est à jour

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

// 1. Valider les contraintes (inclut la détection de circularité)
try {
    $validator->validateConstraints($user, $hospital, HospitalRole::DOCTOR);
} catch (RuntimeException $e) {
    echo "Validation failed: " . $e->getMessage();
}

// 2. Valider les contraintes uniques (inclut la détection de circularité unique)
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