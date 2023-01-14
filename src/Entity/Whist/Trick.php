<?php

namespace App\Entity\Whist;

use App\Repository\Whist\TrickRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TrickRepository::class)
 */
class Trick
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Round::class, inversedBy="tricks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $round;

    /**
     * @ORM\Column(type="integer")
     */
    private $orderInRound;

    /**
     * @ORM\OneToMany(targetEntity=Play::class, mappedBy="trick", orphanRemoval=true)
     */
    private $plays;

    /**
     * @ORM\ManyToOne(targetEntity=Player::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $winner;

    public function __construct()
    {
        $this->plays = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOrderInRound(): ?int
    {
        return $this->orderInRound;
    }

    public function setOrderInRound(int $orderInRound): self
    {
        $this->orderInRound = $orderInRound;

        return $this;
    }

    /**
     * @return Collection|Play[]
     */
    public function getPlays(): Collection
    {
        return $this->plays;
    }

    public function addPlay(Play $play): self
    {
        if (!$this->plays->contains($play)) {
            $this->plays[] = $play;
            $play->setTrick($this);
        }

        return $this;
    }

    public function removePlay(Play $play): self
    {
        if ($this->plays->removeElement($play)) {
            // set the owning side to null (unless already changed)
            if ($play->getTrick() === $this) {
                $play->setTrick(null);
            }
        }

        return $this;
    }

    public function getWinner(): ?Player
    {
        return $this->winner;
    }

    public function setWinner(?Player $winner): self
    {
        $this->winner = $winner;

        return $this;
    }
}
