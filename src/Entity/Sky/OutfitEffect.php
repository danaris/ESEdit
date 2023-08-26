<?php

namespace App\Entity\Sky;

use App\Repository\Sky\OutfitEffectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OutfitEffectRepository::class)]
class OutfitEffect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Effect $effect = null;

    #[ORM\ManyToOne(inversedBy: 'effectCollection')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Outfit $outfit = null;
	
	#[ORM\Column]
	private ?string $type = null;

    #[ORM\Column]
    private ?int $count = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEffect(): ?Effect
    {
        return $this->effect;
    }

    public function setEffect(?Effect $effect): self
    {
        $this->effect = $effect;

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

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }
	
	public function getType(): ?string
	{
		return $this->type;
	}
	
	public function setType(string $type): self
	{
		$this->type = $type;
	
		return $this;
	}
}
