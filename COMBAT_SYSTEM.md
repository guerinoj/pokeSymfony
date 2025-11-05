# 🥊 Système de Combat Pokémon - Guide Symfony

Ce document explique le fonctionnement du système de combat Pokémon implémenté dans cette application Symfony, en mettant l'accent sur les bonnes pratiques et les concepts clés du framework.

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Architecture MVC](#architecture-mvc)
- [Service Layer Pattern](#service-layer-pattern)
- [Routing et Controllers](#routing-et-controllers)
- [Injection de Dépendances](#injection-de-dépendances)
- [Gestion des Requêtes](#gestion-des-requêtes)
- [Templates Twig](#templates-twig)
- [Logique Métier](#logique-métier)
- [Gestion d'Erreurs](#gestion-derreurs)
- [Bonnes Pratiques](#bonnes-pratiques)

## 🎯 Vue d'ensemble

Le système de combat permet à deux Pokémon de s'affronter en utilisant leurs statistiques récupérées via l'API PokéAPI. Il respecte les principes SOLID et l'architecture Symfony.

### Flux de l'application :
1. **Sélection** → L'utilisateur choisit deux Pokémon
2. **Combat** → Le système calcule automatiquement le résultat
3. **Affichage** → Les résultats sont présentés de manière détaillée

## 🏗️ Architecture MVC

### Model (Service)
```php
// src/Service/PokemonService.php
class PokemonService
{
    public function battle(string $pokemon1Name, string $pokemon2Name): array
    {
        // Logique métier du combat
    }
}
```

### View (Templates)
```twig
{# templates/pokemon/battle_result.html.twig #}
{% extends 'base.html.twig' %}
{% block body %}
    <!-- Affichage des résultats -->
{% endblock %}
```

### Controller
```php
// src/Controller/PokemonController.php
#[Route('/pokemon/battle/fight', name: 'pokemon.battle.fight')]
public function battleFight(Request $request): Response
{
    // Orchestration entre Model et View
}
```

## 🔧 Service Layer Pattern

### Principe
Le **Service Layer Pattern** sépare la logique métier du contrôleur. Dans notre cas, `PokemonService` encapsule toute la logique de combat, assisté par la classe `BattleState` pour l'organisation des données.

### Avantages
- **Réutilisabilité** : Le service peut être utilisé dans plusieurs contrôleurs
- **Testabilité** : Logique métier isolée et facilement testable
- **Maintenabilité** : Séparation claire des responsabilités
- **Respect du DRY** : Élimination des duplications de code

### Architecture Améliorée

#### Classe BattleState
```php
// src/Service/BattleState.php
class BattleState
{
    public function __construct(
        public array $pokemon1,
        public array $pokemon2,
        public array $pokemon1Stats,
        public array $pokemon2Stats,
        public int $pokemon1CurrentHp,
        public int $pokemon2CurrentHp,
        public array $battleLog = [],
        public int $turn = 1
    ) {}

    public function isFinished(): bool
    {
        return $this->pokemon1CurrentHp <= 0 || $this->pokemon2CurrentHp <= 0;
    }

    public function getWinner(): ?array
    {
        if ($this->pokemon1CurrentHp > 0) return $this->pokemon1;
        if ($this->pokemon2CurrentHp > 0) return $this->pokemon2;
        return null;
    }
}
```

#### Service Principal Refactorisé
```php
class PokemonService
{
    public function battle(string $pokemon1Name, string $pokemon2Name): array
    {
        // 1. Récupération des données
        $pokemon1 = $this->getByName($pokemon1Name);
        $pokemon2 = $this->getByName($pokemon2Name);

        // 2. Extraction des statistiques (méthode réutilisable)
        $pokemon1Stats = $this->extractPokemonStats($pokemon1);
        $pokemon2Stats = $this->extractPokemonStats($pokemon2);

        // 3. Création de l'état de combat
        $battle = new BattleState(
            pokemon1: $pokemon1,
            pokemon2: $pokemon2,
            pokemon1Stats: $pokemon1Stats,
            pokemon2Stats: $pokemon2Stats,
            pokemon1CurrentHp: $pokemon1Stats['hp'],
            pokemon2CurrentHp: $pokemon2Stats['hp']
        );

        // 4. Déroulement du combat
        // ... (voir code complet)

        return $battle->getBattleResult();
    }

    // Méthodes privées pour éliminer la duplication
    private function extractPokemonStats(array $pokemon): array
    private function determineBattleOrder(array $stats1, array $stats2, BattleState $battle): bool
    private function processTurn(BattleState $battle, bool $pokemon1Attacks): void
}
```

## 🛣️ Routing et Controllers

### Définition des Routes

```php
#[Route('/pokemon/battle/select', name: 'pokemon.battle.select')]
public function battleSelect(Request $request): Response

#[Route('/pokemon/battle/fight', name: 'pokemon.battle.fight')]
public function battleFight(Request $request): Response
```

### Conventions Symfony
- **Nom des routes** : `entity.action` (ex: `pokemon.battle.select`)
- **Méthodes** : Verbes descriptifs (`battleSelect`, `battleFight`)
- **Paramètres** : Injection de `Request` pour accéder aux données GET/POST

### Responsabilités du Controller

1. **Validation des entrées**
```php
if (!$pokemon1 || !$pokemon2) {
    $this->addFlash('error', 'Veuillez sélectionner deux Pokémon pour le combat.');
    return $this->redirectToRoute('pokemon.battle.select');
}
```

2. **Délégation au Service**
```php
$battleResult = $this->pokemonService->battle($pokemon1, $pokemon2);
```

3. **Rendu de la réponse**
```php
return $this->render('pokemon/battle_result.html.twig', [
    'battleResult' => $battleResult,
]);
```

## 💉 Injection de Dépendances

### Dans le Service
```php
class PokemonService
{
    public function __construct(
        private HttpClientInterface $httpClient  // Injection automatique
    ) {}
}
```

### Dans le Controller
```php
final class PokemonController extends AbstractController
{
    public function __construct(
        private PokemonService $pokemonService  // Injection automatique
    ) {}
}
```

### Avantages
- **Découplage** : Classes indépendantes de leurs dépendances
- **Testabilité** : Injection de mocks pour les tests
- **Flexibilité** : Changement d'implémentation sans modification du code

## 📥 Gestion des Requêtes

### Récupération des Paramètres GET

```php
public function battleFight(Request $request): Response
{
    $pokemon1 = $request->query->get('pokemon1');
    $pokemon2 = $request->query->get('pokemon2');
    
    // Validation
    if (!$pokemon1 || !$pokemon2) {
        // Gestion d'erreur
    }
}
```

### Pagination
```php
public function battleSelect(Request $request): Response
{
    $page = max(1, $request->query->getInt('page', 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $pokemonData = $this->pokemonService->getAll($limit, $offset);
}
```

## 🎨 Templates Twig

### Héritage de Templates
```twig
{# templates/pokemon/battle_result.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}
    Résultat du Combat - {{ battleResult.pokemon1.name|title }} vs {{ battleResult.pokemon2.name|title }}
{% endblock %}

{% block body %}
    <!-- Contenu spécifique -->
{% endblock %}
```

### Filtres Twig
```twig
{# Capitalisation des noms #}
{{ pokemon.name|title }}

{# Calcul de pourcentage #}
{% set hpPercentage = (battleResult.pokemon1CurrentHp / battleResult.pokemon1Stats.hp) * 100 %}
```

### Logique Conditionnelle
```twig
{% if battleResult.winner and battleResult.winner.name == battleResult.pokemon1.name %}
    <span class="badge bg-success">🏆 VAINQUEUR</span>
{% else %}
    <span class="badge bg-danger">💀 KO</span>
{% endif %}
```

### Boucles
```twig
{% for logEntry in battleResult.battleLog %}
    <div class="mb-2 p-2">{{ logEntry }}</div>
{% endfor %}
```

## 🧠 Logique Métier

### Architecture Refactorisée et Améliorée

#### Extraction des Statistiques (Élimination DRY)
```php
private function extractPokemonStats(array $pokemon): array
{
    return [
        'hp' => $pokemon['stats'][0]['base_stat'],
        'attack' => $pokemon['stats'][1]['base_stat'],
        'defense' => $pokemon['stats'][2]['base_stat'],
        'speed' => $pokemon['stats'][5]['base_stat'],
    ];
}
```

#### Détermination de l'Ordre d'Attaque (Simplifiée)
```php
private function determineBattleOrder(array $pokemon1Stats, array $pokemon2Stats, BattleState $battle): bool
{
    if ($pokemon1Stats['speed'] > $pokemon2Stats['speed']) {
        $battle->addLogEntry("Ordre d'attaque déterminé par la vitesse : " . 
                           ucfirst($battle->pokemon1['name']) . " attaque en premier !");
        return true;
    } elseif ($pokemon2Stats['speed'] > $pokemon1Stats['speed']) {
        $battle->addLogEntry("Ordre d'attaque déterminé par la vitesse : " . 
                           ucfirst($battle->pokemon2['name']) . " attaque en premier !");
        return false;
    } else {
        // Tirage aléatoire en cas d'égalité
        $pokemon1First = rand(0, 1) === 1;
        $firstAttacker = $pokemon1First ? $battle->pokemon1['name'] : $battle->pokemon2['name'];
        $battle->addLogEntry("Égalité de vitesse ! Tirage au sort : " . 
                           ucfirst($firstAttacker) . " attaque en premier !");
        return $pokemon1First;
    }
}
```

#### Traitement d'un Tour de Combat (Logique Centralisée)
```php
private function processTurn(BattleState $battle, bool $pokemon1Attacks): void
{
    // Définition dynamique de l'attaquant et du défenseur
    if ($pokemon1Attacks) {
        $attacker = $battle->pokemon1;
        $defender = $battle->pokemon2;
        $attackerStats = $battle->pokemon1Stats;
        $defenderStats = $battle->pokemon2Stats;
        $defenderHp = &$battle->pokemon2CurrentHp; // Référence pour modification
    } else {
        $attacker = $battle->pokemon2;
        $defender = $battle->pokemon1;
        $attackerStats = $battle->pokemon2Stats;
        $defenderStats = $battle->pokemon1Stats;
        $defenderHp = &$battle->pokemon1CurrentHp; // Référence pour modification
    }

    // Calcul et application des dégâts
    $damage = $this->calculateDamage($attackerStats['attack'], $defenderStats['defense']);
    $defenderHp -= $damage;
    $defenderHp = max(0, $defenderHp);

    // Messages du journal
    $battle->addLogEntry(ucfirst($attacker['name']) . " attaque " . 
                        ucfirst($defender['name']) . " et inflige $damage dégâts !");
    $battle->addLogEntry(ucfirst($defender['name']) . " : $defenderHp/" . 
                        $defenderStats['hp'] . " PV");

    // Vérification KO
    if ($defenderHp <= 0) {
        $battle->addLogEntry(ucfirst($defender['name']) . " est KO !");
        $battle->addLogEntry("🏆 " . ucfirst($attacker['name']) . " remporte le combat !");
    }
}
```

### Calcul des Dégâts (Inchangé)
```php
private function calculateDamage(int $attack, int $defense): int
{
    // Formule de base
    $baseDamage = ($attack / max(1, $defense)) * 10;
    
    // Ajout de variabilité
    $variance = mt_rand(85, 115) / 100;
    $finalDamage = $baseDamage * $variance;
    
    // Garantie d'au moins 1 dégât
    return max(1, (int)round($finalDamage));
}
```

### Boucle de Combat Simplifiée
```php
while (!$battle->isFinished() && !$battle->isTooLong()) {
    $battle->addLogEntry("--- Tour {$battle->turn} ---");

    if ($pokemon1AttacksFirst) {
        $this->processTurn($battle, true);  // Pokémon 1 attaque
        if (!$battle->isFinished()) {
            $this->processTurn($battle, false); // Pokémon 2 attaque
        }
    } else {
        $this->processTurn($battle, false); // Pokémon 2 attaque
        if (!$battle->isFinished()) {
            $this->processTurn($battle, true);  // Pokémon 1 attaque
        }
    }

    $battle->nextTurn();
}
```

## ⚠️ Gestion d'Erreurs

### Validation des Données
```php
if (!$pokemon1 || !$pokemon2) {
    $this->addFlash('error', 'Veuillez sélectionner deux Pokémon pour le combat.');
    return $this->redirectToRoute('pokemon.battle.select');
}

if ($pokemon1 === $pokemon2) {
    $this->addFlash('error', 'Un Pokémon ne peut pas se battre contre lui-même !');
    return $this->redirectToRoute('pokemon.battle.select');
}
```

### Try-Catch pour les Erreurs API
```php
try {
    $battleResult = $this->pokemonService->battle($pokemon1, $pokemon2);
    return $this->render('pokemon/battle_result.html.twig', [
        'battleResult' => $battleResult,
    ]);
} catch (\Exception $e) {
    $this->addFlash('error', 'Erreur lors du combat : ' . $e->getMessage());
    return $this->redirectToRoute('pokemon.battle.select');
}
```

### Affichage des Messages Flash
```twig
{% for message in app.flashes('error') %}
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
{% endfor %}
```

## 📊 Variables Dynamiques en PHP → Simplification avec BattleState

### ❌ Ancienne Approche (Complexe pour Débutants)
```php
// Variables dynamiques difficiles à comprendre
$firstAttacker = 'pokemon1';
$secondAttacker = 'pokemon2';

// Accès dynamique aux données
$damage = $this->calculateDamage(
    ${$firstAttacker . 'Stats'}['attack'],    // $pokemon1Stats['attack']
    ${$secondAttacker . 'Stats'}['defense']   // $pokemon2Stats['defense']
);

// Modification dynamique des PV
${$secondAttacker . 'CurrentHp'} -= $damage; // $pokemon2CurrentHp -= $damage
```

### ✅ Nouvelle Approche (Simple et Claire)
```php
// Utilisation de références et de conditions simples
private function processTurn(BattleState $battle, bool $pokemon1Attacks): void
{
    if ($pokemon1Attacks) {
        // Pokémon 1 attaque Pokémon 2
        $attacker = $battle->pokemon1;
        $defender = $battle->pokemon2;
        $attackerStats = $battle->pokemon1Stats;
        $defenderStats = $battle->pokemon2Stats;
        $defenderHp = &$battle->pokemon2CurrentHp; // Référence claire
    } else {
        // Pokémon 2 attaque Pokémon 1
        $attacker = $battle->pokemon2;
        $defender = $battle->pokemon1;
        $attackerStats = $battle->pokemon2Stats;
        $defenderStats = $battle->pokemon1Stats;
        $defenderHp = &$battle->pokemon1CurrentHp; // Référence claire
    }

    // Code de combat utilisant les variables locales claires
    $damage = $this->calculateDamage($attackerStats['attack'], $defenderStats['defense']);
    $defenderHp -= $damage; // Modification via référence
}
```

### Avantages de la Nouvelle Approche
- **✅ Lisibilité** : Code plus facile à comprendre pour les débutants
- **✅ Débogage** : Variables nommées explicitement
- **✅ Maintenabilité** : Logique centralisée dans une méthode
- **✅ Évite la magie** : Pas de variables dynamiques complexes

## ✅ Bonnes Pratiques Appliquées

### 1. **Single Responsibility Principle**
- Chaque classe a une responsabilité unique
- `PokemonService` → Logique métier Pokémon
- `BattleState` → Gestion de l'état du combat
- `PokemonController` → Gestion des requêtes HTTP

### 2. **DRY (Don't Repeat Yourself)**
- ✅ Méthode `extractPokemonStats()` pour éviter la duplication
- ✅ Méthode `processTurn()` pour centraliser la logique d'attaque
- ✅ Classe `BattleState` pour encapsuler les données

### 3. **Dependency Injection**
- Injection automatique des dépendances
- Code découplé et testable

### 4. **Naming Conventions**
- Routes nommées : `pokemon.battle.select`
- Méthodes descriptives : `battleSelect()`, `calculateDamage()`, `processTurn()`
- Variables explicites : `$pokemon1CurrentHp`, `$defenderHp`

### 5. **Error Handling**
- Validation des entrées utilisateur
- Messages d'erreur informatifs
- Redirections appropriées
- Protection contre les boucles infinies

### 6. **Code Organization**
- Méthodes privées pour la logique interne
- Séparation claire des responsabilités
- Code lisible et bien commenté

### 7. **Object-Oriented Design**
- Encapsulation des données dans `BattleState`
- Méthodes utilitaires (`isFinished()`, `getWinner()`)
- État cohérent et méthodes associées

### 8. **Template Organisation**
- Héritage de templates
- Réutilisation de composants
- Séparation logique/présentation

### 9. **Security**
- Validation des paramètres GET
- Protection contre les boucles infinies
- Échappement automatique dans Twig

### 10. **Readability for Beginners**
- Élimination des variables dynamiques complexes
- Code explicite et auto-documenté
- Commentaires pertinents

## 🔄 Flux de Données Complet

```
1. Utilisateur → URL avec paramètres GET
   ↓
2. Router Symfony → PokemonController::battleFight()
   ↓
3. Controller → Validation des paramètres
   ↓
4. Controller → PokemonService::battle()
   ↓
5. Service → Appels API (HttpClient)
   ↓
6. Service → Logique de combat + Calculs
   ↓
7. Service → Retour du résultat (array)
   ↓
8. Controller → Rendu du template Twig
   ↓
9. Template → Affichage HTML avec résultats
   ↓
10. Utilisateur → Page de résultats interactive
```

## 🎯 Points d'Apprentissage Symfony

### 1. **Architecture MVC**
- Séparation claire des responsabilités
- Controller comme orchestrateur

### 2. **Services**
- Logique métier externalisée
- Réutilisabilité et testabilité

### 3. **Routing**
- Annotations/Attributes pour les routes
- Paramètres de requête

### 4. **Twig**
- Moteur de templates puissant
- Filtres et fonctions intégrées

### 5. **HTTP Foundation**
- Gestion des requêtes et réponses
- Objets Request et Response

### 6. **Flash Messages**
- Communication temporaire avec l'utilisateur
- Gestion des erreurs et succès

## 🎯 Points d'Apprentissage Symfony

### 1. **Architecture MVC**
- Séparation claire des responsabilités
- Controller comme orchestrateur

### 2. **Services**
- Logique métier externalisée
- Réutilisabilité et testabilité

### 3. **Routing**
- Annotations/Attributes pour les routes
- Paramètres de requête

### 4. **Twig**
- Moteur de templates puissant
- Filtres et fonctions intégrées

### 5. **HTTP Foundation**
- Gestion des requêtes et réponses
- Objets Request et Response

### 6. **Flash Messages**
- Communication temporaire avec l'utilisateur
- Gestion des erreurs et succès

## 🔄 Évolution du Code : Avant vs Après

### 📊 **Métriques d'Amélioration**

| Aspect | Avant | Après | Amélioration |
|--------|--------|--------|--------------|
| **Lignes de code** | ~120 lignes | ~80 lignes | ✅ -33% |
| **Duplication** | 3 blocs dupliqués | 0 duplication | ✅ 100% éliminée |
| **Méthodes** | 2 méthodes | 5 méthodes | ✅ Mieux organisé |
| **Complexité** | Variables dynamiques | Logique claire | ✅ Plus lisible |
| **Testabilité** | Difficile | Facile | ✅ Méthodes isolées |

### 🚀 **Principales Améliorations**

#### ✅ **1. Élimination de la Duplication (DRY)**
```php
// ❌ AVANT : Code dupliqué
$pokemon1Stats = [
    'hp' => $pokemon1['stats'][0]['base_stat'],
    'attack' => $pokemon1['stats'][1]['base_stat'],
    'defense' => $pokemon1['stats'][2]['base_stat'],
    'speed' => $pokemon1['stats'][5]['base_stat'],
];
$pokemon2Stats = [
    'hp' => $pokemon2['stats'][0]['base_stat'],
    'attack' => $pokemon2['stats'][1]['base_stat'],
    'defense' => $pokemon2['stats'][2]['base_stat'],
    'speed' => $pokemon2['stats'][5]['base_stat'],
];

// ✅ APRÈS : Méthode réutilisable
private function extractPokemonStats(array $pokemon): array
{
    return [
        'hp' => $pokemon['stats'][0]['base_stat'],
        'attack' => $pokemon['stats'][1]['base_stat'],
        'defense' => $pokemon['stats'][2]['base_stat'],
        'speed' => $pokemon['stats'][5]['base_stat'],
    ];
}
```

#### ✅ **2. Simplification des Variables Dynamiques**
```php
// ❌ AVANT : Difficile à comprendre
${$firstAttacker . 'CurrentHp'} -= $damage;

// ✅ APRÈS : Clair et explicite
$defenderHp = &$battle->pokemon2CurrentHp;
$defenderHp -= $damage;
```

#### ✅ **3. Encapsulation avec BattleState**
```php
// ❌ AVANT : Variables éparpillées
$pokemon1CurrentHp = ...;
$pokemon2CurrentHp = ...;
$battleLog = [];
$turn = 1;

// ✅ APRÈS : État centralisé
$battle = new BattleState(
    pokemon1: $pokemon1,
    pokemon2: $pokemon2,
    pokemon1Stats: $pokemon1Stats,
    pokemon2Stats: $pokemon2Stats,
    pokemon1CurrentHp: $pokemon1Stats['hp'],
    pokemon2CurrentHp: $pokemon2Stats['hp']
);
```

#### ✅ **4. Séparation des Responsabilités**
```php
// ✅ Chaque méthode a un rôle précis
private function extractPokemonStats(array $pokemon): array        // Extraction
private function determineBattleOrder(...): bool                   // Ordre
private function processTurn(BattleState $battle, bool $p1): void  // Combat
private function calculateDamage(int $attack, int $defense): int   // Calculs
```

### 🎓 **Valeur Pédagogique**

Cette refactorisation illustre parfaitement :
- **L'évolution naturelle** du code (faire fonctionner → améliorer)
- **L'application des principes SOLID** en pratique
- **L'importance du refactoring** pour la maintenance
- **Les bonnes pratiques Symfony** en action

Le code est maintenant :
- ✅ **Plus facile à comprendre** pour les débutants
- ✅ **Plus facile à tester** (méthodes isolées)
- ✅ **Plus facile à maintenir** (pas de duplication)
- ✅ **Plus professionnel** (respect des standards)

Cette implémentation démontre comment Symfony facilite la création d'applications web robustes en respectant les bonnes pratiques de développement et les patterns d'architecture modernes.