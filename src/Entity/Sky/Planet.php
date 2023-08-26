<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;
use App\Entity\TemplatedArray;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'Planet')]
#[ORM\HasLifecycleCallbacks]
class Planet {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'boolean', name: 'isDefined')]
	private bool $isDefined = false;
	
	#[ORM\Column(type: 'string', name: 'name')]
	private string $name = '';
	
	#[ORM\Column(type: 'text', name: 'description')]
	private string $description = '';
	
	#[ORM\Column(type: 'text', name: 'spaceport')]
	private string $spaceport = '';
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sprite', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'landscapeId')]
	private ?Sprite $landscape = null;
	
	#[ORM\Column(type: 'string', name: 'music')]
	private string $music = '';
	
	#[ORM\Column(type: 'text', name: 'attributes')]
	private string $attributesString = '';
	private array $attributes = []; //set<string>
	
	private array $shipSales = []; //set<const Sale<Ship> *>
	private array $outfitSales = []; //set<const Sale<Outfit> *>
	// The lists above will be converted into actual ship lists when they are
	// first asked for:
	private $shipyard; // Sale<Ship>
	private $outfitter; // Sale<Outfit>
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Government', inversedBy: 'governmentPlanet')]
	#[ORM\JoinColumn(nullable: true, name: 'governmentId')]
	private ?Government $government = null;
	
	#[ORM\Column(type: 'float', name: 'requiredReputation')]
	private float $requiredReputation = 0.;
	
	#[ORM\Column(type: 'float', name: 'bribe')]
	private float $bribe = 0.01;
	
	#[ORM\Column(type: 'float', name: 'security')]
	private float $security = .25;
	
	#[ORM\Column(type: 'boolean', name: 'inhabited')]
	private bool $inhabited = false;
	
	#[ORM\Column(type: 'boolean', name: 'customSecurity')]
	private bool $customSecurity = false;
	
	// Any required attributes needed to land on this planet.
	#[ORM\Column(type: 'text', name: 'requiredAttributes')]
	private string $requiredAttributesString = '';
	private array $requiredAttributes = []; // set<string>
	
	// The salary to be paid if this planet is dominated.
	#[ORM\Column(type: 'integer', name: 'tribute')]
	private int $tribute = 0;
	
	// The minimum combat rating needed to dominate this planet.
	#[ORM\Column(type: 'integer', name: 'defenseThreshold')]
	private int $defenseThreshold = 4000;
	
	#[ORM\Column(type: 'boolean', name: 'isDefending')]
	private bool $isDefending = false;
	
	// The defense fleets that should be spawned (in order of specification).
	private array $defenseFleets = []; // vector<const Fleet *>
	// How many fleets have been spawned, and the index of the next to be spawned.
	#[ORM\Column(type: 'integer', name: 'defenseDeployed')]
	private int $defenseDeployed = 0;
	
	// Ships that have been created by instantiating its defense fleets.
	private array $defenders = []; // list<shared_ptr<Ship>>
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Wormhole', inversedBy: 'wormholePlanet')]
	#[ORM\JoinColumn(nullable: true, name: 'wormholeId')]
	private ?Wormhole $wormhole = null;
	
	#[ORM\JoinTable(name: 'planetSystems')]
	#[ORM\JoinColumn(name: 'planetId', referencedColumnName: 'id')]
	#[ORM\InverseJoinColumn(name: 'systemId', referencedColumnName: 'id', unique: false)]
	#[ORM\ManyToMany(targetEntity: 'App\Entity\Sky\System')]
	private Collection $systems; // vector<const System *>
	
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\Mission', mappedBy: 'source')]
	private Collection $sourcedMissions;
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->attributesString = json_encode($this->attributes);
		$this->requiredAttributesString = json_encode($this->requiredAttributes);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->attributes = json_decode($this->attributesString, true);
		$this->requiredAttributes = json_decode($this->requiredAttributesString, true);
	}
	
	const WORMHOLE = "wormhole";
	const PLANET = "planet";

	// Planet attributes in the form "requires: <attribute>" restrict the ability of ships to land
	// unless the ship has all required attributes.
	public static function SetRequiredAttributes(array &$attributes, array &$required): void {
		$PREFIX = "requires: ";
		$PREFIX_END = "requires:!";
		$required = [];
		foreach ($attributes as $attrName) {
			$required []= substr($attrName, 0, strlen($PREFIX));
		}
	}
	
	public function __construct() {
		$this->systems = new ArrayCollection();
	}

	// Load a planet's description from a file.
	public function load(DataNode $node, TemplatedArray &$wormholes): void {
		if ($node->size() < 2) {
			return;
		}
		$this->name = $node->getToken(1);
		// The planet's name is needed to save references to this object, so a
		// flag is used to test whether Load() was called at least once for it.
		$this->isDefined = true;
	
		// If this planet has been loaded before, these sets of items should be
		// reset instead of appending to them:
		$shouldOverwrite = ["attributes", "description", "spaceport"];
	
		foreach ($node as $child) {

			// Check for the "add" or "remove" keyword.
			$add = ($child->getToken(0) == "add");
			$remove = ($child->getToken(0) == "remove");
			if (($add || $remove) && $child->size() < 2) {
				$child->printTrace("Skipping " + $child->getToken(0) + " with no key given:");
				continue;
			}
	
			// Get the key and value (if any).
			$key = $child->getToken(($add || $remove) ? 1 : 0);
			$valueIndex = ($add || $remove) ? 2 : 1;
			$hasValue = ($child->size() > $valueIndex);
			$value = $child->getToken($hasValue ? $valueIndex : 0);
	
			// Check for conditions that require clearing this key's current value.
			// "remove <key>" means to clear the key's previous contents.
			// "remove <key> <value>" means to remove just that value from the key.
			$removeAll = ($remove && !$hasValue);
			// "<key> clear" is the deprecated way of writing "remove <key>."
			$removeAll |= (!$add && !$remove && $hasValue && $value == "clear");
			// If this is the first entry for the given key, and we are not in "$add"
			// or "remove" mode, its previous value should be cleared.
			$overwriteAll = (!$add && !$remove && !$removeAll && isset($shouldOverwrite[$key]));
			// Clear the data of the given type.
			if ($removeAll || $overwriteAll) {
				// Clear the data of the given type.
				if ($key == "music") {
					$this->music = [];
				} else if ($key == "attributes") {
					$this->attributes = [];
				} else if ($key == "description") {
					$this->description = [];
				} else if ($key == "spaceport") {
					$this->spaceport = [];
				} else if ($key == "shipyard") {
					$this->shipSales = [];
				} else if ($key == "outfitter") {
					$this->outfitSales = [];
				} else if ($key == "government") {
					$this->government = nullptr;
				} else if ($key == "required reputation") {
					$this->requiredReputation = 0.;
				} else if ($key == "bribe") {
					$this->bribe = 0.;
				} else if ($key == "security") {
					$this->security = 0.;
				} else if ($key == "tribute") {
					$this->tribute = 0;
				} else if ($key == "wormhole") {
					$this->wormhole = null;
				}
	
				// If not in "overwrite" mode, move on to the next node.
				if ($overwriteAll) {
					unset($shouldOverwrite[$key]);
				} else {
					continue;
				}
			}
	
			// Handle the attributes which can be "removed."
			if (!$hasValue) {
				$child->printTrace("Error: Expected key to have a value:");
				continue;
			} else if ($key == "attributes") {
				if ($remove) {
					for ($i = $valueIndex; $i < $child->size(); ++$i) {
						unset($this->attributes[$child->getToken($i)]);
					}
				} else {
					for ($i = $valueIndex; $i < $child->size(); ++$i) {
						$this->attributes []= $child->getToken($i);
					}
				}
			} else if ($key == "shipyard") {
				// if ($remove) {
				// 	unset($this->shipSales[GameData::Shipyards()[$value]]);
				// } else {
				// 	$this->shipSales []= GameData::Shipyards()[$value];
				// }
			} else if ($key == "outfitter") {
				// if ($remove) {
				// 	unset($this->outfitSales[GameData::Outfitters()[$value]]);
				// } else {
				// 	$this->outfitSales []= GameData::Outfitters()[$value];
				// }
			// Handle the attributes which cannot be "removed."
			} else if ($remove) {
				$child->printTrace("Error: Cannot \"remove\" a specific value from the given key:");
				continue;
			} else if ($key == "landscape") {
				$this->landscape = SpriteSet::Get($value);
			} else if ($key == "music") {
				$this->music = $value;
			} else if ($key == "description" || $key == "spaceport") {
				$text = ($key == "description") ? 'description' : 'spaceport';
				if ($this->$text != '' && $value != '' && mb_ord($value[0]) > mb_ord(' ')) {
					$this->$text .= '	';
				}
				$this->$text .= $value;
				$this->$text .= "\n";
			} else if ($key == "government") {
				$this->government = GameData::Governments()[$value];
			} else if ($key == "required reputation") {
				$this->requiredReputation = $child->getValue($valueIndex);
			} else if ($key == "bribe") {
				$this->bribe = $child->getValue($valueIndex);
			} else if ($key == "security") {
				$this->customSecurity = true;
				$this->security = $child->getValue($valueIndex);
			} else if ($key == "tribute") {
				$this->tribute = $child->getValue($valueIndex);
				$resetFleets = count($this->defenseFleets) > 0;
				foreach ($child as $grand) {
					if ($grand->getToken(0) == "threshold" && $grand->size() >= 2) {
						$this->defenseThreshold = $grand->getValue(1);
					} else if ($grand->getToken(0) == "fleet") {
						if ($grand->size() >= 2 && !$grand->hasChildren()) {
							// Allow only one "tribute" node to define the tribute fleets.
							if ($resetFleets) {
								$this->defenseFleets = [];
								$resetFleets = false;
							}
							$fleet = GameData::Fleets()[$grand->getToken(1)];
							$count = $grand->size() >= 3 ? $grand->getValue(2) : 1;
							for ($i=0; $i<$count; $i++) {
								$this->defenseFleets []= $fleet;
							}
						} else {
							$grand->printTrace("Skipping unsupported tribute fleet definition:");
						}
					} else {
						$grand->printTrace("Skipping unrecognized tribute attribute:");
					}
				}
			} else if ($key == "wormhole") {
				$this->wormhole = $wormholes[$value];
				$this->wormhole->setPlanet($this);
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	
		$AUTO_ATTRIBUTES = ["spaceport", "shipyard", "outfitter"];
		$autoValues = [$this->spaceport != '', count($this->shipSales) > 0, count($this->outfitSales) > 0];
		for ($i = 0; $i < count($AUTO_ATTRIBUTES); ++$i) {
			if ($autoValues[$i]) {
				$this->attributes []= $AUTO_ATTRIBUTES[$i];
			} else {
				unset($this->attributes[array_search($AUTO_ATTRIBUTES[$i], $this->attributes)]);
			}
		}
	
		// Precalculate commonly used values that can only change due to Load().
		$this->inhabited = ($this->hasSpaceport() || $this->requiredReputation || count($this->defenseFleets) > 0) && !in_array("uninhabited", $this->attributes);
		self::SetRequiredAttributes($this->attributes, $this->requiredAttributes);
	}

	// Legacy wormhole do not have an associated Wormhole object so
	// we must auto generate one if we detect such legacy wormhole.
	public function finishLoading(TemplatedArray &$wormholes): void {
		// If this planet is in multiple systems, then it is a wormhole.
		if (!$this->wormhole && count($this->systems) > 1) {
			$this->wormhole = $wormholes[$this->getTrueName()];
			$this->wormhole->loadFromPlanet($this);
			error_log("Warning: deprecated automatic generation of wormhole \"" . $this->name . "\" from a multi-system planet.");
		// If the wormhole was autogenerated we need to update it to
		// match the planet's state.
		} else if ($this->wormhole && $this->wormhole->isAutogenerated()) {
			error_log('Normal generation of wormhole '.$this->name.' from a planet');
			$this->wormhole->loadFromPlanet($this);
		}
	}

	// Test if this planet has been loaded (vs. just referred to). It must also be located in
	// at least one system, and all systems that claim it must themselves be valid.
	public function isValid(): bool {
		$allValid = true;
		foreach ($this->systems as $system) {
			$allValid &= $system->isValid();
		}
		return $this->isDefined && count($this->systems) > 0 && $allValid;
	}

	// Get the name of the planet.
	public function getName(): string {
		return $this->isWormhole() ? $this->wormhole->getName() : $this->name;
	}

	public function setName(string $name): void {
		$this->name = $name;
	}

	// Get the name used for this planet in the data files.
	public function getTrueName(): string {
		return $this->name;
	}

	// Get the planet's descriptive text.
	public function getDescription(): string {
		return $this->description;
	}

	// Get the landscape sprite.
	public function getLandscape(): ?Sprite {
		return $this->landscape;
	}

	// Get the name of the ambient audio to play on this planet.
	public function getMusicName(): string {
		return $this->music;
	}

	// Get the list of "attributes" of the planet.
	public function getAttributes(): array {
		return $this->attributes;
	}

	// Get planet's noun descriptor from attributes
	public function getNoun(): string {
		if ($this->isWormhole()) {
			return self::WORMHOLE;
		}
	
		foreach ($this->attributes as $attribute) {
			if ($attribute == "moon" || $attribute == "station") {
				return $attribute;
			}
		}
	
		return self::PLANET;
	}

	// Check whether there is a spaceport (which implies there is also trading,
	// jobs, banking, and hiring).
	public function hasSpaceport(): bool {
		return $this->spaceport != '';
	}

	// Get the spaceport's descriptive text.
	public function getSpaceportDescription(): string {
		return $this->spaceport;
	}

	// Check if this planet is inhabited (i.e. it has a spaceport, and does not
	// have the "uninhabited" attribute).
	public function isInhabited(): bool {
		return $this->inhabited;
	}

	// Check if this planet has a shipyard.
	public function hasShipyard(): bool {
		return count($this->getShipyard()) > 0;
	}

	// Get the list of ships in the shipyard.
	public function getShipyard(): array {
		$this->shipyard = [];
		foreach ($this->shipSales as $sale) {
			$this->shipyard []= $sale;
		}
	
		return $this->shipyard;
	}

	// Check if this planet has an outfitter.
	public function hasOutfitter(): bool {
		return count($this->getOutfitter()) > 0;
	}

	// Get the list of outfits available from the outfitter.
	public function getOutfitter(): array {
		$this->outfitter = [];
		foreach ($this->outfitSales as $sale) {
			$this->outfitter []= $sale;
		}
	
		return $this->outfitter;
	}

	// Get this planet's government. Most planets follow the government of the system they are in.
	public function getGovernment(): ?Government {
		return $this->government ? $this->government : (count($this->systems) == 0 ? null : $this->getSystem()->getGovernment());
	}

	// You need this good a reputation with this system's government to land here.
	public function getRequiredReputation(): float {
		return $this->requiredReputation;
	}

	// This is what fraction of your fleet's value you must pay as a bribe in
	// order to land on this planet. (If zero, you cannot bribe it.)
	public function getBribeFraction(): float {
		return $this->bribe;
	}

	// This is how likely the planet's authorities are to notice if you are
	// doing something illegal.
	public function getSecurity(): float {
		return $this->security;
	}

	public function hasCustomSecurity(): bool {
		return $this->customSecurity;
	}

	public function getSystem(): ?System {
		return (count($this->systems) == 0 ? null : $this->systems[0]);
	}

	// Check if this planet is in the given system. Note that wormholes may be
	// in more than one system.
	public function isInSystem(System $system): bool {
		return in_array($system, $this->systems->toArray());
	}

	public function setSystem(System $system): void {
		if (!in_array($system, $this->systems->toArray())) {
			$this->systems []= $system;
		}
	}

	// Remove the given system from the list of systems this planet is in. This
	// must be done when game events rearrange the planets in a system.
	public function removeSystem(System $system): void {
		$sysIndex = array_search($system, $this->systems->toArray());
		if ($sysIndex !== false) {
			unset($this->systems[$sysIndex]);
		}
	}

	public function getSystems(): array {
		return $this->systems;
	}

	// Check if this is a wormhole (that is, it appears in multiple systems).
	public function isWormhole(): bool {
		return $this->wormhole != null;
	}

	public function getWormhole(): ?Wormhole {
		return $this->wormhole;
	}

	// Check if the given ship has all the attributes necessary to allow it to
	// land on this planet.
	public function isAccessible(?Ship $ship): bool {
		// If this is a wormhole that leads to an inaccessible system, no ship can land here.
		if ($this->wormhole && $ship && $ship->getSystem() && $this->wormhole->getWormholeDestination($ship->getSystem())->isInaccessible()) {
			return false;
		}
		// If there are no required attributes, then any ship may land here.
		if ($this->isUnrestricted()) {
			return true;
		}
		if (!$ship) {
			return true; // In-game, this would be "return false"; however, not having a ship is the default case here, and if need be, we can add another parameter to handle other cases
		}
	
		$shipAttributes = $ship->getAttributes();
		$allRequired = true;
		foreach ($this->requiredAttributes as $attribute) {
			if (!in_array($attribute, $shipAttributes)) {
				$allRequired = false;
			}
		}
		return $allRequired;
	}

	// Check if this planet has any required attributes that restrict landability.
	public function isUnrestricted(): bool {
		return count($this->requiredAttributes) == 0;
	}

	// Below are convenience functions which access the game state in Politics,
	// but do so with a less convoluted syntax:
	public function hasFuelFor(Ship $ship): bool {
		return !$this->isWormhole() && $this->hasSpaceport() && $this->canLand($ship);
	}

	public function canLand(?Ship $ship = null): bool {
		if ($ship) {
			return $this->isAccessible($ship) && GameData::GetPolitics()->canLand($ship, $this);
		} else {
			return GameData::GetPolitics()->canLand($this);
		}
	}

	public function getFriendliness(): Friendliness {
		if (GameData::GetPolitics()->hasDominated($this)) {
			return Friendliness::DOMINATED;
		} else if ($this->getGovernment() && $this->getGovernment()->isEnemy()) {
			return Friendliness::HOSTILE;
		} else if ($this->canLand()) {
			return Friendliness::FRIENDLY;
		} else {
			return Friendliness::RESTRICTED;
		}
	}

	public function canUseServices(): bool {
		return GameData::GetPolitics()->canUseServices($this);
	}

	public function bribe(bool $fullAccess): void {
		GameData::GetPolitics()->bribePlanet($this, $fullAccess);
	}

// 	// Demand tribute, and get the planet's response.
// 	string Planet::DemandTribute(PlayerInfo &player) const
// 	{
// 		const auto &playerTribute = player.GetTribute();
// 		if (playerTribute.find(this) != playerTribute.end()) {
// 			return "We are already paying you as much as we can afford.";
// 		if (!tribute || defenseFleets.empty()) {
// 			return "Please don't joke about that sort of thing.";
// 		if (player.Conditions().Get("combat rating") < defenseThreshold) {
// 			return "You're not worthy of our time.";
// 	
// 		// The player is scary enough for this planet to take notice. Check whether
// 		// this is the first demand for tribute, or not.
// 		if (!isDefending) {
// 
// 			isDefending = true;
// 			set<const Government *> toProvoke;
// 			for (const auto &fleet : defenseFleets) {
// 				toProvoke.insert(fleet->GetGovernment());
// 			for (const auto &gov : toProvoke) {
// 				gov->Offend(ShipEvent::PROVOKE);
// 			// Terrorizing a planet is not taken lightly by it or its allies.
// 			// TODO: Use a distinct event type for the domination system and
// 			// expose syntax for controlling its impact on the targeted government
// 			// and those that know it.
// 			GetGovernment()->Offend(ShipEvent::ATROCITY);
// 			return "Our defense fleet will make short work of you.";
// 		}
// 	
// 		// The player has already demanded tribute. Have they defeated the entire defense fleet?
// 		bool isDefeated = (defenseDeployed == defenseFleets.size());
// 		for (const shared_ptr<Ship> &ship : defenders) {
// 			if (!ship->IsDisabled() && !ship->IsYours()) {
// 
// 				isDefeated = false;
// 				break;
// 			}
// 	
// 		if (!isDefeated) {
// 			return "We're not ready to surrender yet.";
// 	
// 		player.SetTribute(this, tribute);
// 		return "We surrender. We will pay you " + Format::CreditString(tribute) + " per day to leave us alone.";
// 	}
// 
// 	// While being tributed, attempt to spawn the next specified defense fleet.
// 	void Planet::DeployDefense(list<shared_ptr<Ship>> &ships) const
// 	{
// 		if (!isDefending || Random::Int(60) || defenseDeployed == defenseFleets.size()) {
// 			return;
// 	
// 		auto end = defenders.begin();
// 		defenseFleets[defenseDeployed]->Enter(*GetSystem(), defenders, this);
// 		ships.insert(ships.begin(), defenders.begin(), end);
// 	
// 		// All defenders use a special personality.
// 		Personality defenderPersonality = Personality::Defender();
// 		Personality fighterPersonality = Personality::DefenderFighter();
// 		for (auto it = defenders.begin(); it != end; ++it) {
// 
// 			(**it).SetPersonality(defenderPersonality);
// 			if ((**it).HasBays()) {
// 				for (auto bay = (**it).Bays().begin(); bay != (**it).Bays().end(); ++bay) {
// 					if (bay->ship) {
// 						bay->ship->SetPersonality(fighterPersonality);
// 		}
// 	
// 		++defenseDeployed;
// 	}

	public function resetDefense(): void {
		$this->isDefending = false;
		$this->defenseDeployed = 0;
		$this->defenders = [];
	}
	
	public function toJSON($justArray=false): array|string {
		$jsonArray = ['name'=>$this->name];
		$jsonArray['description'] = $this->description;
		$jsonArray['spaceport'] = $this->spaceport;
		$jsonArray['landscape'] = $this->landscape ? $this->landscape->toJSON(true) : '';
		$jsonArray['music'] = $this->music;
		
		$jsonArray['attributes'] = $this->attributes;
		$jsonArray['shipSales'] = $this->shipSales;
		$jsonArray['outfitSales'] = $this->outfitSales;
		
		$jsonArray['government'] = $this->getGovernment() ? $this->getGovernment()->getTrueName() : null;
		$jsonArray['requiredReputation'] = $this->requiredReputation;
		$jsonArray['bribe'] = $this->bribe;
		$jsonArray['security'] = $this->security;
		$jsonArray['inhabited'] = $this->inhabited;
		$jsonArray['customSecurity'] = $this->customSecurity;
		$jsonArray['requiredAttributes'] = $this->requiredAttributes;
		$jsonArray['tribute'] = $this->tribute;
		$jsonArray['defenseThreshold'] = $this->defenseThreshold;
		$jsonArray['isDefending'] = $this->isDefending;
		$jsonArray['defenseFleets'] = [];
		foreach ($this->defenseFleets as $fleet) {
			$jsonArray['defenseFleets'] []= $fleet->getName();
		}
		
		$jsonArray['wormhole'] = $this->wormhole?->getTrueName();
		$jsonArray['systems'] = [];
		foreach ($this->systems as $system) {
			$jsonArray['systems'] []= $system->getName();
		}
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}

}