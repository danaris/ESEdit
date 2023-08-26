<?php

namespace App\Entity\Sky;

use App\Repository\Sky\WormholeLinkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WormholeLinkRepository::class)]
class WormholeLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'linkObjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Wormhole $wormhole = null;

    #[ORM\ManyToOne(inversedBy: 'wormholeFromLinks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?System $fromSystem = null;

    #[ORM\ManyToOne(inversedBy: 'wormholeToLinks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?System $toSystem = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWormhole(): ?Wormhole
    {
        return $this->wormhole;
    }

    public function setWormhole(?Wormhole $wormhole): self
    {
        $this->wormhole = $wormhole;

        return $this;
    }

    public function getFromSystem(): ?System
    {
        return $this->fromSystem;
    }

    public function setFromSystem(?System $fromSystem): self
    {
        $this->fromSystem = $fromSystem;

        return $this;
    }

    public function getToSystem(): ?System
    {
        return $this->toSystem;
    }

    public function setToSystem(?System $toSystem): self
    {
        $this->toSystem = $toSystem;

        return $this;
    }
}
