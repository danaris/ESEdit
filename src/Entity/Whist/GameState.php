<?php

namespace App\Entity\Whist;

use App\Repository\Whist\GameStateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GameStateRepository::class)
 */
class GameState
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Game::class, inversedBy="gameState", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $game;

    /**
     * @ORM\ManyToOne(targetEntity=Round::class)
     */
    private $curRound;

    /**
     * @ORM\ManyToOne(targetEntity=Player::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $turnPlayer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(Game $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getCurRound(): ?Round
    {
        return $this->curRound;
    }

    public function setCurRound(?Round $curRound): self
    {
        $this->curRound = $curRound;

        return $this;
    }

    public function getTurnPlayer(): ?Player
    {
        return $this->turnPlayer;
    }

    public function setTurnPlayer(?Player $turnPlayer): self
    {
        $this->turnPlayer = $turnPlayer;

        return $this;
    }
}
