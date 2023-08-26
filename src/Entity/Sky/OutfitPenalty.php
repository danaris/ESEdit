<?php

namespace App\Entity\Sky;

use App\Repository\Sky\OutfitPenaltyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OutfitPenaltyRepository::class)]
class OutfitPenalty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'outfitPenalties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Government $government = null;

    #[ORM\ManyToOne(inversedBy: 'penalties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Outfit $outfit = null;

    #[ORM\Column(length: 32)]
    private ?string $penaltyType = null;

    #[ORM\Column(nullable: true)]
    private ?int $penalty = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGovernment(): ?Government
    {
        return $this->government;
    }

    public function setGovernment(?Government $government): self
    {
        $this->government = $government;

        return $this;
    }

    public function getOutfit(): ?Outfit
    {
        return $this->outfit;
    }

    public function setOutfit(?Outfit $outfit): self
    {
        $this->outfit = $outfit;

        return $this;
    }

    public function getPenaltyType(): ?string
    {
        return $this->penaltyType;
    }

    public function setPenaltyType(string $penaltyType): self
    {
        $this->penaltyType = $penaltyType;

        return $this;
    }

    public function getPenalty(): ?int
    {
        return $this->penalty;
    }

    public function setPenalty(?int $penalty): self
    {
        $this->penalty = $penalty;

        return $this;
    }
}
