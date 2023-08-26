<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'Fleet')]
class Fleet {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string')]
	private string $fleetName = '';
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Government')]
	#[ORM\JoinColumn(nullable: true)]
	private ?Government $government = null;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\Phrase', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true)]
	private ?Phrase $names = null;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\Phrase', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true)]
	private ?Phrase $fighterNames = null;
	private array $variants = []; //WeightedList<Variant>
	// The cargo ships in this fleet will carry.
	private FleetCargo $cargo;
	
	private Personality $personality;
	private Personality $fighterPersonality;
	
	// public:
	// 	Fleet() = default;
	// 	// Construct and Load() at the same time.
	// 	Fleet(const DataNode &node);
	// 
	// 	void Load(const DataNode &node);
	// 
	// 	// Determine if this fleet template uses well-defined data.
	// 	bool IsValid(bool requireGovernment = true) const;
	// 	// Ensure any variant selected during gameplay will have at least one ship to spawn.
	// 	void RemoveInvalidVariants();
	// 
	
	public function getName(): string {
		return $this->fleetName;
	}
	
	// Get the government of this fleet.
	public function getGovernment(): ?Government {
		return $this->government;
	}
	// 
	// 	// Choose a fleet to be created during flight, and have it enter the system via jump or planetary departure.
	// 	void Enter(const System &system, list<shared_ptr<Ship>> &ships, const Planet *planet = nullptr) const;
	// 	// Place a fleet in the given system, already "in action." If the carried flag is set, only
	// 	// uncarried ships will be added to the list (as any carriables will be stored in bays).
	// 	void Place(const System &system, list<shared_ptr<Ship>> &ships,
	// 			bool carried = true, bool addCargo = true) const;
	// 
	// 	// Do the randomization to make a ship enter or be in the given system.
	// 	// Return the system that was chosen for the ship to enter from.
	// 	static const System *Enter(const System &system, Ship &ship, const System *source = nullptr);
	// 	static void Place(const System &system, Ship &ship);
	// 
	// 	int64_t Strength() const;
	// 
	// 
	// private:
	// 	static pair<Point, double> ChooseCenter(const System &system);
	// 	vector<shared_ptr<Ship>> Instantiate(const vector<const Ship *> &ships) const;
	// 	bool PlaceFighter(shared_ptr<Ship> fighter, vector<shared_ptr<Ship>> &placed) const;
	// 
	// 
	// private:

}