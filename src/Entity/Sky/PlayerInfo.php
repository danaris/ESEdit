<?php

namespace App\Entity\Sky;

use App\Repository\Sky\PlayerInfoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerInfoRepository::class)]
class PlayerInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $filePath = null;

    #[ORM\Column]
    private ?int $dateInt = null;

    #[ORM\ManyToOne]
    private ?System $system = null;

    #[ORM\ManyToOne]
    private ?Planet $planet = null;

    #[ORM\ManyToOne]
    private ?Ship $flagship = null;

    #[ORM\ManyToMany(targetEntity: Mission::class)]
    private Collection $missions;

    #[ORM\ManyToMany(targetEntity: System::class)]
    private Collection $seen;

    #[ORM\ManyToMany(targetEntity: GameEvent::class)]
    private Collection $gameEvents;

    public function __construct()
    {
        $this->missions = new ArrayCollection();
        $this->seen = new ArrayCollection();
        $this->gameEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getDateInt(): ?int
    {
        return $this->dateInt;
    }

    public function setDateInt(int $dateInt): static
    {
        $this->dateInt = $dateInt;

        return $this;
    }

    public function getSystem(): ?System
    {
        return $this->system;
    }

    public function setSystem(?System $system): static
    {
        $this->system = $system;

        return $this;
    }

    public function getPlanet(): ?Planet
    {
        return $this->planet;
    }

    public function setPlanet(?Planet $planet): static
    {
        $this->planet = $planet;

        return $this;
    }

    public function getFlagship(): ?Ship
    {
        return $this->flagship;
    }

    public function setFlagship(?Ship $flagship): static
    {
        $this->flagship = $flagship;

        return $this;
    }

    /**
     * @return Collection<int, Mission>
     */
    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(Mission $mission): static
    {
        if (!$this->missions->contains($mission)) {
            $this->missions->add($mission);
        }

        return $this;
    }

    public function removeMission(Mission $mission): static
    {
        $this->missions->removeElement($mission);

        return $this;
    }

    /**
     * @return Collection<int, System>
     */
    public function getSeen(): Collection
    {
        return $this->seen;
    }

    public function addSeen(System $seen): static
    {
        if (!$this->seen->contains($seen)) {
            $this->seen->add($seen);
        }

        return $this;
    }

    public function removeSeen(System $seen): static
    {
        $this->seen->removeElement($seen);

        return $this;
    }

    /**
     * @return Collection<int, GameEvent>
     */
    public function getGameEvents(): Collection
    {
        return $this->gameEvents;
    }

    public function addGameEvent(GameEvent $gameEvent): static
    {
        if (!$this->gameEvents->contains($gameEvent)) {
            $this->gameEvents->add($gameEvent);
        }

        return $this;
    }

    public function removeGameEvent(GameEvent $gameEvent): static
    {
        $this->gameEvents->removeElement($gameEvent);

        return $this;
    }
}
