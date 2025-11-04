<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PokemonService
{
  public function __construct(
    private HttpClientInterface $httpClient
  ) {}

  public function getAll($limit = 20, $offset = 0)
  {
    //code to get all pokemons from API with pagination
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
}
