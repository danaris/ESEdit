<?php

namespace App\Entity\Sky;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\Sky\SaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;
use App\Entity\TemplatedArray;

use App\Entity\Sky\Ship;

#[ORM\Entity(repositoryClass: SaleRepository::class)]
#[ApiResource]
class Sale implements \Iterator, \ArrayAccess, \Countable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Outfit::class, inversedBy: 'outfitters')]
    private Collection $outfits;

    #[ORM\ManyToMany(targetEntity: Ship::class, inversedBy: 'shipyards')]
    private Collection $ships;
	
	#[ORM\Column(length: 32)]
	private string $type = '';

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Planet::class, mappedBy: 'sales')]
    private Collection $planets;
	
	#[ORM\Column(type: 'string')]
	private string $sourceName = '';
	#[ORM\Column(type: 'string')]
	private string $sourceFile = '';
	#[ORM\Column(type: 'string')]
	private string $sourceVersion = '';

    public function __construct()
    {
        $this->outfits = new ArrayCollection();
        $this->ships = new ArrayCollection();
        $this->planets = new ArrayCollection();
       // $this->outfitPlanets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
	
	public function getContents(): Collection {
		if ($this->type == Ship::class) {
			return $this->ships;
		} else {
			return $this->outfits;
		}
	}

    /**
     * @return Collection<int, Outfit>
     */
    public function getOutfits(): Collection
    {
        return $this->outfits;
    }

    public function addOutfit(Outfit $outfit): static
    {
        if (!$this->outfits->contains($outfit)) {
            $this->outfits->add($outfit);
        }

        return $this;
    }

    public function removeOutfit(Outfit $outfit): static
    {
        $this->outfits->removeElement($outfit);

        return $this;
    }

    /**
     * @return Collection<int, Ship>
     */
    public function getShips(): Collection
    {
        return $this->ships;
    }

    public function addShip(Ship $ship): static
    {
        if (!$this->ships->contains($ship)) {
            $this->ships->add($ship);
        }

        return $this;
    }

    public function removeShip(Ship $ship): static
    {
        $this->ships->removeElement($ship);

        return $this;
    }
	
	public function has(mixed $offset): bool {
		return $this->offsetExists($offset);
	}
	
	// ArrayAccess interface methods
	public function offsetExists(mixed $offset): bool {
		return isset($this->getContents()[$offset]);
	}
	public function offsetGet(mixed $offset): mixed {
		if (!isset($this->getContents()[$offset])) {
			return null;
		}
		return $this->getContents()[$offset];
	}
	public function offsetSet(mixed $offset, mixed $value): void {
		if ($offset === null) {
			$this->getContents() []= $value;
		} else {
			$this->getContents()[$offset] = $value;
		}
	}
	public function offsetUnset(mixed $offset): void {
		$this->getContents()->remove($offset);
	}

	private int $iterIndex = 0;
	
	// Iterator interface methods
	public function current(): mixed {
		return $this->getContents()[$this->getContents()->getKeys()[$this->iterIndex]];
	}
	
	public function key(): string|int {
		return $this->getContents()->getKeys()[$this->iterIndex];
	}
	
	public function next(): void {
		$this->iterIndex++;
	}
	
	public function rewind(): void {
		$this->iterIndex = 0;
	}
	
	public function valid(): bool {
		if ($this->iterIndex < 0) {
			return false;
		}
		if (!isset($this->getContents()->getKeys()[$this->iterIndex])) {
			return false;
		}
		$childKey = $this->getContents()->getKeys()[$this->iterIndex];
		if (isset($this->getContents()[$childKey])) {
			return true;
		}
		
		return false;
	}	
	
	// Countable interface method
	public function count(): int {
		return count($this->getContents());
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
	
	public function load(DataNode $node, TemplatedArray &$items): void
	{
		$this->name = $node->getToken(1);
		if ($node->getSourceName()) {
			$this->sourceName = $node->getSourceName();
			$this->sourceFile = $node->getSourceFile();
			$this->sourceVersion = $node->getSourceVersion();
		}
		if ($items->getType() == Ship::class) {
			$itemType = 'ships';
		} else {
			$itemType = 'outfits';
		}
		$this->type = $items->getType();
		foreach ($node as $child) {
			$token = $child->getToken(0);
			$remove = ($token == "clear" || $token == "remove");
			if ($remove && $child->size() == 1) {
				$this->$itemType->clear();
			} else if ($remove && $child->size() >= 2) {
				$this->$itemType->removeElement($items[$child->getToken(1)]);
			} else if ($token == "add" && $child->size() >= 2) {
				$this->$itemType->add($items[$child->getToken(1)]);
			} else {
				$this->$itemType->add($items[$token]);
			}
		}
	}
	
	public function add(Sale $other): void
	{
		foreach ($other as $OtherItem) {
			$this []= $OtherItem;
		}
	}

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Planet>
     */
    public function getPlanets(): Collection
    {
        return $this->planets;
    }

    public function addPlanet(Planet $planet): static
    {
        if (!$this->planets->contains($planet)) {
            $this->planets->add($planet);
            $planet->addShipyard($this);
        }

        return $this;
    }

    public function removePlanet(Planet $planet): static
    {
        if ($this->planets->removeElement($planet)) {
            $planet->removeShipyard($this);
        }

        return $this;
    }

    // /**
    //  * @return Collection<int, Planet>
    //  */
    // public function getOutfitPlanets(): Collection
    // {
    //     return $this->outfitPlanets;
    // }

    // public function addOutfitPlanet(Planet $outfitPlanet): static
    // {
    //     if (!$this->outfitPlanets->contains($outfitPlanet)) {
    //         $this->outfitPlanets->add($outfitPlanet);
    //         $outfitPlanet->addOutfitter($this);
    //     }

    //     return $this;
    // }

    // public function removeOutfitPlanet(Planet $outfitPlanet): static
    // {
    //     if ($this->outfitPlanets->removeElement($outfitPlanet)) {
    //         $outfitPlanet->removeOutfitter($this);
    //     }

    //     return $this;
    // }
	
	public function getType(): string {
		return $this->type;
	}
	
	public function setType(string $type): void {
		$this->type = $type;
	}
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['name'] = $this->name;
		
		if ($this->type == Ship::class) {
			$contentsType = 'ships';
			$nameMethod = 'getTrueModelName';
		} else {
			$contentsType = 'outfits';
			$nameMethod = 'getTrueName';
		}
		$jsonArray[$contentsType] = [];
		foreach ($this->$contentsType as $Contents) {
			$jsonArray[$contentsType] []= $Contents->$nameMethod();
		}
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
}
