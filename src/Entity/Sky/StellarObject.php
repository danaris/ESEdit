<?php

namespace App\Entity\Sky;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use ApiPlatform\Metadata\ApiResource;

use App\Entity\DataNode;

/**
 * A planet, star, moon, or station within a system
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'StellarObject')]
#[ApiResource]
class StellarObject extends Body {
	// #[ORM\Id]
	// #[ORM\GeneratedValue]
	// #[ORM\Column(type: 'integer')]
	// public int $id;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Planet')]
	#[ORM\JoinColumn(nullable: true, name: 'planetId')]
	public ?Planet $planet = null;
	
	#[ORM\Column(type: 'float')]
	public float $distance = 0.0;
	#[ORM\Column(type: 'float')]
	public float $speed = 0.0;
	#[ORM\Column(type: 'float', name: 'angleOffset')]
	public float $offset = 0.0;
	public array $hazards = []; // vector<RandomEvent<Hazard>>
	#[ORM\Column(type: 'integer', name: 'objectIndex')]
	public int $index = -1;
	
	#[ORM\Column(type: 'text')]
	public string $message = '';
	#[ORM\Column(type: 'boolean')]
	public bool $isStar = false;
	#[ORM\Column(type: 'boolean')]
	public bool $isStation = false;
	#[ORM\Column(type: 'boolean')]
	public bool $isMoon = false;
	
	#[ORM\Column(type: 'integer', name: 'parentIndex')]
	private int $parentIndex = -1;

    private ?self $parent = null;

    private ?array $children = null;
	
	#[ORM\ManyToOne(targetEntity: System::class, inversedBy: 'objects')]
	private ?System $system = null;
	
	// Object default constructor.
	public function __construct() {
		parent::__construct();
		// Unlike ships and projectiles, stellar objects are not drawn shrunk to half size,
		// because they do not need to be so sharp.
		$this->zoom = 2.;
	}
	
	public function getId(): int {
		return $this->id;
	}
	
	public function setId(int $id): void {
		$this->id = $id;
	}
	
	public function getDistance(): float {
		return $this->distance;
	}
	
	public function setDistance(float $distance): void {
		$this->distance = $distance;
	}
	
	public function getSpeed(): float {
		return $this->speed;
	}
	
	public function setSpeed(float $speed): void {
		$this->speed = $speed;
	}
	
	public function getOffset(): float {
		return $this->offset;
	}
	
	public function setOffset(float $offset): void {
		$this->offset = $offset;
	}
	
	public function getMessage(): string {
		return $this->message;
	}
	
	public function setMessage(string $message): void {
		$this->message = $message;
	}
	// 
	// // Get the radius of this planet, i.e. how close you must be to land.
	// double StellarObject::Radius() const
	// {
	// 	double radius = -1.;
	// 	if(HasSprite())
	// 		radius = .5 * min(Width(), Height());
	// 
	// 	// Special case: stars may have a huge cloud around them, but only count the
	// 	// core of the cloud as part of the radius.
	// 	if(isStar)
	// 		radius = min(radius, 80.);
	// 
	// 	return radius;
	// }
	// 
	// 
	// 
	public function hasValidPlanet(): bool {
		return $this->planet != null && $this->planet->isValid();
	}
	// 
	// 
	// 
	public function getPlanet(): ?Planet {
		return $this->planet;
	}
	// 
	// 
	// 
	// // Only planets that you can land on have names.
	// const string &StellarObject::Name() const
	// {
	// 	static const string UNKNOWN = "???";
	// 	return (planet && !planet->Name().empty()) ? planet->Name() : UNKNOWN;
	// }
	// 
	// 
	// 
	// // If it is impossible to land on this planet, get the message
	// // explaining why (e.g. too hot, too cold, etc.).
	// const string &StellarObject::LandingMessage() const
	// {
	// 	// Check if there's a custom message for this sprite type.
	// 	if(GameData::HasLandingMessage(GetSprite()))
	// 		return GameData::LandingMessage(GetSprite());
	// 
	// 	static const string EMPTY;
	// 	return (message ? *message : EMPTY);
	// }
	// 
	// 
	// 
	// // Get the color to be used for displaying this object.
	// int StellarObject::RadarType(const Ship *ship) const
	// {
	// 	if(IsStar())
	// 		return Radar::STAR;
	// 	else if(!planet || !planet->IsAccessible(ship))
	// 		return Radar::INACTIVE;
	// 	else if(planet->IsWormhole())
	// 		return Radar::ANOMALOUS;
	// 	else if(GameData::GetPolitics().HasDominated(planet))
	// 		return Radar::PLAYER;
	// 	else if(planet->CanLand())
	// 		return Radar::FRIENDLY;
	// 	else if(!planet->GetGovernment()->IsEnemy())
	// 		return Radar::UNFRIENDLY;
	// 
	// 	return Radar::HOSTILE;
	// }
	// 
	// 
	// 
	// // Check if this is a star.
	// bool StellarObject::IsStar() const
	// {
	// 	return isStar;
	// }
	// 
	// 
	// 
	// // Check if this is a station.
	// bool StellarObject::IsStation() const
	// {
	// 	return isStation;
	// }
	// 
	// 
	// 
	// // Check if this is a moon.
	// bool StellarObject::IsMoon() const
	// {
	// 	return isMoon;
	// }
	// 
	// 
	// 
	// // Get this object's parent index (in the System's vector of objects).
	// int StellarObject::Parent() const
	// {
	// 	return parent;
	// }
	// 
	// 
	// 
	// // Find out how far this object is from its parent.
	// double StellarObject::Distance() const
	// {
	// 	return distance;
	// }
	// 
	// 
	// 
	// const vector<RandomEvent<Hazard>> &StellarObject::Hazards() const
	// {
	// 	return hazards;
	// }
	
	public function setSystem(?System $system): void {
		$this->system = $system;
	}
	
	public function getSystem(): ?System {
		return $this->system;
	}
	
	public function getIndex(): int {
		return $this->index;
	}
	public function setIndex(int $index): void {
		$this->index = $index;
	}

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }
	
	public function getParentIndex(): int {
		return $this->parentIndex;
	}
	public function setParentIndex(int $parentIndex): void {
		$this->parentIndex = $parentIndex;
	}

    public function getChildren(): array {
		if ($this->children == null) {
			$this->initChildren();
		}
        return $this->children;
    }

	private function initChildren(): void {
		if ($this->children) {
			return;
		}
		if ($this->system) {
			$systemObjects = $this->system->getObjectsByIndex();
			foreach ($systemObjects as $index => $Object) {
				if ($Object->parentIndex != -1) {
					$Parent = $systemObjects[$Object->parentIndex];
					$Parent->addChild($Object);
				}
				if ($Object->children == null) {
					$Object->children = [];
				}
			}
		}
	}

    public function addChild(self $child): self {
		if (!$this->children) {
			$this->children = [];
		}
        if (!in_array($child, $this->children)) {
			//error_log("%- Adding ".$child->sprite?->getName().' as child of '.$this->sprite?->getName());
            $this->children []= $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($index = array_search($child, $this->children)) {
			array_splice($this->children, $index, 1);
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		parent::toDatabase($eventArgs);
		if ($this->parent && $this->parent->index != $this->parentIndex) {
			$this->parentIndex = $this->parent->index;
		}
		if ($this->children) {
			foreach ($this->children as $ChildObject) {
				if ($ChildObject->parentIndex != $this->index) {
					$ChildObject->parentIndex = $this->index;
				}
			}
		}
	}
	
	// #[ORM\PostLoad]
	// public function fromDatabase(PostLoadEventArgs $eventArgs) {
	//	parent::fromDatabase($eventArgs);
	// 	if ($this->parentIndex != -1 && $this->system) {
	// 		$systemObjects = $this->system->getObjectsByIndex();
	// 		$Parent = $systemObjects[$this->parentIndex];
	// 		$Parent->addChild($this);
	// 	}
	// }
	
	public function toJSON(bool $justArray = false): array|string {
		$jsonArray = parent::toJSON(true);
		
		//error_log('--% Setting object basic data for '.$this->sprite?->getName());
		$jsonArray['planet'] = $this->planet?->getName();
		$jsonArray['distance'] = $this->distance;
		$jsonArray['speed'] = $this->speed;
		$jsonArray['offset'] = $this->offset;
		$jsonArray['index'] = $this->index;
		$jsonArray['isStar'] = $this->isStar;
		$jsonArray['isStation'] = $this->isStation;
		$jsonArray['isMoon'] = $this->isMoon;
		$jsonArray['parentIndex'] = $this->parent ? $this->parent->index : null;
		//error_log('--% Setting children for '.$this->sprite?->getName());
		$jsonArray['children'] = [];
		if ($this->children == null) {
			$this->initChildren();
		}
		foreach ($this->children as $ChildObject) {
			if ($ChildObject->getParent() == null || $ChildObject->getParent() == $ChildObject) {
				continue;
			}
			$jsonArray['children'] []= $ChildObject->toJSON(true);
		}
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}

}