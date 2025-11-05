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
}
