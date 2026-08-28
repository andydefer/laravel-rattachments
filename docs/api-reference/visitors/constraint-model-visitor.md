# ConstraintModelVisitor - Référence Technique

## Description

Visiteur PHP Parser qui parcourt l'AST (Abstract Syntax Tree) d'un fichier PHP pour identifier les classes qui implémentent l'interface `RattachmentInterface`.

## Hiérarchie / Implémentations

```
PhpParser\NodeVisitorAbstract
    └── ConstraintModelVisitor
```

## Rôle principal

Ce visiteur est utilisé par le `ConstraintDiscoveryService` pour analyser les fichiers PHP et découvrir automatiquement tous les modèles qui définissent des contraintes d'attachement. Il extrait :

- Le namespace de chaque classe
- Les classes concrètes (non abstraites)
- Les interfaces implémentées
- Les alias d'importation (`use`)

## API / Méthodes publiques

### `enterNode(Node $node): ?int`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$node` | `PhpParser\Node` | Nœud de l'AST en cours de visite |

**Retourne :** `?int` - `null` (aucune modification de la traversée)

**Exceptions :** Aucune

**Exemple :**
```php
$visitor = new ConstraintModelVisitor();
$traverser = new NodeTraverser();
$traverser->addVisitor($visitor);

$ast = $parser->parse(file_get_contents($filePath));
$traverser->traverse($ast);

$models = $visitor->getModels();
```

---

### `getModels(): array`

| Paramètre | Type | Description |
|-----------|------|-------------|
| Aucun | - | - |

**Retourne :** `array<string>` - Liste des FQCN des modèles trouvés

**Exemple :**
```php
$visitor = new ConstraintModelVisitor();
// ... traverser l'AST
$models = $visitor->getModels();
// ['App\Models\User', 'App\Models\Hospital', ...]
```

## Cas d'utilisation

### Cas 1 : Découverte automatique des modèles

**Problème :** Vous avez 50 modèles dans votre application et vous voulez savoir lesquels implémentent `RattachmentInterface`.

**Solution :** Utiliser le visiteur pour scanner tous les fichiers.

```php
use AndyDefer\LaravelRattachments\Services\Visitors\ConstraintModelVisitor;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

$parser = (new ParserFactory())->createForNewestSupportedVersion();
$files = glob(__DIR__ . '/app/Models/*.php');

$visitor = new ConstraintModelVisitor();
$traverser = new NodeTraverser();
$traverser->addVisitor($visitor);

foreach ($files as $file) {
    $ast = $parser->parse(file_get_contents($file));
    if ($ast !== null) {
        $traverser->traverse($ast);
    }
}

$models = $visitor->getModels();
// ['App\Models\User', 'App\Models\Hospital']
```

### Cas 2 : Analyse d'un namespace spécifique

**Problème :** Vous voulez scanner uniquement les modèles d'un domaine spécifique.

**Solution :** Le visiteur capture automatiquement le namespace de chaque classe.

```php
// Le visiteur détecte automatiquement le namespace
$models = $visitor->getModels();
// Seules les classes avec un namespace sont retournées
```

## Fonctionnement interne

### Flux d'exécution

```
1. enterNode(Namespace_)
   └── Enregistre le namespace courant

2. enterNode(Use_)
   └── Enregistre les alias d'importation

3. enterNode(Class_)
   └── Vérifie si la classe implémente l'interface
       └── Résout les alias (use)
       └── Vérifie les interfaces implémentées
       └── Ajoute le FQCN à la liste si l'interface est présente
```

### Gestion des alias

Le visiteur gère correctement les alias d'importation :

```php
// Fichier source
use App\Models\User as UserModel;
use AndyDefer\LaravelRattachments\Contracts\RattachmentInterface;

class Hospital implements RattachmentInterface
{
    // ...
}
```

Le visiteur résout `RattachmentInterface` vers son FQCN complet.

## Gestion des erreurs

| Situation | Comportement |
|-----------|--------------|
| Classe abstraite | Ignorée (non incluse) |
| Namespace non défini | Ignorée (non incluse) |
| Interface non trouvée | Ignorée (non incluse) |
| Alias non résolu | Le nom original est utilisé |

## Intégration

Ce visiteur s'intègre avec :

- **nikic/php-parser** : Analyse AST
- **ConstraintDiscoveryService** : Service de découverte
- **ConstraintModelVisitor** : Peut être combiné avec d'autres visiteurs

## Performance

- La traversée AST est **O(n)** où n est le nombre de nœuds
- Chaque fichier est analysé individuellement
- Pas de mise en cache : chaque exécution analyse les fichiers
- Pour de grands projets, envisager un cache des résultats

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

use AndyDefer\LaravelRattachments\Services\Visitors\ConstraintModelVisitor;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

// 1. Créer le parser
$parser = (new ParserFactory())->createForNewestSupportedVersion();

// 2. Créer le visiteur
$visitor = new ConstraintModelVisitor();

// 3. Créer le traverser
$traverser = new NodeTraverser();
$traverser->addVisitor($visitor);

// 4. Analyser les fichiers
$files = [
    __DIR__ . '/app/Models/User.php',
    __DIR__ . '/app/Models/Hospital.php',
    __DIR__ . '/app/Models/Doctor.php',
];

foreach ($files as $file) {
    if (! file_exists($file)) {
        continue;
    }

    $ast = $parser->parse(file_get_contents($file));
    if ($ast !== null) {
        $traverser->traverse($ast);
    }
}

// 5. Récupérer les résultats
$models = $visitor->getModels();

echo "Models implementing RattachmentInterface:\n";
foreach ($models as $model) {
    echo "  - {$model}\n";
}
```

## Voir aussi

- `ConstraintDiscoveryService` - Service utilisant ce visiteur
- `RattachmentInterface` - Interface détectée
- `PhpParser\NodeVisitorAbstract` - Classe de base du visiteur
- `PhpParser\Node` - Nœuds de l'AST analysés