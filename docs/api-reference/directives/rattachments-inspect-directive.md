# RattachmentsInspectDirective - Référence Technique

## Description

Directive CLI qui inspecte les contraintes de rattachement (`allowedTargets`, `uniqueTargets`, `disallowedTargets`) et les connexions existantes en base de données pour les modèles spécifiés.

## Hiérarchie / Implémentations

```
AbstractDirective
    └── RattachmentsInspectDirective
```

**Classe parente :** `AndyDefer\Directive\AbstractDirective`

## Rôle principal

Cette directive est un outil d'inspection CLI qui permet aux développeurs de visualiser et de déboguer les relations définies dans leurs modèles. Elle affiche :

1. **Les contraintes** : Ce que chaque modèle peut attacher (`allowedTargets`), les contraintes d'unicité (`uniqueTargets`) et les interdictions (`disallowedTargets`)
2. **Les connexions existantes** : Les relations qui existent réellement en base de données
3. **Les conflits** : Quand un modèle apparaît à la fois dans `allowedTargets` et `disallowedTargets`
4. **Les relations manquantes** : Les contraintes définies mais sans connexion en base

## DETAILS

[Voir la classe RattachmentsInspectDirective](https://github.com/andydefer/laravel-rattachments/blob/main/src/Directives/RattachmentsInspectDirective.php)

## API / Méthodes publiques

### `getSignature(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| - | - | - |

**Retourne :** `string` - La signature de la commande CLI avec ses arguments et options

**Exemple :**
```php
$signature = $directive->getSignature();
// 'rattachments:inspect 
//  {models*}#"List of models to inspect..."
//  {--connections}#"Show existing connections in database"
//  {--constraints}#"Show model constraints only"
//  {--ignore-missing}#"Hide missing connections suggestions"'
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
// 'Inspect rattachments constraints and existing connections for specific models.'
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
// Collection contenant 'ri' et 'rattachments:list'
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
./bin/app rattachments:inspect [App.Models.User, App.Models.Hospital]
```

## Cas d'utilisation

### Cas 1 : Inspection complète d'un modèle

**Problème** : Vérifier toutes les contraintes et connexions d'un modèle.

**Solution** : Utiliser la commande sans flags.

```bash
./bin/app rattachments:inspect [App.Models.User]

# Affiche :
# 🔒 CONSTRAINTS
#   ✅ Allowed targets
#   🔒 Unique targets
#   🚫 Disallowed targets
# 🔗 EXISTING CONNECTIONS
#   📊 Connection types
#   📋 Roles by connection
# 💡 Possible missing connections
```

---

### Cas 2 : Voir uniquement les contraintes

**Problème** : Voir uniquement ce qu'un modèle peut attacher.

**Solution** : Utiliser le flag `--constraints`.

```bash
./bin/app rattachments:inspect [App.Models.User] --constraints

# Affiche uniquement :
# 🔒 CONSTRAINTS
```

---

### Cas 3 : Voir uniquement les connexions

**Problème** : Voir uniquement les relations existantes en base.

**Solution** : Utiliser le flag `--connections`.

```bash
./bin/app rattachments:inspect [App.Models.User] --connections

# Affiche uniquement :
# 🔗 EXISTING CONNECTIONS
```

---

### Cas 4 : Masquer les suggestions de relations manquantes

**Problème** : La section "Possible missing connections" pollue la sortie.

**Solution** : Utiliser le flag `--ignore-missing`.

```bash
./bin/app rattachments:inspect [App.Models.User] --connections --ignore-missing

# Affiche :
# 🔗 EXISTING CONNECTIONS
# ℹ️  Missing connections suggestions hidden
```

---

### Cas 5 : Inspection de plusieurs modèles

**Problème** : Inspecter plusieurs modèles en une seule commande.

**Solution** : Passer plusieurs classes dans la liste.

```bash
./bin/app rattachments:inspect [App.Models.User, App.Models.Hospital, App.Models.Specialty] --constraints
```

## Options de la commande

| Option | Description |
|--------|-------------|
| `--constraints` | Affiche uniquement les contraintes (`allowedTargets`, `uniqueTargets`, `disallowedTargets`) |
| `--connections` | Affiche uniquement les connexions existantes en base |
| `--ignore-missing` | Masque les suggestions de relations manquantes |

**Comportement par défaut :** Sans flags, affiche tout (constraints + connections + missing suggestions).

## Gestion des erreurs

| Situation | Code | Message |
|-----------|------|---------|
| Aucun modèle spécifié | `ExitCode::INVALID_ARGUMENT` | `You must specify at least one model to inspect.` |
| Classe introuvable | (affichée dans la sortie) | `Class not found: {class}` |
| Modèle n'implémente pas l'interface | (affichée dans la sortie) | `{class} does not implement RattachmentInterface` |
| Table `rattachments` inexistante | (affichée dans la sortie) | `Table "rattachments" does not exist. Run migrations first.` |
| Erreur d'instanciation | (affichée dans la sortie) | `Error instantiating {class}: {message}` |

## Sortie détaillée

### Section CONSTRAINTS

```
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
   ⚠️  CONFLICT DETECTED:
Post                                                         : ⚠️ Allowed: author, editor | Disallowed: reviewer → DISALLOW WINS
```

### Section EXISTING CONNECTIONS

```
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
```

### Section Possible missing connections (si `--ignore-missing` absent)

```
💡 Possible missing connections (based on constraints):

User → Specialty                                               : ⚠️ Constraint defined but no connections found
```

### Section avec `--ignore-missing`

```
ℹ️  Missing connections suggestions hidden (use without --ignore-missing to see them)
```

## Intégration

Cette directive s'intègre avec :

- **`RattachmentInterface`** : Utilisée pour lire les contraintes (`allowedTargets`, `uniqueTargets`, `disallowedTargets`)
- **`AbstractDirective`** : Hérite des fonctionnalités de base des directives CLI
- **Table `rattachments`** : Lecture des connexions existantes

## Performance

- **Complexité** : O(n) où n est le nombre de modèles inspectés
- **Requêtes SQL** : 1 requête pour les connexions + 1 requête par type de connexion pour les rôles
- **Mémoire** : Les données sont stockées en mémoire pour l'affichage
- **Recommandation** : Limiter à 10-20 modèles maximum par exécution

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

# Inspection complète de tous les modèles avec masquage des suggestions
./bin/app rattachments:inspect \
    [App.Models.User, App.Models.Hospital, App.Models.Specialty, App.Models.Drug, App.Models.Offer] \
    --connections \
    --constraints \
    --ignore-missing

# Sortie attendue :
# 🔍 Inspecting rattachments...
# 
# ═════════════════════════════════════════════════════════════
#   🔒 CONSTRAINTS
# ═════════════════════════════════════════════════════════════
# 
# 📦 User
#    FQCN: App\Models\User
#    ✅ Allowed targets:
# Hospital                                                     : doctor, admin
# Specialty                                                    : specialist
#    🔒 Unique targets:
# Profile                                                      : one-to-one (any role)
#    🚫 Disallowed targets:
#       (none)
# 
# 📦 Hospital
#    FQCN: App\Models\Hospital
#    ✅ Allowed targets:
# User                                                         : doctor, admin
#    🔒 Unique targets:
# User                                                         : one-to-one (roles: admin)
#    🚫 Disallowed targets:
#       (none)
# 
# 📦 Specialty
#    FQCN: App\Models\Specialty
#    ✅ Allowed targets:
# User                                                         : specialist
# Hospital                                                     : specialty
#    🔒 Unique targets:
#       (none)
#    🚫 Disallowed targets:
#       (none)
# 
# ═════════════════════════════════════════════════════════════
#   🔗 EXISTING CONNECTIONS
# ═════════════════════════════════════════════════════════════
# 
# 📊 Found 2 connection types:
# 
# User → Specialty                                             : 32x
# User → Hospital                                              : 22x
# 
# 📋 Roles by connection:
# 
#    User → Specialty:
# specialist                                                   : 32
# 
#    User → Hospital:
# doctor                                                       : 22
# 
# ℹ️  Missing connections suggestions hidden (use without --ignore-missing to see them)
# 
# ✅ Inspection completed
```

## Voir aussi

- `RattachmentsCircularityDetectorDirective` - Détection des circularités
- `RattachmentInterface::allowedTargets()` - Définition des cibles autorisées
- `RattachmentInterface::uniqueTargets()` - Définition des contraintes uniques
- `RattachmentInterface::disallowedTargets()` - Définition des interdictions
- `AbstractDirective` - Classe de base des directives CLI