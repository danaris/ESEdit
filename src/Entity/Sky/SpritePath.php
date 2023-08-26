<?php

namespace App\Entity\Sky;

use App\Repository\Sky\SpritePathRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpritePathRepository::class)]
class SpritePath
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'framePaths')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sprite $sprite = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $path = null;

    #[ORM\Column]
    private ?int $pathIndex = null;

    #[ORM\Column]
    private ?bool $is2x = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSprite(): ?Sprite
    {
        return $this->sprite;
    }

    public function setSprite(?Sprite $sprite): static
    {
        $this->sprite = $sprite;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getPathIndex(): ?int
    {
        return $this->pathIndex;
    }

    public function setPathIndex(int $pathIndex): static
    {
        $this->pathIndex = $pathIndex;

        return $this;
    }

    public function isIs2x(): ?bool
    {
        return $this->is2x;
    }

    public function setIs2x(bool $is2x): static
    {
        $this->is2x = $is2x;

        return $this;
    }
}
