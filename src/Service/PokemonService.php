<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PokemonService
{
  public function __construct(
    private HttpClientInterface $httpClient
  ) {}

  public function getAll($limit = 20, $offset = 0, $sort = null)
  {
    //code to get all pokemons from API with pagination
    if ($sort === 'name_asc' || $sort === 'name_desc') {
      // Pour trier, on récupère plus de données puis on pagine
      $response = $this->httpClient->request('GET', 'https://pokeapi.co/api/v2/pokemon', [
        'query' => [
          'limit' => 2000, // Récupérer beaucoup de pokémons pour le tri
          'offset' => 0,
        ]
      ]);
      $data = $response->toArray();

      // Trier les résultats
      $results = $data['results'];
      usort($results, function ($a, $b) use ($sort) {
        if ($sort === 'name_asc') {
          return strcasecmp($a['name'], $b['name']);
        } else {
          return strcasecmp($b['name'], $a['name']);
        }
      });

      // Appliquer la pagination après tri
      $paginatedResults = array_slice($results, $offset, $limit);

      return [
        'count' => $data['count'],
        'results' => $paginatedResults
      ];
    }

    // Comportement par défaut (ordre de l'API)
    $response = $this->httpClient->request('GET', 'https://pokeapi.co/api/v2/pokemon', [
      'query' => [
        'limit' => $limit,
        'offset' => $offset,
      ]
    ]);
    $data = $response->toArray();
    return $data;
  }

  public function getById(int $id)
  {
    //code to get a pokemon by id from API
    $response = $this->httpClient->request('GET', 'https://pokeapi.co/api/v2/pokemon/' . $id);
    $data = $response->toArray();
    return $data;
  }

  public function getByName(string $name)
  {
    //code to get a pokemon by name from API
    $response = $this->httpClient->request('GET', 'https://pokeapi.co/api/v2/pokemon/' . strtolower($name));
    $data = $response->toArray();
    return $data;
  }

  public function searchByName(string $name)
  {
    //code to search pokemons by name from API
    $allPokemons = $this->getAll(2000, 0); // Get a large number of pokemons to search from
    $filtered = array_filter($allPokemons['results'], function ($pokemon) use ($name) {
      return stripos($pokemon['name'], $name) !== false;
    });
    return array_values($filtered);
  }

  public function battle(string $pokemon1Name, string $pokemon2Name): array
  {
    // Récupérer les données des deux Pokémon
    $pokemon1 = $this->getByName($pokemon1Name);
    $pokemon2 = $this->getByName($pokemon2Name);

    // Initialiser les PV actuels
    $pokemon1CurrentHp = $pokemon1['stats'][0]['base_stat']; // HP
    $pokemon2CurrentHp = $pokemon2['stats'][0]['base_stat']; // HP

    // Extraire les statistiques
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

    $battleLog = [];
    $turn = 1;

    // Déterminer qui attaque en premier
    $firstAttacker = null;
    $secondAttacker = null;

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

    $battleLog[] = "Le combat commence ! " . ucfirst($pokemon1['name']) . " vs " . ucfirst($pokemon2['name']);
    $battleLog[] = "Ordre d'attaque déterminé par la vitesse : " .
      ucfirst(${$firstAttacker}['name']) . " attaque en premier !";

    // Boucle de combat
    while ($pokemon1CurrentHp > 0 && $pokemon2CurrentHp > 0) {
      $battleLog[] = "--- Tour $turn ---";

      // Premier attaquant
      if (${$firstAttacker . 'CurrentHp'} > 0) {
        $damage = $this->calculateDamage(
          ${$firstAttacker . 'Stats'}['attack'],
          ${$secondAttacker . 'Stats'}['defense']
        );

        ${$secondAttacker . 'CurrentHp'} -= $damage;
        ${$secondAttacker . 'CurrentHp'} = max(0, ${$secondAttacker . 'CurrentHp'});

        $battleLog[] = ucfirst(${$firstAttacker}['name']) . " attaque " .
          ucfirst(${$secondAttacker}['name']) . " et inflige $damage dégâts !";
        $battleLog[] = ucfirst(${$secondAttacker}['name']) . " : " .
          ${$secondAttacker . 'CurrentHp'} . "/" . ${$secondAttacker . 'Stats'}['hp'] . " PV";

        if (${$secondAttacker . 'CurrentHp'} <= 0) {
          $battleLog[] = ucfirst(${$secondAttacker}['name']) . " est KO !";
          $battleLog[] = "🏆 " . ucfirst(${$firstAttacker}['name']) . " remporte le combat !";
          break;
        }
      }

      // Deuxième attaquant
      if (${$secondAttacker . 'CurrentHp'} > 0) {
        $damage = $this->calculateDamage(
          ${$secondAttacker . 'Stats'}['attack'],
          ${$firstAttacker . 'Stats'}['defense']
        );

        ${$firstAttacker . 'CurrentHp'} -= $damage;
        ${$firstAttacker . 'CurrentHp'} = max(0, ${$firstAttacker . 'CurrentHp'});

        $battleLog[] = ucfirst(${$secondAttacker}['name']) . " attaque " .
          ucfirst(${$firstAttacker}['name']) . " et inflige $damage dégâts !";
        $battleLog[] = ucfirst(${$firstAttacker}['name']) . " : " .
          ${$firstAttacker . 'CurrentHp'} . "/" . ${$firstAttacker . 'Stats'}['hp'] . " PV";

        if (${$firstAttacker . 'CurrentHp'} <= 0) {
          $battleLog[] = ucfirst(${$firstAttacker}['name']) . " est KO !";
          $battleLog[] = "🏆 " . ucfirst(${$secondAttacker}['name']) . " remporte le combat !";
          break;
        }
      }

      $turn++;

      // Sécurité pour éviter les boucles infinies
      if ($turn > 100) {
        $battleLog[] = "Combat trop long ! Match nul déclaré.";
        break;
      }
    }

    // Déterminer le vainqueur
    $winner = null;
    if ($pokemon1CurrentHp > 0) {
      $winner = $pokemon1;
    } elseif ($pokemon2CurrentHp > 0) {
      $winner = $pokemon2;
    }

    return [
      'pokemon1' => $pokemon1,
      'pokemon2' => $pokemon2,
      'pokemon1Stats' => $pokemon1Stats,
      'pokemon2Stats' => $pokemon2Stats,
      'pokemon1CurrentHp' => $pokemon1CurrentHp,
      'pokemon2CurrentHp' => $pokemon2CurrentHp,
      'winner' => $winner,
      'battleLog' => $battleLog,
      'turns' => $turn - 1,
    ];
  }

  private function calculateDamage(int $attack, int $defense): int
  {
    // Formule de dégâts simplifiée : (Attaque / Défense) * 10 * variance
    $baseDamage = ($attack / max(1, $defense)) * 10;

    // Variance entre 0.85 et 1.15
    $variance = mt_rand(85, 115) / 100;

    $finalDamage = $baseDamage * $variance;

    // Minimum 1 dégât
    return max(1, (int)round($finalDamage));
  }
}
