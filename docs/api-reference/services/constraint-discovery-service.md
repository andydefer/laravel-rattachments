# ConstraintDiscoveryService - Référence Technique

## Description

Service de découverte automatique des modèles qui implémentent l'interface `RattachmentConstraintsInterface` en scannant les fichiers PHP d'un projet.

## Hiérarchie / Implémentations

```
ConstraintDiscoveryServiceInterface
    └── ConstraintDiscoveryService
```

## Rôle principal

Ce service analyse les répertoires sources d'un projet pour identifier automatiquement tous les modèles Eloquent qui définissent des contraintes d'attachement. Il utilise :

- L'analyse AST via `nikic/php-parser` pour détecter les classes
- Le système de fichiers pour parcourir les répertoires
- La réflexion PHP pour valider les classes trouvées

## API / Méthodes publiques

### `discoverConstrainedModels(array $sources): array`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$sources` | `array<string>` | Liste des chemins ou namespaces à scanner |

**Retourne :** `array<string, array{allowedTargets: array, uniqueTargets: array}>` - Dictionnaire des modèles trouvés avec leurs contraintes

**Exceptions :** Aucune (les erreurs sont capturées et ignorées)

**Exemple :**
```php
$service = new ConstraintDiscoveryService($fileSystem, $parser);
$models = $service->discoverConstrainedModels(['app.Models', 'tests.Fixtures.Models']);

foreach ($models as $class => $constraints) {
    echo $class . "\n";
    print_r($constraints['allowedTargets']);
    print_r($constraints['uniqueTargets']);
}
```

## Cas d'utilisation

### Cas 1 : Découverte des modèles d'une application

**Problème :** Vous voulez trouver tous les modèles qui définissent des contraintes d'attachement dans votre application.

**Solution :** Scanner le namespace `app.Models`.

```php
$models = $service->discoverConstrainedModels(['app.Models']);
// Retourne : ['App\Models\User' => [...], 'App\Models\Hospital' => [...]]
```

### Cas 2 : Découverte dans plusieurs sources

**Problème :** Vous avez des modèles dans plusieurs répertoires (app, modules, tests).

**Solution :** Passer plusieurs sources.

```php
$sources = [
    'app.Models',
    'modules/UserManager/src/Models',
    'tests/Fixtures/Models',
];

$models = $service->discoverConstrainedModels($sources);
```

### Cas 3 : Utilisation des chemins relatifs

**Problème :** Vous voulez scanner un répertoire en dehors du projet racine.

**Solution :** Utiliser le préfixe `%` pour remonter dans l'arborescence.

```php
// % = remonte d'un niveau, %% = remonte de deux niveaux
$models = $service->discoverConstrainedModels(['%vendor/package/src/Models']);
```

## Fonctionnement interne

### Flux d'exécution

```
1. discoverConstrainedModels($sources)
   ├── Pour chaque source
   │   ├── resolvePath() → Convertir en chemin absolu
   │   ├── scanDirectory() → Parcourir récursivement
   │   └── extractModelsFromFile() → Analyser chaque fichier
   ├── array_unique() → Supprimer les doublons
   ├── Pour chaque modèle trouvé
   │   ├── class_exists() → Vérifier l'existence
   │   ├── ReflectionClass → Vérifier l'interface
   │   └── Instancier pour récupérer les contraintes
   └── Retourner les résultats
```

### Résolution des chemins

| Entrée | Résultat |
|--------|----------|
| `app.Models` | `/project/root/app/Models` |
| `tests.Fixtures.Models` | `/project/root/tests/Fixtures/Models` |
| `%vendor/package/src` | `/project/root/../vendor/package/src` |
| `%%modules/User` | `/project/root/../../modules/User` |

### Parcours des répertoires

- Profondeur maximale : 4 niveaux (constante `MAX_SCAN_DEPTH`)
- Fichiers PHP uniquement (extension `.php`)
- Sous-répertoires explorés récursivement

## Gestion des erreurs

| Situation | Comportement |
|-----------|--------------|
| Répertoire inexistant | Ignoré silencieusement |
| Fichier PHP invalide | Ignoré silencieusement |
| Erreur de parsing AST | Capturée et ignorée |
| Classe non trouvée | Ignorée |
| Erreur d'instanciation | Capturée et ignorée |

## Intégration

Ce service s'intègre avec :

- **FileSystemInterface** (`AndyDefer\PhpServices`) - Opérations sur les fichiers
- **nikic/php-parser** - Analyse AST
- **ConstraintModelVisitor** - Visiteur personnalisé
- **RattachmentConstraintsInterface** - Interface recherchée

## Performance

- Chaque fichier est parsé individuellement
- La profondeur de scan est limitée à 4 niveaux
- Les résultats ne sont pas mis en cache
- Pour de grands projets, envisager un cache des résultats

### Complexité

- **Temps** : O(n × m) où n = nombre de fichiers, m = taille moyenne des fichiers
- **Mémoire** : O(k) où k = nombre de classes trouvées

## Compatibilité

| Version | Support |
|---------|---------|
| PHP 8.1+ | ✅ Complet |
| PHP 8.0 | ✅ Complet |
| nikic/php-parser 4.x+ | ✅ Complet |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\LaravelRattachments\Services\ConstraintDiscoveryService;
use AndyDefer\PhpServices\Services\FileSystemService;
use PhpParser\ParserFactory;

// 1. Créer les dépendances
$fileSystem = new FileSystemService();
$parser = (new ParserFactory())->createForNewestSupportedVersion();

// 2. Créer le service
$service = new ConstraintDiscoveryService($fileSystem, $parser);

// 3. Découvrir les modèles
$sources = [
    'app.Models',
    'tests.Fixtures.Models',
    '%vendor/package/src/Models',
];

$models = $service->discoverConstrainedModels($sources);

// 4. Afficher les résultats
echo "Found " . count($models) . " constrained models:\n";

foreach ($models as $className => $constraints) {
    echo "\n📦 " . $className . "\n";
    echo "   Allowed targets: " . count($constraints['allowedTargets']) . "\n";
    echo "   Unique targets: " . count($constraints['uniqueTargets']) . "\n";

    foreach ($constraints['allowedTargets'] as $target => $roles) {
        $targetName = basename(str_replace('\\', '/', $target));
        $roleNames = array_map(fn($r) => $r->getValue(), $roles);
        echo "      {$targetName}: " . implode(', ', $roleNames) . "\n";
    }
}
```

## Voir aussi

- `ConstraintModelVisitor` - Visiteur AST utilisé
- `RattachmentConstraintsInterface` - Interface recherchée
- `FileSystemInterface` - Service de fichiers
- `Paths` - Utilitaire de chemins (Laravel Directive)