<?php

namespace App\Service;

/**
 * Classe représentant l'état d'un combat entre deux Pokémon
 * Cette classe encapsule toutes les données nécessaires au combat
 * et fournit des méthodes utilitaires pour vérifier l'état du combat
 */
class BattleState
{
    public function __construct(
        public array $pokemon1,
        public array $pokemon2,
        public array $pokemon1Stats,
        public array $pokemon2Stats,
        public int $pokemon1CurrentHp,
        public int $pokemon2CurrentHp,
        public array $battleLog = [],
        public int $turn = 1
    ) {}

    /**
     * Vérifie si le combat est terminé
     * Un combat est terminé quand l'un des Pokémon n'a plus de PV
     */
    public function isFinished(): bool
    {
        return $this->pokemon1CurrentHp <= 0 || $this->pokemon2CurrentHp <= 0;
    }

    /**
     * Détermine le vainqueur du combat
     * Retourne le Pokémon gagnant ou null en cas de match nul
     */
    public function getWinner(): ?array
    {
        if ($this->pokemon1CurrentHp > 0) {
            return $this->pokemon1;
        }
        if ($this->pokemon2CurrentHp > 0) {
            return $this->pokemon2;
        }
        return null;
    }

    /**
     * Ajoute une entrée au journal de combat
     */
    public function addLogEntry(string $message): void
    {
        $this->battleLog[] = $message;
    }

    /**
     * Passe au tour suivant
     */
    public function nextTurn(): void
    {
        $this->turn++;
    }

    /**
     * Vérifie si le combat dure trop longtemps (sécurité)
     */
    public function isTooLong(): bool
    {
        return $this->turn > 100;
    }

    /**
     * Retourne les données formatées pour le résultat final
     */
    public function getBattleResult(): array
    {
        return [
            'pokemon1' => $this->pokemon1,
            'pokemon2' => $this->pokemon2,
            'pokemon1Stats' => $this->pokemon1Stats,
            'pokemon2Stats' => $this->pokemon2Stats,
            'pokemon1CurrentHp' => $this->pokemon1CurrentHp,
            'pokemon2CurrentHp' => $this->pokemon2CurrentHp,
            'winner' => $this->getWinner(),
            'battleLog' => $this->battleLog,
            'turns' => $this->turn - 1,
        ];
    }
}