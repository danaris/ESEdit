<?php

namespace App\Entity\Sky;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'GameEvent')]
#[ORM\HasLifecycleCallbacks]
class GameEvent {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column]
	private int $dateInt = 0;
	private Date $date;
	#[ORM\Column(type: 'string')]
	private string $name = '';
	#[ORM\Column(type: 'boolean')]
	private bool $isDisabled = false;
	#[ORM\Column(type: 'boolean')]
	private bool $isDefined = false;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', cascade: ['persist'])]
	private ConditionSet $conditionsToApply;
	private array $systemsToVisit = []; //vector<const System *> 
	private array $planetsToVisit = []; //vector<const Planet *>
	private array $systemsToUnvisit = []; //vector<const System *>
	private array $planetsToUnvisit = []; //vector<const Planet *>
	
	#[ORM\Column(type: 'string')]
	private string $sourceName = '';
	#[ORM\Column(type: 'string')]
	private string $sourceFile = '';
	#[ORM\Column(type: 'string')]
	private string $sourceVersion = '';

    #[ORM\OneToMany(mappedBy: 'changeEvent', targetEntity: DataNode::class, cascade: ['persist'])]
    private Collection $changes;

    #[ORM\Column(type: Types::TEXT)]
    private string $systemsToVisitStr = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $planetsToVisitStr = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $systemsToUnvisitStr = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $planetsToUnvisitStr = '';

    public function __construct()
    {
        $this->changes = new ArrayCollection();
		$this->conditionsToApply = new ConditionSet();
		$this->date = new Date(0, 0, 0);
    }
	
	private const DEFINITION_NODES = ["fleet",
		"galaxy",
		"government",
		"outfitter",
		"news",
		"planet",
		"shipyard",
		"system",
		"substitutions",
		"wormhole"
	];
	
	public function load(DataNode $node) {
		// If the event has a name, a condition should be automatically created that
		// represents the fact that this event has occurred.
		if ($node->size() >= 2)
		{
			$this->name = $node->getToken(1);
			$this->conditionsToApply->add(firstToken: "set", secondToken:"event: " . $this->name);
		}
		$this->isDefined = true;
		if ($node->getSourceName()) {
			$this->sourceName = $node->getSourceName();
			$this->sourceFile = $node->getSourceFile();
			$this->sourceVersion = $node->getSourceVersion();
		}
		
		$allowed = self::DEFINITION_NODES;
		$allowed []= 'link';
		$allowed []= 'unlink';
		
		foreach ($node as $child) {
			$key = $child->getToken(0);
			if ($key == "date" && $child->size() >= 4) {
				$this->date = new Date($child->getValue(1), $child->getValue(2), $child->getValue(3));
			} else if ($key == "unvisit" && $child->size() >= 2) {
				$this->systemsToUnvisit []= GameData::Systems()[$child->getToken(1)];
			} else if ($key == "visit" && $child->size() >= 2) {
				$this->systemsToVisit []= GameData::Systems()[$child->getToken(1)];
			} else if ($key == "unvisit planet" && $child->size() >= 2) {
				$this->planetsToUnvisit []= GameData::Planets()[$child->getToken(1)];
			} else if ($key == "visit planet" && $child->size() >= 2) {
				$this->planetsToVisit []= GameData::Planets()[$child->getToken(1)];
			} else if(in_array($key, $allowed)) {
				$child->setIsChanges(true, true);
				$this->changes []= $child;
			} else {
				$this->conditionsToApply->add($child);
			}
		}
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function setName(string $name): void {
		$this->name = $name;
	}
	
	public function getSourceName(): string {
		return $this->sourceName;
	}
	public function setSourceName(string $sourceName): self {
		$this->sourceName = $sourceName;
		return $this;
	}
	
	public function getSourceFile(): string {
		return $this->sourceFile;
	}
	public function setSourceFile(string $sourceFile): self {
		$this->sourceFile = $sourceFile;
		return $this;
	}
	
	public function getSourceVersion(): string {
		return $this->sourceVersion;
	}
	public function setSourceVersion(string $sourceVersion): self {
		$this->sourceVersion = $sourceVersion;
		return $this;
	}
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->dateInt = $this->date->getDate();
		$vSystemNames = [];
		foreach ($this->systemsToVisit as $System) {
			$vSystemNames []= $System->getName();
		}
		$this->systemsToVisitStr = json_encode($vSystemNames);
		$uSystemNames = [];
		foreach ($this->systemsToUnvisit as $System) {
			$uSystemNames []= $System->getName();
		}
		$this->systemsToUnvisitStr = json_encode($uSystemNames);
		$vPlanetNames = [];
		foreach ($this->planetsToVisit as $Planet) {
			$vPlanetNames []= $Planet->getName();
		}
		$this->planetsToVisitStr = json_encode($vPlanetNames);
		$uPlanetNames = [];
		foreach ($this->planetsToUnvisit as $Planet) {
			$uPlanetNames []= $Planet->getName();
		}
		$this->planetsToUnvisitStr = json_encode($uPlanetNames);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->date = new Date($this->dateInt, 0, 0);
		$vSystemNames = json_decode($this->systemsToVisitStr, true);
		foreach ($vSystemNames as $systemName) {
			$this->systemsToVisit []= GameData::Systems()[$systemName];
		}
		$uSystemNames = json_decode($this->systemsToUnvisitStr, true);
		foreach ($uSystemNames as $systemName) {
			$this->systemsToUnvisit []= GameData::Systems()[$systemName];
		}
		$vPlanetNames = json_decode($this->planetsToVisitStr, true);
		foreach ($vPlanetNames as $planetName) {
			$this->planetsToVisit []= GameData::Planets()[$planetName];
		}
		$uPlanetNames = json_decode($this->planetsToUnvisitStr, true);
		foreach ($uPlanetNames as $planetName) {
			$this->planetsToUnvisit []= GameData::Planets()[$planetName];
		}
	}
	
	public function toJSON(bool $justArray=false) {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		$jsonArray['name'] = $this->name;
		$jsonArray['date'] = $this->dateInt;
		$jsonArray['isDisabled'] = $this->isDisabled;
		$jsonArray['isDefined'] = $this->isDefined;
		
		$jsonArray['conditionsToApply'] = $this->conditionsToApply->toJSON(true);
		
		$jsonArray['changes'] = [];
		foreach ($this->changes as $ChangeNode) {
			$jsonArray['changes'] []= $ChangeNode->toJSON(true);
		}
		
		$jsonArray['source'] = ['name'=>$this->sourceName,'file'=>$this->sourceFile,'version'=>$this->sourceVersion];
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}

    /**
     * @return Collection<int, DataNode>
     */
    public function getChanges(): Collection
    {
        return $this->changes;
    }

    public function addChange(DataNode $change): static
    {
        if (!$this->changes->contains($change)) {
            $this->changes->add($change);
            $change->setChangeEvent($this);
        }

        return $this;
    }

    public function removeChange(DataNode $change): static
    {
        if ($this->changes->removeElement($change)) {
            // set the owning side to null (unless already changed)
            if ($change->getChangeEvent() === $this) {
                $change->setChangeEvent(null);
            }
        }

        return $this;
    }

    public function getSystemsToVisitStr(): ?string
    {
        return $this->systemsToVisitStr;
    }

    public function setSystemsToVisitStr(string $systemsToVisitStr): static
    {
        $this->systemsToVisitStr = $systemsToVisitStr;

        return $this;
    }

    public function getPlanetsToVisitStr(): ?string
    {
        return $this->planetsToVisitStr;
    }

    public function setPlanetsToVisitStr(string $planetsToVisitStr): static
    {
        $this->planetsToVisitStr = $planetsToVisitStr;

        return $this;
    }

    public function getSystemsToUnvisitStr(): ?string
    {
        return $this->systemsToUnvisitStr;
    }

    public function setSystemsToUnvisitStr(string $systemsToUnvisitStr): static
    {
        $this->systemsToUnvisitStr = $systemsToUnvisitStr;

        return $this;
    }

    public function getPlanetsToUnvisitStr(): ?string
    {
        return $this->planetsToUnvisitStr;
    }

    public function setPlanetsToUnvisitStr(string $planetsToUnvisitStr): static
    {
        $this->planetsToUnvisitStr = $planetsToUnvisitStr;

        return $this;
    }
}