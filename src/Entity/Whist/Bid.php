<?php

namespace App\Entity\Whist;

use App\Repository\Whist\BidRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BidRepository::class)
 */
class Bid
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Player::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $player;

    /**
     * @ORM\ManyToOne(targetEntity=Round::class, inversedBy="bids")
     * @ORM\JoinColumn(nullable=false)
     */
    private $round;

    /**
     * @ORM\Column(type="integer")
     */
    private $tricks;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getRound(): ?Round
    {
        return $this->round;
    }

    public function setRound(?Round $round): self
    {
        $this->round = $round;

        return $this;
    }

    public function getTricks(): ?int
    {
        return $this->tricks;
    }

    public function setTricks(int $tricks): self
    {
        $this->tricks = $tricks;

        return $this;
    }
}
