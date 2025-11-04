<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PokemonController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}
    #[Route('/pokemon', name: 'pokemon.index')]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $pokemonData = $this->getAll($limit, $offset);

        // Calculer le nombre total de pages
        $totalPokemon = $pokemonData['count'];
        $totalPages = ceil($totalPokemon / $limit);

        return $this->render('pokemon/index.html.twig', [
            'pokemon_list' => $pokemonData['results'],
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_pokemon' => $totalPokemon,
            'limit' => $limit,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
            'previous_page' => $page - 1,
            'next_page' => $page + 1,
        ]);
    }

    #[Route('/pokemon/{name}', name: 'pokemon.show')]
    public function show(string $name): Response
    {
        return $this->render('pokemon/show.html.twig', [
            'controller_name' => 'PokemonController',
            'pokemon_name' => $name,
            'pokemon_data' => $this->getByName($name),
        ]);
    }

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
