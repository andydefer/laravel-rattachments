# Comprendre les rattachements et les rôles dans Laravel Rattachments

## Introduction

Quand on commence avec Laravel Rattachments, la notion de **rattachable** et de **target** peut être déroutante. On a l'habitude des relations Eloquent où l'on définit simplement "un User a plusieurs Posts". Avec Rattachments, il faut penser en termes de **direction** et de **rôle**.

Ce guide a pour but de clarifier ces concepts fondamentaux en les comparant toujours aux relations Eloquent que vous connaissez déjà.

---

## 1. La direction : rattachable → target

Toute relation dans Rattachments est **orientée**. Elle va dans un sens :

```
Qui attache (rattachable) → Qui est attaché (target)
```

### Analogie avec les relations Eloquent

| Eloquent | Rattachments | Explication |
|----------|--------------|-------------|
| `$user->profile()` | `$user->attachTo($profile, ProfileRole::USER)` | User est le rattachable, Profile est la target |
| `$post->tags()` | `$post->attachTo($tag, TagRole::TAG)` | Post est le rattachable, Tag est la target |
| `$doctor->hospitals()` | `$doctor->attachTo($hospital, HospitalRole::DOCTOR)` | Doctor est le rattachable, Hospital est la target |

**C'est comme si vous faisiez une relation `hasOne` ou `hasMany` :**

```php
// En Eloquent - User a un Profile
class User extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}

// En Rattachments - User a un Profile
$user->attachTo($profile, ProfileRole::USER);
// User est le rattachable (celui qui a)
// Profile est la target (ce qui est eu)
```

### Pourquoi cette direction est importante ?

Parce qu'elle définit **qui possède la relation** :

| Expression | Rattachable | Target | Équivalent Eloquent |
|------------|-------------|--------|---------------------|
| "User a un Profile" | User | Profile | `User::profile()` |
| "Post a des Tags" | Post | Tag | `Post::tags()` |
| "Doctor travaille à Hospital" | Doctor | Hospital | `Doctor::hospitals()` |
| "User suit User" | User A | User B | `User::following()` |

---

## 2. Les deux rôles d'un modèle

Un modèle peut jouer **deux rôles** dans Rattachments :

1. **Rattachable** : Il peut attacher d'autres modèles (comme le parent dans Eloquent)
2. **Target** : Il peut être attaché par d'autres modèles (comme l'enfant dans Eloquent)

### Exemple avec User et Profile

```php
// En Eloquent
class User extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);  // User est le parent
    }
}

// En Rattachments
$user->attachTo($profile, ProfileRole::USER);
// User est le rattachable (parent)
// Profile est la target (enfant)
```

**Mais un User peut aussi être target :**

```php
// En Eloquent - User a des followers
class User extends Model
{
    public function followers(): HasMany
    {
        return $this->hasMany(User::class, 'followed_by');  // User est l'enfant
    }
}

// En Rattachments
$otherUser->attachTo($user, FollowRole::FOLLOWER);
// Ici, User est target (enfant)
```

**Un Profile pourrait être rattachable :**

```php
// En Eloquent - Profile a des Specialties
class Profile extends Model
{
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class);
    }
}

// En Rattachments
$profile->attachTo($specialty, SpecialtyRole::PRIMARY);
// Ici, Profile est rattachable (parent)
```

---

## 3. Les trois types de contraintes

### 3.1 `allowedTargets()` : Ce que le modèle peut attacher

Cette méthode dit : **"En tant que rattachable, voici ce que je peux attacher."**

**C'est comme définir les relations autorisées dans Eloquent.**

```php
// En Eloquent - Les relations autorisées
class User extends Model
{
    public function profile(): HasOne { /* ... */ }
    public function hospitals(): BelongsToMany { /* ... */ }
    public function friends(): BelongsToMany { /* ... */ }
}

// En Rattachments - Une seule méthode pour toutes les relations
class User extends Model implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            // User peut attacher un Profile (hasOne)
            Profile::class => [ProfileRole::USER, ProfileRole::ADMIN],
            
            // User peut attacher des Hôpitaux (belongsToMany)
            Hospital::class => [HospitalRole::DOCTOR, HospitalRole::STAFF],
            
            // User peut attacher d'autres Users (belongsToMany)
            User::class => [FriendRole::FRIEND, FriendRole::BEST_FRIEND],
        ];
    }
}
```

**Traduction :** "Un User peut attacher des Profiles (comme un `hasOne`), des Hôpitaux (comme un `belongsToMany`) et d'autres Users (comme un `belongsToMany`)."

**Cas d'usage :**

```php
// ✅ OK - Comme un hasOne
$user->attachTo($profile, ProfileRole::USER);

// ✅ OK - Comme un belongsToMany
$user->attachTo($hospital, HospitalRole::DOCTOR);

// ✅ OK - Comme un belongsToMany
$user->attachTo($friend, FriendRole::FRIEND);

// ❌ Erreur - Non autorisé (comme si la relation n'existait pas en Eloquent)
$user->attachTo($profile, ProfileRole::GUEST);
```

---

### 3.2 `uniqueTargets()` : Les contraintes d'unicité

Cette méthode dit : **"En tant que rattachable, je ne peux avoir qu'un seul de ce type."**

**C'est comme une contrainte `hasOne` ou une clé unique en base de données.**

```php
// En Eloquent - Contrainte d'unicité
class User extends Model
{
    // Un User ne peut avoir qu'un seul Profile (hasOne)
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
    
    // Un User ne peut avoir qu'un seul BEST_FRIEND
    public function bestFriend(): HasOne
    {
        return $this->hasOne(User::class, 'friend_id');
    }
}

// En Rattachments
class User extends Model implements RattachmentInterface
{
    public function uniqueTargets(): array
    {
        return [
            // Un seul Profile (hasOne)
            Profile::class => [],
            
            // Un seul BEST_FRIEND (hasOne)
            User::class => [FriendRole::BEST_FRIEND],
            
            // Un seul CHIEF par Hôpital
            Hospital::class => [HospitalRole::CHIEF],
        ];
    }
}
```

**Traduction :** "Un User ne peut avoir qu'un seul Profile (comme un `hasOne`). Un User ne peut avoir qu'un seul BEST_FRIEND (comme un `hasOne`). Un User ne peut être CHIEF que d'un seul Hôpital."

**Cas d'usage :**

```php
// ✅ OK - Premier Profile (comme un hasOne)
$user->attachTo($profile1, ProfileRole::USER);

// ❌ Erreur - Deuxième Profile (violation de hasOne)
$user->attachTo($profile2, ProfileRole::USER);
// Exception: "User already has a unique attachment to Profile"

// ✅ OK - Premier BEST_FRIEND
$user->attachTo($friend1, FriendRole::BEST_FRIEND);

// ❌ Erreur - Deuxième BEST_FRIEND
$user->attachTo($friend2, FriendRole::BEST_FRIEND);
// Exception: "User already has a unique attachment to User with role 'best_friend'"
```

---

### 3.3 `disallowedTargets()` : Les interdictions (priorité maximale)

Cette méthode dit : **"En tant que rattachable, voici ce que je ne peux PAS attacher."**

**C'est comme une règle métier qui empêche certaines relations.**

```php
// En Eloquent - Règle métier dans le code
class User extends Model
{
    public function attachProfile(Profile $profile): void
    {
        if ($profile->type === 'admin') {
            throw new \Exception('Cannot attach admin profile');
        }
        // ...
    }
}

// En Rattachments - Règle métier déclarative
class User extends Model implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Profile::class => [ProfileRole::USER, ProfileRole::ADMIN],
        ];
    }

    public function disallowedTargets(): array
    {
        return [
            // ADMIN est interdit même s'il est autorisé
            Profile::class => [ProfileRole::ADMIN],
        ];
    }
}
```

**Traduction :** "Un User peut attacher des Profiles avec les rôles USER ou ADMIN, MAIS ADMIN est interdit (comme une règle métier)."

**Cas d'usage :**

```php
// ✅ OK - USER (autorisé et non interdit)
$user->attachTo($profile, ProfileRole::USER);

// ❌ Erreur - ADMIN (autorisé mais interdit)
$user->attachTo($profile, ProfileRole::ADMIN);
// Exception: "Role 'admin' is disallowed for User -> Profile"
```

**⚠️ Priorité absolue :** `disallowedTargets()` l'emporte sur `allowedTargets()`, comme une règle métier qui l'emporte sur une configuration.

---

## 4. La différence entre être rattachable et être target

### Parallèle avec Eloquent

| Eloquent | Rattachments | Explication |
|----------|--------------|-------------|
| `hasOne` / `hasMany` | Rattachable → Target | Le modèle parent attache le modèle enfant |
| `belongsTo` | Target ← Rattachable | Le modèle enfant est attaché par le modèle parent |
| `belongsToMany` | Rattachable ↔ Target | Les deux peuvent être rattachables et targets |

### Exemple concret : User et Profile

**User (rattachable) - C'est comme un `hasOne` :**

```php
// En Eloquent - User est le parent (hasOne)
class User extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}

// En Rattachments - User est le rattachable
class User implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Profile::class => [ProfileRole::USER],  // User peut attacher Profile
        ];
    }
}
```

**Profile (target) - C'est comme un `belongsTo` :**

```php
// En Eloquent - Profile est l'enfant (belongsTo)
class Profile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// En Rattachments - Profile est la target
class Profile implements RattachmentInterface
{
    public function uniqueTargets(): array
    {
        return [
            User::class => [ProfileRole::USER],  // Un seul User par Profile
        ];
    }
}
```

**Résultat :**
- User peut attacher Profile (comme un `hasOne`)
- Profile ne peut être attaché qu'à un seul User (comme un `belongsTo`)

---

## 5. Les rôles contextuels

### Le problème des rôles globaux

Dans beaucoup d'applications, on utilise un enum global :

```php
enum Role: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}
```

**Problème :** "admin" signifie la même chose partout. Mais dans la réalité :

- Un administrateur d'hôpital n'est pas un administrateur de site
- Un administrateur de profil n'est pas un administrateur de produit

**C'est comme si en Eloquent, vous ne pouviez avoir qu'un seul type de relation.**

### La solution : des rôles contextuels

Avec Rattachments, chaque contexte définit ses propres rôles :

```php
// Rôles pour les Hôpitaux (comme un type de relation spécifique)
enum HospitalRole: string implements EnumerableInterface
{
    case DOCTOR = 'doctor';
    case CHIEF = 'chief';
    case ADMIN = 'admin';  // ← Admin d'hôpital
}

// Rôles pour les Profiles (un autre type de relation)
enum ProfileRole: string implements EnumerableInterface
{
    case USER = 'user';
    case ADMIN = 'admin';  // ← Admin de profil (différent !)
}

// Rôles pour les Amis (encore un autre type)
enum FriendRole: string implements EnumerableInterface
{
    case FRIEND = 'friend';
    case BEST_FRIEND = 'best_friend';
    case ACQUAINTANCE = 'acquaintance';
}
```

**Le même nom "admin" signifie des choses différentes selon le contexte, comme `hasOne` et `belongsToMany` sont des relations différentes.**

### Résolution dynamique des rôles

Le package résout automatiquement le bon enum :

```php
// En base de données, le rôle est stocké comme string
$attachment->role; // 'admin'

// Résolution automatique selon le contexte
$attachment = $user->getAttachment($hospital);
$attachment->role; // HospitalRole::ADMIN

$attachment = $user->getAttachment($profile);
$attachment->role; // ProfileRole::ADMIN
```

**C'est comme si Eloquent savait automatiquement quelle relation utiliser.**

---

## 6. Exemples complets avec parallèles Eloquent

### Exemple 1 : User et Profile (hasOne / belongsTo)

**Eloquent :**
```php
class User extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}

class Profile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// Utilisation
$user->profile; // Profile
$profile->user; // User
```

**Rattachments :**
```php
class User extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function allowedTargets(): array
    {
        return [
            Profile::class => [ProfileRole::USER],
        ];
    }

    // Attribut personnalisé comme une relation Eloquent
    protected function profile(): Attribute
    {
        return Attribute::make(
            get: fn (): ?Profile => $this->getTargetsByType(Profile::class)->first()
        );
    }
}

class Profile extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function allowedTargets(): array
    {
        return [];
    }

    public function uniqueTargets(): array
    {
        return [
            User::class => [ProfileRole::USER],
        ];
    }

    // Attribut personnalisé comme une relation Eloquent
    protected function user(): Attribute
    {
        return Attribute::make(
            get: fn (): ?User => $this->getRattachablesByType(User::class)->first()
        );
    }
}

// Utilisation - exactement comme Eloquent !
$user->profile; // Profile
$profile->user; // User
```

---

### Exemple 2 : Post et Tags (belongsToMany)

**Eloquent :**
```php
class Post extends Model
{
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}

class Tag extends Model
{
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}

// Utilisation
$post->tags; // Collection de Tags
$tag->posts; // Collection de Posts
```

**Rattachments :**
```php
class Post extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function allowedTargets(): array
    {
        return [
            Tag::class => [TagRole::PRIMARY, TagRole::REGULAR],
        ];
    }

    // Attribut personnalisé comme une relation Eloquent
    protected function tags(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getTargetsByType(Tag::class)
        );
    }

    protected function primaryTags(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getTargetsByTypeAndRole(Tag::class, TagRole::PRIMARY)
        );
    }
}

class Tag extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function allowedTargets(): array
    {
        return [];
    }

    // Attribut personnalisé comme une relation Eloquent
    protected function posts(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getRattachablesByType(Post::class)
        );
    }
}

// Utilisation - exactement comme Eloquent !
$post->tags; // Collection de Tags
$tag->posts; // Collection de Posts
$post->primary_tags; // Collection de Tags primaires
```

---

### Exemple 3 : Doctor et Hospital (belongsToMany avec métadonnées)

**Eloquent :**
```php
class Doctor extends Model
{
    public function hospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class)
            ->withPivot('role', 'department', 'start_date');
    }
}

class Hospital extends Model
{
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class)
            ->withPivot('role', 'department', 'start_date');
    }
}

// Utilisation
$doctor->hospitals; // Collection de Hospitals
$hospital->doctors; // Collection de Doctors
$doctor->hospitals->first()->pivot->role; // 'chief'
```

**Rattachments :**
```php
class Doctor extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function allowedTargets(): array
    {
        return [
            Hospital::class => [HospitalRole::DOCTOR, HospitalRole::CHIEF],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            Hospital::class => [HospitalRole::CHIEF],
        ];
    }

    // Attribut personnalisé comme une relation Eloquent
    protected function hospitals(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getTargetsByType(Hospital::class)
        );
    }

    protected function chiefHospitals(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getTargetsByTypeAndRole(Hospital::class, HospitalRole::CHIEF)
        );
    }

    // Méthode métier avec métadonnées
    public function workAt(Hospital $hospital, HospitalRole $role): void
    {
        $this->attachTo($hospital, $role, [
            'start_date' => now()->toDateString(),
            'department' => 'Cardiology',
        ]);
    }

    // Récupérer les métadonnées d'une relation
    public function getHospitalMetadata(Hospital $hospital): ?array
    {
        $attachment = $this->getAttachment($hospital);
        return $attachment?->metadata;
    }
}

class Hospital extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function allowedTargets(): array
    {
        return [
            Doctor::class => [HospitalRole::DOCTOR, HospitalRole::CHIEF],
        ];
    }

    // Attribut personnalisé comme une relation Eloquent
    protected function doctors(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getRattachablesByType(Doctor::class)
        );
    }
}

// Utilisation - exactement comme Eloquent !
$doctor->hospitals; // Collection de Hospitals
$hospital->doctors; // Collection de Doctors
$doctor->chief_hospitals; // Collection de Hospitals où Doctor est CHIEF

// Avec métadonnées
$doctor->workAt($hospital, HospitalRole::CHIEF);
$metadata = $doctor->getHospitalMetadata($hospital); // ['start_date' => '...', 'department' => '...']
```

---

### Exemple 4 : User et User (relation récursive)

**Eloquent :**
```php
class User extends Model
{
    // Amis (belongsToMany récursive)
    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id');
    }

    // Abonnements (belongsToMany récursive)
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followed_id');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_id', 'follower_id');
    }

    // Blocages (belongsToMany récursive)
    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocker_id', 'blocked_id');
    }
}
```

**Rattachments :**
```php
class User extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function allowedTargets(): array
    {
        return [
            // Une seule table pour toutes les relations récursives !
            User::class => [
                FriendRole::FRIEND,
                FriendRole::BEST_FRIEND,
                FollowRole::FOLLOWER,
                BlockRole::BLOCKED,
            ],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            User::class => [FriendRole::BEST_FRIEND],
        ];
    }

    public function disallowedTargets(): array
    {
        return [
            User::class => [BlockRole::BLOCKED],
        ];
    }

    // ============================================================
    // ATTRIBUTS COMME DES RELATIONS ELOQUENT
    // ============================================================

    protected function friends(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getTargetsByTypeAndRole(User::class, FriendRole::FRIEND)
        );
    }

    protected function bestFriend(): Attribute
    {
        return Attribute::make(
            get: fn (): ?User => $this->getTargetsByTypeAndRole(User::class, FriendRole::BEST_FRIEND)->first()
        );
    }

    protected function following(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getTargetsByTypeAndRole(User::class, FollowRole::FOLLOWER)
        );
    }

    protected function followers(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getRattachablesByTypeAndRole(User::class, FollowRole::FOLLOWER)
        );
    }

    protected function blockedUsers(): Attribute
    {
        return Attribute::make(
            get: fn (): Collection => $this->getTargetsByTypeAndRole(User::class, BlockRole::BLOCKED)
        );
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    public function becomeFriendWith(User $friend): void
    {
        $this->attachTo($friend, FriendRole::FRIEND);
        $friend->attachTo($this, FriendRole::FRIEND);
    }

    public function follow(User $user): void
    {
        $this->attachTo($user, FollowRole::FOLLOWER);
    }

    public function block(User $user): void
    {
        $this->attachTo($user, BlockRole::BLOCKED);
        $user->attachTo($this, BlockRole::BLOCKED);
    }
}

// Utilisation - comme avec Eloquent !
$user->friends;      // Collection d'amis
$user->best_friend;  // Meilleur ami
$user->following;    // Abonnements
$user->followers;    // Abonnés
$user->blocked_users; // Utilisateurs bloqués
```

---

## 7. Tableau récapitulatif des parallèles

### Que fait chaque méthode ?

| Méthode | Équivalent Eloquent | Que fait-elle ? | Priorité |
|---------|---------------------|-----------------|----------|
| `allowedTargets()` | Déclaration des relations (`hasOne`, `belongsToMany`, etc.) | Définit ce que le modèle peut attacher | 🟢 Normale |
| `uniqueTargets()` | Contrainte `hasOne` ou `UNIQUE` en base de données | Définit l'unicité de ce qu'il attache | 🟡 Élevée |
| `disallowedTargets()` | Règle métier dans le code | Définit ce qu'il ne peut PAS attacher | 🔴 Maximale |

### Comment interpréter une relation ?

| Vous voulez... | En Eloquent | En Rattachments | Rattachable | Target |
|----------------|-------------|-----------------|-------------|--------|
| User a un Profile | `hasOne` | `$user->attachTo($profile, ProfileRole::USER)` | User | Profile |
| Post a des Tags | `belongsToMany` | `$post->attachTo($tag, TagRole::TAG)` | Post | Tag |
| Doctor travaille à Hospital | `belongsToMany` | `$doctor->attachTo($hospital, HospitalRole::DOCTOR)` | Doctor | Hospital |
| User suit User | `belongsToMany` | `$user->attachTo($other, FollowRole::FOLLOWER)` | User A | User B |
| User bloque User | `belongsToMany` | `$user->attachTo($other, BlockRole::BLOCKED)` | User A | User B |

---

## 8. Points clés à retenir

1. **Toute relation est orientée** : elle va de `rattachable` vers `target` (comme un `hasOne` va de User vers Profile)

2. **Un modèle peut être les deux** : rattachable dans une relation (comme User dans `hasOne`), target dans une autre (comme Profile dans `belongsTo`)

3. **`allowedTargets()`** : ce que le modèle peut attacher (comme déclarer des relations Eloquent)

4. **`uniqueTargets()`** : ce que le modèle ne peut attacher qu'une seule fois (comme un `hasOne`)

5. **`disallowedTargets()`** : ce que le modèle ne peut PAS attacher (comme une règle métier)

6. **Les rôles sont contextuels** : le même nom peut avoir des significations différentes selon le contexte (comme une clé étrangère différente)

7. **C'est le rattachable qui définit les contraintes** : ce qu'il peut ou ne peut pas attacher (comme le parent dans Eloquent)

---

## Conclusion

Les concepts de **rattachable**, **target** et **rôles contextuels** sont au cœur de Laravel Rattachments. Une fois qu'on a compris que :

1. **Toute relation va d'un rattachable vers une target** (comme un `hasOne`)
2. **C'est le rattachable qui définit ce qu'il peut attacher** (comme le modèle parent)
3. **Les rôles sont spécifiques à chaque contexte** (comme des relations différentes)

...tout devient plus clair. Le système devient intuitif et puissant.

**C'est comme si vous pouviez définir toutes vos relations Eloquent dans une seule méthode `allowedTargets()`.**

---

**Construit avec ❤️ pour la communauté Laravel**