<?php

namespace App\Entity\Sky;

use App\Repository\Sky\EventTriggerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventTriggerRepository::class)]
class EventTrigger
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?GameAction $gameAction = null;

    #[ORM\Column]
    private ?int $minDays = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxDays = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?GameEvent $event = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGameAction(): ?GameAction
    {
        return $this->gameAction;
    }

    public function setGameAction(?GameAction $gameAction): self
    {
        $this->gameAction = $gameAction;

        return $this;
    }

    public function getMinDays(): ?int
    {
        return $this->minDays;
    }

    public function setMinDays(int $minDays): self
    {
        $this->minDays = $minDays;

        return $this;
    }

    public function getMaxDays(): ?int
    {
        return $this->maxDays;
    }

    public function setMaxDays(?int $maxDays): self
    {
        $this->maxDays = $maxDays;

        return $this;
    }

    public function getEvent(): ?GameEvent
    {
        return $this->event;
    }

    public function setEvent(?GameEvent $event): self
    {
        $this->event = $event;

        return $this;
    }
}
