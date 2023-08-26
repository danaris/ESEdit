<?php

namespace App\Entity\Sky;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'StellarObject')]
class StellarObject extends Body {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	public int $id;
	
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

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, fetch: 'EAGER')]
    private Collection $children;
	
	#[ORM\ManyToOne(targetEntity: System::class, inversedBy: 'objects')]
	private ?System $system = null;
	
	// TGC added
	//public array $children = [];
	
	// Object default constructor.
	public function __construct() {
		parent::__construct();
		// Unlike ships and projectiles, stellar objects are not drawn shrunk to half size,
		// because they do not need to be so sharp.
		$this->zoom = 2.;
        $this->children = new ArrayCollection();
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
			error_log("%- Adding ".$child->sprite?->getName().' as child of '.$this->sprite?->getName());
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }
	
	public function toJSON(bool $justArray = false): array|string {
		$jsonArray = parent::toJSON(true);
		
		error_log('--% Setting object basic data for '.$this->sprite?->getName());
		$jsonArray['planet'] = $this->planet?->getName();
		$jsonArray['distance'] = $this->distance;
		$jsonArray['speed'] = $this->speed;
		$jsonArray['offset'] = $this->offset;
		$jsonArray['index'] = $this->index;
		$jsonArray['isStar'] = $this->isStar;
		$jsonArray['isStation'] = $this->isStation;
		$jsonArray['isMoon'] = $this->isMoon;
		$jsonArray['parentIndex'] = $this->parent ? $this->parent->index : null;
		error_log('--% Setting children for '.$this->sprite?->getName());
		$jsonArray['children'] = [];
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