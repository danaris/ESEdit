<?php

namespace App\Entity\Sky;

use App\Entity\Sky\Outfit;
use App\Repository\OutfitAttributesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OutfitAttributesRepository::class)]
class OutfitAttributes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Outfit $outfit = null;

    #[ORM\Column]
    private ?float $value = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

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
}
