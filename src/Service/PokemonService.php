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

    // Extraire les statistiques (méthode réutilisable)
    $pokemon1Stats = $this->extractPokemonStats($pokemon1);
    $pokemon2Stats = $this->extractPokemonStats($pokemon2);

    // Créer l'état de combat initial
    $battle = new BattleState(
      pokemon1: $pokemon1,
      pokemon2: $pokemon2,
      pokemon1Stats: $pokemon1Stats,
      pokemon2Stats: $pokemon2Stats,
      pokemon1CurrentHp: $pokemon1Stats['hp'],
      pokemon2CurrentHp: $pokemon2Stats['hp']
    );

    // Messages d'introduction
    $battle->addLogEntry("Le combat commence ! " . ucfirst($pokemon1['name']) . " vs " . ucfirst($pokemon2['name']));

    // Déterminer l'ordre d'attaque
    $pokemon1AttacksFirst = $this->determineBattleOrder($pokemon1Stats, $pokemon2Stats, $battle);

    // Boucle de combat principale
    while (!$battle->isFinished() && !$battle->isTooLong()) {
      $battle->addLogEntry("--- Tour {$battle->turn} ---");

      if ($pokemon1AttacksFirst) {
        $this->processTurn($battle, true); // Pokémon 1 attaque
        if (!$battle->isFinished()) {
          $this->processTurn($battle, false); // Pokémon 2 attaque
        }
      } else {
        $this->processTurn($battle, false); // Pokémon 2 attaque
        if (!$battle->isFinished()) {
          $this->processTurn($battle, true); // Pokémon 1 attaque
        }
      }

      $battle->nextTurn();
    }

    // Gestion des cas particuliers
    if ($battle->isTooLong()) {
      $battle->addLogEntry("Combat trop long ! Match nul déclaré.");
    }

    return $battle->getBattleResult();
  }

  /**
   * Extrait les statistiques d'un Pokémon de manière réutilisable
   * Évite la duplication de code pour l'extraction des stats
   */
  private function extractPokemonStats(array $pokemon): array
  {
    return [
      'hp' => $pokemon['stats'][0]['base_stat'],
      'attack' => $pokemon['stats'][1]['base_stat'],
      'defense' => $pokemon['stats'][2]['base_stat'],
      'speed' => $pokemon['stats'][5]['base_stat'],
    ];
  }

  /**
   * Détermine qui attaque en premier basé sur la vitesse
   * Retourne true si le Pokémon 1 attaque en premier, false sinon
   */
  private function determineBattleOrder(array $pokemon1Stats, array $pokemon2Stats, BattleState $battle): bool
  {
    if ($pokemon1Stats['speed'] > $pokemon2Stats['speed']) {
      $battle->addLogEntry("Ordre d'attaque déterminé par la vitesse : " . ucfirst($battle->pokemon1['name']) . " attaque en premier !");
      return true;
    } elseif ($pokemon2Stats['speed'] > $pokemon1Stats['speed']) {
      $battle->addLogEntry("Ordre d'attaque déterminé par la vitesse : " . ucfirst($battle->pokemon2['name']) . " attaque en premier !");
      return false;
    } else {
      // Tirage aléatoire en cas d'égalité
      $pokemon1First = rand(0, 1) === 1;
      $firstAttacker = $pokemon1First ? $battle->pokemon1['name'] : $battle->pokemon2['name'];
      $battle->addLogEntry("Égalité de vitesse ! Tirage au sort : " . ucfirst($firstAttacker) . " attaque en premier !");
      return $pokemon1First;
    }
  }

  /**
   * Traite un tour d'attaque pour un Pokémon
   * Centralise la logique d'attaque pour éviter la duplication
   */
  private function processTurn(BattleState $battle, bool $pokemon1Attacks): void
  {
    if ($pokemon1Attacks) {
      // Pokémon 1 attaque Pokémon 2
      $attacker = $battle->pokemon1;
      $defender = $battle->pokemon2;
      $attackerStats = $battle->pokemon1Stats;
      $defenderStats = $battle->pokemon2Stats;
      $defenderHp = &$battle->pokemon2CurrentHp;
    } else {
      // Pokémon 2 attaque Pokémon 1
      $attacker = $battle->pokemon2;
      $defender = $battle->pokemon1;
      $attackerStats = $battle->pokemon2Stats;
      $defenderStats = $battle->pokemon1Stats;
      $defenderHp = &$battle->pokemon1CurrentHp;
    }

    // Vérifier que l'attaquant est encore en vie
    $attackerHp = $pokemon1Attacks ? $battle->pokemon1CurrentHp : $battle->pokemon2CurrentHp;
    if ($attackerHp <= 0) {
      return;
    }

    // Calculer et appliquer les dégâts
    $damage = $this->calculateDamage($attackerStats['attack'], $defenderStats['defense']);
    $defenderHp -= $damage;
    $defenderHp = max(0, $defenderHp);

    // Ajouter les messages au journal
    $battle->addLogEntry(ucfirst($attacker['name']) . " attaque " . ucfirst($defender['name']) . " et inflige $damage dégâts !");
    $battle->addLogEntry(ucfirst($defender['name']) . " : $defenderHp/" . $defenderStats['hp'] . " PV");

    // Vérifier si le défenseur est KO
    if ($defenderHp <= 0) {
      $battle->addLogEntry(ucfirst($defender['name']) . " est KO !");
      $battle->addLogEntry("🏆 " . ucfirst($attacker['name']) . " remporte le combat !");
    }
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
