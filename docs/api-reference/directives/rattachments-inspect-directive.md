# RattachmentsInspectDirective - Référence Technique

## Description

Directive CLI permettant d'inspecter les contraintes d'attachement et les connexions existantes dans la base de données pour les modèles Eloquent.

## Hiérarchie / Implémentations

```
AbstractDirective (AndyDefer\Directive)
    └── RattachmentsInspectDirective
```

## Rôle principal

Cette directive offre une vue d'ensemble des relations d'attachement définies par les modèles implémentant `RattachmentInterface`. Elle permet de :

- Visualiser les cibles autorisées (`allowedTargets`)
- Visualiser les cibles uniques (`uniqueTargets`)
- Visualiser les cibles interdites (`disallowedTargets`)
- Détecter les conflits entre `allowedTargets` et `disallowedTargets`
- Lister les connexions existantes en base de données
- Suggérer les connexions manquantes basées sur les contraintes définies

## API / Méthodes publiques

### `getSignature(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `string` - La signature de la commande CLI

**Exemple :**
```bash
./bin/app rattachments:inspect [App.Models.User] --constraints
```

---

### `getDescription(): string`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `string` - La description de la commande

---

### `getAliases(): StringTypedCollection`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `StringTypedCollection` - Collection des alias de la commande

**Alias disponibles :**
- `ri`
- `rattachments:list`

---

### `execute(): ExitCode`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `ExitCode` - Code de sortie de l'exécution

**Exceptions :** Aucune (les erreurs sont gérées et affichées)

**Exemple :**
```bash
./bin/app rattachments:inspect [App.Models.User, App.Models.Hospital] --connections --constraints
```

## Cas d'utilisation

### Cas 1 : Inspection des contraintes d'un modèle spécifique

**Problème :** Vous voulez voir quelles cibles un modèle peut attacher et avec quels rôles.

**Solution :** Utiliser la directive avec le flag `--constraints`.

```bash
./bin/app rattachments:inspect [App.Models.User] --constraints
```

**Sortie :**
```
🔍 Inspecting rattachments...

═════════════════════════════════════════════════════════════
  🔒 CONSTRAINTS
═════════════════════════════════════════════════════════════

📦 User
   FQCN: App\Models\User
   ✅ Allowed targets:
TestPost                                                     : doctor, admin
TestTag                                                      : tag, category
   🔒 Unique targets:
TestCategory                                                 : one-to-one
   🚫 Disallowed targets:
TestPost                                                     : 🚫 Roles: staff

✅ Inspection completed
```

### Cas 2 : Vérification des connexions existantes

**Problème :** Vous voulez voir quelles connexions existent réellement en base de données.

**Solution :** Utiliser la directive avec le flag `--connections`.

```bash
./bin/app rattachments:inspect [App.Models.User] --connections
```

**Sortie :**
```
🔍 Inspecting rattachments...

═════════════════════════════════════════════════════════════
  🔗 EXISTING CONNECTIONS
═════════════════════════════════════════════════════════════

📊 Found 2 connection types:

User → Post                                                    : 5x
User → Tag                                                     : 3x

📋 Roles by connection:

   User → Post:
doctor                                                       : 3
admin                                                        : 2

   User → Tag:
tag                                                          : 2
category                                                     : 1

💡 Possible missing connections (based on constraints):

User → Category                                               : ⚠️ Constraint defined but no connections found

✅ Inspection completed
```

### Cas 3 : Détection automatique des modèles

**Problème :** Vous ne savez pas quels modèles implémentent les contraintes d'attachement.

**Solution :** Ne pas spécifier de modèles, la directive les découvre automatiquement.

```bash
./bin/app rattachments:inspect --constraints
```

**Sortie :**
```
🔍 Inspecting rattachments...

No models specified. Discovering models from sources...
No sources specified. Using default: app.Models
Scanning sources: app.Models

═════════════════════════════════════════════════════════════
  🔒 CONSTRAINTS
═════════════════════════════════════════════════════════════

📦 User
   FQCN: App\Models\User
   ✅ Allowed targets:
...

📦 Hospital
   FQCN: App\Models\Hospital
   ✅ Allowed targets:
...

✅ Inspection completed
```

### Cas 4 : Détection des conflits

**Problème :** Un modèle a des conflits entre `allowedTargets` et `disallowedTargets`.

**Solution :** La directive détecte et affiche automatiquement les conflits.

```bash
./bin/app rattachments:inspect [App.Models.User] --constraints
```

**Sortie :**
```
📦 User
   FQCN: App\Models\User
   ✅ Allowed targets:
TestPost                                                     : doctor, admin ⚠️ OVERRIDDEN BY DISALLOW (staff)
   🚫 Disallowed targets:
TestPost                                                     : 🚫 Roles: staff

   ⚠️  CONFLICT DETECTED: The following targets appear in both allowed and disallowed:
TestPost                                                     : ⚠️ Allowed: doctor, admin | Disallowed: staff → DISALLOW WINS
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Classe de modèle inexistante | `Exception` (capturée) | `⚠️ Class not found: {class}` |
| Erreur d'instanciation du modèle | `Exception` (capturée) | `⚠️ Error instantiating {class}: {message}` |
| Table `rattachments` inexistante | Aucune (vérifiée) | `Table "rattachments" does not exist. Run migrations first.` |
| Aucun modèle trouvé | Aucune | `No constrained models found.` |
| Aucune connexion trouvée | Aucune | `No connections found in the database for the specified models.` |

## Intégration

Cette directive s'intègre avec :

- **Laravel Directive** : Framework CLI
- **ConstraintDiscoveryService** : Découverte automatique des modèles
- **ConsoleWriter** : Affichage formaté des données
- **Eloquent** : Requêtes vers la base de données

## Performance

- La découverte des modèles utilise l'AST (via `nikic/php-parser`) et peut être lente sur de grands projets
- Les requêtes SQL sont optimisées avec des `GROUP BY` et des `WHERE IN`
- La vérification de l'existence de la table utilise deux tentatives (information_schema puis SQL direct)
- Pas de mise en cache : chaque exécution est une inspection à jour

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| Laravel 10+ | ✅ Complet |

## Exemple complet

```bash
#!/bin/bash

# Inspecter un modèle spécifique
./bin/app rattachments:inspect [App.Models.User] --constraints

# Inspecter plusieurs modèles
./bin/app rattachments:inspect [App.Models.User, App.Models.Hospital, App.Models.Post]

# Afficher uniquement les connexions
./bin/app rattachments:inspect [App.Models.User] --connections

# Afficher uniquement les contraintes
./bin/app rattachments:inspect [App.Models.User] --constraints

# Afficher les deux (comportement par défaut)
./bin/app rattachments:inspect [App.Models.User]

# Découverte automatique
./bin/app rattachments:inspect --constraints

# Avec sources personnalisées
./bin/app rattachments:inspect [] [app.Models, tests.Fixtures.Models] --constraints

# Utiliser un alias
./bin/app ri [App.Models.User] --constraints
```

## Voir aussi

- `RattachmentInterface` - Interface pour les contraintes d'attachement
- `ConstraintDiscoveryService` - Service de découverte des modèles
- `DirectiveTestingService` - Service de test des directives
- `AbstractDirective` - Classe de base des directives Laravel Directive