<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PokemonController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}
    #[Route('/pokemon', name: 'pokemon.index')]
    public function index(): Response
    {
        return $this->render('pokemon/index.html.twig', [
            'pokemon_list' => $this->getAll(),
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

    public function getAll()
    {
        //code to get all pokemons from API
        $response = $this->httpClient->request('GET', 'https://pokeapi.co/api/v2/pokemon');
        $data = $response->toArray();
        return $data['results'];
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
