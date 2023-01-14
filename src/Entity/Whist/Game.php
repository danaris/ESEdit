<?php

namespace App\Entity\Whist;

use App\Repository\Whist\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GameRepository::class)
 */
class Game
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=512, name="playerNames", nullable=true)
     */
    private $playerNames;

    /**
     * @ORM\Column(type="string", length=255, name="deckId")
     */
    private $deckId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $started;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity=Round::class, mappedBy="game", orphanRemoval=true)
     */
    private $rounds;

    /**
     * @ORM\ManyToMany(targetEntity=Player::class, inversedBy="games")
     */
    private $gamePlayers;

    /**
     * @ORM\OneToOne(targetEntity=GameState::class, mappedBy="game", cascade={"persist", "remove"})
     */
    private $gameState;

    public function __construct()
    {
        $this->rounds = new ArrayCollection();
        $this->gamePlayers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayerNames(): ?string
    {
        return $this->playerNames;
    }

    public function setPlayerNames(string $playerNames): self
    {
        $this->playerNames = $playerNames;

        return $this;
    }

    public function getDeckId(): ?string
    {
        return $this->deckId;
    }

    public function setDeckId(string $deckId): self
    {
        $this->deckId = $deckId;

        return $this;
    }

    public function getStarted(): ?\DateTimeInterface
    {
        return $this->started;
    }

    public function setStarted(\DateTimeInterface $started): self
    {
        $this->started = $started;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection|Round[]
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function addRound(Round $round): self
    {
        if (!$this->rounds->contains($round)) {
            $this->rounds[] = $round;
            $round->setGame($this);
        }

        return $this;
    }

    public function removeRound(Round $round): self
    {
        if ($this->rounds->removeElement($round)) {
            // set the owning side to null (unless already changed)
            if ($round->getGame() === $this) {
                $round->setGame(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Player[]
     */
    public function getGamePlayers(): Collection
    {
        return $this->gamePlayers;
    }

    public function addGamePlayer(Player $gamePlayer): self
    {
        if (!$this->gamePlayers->contains($gamePlayer)) {
            $this->gamePlayers[] = $gamePlayer;
        }

        return $this;
    }

    public function removeGamePlayer(Player $gamePlayer): self
    {
        $this->gamePlayers->removeElement($gamePlayer);

        return $this;
    }

    public function getGameState(): ?GameState
    {
        return $this->gameState;
    }

    public function setGameState(GameState $gameState): self
    {
        // set the owning side of the relation if necessary
        if ($gameState->getGame() !== $this) {
            $gameState->setGame($this);
        }

        $this->gameState = $gameState;

        return $this;
    }
}
