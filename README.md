# Laravel Rattachments

> Un système de rattachement polymorphique double pour applications Laravel

Un package Laravel complet pour gérer des relations polymorphiques doubles entre n'importe quels modèles Eloquent, avec des rôles contextuels, des métadonnées, un système de contraintes avancé et des hooks de cycle de vie.

---

## 📋 Table des matières

- [Problématique et solution](#-problématique-et-solution)
- [Fonctionnalités](#-fonctionnalités)
- [Installation](#-installation)
- [Concept fondamental](#-concept-fondamental)
- [Double polymorphisme](#-double-polymorphisme)
- [Les contraintes](#-les-contraintes)
- [Rôles contextuels](#-rôles-contextuels)
- [Résolution dynamique des rôles](#-résolution-dynamique-des-rôles)
- [Système de hooks](#-système-de-hooks)
- [Utilisation avec le service](#-utilisation-avec-le-service)
- [Utilisation avec le trait](#-utilisation-avec-le-trait)
- [Exemples concrets](#-exemples-concrets)
- [Cas d'usage](#-cas-dusage)
- [Règles du système](#-règles-du-système)
- [Inspection CLI](#-inspection-cli)
- [Dépendances](#-dépendances)
- [Licence](#-licence)

---

## 🎯 Problématique et solution

### Le problème

Dans les applications modernes, les relations entre entités sont rarement simples et unidirectionnelles. Les besoins courants incluent :

- **Relations sociales** : Amitiés, abonnements, blocages
- **Relations professionnelles** : Médecins dans des hôpitaux, employés dans des entreprises
- **Relations de contenu** : Tags sur des articles, catégories sur des produits
- **Relations contextuelles** : Rôles différents selon le contexte

Les solutions traditionnelles (tables de liaison spécifiques) conduisent à :

```php
// ❌ Une table par type de relation
// friendships table
// follows table
// blocks table
// tags table
// memberships table
// invitations table
// ...

// ❌ Ou des relations complexes avec des packages lourds
// Spatie\Permission, Laravel\Nova, etc.
```

**Le résultat :**
- ✅ Multiples tables
- ❌ Code répétitif
- ❌ Relations complexes
- ❌ Pas de polymorphisme
- ❌ Pas de rôles contextuels
- ❌ Pas de métadonnées

---

### La solution

**Laravel Rattachments** propose une approche élégante : **une seule table polymorphique** pour gérer **tous les types de relations**.

```php
// ✅ Une table pour toutes les relations
// Une seule table rattachments gère :
// - Amitiés
// - Abonnements
// - Blocages
// - Tags
// - Adhésions
// - Invitations
// - Et tout ce que vous voulez !
```

**Le principe :**

```php
// Une relation est définie par :
// - Qui attache (rattachable)
// - Qui est attaché (target)
// - Le rôle de la relation
// - Des métadonnées optionnelles

$user1->attachTo($user2, FriendRole::FRIEND);  // Une amitié
$user1->attachTo($user2, BlockRole::BLOCKED);  // Un blocage
$doctor->attachTo($hospital, HospitalRole::DOCTOR); // Une affiliation
$post->attachTo($tag, TagRole::TAG); // Un tag
```

**Pourquoi c'est révolutionnaire ?**

- ✅ **Une table** pour toutes les relations
- ✅ **Polymorphique** : n'importe quel modèle avec n'importe quel autre
- ✅ **Rôles contextuels** : chaque modèle définit ses propres rôles
- ✅ **Contraintes avancées** : autorisation, unicité, interdiction
- ✅ **Métadonnées** : stockez des données supplémentaires
- ✅ **Hooks de cycle de vie** : intervenez avant/après chaque opération
- ✅ **API fluide** : utilisez vos modèles comme des objets métier

---

## ✨ Fonctionnalités

- ✅ **Double polymorphisme** - Rattachez n'importe quel modèle à n'importe quel autre modèle
- ✅ **Rôles contextuels** - Chaque modèle définit ses propres rôles via des enums
- ✅ **Système de contraintes complet** - Autorisation, unicité et interdiction
- ✅ **Validation centralisée** - `ConstraintValidator` pour une validation cohérente
- ✅ **Résolution dynamique des rôles** - Pas d'enum global, résolution basée sur le contexte
- ✅ **Métadonnées flexibles** - Stockez des données supplémentaires au format JSON
- ✅ **Hooks de cycle de vie** - `beforeAttach`, `afterAttach`, `beforeDetach`, `afterDetach`, etc.
- ✅ **Trait HasRattachments** - API fluide directement dans vos modèles
- ✅ **Filtrage avancé** - Par type, rôle, ou combinaison
- ✅ **Opérations en masse** - Rattachement et détachement multiples
- ✅ **Synchronisation** - Synchronisez tous les rattachements d'un modèle en une seule opération
- ✅ **Pagination** - Récupérez les résultats paginés
- ✅ **Inspection CLI** - Directive `rattachments:inspect` pour analyser les contraintes
- ✅ **Découverte automatique** - Scan des modèles implémentant l'interface
- ✅ **Contraintes uniques granulaires** - Un seul attachement par type ET rôle
- ✅ **UnknownRole** - Rétrocompatibilité pour les rôles supprimés
- ✅ **Typage strict** - Utilisation de `Model&RattachmentInterface` pour la sécurité des types
- ✅ **Détection de circularité** - Empêche les relations circulaires
- ✅ **Auto-attachement** - Empêche un modèle de s'attacher à lui-même
- ✅ **Tests complets** - Couverture complète des tests d'intégration

---

## 🚀 Installation

```bash
composer require andydefer/laravel-rattachments
```

### Publier les migrations

```bash
php artisan vendor:publish --tag=rattachments-migrations
```

### Exécuter les migrations

```bash
php artisan migrate
```

---

## 🎯 Concept fondamental

### Qu'est-ce qu'un rattachement ?

Un rattachement est une relation polymorphique double entre deux modèles Eloquent. Il est représenté par un enregistrement dans la table `rattachments` qui stocke :

- **Le modèle source** (`rattachable`) - Celui qui attache
- **Le modèle cible** (`target`) - Celui qui est attaché
- **Un rôle** - Définit la nature de la relation
- **Des métadonnées** - Données supplémentaires au format JSON

### Exemple concret

```php
// Un médecin (rattachable) est rattaché à un hôpital (target)
// avec le rôle "doctor" et des métadonnées sur ses horaires

$attachment = $service->attach(
    $doctor,                    // rattachable
    $hospital,                  // target
    HospitalUserRole::DOCTOR,   // rôle
    [                           // métadonnées
        'consultation_days' => ['monday', 'wednesday', 'friday'],
        'consultation_hours' => '09:00-17:00',
    ]
);
```

---

## 🔄 Double polymorphisme

### Qu'est-ce que le double polymorphisme ?

Le package permet de rattacher **n'importe quel modèle** à **n'importe quel autre modèle**, dans les deux sens :

```php
// ✅ User → Hospital
$user->attachTo($hospital, Role::DOCTOR);

// ✅ Hospital → User
$hospital->attachTo($user, Role::PATIENT);

// ✅ User → User
$user1->attachTo($user2, FriendRole::FRIEND);

// ✅ Post → Tag
$post->attachTo($tag, TagRole::TAG);

// ✅ Doctor → Specialty
$doctor->attachTo($specialty, SpecialtyRole::PRIMARY);
```

### Pourquoi c'est puissant ?

| Approche traditionnelle | Laravel Rattachments |
|------------------------|---------------------|
| `User::friends()` | `$user->getTargetsByType(User::class)` |
| `User::follows()` | `$user->getTargetsByType(User::class, FollowRole::FOLLOWER)` |
| `User::blocks()` | `$user->getTargetsByType(User::class, BlockRole::BLOCKED)` |
| `Post::tags()` | `$post->getTargetsByType(Tag::class)` |
| `Doctor::hospitals()` | `$doctor->getTargetsByType(Hospital::class)` |

**Une seule méthode, des résultats infinis !**

---

## 🔒 Les contraintes

L'interface `RattachmentInterface` permet à vos modèles de définir trois types de contraintes.

### 1. Cibles autorisées (`allowedTargets`)

Définit quels modèles peuvent être attachés et avec quels rôles.

```php
final class User extends Model implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Hospital::class => [
                HospitalUserRole::DOCTOR,
                HospitalUserRole::STAFF,
                HospitalUserRole::ADMIN,
            ],
            Pharmacy::class => [
                PharmacyUserRole::PHARMACIST,
                PharmacyUserRole::STAFF,
            ],
        ];
    }
}
```

### 2. Cibles uniques granulaires (`uniqueTargets`)

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

        // Une seule PRIMARY specialty
        Specialty::class => [Role::PRIMARY],
    ];
}
```

**Exemple concret :**

```php
// ✅ OK - Premier CHIEF
$doctor->attachTo($hospital1, Role::CHIEF);

// ❌ ERREUR - Déjà CHIEF d'un hôpital
$doctor->attachTo($hospital2, Role::CHIEF);
// "Doctor already has a unique attachment to Hospital with role 'chief'"

// ✅ OK - Peut être DOCTOR dans plusieurs hôpitaux
$doctor->attachTo($hospital2, Role::DOCTOR);
$doctor->attachTo($hospital3, Role::DOCTOR);
```

### 3. Cibles interdites (`disallowedTargets`)

Bloque des cibles ou des rôles spécifiques (priorité maximale).

```php
public function disallowedTargets(): array
{
    return [
        Specialty::class => [],  // Bloque TOUS les rattachements
        Post::class => [PostUserRole::REVIEWER], // Bloque ce rôle
        Hospital::class => [HospitalUserRole::ADMIN], // Bloque ADMIN
    ];
}
```

---

## 🏷️ Rôles contextuels

### Pourquoi des rôles contextuels ?

Dans une application, le même terme ("admin") peut avoir des significations différentes :

- Un administrateur d'hôpital ≠ administrateur de site
- Un médecin peut avoir des rôles différents selon l'hôpital

Les rôles contextuels permettent de définir des enums spécifiques à chaque contexte.

```php
// Rôles pour un médecin dans un hôpital
enum HospitalRole: string implements EnumerableInterface
{
    case DOCTOR = 'doctor';
    case CHIEF = 'chief';
    case RESIDENT = 'resident';

    public function getValue(): string
    {
        return $this->value;
    }
}

// Rôles pour un utilisateur dans un réseau social
enum FriendRole: string implements EnumerableInterface
{
    case FRIEND = 'friend';
    case BEST_FRIEND = 'best_friend';
    case ACQUAINTANCE = 'acquaintance';

    public function getValue(): string
    {
        return $this->value;
    }
}
```

---

## 🔄 Résolution dynamique des rôles

### Comment ça fonctionne ?

Le package résout automatiquement le bon enum en fonction du contexte :

```php
// En base de données
$attachment = [
    'rattachable_type' => 'App\Models\User',
    'target_type' => 'App\Models\Hospital',
    'role' => 'doctor',
];

// Lecture - résolution automatique
$attachment->role; // HospitalUserRole::DOCTOR

// Autre contexte
$attachment = [
    'rattachable_type' => 'App\Models\Post',
    'target_type' => 'App\Models\Tag',
    'role' => 'primary',
];

// Lecture - résolution automatique
$attachment->role; // TagRole::PRIMARY
```

### Avantages

- ✅ **Pas d'enum global** - Chaque modèle définit ses propres rôles
- ✅ **Contextuel** - Le même nom a des significations différentes selon le contexte
- ✅ **Rétrocompatible** - Les rôles supprimés retournent `UnknownRole`
- ✅ **Performant** - Instanciation directe, pas de requête SQL

### UnknownRole

Si un rôle n'est plus autorisé par les contraintes actuelles, l'accesseur retourne une instance de `UnknownRole` :

```php
$role = $attachment->role;

if ($role instanceof UnknownRole) {
    Log::warning('Unknown role detected', [
        'attachment_id' => $attachment->id,
        'role_value' => $role->getValue(),
    ]);
}
```

---

## 🔌 Système de hooks

Le package expose un système de hooks permettant d'intervenir à chaque étape du cycle de vie d'un attachement.

### Interface `AttachmentHookInterface`

Tous les modèles implémentant `RattachmentInterface` héritent de `AttachmentHookInterface` et peuvent surcharger les méthodes suivantes :

| Hook | Déclenchement |
|------|---------------|
| `beforeAttach()` | Avant la création d'un attachement |
| `afterAttach()` | Après la création d'un attachement |
| `beforeDetach()` | Avant la suppression d'un attachement |
| `afterDetach()` | Après la suppression d'un attachement |
| `beforeUpdateRole()` | Avant la mise à jour d'un rôle |
| `afterUpdateRole()` | Après la mise à jour d'un rôle |
| `beforeUpdateMetadata()` | Avant la mise à jour des métadonnées |
| `afterUpdateMetadata()` | Après la mise à jour des métadonnées |
| `beforeDetachAll()` | Avant la suppression de tous les attachements |
| `afterDetachAll()` | Après la suppression de tous les attachements |

### Position du modèle

Chaque hook reçoit un paramètre `HookPosition` qui indique si le modèle est le **rattachable** (celui qui attache) ou le **target** (celui qui est attaché) :

```php
use AndyDefer\LaravelRattachments\Enums\HookPosition;

enum HookPosition: string
{
    case RATTACHABLE = 'rattachable';
    case TARGET = 'target';
}
```

### Exemple d'implémentation

```php
class User extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function beforeAttach(
        Model $other,
        EnumerableInterface $role,
        array $metadata,
        HookPosition $position
    ): void {
        Log::info('Before attach', [
            'user_id' => $this->id,
            'other_type' => get_class($other),
            'other_id' => $other->getKey(),
            'role' => $role->getValue(),
            'position' => $position->value,
        ]);

        // Validation supplémentaire
        if ($position === HookPosition::RATTACHABLE && $role->getValue() === 'admin') {
            throw new \RuntimeException('Only administrators can attach with admin role');
        }
    }

    public function afterAttach(
        Model $other,
        EnumerableInterface $role,
        Model $attachment,
        HookPosition $position
    ): void {
        AuditLog::create([
            'user_id' => $this->id,
            'action' => 'attach',
            'target_type' => get_class($other),
            'target_id' => $other->getKey(),
            'role' => $role->getValue(),
            'attachment_id' => $attachment->id,
            'position' => $position->value,
        ]);

        // Invalider le cache
        Cache::forget("user_{$this->id}_attachments");

        // Envoyer une notification
        if ($position === HookPosition::TARGET) {
            $other->notify(new UserAttachedToYouNotification($this, $role));
        }
    }

    public function beforeDetach(
        Model $other,
        Model $attachment,
        HookPosition $position
    ): void {
        Log::info('Before detach', [
            'user_id' => $this->id,
            'attachment_id' => $attachment->id,
        ]);
    }

    public function afterDetach(
        Model $other,
        Model $attachment,
        HookPosition $position
    ): void {
        Cache::forget("user_{$this->id}_attachments");

        if ($position === HookPosition::RATTACHABLE) {
            $other->notify(new UserDetachedFromYouNotification($this));
        }
    }
}
```

---

## 📖 Utilisation avec le service

### Injection du service

```php
use AndyDefer\LaravelRattachments\Services\RattachmentService;

class DoctorController extends Controller
{
    public function __construct(
        private readonly RattachmentService $rattachmentService
    ) {}
}
```

### Créer un rattachement

```php
public function attachToHospital(Hospital $hospital)
{
    $doctor = auth()->user();

    $attachment = $this->rattachmentService->attach(
        $doctor,
        $hospital,
        HospitalUserRole::DOCTOR,
        [
            'consultation_days' => ['monday', 'wednesday', 'friday'],
            'consultation_hours' => '09:00-17:00',
        ]
    );

    return response()->json([
        'message' => 'Docteur rattaché à l\'hôpital',
        'role' => $attachment->role->getValue(),
        'metadata' => $attachment->metadata,
    ]);
}
```

### Rattachement multiple

```php
// Rattacher plusieurs modèles à une même cible
$attachments = $this->rattachmentService->attachMultiple(
    collect([$doctor1, $doctor2, $doctor3]),
    $hospital,
    HospitalUserRole::DOCTOR,
    ['department' => 'Cardiology']
);

// Rattacher un modèle à plusieurs cibles
$attachments = $this->rattachmentService->attachToMultiple(
    $doctor,
    collect([$hospital1, $hospital2, $hospital3]),
    HospitalUserRole::DOCTOR
);
```

### Supprimer un rattachement

```php
// Supprimer un rattachement spécifique
$this->rattachmentService->detach($doctor, $hospital);

// Supprimer plusieurs rattachements
$this->rattachmentService->detachMultiple(
    collect([$doctor1, $doctor2]),
    $hospital
);

// Supprimer tous les rattachements d'un modèle
$this->rattachmentService->detachAll($doctor);
```

### Vérifier l'existence

```php
// Vérifier si un modèle est rattaché à un autre
if ($this->rattachmentService->isAttached($doctor, $hospital)) {
    // ...
}

// Vérifier si un rôle existe pour une cible
if ($this->rattachmentService->hasRoleAttached($hospital, HospitalUserRole::DOCTOR)) {
    // L'hôpital a au moins un médecin
}

// Récupérer un attachement spécifique
$attachment = $this->rattachmentService->getAttachment($doctor, $hospital);
```

### Récupérer les rattachements

```php
// Récupérer tous les modèles rattachés à une cible
$doctors = $this->rattachmentService->getRattachables($hospital);

// Récupérer les modèles rattachés avec un rôle spécifique
$doctors = $this->rattachmentService->getRattachablesByRole($hospital, HospitalUserRole::DOCTOR);

// Récupérer toutes les cibles d'un modèle
$hospitals = $this->rattachmentService->getTargets($doctor);

// Récupérer les cibles d'un type spécifique
$hospitals = $this->rattachmentService->getTargetsByType($doctor, Hospital::class);

// Récupérer les cibles par type et rôle
$hospitals = $this->rattachmentService->getTargetsByTypeAndRole(
    $doctor,
    Hospital::class,
    HospitalUserRole::DOCTOR
);

// Récupérer les cibles de plusieurs types avec plusieurs rôles
$targets = $this->rattachmentService->getTargetsByTypesAndRoles(
    $doctor,
    [Hospital::class, Pharmacy::class],
    [HospitalUserRole::DOCTOR, PharmacyUserRole::PHARMACIST]
);
```

### Pagination

```php
// Paginer les cibles
$hospitals = $this->rattachmentService->getTargetsPaginated($doctor, 10, 2);

// Paginer les cibles par rôle
$hospitals = $this->rattachmentService->getTargetsByRolePaginated(
    $doctor,
    HospitalUserRole::DOCTOR,
    10,
    2
);

// Paginer les rattachables
$doctors = $this->rattachmentService->getRattachablesPaginated($hospital, 10, 2);

// Paginer les rattachables par rôle
$doctors = $this->rattachmentService->getRattachablesByRolePaginated(
    $hospital,
    HospitalUserRole::DOCTOR,
    10,
    2
);
```

### Compter

```php
// Compter les cibles
$total = $this->rattachmentService->countTargets($doctor);

// Compter les cibles par rôle
$total = $this->rattachmentService->countTargetsByRole($doctor, HospitalUserRole::DOCTOR);

// Compter les rattachables
$total = $this->rattachmentService->countRattachables($hospital);

// Compter les rattachables par rôle
$total = $this->rattachmentService->countRattachablesByRole($hospital, HospitalUserRole::DOCTOR);
```

### Mettre à jour

```php
// Mettre à jour le rôle
$this->rattachmentService->updateRole($doctor, $hospital, HospitalUserRole::ADMIN);

// Mettre à jour plusieurs rôles
$this->rattachmentService->updateRoleForMultiple(
    collect([$doctor1, $doctor2]),
    $hospital,
    HospitalUserRole::ADMIN
);

// Mettre à jour les métadonnées (remplacement)
$this->rattachmentService->updateMetadata($doctor, $hospital, [
    'consultation_hours' => '10:00-18:00',
]);

// Fusionner les métadonnées (conserve les existantes)
$this->rattachmentService->mergeMetadata($doctor, $hospital, [
    'availability' => 'Monday-Friday',
]);
```

### Synchroniser

```php
// Synchronise tous les rattachements d'un modèle
$attachments = $this->rattachmentService->syncAttachments($doctor, [
    [
        'target' => $hospital1,
        'role' => HospitalUserRole::DOCTOR,
        'metadata' => ['primary' => true],
    ],
    [
        'target' => $hospital2,
        'role' => HospitalUserRole::DOCTOR,
    ],
    [
        'target' => $pharmacy,
        'role' => PharmacyUserRole::PHARMACIST,
        'metadata' => ['supplier' => true],
    ],
]);

// Les rattachements précédents qui ne sont pas dans la liste sont supprimés
```

### Rôles distincts

```php
// Rôles distincts pour ce modèle comme rattachable
$roles = $this->rattachmentService->getDistinctRolesForRattachable($doctor);

// Rôles distincts pour ce modèle comme cible
$roles = $this->rattachmentService->getDistinctRolesForTarget($hospital);
```

---

## 📖 Utilisation avec le trait

Le trait `HasRattachments` offre une API fluide directement sur vos modèles.

### Ajouter le trait

```php
class Doctor extends Model implements RattachmentInterface
{
    use HasRattachments;
}
```

### API fluide

```php
$doctor = Doctor::find(1);
$hospital = Hospital::find(1);

// Rattacher
$doctor->attachTo($hospital, HospitalRole::DOCTOR);

// Vérifier
if ($doctor->isAttachedTo($hospital)) {
    // ...
}

// Récupérer les hôpitaux
$hospitals = $doctor->getTargetsByRole(HospitalRole::DOCTOR);

// Compter
$count = $doctor->countTargetsByRole(HospitalRole::DOCTOR);

// Détacher
$doctor->detachFrom($hospital);

// Synchroniser
$doctor->syncAttachments([
    ['target' => $hospital1, 'role' => HospitalRole::DOCTOR],
    ['target' => $hospital2, 'role' => HospitalRole::CHIEF],
]);
```

### Récupérer dans les deux sens

```php
$user = User::find(1);

// Récupérer les cibles (ce que User a attaché)
$hospitals = $user->getTargetsByType(Hospital::class);

// Récupérer les rattachables (ce qui est attaché à User)
$specialties = $user->getRattachablesByType(Specialty::class);

// Vérifier les deux sens
if ($user->isAttachedTo($hospital)) {
    // User est attaché à Hospital
}

if ($hospital->isAttachedTo($user)) {
    // Hospital est attaché à User
}
```

### Compter les relations

```php
// Compter les cibles
$totalHospitals = $user->countTargets();
$totalDoctors = $user->countTargetsByRole(HospitalRole::DOCTOR);

// Compter les rattachables
$totalUsers = $hospital->countRattachables();
$totalDoctors = $hospital->countRattachablesByRole(HospitalRole::DOCTOR);
```

### Méthodes métier explicites

Créez des méthodes métier pour une sémantique claire :

```php
class User extends Model implements RattachmentInterface
{
    use HasRattachments;

    // Amitié bidirectionnelle
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

    public function getBestFriends(): Collection
    {
        return $this->getTargetsByTypeAndRole(User::class, FriendRole::BEST_FRIEND);
    }

    // Suivi unidirectionnel
    public function follow(User $user): void
    {
        $this->attachTo($user, FollowRole::FOLLOWER);
    }

    public function unfollow(User $user): void
    {
        $this->detachFrom($user);
    }

    public function isFollowing(User $user): bool
    {
        return $this->isAttachedTo($user);
    }

    public function getFollowers(): Collection
    {
        return $this->getRattachablesByRole(FollowRole::FOLLOWER);
    }

    public function getFollowing(): Collection
    {
        return $this->getTargetsByRole(FollowRole::FOLLOWER);
    }

    // Blocage
    public function block(User $user): void
    {
        $this->attachTo($user, BlockRole::BLOCKED);
    }

    public function blockMutually(User $user): void
    {
        $this->attachTo($user, BlockRole::BLOCKED);
        $user->attachTo($this, BlockRole::BLOCKED);
    }

    public function unblock(User $user): void
    {
        $this->detachFrom($user);
    }

    public function isBlocking(User $user): bool
    {
        return $this->isAttachedTo($user);
    }

    public function getBlockedUsers(): Collection
    {
        return $this->getTargetsByRole(BlockRole::BLOCKED);
    }
}
```

---

## 💡 Exemples concrets

### Exemple 1 : Réseau social - Amitiés

```php
class User extends Model implements RattachmentInterface
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
        return $this->getTargetsByTypeAndRole(User::class, FriendRole::FRIEND);
    }

    public function getBestFriends(): Collection
    {
        return $this->getTargetsByTypeAndRole(User::class, FriendRole::BEST_FRIEND);
    }

    public function isFriendWith(User $user): bool
    {
        return $this->isAttachedTo($user);
    }
}
```

### Exemple 2 : Réseau social - Abonnements

```php
class User extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function follow(User $user): void
    {
        $this->attachTo($user, FollowRole::FOLLOWER);
    }

    public function unfollow(User $user): void
    {
        $this->detachFrom($user);
    }

    public function isFollowing(User $user): bool
    {
        return $this->isAttachedTo($user);
    }

    public function getFollowers(): Collection
    {
        return $this->getRattachablesByRole(FollowRole::FOLLOWER);
    }

    public function getFollowing(): Collection
    {
        return $this->getTargetsByRole(FollowRole::FOLLOWER);
    }
}
```

### Exemple 3 : Gestion de contenu - Tags

```php
class Post extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function addTag(Tag $tag, TagRole $role): void
    {
        $this->attachTo($tag, $role, [
            'added_at' => now()->toDateTimeString(),
        ]);
    }

    public function removeTag(Tag $tag): void
    {
        $this->detachFrom($tag);
    }

    public function getTags(): Collection
    {
        return $this->getTargetsByType(Tag::class);
    }

    public function getPrimaryTags(): Collection
    {
        return $this->getTargetsByTypeAndRole(Tag::class, TagRole::PRIMARY);
    }

    public function syncTags(array $tags): Collection
    {
        return $this->syncAttachments($tags);
    }
}
```

### Exemple 4 : Relations professionnelles avec hooks

```php
class Doctor extends Model implements RattachmentInterface
{
    use HasRattachments;

    public function workAt(Hospital $hospital, HospitalRole $role): void
    {
        $this->attachTo($hospital, $role, [
            'start_date' => now()->toDateString(),
        ]);
    }

    public function leaveHospital(Hospital $hospital): void
    {
        $this->detachFrom($hospital);
    }

    public function promoteToChief(Hospital $hospital): void
    {
        $this->updateRoleFor($hospital, HospitalRole::CHIEF);
    }

    public function getHospitals(): Collection
    {
        return $this->getTargetsByType(Hospital::class);
    }

    public function beforeAttach(
        Model $other,
        EnumerableInterface $role,
        array $metadata,
        HookPosition $position
    ): void {
        Log::info('Doctor ' . $this->id . ' is being attached', [
            'hospital_id' => $other->getKey(),
            'role' => $role->getValue(),
        ]);
    }

    public function afterAttach(
        Model $other,
        EnumerableInterface $role,
        Model $attachment,
        HookPosition $position
    ): void {
        // Envoyer une notification à l'hôpital
        $other->notify(new DoctorJoinedNotification($this, $role));
    }
}
```

---

## 🎯 Cas d'usage

### Réseau social

| Relation | Rattachable → Target | Rôle |
|----------|---------------------|------|
| Amitié | User → User | `FriendRole::FRIEND` |
| Abonnement | User → User | `FollowRole::FOLLOWER` |
| Blocage | User → User | `BlockRole::BLOCKED` |
| Invitation | Group → User | `InviteRole::PENDING` |
| Acceptation | User → Group | `InviteRole::ACCEPTED` |

### Santé

| Relation | Rattachable → Target | Rôle |
|----------|---------------------|------|
| Médecin → Hôpital | Doctor → Hospital | `HospitalRole::DOCTOR` |
| Patient → Médecin | Patient → Doctor | `PatientRole::PATIENT` |
| Spécialité → Médecin | Specialty → Doctor | `SpecialtyRole::PRIMARY` |
| Ordonnance → Patient | Prescription → Patient | `PrescriptionRole::ACTIVE` |

### Contenu

| Relation | Rattachable → Target | Rôle |
|----------|---------------------|------|
| Article → Tag | Post → Tag | `TagRole::PRIMARY` |
| Article → Catégorie | Post → Category | `CategoryRole::MAIN` |
| Article → Auteur | Post → User | `AuthorRole::AUTHOR` |
| Commentaire → Article | Comment → Post | `CommentRole::APPROVED` |

### Entreprise

| Relation | Rattachable → Target | Rôle |
|----------|---------------------|------|
| Employé → Entreprise | Employee → Company | `EmployeeRole::MANAGER` |
| Employé → Projet | Employee → Project | `ProjectRole::LEAD` |
| Projet → Client | Project → Client | `ClientRole::ACTIVE` |
| Tâche → Employé | Task → Employee | `TaskRole::ASSIGNED` |

---

## 📋 Règles du système

### Règle 1 : Toute relation est orientée

```
Qui attache (rattachable) → Qui est attaché (target)
```

```php
$user->attachTo($profile, ProfileRole::USER);
// User est le rattachable, Profile est le target
// "User a un Profile"
```

---

### Règle 2 : Les modèles doivent implémenter l'interface

```php
// ❌ Erreur
class User extends Model
{
    use HasRattachments;
}

// ✅ OK
class User extends Model implements RattachmentInterface
{
    use HasRattachments;
}
```

---

### Règle 3 : Le rôle est obligatoire

```php
// ❌ Erreur
$user->attachTo($profile);

// ✅ OK
$user->attachTo($profile, ProfileRole::USER);
```

---

### Règle 4 : `disallowedTargets` a priorité sur `allowedTargets`

```php
public function allowedTargets(): array
{
    return [
        Profile::class => [ProfileRole::USER, ProfileRole::ADMIN],
    ];
}

public function disallowedTargets(): array
{
    return [
        Profile::class => [ProfileRole::ADMIN],  // ✅ ADMIN est bloqué
    ];
}
```

**Résultat :**
```php
// ✅ OK
$user->attachTo($profile, ProfileRole::USER);

// ❌ Erreur - ADMIN est bloqué
$user->attachTo($profile, ProfileRole::ADMIN);
// Role "admin" is disallowed
```

---

### Règle 5 : Circularité - INTERDITE

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


```php
// ❌ Erreur - Circularité sur uniqueTargets
class User implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Profile::class => [ProfileRole::USER],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            Profile::class => [ProfileRole::USER],  // Un User ne peut avoir qu'un seul Profile
        ];
    }
}

class Profile implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            User::class => [ProfileRole::USER],
        ];
    }

    public function uniqueTargets(): array
    {
        return [
            User::class => [ProfileRole::USER],  // Un Profile ne peut appartenir qu'à un seul User
        ];
    }
}

// 💥 Exception: Circular unique constraint detected: User → Profile with role "user"
// and Profile → User with the same role.
// This creates a circular dependency. Define the unique constraint in only one direction.
```


**Exceptions :**
- ✅ Les relations entre modèles de même type (ex: `User → User`) ne sont pas concernées



---

### Règle 6 : Auto-attachement - INTERDIT

```php
// ❌ Erreur
$user->attachTo($user, FriendRole::FRIEND);
// Cannot attach a model to itself. App\Models\User 1 cannot be attached to itself.
```

---

### Règle 7 : Ordre de validation

```
1. Auto-attachement
2. Cibles interdites (disallowedTargets)
3. Cibles autorisées (allowedTargets)
4. Circularité
5. Contraintes uniques (uniqueTargets)
```

---

### Règle 8 : Résumé des contraintes

| Contrainte | Priorité |
|------------|----------|
| `disallowedTargets` | 🔴 **Maximale** |
| `uniqueTargets` | 🟡 Élevée |
| `allowedTargets` | 🟢 Normale |

---

### Règle 9 : Bonnes pratiques

**Une direction unique**

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

**Interdire la mauvaise direction**

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

**Ne pas contredire `allowedTargets`**

```php
// ❌ À ÉVITER
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

**Symétrie explicite**

```php
public function becomeFriendWith(User $friend): void
{
    $this->attachTo($friend, FriendRole::FRIEND);
    $friend->attachTo($this, FriendRole::FRIEND);
}
```

---
# 🔍 Inspection CLI

### Commandes

```bash
# Inspecter un modèle spécifique
php artisan rattachments:inspect [App.Models.User] --constraints

# Inspecter plusieurs modèles
php artisan rattachments:inspect [App.Models.User, App.Models.Hospital]

# Afficher uniquement les connexions
php artisan rattachments:inspect [App.Models.User] --connections

# Afficher uniquement les contraintes
php artisan rattachments:inspect [App.Models.User] --constraints

# Utiliser des alias
php artisan ri [App.Models.User] --constraints

# Détecter les circularités entre modèles
php artisan rattachments:circularity [App.Models.User] [App.Models.Profile]

# Détecter les circularités entre plusieurs modèles
php artisan rattachments:circularity [App.Models.User, App.Models.Doctor] [App.Models.Profile, App.Models.Hospital]

# Ignorer les messages "Skipped" (same class, n'implémente pas l'interface)
php artisan rattachments:circularity [App.Models.User, App.Models.Doctor] [App.Models.Profile, App.Models.Hospital] --ignore-skipped

# Utiliser des alias pour la détection de circularité
php artisan rc [App.Models.User] [App.Models.Profile]

# Utiliser l'alias avec le flag --ignore-skipped
php artisan rc [App.Models.User, App.Models.Doctor] [App.Models.Profile, App.Models.Hospital] --ignore-skipped
```

### Exemple de sortie - `rattachments:inspect`

```
🔍 Inspecting rattachments...

═════════════════════════════════════════════════════════════
  🔒 CONSTRAINTS
═════════════════════════════════════════════════════════════

📦 User
   FQCN: App\Models\User
   ✅ Allowed targets:
Hospital                                                     : doctor, staff, admin
Pharmacy                                                     : pharmacist, staff
   🔒 Unique targets:
Hospital                                                     : one-to-one (any role)
Specialty                                                    : one-to-one (roles: primary)
   🚫 Disallowed targets:
Post                                                         : 🚫 Roles: reviewer
   ⚠️  CONFLICT DETECTED: The following targets appear in both allowed and disallowed:
Post                                                         : ⚠️ Allowed: author, editor | Disallowed: reviewer → DISALLOW WINS

═════════════════════════════════════════════════════════════
  🔗 EXISTING CONNECTIONS
═════════════════════════════════════════════════════════════

📊 Found 3 connection types:

User → Hospital                                                : 5x
User → Pharmacy                                                : 3x
User → Post                                                    : 12x

📋 Roles by connection:

   User → Hospital:
doctor                                                       : 3
staff                                                        : 2

   User → Pharmacy:
pharmacist                                                   : 3

   User → Post:
author                                                       : 8
editor                                                       : 4

💡 Possible missing connections (based on constraints):

User → Specialty                                               : ⚠️ Constraint defined but no connections found

✅ Inspection completed
```

### Exemple de sortie - `rattachments:circularity`

#### Sans `--ignore-skipped` (affichage complet)

```
🔄 Checking circularity violations...

📋 Rattachables: App\Models\User, App\Models\Doctor
📋 Targets: App\Models\Profile, App\Models\Hospital

═══════════════════════════════════════════════════════════════════════
  🚨 VIOLATIONS DETECTED
═══════════════════════════════════════════════════════════════════════

   🔄 Circular relationships:

   🔴 Circular relationship: App\Models\User → App\Models\Profile with role "user"
      and App\Models\Profile → App\Models\User with same role.

   🔒 Circular unique constraints:

   🔴 Circular unique constraint: App\Models\User → App\Models\Hospital with role "chief"
      and App\Models\Hospital → App\Models\User with same role.

   ⏭️  Skipped:

   Skipped: App\Models\Doctor → App\Models\Doctor (same class)

⚠️  Total violations found: 2

✅ Circularity check completed
```

#### Avec `--ignore-skipped` (sortie épurée)

```
🔄 Checking circularity violations...

📋 Rattachables: App\Models\User, App\Models\Doctor
📋 Targets: App\Models\Profile, App\Models\Hospital

═══════════════════════════════════════════════════════════════════════
  🚨 VIOLATIONS DETECTED
═══════════════════════════════════════════════════════════════════════

   🔄 Circular relationships:

   🔴 Circular relationship: App\Models\User → App\Models\Profile with role "user"
      and App\Models\Profile → App\Models\User with same role.

   🔒 Circular unique constraints:

   🔴 Circular unique constraint: App\Models\User → App\Models\Hospital with role "chief"
      and App\Models\Hospital → App\Models\User with same role.

ℹ️  Skipped items hidden (use without --ignore-skipped to see them)

⚠️  Total violations found: 2

✅ Circularity check completed
```

### Options de la commande `rattachments:circularity`

| Option | Description |
|--------|-------------|
| `--ignore-skipped` | Masque les éléments "Skipped" (même classe, n'implémente pas l'interface) pour une sortie plus épurée |

### Alias disponibles

| Commande | Alias | Description |
|----------|-------|-------------|
| `rattachments:inspect` | `ri` | Inspection des contraintes et connexions |
| `rattachments:circularity` | `rc` | Détection de circularité |
| `rattachments:circularity` | `rattachments:check-circularity` | Détection de circularité (nom long) |
---

## 📦 Dépendances

- [`andydefer/domain-structures`](https://github.com/andydefer/domain-structures) - Structures de domaine (Value Objects, Records)
- [`andydefer/laravel-repository`](https://github.com/andydefer/laravel-repository) - Pattern Repository
- [`andydefer/laravel-directive`](https://github.com/andydefer/laravel-directive) - Framework CLI
- [`andydefer/php-services`](https://github.com/andydefer/php-services) - Services PHP

---

## 📄 Licence

MIT © [Andy Defer](https://github.com/andydefer)

---

**Construit avec ❤️ pour la communauté Laravel**