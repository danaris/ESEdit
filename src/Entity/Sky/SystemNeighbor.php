<?php

namespace App\Entity\Sky;

use App\Repository\Sky\SystemNeighborRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemNeighborRepository::class)]
class SystemNeighbor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $distance = null;

    #[ORM\ManyToOne(inversedBy: 'systemNeighbors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?System $fromSystem = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?System $toSystem = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(float $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function getFromSystem(): ?System
    {
        return $this->fromSystem;
    }

    public function setFromSystem(?System $fromSystem): static
    {
        $this->fromSystem = $fromSystem;

        return $this;
    }

    public function getToSystem(): ?System
    {
        return $this->toSystem;
    }

    public function setToSystem(?System $toSystem): static
    {
        $this->toSystem = $toSystem;

        return $this;
    }
}
