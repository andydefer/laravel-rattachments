# RattachmentsCircularityDetectorDirective - Référence Technique

## Description

Directive CLI qui détecte les violations de circularité entre les modèles rattachables (`rattachables`) et les modèles cibles (`targets`). Elle analyse les contraintes `allowedTargets()` et `uniqueTargets()` pour identifier les relations circulaires interdites.

## Hiérarchie / Implémentations

```
AbstractDirective
    └── RattachmentsCircularityDetectorDirective
```

**Classe parente :** `AndyDefer\Directive\AbstractDirective`

## Rôle principal

Cette directive est un outil d'inspection CLI qui permet aux développeurs de détecter proactivement les circularités dans leur configuration de contraintes avant qu'elles ne causent des exceptions lors de l'exécution.

Elle compare systématiquement chaque paire `rattachable → target` pour vérifier :

1. **Circularité dans `allowedTargets`** : Si A autorise B avec un rôle, et B autorise A avec le même rôle.
2. **Circularité dans `uniqueTargets`** : Si A a une contrainte unique sur B avec un rôle, et B a une contrainte unique sur A avec le même rôle.

## DETAILS

[Voir la classe RattachmentsCircularityDetectorDirective](https://github.com/andydefer/laravel-rattachments/blob/main/src/Directives/RattachmentsCircularityDetectorDirective.php)

## API / Méthodes publiques

### `getSignature(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | - |

**Retourne :** `string` - La signature de la commande CLI avec ses arguments et options

**Exemple :**
```php
$signature = $directive->getSignature();
// 'rattachments:circularity 
//  {rattachables*}#"List of rattachable models..."
//  {targets*}#"List of target models..."
//  {--ignore-skipped}#"Do not display skipped items..."'
```

---

### `getDescription(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | - |

**Retourne :** `string` - La description de la commande

**Exemple :**
```php
$description = $directive->getDescription();
// 'Detect circularity violations between rattachables and targets.'
```

---

### `getAliases(): StringTypedCollection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | - |

**Retourne :** `StringTypedCollection` - Collection des alias de la commande

**Exemple :**
```php
$aliases = $directive->getAliases();
// Collection contenant 'rc' et 'rattachments:check-circularity'
```

---

### `execute(): ExitCode`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | - |

**Retourne :** `ExitCode` - Code de sortie de la commande

| Code | Signification |
|------|---------------|
| `ExitCode::SUCCESS` | Exécution réussie (0) |
| `ExitCode::INVALID_ARGUMENT` | Arguments manquants (2) |

**Exemple :**
```bash
./bin/app rattachments:circularity [App.Models.User] [App.Models.Profile]
```

## Cas d'utilisation

### Cas 1 : Détection de circularité entre utilisateur et hôpital

**Problème** : Un utilisateur autorise les hôpitaux avec le rôle `CHIEF`, et un hôpital autorise les utilisateurs avec le même rôle `CHIEF`. Cette configuration crée une circularité interdite.

**Solution** : La directive détecte et signale la violation.

```php
// Modèle User
class User implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            Hospital::class => [Role::CHIEF, Role::DOCTOR],
        ];
    }
}

// Modèle Hospital
class Hospital implements RattachmentInterface
{
    public function allowedTargets(): array
    {
        return [
            User::class => [Role::CHIEF, Role::ADMIN],
        ];
    }
}
```

```bash
./bin/app rattachments:circularity [App.Models.User] [App.Models.Hospital]

# 🔴 Circular relationship: App\Models\User → App\Models\Hospital with role "chief"
# and App\Models\Hospital → App\Models\User with same role.
# ⚠️ Total violations found: 1
```

---

### Cas 2 : Détection de circularité unique

**Problème** : Un utilisateur a une contrainte unique sur les hôpitaux avec le rôle `CHIEF`, et un hôpital a une contrainte unique sur les utilisateurs avec le même rôle. Cela crée une dépendance circulaire.

**Solution** : La directive détecte et signale la violation.

```php
// Modèle User
class User implements RattachmentInterface
{
    public function uniqueTargets(): array
    {
        return [
            Hospital::class => [Role::CHIEF],
        ];
    }
}

// Modèle Hospital
class Hospital implements RattachmentInterface
{
    public function uniqueTargets(): array
    {
        return [
            User::class => [Role::CHIEF],
        ];
    }
}
```

```bash
./bin/app rattachments:circularity [App.Models.User] [App.Models.Hospital]

# 🔴 Circular unique constraint: App\Models\User → App\Models\Hospital with role "chief"
# and App\Models\Hospital → App\Models\User with same role.
# ⚠️ Total violations found: 1
```

---

### Cas 3 : Ignorer les skips avec `--ignore-skipped`

**Problème** : Lorsqu'on inspecte plusieurs modèles, la sortie est polluée par de nombreux messages "Skipped" (même classe, n'implémente pas l'interface).

**Solution** : Utiliser le flag `--ignore-skipped` pour masquer ces messages.

```bash
./bin/app rattachments:circularity [App.Models.User, App.Models.Doctor] [App.Models.User, App.Models.Profile] --ignore-skipped

# 🔄 Circular relationships:
# 🔴 Circular relationship: User → Profile with role "user" and Profile → User with same role.
# ⚠️ Total violations found: 1
# ℹ️  Skipped items hidden (use without --ignore-skipped to see them)
```

---

### Cas 4 : Inspection de plusieurs modèles

**Problème** : Vérifier toutes les circularités entre plusieurs types de modèles.

**Solution** : Passer plusieurs classes dans les listes.

```bash
./bin/app rattachments:circularity [App.Models.User, App.Models.Doctor] [App.Models.Hospital, App.Models.Specialty]

# 🔄 Circular relationships:
# 🔴 Circular relationship: User → Hospital with role "chief" and Hospital → User with same role.
# 🔴 Circular relationship: Doctor → Specialty with role "primary" and Specialty → Doctor with same role.
# ⚠️ Total violations found: 2
```

## Gestion des erreurs

| Situation | Code | Message |
|-----------|------|---------|
| Aucun rattachable spécifié | `ExitCode::INVALID_ARGUMENT` | `You must specify both rattachables and targets.` |
| Aucun target spécifié | `ExitCode::INVALID_ARGUMENT` | `You must specify both rattachables and targets.` |
| Classe introuvable | (affichée dans la sortie) | `Class not found: {class}` |
| Modèle n'implémente pas l'interface | (affichée dans la sortie) | `{class} does not implement RattachmentInterface. Skipped.` |
| Même classe en rattachable et target | (affichée dans la sortie) | `Skipped: {class} → {class} (same class)` |

## Intégration

Cette directive s'intègre avec :

- **`RattachmentInterface`** : Utilisée pour vérifier que les modèles implémentent l'interface
- **`AbstractDirective`** : Hérite des fonctionnalités de base des directives CLI
- **`ConstraintValidator`** : Les règles de circularité détectées correspondent à celles validées par le `ConstraintValidator`

La directive est conçue pour être utilisée en développement ou en CI pour détecter les problèmes de configuration avant qu'ils ne causent des exceptions en production.

## Performance

- **Complexité** : O(n × m) où n est le nombre de rattachables et m le nombre de targets
- **Mémoire** : Les violations sont stockées dans une collection en mémoire
- **Recommandation** : Limiter le nombre de modèles inspectés en une seule exécution (10-20 modèles maximum)

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.2+ | ✅ Complet |
| PHP 8.3+ | ✅ Complet |
| Laravel 12.x | ✅ Complet |
| Laravel 13.x | ✅ Complet |
| Laravel 14.x | ✅ Complet |
| Laravel 15.x | ✅ Complet |

## Exemple complet

```bash
#!/bin/bash

# Exemple d'exécution complète avec ignore-skipped
./bin/app rattachments:circularity \
    [App.Models.User, App.Models.Admin] \
    [App.Models.Profile, App.Models.Hospital, App.Models.Specialty] \
    --ignore-skipped

# Sortie attendue :
# 🔄 Checking circularity violations...
# 
# 📋 Rattachables: App\Models\User, App\Models\Admin
# 📋 Targets: App\Models\Profile, App\Models\Hospital, App\Models\Specialty
# 
# ═══════════════════════════════════════════════════════════════════════
#   🚨 VIOLATIONS DETECTED
# ═══════════════════════════════════════════════════════════════════════
# 
#    🔄 Circular relationships:
# 
#    🔴 Circular relationship: App\Models\User → App\Models\Profile with role "user"
#       and App\Models\Profile → App\Models\User with same role.
# 
#    🔒 Circular unique constraints:
# 
#    🔴 Circular unique constraint: App\Models\User → App\Models\Hospital with role "chief"
#       and App\Models\Hospital → App\Models\User with same role.
# 
#    ❌ Errors:
# 
#    Class not found: App\Models\NonExistent
# 
# ℹ️  Skipped items hidden (use without --ignore-skipped to see them)
# 
# ⚠️  Total violations found: 2
# 
# ✅ Circularity check completed
```

## Voir aussi

- `ConstraintValidator` - Validation des contraintes
- `RattachmentInterface::allowedTargets()` - Définition des cibles autorisées
- `RattachmentInterface::uniqueTargets()` - Définition des contraintes uniques
- `RattachmentsInspectDirective` - Inspection générale des contraintes
- `AbstractDirective` - Classe de base des directives CLI