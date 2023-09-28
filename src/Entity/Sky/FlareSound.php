<?php

namespace App\Entity\Sky;

use App\Repository\Sky\FlareSoundRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlareSoundRepository::class)]
class FlareSound
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Sound $sound = null;
	
	#[ORM\Column]
	private ?string $type = null;

    #[ORM\Column]
    private ?int $count = null;

    #[ORM\ManyToOne(inversedBy: 'flareSoundCollection')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Outfit $outfit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSound(): ?Sound
    {
        return $this->sound;
    }

    public function setSound(?Sound $sound): self
    {
        $this->sound = $sound;

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

	/**
	 * Get the value of type
	 */ 
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Set the value of type
	 *
	 * @return  self
	 */ 
	public function setType($type): self
	{
		$this->type = $type;

		return $this;
	}
}
