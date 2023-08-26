<?php

namespace App\Entity\Sky;

use App\Repository\Sky\SoundRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoundRepository::class)]
class Sound
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 1024)]
    private ?string $soundFile = null;

    #[ORM\Column]
    private ?bool $isLooped = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSoundFile(): ?string
    {
        return $this->soundFile;
    }

    public function setSoundFile(string $soundFile): self
    {
        $this->soundFile = $soundFile;

        return $this;
    }

    public function isIsLooped(): ?bool
    {
        return $this->isLooped;
    }

    public function setIsLooped(bool $isLooped): self
    {
        $this->isLooped = $isLooped;

        return $this;
    }
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['name'] = $this->name;
		$jsonArray['soundFile'] = $this->soundFile;
		$jsonArray['isLooped'] = $this->isLooped;
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}
}
