<?php

namespace App\Entity\Sky;

use App\Repository\Sky\GovernmentPenaltyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GovernmentPenaltyRepository::class)]
class GovernmentPenalty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'penaltyForObject')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Government $government = null;

    #[ORM\Column]
    private ?int $eventType = null;

    #[ORM\Column]
    private ?float $penalty = null;

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

    public function getEventType(): ?int
    {
        return $this->eventType;
    }

    public function setEventType(int $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getPenalty(): ?float
    {
        return $this->penalty;
    }

    public function setPenalty(float $penalty): self
    {
        $this->penalty = $penalty;

        return $this;
    }
}
