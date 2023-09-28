<?php

namespace App\Entity\Sky;

use App\Repository\Sky\FlareSpriteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlareSpriteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FlareSprite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private ?string $type = null;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?Body $sprite = null;

    #[ORM\Column]
    private ?int $count = null;

    #[ORM\ManyToOne(inversedBy: 'flareSpriteCollection')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Outfit $outfit = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSprite(): ?Body
    {
        return $this->sprite;
    }

    public function setSprite(?Body $sprite): self
    {
        $this->sprite = $sprite;

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

    public function getOutfit(): ?Outfit
    {
        return $this->outfit;
    }

    public function setOutfit(?Outfit $outfit): self
    {
        $this->outfit = $outfit;

        return $this;
    }
	
	#[ORM\PrePersist]
	public function prePersist() {
		//error_log('Persisting flare sprite '.$this);
	}
	
	public function __toString(): string {
		$output = $this->type.' Flare sprite ('.$this->sprite?->getSprite()?->getPath().') of outfit '.$this->outfit?->getTrueName();
		
		return $output;
	}
}
