# Règles du système d'attachement - Rules.md

## Table des matières

1. [Règles fondamentales](#1-règles-fondamentales)
2. [Contraintes sur les cibles](#2-contraintes-sur-les-cibles)
3. [Contraintes uniques](#3-contraintes-uniques)
4. [Contraintes d'interdiction](#4-contraintes-dinterdiction)
5. [Règles de circularité](#5-règles-de-circularité)
6. [Règles d'auto-attachement](#6-règles-dauto-attachement)
7. [Règles de priorité](#7-règles-de-priorité)
8. [Bonnes pratiques](#8-bonnes-pratiques)

---

## 1. Règles fondamentales

### 1.1 Toute relation est orientée

```
Qui attache (rattachable) → Qui est attaché (target)
```

**Exemple :**
```php
$user->attachTo($profile, ProfileRole::USER);
// User est le rattachable, Profile est le target
// "User a un Profile"
```

### 1.2 Les modèles doivent implémenter l'interface

```php
// ❌ Erreur
class User extends Model
{
    use HasRattachments;
    // Ne peut pas être attaché
}

// ✅ OK
class User extends Model implements RattachmentInterface
{
    use HasRattachments;
    // Peut être attaché
}
```

### 1.3 Le rôle est obligatoire

```php
// ❌ Erreur
$user->attachTo($profile);

// ✅ OK
$user->attachTo($profile, ProfileRole::USER);
```

---

## 2. Contraintes sur les cibles

### 2.1 `allowedTargets()` - Cibles autorisées

Définit quels modèles peuvent être attachés et avec quels rôles.

```php
public function allowedTargets(): array
{
    return [
        Profile::class => [ProfileRole::USER],
        Hospital::class => [HospitalRole::DOCTOR, HospitalRole::ADMIN],
    ];
}
```

**Règles :**
- ✅ Une cible autorisée peut être attachée
- ✅ Seuls les rôles listés sont autorisés
- ❌ Une cible non listée ne peut pas être attachée

**Exemple :**
```php
// ✅ OK - Profile est autorisé
$user->attachTo($profile, ProfileRole::USER);

// ❌ Erreur - Rôle non autorisé
$user->attachTo($profile, ProfileRole::ADMIN);
// Role "admin" is not allowed for User -> Profile. Allowed roles: user

// ❌ Erreur - Cible non autorisée
$user->attachTo($specialty, SpecialtyRole::SPECIALIST);
// User cannot be attached to Specialty. Allowed targets: Profile, Hospital
```

---

## 3. Contraintes uniques

### 3.1 `uniqueTargets()` - Contraintes uniques granulaires

Limite un modèle à un seul rattachement par type et/ou rôle.

```php
public function uniqueTargets(): array
{
    return [
        // Un seul Hospital (n'importe quel rôle)
        Hospital::class => [],

        // Un seul CHIEF par Hospital
        Hospital::class => [Role::CHIEF],

        // Un seul BEST_FRIEND
        User::class => [FriendRole::BEST_FRIEND],
    ];
}
```

**Règles :**
- ✅ `[]` = un seul attachement par type (n'importe quel rôle)
- ✅ `[Role::CHIEF]` = un seul attachement avec ce rôle
- ✅ Plusieurs attachements autorisés si rôles différents

**Exemple :**
```php
// ✅ OK - Premier CHIEF
$doctor->attachTo($hospital1, Role::CHIEF);

// ❌ Erreur - Déjà CHIEF
$doctor->attachTo($hospital2, Role::CHIEF);
// Doctor already has a unique attachment to Hospital with role "chief"

// ✅ OK - Peut être DOCTOR ailleurs
$doctor->attachTo($hospital2, Role::DOCTOR);
```

---

## 4. Contraintes d'interdiction

### 4.1 `disallowedTargets()` - Cibles interdites

Bloque des cibles ou des rôles spécifiques. **A priorité maximale.**

```php
public function disallowedTargets(): array
{
    return [
        // Bloque TOUS les rattachements à Specialty
        Specialty::class => [],

        // Bloque uniquement le rôle REVIEWER pour Post
        Post::class => [PostUserRole::REVIEWER],

        // Bloque uniquement le rôle ADMIN pour Hospital
        Hospital::class => [HospitalRole::ADMIN],
    ];
}
```

**Règles :**
- ✅ `[]` = TOUS les rôles bloqués pour cette cible
- ✅ `[Role::REVIEWER]` = seulement ce rôle bloqué
- ⚠️ **`disallowedTargets` a priorité sur `allowedTargets`**

**Exemple :**
```php
// ❌ Erreur - Cible complètement bloquée
$user->attachTo($specialty, SpecialtyRole::SPECIALIST);
// User cannot be attached to Specialty. This target is disallowed.

// ❌ Erreur - Rôle bloqué
$user->attachTo($post, PostUserRole::REVIEWER);
// Role "reviewer" is disallowed for User -> Post. Disallowed roles: reviewer

// ✅ OK - Rôle autorisé
$user->attachTo($post, PostUserRole::AUTHOR);
```

---

## 5. Règles de circularité

### 5.1 Circularité dans `allowedTargets()` - INTERDITE

Deux modèles ne peuvent pas s'autoriser mutuellement avec le même rôle.

```php
// ❌ Erreur - Circularité
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
        return [
            User::class => [ProfileRole::USER],  // ❌ Circularité !
        ];
    }
}

// Exception: Circular relationship detected: User → Profile with role "user" 
// and Profile → User with the same role.
```

**Exceptions :**
- ✅ Les relations entre modèles de même type (ex: `User → User`) ne sont pas concernées

### 5.2 Circularité dans `uniqueTargets()` - INTERDITE

Deux modèles ne peuvent pas avoir de contraintes uniques circulaires avec le même rôle.

```php
// ❌ Erreur - Circularité unique
class TestSpecializedUser implements RattachmentInterface
{
    public function uniqueTargets(): array
    {
        return [
            TestHospital::class => [Role::CHIEF],
        ];
    }
}

class TestHospital implements RattachmentInterface
{
    public function uniqueTargets(): array
    {
        return [
            TestSpecializedUser::class => [Role::CHIEF],  // ❌ Circularité !
        ];
    }
}

// Exception: Circular unique constraint detected: TestSpecializedUser → TestHospital 
// with role "chief" and TestHospital → TestSpecializedUser with the same role.
```

---

## 6. Règles d'auto-attachement

### 6.1 Auto-attachement - INTERDIT

Un modèle ne peut pas s'attacher à lui-même.

```php
// ❌ Erreur - Auto-attachement
$user->attachTo($user, FriendRole::FRIEND);
// Cannot attach a model to itself. App\Models\User 1 cannot be attached to itself.
```

**Règle :** `$rattachable->getMorphClass() === $target->getMorphClass()` ET `$rattachable->getKey() === $target->getKey()` → BLOQUÉ

---

## 7. Règles de priorité

### 7.1 Ordre de validation

```
1. Auto-attachement
2. Cibles interdites (disallowedTargets)
3. Cibles autorisées (allowedTargets)
4. Circularité
```

### 7.2 Priorité des contraintes

| Contrainte | Priorité |
|------------|----------|
| `disallowedTargets` | 🔴 **Maximale** |
| `uniqueTargets` | 🟡 Élevée |
| `allowedTargets` | 🟢 Normale |

**Exemple :** Si une cible est dans `allowedTargets` ET `disallowedTargets`, c'est `disallowedTargets` qui gagne.

---

## 8. Bonnes pratiques

### 8.1 Une direction unique

Pour une relation `User → Profile`, définissez l'autorisation dans un seul sens :

```php
// ✅ Recommandé
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
        return [];  // ❌ Ne pas autoriser l'inverse
    }
}
```

### 8.2 Interdire la mauvaise direction

```php
class Profile implements RattachmentInterface
{
    public function disallowedTargets(): array
    {
        return [
            User::class => [ProfileRole::USER],  // ✅ Bloque l'inverse
        ];
    }
}
```

### 8.3 Ne pas contredire `allowedTargets`

```php
// ❌ À ÉVITER - Contradiction
class User implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Profile::class => [ProfileRole::USER],  // ✅ Autorisé
        ];
    }

    public function disallowedTargets(): array
    {
        return [
            Profile::class => [ProfileRole::USER],  // ❌ Mais aussi bloqué !
        ];
    }
}
```

### 8.4 Symétrie explicite

Pour les relations symétriques (amitié), attacher dans les deux sens :

```php
public function becomeFriendWith(User $friend): void
{
    $this->attachTo($friend, FriendRole::FRIEND);
    $friend->attachTo($this, FriendRole::FRIEND);
}
```

---

## 📋 Résumé des règles

| Règle | Statut | Exemple |
|-------|--------|---------|
| Orientation `rattachable → target` | ✅ Obligatoire | `$user->attachTo($profile)` |
| Modèle doit implémenter `RattachmentInterface` | ✅ Obligatoire | `class User implements RattachmentInterface` |
| Rôle obligatoire | ✅ Obligatoire | `$user->attachTo($profile, ProfileRole::USER)` |
| Cible autorisée | ✅ Obligatoire | Dans `allowedTargets()` |
| Rôle autorisé | ✅ Obligatoire | Dans `allowedTargets[$target]` |
| Cible unique | ⚠️ Optionnel | Dans `uniqueTargets()` |
| Cible interdite | ⚠️ Optionnel | Dans `disallowedTargets()` |
| Circularité `allowedTargets` | ❌ Interdit | Même rôle dans les deux sens |
| Circularité `uniqueTargets` | ❌ Interdit | Même rôle dans les deux sens |
| Auto-attachement | ❌ Interdit | `$user->attachTo($user)` |
| `disallowedTargets` priorité | ✅ Max | Priorité sur `allowedTargets` |