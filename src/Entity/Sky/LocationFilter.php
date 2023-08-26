<?php

namespace App\Entity\Sky;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;
use App\Entity\DataWriter;

#[ORM\Entity]
#[ORM\Table(name: 'LocationFilter')]
#[ORM\HasLifecycleCallbacks]
class LocationFilter {
	#[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;
	
	#[ORM\Column(type: 'boolean', name: 'isEmpty')]
    private bool $isEmpty = true;
	
	// The planet must satisfy these conditions:
	private array $planets = []; //set<const Planet *>
	// It must have at least one attribute from each set in this list:
	#[ORM\Column(type: 'string', name: 'attributesSetString')]
    private string $attributesSetString = '';
	private array $attributes = []; //list<set<string>>
	
	// The system must satisfy these conditions:
	//private array $systems = []; //set<const System *>
	//private array $governments = []; //set<const Government *>
	#[ORM\ManyToMany(targetEntity: System::class)]
	private Collection $systems;
	#[ORM\ManyToMany(targetEntity: Government::class)]
	private Collection $governments;
	
	// The reference point and distance limits of a "near <system>" filter.
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\System')]
    #[ORM\JoinColumn(nullable: true, name: 'centerId')]
    private ?System $center = null;
	#[ORM\Column(type: 'integer', name: 'centerMinDistance')]
    private int $centerMinDistance = 0;
	
	#[ORM\Column(type: 'integer', name: 'centerMaxDistance')]
    private int $centerMaxDistance = 1;
	
	#[ORM\Column(type: 'string', name: 'centerDistanceString')]
    private string $centerDistanceString = '';
	private DistanceCalculationSettings $centerDistanceOptions;
	
	// Distance limits used in a "distance" filter.
	#[ORM\Column(type: 'integer', name: 'originMinDistance')]
    private int $originMinDistance = 0;
	
	#[ORM\Column(type: 'integer', name: 'originMaxDistance')]
    private int $originMaxDistance = -1;
	
	#[ORM\Column(type: 'string', name: 'originDistanceString')]
    private string $originDistanceString = '';
	private DistanceCalculationSettings $originDistanceOptions;
	
	// At least one of the outfits from each set must be available
	// (to purchase or plunder):
	private array $outfits = []; //list<set<const Outfit *>>
	// A ship must belong to one of these categories:
	#[ORM\Column(type: 'string', name: 'shipCategoryString')]
    private string $shipCategoryString = '';
	private array $shipCategory = [];

	// These filters store all the things the planet, system, or ship must not be.
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'notFilters', cascade: ['persist'])]
    private ?self $filterNot = null;

    #[ORM\OneToMany(mappedBy: 'filterNot', targetEntity: self::class, cascade: ['persist'])]
    private Collection $notFilters;

	// These filters store all the things the planet or system must border.
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'neighborFilters', cascade: ['persist'])]
    private ?self $filterNeighbors = null;

    #[ORM\OneToMany(mappedBy: 'filterNeighbors', targetEntity: self::class, cascade: ['persist'])]
    private Collection $neighborFilters; //set<string>
	
	public function __construct() {
    	$this->centerDistanceOptions = new DistanceCalculationSettings();
    	$this->originDistanceOptions = new DistanceCalculationSettings();
        $this->systems = new ArrayCollection();
        $this->governments = new ArrayCollection();
        $this->notFilters = new ArrayCollection();
        $this->neighborFilters = new ArrayCollection();
    }
	
	public function load(DataNode $node): void {
    	foreach ($node as $child) {
    		// Handle filters that must not match, or must apply to a
    		// neighboring system. If the token is alone on a line, it
    		// introduces many lines of this type of filter. Otherwise, this
    		// child is a normal LocationFilter line.
    		if ($child->getToken(0) == "not" || $child->getToken(0) == "neighbor") {
    			$filters = (($child->getToken(0) == "not") ? 'notFilters' : 'neighborFilters');
    			$newFilter = new LocationFilter();
    			$this->$filters []= $newFilter;
    			if ($child->size() == 1) {
    				$newFilter->load($child);
    			} else {
    				$newFilter->loadChild($child);
    			}
    		} else {
    			$this->loadChild($child);
    		}
    	}
    
    	$this->isEmpty = count($this->planets) == 0 && count($this->attributes) == 0 && count($this->systems) == 0 && count($this->governments) == 0
    		&& !$this->center && $this->originMaxDistance < 0 && count($this->notFilters) == 0 && count($this->neighborFilters) == 0
    		&& count($this->outfits) == 0 && count($this->shipCategory) == 0;
    }
	
	// Load one particular line of conditions.
	public function loadChild(DataNode $child) {
    	$isNot = ($child->getToken(0) == "not" || $child->getToken(0) == "neighbor");
    	$valueIndex = 1 + ($isNot ? 1 : 0);
    	$key = $child->getToken($valueIndex - 1);
    	if ($key == "not" || $key == "neighbor") {
    		$child->printTrace("Error: Skipping unsupported use of 'not' and 'neighbor'. These keywords must be nested if used together.");
    	} else if ($key == "planet") {
    		for ($i = $valueIndex; $i < $child->size(); ++$i) {
    			$this->planets []= GameData::Planets()[$child->getToken($i)];
    		}
    		foreach ($child as $grand) {
    			for ($i = 0; $i < $grand->size(); $i++) {
    				$this->planets []= GameData::Planets()[$grand->getToken($i)];
    			}
    		}
    	} else if ($key == "system") {
    		for ($i = $valueIndex; $i < $child->size(); $i++) {
    			$this->systems []= GameData::Systems()[$child->getToken($i)];
    		}
    		foreach ($child as $grand) {
    			for ($i = 0; $i < $grand->size(); $i++) {
    				$this->systems []= GameData::Systems()[$grand->getToken($i)];
    			}
    		}
    	} else if ($key == "government") {
    		for ($i = $valueIndex; $i < $child->size(); $i++) {
    			$this->governments []= GameData::Governments()[$child->getToken($i)];
    		}
    		foreach ($child as $grand) {
    			for ($i = 0; $i < $grand->size(); $i++) {
    				$this->governments []= GameData::Governments()[$grand->getToken($i)];
    			}
    		}
    	} else if ($key == "attributes") {
    		$theseAttrs = array();
    		for ($i = $valueIndex; $i < $child->size(); $i++) {
    			$theseAttrs []= $child->getToken($i);
    		}
    		foreach ($child as $grand) {
    			for ($i = 0; $i < $grand->size(); $i++) {
    				$theseAttrs []= $grand->getToken($i);
    			}
    		}
    		// Don't allow empty attribute sets; that's probably a typo.
    		if (count($theseAttrs) > 0) {
    			$this->attributes []= $theseAttrs;
    		}
    	} else if ($key == "near" && $child->size() >= 1 + $valueIndex) {
    		$this->center = GameData::Systems()[$child->getToken($valueIndex)];
    		if ($child->size() == 2 + $valueIndex) {
    			$this->centerMaxDistance = $child->getValue(1 + $valueIndex);
    		} else if ($child->size() == 3 + $valueIndex) {
    			$this->centerMinDistance = $child->getValue(1 + $valueIndex);
    			$this->centerMaxDistance = $child->getValue(2 + $valueIndex);
    		}
    
    		if ($child->hasChildren()) {
    			$this->centerDistanceOptions->load($child);
    		}
    	} else if ($key == "distance" && $child->size() >= 1 + $valueIndex) {
    		if ($child->size() == 1 + $valueIndex) {
    			$this->originMaxDistance = $child->getValue($valueIndex);
    		} else if ($child->size() == 2 + $valueIndex) {
    			$this->originMinDistance = $child->getValue($valueIndex);
    			$this->originMaxDistance = $child->getValue(1 + $valueIndex);
    		}
    
    		if ($child->hasChildren()) {
    			$this->originDistanceOptions->load($child);
    		}
    	} else if ($key == "category" && $child->size() >= 2 + intval($isNot)) {
    		// Ship categories cannot be combined in an "and" condition.
    		for ($i = 1 + intval($isNot); $i < count($child->getTokens()); $i++) {
    			$this->shipCategory []= $child->getTokens()[$i];
    		}
    		foreach ($child as $grand) {
    			foreach ($grand->getTokens() as $token) {
    				$this->shipCategory []= $token;
    			}
    		}
    	} else if ($key == "outfits" && $child->size() >= 2 + intval($isNot)) {
    		$theseOutfits = [];
    		for ($i = 1 + intval($isNot); $i < $child->size(); ++$i) {
    			$theseOutfits []= GameData::Outfits()[$child->getToken($i)];
    		}
    		foreach ($child as $grand) {
    			for ($i = 0; $i < $grand->size(); $i++) {
    				$theseOutfits []= GameData::Outfits()[$grand->getToken($i)];
    			}
    		}
    		// Don't allow empty outfit sets; that's probably a typo.
    		if (count($theseOutfits) > 0) {
    			$this->outfits []= $theseOutfits;
    		}
    	} else {
    		$child->printTrace("Skipping unrecognized attribute:");
    	}
    }
	
	public function save(DataWriter $out): void {
    	$out->beginChild();
    	//{
    		foreach ($this->notFilters as $filter) {
    			$out->write("not");
    			$filter->save($out);
    		}
    		foreach ($this->neighborFilters as $filter) {
    			$out->write("neighbor");
    			$filter->save($out);
    		}
    		if (count($this->planets) > 0) {
    			$out->write("planet");
    			$out->beginChild();
    			//{
    				foreach ($this->planets as $planet) {
    					$out->write($planet->getTrueName());
    				}
    			//}
    			$out->endChild();
    		}
    		if (count($this->systems) > 0) {
    			$out->write("system");
    			$out->beginChild();
    			//{
    				foreach ($this->systems as $system) {
    					$out->write($system->getName());
    				}
    			//}
    			$out->endChild();
    		}
    		if (count($this->governments) > 0) {
    			$out->write("government");
    			$out->beginChild();
    			//{
    				foreach ($this->governments as $government) {
    					$out->write($government->getTrueName());
    				}
    			//}
    			$out->endChild();
    		}
    		foreach ($this->attributes as $attrSet) {
    			$out->write("attributes");
    			$out->beginChild();
    			//{
    				foreach ($attrSet as $attrName) {
    					$out->write($attrName);
    				}
    			//}
    			$out->endChild();
    		}
    		foreach ($this->outfits as $outfitSet) {
    			$out->write("outfits");
    			$out->beginChild();
    			//{
    				foreach ($outfitSet as $outfit) {
    					$out->write($outfit->getTrueName());
    				}
    			//}
    			$out->endChild();
    		}
    		if (count($this->shipCategory) > 0) {
    			$out->write("category");
    			$out->beginChild();
    			//{
    				foreach ($this->shipCategory as $category) {
    					$out->write($category);
    				}
    			//}
    			$out->endChild();
    		}
    		if ($this->center) {
    			$out->write(["near", $this->center->getName(), $this->centerMinDistance, $this->centerMaxDistance]);
    		}
    	//}
    	$out->endChild();
    }
	
	// Check if this filter contains any specifications.
	public function isEmpty(): bool {
    	return $this->isEmpty;
    }

    /**
     * @return Collection<int, System>
     */
    public function getSystems(): Collection
    {
        return $this->systems;
    }

    public function addSystem(System $system): self
    {
        if (!$this->systems->contains($system)) {
            $this->systems->add($system);
        }

        return $this;
    }

    public function removeSystem(System $system): self
    {
        $this->systems->removeElement($system);

        return $this;
    }

    /**
     * @return Collection<int, Government>
     */
    public function getGovernments(): Collection
    {
        return $this->governments;
    }

    public function addGovernment(Government $government): self
    {
        if (!$this->governments->contains($government)) {
            $this->governments->add($government);
        }

        return $this;
    }

    public function removeGovernment(Government $government): self
    {
        $this->governments->removeElement($government);

        return $this;
    }

    public function getFilterNot(): ?self
    {
        return $this->filterNot;
    }

    public function setFilterNot(?self $filterNot): self
    {
        $this->filterNot = $filterNot;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getNotFilters(): Collection
    {
        return $this->notFilters;
    }

    public function addNotFilter(self $notFilter): self
    {
        if (!$this->notFilters->contains($notFilter)) {
            $this->notFilters->add($notFilter);
            $notFilter->setFilterNot($this);
        }

        return $this;
    }

    public function removeNotFilter(self $notFilter): self
    {
        if ($this->notFilters->removeElement($notFilter)) {
            // set the owning side to null (unless already changed)
            if ($notFilter->getFilterNot() === $this) {
                $notFilter->setFilterNot(null);
            }
        }

        return $this;
    }

    public function getFilterNeighbors(): ?self
    {
        return $this->filterNeighbors;
    }

    public function setFilterNeighbors(?self $filterNeighbors): self
    {
        $this->filterNeighbors = $filterNeighbors;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getNeighborFilters(): Collection
    {
        return $this->neighborFilters;
    }

    public function addNeighborFilter(self $neighborFilter): self
    {
        if (!$this->neighborFilters->contains($neighborFilter)) {
            $this->neighborFilters->add($neighborFilter);
            $neighborFilter->setFilterNeighbors($this);
        }

        return $this;
    }

    public function removeNeighborFilter(self $neighborFilter): self
    {
        if ($this->neighborFilters->removeElement($neighborFilter)) {
            // set the owning side to null (unless already changed)
            if ($neighborFilter->getFilterNeighbors() === $this) {
                $neighborFilter->setFilterNeighbors(null);
            }
        }

        return $this;
    }
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->attributesSetString = json_encode($this->attributes);
		$this->shipCategoryString = json_encode($this->shipCategory);
		$this->centerDistanceString = DistanceCalculationSettings::StringFromSettings($this->centerDistanceOptions);
		$this->originDistanceString = DistanceCalculationSettings::StringFromSettings($this->originDistanceOptions);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->attributes = json_decode($this->attributesSetString, true);
		$this->shipCategory = json_decode($this->shipCategoryString, true);
		$this->centerDistanceOptions = DistanceCalculationSettings::SettingsFromString($this->centerDistanceString);
		$this->originDistanceOptions = DistanceCalculationSettings::SettingsFromString($this->originDistanceString);
	}
}