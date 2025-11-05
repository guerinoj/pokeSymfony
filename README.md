# PokeSymfony 🎮

Un projet Symfony pour explorer l'univers des Pokémon utilisant l'API [PokéAPI](https://pokeapi.co/).

## 🌐 API utilisée

Ce projet utilise **PokéAPI** (https://pokeapi.co/), une API REST gratuite qui fournit des données complètes sur l'univers Pokémon :

- **Base URL** : `https://pokeapi.co/api/v2/`
- **Documentation** : https://pokeapi.co/docs/v2
- **Pas d'authentification requise**
- **Rate limit** : Aucune limite stricte, mais utilisation responsable recommandée

### Endpoints principaux utilisés

```bash
# Lister les Pokémon (avec pagination)
GET https://pokeapi.co/api/v2/pokemon/?limit=20&offset=0

# Détails d'un Pokémon spécifique
GET https://pokeapi.co/api/v2/pokemon/{id_ou_nom}/

# Informations sur une espèce
GET https://pokeapi.co/api/v2/pokemon-species/{id}/

# Types de Pokémon
GET https://pokeapi.co/api/v2/type/

# Générations
GET https://pokeapi.co/api/v2/generation/
```

### Exemple de données retournées

```json
{
  "id": 25,
  "name": "pikachu",
  "height": 4,
  "weight": 60,
  "sprites": {
    "front_default": "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/25.png"
  },
  "types": [
    {
      "slot": 1,
      "type": {
        "name": "electric",
        "url": "https://pokeapi.co/api/v2/type/13/"
      }
    }
  ]
}
```

## 🚀 Installation du projet

### Prérequis

Avant de commencer, assurez-vous d'avoir installé :
- PHP 8.2 ou supérieur
- Composer
- Symfony CLI
- Un serveur de base de données (MySQL, PostgreSQL, etc.)

### Installation de Symfony CLI

Si vous n'avez pas encore installé Symfony CLI :

```bash
# Sur macOS avec Homebrew
brew install symfony-cli/tap/symfony-cli

# Sur Linux/macOS avec curl
curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | sudo -E bash
sudo apt install symfony-cli

# Vérifier l'installation
symfony version
```

### Étapes d'installation

1. **Cloner le projet**
   ```bash
   git clone <url-du-repo>
   cd pokeSymfony
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer la base de données**
   - Dupliquer le fichier `.env` en `.env.local`
   - Modifier la variable `DATABASE_URL` dans `.env.local`
   ```
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/pokesymfony"
   ```

4. **Créer la base de données**
   ```bash
   symfony console doctrine:database:create
   ```

5. **Exécuter les migrations (si elles existent)**
   ```bash
   symfony console doctrine:migrations:migrate
   ```

6. **Installer les assets**
   ```bash
   symfony console importmap:install
   ```

## 🏃‍♂️ Lancer le serveur de développement

### Avec Symfony CLI (Recommandé)

```bash
# Démarrer le serveur sur le port par défaut (8000)
symfony serve

# Démarrer le serveur sur un port spécifique
symfony serve --port=8080

# Démarrer le serveur en arrière-plan
symfony serve -d

# Arrêter le serveur en arrière-plan
symfony server:stop

# Voir les logs du serveur
symfony server:log
```

### Autres options utiles

```bash
# Vérifier les prérequis Symfony
symfony check:requirements

# Vérifier la sécurité des dépendances
symfony check:security

# Ouvrir le projet dans le navigateur
symfony open:local
```

## 🎛️ Créer des composants avec Maker Bundle

Le projet utilise `symfony/maker-bundle` pour générer du code automatiquement.

### Créer un Controller

```bash
# Créer un controller basique
symfony console make:controller NomDuController

# Créer un controller avec toutes les actions CRUD
symfony console make:crud

# Exemple : créer un controller Pokemon
symfony console make:controller PokemonController
```

Cette commande va créer :
- `src/Controller/PokemonController.php`
- `templates/pokemon/index.html.twig`

### Créer une Entity

```bash
# Créer une nouvelle entité
symfony console make:entity

# Exemple : créer une entité Pokemon
symfony console make:entity Pokemon
```

### Créer un Form

```bash
# Créer un formulaire pour une entité
symfony console make:form

# Exemple : créer un formulaire pour Pokemon
symfony console make:form PokemonType Pokemon
```

### Autres générateurs utiles

```bash
# Créer un repository personnalisé
symfony console make:repository

# Créer une commande console
symfony console make:command

# Créer un service/classe
symfony console make:service

# Créer des tests
symfony console make:test

# Voir toutes les commandes disponibles
symfony console list make
```

## 🎨 Créer des composants Twig

### Créer un composant Twig avec UX

Si vous utilisez Symfony UX (déjà installé dans ce projet) :

```bash
# Installer Twig Components (si pas déjà fait)
composer require symfony/ux-twig-component

# Créer un composant Twig
symfony console make:twig-component

# Exemple : créer un composant PokemonCard
symfony console make:twig-component PokemonCard
```

Cette commande crée :
- `src/Twig/Components/PokemonCard.php`
- `templates/components/PokemonCard.html.twig`

### Utilisation d'un composant Twig

Une fois créé, vous pouvez utiliser votre composant dans vos templates :

```twig
{# Dans un template Twig #}
<twig:PokemonCard :pokemon="pokemon" />

{# Ou avec des attributs #}
<twig:PokemonCard 
    :pokemon="pokemon" 
    class="pokemon-card-custom"
    data-id="{{ pokemon.id }}"
/>
```

### Créer des templates personnalisés

```bash
# Créer un template dans un dossier spécifique
mkdir -p templates/pokemon
touch templates/pokemon/card.html.twig
```

### Fonctions Twig utiles

```bash
# Créer une extension Twig personnalisée
symfony console make:twig-extension
```

## 🗃️ Base de données

### Commandes Doctrine utiles

```bash
# Créer une migration
symfony console make:migration

# Exécuter les migrations
symfony console doctrine:migrations:migrate

# Créer la base de données
symfony console doctrine:database:create

# Supprimer la base de données
symfony console doctrine:database:drop --force

# Mettre à jour le schéma (attention en production !)
symfony console doctrine:schema:update --force

# Vérifier le mapping des entités
symfony console doctrine:schema:validate

# Charger des fixtures (si configurées)
symfony console doctrine:fixtures:load
```

## 🧪 Tests

```bash
# Lancer tous les tests
symfony console doctrine:database:create --env=test
symfony console doctrine:migrations:migrate --env=test
php bin/phpunit

# Lancer un test spécifique
php bin/phpunit tests/Controller/HomeControllerTest.php

# Lancer les tests avec couverture
php bin/phpunit --coverage-html coverage
```

## � Intégration avec PokéAPI

### Service HTTP Client

Le projet utilise le composant `symfony/http-client` pour communiquer avec l'API :

```bash
# Le client HTTP est déjà configuré dans le projet
# Vérifier la configuration dans config/packages/framework.yaml
```

### Exemples d'utilisation dans les controllers

```php
// Dans un controller
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PokemonController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}
    
    public function show(int $id): Response
    {
        // Appel à l'API PokéAPI
        $response = $this->httpClient->request('GET', "https://pokeapi.co/api/v2/pokemon/{$id}");
        $pokemon = $response->toArray();
        
        return $this->render('pokemon/show.html.twig', [
            'pokemon' => $pokemon
        ]);
    }
}
```

### Gestion du cache

Pour optimiser les performances et réduire les appels à l'API :

```bash
# Le cache HTTP est configuré pour les réponses de l'API
# Vérifier la configuration dans config/packages/cache.yaml

# Vider le cache HTTP si nécessaire
symfony console cache:pool:clear cache.http
```

### Gestion des erreurs API

L'application gère les cas d'erreur de l'API :
- Pokémon non trouvé (404)
- Erreurs de réseau
- Timeouts
- Rate limiting

## �📝 Commandes utiles

### Cache

```bash
# Vider le cache
symfony console cache:clear

# Vider le cache de production
symfony console cache:clear --env=prod

# Préchauffer le cache
symfony console cache:warmup
```

### Debug

```bash
# Lister toutes les routes
symfony console debug:router

# Voir les détails d'une route
symfony console debug:router app_pokemon_show

# Lister les services
symfony console debug:container

# Voir la configuration
symfony console debug:config framework

# Voir les événements
symfony console debug:event-dispatcher
```

### Assets

```bash
# Installer les assets
symfony console importmap:install

# Mettre à jour les assets
symfony console importmap:update

# Voir les assets installés
symfony console importmap:list
```

## 🚀 Déploiement

```bash
# Préparer l'application pour la production
composer install --no-dev --optimize-autoloader
symfony console cache:clear --env=prod
symfony console cache:warmup --env=prod
```

## 📚 Structure du projet

```
src/
├── Controller/     # Contrôleurs de l'application
├── Entity/         # Entités Doctrine
├── Repository/     # Repositories Doctrine
└── Kernel.php      # Noyau de l'application

templates/          # Templates Twig
├── base.html.twig  # Template de base
├── home/           # Templates pour HomeController
└── pokemon/        # Templates pour PokemonController

config/             # Configuration de l'application
├── packages/       # Configuration des bundles
└── routes/         # Configuration des routes

public/             # Dossier web accessible
└── index.php       # Point d'entrée

assets/             # Assets frontend
├── app.js          # JavaScript principal
├── styles/         # Feuilles de style
└── controllers/    # Contrôleurs Stimulus
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📚 Documentation

- [Documentation Symfony](https://symfony.com/doc)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [Twig Documentation](https://twig.symfony.com/doc)
- [PokéAPI Documentation](https://pokeapi.co/docs/v2) - API des données Pokémon
- [Symfony HTTP Client](https://symfony.com/doc/current/http_client.html) - Pour les appels d'API

## 🎓 Guide d'Apprentissage Symfony

### 🥊 Système de Combat - Étude de Cas
Le projet inclut un **système de combat Pokémon complet** qui sert d'exemple pédagogique pour comprendre les concepts avancés de Symfony :

👉 **[Guide Complet du Système de Combat](COMBAT_SYSTEM.md)**

**Ce que vous y apprendrez :**
- 🏗️ **Architecture MVC** - Séparation des responsabilités
- 🔧 **Service Layer Pattern** - Logique métier externalisée
- 💉 **Injection de Dépendances** - Découplage et testabilité
- 🛣️ **Routing avancé** - Gestion des paramètres GET
- 🎨 **Templates Twig** - Héritage et logique conditionnelle
- ⚠️ **Gestion d'erreurs** - Validation et messages Flash
- 🔄 **Appels d'API** - HttpClient et gestion des réponses
- 📝 **Variables dynamiques PHP** - Techniques avancées

**Fonctionnalités du combat :**
- Sélection interactive de 2 Pokémon
- Calcul automatique basé sur les statistiques réelles
- Journal détaillé de chaque action
- Interface responsive avec résultats visuels

Ce système illustre parfaitement comment structurer une application Symfony en respectant les bonnes pratiques et les principes SOLID.

## 🎯 Fonctionnalités

### Actuellement implémentées
- 🏠 Page d'accueil
- 🔍 Affichage des détails d'un Pokémon via l'API
- 📋 Liste paginée des Pokémon avec tri
- 🔍 Recherche par nom
- 🥊 **Système de combat Pokémon** - [Guide détaillé](COMBAT_SYSTEM.md)
- 🎨 Interface responsive avec Bootstrap
- 🚀 Navigation entre les Pokémon

### À venir
- ❤️ Système de favoris
- 📊 Statistiques détaillées avancées
- 🎲 Pokémon aléatoire amélioré
- 📱 PWA (Progressive Web App)

---

Fait avec ❤️ et Symfony 7.3