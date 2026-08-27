# RattachmentService - Référence Technique

## Description

Service central de gestion des rattachements polymorphiques (rattachments) entre modèles Eloquent. Orchestre les opérations CRUD avec validation des contraintes, gestion des rôles et des métadonnées.

## Hiérarchie / Implémentations

```
RattachmentServiceInterface
    └── RattachmentService
```

**Dépendances :**
- `RattachmentRepository` - Accès aux données
- `RattachmentConstraintsInterface` - Validation des contraintes (optionnelle)

## Rôle principal

Le service orchestre toutes les opérations liées aux rattachements :
- Création, mise à jour et suppression de rattachements
- Validation des contraintes (`RattachmentConstraintsInterface`)
- Gestion des rôles (via `EnumerableInterface`)
- Gestion des métadonnées (`StrictDataObject`)
- Requêtes paginées et filtrées
- Synchronisation en masse

## API

### `attach(Model $rattachable, Model $target, EnumerableInterface $role, array $metadata = []): Model`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à rattacher (ex: User, Doctor) |
| `$target` | `Model` | Modèle cible du rattachement (ex: Hospital, Pharmacy) |
| `$role` | `EnumerableInterface` | Rôle du rattachement (ex: Role::DOCTOR) |
| `$metadata` | `array` | Métadonnées supplémentaires (ex: ['priority' => 'high']) |

**Retourne :** `Model` - L'instance de `Rattachment` créée

**Exceptions :**
- `RuntimeException` - Si le rattachement existe déjà
- `RuntimeException` - Si les contraintes sont violées (si `RattachmentConstraintsInterface` est implémenté)

**Exemple :**
```php
use AndyDefer\LaravelRattachments\Enums\Role;

$attachment = $service->attach(
    $doctor,
    $hospital,
    Role::DOCTOR,
    ['consultation_days' => ['monday', 'wednesday']]
);
```

---

### `attachMultiple(Collection $rattachables, Model $target, EnumerableInterface $role, array $metadata = []): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachables` | `Collection<Model>` | Collection de modèles à rattacher |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle pour tous les rattachements |
| `$metadata` | `array` | Métadonnées communes |

**Retourne :** `Collection<Model>` - Collection des rattachements créés

**Exceptions :** `RuntimeException` - Si un rattachement existe déjà ou contraintes violées

**Exemple :**
```php
$attachments = $service->attachMultiple(
    collect([$doctor1, $doctor2, $doctor3]),
    $hospital,
    Role::DOCTOR
);
```

---

### `attachToMultiple(Model $rattachable, Collection $targets, EnumerableInterface $role, array $metadata = []): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à rattacher |
| `$targets` | `Collection<Model>` | Collection de modèles cibles |
| `$role` | `EnumerableInterface` | Rôle pour tous les rattachements |
| `$metadata` | `array` | Métadonnées communes |

**Retourne :** `Collection<Model>` - Collection des rattachements créés

**Exemple :**
```php
$attachments = $service->attachToMultiple(
    $doctor,
    collect([$hospital1, $hospital2, $hospital3]),
    Role::DOCTOR
);
```

---

### `detach(Model $rattachable, Model $target): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à détacher |
| `$target` | `Model` | Modèle cible |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas

---

### `detachAll(Model $model): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$model` | `Model` | Modèle à détacher de tous ses rattachements (comme rattachable ET target) |

---

### `isAttached(Model $rattachable, Model $target): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à vérifier |
| `$target` | `Model` | Modèle cible |

**Retourne :** `bool` - `true` si le rattachement existe

---

### `getRattachables(Model $target): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |

**Retourne :** `Collection<Model>` - Tous les modèles rattachés à la cible

---

### `getTargets(Model $rattachable): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |

**Retourne :** `Collection<Model>` - Toutes les cibles du modèle

---

### `getRattachablesByRole(Model $target, EnumerableInterface $role): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Rôle à filtrer |

**Retourne :** `Collection<Model>` - Modèles rattachés avec le rôle spécifié

---

### `updateRole(Model $rattachable, Model $target, EnumerableInterface $role): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$target` | `Model` | Modèle cible |
| `$role` | `EnumerableInterface` | Nouveau rôle |

**Exceptions :** `RuntimeException` - Si le rattachement n'existe pas ou contraintes violées

---

### `syncAttachments(Model $rattachable, array $targets): Collection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle à synchroniser |
| `$targets` | `array` | Tableau des cibles avec rôles et métadonnées |

**Format de `$targets` :**
```php
[
    [
        'target' => $hospital,        // Model
        'role' => Role::DOCTOR,       // EnumerableInterface
        'metadata' => ['key' => 'value'], // array (optionnel)
    ],
    // ...
]
```

**Retourne :** `Collection<Model>` - Rattachements créés ou mis à jour

**Fonctionnement :**
1. Crée les nouveaux rattachements
2. Met à jour les rattachements existants
3. Supprime les rattachements non inclus

---

### `mergeMetadata(Model $rattachable, Model $target, array $metadata): void`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$rattachable` | `Model` | Modèle rattaché |
| `$target` | `Model` | Modèle cible |
| `$metadata` | `array` | Métadonnées à fusionner |

**Fonctionnement :** Fusionne les nouvelles métadonnées avec les existantes (via `StrictDataObject::merge()`)

---

## Cas d'utilisation

### Cas 1 : Gestion des médecins dans un hôpital

```php
// Rattacher un médecin à un hôpital
$service->attach($doctor, $hospital, Role::DOCTOR, [
    'consultation_days' => ['monday', 'wednesday', 'friday'],
    'consultation_hours' => '09:00-17:00',
]);

// Récupérer tous les médecins d'un hôpital
$doctors = $service->getRattachablesByRole($hospital, Role::DOCTOR);

// Modifier les horaires
$service->mergeMetadata($doctor, $hospital, [
    'consultation_hours' => '10:00-18:00',
]);

// Détacher un médecin
$service->detach($doctor, $hospital);
```

### Cas 2 : Synchronisation des rattachements

```php
// Synchroniser tous les rattachements d'un doctor en une seule opération
$attachments = $service->syncAttachments($doctor, [
    [
        'target' => $hospital1,
        'role' => Role::DOCTOR,
        'metadata' => ['primary' => true],
    ],
    [
        'target' => $hospital2,
        'role' => Role::DOCTOR,
    ],
    [
        'target' => $pharmacy,
        'role' => Role::PHARMACIST,
    ],
]);

// Les rattachements précédents (ex: hospital3) seront automatiquement supprimés
```

### Cas 3 : Gestion des pharmacies et fournisseurs

```php
// Une pharmacie fournit plusieurs hôpitaux
$service->attachMultiple(
    collect([$pharmacy1, $pharmacy2]),
    $hospital,
    Role::SUPPLIER,
    ['contract_signed_at' => now()->toDateString()]
);

// Récupérer tous les fournisseurs d'un hôpital
$suppliers = $service->getRattachablesByRole($hospital, Role::SUPPLIER);

// Récupérer tous les hôpitaux d'une pharmacie
$hospitals = $service->getTargetsByRole($pharmacy, Role::SUPPLIER);
```

### Cas 4 : Relations patient-médecin

```php
// Un patient est suivi par un médecin
$service->attach($patient, $doctor, Role::PATIENT_OF, [
    'since' => '2024-01-15',
    'referral' => 'general_practitioner',
]);

// Un médecin suit un patient
$service->attach($doctor, $patient, Role::DOCTOR_OF, [
    'notes' => 'Regular check-up every 3 months',
]);

// Récupérer tous les patients d'un médecin
$patients = $service->getRattachablesByRole($doctor, Role::PATIENT_OF);

// Récupérer tous les médecins d'un patient
$doctors = $service->getTargetsByRole($patient, Role::DOCTOR_OF);
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Rattachement déjà existant | `RuntimeException` | `{rattachable} {rattachable_id} is already attached to {target} {target_id}` |
| Rattachement inexistant (detach) | `RuntimeException` | `{rattachable} {rattachable_id} is not attached to {target} {target_id}` |
| Target non autorisé (contraintes) | `RuntimeException` | `{rattachable} cannot be attached to {target}. Allowed targets: {allowed_targets}` |
| Rôle non autorisé (contraintes) | `RuntimeException` | `Role "{role}" is not allowed for {rattachable} -> {target}. Allowed roles: {allowed_roles}` |
| Données `syncAttachments` invalides | `RuntimeException` | `Each target must have "target" and "role" keys` |

## Performance

- **Toutes les opérations CRUD** : O(1) - requêtes SQL uniques
- **`syncAttachments`** : O(n) - une requête pour récupérer les existants + n requêtes pour créer/mettre à jour
- **Optimisations natives** :
  - Utilisation de `exists()` pour les vérifications (pas de chargement de données)
  - Utilisation de `pluck()` pour les collections
  - Eager loading non nécessaire (géré par Eloquent)

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.2+ | ✅ Complet |
| PHP 8.1 | ✅ Complet |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelRattachments\Services\RattachmentService;
use AndyDefer\LaravelRattachments\Enums\Role;

// 1. Configuration du repository dans le service provider
$service = new RattachmentService(
    new RattachmentRepository()
);

// 2. Créer des modèles
$doctor = User::create(['name' => 'Dr. Smith', 'email' => 'smith@hospital.com']);
$hospital = Hospital::create(['name' => 'Central Hospital', 'address' => '123 Main St']);

// 3. Rattacher le docteur à l'hôpital
$attachment = $service->attach(
    $doctor,
    $hospital,
    Role::DOCTOR,
    ['department' => 'Cardiology', 'consultation_fee' => 50000]
);

// 4. Vérifier le rattachement
if ($service->isAttached($doctor, $hospital)) {
    echo "Doctor is attached to hospital\n";
}

// 5. Récupérer tous les docteurs de l'hôpital
$doctors = $service->getRattachablesByRole($hospital, Role::DOCTOR);
foreach ($doctors as $doc) {
    echo $doc->name . "\n";
}

// 6. Mettre à jour le rôle
$service->updateRole($doctor, $hospital, Role::STAFF);

// 7. Mettre à jour les métadonnées
$service->mergeMetadata($doctor, $hospital, [
    'consultation_fee' => 75000,
    'availability' => 'Monday-Friday',
]);

// 8. Synchroniser les rattachements
$service->syncAttachments($doctor, [
    [
        'target' => $hospital,
        'role' => Role::STAFF,
        'metadata' => ['department' => 'Emergency'],
    ],
]);

// 9. Nettoyer
$service->detach($doctor, $hospital);
```

## Voir aussi

- `Rattachment` - Modèle Eloquent du rattachement
- `RattachmentRepository` - Accès aux données
- `RattachmentConstraintsInterface` - Interface pour les contraintes
- `Role` - Énumération des rôles par défaut
- `RattachmentFilterRecord` - DTO de filtrage