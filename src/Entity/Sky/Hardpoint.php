<?php

namespace App\Entity\Sky;

use App\Repository\Sky\HardpointRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

#[ORM\Entity(repositoryClass: HardpointRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Hardpoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $pointStr = null;

    #[ORM\Column]
    private ?float $baseAngleDegrees = null;

    #[ORM\Column]
    private ?bool $isTurret = null;

    #[ORM\Column]
    private ?bool $isParallel = null;

    #[ORM\Column]
    private ?bool $isUnder = null;

    #[ORM\ManyToOne]
    private ?Outfit $outfit = null;

    #[ORM\ManyToOne(inversedBy: 'hardpoints')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ship $ship = null;
	
	private ?Point $point = null;
	private ?Angle $baseAngle = null;
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->pointStr = json_encode($this->point);
		$this->baseAngleDegrees = $this->baseAngle->getDegrees();
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$pointArray = json_decode($this->pointStr, true);
		$this->point = new Point($pointArray['x'], $pointArray['y']);
		$this->baseAngle = new Angle($this->baseAngleDegrees);
	}
	
	public function __construct(Point $point, Ship $ship, ?Outfit $outfit, bool $isUnder, ?Angle $baseAngle = null, bool $isParallel = false, bool $isTurret = false) {
		$this->ship = $ship;
		$this->point = $point;
		if ($baseAngle) {
			$this->baseAngle = $baseAngle;
		} else {
			$this->baseAngle = new Angle();
		}
		$this->isTurret = $isTurret;
		$this->isParallel = $isParallel;
		$this->isUnder = $isUnder;
		$this->outfit = $outfit;
	}

    public function getId(): ?int
    {
        return $this->id;
    }
	
	public function getPoint(): Point {
		if ($this->point == null) {
			$pointArray = json_decode($this->pointStr, true);
			$this->point = new Point($pointArray['x'], $pointArray['y']);
		}
		return $this->point;
	}

    public function getPointStr(): ?string
    {
        return $this->pointStr;
    }

    public function setPointStr(string $pointStr): static
    {
        $this->pointStr = $pointStr;

        return $this;
    }
	
	public function getBaseAngle(): Angle {
		if ($this->baseAngle == null) {
			$this->baseAngle = new Angle($this->baseAngleDegrees);
		}
		return $this->baseAngle;
	}

    public function getBaseAngleDegrees(): ?float
    {
        return $this->baseAngleDegrees;
    }

    public function setBaseAngleDegrees(float $baseAngleDegrees): static
    {
        $this->baseAngleDegrees = $baseAngleDegrees;

        return $this;
    }

    public function isTurret(): ?bool
    {
        return $this->isTurret;
    }

    public function setIsTurret(bool $isTurret): static
    {
        $this->isTurret = $isTurret;

        return $this;
    }

    public function isParallel(): ?bool
    {
        return $this->isParallel;
    }

    public function setIsParallel(bool $isParallel): static
    {
        $this->isParallel = $isParallel;

        return $this;
    }

    public function isUnder(): ?bool
    {
        return $this->isUnder;
    }

    public function setIsUnder(bool $isUnder): static
    {
        $this->isUnder = $isUnder;

        return $this;
    }

    public function getOutfit(): ?Outfit
    {
        return $this->outfit;
    }

    public function setOutfit(?Outfit $outfit): static
    {
        $this->outfit = $outfit;

        return $this;
    }

    public function getShip(): ?Ship
    {
        return $this->ship;
    }

    public function setShip(?Ship $ship): static
    {
        $this->ship = $ship;

        return $this;
    }
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['point'] = ['x'=>$this->point->X(), 'y'=>$this->point->Y()];
		$jsonArray['baseAngle'] = $this->baseAngle->getDegrees();
		$jsonArray['isTurret'] = $this->isTurret;
		$jsonArray['isParallel'] = $this->isParallel;
		$jsonArray['isUnder'] = $this->isUnder;
		$jsonArray['equippedOutfit'] = $this->outfit?->getTrueName();
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
	
	public function setFromJSON(string|array $jsonArray): void {
		if (!is_array($jsonArray)) {
			$jsonArray = json_decode($jsonArray, true);
		}
		
		$this->point = new Point($jsonArray['point']['x'], $jsonArray['point']['y']);
		$this->baseAngle = new Angle($jsonArray['baseAngle']);
		$this->isTurret = $jsonArray['isTurret'];
		$this->isParallel = $jsonArray['isParallel'];
		$this->isUnder = $jsonArray['isUnder'];
		if ($jsonArray['equippedOutfit']) {
			$this->outfit = GameData::Outfits()[$jsonArray['equippedOutfit']];
		}
	}
}
