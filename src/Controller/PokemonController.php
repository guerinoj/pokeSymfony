<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PokemonController extends AbstractController
{
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
        $response = file_get_contents('https://pokeapi.co/api/v2/pokemon');
        $data = json_decode($response, true);
        return $data['results'];
    }

    public function getById(int $id)
    {
        //code to get a pokemon by id from API
        $response = file_get_contents('https://pokeapi.co/api/v2/pokemon/' . $id);
        $data = json_decode($response, true);
        return $data;
    }

    public function getByName(string $name)
    {
        //code to get a pokemon by name from API
        $response = file_get_contents('https://pokeapi.co/api/v2/pokemon/' . strtolower($name));
        $data = json_decode($response, true);
        return $data;
    }
}
