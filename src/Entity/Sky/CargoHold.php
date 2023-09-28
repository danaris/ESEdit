<?php

namespace App\Entity\Sky;

use App\Repository\Sky\CargoHoldRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataWriter;
use App\Entity\DataNode;

#[ORM\Entity(repositoryClass: CargoHoldRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CargoHold
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $size = null;

    #[ORM\Column]
    private ?int $bunks = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $commoditiesStr = null;
	private array $commodities = [];

    #[ORM\Column(type: Types::TEXT)]
    private ?string $outfitsStr = null;
	private array $outfits = [];
	
	private $missionCargo;
	private $missionPassengers;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getBunks(): ?int
    {
        return $this->bunks;
    }

    public function setBunks(int $bunks): static
    {
        $this->bunks = $bunks;

        return $this;
    }

    public function getCommodities(): array
    {
        return $this->commodities;
    }

    public function setCommodities(string $commodities): static
    {
        $this->commodities = $commodities;

        return $this;
    }

    public function getOutfitsStr(): ?string
    {
        return $this->outfitsStr;
    }

    public function setOutfitsStr(string $outfitsStr): static
    {
        $this->outfitsStr = $outfitsStr;

        return $this;
    }

	public function getOutfits(): array {
		return $this->outfits;
	}

	public function load(DataNode $node): void {
		// TODO!
	}
	
	// Save the cargo manifest to a file.
	public function save(DataWriter $out): void
	{
		$first = true;
	// 	foreach ($this->commodities as $commodity)
	// 		if(it.second)
	// 		{
	// 			// Only write a "cargo" block if it is not going to be empty.
	// 			if(first)
	// 			{
	// 				out.Write("cargo");
	// 				out.BeginChild();
	// 				out.Write("commodities");
	// 				out.BeginChild();
	// 			}
	// 			first = false;
	// 
	// 			out.Write(it.first, it.second);
	// 		}
	// 	// We only need to EndChild() if at least one line was written above.
	// 	if(!first)
	// 		out.EndChild();
	// 
	// 	// Save all outfits, even ones which have only been referred to.
	// 	bool firstOutfit = true;
	// 	for(const auto &it : outfits)
	// 		if(it.second)
	// 		{
	// 			// It is possible this cargo hold contained no commodities, meaning
	// 			// we must print the opening tag now.
	// 			if(first)
	// 			{
	// 				out.Write("cargo");
	// 				out.BeginChild();
	// 			}
	// 			first = false;
	// 
	// 			// If this is the first outfit to be written, print the opening tag.
	// 			if(firstOutfit)
	// 			{
	// 				out.Write("outfits");
	// 				out.BeginChild();
	// 			}
	// 			firstOutfit = false;
	// 
	// 			out.Write(it.first->TrueName(), it.second);
	// 		}
	// 	// Back out any indentation blocks that are set, depending on what sorts of
	// 	// cargo were written to the file.
	// 	if(!firstOutfit)
	// 		out.EndChild();
	// 	if(!first)
	// 		out.EndChild();
	// 
	// 	// Mission cargo is not saved because it is repopulated when the missions
	// 	// are read rather than when the cargo is read.
	}
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->commoditiesStr = json_encode($this->commodities);
		$this->outfitsStr = json_encode($this->outfits);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->commodities = json_decode($this->commoditiesStr, true);
		$this->outfits = json_decode($this->outfitsStr, true);
	}
}
