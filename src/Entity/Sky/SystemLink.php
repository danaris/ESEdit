<?php

namespace App\Entity\Sky;

use App\Repository\Sky\SystemLinkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemLinkRepository::class)]
class SystemLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'linksFrom')]
    #[ORM\JoinColumn(nullable: false)]
    private ?System $fromSystem = null;

    #[ORM\ManyToOne(inversedBy: 'linksTo')]
    #[ORM\JoinColumn(nullable: false)]
    private ?System $toSystem = null;
	
	#[ORM\Column(type: 'boolean', name: 'accessibleLink')]
	private $accessible = true;

    public function getId(): ?int
    {
        return $this->id;
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
	
	public function getAccessible(): bool
	{
		return $this->accessible;
	}
	
	public function isAccessible(): bool
	{
		return $this->accessible;
	}
	
	public function setAccessible(bool $accessible): self
	{
		$this->accessible = $accessible;
	
		return $this;
	}
}
