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
Le **Service Layer Pattern** sépare la logique métier du contrôleur. Dans notre cas, `PokemonService` encapsule toute la logique de combat.

### Avantages
- **Réutilisabilité** : Le service peut être utilisé dans plusieurs contrôleurs
- **Testabilité** : Logique métier isolée et facilement testable
- **Maintenabilité** : Séparation claire des responsabilités

### Implémentation

```php
class PokemonService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function battle(string $pokemon1Name, string $pokemon2Name): array
    {
        // 1. Récupération des données
        $pokemon1 = $this->getByName($pokemon1Name);
        $pokemon2 = $this->getByName($pokemon2Name);

        // 2. Initialisation du combat
        $pokemon1CurrentHp = $pokemon1['stats'][0]['base_stat'];
        $pokemon2CurrentHp = $pokemon2['stats'][0]['base_stat'];

        // 3. Logique de combat
        // ... (voir code complet dans le service)

        return $battleResult;
    }
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

### Calcul des Dégâts
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

### Détermination de l'Ordre d'Attaque
```php
if ($pokemon1Stats['speed'] > $pokemon2Stats['speed']) {
    $firstAttacker = 'pokemon1';
    $secondAttacker = 'pokemon2';
} elseif ($pokemon2Stats['speed'] > $pokemon1Stats['speed']) {
    $firstAttacker = 'pokemon2';
    $secondAttacker = 'pokemon1';
} else {
    // Tirage aléatoire en cas d'égalité
    $firstAttacker = rand(0, 1) ? 'pokemon1' : 'pokemon2';
    $secondAttacker = $firstAttacker === 'pokemon1' ? 'pokemon2' : 'pokemon1';
}
```

### Boucle de Combat
```php
while ($pokemon1CurrentHp > 0 && $pokemon2CurrentHp > 0) {
    // Attaque du premier Pokémon
    // Vérification KO
    // Attaque du second Pokémon
    // Vérification KO
    
    $turn++;
    
    // Sécurité anti-boucle infinie
    if ($turn > 100) {
        $battleLog[] = "Combat trop long ! Match nul déclaré.";
        break;
    }
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

## 📊 Variables Dynamiques en PHP

### Utilisation Avancée
```php
// Variables dynamiques pour gérer les deux Pokémon
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

### Avantages
- **Évite la duplication** de code
- **Flexibilité** dans l'ordre d'attaque
- **Maintenabilité** du code

## ✅ Bonnes Pratiques Appliquées

### 1. **Single Responsibility Principle**
- Chaque classe a une responsabilité unique
- `PokemonService` → Logique métier Pokémon
- `PokemonController` → Gestion des requêtes HTTP

### 2. **Dependency Injection**
- Injection automatique des dépendances
- Code découplé et testable

### 3. **Naming Conventions**
- Routes nommées : `pokemon.battle.select`
- Méthodes descriptives : `battleSelect()`, `calculateDamage()`
- Variables explicites : `$pokemon1CurrentHp`

### 4. **Error Handling**
- Validation des entrées utilisateur
- Messages d'erreur informatifs
- Redirections appropriées

### 5. **Template Organisation**
- Héritage de templates
- Réutilisation de composants
- Séparation logique/présentation

### 6. **Security**
- Validation des paramètres GET
- Protection contre les boucles infinies
- Échappement automatique dans Twig

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

Cette implémentation démontre comment Symfony facilite la création d'applications web robustes en respectant les bonnes pratiques de développement et les patterns d'architecture modernes.