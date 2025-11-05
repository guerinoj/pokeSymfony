<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PokemonService;

final class PokemonController extends AbstractController
{
    public function __construct(private PokemonService $pokemonService) {}

    #[Route('/pokemon', name: 'pokemon.index')]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $sort = $request->query->get('sort');
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $pokemonData = $this->pokemonService->getAll($limit, $offset, $sort);

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
            'current_sort' => $sort,
        ]);
    }

    #[Route('/pokemon/search', name: 'pokemon.search')]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $results = [];
        $totalResults = 0;

        if ($query) {
            $allResults = $this->pokemonService->searchByName($query);
            $totalResults = count($allResults);

            // Pagination des résultats
            $offset = ($page - 1) * $limit;
            $results = array_slice($allResults, $offset, $limit);
        }

        $totalPages = $totalResults > 0 ? ceil($totalResults / $limit) : 0;

        return $this->render('pokemon/search.html.twig', [
            'controller_name' => 'PokemonController',
            'search_query' => $query,
            'pokemon_list' => $results,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_pokemon' => $totalResults,
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
            'pokemon_data' => $this->pokemonService->getByName($name),
        ]);
    }
}
