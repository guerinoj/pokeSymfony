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
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $pokemonData = $this->pokemonService->getAll($limit, $offset);

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
            'pokemon_data' => $this->pokemonService->getByName($name),
        ]);
    }
}
