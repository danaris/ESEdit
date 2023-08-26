<?php

namespace App\Entity\Sky;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;
use App\Entity\DataWriter;

#[ORM\Entity]
#[ORM\Table(name: 'Ship')]
class Ship extends Body {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	protected int $id;
	
	protected bool $isDefined = false;
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Ship')]
	#[ORM\JoinColumn(nullable: true, name: 'baseId')]
	protected ?Ship $base = null;
	
	#[ORM\Column(type: 'string', name: 'trueModelName')]
	protected string $trueModelName = '';
	
	#[ORM\Column(type: 'string', name: 'displayModelName')]
	protected string $displayModelName = '';
	
	#[ORM\Column(type: 'string', name: 'pluralModelName')]
	protected string $pluralModelName = '';
	
	#[ORM\Column(type: 'string', name: 'variantName')]
	protected string $variantName = '';
	
	#[ORM\Column(type: 'string', name: 'noun')]
	protected string $noun = '';
	
	#[ORM\Column(type: 'text', name: 'description')]
	protected string $description = '';
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sprite', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'thumbnailId')]
	protected ?Sprite $thumbnail = null;
	// Characteristics of this particular ship:
	protected EsUuid $uuid;
	
	#[ORM\Column(type: 'string', name: 'name')]
	protected string $name = '';
	#[ORM\Column(type: 'boolean')]
	protected bool $canBeCarried = false;
	
	protected int $forget = 0;
	protected bool $isInSystem = true;
	// "Special" ships cannot be forgotten, and if they land on a planet, they
	// continue to exist and refuel instead of being deleted.
	protected bool $isSpecial = false;
	protected bool $isYours = false;
	protected bool $isParked = false;
	protected bool $shouldDeploy = false;
	protected bool $isOverheated = false;
	protected bool $isDisabled = false;
	protected bool $isBoarding = false;
	protected bool $hasBoarded = false;
	protected bool $isFleeing = false;
	protected bool $isThrusting = false;
	protected bool $isReversing = false;
	protected bool $isSteering = false;
	protected float $steeringDirection = 0.;
	
	#[ORM\Column(type: 'boolean')]
	protected bool $neverDisabled = false;
	#[ORM\Column(type: 'boolean')]
	protected bool $isCapturable = true;
	#[ORM\Column(type: 'boolean')]
	protected bool $isInvisible = false;
	#[ORM\Column(type: 'integer')]
	protected int $customSwizzle = -1;
	#[ORM\Column(type: 'float')]
	protected float $cloak = 0.;
	#[ORM\Column(type: 'float')]
	protected float $cloakDisruption = 0.;
	// Cached values for figuring out when anti-missile is in range.
	#[ORM\Column(type: 'float')]
	protected float $antiMissileRange = 0.;
	#[ORM\Column(type: 'float')]
	protected float $weaponRadius = 0.;
	// Cargo and outfit scanning takes time.
	#[ORM\Column(type: 'float')]
	protected float $cargoScan = 0.;
	#[ORM\Column(type: 'float')]
	protected float $outfitScan = 0.;
	
	#[ORM\Column(type: 'float')]
	protected float $attraction = 0.;
	#[ORM\Column(type: 'float')]
	protected float $deterrence = 0.;
	
	// Number of AI steps this ship has spent lingering
	protected int $lingerSteps = 0;
	
	protected Command $commands;
	protected FireCommand $firingCommands;
	
	protected Personality $personality;
	protected ?Phrase $hail = null;
	protected ShipAICache $aiCache;
	
	// Installed outfits, cargo, etc.:
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Outfit', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'attributesOutfitId')]
	protected ?Outfit $attributes = null;
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Outfit', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'baseAttributesOutfitId')]
	protected ?Outfit $baseAttributes = null;
	#[ORM\Column(type: 'boolean')]
	protected bool $addAttributes = false;
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\Outfit', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'explosionOutfitId')]
	protected ?Outfit $explosionWeapon = null;
	
	protected array $outfits = []; // map<Outfit *, int>
	
	protected CargoHold $cargo;
	protected array $jettisoned = []; // list<shared_ptr<Flotsam>>
	
	protected array $bays = []; // vector<Bay>
	// Cache the mass of carried ships to avoid repeatedly recomputing it.
	protected float $carriedMass = 0.;
	
	protected array $enginePoints = []; // vector<EnginePoint>
	protected array $reverseEnginePoints = []; // vector<EnginePoint>
	protected array $steeringEnginePoints = []; // vector<EnginePoint>
	
	// Various energy levels:
	#[ORM\Column(type: 'float')]
	protected float $shields = 0.;
	#[ORM\Column(type: 'float')]
	protected float $hull = 0.;
	#[ORM\Column(type: 'float')]
	protected float $fuel = 0.;
	#[ORM\Column(type: 'float')]
	protected float $energy = 0.;
	#[ORM\Column(type: 'float')]
	protected float $heat = 0.;
	// Accrued "ion damage" that will affect this ship's energy over time.
	#[ORM\Column(type: 'float')]
	protected float $ionization = 0.;
	// Accrued "scrambling damage" that will affect this ship's weaponry over time.
	#[ORM\Column(type: 'float')]
	protected float $scrambling = 0.;
	// Accrued "disruption damage" that will affect this ship's shield effectiveness over time.
	#[ORM\Column(type: 'float')]
	protected float $disruption = 0.;
	// Accrued "slowing damage" that will affect this ship's movement over time.
	#[ORM\Column(type: 'float')]
	protected float $slowness = 0.;
	// Accrued "discharge damage" that will affect this ship's shields over time.
	#[ORM\Column(type: 'float')]
	protected float $discharge = 0.;
	// Accrued "corrosion damage" that will affect this ship's hull over time.
	#[ORM\Column(type: 'float')]
	protected float $corrosion = 0.;
	// Accrued "leak damage" that will affect this ship's fuel over time.
	#[ORM\Column(type: 'float')]
	protected float $leakage = 0.;
	// Accrued "burn damage" that will affect this ship's heat over time.
	#[ORM\Column(type: 'float')]
	protected float $burning = 0.;
	// Delays for shield generation and hull repair.
	#[ORM\Column(type: 'integer')]
	protected int $shieldDelay = 0;
	#[ORM\Column(type: 'integer')]
	protected int $hullDelay = 0;
	// Acceleration can be created by engines, firing weapons, or weapon impacts.
	protected Point $acceleration;
	
	#[ORM\Column(type: 'integer')]
	protected int $crew = 0;
	#[ORM\Column(type: 'integer')]
	protected int $pilotError = 0;
	#[ORM\Column(type: 'integer')]
	protected int $pilotOkay = 0;
	
	// Current status of this particular ship:
	protected ?System $currentSystem = null;
	// A Ship can be locked into one of three special states: landing,
	// hyperspacing, and exploding. Each one must track some special counters:
	protected ?Planet $landingPlanet = null;
	
	protected ShipJumpNavigation $navigation;
	protected int $hyperspaceCount = 0;
	protected ?System $hyperspaceSystem = null;
	protected bool $isUsingJumpDrive = false;
	protected float $hyperspaceFuelCost = 0.;
	protected Point $hyperspaceOffset;
	
	protected array $leaks = []; // vector<Leak>
	protected array $activeLeaks = []; // vector<Leak>
	
	// Explosions that happen when the ship is dying:
	protected array $explosionEffects = []; // map<Effect *, int>
	protected int $explosionRate = 0;
	protected int $explosionCount = 0;
	protected int $explosionTotal = 0;
	protected array $finalExplosions = []; // map<Effect *, int>
	
	// Target ships, planets, systems, etc.
	protected array $targetShip = []; // weak_ptr<Ship>
	protected array $shipToAssist = []; // weak_ptr<Ship>
	protected ?StellarObject $targetPlanet = null;
	protected ?System $targetSystem = null;
	protected array $targetAsteroid = []; // weak_ptr<Minable>
	protected array $targetFlotsam = []; // weak_ptr<Flotsam>
	
	// Links between escorts and parents.
	protected array $escorts = []; // vector<weak_ptr<Ship>>
	protected array $parent = []; // weak_ptr<Ship>
	
	protected bool $removeBays = false;
	
	const FIGHTER_REPAIR = "Repair fighters in";
	const BAY_SIDE = ["inside", "over", "under"];
	const BAY_FACING = ["forward", "left", "right", "back"];
	protected array $BAY_ANGLE;

	const ENGINE_SIDE = ["under", "over"];
	const STEERING_FACING = ["none", "left", "right"];

	const MAXIMUM_TEMPERATURE = 100.;

	const SCAN_TIME = 600.;
	
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\Mission', mappedBy: 'source')]
	protected Collection $sourcedMissions;

    #[ORM\OneToMany(mappedBy: 'ship', targetEntity: ShipOutfit::class, orphanRemoval: true, cascade: ['persist'])]
    protected Collection $shipOutfits;

    #[ORM\OneToMany(mappedBy: 'ship', targetEntity: Hardpoint::class, orphanRemoval: true, cascade: ['persist'])]
    protected Collection $hardpoints;

	// Helper function to transfer energy to a given stat if it is less than the
	// given maximum value.
	public static function DoRepair(float &$stat, float &$available, float $maximum): void {
		$transfer = max(0., min($available, $maximum - $stat));
		$stat += $transfer;
		$available -= $transfer;
	}
	
	// 	// Helper function to repair a given stat up to its maximum, limited by
	// 	// how much repair is available and how much energy, fuel, and heat are available.
	// 	// Updates the stat, the available amount, and the energy, fuel, and heat amounts.
	// 	void DoRepair(float &stat, float &available, float maximum, float &energy, float energyCost,
	// 		float &fuel, float fuelCost, float &heat, float heatCost)
	// 	{
	// 		if (available <= 0. || stat >= maximum) {
	// 			return;
	// 
	// 		// Energy, heat, and fuel costs are the energy, fuel, or heat required per unit repaired.
	// 		if (energyCost > 0.) {
	// 			available = min(available, energy / energyCost);
	// 		if (fuelCost > 0.) {
	// 			available = min(available, fuel / fuelCost);
	// 		if (heatCost < 0.) {
	// 			available = min(available, heat / -heatCost);
	// 
	// 		float transfer = min(available, maximum - stat);
	// 		if (transfer > 0.) {
	// 		{
	// 			stat += transfer;
	// 			available -= transfer;
	// 			energy -= transfer * energyCost;
	// 			fuel -= transfer * fuelCost;
	// 			heat += transfer * heatCost;
	// 		}
	// 	}
	// 
	// 	// Helper function to reduce a given status effect according
	// 	// to its resistance, limited by how much energy, fuel, and heat are available.
	// 	// Updates the stat and the energy, fuel, and heat amounts.
	// 	void DoStatusEffect(bool isDeactivated, float &stat, float resistance, float &energy, float energyCost,
	// 		float &fuel, float fuelCost, float &heat, float heatCost)
	// 	{
	// 		if (isDeactivated || resistance <= 0.) {
	// 		{
	// 			stat = max(0., .99 * stat);
	// 			return;
	// 		}
	// 
	// 		// Calculate how much resistance can be used assuming no
	// 		// energy or fuel cost.
	// 		resistance = .99 * stat - max(0., .99 * stat - resistance);
	// 
	// 		// Limit the resistance by the available energy, heat, and fuel.
	// 		if (energyCost > 0.) {
	// 			resistance = min(resistance, energy / energyCost);
	// 		if (fuelCost > 0.) {
	// 			resistance = min(resistance, fuel / fuelCost);
	// 		if (heatCost < 0.) {
	// 			resistance = min(resistance, heat / -heatCost);
	// 
	// 		// Update the stat, energy, heat, and fuel given how much resistance is being used.
	// 		if (resistance > 0.) {
	// 		{
	// 			stat = max(0., .99 * stat - resistance);
	// 			energy -= resistance * energyCost;
	// 			fuel -= resistance * fuelCost;
	// 			heat += resistance * heatCost;
	// 		}
	// 		} else
	// 			stat = max(0., .99 * stat);
	// 	}
	// 
	// Get an overview of how many weapon-outfits are equipped.
	public function getEquipped(): array {
		$equipped = [];
		foreach ($this->hardpoints as $hardpoint) {
			if ($hardpoint->getOutfit()) {
				if (!isset($equipped[$hardpoint->getOutfit()->getTrueName()])) {
					$equipped[$hardpoint->getOutfit()->getTrueName()] = 0;
				}
				$equipped[$hardpoint->getOutfit()->getTrueName()]++;
			}
		}
		return $equipped;
	}
	// 
	// 	void LogWarning(const string &trueModelName, const string &name, string &&warning)
	// 	{
	// 		string shipID = trueModelName + (name.empty() ? ": " : " \"" + name + "\": ");
	// 		Logger::LogError(shipID + std::move(warning));
	// 	}
	// 
	// 	// Transfer as many of the given outfits from the source ship to the target
	// 	// ship as the source ship can remove and the target ship can handle. Returns the
	// 	// items and amounts that were actually transferred (so e.g. callers can determine
	// 	// how much material was transferred, if any).
	// 	map<const Outfit *, int> TransferAmmo(const map<const Outfit *, int> &stockpile, Ship &from, Ship &to)
	// 	{
	// 		auto transferred = map<const Outfit *, int>{};
	// 		for (auto &&item : stockpile) {
	// 		{
	// 			assert(item.second > 0 && "stockpile count must be positive");
	// 			int unloadable = abs(from.Attributes().CanAdd(*item.first, -item.second));
	// 			int loadable = to.Attributes().CanAdd(*item.first, unloadable);
	// 			if (loadable > 0) {
	// 			{
	// 				from.AddOutfit(item.first, -loadable);
	// 				to.AddOutfit(item.first, loadable);
	// 				transferred[item.first] = loadable;
	// 			}
	// 		}
	// 		return transferred;
	// 	}
	// 
	// 	// Ships which are scrambled have a chance for their weapons to jam,
	// 	// delaying their firing for another reload cycle. The less energy
	// 	// a ship has relative to its max and the more scrambled the ship is,
	// 	// the higher the chance that a weapon will jam. The jam chance is
	// 	// capped at 50%. Very small amounts of scrambling are ignored.
	// 	// The scale is such that a weapon with a scrambling damage of 5 and a reload
	// 	// of 60 (i.e. the ion cannon) will only ever push a ship to a jam chance
	// 	// of 5% when it is at 100% energy.
	// 	float CalculateJamChance(float maxEnergy, float scrambling)
	// 	{
	// 		float scale = maxEnergy * 220.;
	// 		return scrambling > .1 ? min(0.5, scale ? scrambling / scale : 1.) : 0.;
	// 	}
	
	// Construct and Load() at the same time.
	public function __construct(?DataNode $node = null) {
		parent::__construct();
		$this->attributes = new Outfit();
		$this->baseAttributes = new Outfit();
		$this->cargo = new CargoHold();
		if ($node) {
			$this->load($node);
		}
		$this->BAY_ANGLE = [new Angle(0.), new Angle(-90.), new Angle(90.), new Angle(180.)];
		$this->shipOutfits = new ArrayCollection();
                 $this->hardpoints = new ArrayCollection();
	}
	
	public function load(DataNode $node): void {
		if ($node->size() >= 2) {
			$this->trueModelName = $node->getToken(1);
		}
		if ($node->size() >= 3) {
			$this->base = GameData::Ships()[$this->trueModelName];
			$this->variantName = $node->getToken(2);
		}
		$this->isDefined = true;
	
		$this->government = GameData::PlayerGovernment();
			
		$indexName = $this->trueModelName;
		if ($this->variantName) {
			$indexName = $this->variantName;
		}
	
		// Note: I do not clear the attributes list here so that it is permissible
		// to override one ship definition with another.
		$hasEngine = false;
		$hasArmament = false;
		$hasBays = false;
		$hasExplode = false;
		$hasLeak = false;
		$hasFinalExplode = false;
		$hasOutfits = false;
		$hasDescription = false;
		
		foreach ($node as $child) {
			$key = $child->getToken(0);
			$add = ($key == "add");
			if ($add && ($child->size() < 2 || $child->getToken(1) != "attributes")) {
				$child->printTrace("Skipping invalid use of 'add' with " . ($child->size() < 2 ? "no $key." : "$key: " . $child->getToken(1)));
				continue;
			}
			if ($key == "sprite") {
				$this->loadSprite($child);
			} else if ($child->getToken(0) == "thumbnail" && $child->size() >= 2) {
				$this->thumbnail = SpriteSet::Get($child->getToken(1));
			} else if ($key == "name" && $child->size() >= 2) {
				$this->name = $child->getToken(1);
			} else if ($key == "display name" && $child->size() >= 2) {
				$this->displayModelName = $child->getToken(1);
			} else if ($key == "plural" && $child->size() >= 2) {
				$this->pluralModelName = $child->getToken(1);
			} else if ($key == "noun" && $child->size() >= 2) {
				$this->noun = $child->getToken(1);
			} else if ($key == "swizzle" && $child->size() >= 2) {
				$this->customSwizzle = $child->getValue(1);
			} else if ($key == "uuid" && $child->size() >= 2) {
				$this->uuid = EsUuid::FromString($child->getToken(1));
			} else if ($key == "attributes" || $add) {
				if (!$add) {
					$this->baseAttributes->setTrueName($indexName.' Base Attributes');
					$this->baseAttributes->load($child);
					$this->baseAttributes->setTrueName($indexName.' Base Attributes');
				} else {
					$this->addAttributes = true;
					$this->attributes->setTrueName($indexName.' Attributes');
					$this->attributes->load($child);
					$this->attributes->setTrueName($indexName.' Attributes');
				}
			} else if (($key == "engine" || $key == "reverse engine" || $key == "steering engine") && $child->size() >= 3) {
				if (!$hasEngine) {
					$this->enginePoints = [];
					$this->reverseEnginePoints = [];
					$this->steeringEnginePoints = [];
					$hasEngine = true;
				}
				$reverse = ($key == "reverse engine");
				$steering = ($key == "steering engine");
	
				$editPoints = (!$steering && !$reverse) ? 'enginePoints' : ($reverse ? 'reverseEnginePoints' : 'steeringEnginePoints');
				$engine = new EnginePoint(0.5 * $child->getValue(1), 0.5 * $child->getValue(2), ($child->size() > 3 ? $child->getValue(3) : 1.));
				$this->$editPoints []= $engine;
				if ($reverse) {
					$engine->facing = new Angle(180.);
				}
				foreach ($child as $grand) {
					$grandKey = $grand->getToken(0);
					if ($grandKey == "zoom" && $grand->size() >= 2) {
						$engine->zoom = $grand->getValue(1);
					} else if ($grandKey == "angle" && $grand->size() >= 2) {
						$engine->facing->asPlus(new Angle($grand->getValue(1)));
					} else {
						for ($j = 1; $j < count(self::ENGINE_SIDE); ++$j) {
							if ($grandKey == self::ENGINE_SIDE[$j]) {
								$engine->side = $j;
							}
						}
						if ($steering) {
							for ($j = 1; $j < count(self::STEERING_FACING); ++$j) {
								if ($grandKey == self::STEERING_FACING[$j]) {
									$engine->steering = $j;
								}
							}
						}
					}
				}
			} else if ($key == "gun" || $key == "turret") {
				if (!$hasArmament) {
					$hasArmament = true;
				}
				$outfit = null;
				$hardpoint = new Point();
				if ($child->size() >= 3) {
					$hardpoint = new Point($child->getValue(1), $child->getValue(2));
					if ($child->size() >= 4) {
						$outfit = GameData::Outfits()[$child->getToken(3)];
					}
				} else {
					if ($child->size() >= 2) {
						$outfit = GameData::Outfits()[$child->getToken(1)];
					}
				}
				$gunPortAngle = new Angle(0.);
				$gunPortParallel = false;
				$drawUnder = ($key == "gun");
				if ($child->hasChildren()) {
					foreach ($child as $grand) {
						if ($grand->getToken(0) == "angle" && $grand->size() >= 2) {
							$gunPortAngle = new Angle($grand->getValue(1));
						} else if ($grand->getToken(0) == "parallel") {
							$gunPortParallel = true;
						} else if ($grand->getToken(0) == "under") {
							$drawUnder = true;
						} else if ($grand->getToken(0) == "over") {
							$drawUnder = false;
						} else {
							$grand->printTrace("Skipping unrecognized attribute:");
						}
					}
				}
				if ($key == "gun") {
					$this->hardpoints []= new Hardpoint(point: $hardpoint, ship: $this, outfit: $outfit, isUnder: $drawUnder, baseAngle: $gunPortAngle, isParallel: $gunPortParallel);
				} else {
					$this->hardpoints []= new Hardpoint(point: $hardpoint, ship: $this, isUnder: $drawUnder, outfit: $outfit, isTurret: true);
				}
			} else if ($key == "never disabled") {
				$this->neverDisabled = true;
			} else if ($key == "uncapturable") {
				$this->isCapturable = false;
			} else if ((($key == "fighter" || $key == "drone") && $child->size() >= 3) || ($key == "bay" && $child->size() >= 4)) {
				// While the `drone` and `fighter` keywords are supported for backwards compatibility, the
				// standard format is `bay <ship-category>`, with the same signature for other values.
				$category = "Fighter";
				$childOffset = 0;
				if ($key == "drone") {
					$category = "Drone";
				} else if ($key == "bay") {
					$category = $child->getToken(1);
					$childOffset += 1;
				}
				if (!$hasBays) {
					$this->bays = [];
					$hasBays = true;
				}
				$bay = new Bay($child->getValue(1 + $childOffset), $child->getValue(2 + $childOffset), $category);
				$this->bays []= $bay;
				
				for ($i = 3 + $childOffset; $i < $child->size(); ++$i) {
					for ($j = 1; $j < count(self::BAY_SIDE); ++$j) {
						if ($child->getToken($i) == self::BAY_SIDE[$j]) {
							$bay->side = $j;
						}
					}
					for ($j = 1; $j < count(self::BAY_FACING); ++$j) {
						if ($child->getToken($i) == self::BAY_FACING[$j]) {
							$bay->facing = $this->BAY_ANGLE[$j];
						}
					}
				}
				if ($child->hasChildren()) {
					foreach ($child as $grand) {
						// Load in the effect(s) to be displayed when the ship launches.
						if ($grand->getToken(0) == "launch effect" && $grand->size() >= 2) {
							$count = $grand->size() >= 3 ? intval($grand->getValue(2)) : 1;
							$e = GameData::Effects()[$grand->getToken(1)];
							for ($i = 0; $i < $count; $i++) {
								$bay->launchEffects []= $e;
							}
						} else if ($grand->getToken(0) == "angle" && $grand->size() >= 2) {
							$bay->facing = new Angle($grand->getValue(1));
						} else {
							$handled = false;
							for ($i = 1; $i < count(self::BAY_SIDE); ++$i) {
								if ($grand->getToken(0) == self::BAY_SIDE[$i]) {
									$bay->side = $i;
									$handled = true;
								}
							}
							for ($i = 1; $i < count(self::BAY_FACING); ++$i) {
								if ($grand->getToken(0) == self::BAY_FACING[$i]) {
									$bay->facing = $this->BAY_ANGLE[$i];
									$handled = true;
								}
							}
							if (!$handled) {
								$grand->printTrace("Skipping unrecognized attribute:");
							}
						}
					}
				}
			} else if ($key == "leak" && $child->size() >= 2) {
				if (!$hasLeak) {
					$this->leaks = [];
					$hasLeak = true;
				}
				$leak = GameData::Effects()[$child->getToken(1)];
				if ($child->size() >= 3) {
					$leak->openPeriod = $child->getValue(2);
				}
				if ($child->size() >= 4) {
					$leak->closePeriod = $child->getValue(3);
				}
				$this->leaks []= $leak;
			} else if ($key == "explode" && $child->size() >= 2) {
				if (!$hasExplode) {
					$this->explosionEffects = [];
					$this->explosionTotal = 0;
					$hasExplode = true;
				}
				$count = ($child->size() >= 3) ? $child->getValue(2) : 1;
				$effectName = $child->getToken(1);
				$effect = GameData::Effects()[$effectName];
				if (!isset($this->explosionEffects[$effectName])) {
					$this->explosionEffects[$effectName] = ['effect'=>$effect, 'count'=>0];
				}
				$this->explosionEffects[$effectName]['count'] += $count;
				$this->explosionTotal += $count;
			} else if ($key == "final explode" && $child->size() >= 2) {
				if (!$hasFinalExplode) {
					$this->finalExplosions = [];
					$hasFinalExplode = true;
				}
				$count = ($child->size() >= 3) ? $child->getValue(2) : 1;
				$effectName = $child->getToken(1);
				$effect = GameData::Effects()[$effectName];
				if (!isset($this->finalExplosions[$effectName])) {
					$this->finalExplosions[$effectName] = ['effect'=>$effect, 'count'=>0];
				}
				$this->finalExplosions[$effectName]['count'] += $count;
			} else if ($key == "outfits") {
				if (!$hasOutfits) {
					$this->shipOutfits = new ArrayCollection();
					$hasOutfits = true;
				}
				foreach ($child as $grand) {
					$count = ($grand->size() >= 2) ? $grand->getValue(1) : 1;
					if ($count > 0) {
						$outfitName = $grand->getToken(0);
						$outfit = GameData::Outfits()[$outfitName];
						if (!isset($this->shipOutfits[$outfitName])) {
							$ShipOutfit = new ShipOutfit();
							$ShipOutfit->setShip($this);
							$ShipOutfit->setOutfit($outfit);
							$ShipOutfit->setCount(0);
							$this->shipOutfits[$outfitName] = $ShipOutfit;
						}
						$this->shipOutfits[$outfitName]->setCount($this->shipOutfits[$outfitName]->getCount() + $count);
					} else {
						$grand->printTrace("Skipping invalid outfit count:");
					}
				}
	
				// Verify we have at least as many installed outfits as were identified as "equipped."
				// If not (e.g. a variant definition), ensure FinishLoading equips into a blank slate.
				if (!$hasArmament) {
					foreach ($this->getEquipped() as $outfitName => $equippedCount) {
						if (!isset($this->shipOutfits[$outfitName]) || $this->shipOutfits[$outfitName]->getCount() < $equippedCount) {
							$this->hardpoints = new ArrayCollection();
							break;
						}
					}
				}
			} else if ($key == "cargo") {
				$this->cargo->load($child);
			} else if ($key == "crew" && $child->size() >= 2) {
				$this->crew = intval($child->getValue(1));
			} else if ($key == "fuel" && $child->size() >= 2) {
				$this->fuel = $child->getValue(1);
			} else if ($key == "shields" && $child->size() >= 2) {
				$this->shields = $child->getValue(1);
			} else if ($key == "hull" && $child->size() >= 2) {
				$this->hull = $child->getValue(1);
			} else if ($key == "position" && $child->size() >= 3) {
				$this->position = new Point($child->getValue(1), $child->getValue(2));
			} else if ($key == "system" && $child->size() >= 2) {
				$this->currentSystem = GameData::Systems()[$child->getToken(1)];
			} else if ($key == "planet" && $child->size() >= 2) {
				$this->zoom = 0.;
				$this->landingPlanet = GameData::Planets()[$child->getToken(1)];
			} else if ($key == "destination system" && $child->size() >= 2) {
				$this->targetSystem = GameData::Systems()[$child->getToken(1)];
			} else if ($key == "parked") {
				$this->isParked = true;
			} else if ($key == "description" && $child->size() >= 2) {
				if (!$hasDescription) {
					$this->description = '';
					$hasDescription = true;
				}
				$this->description .= $child->getToken(1);
				$this->description .= "\n";
			} else if ($key == "remove" && $child->size() >= 2) {
				if ($child->getToken(1) == "bays") {
					$this->removeBays = true;
				} else {
					$child->printTrace("Skipping unsupported \"remove\":");
				}
			} else if ($key != "actions") {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	
		if ($this->displayModelName == '') {
			$this->displayModelName = $this->trueModelName;
		}
	
		// If no plural model name was given, default to the model name with an 's' appended.
		// If the model name ends with an 's' or 'z', print a warning because the default plural will never be correct.
		// Variants will import their plural name from the base model in FinishLoading.
		if ($this->pluralModelName == '' && $this->variantName == '') {
			$this->pluralModelName = $this->displayModelName . 's';
			if ($this->displayModelName[strlen($this->displayModelName)-1] == 's' || $this->displayModelName[strlen($this->displayModelName)-1] == 'z') {
				$node->printTrace("Warning: explicit plural name definition required, but none is provided. Defaulting to \"" . $this->pluralModelName . "\".");
			}
		}
	}
	
	public function getId(): int {
		return $this->id;
	}
	
	public function gunPorts(): int {
		$gunPorts = 0;
		foreach ($this->hardpoints as $Hardpoint) {
			if (!$Hardpoint->isTurret()) {
				$gunPorts++;
			}
		}
		
		return $gunPorts;
	}
	
	public function turretMounts(): int {
		$turrets = 0;
		foreach ($this->hardpoints as $Hardpoint) {
			if ($Hardpoint->isTurret()) {
				$turrets++;
			}
		}
		
		return $turrets;
	}
	
	// When loading a ship, some of the outfits it lists may not have been
	// loaded yet. So, wait until everything has been loaded, then call this.
	public function finishLoading(bool $isNewInstance) {
		// All copies of this ship should save pointers to the "explosion" weapon
		// definition stored safely in the ship model, which will not be destroyed
		// until GameData is when the program quits. Also copy other attributes of
		// the base model if no overrides were given.
		if (GameData::Ships()->has($this->trueModelName)) {
			$model = GameData::Ships()[$this->trueModelName];
			//$this->explosionWeapon = &model->BaseAttributes();
			if ($this->displayModelName == '') {
				$this->displayModelName = $model->displayModelName;
			}
			if ($this->pluralModelName == '') {
				$this->pluralModelName = $model->pluralModelName;
			}
			if ($this->noun = '') {
				$this->noun = $model->noun;
			}
			if (!$this->thumbnail) {
				$this->thumbnail = $model->thumbnail;
			}
		}
	
		// If this ship has a base class, copy any attributes not defined here.
		// Exception: uncapturable and "never disabled" flags don't carry over.
		if ($this->base && $this->base != $this) {
			if ($this->customSwizzle == -1) {
				$this->customSwizzle = $this->base->customSwizzle;
			}
			if (count($this->baseAttributes->getAttributes()) == 0) {
				$this->baseAttributes = $this->base->baseAttributes->copy();
			}
			if (count($this->bays) == 0 && count($this->base->bays) != 0 && !$this->removeBays) {
				$this->bays = $this->base->bays;
			}
			if (count($this->enginePoints) == 0) {
				$this->enginePoints = $this->base->enginePoints;
			}
			if (count($this->reverseEnginePoints) == 0) {
				$this->reverseEnginePoints = $this->base->reverseEnginePoints;
			}
			if (count($this->steeringEnginePoints) == 0) {
				$this->steeringEnginePoints = $this->base->steeringEnginePoints;
			}
			if (count($this->explosionEffects) == 0) {
				$this->explosionEffects = $this->base->explosionEffects;
				$this->explosionTotal = $this->base->explosionTotal;
			}
			if (count($this->finalExplosions) == 0) {
				$this->finalExplosions = $this->base->finalExplosions;
			}
			if (count($this->outfits) == 0) {
				$this->outfits = $this->base->outfits;
			}
			if ($this->description == '') {
				$this->description = $this->base->description;
			}
			$hasHardpoints = false;
			foreach ($this->hardpoints as $Hardpoint) {
				if ($Hardpoint->getPoint()->X() != 0 || $Hardpoint->getPoint()->Y() != 0) {
					$hasHardpoints = true;
					break;
				}
			}
			$hardpointOutfits = [];
			$emptyTurretPoints = 0;
			$emptyGunPoints = 0;
			if (!$hasHardpoints) {
				// Check if any hardpoint locations were not specified on the variant, and get their full specifications from the base.
				$nextGunIndex = -1;
				$nextTurretIndex = -1;
				foreach ($this->hardpoints as $hpIndex => $Hardpoint) {
					if ($Hardpoint->isTurret() && $nextTurretIndex == -1) {
						$nextTurretIndex = $hpIndex;
					} else if (!$Hardpoint->isTurret() && $nextGunIndex == -1) {
						$nextGunIndex = $hpIndex;
					}
				}
				foreach ($this->base->hardpoints as $baseIndex => $BaseHardpoint) {
					$myIndex = -1;
					if ($BaseHardpoint->isTurret() && $nextTurretIndex != -1) {
						$myIndex = $nextTurretIndex;
						$nextTurretIndex = -1;
						for ($i=$nextTurretIndex+1; $i<count($this->hardpoints); $i++) {
							if ($this->hardpoints[$i]->isTurret()) {
								$nextTurretIndex = $i;
							}
						}
					} else if (!$BaseHardpoint->isTurret() && $nextGunIndex != -1) {
						$myIndex = $nextGunIndex;
						$nextGunIndex = -1;
						for ($i=$nextGunIndex+1; $i<count($this->hardpoints); $i++) {
							if (!$this->hardpoints[$i]->isTurret()) {
								$nextGunIndex = $i;
							}
						}
					}
					if ($myIndex != -1) {
						$newPoint = new Point($BaseHardpoint->getPoint()->X() * 2, $BaseHardpoint->getPoint()->Y() * 2);
						$angle = new Angle($BaseHardpoint->getBaseAngleDegrees());
						$NewHardpoint = new Hardpoint(ship: $this, point: $newPoint, baseAngle: $angle, isParallel: $BaseHardpoint->isParallel(), isUnder: $BaseHardpoint->isUnder(), isTurret: $BaseHardpoint->isTurret(), outfit: $this->hardpoints[$myIndex]->getOutfit());
						$this->hardpoints[$myIndex] = $NewHardpoint;
						if ($NewHardpoint->getOutfit()) {
							$hardpointOutfitName = $NewHardpoint->getOutfit()->getTrueName();
							if (!isset($hardpointOutfits[$hardpointOutfitName])) {
								$hardpointOutfits[$hardpointOutfitName] = 1;
							} else {
								$hardpointOutfits[$hardpointOutfitName]++;
							}
						} else {
							if ($NewHardpoint->isTurret()) {
								$emptyTurretPoints++;
							} else {
								$emptyGunPoints++;
							}
						}
					} else {
						$newPoint = new Point($BaseHardpoint->getPoint()->X() * 2, $BaseHardpoint->getPoint()->Y() * 2);
						$angle = new Angle($BaseHardpoint->getBaseAngleDegrees());
						$NewHardpoint = new Hardpoint(ship: $this, point: $newPoint, baseAngle: $angle, isParallel: $BaseHardpoint->isParallel(), isUnder: $BaseHardpoint->isUnder(), isTurret: $BaseHardpoint->isTurret(), outfit: null);
						$this->hardpoints[$baseIndex] = $NewHardpoint;
						if ($BaseHardpoint->isTurret()) {
							$emptyTurretPoints++;
						} else {
							$emptyGunPoints++;
						}
					}
				}
				// We also need to check if there are outfits specified on the variant that have no hardpoints assigned—if the number matches (eg, 4 gun outfits and 4 gun hardpoints), 
				// they should just be applied as-is
				foreach ($this->outfits as $ShipOutfit) {
					if (isset($hardpointOutfits[$ShipOutfit->getOutfit()->getTrueName()])) {
						$hardpointCount = $hardpointOutfits[$ShipOutfit->getOutfit()->getTrueName()];
						if ($hardpointCount >= $ShipOutfit->getCount()) {
							// If we've already seen at least as many of these as the outfit section specifies, we don't need to do anything here
							continue;
						}
						if ($ShipOutfit->getOutfit()->get('turret mounts') != 0) {
							$emptyGunPoints += $ShipOutfit->getOutfit()->get('gun ports') * $ShipOutfit->getCount();
							$gunsToAdd = $ShipOutfit->getCount();
							foreach ($this->hardpoints as $Hardpoint) {
								if ($Hardpoint->getOutfit() == null && !$Hardpoint->isTurret()) {
									$Hardpoint->setOutfit($ShipOutfit->getOutfit());
									if ($gunsToAdd > 0) {
										$gunsToAdd--;
									} else {
										break;
									}
								}
							}
						} else {
							$emptyTurretPoints += $ShipOutfit->getOutfit()->get('turret mounts') * $ShipOutfit->getCount();
							$turretsToAdd = $ShipOutfit->getCount();
							foreach ($this->hardpoints as $Hardpoint) {
								if ($Hardpoint->getOutfit() == null && $Hardpoint->isTurret()) {
									$Hardpoint->setOutfit($ShipOutfit->getOutfit());
									if ($turretsToAdd > 0) {
										$turretsToAdd--;
									} else {
										break;
									}
								}
							}
						}
					}
				}
				// $weaponCounts = $this->getWeapons();
				// foreach ($weaponCounts as $outfitName => $count) {
				// 	$lastPoint = new Point(0, 0);
				// 	foreach ($this->hardpoints as $Hardpoint) {
				// 		if ($Hardpoint->getOutfit()?->getName() == $outfitName) {
				// 			if ($count > 0) {
				// 				$count--;
				// 				$lastPoint = $Hardpoint->getPoint();
				// 			} else {
				// 				$newPoint = new Point($lastPoint->X() * 2, $lastPoint->Y() * 2);
				// 				$angle = new Angle($Hardpoint->getBaseAngleDegrees());
				// 				$NewHardpoint = new Hardpoint(point: $newPoint, angle: $angle, parallel: $Hardpoint->isParallel(), isUnder: $Hardpoint->isUnder(), isTurret: $Hardpoint->isTurret(), outfit: $Hardpoint->getOutfit());
				// 				$this->hardpoints []= $NewHardpoint;
				// 				$lastPoint = $newPoint;
				// 			}
				// 		}
				// 	}
				// }
			}
		} else if ($this->removeBays) {
			$this->bays = [];
		}
		// Check that all the "equipped" weapons actually match what your ship
		// has, and that they are truly weapons. Remove any excess weapons and
		// warn if any non-weapon outfits are "installed" in a hardpoint.
// 		auto equipped = GetEquipped(Weapons());
// 		for (auto &it : equipped) {
// 
// 			auto outfitIt = outfits.find(it.first);
// 			int amount = (outfitIt != outfits.end() ? outfitIt->second : 0);
// 			int excess = it.second - amount;
// 			if (excess > 0) {
// 
// 				// If there are more hardpoints specifying this outfit than there
// 				// are instances of this outfit installed, remove some of them.
// 				armament.Add(it.first, -excess);
// 				it.second -= excess;
// 	
// 				LogWarning(VariantName(), Name(),
// 						"outfit \"" + it.first->TrueName() + "\" equipped but not included in outfit list.");
// 			} else if (!it.first->IsWeapon()) {
// 				// This ship was specified with a non-weapon outfit in a
// 				// hardpoint. Hardpoint::Install removes it, but issue a
// 				// warning so the definition can be fixed.
// 				LogWarning(VariantName(), Name(),
// 						"outfit \"" + it.first->TrueName() + "\" is not a weapon, but is installed as one.");
// 		}
	
		// Mark any drone that has no "automaton" value as an automaton, to
		// grandfather in the drones from before that attribute existed.
		if ($this->baseAttributes->getCategory() == "Drone" && !$this->baseAttributes->get("automaton")) {
			$this->baseAttributes->set("automaton", 1.);
		}	
		$this->baseAttributes->set("gun ports", $this->gunPorts());
		$this->baseAttributes->set("turret mounts", $this->turretMounts());
	
		if ($this->addAttributes) {
			// Store attributes from an "add attributes" node in the ship's
			// baseAttributes so they can be written to the save file.
			$this->baseAttributes->add($this->attributes);
			//$this->addAttributes = false;
		}
		// Add the attributes of all your outfits to the ship's base attributes.
		$this->attributes = $this->baseAttributes->copy();
		$indexName = $this->trueModelName;
		if ($this->variantName) {
			$indexName = $this->variantName;
		}
		$this->attributes->setTrueName($indexName.' Attributes');
		$this->attributes->setDisplayName($indexName.' Attributes');
		$this->attributes->setPluralName($indexName.' Attributeses');
		$undefinedOutfits = [];
		foreach ($this->shipOutfits as $ShipOutfit) {
			if (!$ShipOutfit->getOutfit()->isDefined()) {
				$undefinedOutfits []= $ShipOutfit->getOutfit()->TrueName();
				continue;
			}
			$this->attributes->add($ShipOutfit->getOutfit(), $ShipOutfit->getCount());
			// Some ship variant definitions do not specify which weapons
			// are placed in which hardpoint. Add any weapons that are not
			// yet installed to the ship's armament.
			if ($ShipOutfit->getOutfit()->isWeapon()) {
				$count = $ShipOutfit->getCount();
				if (isset($this->getEquipped()[$ShipOutfit->getOutfit()->getTrueName()])) {
					$count -= $this->getEquipped()[$ShipOutfit->getOutfit()->getTrueName()];
				}
				if ($count > 0) {
					foreach ($this->hardpoints as $Hardpoint) {
						if ($Hardpoint->getOutfit() == null && $Hardpoint->isTurret() == isset($ShipOutfit->getOutfit()->getAttributes()['turret mounts'])) {
							$Hardpoint->setOutfit($ShipOutfit->getOutfit());
							$count--;
						}
					}
				}
			}
		}
		if (count($undefinedOutfits) > 0) {

			$plural = count($undefinedOutfits) > 1;
			// Print the ship name once, then all undefined outfits. If we're reporting for a stock ship, then it
			// doesn't have a name, and missing outfits aren't named yet either. A variant name might exist, though.
			$message = '';
			if ($this->isYours) {
				$message = "Player ship " . $this->trueModelName . " \"" . $this->name . "\":";
				$PREFIX = $plural ? "\n\tUndefined outfit " : " undefined outfit ";
				foreach ($undefinedOutfits as $outfitName) {
					$message .= $PREFIX . $outfit;
				}
			} else {
				$message = $this->variantName == '' ? "Stock ship \"" . $this->trueModelName . "\": " : $this->trueModelName . " variant \"" . $this->variantName . "\": ";
				$message += count($undefinedOutfits) . " undefined outfit" . ($plural ? "s" : "") . " installed.";
			}
	
			error_log($message);
		}
		// Inspect the ship's armament to ensure that guns are in gun ports and
		// turrets are in turret mounts. This can only happen when the armament
		// is configured incorrectly in a ship or variant definition. Do not
		// bother printing this warning if the outfit is not fully defined.
// 		for (const Hardpoint &hardpoint : armament.Get()) {
// 
// 			const Outfit *outfit = hardpoint.GetOutfit();
// 			if (outfit && outfit->IsDefined() {
// 					&& (hardpoint.IsTurret() != (outfit->Get("turret mounts") != 0.)))
// 			{
// 				string warning = (!isYours && !variantName.empty()) ? "variant \"" + variantName + "\"" : trueModelName;
// 				if (!name.empty()) {
// 					warning += " \"" + name + "\"";
// 				warning += ": outfit \"" + outfit->TrueName() + "\" installed as a ";
// 				warning += (hardpoint.IsTurret() ? "turret but is a gun.\n\tturret" : "gun but is a turret.\n\tgun");
// 				warning += to_string(2. * hardpoint.GetPoint().X()) + " " + to_string(2. * hardpoint.GetPoint().Y());
// 				warning += " \"" + outfit->TrueName() + "\"";
// 				Logger::LogError(warning);
// 			}
// 		}
		$this->cargo->setSize($this->attributes->get("cargo space"));
	
		// Figure out how far from center the farthest hardpoint is.
		$this->weaponRadius = 0.;
		foreach ($this->hardpoints as $Hardpoint) {
			$this->weaponRadius = max($this->weaponRadius, $Hardpoint->getPoint()->getLength());
		}
	
		// Ensure that all defined bays are of a valid category. Remove and warn about any
		// invalid bays. Add a default "launch effect" to any remaining internal bays if
		// this ship is crewed (i.e. pressurized).
// 		string warning;
// 		const auto &bayCategories = GameData::GetCategory(CategoryType::BAY);
// 		for (auto it = bays.begin(); it != bays.end(); ) {
// 
// 			Bay &bay = *it;
// 			if (!bayCategories.Contains(bay.category)) {
// 
// 				warning += "Invalid bay category: " + bay.category + "\n";
// 				it = bays.erase(it);
// 				continue;
// 			} else
// 				++it;
// 			if (bay.side == Bay::INSIDE && bay.launchEffects.empty() && Crew()) {
// 				bay.launchEffects.emplace_back(GameData::Effects().Get("basic launch"));
// 		}
	
		$this->canBeCarried = in_array($this->attributes->getCategory(), ['Fighter','Drone']);
	
		// Issue warnings if this ship has is misconfigured, e.g. is missing required values
		// or has negative outfit, cargo, weapon, or engine capacity.
// 		for (auto &&attr : set<string>{"outfit space", "cargo space", "weapon capacity", "engine capacity"}) {
// 
// 			float val = attributes.Get(attr);
// 			if (val < 0) {
// 				warning += attr + ": " + Format::Number(val) + "\n";
// 		}
// 		if (attributes.Get("drag") <= 0.) {
// 
// 			warning += "Defaulting " + string(attributes.Get("drag") ? "invalid" : "missing") + " \"drag\" attribute to 100.0\n";
// 			attributes.Set("drag", 100.);
// 		}
	
		// Calculate the values used to determine this ship's value and danger.
		// attraction = CalculateAttraction();
		// deterrence = CalculateDeterrence();
	
// 		if (!warning.empty()) {
// 
// 			// This check is mostly useful for variants and stock ships, which have
// 			// no names. Print the outfits to facilitate identifying this ship definition.
// 			string message = (!name.empty() ? "Ship \"" + name + "\" " : "") + "(" + VariantName() + "):\n";
// 			ostringstream outfitNames;
// 			outfitNames << "has outfits:\n";
// 			for (const auto &it : outfits) {
// 				outfitNames << '\t' << it.second << " " + it.first->TrueName() << endl;
// 			Logger::LogError(message + warning + outfitNames.str());
// 		}
	
	}
// 	
// 	
// 	
// 	// Check if this ship (model) and its outfits have been defined.
// 	bool Ship::IsValid() const
// 	{
// 		for (auto &&outfit : outfits) {
// 			if (!outfit.first->IsDefined()) {
// 				return false;
// 	
// 		return isDefined;
// 	}
// 	
// 	
// 	
	// Save a full description of this ship, as currently configured.
	public function save(DataWriter $out): void {
		$out->write(["ship", $this->trueModelName]);
		$out->beginChild();
		{
			$out->write(["name", $this->name]);
			if ($this->displayModelName != $this->trueModelName) {
				$out->write(["display name", $this->displayModelName]);
			}
			if ($this->pluralModelName != $this->displayModelName . 's') {
				$out->write(["plural", $this->pluralModelName]);
			}
			if ($this->noun != '') {
				$out->write(["noun", $this->noun]);
			}
			$this->saveSprite($out);
			if ($this->thumbnail) {
				$out->write(["thumbnail", $this->thumbnail->getName()]);
			}
	
			if ($this->neverDisabled) {
				$out->write("never disabled");
			}
			if (!$this->isCapturable) {
				$out->write("uncapturable");
			}
			if ($this->customSwizzle >= 0) {
				$out->write(["swizzle", $this->customSwizzle]);
			}
			//$out->write(["uuid", uuid.ToString()]);
	
			$out->write("attributes");
			$out->beginChild();
			{
				$out->write(["category", $this->baseAttributes->getCategory()]);
				$out->write(["cost", $this->baseAttributes->getCost()]);
				$out->write(["mass", $this->baseAttributes->getMass()]);
				foreach ($this->baseAttributes->getFlareSprites() as $flareSpriteName => $count) {
					$flareSprite = SpriteSet::Get($flareSpriteName);
					for ($i=0; $i<$count; $i++) {
						$flareSprite->saveSprite($out, 'flare sprite');
					}
				}
				foreach ($this->baseAttributes->getFlareSounds() as $flareSoundsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['flare sound', $flareSounds]);
					}
				}
				foreach ($this->baseAttributes->getReverseFlareSprites() as $reverseFlareSpritesName => $count) {
					$reverseFlareSprites = SpriteSet::Get($reverseFlareSpritesName);
					for ($i=0; $i<$count; $i++) {
						$reverseFlareSprites->saveSprite($out, 'reverse flare sprite');
					}
				}
				foreach ($this->baseAttributes->getReverseFlareSounds() as $reverseFlareSoundsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['reverse flare sound', $reverseFlareSounds]);
					}
				}
				foreach ($this->baseAttributes->getSteeringFlareSprites() as $steeringFlareSpritesName => $count) {
					$steeringFlareSprites = SpriteSet::Get($steeringFlareSpritesName);
					for ($i=0; $i<$count; $i++) {
						$steeringFlareSprites->saveSprite($out, 'steering flare sprite');
					}
				}
				foreach ($this->baseAttributes->getSteeringFlareSounds() as $steeringFlareSoundsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['steering flare sound', $steeringFlareSounds]);
					}
				}
				foreach ($this->baseAttributes->getAfterburnerEffects() as $afterburnerEffectsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['afterburner effect', $afterburnerEffects]);
					}
				}
				foreach ($this->baseAttributes->getJumpEffects() as $jumpEffectsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['jump effect', $jumpEffects]);
					}
				}
				foreach ($this->baseAttributes->getJumpSounds() as $jumpSoundsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['jump sound', $jumpSounds]);
					}
				}
				foreach ($this->baseAttributes->getJumpInSounds() as $jumpInSoundsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['jump in sound', $jumpInSounds]);
					}
				}
				foreach ($this->baseAttributes->getJumpOutSounds() as $jumpOutSoundsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['jump out sound', $jumpOutSounds]);
					}
				}
				foreach ($this->baseAttributes->getHyperSounds() as $hyperSoundsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['hyperdrive sound', $hyperSounds]);
					}
				}
				foreach ($this->baseAttributes->getHyperInSounds() as $hyperInSoundsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['hyperdrive in sound', $hyperInSounds]);
					}
				}
				foreach ($this->baseAttributes->getHyperOutSounds() as $hyperOutSoundsName => $count) {
					for ($i=0; $i<$count; $i++) {
						$out->write(['hyperdrive out sound', $hyperOutSounds]);
					}
				}
				foreach ($this->baseAttributes->getAttributes() as $attrName => $count) {
					if ($count > 0) {
						$out->write([$attrName, $count]);
					}
				}
			}
			$out->endChild();
	
			$out->write("outfits");
			$out->beginChild();
			{
				uksort($this->outfits, function($a, $b) {
					if (!is_string($a)) {
						$a = $a->getTrueName();
					}
					if (!is_string($b)) {
						$b = $b->getTrueName();
					}
					if ($a < $b) {
						return -1;
					} else if ($a > $b) {
						return 1;
					} else {
						return 0;
					}
				});
				foreach ($this->outfits as $outfitName => $outfitCount) {
					if ($outfitCount == 1) {
						$out->write($outfitName);
					} else {
						$out->write([$outfitName, $outfitCount]);
					}
				}
			}
			$out->endChild();
	
			$this->cargo->save($out);
			$out->write(["crew", $this->crew]);
			$out->write(["fuel", $this->fuel]);
			$out->write(["shields", $this->shields]);
			$out->write(["hull", $this->hull]);
			$out->write(["position", $this->position->X(), $this->position->Y()]);
			
			foreach ($this->enginePoints as $point) {
				$out->write(["engine", 2. * $point->X(), 2. * $point->Y()]);
				$out->beginChild();
				$out->write(["zoom", $point->zoom]);
				$out->write(["angle", $point->facing->getDegrees()]);
				$out->write(ENGINE_SIDE[$point->side]);
				$out->endChild();
	
			}
			foreach ($this->reverseEnginePoints as $point) {
				$out->write(["reverse engine", 2. * $point->X(), 2. * $point->Y()]);
				$out->beginChild();
				$out->write(["zoom", $point->zoom]);
				$out->write(["angle", $point->facing->getDegrees() - 180.]);
				$out->write(ENGINE_SIDE[$point->side]);
				$out->endChild();
			}
			foreach ($this->steeringEnginePoints as $point) {
				$out->write(["steering engine", 2. * $point->X(), 2. * $point->Y()]);
				$out->beginChild();
				$out->write(["zoom", $point->zoom]);
				$out->write(["angle", $point->facing->getDegrees()]);
				$out->write(ENGINE_SIDE[$point->side]);
				$out->write(STEERING_FACING[$point->steering]);
				$out->endChild();
			}
			foreach ($this->hardpoints as $Hardpoint) {
				$type = ($Hardpoint->isTurret() ? "turret" : "gun");
				if ($Hardpoint->getOutfit()) {
					$out->write([$type, 2. * $Hardpoint->getPoint()->X(), 2. * $Hardpoint->getPoint()->Y(),
						$Hardpoint->getOutfit()->getTrueName()]);
				} else {
					$out->write([$type, 2. * $Hardpoint->getPoint()->X(), 2. * $Hardpoint->getPoint()->Y()]);
				}
				$HardpointAngle = $Hardpoint->getBaseAngle()->getDegrees();
				$out->beginChild();
				{
					if ($HardpointAngle) {
						$out->write(["angle", $HardpointAngle]);
					}
					if ($Hardpoint->isParallel()) {
						$out->write("parallel");
					}
					if ($Hardpoint->isUnder()) {
						$out->write("under");
					} else {
						$out->write("over");
					}
				}
				$out->endChild();
			}
			foreach ($this->bays as $Bay) {
				$x = 2.0 * $Bay->point->X();
				$y = 2.0 * $Bay->point->Y();
	
				$out->write(["bay", $Bay->category, $x, $y]);
				if (count($Bay->launchEffects) > 0 || $Bay->facing->getDegrees() || $Bay->side) {
					$out->beginChild();
					{
						if ($Bay->facing->getDegrees()) {
							$out->write(["angle", $Bay->facing->getDegrees()]);
						}
						if ($Bay->side) {
							$out->write(BAY_SIDE[$Bay->side]);
						}
						foreach ($Bay->launchEffects as $effect) {
							$out->write(["launch effect", $effect->getName()]);
						}
					}
					$out->endChild();
				}
			}
			// for (const Leak &leak : leaks) {
			// 	$out->write(["leak", leak.effect->Name(), leak.openPeriod, leak.closePeriod]);
			
			// using EffectElement = pair<const Effect *const, int>;
			// auto effectSort = [](const EffectElement *lhs, const EffectElement *rhs)
			// 	{ return lhs->first->Name() < rhs->first->Name(); };
			// WriteSorted(explosionEffects, effectSort, [&out](const EffectElement &it)
			// {
			// 	if (it.second) {
			// 		$out->write(["explode", it.first->Name(), it.second]);
			// });
			// WriteSorted(finalExplosions, effectSort, [&out](const EffectElement &it)
			// {
			// 	if (it.second) {
			// 		$out->write(["final explode", it.first->Name(), it.second]);
			// });
	
			if ($this->currentSystem) {
				$out->write(["system", $this->currentSystem->getName()]);
			} else {
				// A carried ship is saved in its carrier's system.
				// $parent = $this->getParent();
				// if ($parent && $parent->currentSystem) {
				// 	$out->write(["system", $parent->currentSystem->getName()]);
				// }
			}
			if ($this->landingPlanet) {
				$out->write(["planet", $landingPlanet->getTrueName()]);
			}
			if ($this->targetSystem) {
				$out->write(["destination system", $this->targetSystem->getName()]);
			}
			if ($this->isParked) {
				$out->write("parked");
			}
		}
		$out->endChild();
	}
// 	
// 	
// 	
// 	const EsUuid &Ship::UUID() const noexcept
// 	{
// 		return uuid;
// 	}
// 	
// 	
// 	
// 	void Ship::SetUUID(const EsUuid &id)
// 	{
// 		uuid.clone(id);
// 	}
// 	
// 	
// 	
	public function getName(): string {
		return $this->name;
	}
	
	// Set / Get the name of this class of ships, e.g. "Marauder Raven."
	public function setTrueModelName(string $model): void {
		$this->trueModelName = $model;
	}
	
	public function getTrueModelName(): string {
		return $this->trueModelName;
	}
	
	public function getDisplayModelName(): string {
		return $this->displayModelName;
	}
	
	public function getPluralModelName(): string {
		return $this->pluralModelName;
	}
	
	// Get the name of this ship as a variant.
	public function getVariantName(): string {
		return $this->variantName == '' ? $this->trueModelName : $this->variantName;
	}
// 	
// 	
// 	
// 	// Get the generic noun (e.g. "ship") to be used when describing this ship.
// 	const string &Ship::Noun() const
// 	{
// 		static const string SHIP = "ship";
// 		return noun.empty() ? SHIP : noun;
// 	}
// 	
// 	
// 	
// 	// Get this ship's description.
// 	const string &Ship::Description() const
// 	{
// 		return description;
// 	}
// 	
// 	
// 	
// 	// Get the shipyard thumbnail for this ship.
// 	const Sprite *Ship::Thumbnail() const
// 	{
// 		return thumbnail;
// 	}
// 	
// 	
// 	
// 	// Get this ship's cost.
// 	int64_t Ship::Cost() const
// 	{
// 		return attributes.Cost();
// 	}
// 	
// 	
// 	
// 	// Get the cost of this ship's chassis, with no outfits installed.
// 	int64_t Ship::ChassisCost() const
// 	{
// 		return baseAttributes.Cost();
// 	}
// 	
// 	
// 	
// 	int64_t Ship::Strength() const
// 	{
// 		return Cost();
// 	}
// 	
// 	
// 	
// 	float Ship::Attraction() const
// 	{
// 		return attraction;
// 	}
// 	
// 	
// 	
// 	float Ship::Deterrence() const
// 	{
// 		return deterrence;
// 	}
// 	
// 	
// 	
// 	// Check if this ship is configured in such a way that it would be difficult
// 	// or impossible to fly.
// 	vector<string> Ship::FlightCheck() const
// 	{
// 		auto checks = vector<string>{};
// 	
// 		float generation = attributes.Get("energy generation") - attributes.Get("energy consumption");
// 		float consuming = attributes.Get("fuel energy");
// 		float solar = attributes.Get("solar collection");
// 		float battery = attributes.Get("energy capacity");
// 		float energy = generation + consuming + solar + battery;
// 		float fuelChange = attributes.Get("fuel generation") - attributes.Get("fuel consumption");
// 		float fuelCapacity = attributes.Get("fuel capacity");
// 		float fuel = fuelCapacity + fuelChange;
// 		float thrust = attributes.Get("thrust");
// 		float reverseThrust = attributes.Get("reverse thrust");
// 		float afterburner = attributes.Get("afterburner thrust");
// 		float thrustEnergy = attributes.Get("thrusting energy");
// 		float turn = attributes.Get("turn");
// 		float turnEnergy = attributes.Get("turning energy");
// 		float hyperDrive = navigation.HasHyperdrive();
// 		float jumpDrive = navigation.HasJumpDrive();
// 	
// 		// Report the first error condition that will prevent takeoff:
// 		if (IdleHeat() >= MaximumHeat()) {
// 			checks.emplace_back("overheating!");
// 		} else if (energy <= 0.) {
// 			checks.emplace_back("no energy!");
// 		} else if ((energy - consuming <= 0.) && (fuel <= 0.)) {
// 			checks.emplace_back("no fuel!");
// 		} else if (!thrust && !reverseThrust && !afterburner) {
// 			checks.emplace_back("no thruster!");
// 		} else if (!turn) {
// 			checks.emplace_back("no steering!");
// 	
// 		// If no errors were found, check all warning conditions:
// 		if (checks.empty()) {
// 
// 			if (RequiredCrew() > attributes.Get("bunks")) {
// 				checks.emplace_back("insufficient bunks?");
// 			if (!thrust && !reverseThrust) {
// 				checks.emplace_back("afterburner only?");
// 			if (!thrust && !afterburner) {
// 				checks.emplace_back("reverse only?");
// 			if (!generation && !solar && !consuming) {
// 				checks.emplace_back("battery only?");
// 			if (energy < thrustEnergy) {
// 				checks.emplace_back("limited thrust?");
// 			if (energy < turnEnergy) {
// 				checks.emplace_back("limited turn?");
// 			if (energy - .8 * solar < .2 * (turnEnergy + thrustEnergy)) {
// 				checks.emplace_back("solar power?");
// 			if (fuel < 0.) {
// 				checks.emplace_back("fuel?");
// 			if (!canBeCarried) {
// 
// 				if (!hyperDrive && !jumpDrive) {
// 					checks.emplace_back("no hyperdrive?");
// 				if (fuelCapacity < navigation.JumpFuel()) {
// 					checks.emplace_back("no fuel?");
// 			}
// 			for (const auto &it : outfits) {
// 				if (it.first->IsWeapon() && it.first->FiringEnergy() > energy) {
// 
// 					checks.emplace_back("insufficient energy to fire?");
// 					break;
// 				}
// 		}
// 	
// 		return checks;
// 	}
// 	
// 	
// 	
// 	void Ship::SetPosition(Point position)
// 	{
// 		this->position = position;
// 	}
// 	
// 	
// 	
// 	// Instantiate a newly-created ship in-flight.
// 	void Ship::Place(Point position, Point velocity, Angle angle, bool isDeparting)
// 	{
// 		this->position = position;
// 		this->velocity = velocity;
// 		this->angle = angle;
// 	
// 		// If landed, place the ship right above the planet.
// 		// Escorts should take off a bit behind their flagships.
// 		if (landingPlanet) {
// 
// 			landingPlanet = nullptr;
// 			zoom = parent.lock() ? (-.2 + -.8 * Random::Real()) : 0.;
// 		} else
// 			zoom = 1.;
// 		// Make sure various special status values are reset.
// 		heat = IdleHeat();
// 		ionization = 0.;
// 		scrambling = 0.;
// 		disruption = 0.;
// 		slowness = 0.;
// 		discharge = 0.;
// 		corrosion = 0.;
// 		leakage = 0.;
// 		burning = 0.;
// 		shieldDelay = 0;
// 		hullDelay = 0;
// 		isInvisible = !HasSprite();
// 		jettisoned.clear();
// 		hyperspaceCount = 0;
// 		forget = 1;
// 		targetShip.reset();
// 		shipToAssist.reset();
// 		if (isDeparting) {
// 			lingerSteps = 0;
// 	
// 		// The swizzle is only updated if this ship has a government or when it is departing
// 		// from a planet. Launching a carry from a carrier does not update its swizzle.
// 		if (government && isDeparting) {
// 
// 			auto swizzle = customSwizzle >= 0 ? customSwizzle : government->GetSwizzle();
// 			SetSwizzle(swizzle);
// 	
// 			// Set swizzle for any carried ships too.
// 			for (const auto &bay : bays) {
// 
// 				if (bay.ship) {
// 					bay.ship->SetSwizzle(bay.ship->customSwizzle >= 0 ? bay.ship->customSwizzle : swizzle);
// 			}
// 		}
// 	}
// 	
// 	
// 	
// 	// Set the name of this particular ship.
// 	void Ship::SetName(const string &name)
// 	{
// 		this->name = name;
// 	}
// 	
// 	
// 	
// 	// Set which system this ship is in.
// 	void Ship::SetSystem(const System *system)
// 	{
// 		currentSystem = system;
// 		navigation.SetSystem(system);
// 	}
// 	
// 	
// 	
// 	void Ship::SetPlanet(const Planet *planet)
// 	{
// 		zoom = !planet;
// 		landingPlanet = planet;
// 	}
// 	
// 	
// 	
// 	void Ship::SetGovernment(const Government *government)
// 	{
// 		if (government) {
// 			SetSwizzle(customSwizzle >= 0 ? customSwizzle : government->GetSwizzle());
// 		this->government = government;
// 	}
// 	
// 	
// 	
// 	void Ship::SetIsSpecial(bool special)
// 	{
// 		isSpecial = special;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsSpecial() const
// 	{
// 		return isSpecial;
// 	}
// 	
// 	
// 	
// 	void Ship::SetIsYours(bool yours)
// 	{
// 		isYours = yours;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsYours() const
// 	{
// 		return isYours;
// 	}
// 	
// 	
// 	
// 	void Ship::SetIsParked(bool parked)
// 	{
// 		isParked = parked;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsParked() const
// 	{
// 		return isParked;
// 	}
// 	
// 	
// 	
// 	bool Ship::HasDeployOrder() const
// 	{
// 		return shouldDeploy;
// 	}
// 	
// 	
// 	
// 	void Ship::SetDeployOrder(bool shouldDeploy)
// 	{
// 		this->shouldDeploy = shouldDeploy;
// 	}
// 	
// 	
// 	
// 	const Personality &Ship::GetPersonality() const
// 	{
// 		return personality;
// 	}
// 	
// 	
// 	
// 	void Ship::SetPersonality(const Personality &other)
// 	{
// 		personality = other;
// 	}
// 	
// 	
// 	
// 	const Phrase *Ship::GetHailPhrase() const
// 	{
// 		return hail;
// 	}
// 	
// 	
// 	
// 	void Ship::SetHailPhrase(const Phrase &phrase)
// 	{
// 		hail = &phrase;
// 	}
// 	
// 	
// 	
// 	string Ship::GetHail(map<string, string> &&subs) const
// 	{
// 		string hailStr = hail ? hail->Get() : government ? government->GetHail(isDisabled) : "";
// 	
// 		if (hailStr.empty()) {
// 			return hailStr;
// 	
// 		subs["<npc>"] = Name();
// 		return Format::Replace(hailStr, subs);
// 	}
// 	
// 	
// 	
// 	ShipAICache &Ship::GetAICache()
// 	{
// 		return aiCache;
// 	}
// 	
// 	
// 	
// 	void Ship::UpdateCaches()
// 	{
// 		aiCache.Recalibrate(*this);
// 		navigation.Recalibrate(*this);
// 	}
// 	
// 	
// 	
// 	bool Ship::CanSendHail(const PlayerInfo &player, bool allowUntranslated) const
// 	{
// 		const System *playerSystem = player.GetSystem();
// 		if (!playerSystem) {
// 			return false;
// 	
// 		// Make sure this ship is in the same system as the player.
// 		if (GetSystem() != playerSystem) {
// 			return false;
// 	
// 		// Player ships shouldn't send hails.
// 		const Government *gov = GetGovernment();
// 		if (!gov || IsYours()) {
// 			return false;
// 	
// 		// Make sure this ship is able to send a hail.
// 		if (IsDisabled() || !Crew() || Cloaking() >= 1. || GetPersonality().IsMute()) {
// 			return false;
// 	
// 		// Ships that don't share a language with the player shouldn't communicate when hailed directly.
// 		// Only random event hails should work, and only if the government explicitly has
// 		// untranslated hails. This is ensured by the allowUntranslated argument.
// 		if (!(allowUntranslated && gov->SendUntranslatedHails()) {
// 				&& !gov->Language().empty() && !player.Conditions().Get("language: " + gov->Language()))
// 			return false;
// 	
// 		return true;
// 	}
// 	
// 	
// 	
// 	// Set the commands for this ship to follow this timestep.
// 	void Ship::SetCommands(const Command &command)
// 	{
// 		commands = command;
// 	}
// 	
// 	
// 	
// 	void Ship::SetCommands(const FireCommand &firingCommand)
// 	{
// 		firingCommands.UpdateWith(firingCommand);
// 	}
// 	
// 	
// 	
// 	const Command &Ship::Commands() const
// 	{
// 		return commands;
// 	}
// 	
// 	
// 	
// 	const FireCommand &Ship::FiringCommands() const noexcept
// 	{
// 		return firingCommands;
// 	}
// 	
// 	
// 	
// 	// Move this ship. A ship may create effects as it moves, in particular if
// 	// it is in the process of blowing up. If this returns false, the ship
// 	// should be deleted.
// 	void Ship::Move(vector<Visual> &visuals, list<shared_ptr<Flotsam>> &flotsam)
// 	{
// 		// Do nothing with ships that are being forgotten.
// 		if (StepFlags()) {
// 			return;
// 	
// 		// We're done if the ship was destroyed.
// 		const int destroyResult = StepDestroyed(visuals, flotsam);
// 		if (destroyResult > 0) {
// 			return;
// 	
// 		const bool isBeingDestroyed = destroyResult;
// 	
// 		// Generate energy, heat, etc. if we're not being destroyed.
// 		if (!isBeingDestroyed) {
// 			DoGeneration();
// 	
// 		DoPassiveEffects(visuals, flotsam);
// 		DoJettison(flotsam);
// 		DoCloakDecision();
// 	
// 		bool isUsingAfterburner = false;
// 	
// 		// Don't let the ship do anything } else if it is being destroyed.
// 		if (!isBeingDestroyed) {
// 
// 			// See if the ship is entering hyperspace.
// 			// If it is, nothing more needs to be done here.
// 			if (DoHyperspaceLogic(visuals)) {
// 				return;
// 	
// 			// Check if we're trying to land.
// 			// If we landed, we're done.
// 			if (DoLandingLogic()) {
// 				return;
// 	
// 			// Move the turrets.
// 			if (!isDisabled) {
// 				armament.Aim(firingCommands);
// 	
// 			DoInitializeMovement();
// 			StepPilot();
// 			DoMovement(isUsingAfterburner);
// 			StepTargeting();
// 		}
// 	
// 		// Move the ship.
// 		position += velocity;
// 	
// 		// Show afterburner flares unless the ship is being destroyed.
// 		if (!isBeingDestroyed) {
// 			DoEngineVisuals(visuals, isUsingAfterburner);
// 	}
// 	
// 	
// 	
// 	// Launch any ships that are ready to launch.
// 	void Ship::Launch(list<shared_ptr<Ship>> &ships, vector<Visual> &visuals)
// 	{
// 		// Allow carried ships to launch from a disabled ship, but not from a ship that
// 		// is landing, jumping, or cloaked. If already destroyed (e.g. self-destructing),
// 		// eject any ships still docked, possibly destroying them in the process.
// 		bool ejecting = IsDestroyed();
// 		if (!ejecting && (!commands.Has(Command::DEPLOY) || zoom != 1.f || hyperspaceCount || cloak)) {
// 			return;
// 	
// 		for (Bay &bay : bays) {
// 			if (bay.ship
// 				&& ((bay.ship->Commands().Has(Command::DEPLOY) && !Random::Int(40 + 20 * !bay.ship->attributes.Get("automaton")))
// 				|| (ejecting && !Random::Int(6))))
// 			{
// 				// Resupply any ships launching of their own accord.
// 				if (!ejecting) {
// 
// 					// Determine which of the fighter's weapons we can restock.
// 					auto restockable = bay.ship->GetArmament().RestockableAmmo();
// 					auto toRestock = map<const Outfit *, int>{};
// 					for (auto &&ammo : restockable) {
// 
// 						int count = OutfitCount(ammo);
// 						if (count > 0) {
// 							toRestock.emplace(ammo, count);
// 					}
// 					auto takenAmmo = TransferAmmo(toRestock, *this, *bay.ship);
// 					bool tookAmmo = !takenAmmo.empty();
// 					if (tookAmmo) {
// 
// 						// Update the carried mass cache.
// 						for (auto &&item : takenAmmo) {
// 							carriedMass += item.first->Mass() * item.second;
// 					}
// 	
// 					// This ship will refuel naturally based on the carrier's fuel
// 					// collection, but the carrier may have some reserves to spare.
// 					float maxFuel = bay.ship->attributes.Get("fuel capacity");
// 					if (maxFuel) {
// 
// 						float spareFuel = fuel - navigation.JumpFuel();
// 						if (spareFuel > 0.) {
// 							TransferFuel(spareFuel, bay.ship.get());
// 						// If still low or out-of-fuel, re-stock the carrier and don't
// 						// launch, except if some ammo was taken (since we can fight).
// 						if (!tookAmmo && bay.ship->fuel < .25 * maxFuel) {
// 
// 							TransferFuel(bay.ship->fuel, this);
// 							continue;
// 						}
// 					}
// 				}
// 				// Those being ejected may be destroyed if they are already injured.
// 				} else if (bay.ship->Health() < Random::Real()) {
// 					bay.ship->SelfDestruct();
// 	
// 				ships.push_back(bay.ship);
// 				float maxV = bay.ship->MaxVelocity() * (1 + bay.ship->IsDestroyed());
// 				Point exitPoint = position + angle.Rotate(bay.point);
// 				// When ejected, ships depart haphazardly.
// 				Angle launchAngle = ejecting ? Angle(exitPoint - position) : angle + bay.facing;
// 				Point v = velocity + (.3 * maxV) * launchAngle.Unit() + (.2 * maxV) * Angle::Random().Unit();
// 				bay.ship->Place(exitPoint, v, launchAngle, false);
// 				bay.ship->SetSystem(currentSystem);
// 				bay.ship->SetParent(shared_from_this());
// 				bay.ship->UnmarkForRemoval();
// 				// Update the cached sum of carried ship masses.
// 				carriedMass -= bay.ship->Mass();
// 				// Create the desired launch effects.
// 				for (const Effect *effect : bay.launchEffects) {
// 					visuals.emplace_back(*effect, exitPoint, velocity, launchAngle);
// 	
// 				bay.ship.reset();
// 			}
// 	}
// 	
// 	
// 	
// 	// Check if this ship is boarding another ship.
// 	shared_ptr<Ship> Ship::Board(bool autoPlunder, bool nonDocking)
// 	{
// 		if (!hasBoarded) {
// 			return shared_ptr<Ship>();
// 		hasBoarded = false;
// 	
// 		shared_ptr<Ship> victim = GetTargetShip();
// 		if (CannotAct() || !victim || victim->IsDestroyed() || victim->GetSystem() != GetSystem()) {
// 			return shared_ptr<Ship>();
// 	
// 		// For a fighter or drone, "board" means "return to ship." Except when the ship is
// 		// explicitly of the nonDocking type.
// 		if (CanBeCarried() && !nonDocking) {
// 
// 			SetTargetShip(shared_ptr<Ship>());
// 			if (!victim->IsDisabled() && victim->GetGovernment() == government) {
// 				victim->Carry(shared_from_this());
// 			return shared_ptr<Ship>();
// 		}
// 	
// 		// Board a friendly ship, to repair or refuel it.
// 		if (!government->IsEnemy(victim->GetGovernment())) {
// 
// 			SetShipToAssist(shared_ptr<Ship>());
// 			SetTargetShip(shared_ptr<Ship>());
// 			bool helped = victim->isDisabled;
// 			victim->hull = min(max(victim->hull, victim->MinimumHull() * 1.5), victim->attributes.Get("hull"));
// 			victim->isDisabled = false;
// 			// Transfer some fuel if needed.
// 			if (victim->NeedsFuel() && CanRefuel(*victim)) {
// 
// 				helped = true;
// 				TransferFuel(victim->JumpFuelMissing(), victim.get());
// 			}
// 			if (helped) {
// 
// 				pilotError = 120;
// 				victim->pilotError = 120;
// 			}
// 			return victim;
// 		}
// 		if (!victim->IsDisabled()) {
// 			return shared_ptr<Ship>();
// 	
// 		// If the boarding ship is the player, they will choose what to plunder.
// 		// Always take fuel if you can.
// 		victim->TransferFuel(victim->fuel, this);
// 		if (autoPlunder) {
// 
// 			// Take any commodities that fit.
// 			victim->cargo.TransferAll(cargo, false);
// 	
// 			// Pause for two seconds before moving on.
// 			pilotError = 120;
// 		}
// 	
// 		// Stop targeting this ship (so you will not board it again right away).
// 		if (!autoPlunder || personality.Disables()) {
// 			SetTargetShip(shared_ptr<Ship>());
// 		return victim;
// 	}
// 	
// 	
// 	
// 	// Scan the target, if able and commanded to. Return a ShipEvent bitmask
// 	// giving the types of scan that succeeded.
// 	int Ship::Scan(const PlayerInfo &player)
// 	{
// 		if (!commands.Has(Command::SCAN) || CannotAct()) {
// 			return 0;
// 	
// 		shared_ptr<const Ship> target = GetTargetShip();
// 		if (!(target && target->IsTargetable())) {
// 			return 0;
// 	
// 		// The range of a scanner is proportional to the square root of its power.
// 		// Because of Pythagoras, if we use square-distance, we can skip this square root.
// 		float cargoDistanceSquared = attributes.Get("cargo scan power");
// 		float outfitDistanceSquared = attributes.Get("outfit scan power");
// 	
// 		// Bail out if this ship has no scanners.
// 		if (!cargoDistanceSquared && !outfitDistanceSquared) {
// 			return 0;
// 	
// 		float cargoSpeed = attributes.Get("cargo scan efficiency");
// 		if (!cargoSpeed) {
// 			cargoSpeed = cargoDistanceSquared;
// 	
// 		float outfitSpeed = attributes.Get("outfit scan efficiency");
// 		if (!outfitSpeed) {
// 			outfitSpeed = outfitDistanceSquared;
// 	
// 		// Check how close this ship is to the target it is trying to scan.
// 		// To normalize 1 "scan power" to reach 100 pixels, divide this square distance by 100^2, or multiply by 0.0001.
// 		// Because this uses distance squared, to reach 200 pixels away you need 4 "scan power".
// 		float distanceSquared = target->position.DistanceSquared(position) * .0001;
// 	
// 		// Check the target's outfit and cargo space. A larger ship takes longer to scan.
// 		// Normalized around 200 tons of cargo/outfit space.
// 		// A ship with less than 10 tons of outfit space or cargo space takes as long to
// 		// scan as one with 10 tons. This avoids small sizes being scanned instantly, or
// 		// causing a divide by zero error at sizes of 0.
// 		// If instantly scanning very small ships is desirable, this can be removed.
// 		// One point of scan opacity is the equivalent of an additional ton of cargo / outfit space
// 		float outfits = max(10., (target->baseAttributes.Get("outfit space")
// 			+ target->attributes.Get("outfit scan opacity"))) * .005;
// 		float cargo = max(10., (target->attributes.Get("cargo space")
// 			+ target->attributes.Get("cargo scan opacity"))) * .005;
// 	
// 		// Check if either scanner has finished scanning.
// 		bool startedScanning = false;
// 		bool activeScanning = false;
// 		int result = 0;
// 		auto doScan = [&distanceSquared, &startedScanning, &activeScanning, &result]
// 				(float &elapsed, const float speed, const float scannerRange,
// 						const float depth, const int event)
// 		-> void
// 		{
// 			if (elapsed < SCAN_TIME && distanceSquared < scannerRange) {
// 
// 				startedScanning |= !elapsed;
// 				activeScanning = true;
// 	
// 				// Division is more expensive to calculate than multiplication,
// 				// so rearrange the formula to minimize divisions.
// 	
// 				// "(scannerRange - 0.5 * distance) / scannerRange"
// 				// This line hits 1 at distace = 0, and 0.5 at distance = scannerRange.
// 				// There is also a hard cap on scanning range.
// 	
// 				// "speed / (sqrt(speed) + distance)"
// 				// This gives a modest speed boost at no distance, and
// 				// the boost tapers off to 0 at arbitrarily large distances.
// 	
// 				// "1 / depth"
// 				// This makes scan time proportional to cargo or outfit space.
// 	
// 				elapsed += ((scannerRange - .5 * distanceSquared) * speed)
// 					/ (scannerRange * (sqrt(speed) + distanceSquared) * depth);
// 	
// 				if (elapsed >= SCAN_TIME) {
// 					result |= event;
// 			}
// 		};
// 		doScan(cargoScan, cargoSpeed, cargoDistanceSquared, cargo, ShipEvent::SCAN_CARGO);
// 		doScan(outfitScan, outfitSpeed, outfitDistanceSquared, outfits, ShipEvent::SCAN_OUTFITS);
// 	
// 		// Play the scanning sound if the actor or the target is the player's ship.
// 		if (isYours || (target->isYours && activeScanning)) {
// 			Audio::Play(Audio::Get("scan"), Position());
// 	
// 		bool isImportant = false;
// 		if (target->isYours) {
// 			isImportant = target.get() == player.Flagship() || government->FinesContents(target.get());
// 	
// 		if (startedScanning && isYours) {
// 
// 			if (!target->Name().empty()) {
// 				Messages::Add("Attempting to scan the " + target->Noun() + " \"" + target->Name() + "\"."
// 					, Messages::Importance::Low);
// 			} else
// 				Messages::Add("Attempting to scan the selected " + target->Noun() + "."
// 					, Messages::Importance::Low);
// 	
// 			if (target->GetGovernment()->IsProvokedOnScan() && target->CanSendHail(player)) {
// 
// 				// If this ship has no name, show its model name instead.
// 				string tag;
// 				const string &gov = target->GetGovernment()->GetName();
// 				if (!target->Name().empty()) {
// 					tag = gov + " " + target->Noun() + " \"" + target->Name() + "\": ";
// 				} else
// 					tag = target->DisplayModelName() + " (" + gov + "): ";
// 				Messages::Add(tag + "Please refrain from scanning us or we will be forced to take action.",
// 					Messages::Importance::Highest);
// 			}
// 		} else if (startedScanning && target->isYours && isImportant) {
// 			Messages::Add("The " + government->GetName() + " " + Noun() + " \""
// 					+ Name() + "\" is attempting to scan your ship \"" + target->Name() + "\".",
// 					Messages::Importance::Low);
// 	
// 		if (target->isYours && !isYours && isImportant) {
// 
// 			if (result & ShipEvent::SCAN_CARGO) {
// 				Messages::Add("The " + government->GetName() + " " + Noun() + " \""
// 						+ Name() + "\" completed its cargo scan of your ship \"" + target->Name() + "\".",
// 						Messages::Importance::High);
// 			if (result & ShipEvent::SCAN_OUTFITS) {
// 				Messages::Add("The " + government->GetName() + " " + Noun() + " \""
// 						+ Name() + "\" completed its outfit scan of your ship \"" + target->Name()
// 						+ (target->Attributes().Get("inscrutable") > 0. ? "\" with no useful results." : "\"."),
// 						Messages::Importance::High);
// 		}
// 	
// 		// Some governments are provoked when a scan is completed on one of their ships.
// 		const Government *gov = target->GetGovernment();
// 		if (result && gov && gov->IsProvokedOnScan() && !gov->IsEnemy(government) {
// 				&& (target->Shields() < .9 || target->Hull() < .9 || !target->GetPersonality().IsForbearing())
// 				&& !target->GetPersonality().IsPacifist())
// 			result |= ShipEvent::PROVOKE;
// 	
// 		return result;
// 	}
// 	
// 	
// 	
// 	// Find out what fraction of the scan is complete.
// 	float Ship::CargoScanFraction() const
// 	{
// 		return cargoScan / SCAN_TIME;
// 	}
// 	
// 	
// 	
// 	float Ship::OutfitScanFraction() const
// 	{
// 		return outfitScan / SCAN_TIME;
// 	}
// 	
// 	
// 	
// 	// Fire any weapons that are ready to fire. If an anti-missile is ready,
// 	// instead of firing here this function returns true and it can be fired if
// 	// collision detection finds a missile in range.
// 	bool Ship::Fire(vector<Projectile> &projectiles, vector<Visual> &visuals)
// 	{
// 		isInSystem = true;
// 		forget = 0;
// 	
// 		// A ship that is about to die creates a special single-turn "projectile"
// 		// representing its death explosion.
// 		if (IsDestroyed() && explosionCount == explosionTotal && explosionWeapon) {
// 			projectiles.emplace_back(position, explosionWeapon);
// 	
// 		if (CannotAct()) {
// 			return false;
// 	
// 		antiMissileRange = 0.;
// 	
// 		float jamChance = CalculateJamChance(Energy(), scrambling);
// 	
// 		const vector<Hardpoint> &hardpoints = armament.Get();
// 		for (unsigned i = 0; i < hardpoints.size(); ++i) {
// 
// 			const Weapon *weapon = hardpoints[i].GetOutfit();
// 			if (weapon && CanFire(weapon)) {
// 
// 				if (weapon->AntiMissile()) {
// 					antiMissileRange = max(antiMissileRange, weapon->Velocity() + weaponRadius);
// 				} else if (firingCommands.HasFire(i)) {
// 					armament.Fire(i, *this, projectiles, visuals, Random::Real() < jamChance);
// 			}
// 		}
// 	
// 		armament.Step(*this);
// 	
// 		return antiMissileRange;
// 	}
// 	
// 	
// 	
// 	// Fire an anti-missile.
// 	bool Ship::FireAntiMissile(const Projectile &projectile, vector<Visual> &visuals)
// 	{
// 		if (projectile.Position().Distance(position) > antiMissileRange) {
// 			return false;
// 		if (CannotAct()) {
// 			return false;
// 	
// 		float jamChance = CalculateJamChance(Energy(), scrambling);
// 	
// 		const vector<Hardpoint> &hardpoints = armament.Get();
// 		for (unsigned i = 0; i < hardpoints.size(); ++i) {
// 
// 			const Weapon *weapon = hardpoints[i].GetOutfit();
// 			if (weapon && CanFire(weapon)) {
// 				if (armament.FireAntiMissile(i, *this, projectile, visuals, Random::Real() < jamChance)) {
// 					return true;
// 		}
// 	
// 		return false;
// 	}
// 	
// 	
// 	
// 	const System *Ship::GetSystem() const
// 	{
// 		return currentSystem;
// 	}
// 	
// 	
// 	
// 	const System *Ship::GetActualSystem() const
// 	{
// 		auto p = GetParent();
// 		return currentSystem ? currentSystem : (p ? p->GetSystem() : nullptr);
// 	}
// 	
// 	
// 	
// 	// If the ship is landed, get the planet it has landed on.
// 	const Planet *Ship::GetPlanet() const
// 	{
// 		return zoom ? nullptr : landingPlanet;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsCapturable() const
// 	{
// 		return isCapturable;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsTargetable() const
// 	{
// 		return (zoom == 1.f && !explosionRate && !forget && !isInvisible && cloak < 1. && hull >= 0. && hyperspaceCount < 70);
// 	}
// 	
// 	
// 	
// 	bool Ship::IsOverheated() const
// 	{
// 		return isOverheated;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsDisabled() const
// 	{
// 		if (!isDisabled) {
// 			return false;
// 	
// 		float minimumHull = MinimumHull();
// 		bool needsCrew = RequiredCrew() != 0;
// 		return (hull < minimumHull || (!crew && needsCrew));
// 	}
// 	
// 	
// 	
// 	bool Ship::IsBoarding() const
// 	{
// 		return isBoarding;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsLanding() const
// 	{
// 		return landingPlanet;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsFleeing() const
// 	{
// 		return isFleeing;
// 	}
// 	
// 	
// 	
// 	// Check if this ship is currently able to begin landing on its target.
// 	bool Ship::CanLand() const
// 	{
// 		if (!GetTargetStellar() || !GetTargetStellar()->GetPlanet() || isDisabled || IsDestroyed()) {
// 			return false;
// 	
// 		if (!GetTargetStellar()->GetPlanet()->CanLand(*this)) {
// 			return false;
// 	
// 		Point distance = GetTargetStellar()->Position() - position;
// 		float speed = velocity.Length();
// 	
// 		return (speed < 1. && distance.Length() < GetTargetStellar()->Radius());
// 	}
// 	
// 	
// 	
// 	bool Ship::CannotAct() const
// 	{
// 		return (zoom != 1.f || isDisabled || hyperspaceCount || pilotError || cloak);
// 	}
// 	
// 	
// 	
// 	float Ship::Cloaking() const
// 	{
// 		return isInvisible ? 1. : cloak;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsEnteringHyperspace() const
// 	{
// 		return hyperspaceSystem;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsHyperspacing() const
// 	{
// 		return hyperspaceCount != 0;
// 	}
// 	
// 	
// 	
// 	// Check if this ship is hyperspacing, specifically via a jump drive.
// 	bool Ship::IsUsingJumpDrive() const
// 	{
// 		return (hyperspaceSystem || hyperspaceCount) && isUsingJumpDrive;
// 	}
// 	
// 	
// 	
// 	// Check if this ship is currently able to enter hyperspace to its target.
// 	bool Ship::IsReadyToJump(bool waitingIsReady) const
// 	{
// 		// Ships can't jump while waiting for someone } else, carried, or if already jumping.
// 		if (IsDisabled() || (!waitingIsReady && commands.Has(Command::WAIT)) {
// 				|| hyperspaceCount || !targetSystem || !currentSystem)
// 			return false;
// 	
// 		// Check if the target system is valid and there is enough fuel to jump.
// 		pair<JumpType, float> jumpUsed = navigation.GetCheapestJumpType(targetSystem);
// 		float fuelCost = jumpUsed.second;
// 		if (!fuelCost || fuel < fuelCost) {
// 			return false;
// 	
// 		Point direction = targetSystem->Position() - currentSystem->Position();
// 		bool isJump = (jumpUsed.first == JumpType::JUMP_DRIVE);
// 		float scramThreshold = attributes.Get("scram drive");
// 	
// 		// If the system has a departure distance the ship is only allowed to leave the system
// 		// if it is beyond this distance.
// 		float departure = isJump ?
// 			currentSystem->JumpDepartureDistance() * currentSystem->JumpDepartureDistance()
// 			: currentSystem->HyperDepartureDistance() * currentSystem->HyperDepartureDistance();
// 		if (position.LengthSquared() <= departure) {
// 			return false;
// 	
// 	
// 		// The ship can only enter hyperspace if it is traveling slowly enough
// 		// and pointed in the right direction.
// 		if (!isJump && scramThreshold) {
// 
// 			float deviation = fabs(direction.Unit().Cross(velocity));
// 			if (deviation > scramThreshold) {
// 				return false;
// 		} else if (velocity.Length() > attributes.Get("jump speed")) {
// 			return false;
// 	
// 		if (!isJump) {
// 
// 			// Figure out if we're within one turn step of facing this system.
// 			bool left = direction.Cross(angle.Unit()) < 0.;
// 			Angle turned = angle + TurnRate() * (left - !left);
// 			bool stillLeft = direction.Cross(turned.Unit()) < 0.;
// 	
// 			if (left == stillLeft) {
// 				return false;
// 		}
// 	
// 		return true;
// 	}
// 	
// 	
// 	
// 	// Get this ship's custom swizzle.
// 	int Ship::CustomSwizzle() const
// 	{
// 		return customSwizzle;
// 	}
// 	
// 	
// 	// Check if the ship is thrusting. If so, the engine sound should be played.
// 	bool Ship::IsThrusting() const
// 	{
// 		return isThrusting;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsReversing() const
// 	{
// 		return isReversing;
// 	}
// 	
// 	
// 	
// 	bool Ship::IsSteering() const
// 	{
// 		return isSteering;
// 	}
// 	
// 	
// 	
// 	float Ship::SteeringDirection() const
// 	{
// 		return steeringDirection;
// 	}
// 	
// 	
// 	
// 	// Get the points from which engine flares should be drawn.
// 	const vector<Ship::EnginePoint> &Ship::EnginePoints() const
// 	{
// 		return enginePoints;
// 	}
// 	
// 	
// 	
// 	const vector<Ship::EnginePoint> &Ship::ReverseEnginePoints() const
// 	{
// 		return reverseEnginePoints;
// 	}
// 	
// 	
// 	
// 	const vector<Ship::EnginePoint> &Ship::SteeringEnginePoints() const
// 	{
// 		return steeringEnginePoints;
// 	}
// 	
// 	
// 	
// 	// Reduce a ship's hull to low enough to disable it. This is so a ship can be
// 	// created as a derelict.
// 	void Ship::Disable()
// 	{
// 		shields = 0.;
// 		hull = min(hull, .5 * MinimumHull());
// 		isDisabled = true;
// 	}
// 	
// 	
// 	
// 	// Mark a ship as destroyed.
// 	void Ship::Destroy()
// 	{
// 		hull = -1.;
// 	}
// 	
// 	
// 	
// 	// Trigger the death of this ship.
// 	void Ship::SelfDestruct()
// 	{
// 		Destroy();
// 		explosionRate = 1024;
// 	}
// 	
// 	
// 	
// 	void Ship::Restore()
// 	{
// 		hull = 0.;
// 		explosionCount = 0;
// 		explosionRate = 0;
// 		UnmarkForRemoval();
// 		Recharge(true);
// 	}
// 	
// 	
// 	
// 	bool Ship::IsDamaged() const
// 	{
// 		// Account for ships with no shields when determining if they're damaged.
// 		return (attributes.Get("shields") != 0 && Shields() != 1.) || Hull() != 1.;
// 	}
// 	
// 	
// 	
// 	// Check if this ship has been destroyed.
// 	bool Ship::IsDestroyed() const
// 	{
// 		return (hull < 0.);
// 	}
// 	
// 	
// 	
// 	// Recharge and repair this ship (e.g. because it has landed).
// 	void Ship::Recharge(bool atSpaceport)
// 	{
// 		if (IsDestroyed()) {
// 			return;
// 	
// 		if (atSpaceport) {
// 			crew = min<int>(max(crew, RequiredCrew()), attributes.Get("bunks"));
// 		pilotError = 0;
// 		pilotOkay = 0;
// 	
// 		if (atSpaceport || attributes.Get("shield generation")) {
// 			shields = attributes.Get("shields");
// 		if (atSpaceport || attributes.Get("hull repair rate")) {
// 			hull = attributes.Get("hull");
// 		if (atSpaceport || attributes.Get("energy generation")) {
// 			energy = attributes.Get("energy capacity");
// 		if (atSpaceport || attributes.Get("fuel generation")) {
// 			fuel = attributes.Get("fuel capacity");
// 	
// 		heat = IdleHeat();
// 		ionization = 0.;
// 		scrambling = 0.;
// 		disruption = 0.;
// 		slowness = 0.;
// 		discharge = 0.;
// 		corrosion = 0.;
// 		leakage = 0.;
// 		burning = 0.;
// 		shieldDelay = 0;
// 		hullDelay = 0;
// 	}
// 	
// 	
// 	
// 	bool Ship::CanRefuel(const Ship &other) const
// 	{
// 		return (fuel - navigation.JumpFuel(targetSystem) >= other.JumpFuelMissing());
// 	}
// 	
// 	
// 	
// 	float Ship::TransferFuel(float amount, Ship *to)
// 	{
// 		amount = max(fuel - attributes.Get("fuel capacity"), amount);
// 		if (to) {
// 
// 			amount = min(to->attributes.Get("fuel capacity") - to->fuel, amount);
// 			to->fuel += amount;
// 		}
// 		fuel -= amount;
// 		return amount;
// 	}
// 	
// 	
// 	
// 	// Convert this ship from one government to another, as a result of boarding
// 	// actions (if the player is capturing) or player death (poor decision-making).
// 	// Returns the number of crew transferred from the capturer.
// 	int Ship::WasCaptured(const shared_ptr<Ship> &capturer)
// 	{
// 		// Repair up to the point where this ship is just barely not disabled.
// 		hull = min(max(hull, MinimumHull() * 1.5), attributes.Get("hull"));
// 		isDisabled = false;
// 	
// 		// Set the new government.
// 		government = capturer->GetGovernment();
// 	
// 		// Transfer some crew over. Only transfer the bare minimum unless even that
// 		// is not possible, in which case, share evenly.
// 		int totalRequired = capturer->RequiredCrew() + RequiredCrew();
// 		int transfer = RequiredCrew() - crew;
// 		if (transfer > 0) {
// 
// 			if (totalRequired > capturer->Crew() + crew) {
// 				transfer = max(crew ? 0 : 1, (capturer->Crew() * transfer) / totalRequired);
// 			capturer->AddCrew(-transfer);
// 			AddCrew(transfer);
// 		}
// 	
// 		// Clear this ship's previous targets.
// 		ClearTargetsAndOrders();
// 		// Set the capturer as this ship's parent.
// 		SetParent(capturer);
// 	
// 		// This ship behaves like its new parent does.
// 		isSpecial = capturer->isSpecial;
// 		isYours = capturer->isYours;
// 		personality = capturer->personality;
// 	
// 		// Fighters should flee a disabled ship, but if the player manages to capture
// 		// the ship before they flee, the fighters are captured, too.
// 		for (const Bay &bay : bays) {
// 			if (bay.ship) {
// 				bay.ship->WasCaptured(capturer);
// 		// If a flagship is captured, its escorts become independent.
// 		for (const auto &it : escorts) {
// 
// 			shared_ptr<Ship> escort = it.lock();
// 			if (escort) {
// 				escort->parent.reset();
// 		}
// 		// This ship should not care about its now-unallied escorts.
// 		escorts.clear();
// 	
// 		return transfer;
// 	}
// 	
// 	
// 	
// 	// Clear all orders and targets this ship has (after capture or transfer of control).
// 	void Ship::ClearTargetsAndOrders()
// 	{
// 		commands.Clear();
// 		firingCommands.Clear();
// 		SetTargetShip(shared_ptr<Ship>());
// 		SetTargetStellar(nullptr);
// 		SetTargetSystem(nullptr);
// 		shipToAssist.reset();
// 		targetAsteroid.reset();
// 		targetFlotsam.reset();
// 		hyperspaceSystem = nullptr;
// 		landingPlanet = nullptr;
// 	}
// 	
// 	
// 	
// 	// Get characteristics of this ship, as a fraction between 0 and 1.
// 	float Ship::Shields() const
// 	{
// 		float maximum = attributes.Get("shields");
// 		return maximum ? min(1., shields / maximum) : 0.;
// 	}
// 	
// 	
// 	
// 	float Ship::Hull() const
// 	{
// 		float maximum = attributes.Get("hull");
// 		return maximum ? min(1., hull / maximum) : 1.;
// 	}
// 	
// 	
// 	
// 	float Ship::Fuel() const
// 	{
// 		float maximum = attributes.Get("fuel capacity");
// 		return maximum ? min(1., fuel / maximum) : 0.;
// 	}
// 	
// 	
// 	
// 	float Ship::Energy() const
// 	{
// 		float maximum = attributes.Get("energy capacity");
// 		return maximum ? min(1., energy / maximum) : (hull > 0.) ? 1. : 0.;
// 	}
// 	
// 	
// 	
// 	// Allow returning a heat value greater than 1 (i.e. conveying how overheated
// 	// this ship has become).
// 	float Ship::Heat() const
// 	{
// 		float maximum = MaximumHeat();
// 		return maximum ? heat / maximum : 1.;
// 	}
// 	
// 	
// 	
// 	// Get the ship's "health," where <=0 is disabled and 1 means full health.
// 	float Ship::Health() const
// 	{
// 		float minimumHull = MinimumHull();
// 		float hullDivisor = attributes.Get("hull") - minimumHull;
// 		float divisor = attributes.Get("shields") + hullDivisor;
// 		// This should not happen, but just in case.
// 		if (divisor <= 0. || hullDivisor <= 0.) {
// 			return 0.;
// 	
// 		float spareHull = hull - minimumHull;
// 		// Consider hull-only and pooled health, compensating for any reductions by disruption damage.
// 		return min(spareHull / hullDivisor, (spareHull + shields / (1. + disruption * .01)) / divisor);
// 	}
// 	
// 	
// 	
// 	// Get the hull fraction at which this ship is disabled.
// 	float Ship::DisabledHull() const
// 	{
// 		float hull = attributes.Get("hull");
// 		float minimumHull = MinimumHull();
// 	
// 		return (hull > 0. ? minimumHull / hull : 0.);
// 	}
// 	
// 	
// 	
// 	// Get the actual shield level of the ship.
// 	float Ship::ShieldLevel() const
// 	{
// 		return shields;
// 	}
// 	
// 	
// 	
// 	// Get how disrupted this ship's shields are.
// 	float Ship::DisruptionLevel() const
// 	{
// 		return disruption;
// 	}
// 	
// 	
// 	
// 	// Get the (absolute) amount of hull that needs to be damaged until the
// 	// ship becomes disabled. Returns 0 if the ships hull is already below the
// 	// disabled threshold.
// 	float Ship::HullUntilDisabled() const
// 	{
// 		// Ships become disabled when they surpass their minimum hull threshold,
// 		// not when they are directly on it, so account for this by adding a small amount
// 		// of hull above the current hull level.
// 		return max(0., hull + 0.25 - MinimumHull());
// 	}
// 	
// 	
// 	
// 	const ShipJumpNavigation &Ship::JumpNavigation() const
// 	{
// 		return navigation;
// 	}
// 	
// 	
// 	
// 	int Ship::JumpsRemaining(bool followParent) const
// 	{
// 		// Make sure this ship has some sort of hyperdrive, and if so return how
// 		// many jumps it can make.
// 		float jumpFuel = 0.;
// 		if (!targetSystem && followParent) {
// 
// 			// If this ship has no destination, the parent's substitutes for it,
// 			// but only if the location is reachable.
// 			auto p = GetParent();
// 			if (p) {
// 				jumpFuel = navigation.JumpFuel(p->GetTargetSystem());
// 		}
// 		if (!jumpFuel) {
// 			jumpFuel = navigation.JumpFuel(targetSystem);
// 		return jumpFuel ? fuel / jumpFuel : 0.;
// 	}
// 	
// 	
// 	
// 	bool Ship::NeedsFuel(bool followParent) const
// 	{
// 		float jumpFuel = 0.;
// 		if (!targetSystem && followParent) {
// 
// 			// If this ship has no destination, the parent's substitutes for it,
// 			// but only if the location is reachable.
// 			auto p = GetParent();
// 			if (p) {
// 				jumpFuel = navigation.JumpFuel(p->GetTargetSystem());
// 		}
// 		if (!jumpFuel) {
// 			jumpFuel = navigation.JumpFuel(targetSystem);
// 		return (fuel < jumpFuel) && (attributes.Get("fuel capacity") >= jumpFuel);
// 	}
// 	
// 	
// 	
// 	float Ship::JumpFuelMissing() const
// 	{
// 		// Used for smart refueling: transfer only as much as really needed
// 		// includes checking if fuel cap is high enough at all
// 		float jumpFuel = navigation.JumpFuel(targetSystem);
// 		if (!jumpFuel || fuel > jumpFuel || jumpFuel > attributes.Get("fuel capacity")) {
// 			return 0.;
// 	
// 		return jumpFuel - fuel;
// 	}
// 	
// 	
// 	
// 	// Get the heat level at idle.
// 	float Ship::IdleHeat() const
// 	{
// 		// This ship's cooling ability:
// 		float coolingEfficiency = CoolingEfficiency();
// 		float cooling = coolingEfficiency * attributes.Get("cooling");
// 		float activeCooling = coolingEfficiency * attributes.Get("active cooling");
// 	
// 		// Idle heat is the heat level where:
// 		// heat = heat * diss + heatGen - cool - activeCool * heat / (100 * mass)
// 		// heat = heat * (diss - activeCool / (100 * mass)) + (heatGen - cool)
// 		// heat * (1 - diss + activeCool / (100 * mass)) = (heatGen - cool)
// 		float production = max(0., attributes.Get("heat generation") - cooling);
// 		float dissipation = HeatDissipation() + activeCooling / MaximumHeat();
// 		if (!dissipation) return production ? numeric_limits<float>::max() : 0;
// 		return production / dissipation;
// 	}
// 	
// 	
// 	
// 	// Get the heat dissipation, in heat units per heat unit per frame.
// 	float Ship::HeatDissipation() const
// 	{
// 		return .001 * attributes.Get("heat dissipation");
// 	}
// 	
// 	
// 	
// 	// Get the maximum heat level, in heat units (not temperature).
// 	float Ship::MaximumHeat() const
// 	{
// 		return MAXIMUM_TEMPERATURE * (cargo.Used() + attributes.Mass() + attributes.Get("heat capacity"));
// 	}
// 	
// 	
// 	
// 	// Calculate the multiplier for cooling efficiency.
// 	float Ship::CoolingEfficiency() const
// 	{
// 		// This is an S-curve where the efficiency is 100% if you have no outfits
// 		// that create "cooling inefficiency", and as that value increases the
// 		// efficiency stays high for a while, then drops off, then approaches 0.
// 		float x = attributes.Get("cooling inefficiency");
// 		return 2. + 2. / (1. + exp(x / -2.)) - 4. / (1. + exp(x / -4.));
// 	}
// 	
// 	
// 	
// 	int Ship::Crew() const
// 	{
// 		return crew;
// 	}
// 	
// 	
// 	
// 	// Calculate drag, accounting for drag reduction.
// 	float Ship::Drag() const
// 	{
// 		return attributes.Get("drag") / (1. + attributes.Get("drag reduction"));
// 	}
// 	
// 	
// 	
// 	int Ship::RequiredCrew() const
// 	{
// 		if (attributes.Get("automaton")) {
// 			return 0;
// 	
// 		// Drones do not need crew, but all other ships need at least one.
// 		return max<int>(1, attributes.Get("required crew"));
// 	}
// 	
// 	
// 	
// 	int Ship::CrewValue() const
// 	{
// 		return max(Crew(), RequiredCrew()) + attributes.Get("crew equivalent");
// 	}
// 	
// 	
// 	
// 	void Ship::AddCrew(int count)
// 	{
// 		crew = min<int>(crew + count, attributes.Get("bunks"));
// 	}
// 	
// 	
// 	
// 	// Check if this is a ship that can be used as a flagship.
// 	bool Ship::CanBeFlagship() const
// 	{
// 		return RequiredCrew() && Crew() && !IsDisabled();
// 	}
// 	
// 	
// 	
// 	float Ship::Mass() const
// 	{
// 		return carriedMass + cargo.Used() + attributes.Mass();
// 	}
// 	
// 	
// 	
// 	// Account for inertia reduction, which affects movement but has no effect on the ship's heat capacity.
// 	float Ship::InertialMass() const
// 	{
// 		return Mass() / (1. + attributes.Get("inertia reduction"));
// 	}
// 	
// 	
// 	
// 	float Ship::TurnRate() const
// 	{
// 		return attributes.Get("turn") / InertialMass();
// 	}
// 	
// 	
// 	
// 	float Ship::Acceleration() const
// 	{
// 		float thrust = attributes.Get("thrust");
// 		return (thrust ? thrust : attributes.Get("afterburner thrust")) / InertialMass();
// 	}
// 	
// 	
// 	
// 	float Ship::MaxVelocity() const
// 	{
// 		// v * drag / mass == thrust / mass
// 		// v * drag == thrust
// 		// v = thrust / drag
// 		float thrust = attributes.Get("thrust");
// 		return (thrust ? thrust : attributes.Get("afterburner thrust")) / Drag();
// 	}
// 	
// 	
// 	
// 	float Ship::ReverseAcceleration() const
// 	{
// 		return attributes.Get("reverse thrust");
// 	}
// 	
// 	
// 	
// 	float Ship::MaxReverseVelocity() const
// 	{
// 		return attributes.Get("reverse thrust") / Drag();
// 	}
// 	
// 	
// 	
// 	// This ship just got hit by a weapon. Take damage according to the
// 	// DamageDealt from that weapon. The return value is a ShipEvent type,
// 	// which may be a combination of PROVOKED, DISABLED, and DESTROYED.
// 	// Create any target effects as sparks.
// 	int Ship::TakeDamage(vector<Visual> &visuals, const DamageDealt &damage, const Government *sourceGovernment)
// 	{
// 		bool wasDisabled = IsDisabled();
// 		bool wasDestroyed = IsDestroyed();
// 	
// 		shields -= damage.Shield();
// 		if (damage.Shield() && !isDisabled) {
// 
// 			int disabledDelay = attributes.Get("depleted shield delay");
// 			shieldDelay = max<int>(shieldDelay, (shields <= 0. && disabledDelay)
// 				? disabledDelay : attributes.Get("shield delay"));
// 		}
// 		hull -= damage.Hull();
// 		if (damage.Hull() && !isDisabled) {
// 			hullDelay = max(hullDelay, static_cast<int>(attributes.Get("repair delay")));
// 	
// 		energy -= damage.Energy();
// 		heat += damage.Heat();
// 		fuel -= damage.Fuel();
// 	
// 		discharge += damage.Discharge();
// 		corrosion += damage.Corrosion();
// 		ionization += damage.Ion();
// 		scrambling += damage.Scrambling();
// 		burning += damage.Burn();
// 		leakage += damage.Leak();
// 	
// 		disruption += damage.Disruption();
// 		slowness += damage.Slowing();
// 	
// 		if (damage.HitForce()) {
// 			ApplyForce(damage.HitForce(), damage.GetWeapon().IsGravitational());
// 	
// 		// Prevent various stats from reaching unallowable values.
// 		hull = min(hull, attributes.Get("hull"));
// 		shields = min(shields, attributes.Get("shields"));
// 		// Weapons are allowed to overcharge a ship's energy or fuel, but code in Ship::DoGeneration()
// 		// will clamp it to a maximum value at the beginning of the next frame.
// 		energy = max(0., energy);
// 		fuel = max(0., fuel);
// 		heat = max(0., heat);
// 	
// 		// Recalculate the disabled ship check.
// 		isDisabled = true;
// 		isDisabled = IsDisabled();
// 	
// 		// Report what happened to this ship from this weapon.
// 		int type = 0;
// 		if (!wasDisabled && isDisabled) {
// 
// 			type |= ShipEvent::DISABLE;
// 			hullDelay = max(hullDelay, static_cast<int>(attributes.Get("disabled repair delay")));
// 		}
// 		if (!wasDestroyed && IsDestroyed()) {
// 			type |= ShipEvent::DESTROY;
// 	
// 		// Inflicted heat damage may also disable a ship, but does not trigger a "DISABLE" event.
// 		if (heat > MaximumHeat()) {
// 
// 			isOverheated = true;
// 			isDisabled = true;
// 		} else if (heat < .9 * MaximumHeat()) {
// 			isOverheated = false;
// 	
// 		// If this ship did not consider itself an enemy of the ship that hit it,
// 		// it is now "provoked" against that government.
// 		if (sourceGovernment && !sourceGovernment->IsEnemy(government) {
// 				&& !personality.IsPacifist() && (!personality.IsForbearing()
// 					|| ((damage.Shield() || damage.Discharge()) && Shields() < .9)
// 					|| ((damage.Hull() || damage.Corrosion()) && Hull() < .9)
// 					|| ((damage.Heat() || damage.Burn()) && isOverheated)
// 					|| ((damage.Energy() || damage.Ion()) && Energy() < 0.5)
// 					|| ((damage.Fuel() || damage.Leak()) && fuel < navigation.JumpFuel() * 2.)
// 					|| (damage.Scrambling() && CalculateJamChance(Energy(), scrambling) > 0.1)
// 					|| (damage.Slowing() && slowness > 10.)
// 					|| (damage.Disruption() && disruption > 100.)))
// 			type |= ShipEvent::PROVOKE;
// 	
// 		// Create target effect visuals, if there are any.
// 		for (const auto &effect : damage.GetWeapon().TargetEffects()) {
// 			CreateSparks(visuals, effect.first, effect.second * damage.Scaling());
// 	
// 		return type;
// 	}
// 	
// 	
// 	
// 	// Apply a force to this ship, accelerating it. This might be from a weapon
// 	// impact, or from firing a weapon, for example.
// 	void Ship::ApplyForce(const Point &force, bool gravitational)
// 	{
// 		if (gravitational) {
// 
// 			// Treat all ships as if they have a mass of 400. This prevents
// 			// gravitational hit force values from needing to be extremely
// 			// small in order to have a reasonable effect.
// 			acceleration += force / 400.;
// 			return;
// 		}
// 	
// 		float currentMass = InertialMass();
// 		if (!currentMass) {
// 			return;
// 	
// 		acceleration += force / currentMass;
// 	}
// 	
// 	
// 	
// 	bool Ship::$hasBays() const
// 	{
// 		return !bays.empty();
// 	}
// 	
// 	
// 	
// 	// Check how many bays are not occupied at present. This does not check whether
// 	// one of your escorts plans to use that bay.
// 	int Ship::BaysFree(const string &category) const
// 	{
// 		int count = 0;
// 		for (const Bay &bay : bays) {
// 			count += (bay.category == category) && !bay.ship;
// 		return count;
// 	}
// 	
// 	
// 	
// 	// Check how many bays this ship has of a given category.
// 	int Ship::BaysTotal(const string &category) const
// 	{
// 		int count = 0;
// 		for (const Bay &bay : bays) {
// 			count += (bay.category == category);
// 		return count;
// 	}
// 	
// 	
// 	
// 	// Check if this ship has a bay free for the given ship, and the bay is
// 	// not reserved for one of its existing escorts.
// 	bool Ship::CanCarry(const Ship &ship) const
// 	{
// 		if (!$hasBays() || !ship.CanBeCarried() || (IsYours() && !ship.IsYours())) {
// 			return false;
// 		// Check only for the category that we are interested in.
// 		const string &category = ship.attributes.Category();
// 	
// 		int free = BaysTotal(category);
// 		if (!free) {
// 			return false;
// 	
// 		for (const auto &it : escorts) {
// 
// 			auto escort = it.lock();
// 			if (!escort) {
// 				continue;
// 			if (escort == ship.shared_from_this()) {
// 				break;
// 			if (escort->attributes.Category() == category && !escort->IsDestroyed() &&
// 					(!IsYours() || (IsYours() && escort->IsYours())))
// 				--free;
// 			if (!free) {
// 				break;
// 		}
// 		return (free > 0);
// 	}
// 	
// 	
// 	
// 	bool Ship::CanBeCarried() const
// 	{
// 		return canBeCarried;
// 	}
// 	
// 	
// 	
// 	bool Ship::Carry(const shared_ptr<Ship> &ship)
// 	{
// 		if (!ship || !ship->CanBeCarried() || ship->IsDisabled()) {
// 			return false;
// 	
// 		// Check only for the category that we are interested in.
// 		const string &category = ship->attributes.Category();
// 	
// 		// NPC ships should always transfer cargo. Player ships should only
// 		// transfer cargo if they set the AI preference.
// 		const bool shouldTransferCargo = !IsYours() || Preferences::Has("Fighters transfer cargo");
// 	
// 		for (Bay &bay : bays) {
// 			if ((bay.category == category) && !bay.ship) {
// 
// 				bay.ship = ship;
// 				ship->SetSystem(nullptr);
// 				ship->SetPlanet(nullptr);
// 				ship->SetTargetSystem(nullptr);
// 				ship->SetTargetStellar(nullptr);
// 				ship->SetParent(shared_from_this());
// 				ship->isThrusting = false;
// 				ship->isReversing = false;
// 				ship->isSteering = false;
// 				ship->commands.Clear();
// 	
// 				// If this fighter collected anything in space, try to store it.
// 				if (shouldTransferCargo && cargo.Free() && !ship->Cargo().IsEmpty()) {
// 					ship->Cargo().TransferAll(cargo);
// 	
// 				// Return unused fuel and ammunition to the carrier, so they may
// 				// be used by the carrier or other fighters.
// 				ship->TransferFuel(ship->fuel, this);
// 	
// 				// Determine the ammunition the fighter can supply.
// 				auto restockable = ship->GetArmament().RestockableAmmo();
// 				auto toRestock = map<const Outfit *, int>{};
// 				for (auto &&ammo : restockable) {
// 
// 					int count = ship->OutfitCount(ammo);
// 					if (count > 0) {
// 						toRestock.emplace(ammo, count);
// 				}
// 				TransferAmmo(toRestock, *ship, *this);
// 	
// 				// Update the cached mass of the mothership.
// 				carriedMass += ship->Mass();
// 				return true;
// 			}
// 		return false;
// 	}
// 	
// 	
// 	
// 	void Ship::UnloadBays()
// 	{
// 		for (Bay &bay : bays) {
// 			if (bay.ship) {
// 
// 				carriedMass -= bay.ship->Mass();
// 				bay.ship->SetSystem(currentSystem);
// 				bay.ship->SetPlanet(landingPlanet);
// 				bay.ship->UnmarkForRemoval();
// 				bay.ship.reset();
// 			}
// 	}
// 	
// 	
// 	
// 	const vector<Ship::Bay> &Ship::Bays() const
// 	{
// 		return bays;
// 	}
// 	
// 	
// 	
// 	// Adjust the positions and velocities of any visible carried fighters or
// 	// drones. If any are visible, return true.
// 	bool Ship::PositionFighters() const
// 	{
// 		bool hasVisible = false;
// 		for (const Bay &bay : bays) {
// 			if (bay.ship && bay.side) {
// 
// 				hasVisible = true;
// 				bay.ship->position = angle.Rotate(bay.point) * Zoom() + position;
// 				bay.ship->velocity = velocity;
// 				bay.ship->angle = angle + bay.facing;
// 				bay.ship->zoom = zoom;
// 			}
// 		return hasVisible;
// 	}
// 	
// 	
// 	
// 	CargoHold &Ship::Cargo()
// 	{
// 		return cargo;
// 	}
// 	
// 	
// 	
// 	const CargoHold &Ship::Cargo() const
// 	{
// 		return cargo;
// 	}
// 	
// 	
// 	
// 	// Display box effects from jettisoning this much cargo.
// 	void Ship::Jettison(const string &commodity, int tons, bool wasAppeasing)
// 	{
// 		cargo.Remove(commodity, tons);
// 		// Removing cargo will have changed the ship's mass, so the
// 		// jump navigation info may be out of date. Only do this for
// 		// player ships as to display correct information on the map.
// 		// Non-player ships will recalibrate before they jump.
// 		if (isYours) {
// 			navigation.Recalibrate(*this);
// 	
// 		// Jettisoned cargo must carry some of the ship's heat with it. Otherwise
// 		// jettisoning cargo would increase the ship's temperature.
// 		heat -= tons * MAXIMUM_TEMPERATURE * Heat();
// 	
// 		const Government *notForGov = wasAppeasing ? GetGovernment() : nullptr;
// 	
// 		for ( ; tons > 0; tons -= Flotsam::TONS_PER_BOX) {
// 			jettisoned.emplace_back(new Flotsam(commodity, (Flotsam::TONS_PER_BOX < tons)
// 				? Flotsam::TONS_PER_BOX : tons, notForGov));
// 	}
// 	
// 	
// 	
// 	void Ship::Jettison(const Outfit *outfit, int count, bool wasAppeasing)
// 	{
// 		if (count < 0) {
// 			return;
// 	
// 		cargo.Remove(outfit, count);
// 		// Removing cargo will have changed the ship's mass, so the
// 		// jump navigation info may be out of date. Only do this for
// 		// player ships as to display correct information on the map.
// 		// Non-player ships will recalibrate before they jump.
// 		if (isYours) {
// 			navigation.Recalibrate(*this);
// 	
// 		// Jettisoned cargo must carry some of the ship's heat with it. Otherwise
// 		// jettisoning cargo would increase the ship's temperature.
// 		float mass = outfit->Mass();
// 		heat -= count * mass * MAXIMUM_TEMPERATURE * Heat();
// 	
// 		const Government *notForGov = wasAppeasing ? GetGovernment() : nullptr;
// 	
// 		const int perBox = (mass <= 0.) ? count : (mass > Flotsam::TONS_PER_BOX)
// 			? 1 : static_cast<int>(Flotsam::TONS_PER_BOX / mass);
// 		while(count > 0)
// 		{
// 			jettisoned.emplace_back(new Flotsam(outfit, (perBox < count)
// 				? perBox : count, notForGov));
// 			count -= perBox;
// 		}
// 	}
// 	
// 	
// 	
// 	const Outfit &Ship::Attributes() const
// 	{
// 		return attributes;
// 	}
// 	
// 	
// 	
// 	const Outfit &Ship::BaseAttributes() const
// 	{
// 		return baseAttributes;
// 	}
// 	
// 	
// 	
// 	// Get outfit information.
// 	const map<const Outfit *, int> &Ship::Outfits() const
// 	{
// 		return outfits;
// 	}
// 	
// 	
// 	
// 	int Ship::OutfitCount(const Outfit *outfit) const
// 	{
// 		auto it = outfits.find(outfit);
// 		return (it == outfits.end()) ? 0 : it->second;
// 	}
// 	
// 	
// 	
// 	// Add or remove outfits. (To remove, pass a negative number.)
// 	void Ship::AddOutfit(const Outfit *outfit, int count)
// 	{
// 		if (outfit && count) {
// 
// 			auto it = outfits.find(outfit);
// 			int before = outfits.count(outfit);
// 			if (it == outfits.end()) {
// 				outfits[outfit] = count;
// 			} else
// 			{
// 				it->second += count;
// 				if (!it->second) {
// 					outfits.erase(it);
// 			}
// 			int after = outfits.count(outfit);
// 			attributes.Add(*outfit, count);
// 			if (outfit->IsWeapon()) {
// 
// 				armament.Add(outfit, count);
// 				// Only the player's ships make use of attraction and deterrence.
// 				if (isYours) {
// 					deterrence = CalculateDeterrence();
// 			}
// 	
// 			if (outfit->Get("cargo space")) {
// 
// 				cargo.SetSize(attributes.Get("cargo space"));
// 				// Only the player's ships make use of attraction and deterrence.
// 				if (isYours) {
// 					attraction = CalculateAttraction();
// 			}
// 			if (outfit->Get("hull")) {
// 				hull += outfit->Get("hull") * count;
// 			// If the added or removed outfit is a hyperdrive or jump drive, recalculate this
// 			// ship's jump navigation. Hyperdrives and jump drives of the same type don't stack,
// 			// so only do this if the outfit is either completely new or has been completely removed.
// 			if ((outfit->Get("hyperdrive") || outfit->Get("jump drive")) && (!before || !after)) {
// 				navigation.Calibrate(*this);
// 			// Navigation may still need to be recalibrated depending on the drives a ship has.
// 			// Only do this for player ships as to display correct information on the map.
// 			// Non-player ships will recalibrate before they jump.
// 			} else if (isYours) {
// 				navigation.Recalibrate(*this);
// 		}
// 	}
// 	
// 	
// 	
// 	// Get the list of weapons.
// 	Armament &Ship::GetArmament()
// 	{
// 		return armament;
// 	}
// 	
// 	
// 	
// 	const vector<Hardpoint> &Ship::Weapons() const
// 	{
// 		return armament.Get();
// 	}
// 	
// 	
// 	
// 	// Check if we are able to fire the given weapon (i.e. there is enough
// 	// energy, ammo, and fuel to fire it).
// 	bool Ship::CanFire(const Weapon *weapon) const
// 	{
// 		if (!weapon || !weapon->IsWeapon()) {
// 			return false;
// 	
// 		if (weapon->Ammo()) {
// 
// 			auto it = outfits.find(weapon->Ammo());
// 			if (it == outfits.end() || it->second < weapon->AmmoUsage()) {
// 				return false;
// 		}
// 	
// 		if (energy < weapon->FiringEnergy() + weapon->RelativeFiringEnergy() * attributes.Get("energy capacity")) {
// 			return false;
// 		if (fuel < weapon->FiringFuel() + weapon->RelativeFiringFuel() * attributes.Get("fuel capacity")) {
// 			return false;
// 		// We do check hull, but we don't check shields. Ships can survive with all shields depleted.
// 		// Ships should not disable themselves, so we check if we stay above minimumHull.
// 		if (hull - MinimumHull() < weapon->FiringHull() + weapon->RelativeFiringHull() * attributes.Get("hull")) {
// 			return false;
// 	
// 		// If a weapon requires heat to fire, (rather than generating heat), we must
// 		// have enough heat to spare.
// 		if (heat < -(weapon->FiringHeat() + (!weapon->RelativeFiringHeat() {
// 				? 0. : weapon->RelativeFiringHeat() * MaximumHeat())))
// 			return false;
// 		// Repeat this for various effects which shouldn't drop below 0.
// 		if (ionization < -weapon->FiringIon()) {
// 			return false;
// 		if (disruption < -weapon->FiringDisruption()) {
// 			return false;
// 		if (slowness < -weapon->FiringSlowing()) {
// 			return false;
// 	
// 		return true;
// 	}
// 	
// 	
// 	
// 	// Fire the given weapon (i.e. deduct whatever energy, ammo, hull, shields
// 	// or fuel it uses and add whatever heat it generates. Assume that CanFire()
// 	// is true.
// 	void Ship::ExpendAmmo(const Weapon &weapon)
// 	{
// 		// Compute this ship's initial capacities, in case the consumption of the ammunition outfit(s)
// 		// modifies them, so that relative costs are calculated based on the pre-firing state of the ship.
// 		const float relativeEnergyChange = weapon.RelativeFiringEnergy() * attributes.Get("energy capacity");
// 		const float relativeFuelChange = weapon.RelativeFiringFuel() * attributes.Get("fuel capacity");
// 		const float relativeHeatChange = !weapon.RelativeFiringHeat() ? 0. : weapon.RelativeFiringHeat() * MaximumHeat();
// 		const float relativeHullChange = weapon.RelativeFiringHull() * attributes.Get("hull");
// 		const float relativeShieldChange = weapon.RelativeFiringShields() * attributes.Get("shields");
// 	
// 		if (const Outfit *ammo = weapon.Ammo()) {
// 
// 			// Some amount of the ammunition mass to be removed from the ship carries thermal energy.
// 			// A realistic fraction applicable to all cases cannot be computed, so assume 50%.
// 			heat -= weapon.AmmoUsage() * .5 * ammo->Mass() * MAXIMUM_TEMPERATURE * Heat();
// 			AddOutfit(ammo, -weapon.AmmoUsage());
// 			// Only the player's ships make use of attraction and deterrence.
// 			if (isYours && !OutfitCount(ammo) && ammo->AmmoUsage()) {
// 
// 				// Recalculate the AI to account for the loss of this weapon.
// 				aiCache.Calibrate(*this);
// 				deterrence = CalculateDeterrence();
// 			}
// 		}
// 	
// 		energy -= weapon.FiringEnergy() + relativeEnergyChange;
// 		fuel -= weapon.FiringFuel() + relativeFuelChange;
// 		heat += weapon.FiringHeat() + relativeHeatChange;
// 		shields -= weapon.FiringShields() + relativeShieldChange;
// 	
// 		// Since weapons fire from within the shields, hull and "status" damages are dealt in full.
// 		hull -= weapon.FiringHull() + relativeHullChange;
// 		ionization += weapon.FiringIon();
// 		scrambling += weapon.FiringScramble();
// 		disruption += weapon.FiringDisruption();
// 		slowness += weapon.FiringSlowing();
// 		discharge += weapon.FiringDischarge();
// 		corrosion += weapon.FiringCorrosion();
// 		leakage += weapon.FiringLeak();
// 		burning += weapon.FiringBurn();
// 	}
// 	
// 	
// 	
// 	// Each ship can have a target system (to travel to), a target planet (to
// 	// land on) and a target ship (to move to, and attack if hostile).
// 	shared_ptr<Ship> Ship::GetTargetShip() const
// 	{
// 		return targetShip.lock();
// 	}
// 	
// 	
// 	
// 	shared_ptr<Ship> Ship::GetShipToAssist() const
// 	{
// 		return shipToAssist.lock();
// 	}
// 	
// 	
// 	
// 	const StellarObject *Ship::GetTargetStellar() const
// 	{
// 		return targetPlanet;
// 	}
// 	
// 	
// 	
// 	const System *Ship::GetTargetSystem() const
// 	{
// 		return (targetSystem == currentSystem) ? nullptr : targetSystem;
// 	}
// 	
// 	
// 	
// 	// Mining target.
// 	shared_ptr<Minable> Ship::GetTargetAsteroid() const
// 	{
// 		return targetAsteroid.lock();
// 	}
// 	
// 	
// 	
// 	shared_ptr<Flotsam> Ship::GetTargetFlotsam() const
// 	{
// 		return targetFlotsam.lock();
// 	}
// 	
// 	
// 	
// 	void Ship::SetFleeing(bool fleeing)
// 	{
// 		isFleeing = fleeing;
// 	}
// 	
// 	
// 	
// 	// Set this ship's targets.
// 	void Ship::SetTargetShip(const shared_ptr<Ship> &ship)
// 	{
// 		if (ship != GetTargetShip()) {
// 
// 			targetShip = ship;
// 			// When you change targets, clear your scanning records.
// 			cargoScan = 0.;
// 			outfitScan = 0.;
// 		}
// 		targetAsteroid.reset();
// 	}
// 	
// 	
// 	
// 	void Ship::SetShipToAssist(const shared_ptr<Ship> &ship)
// 	{
// 		shipToAssist = ship;
// 	}
// 	
// 	
// 	
// 	void Ship::SetTargetStellar(const StellarObject *object)
// 	{
// 		targetPlanet = object;
// 	}
// 	
// 	
// 	
// 	void Ship::SetTargetSystem(const System *system)
// 	{
// 		targetSystem = system;
// 	}
// 	
// 	
// 	
// 	// Mining target.
// 	void Ship::SetTargetAsteroid(const shared_ptr<Minable> &asteroid)
// 	{
// 		targetAsteroid = asteroid;
// 		targetShip.reset();
// 	}
// 	
// 	
// 	
// 	void Ship::SetTargetFlotsam(const shared_ptr<Flotsam> &flotsam)
// 	{
// 		targetFlotsam = flotsam;
// 	}
// 	
// 	
// 	
// 	void Ship::SetParent(const shared_ptr<Ship> &ship)
// 	{
// 		shared_ptr<Ship> oldParent = parent.lock();
// 		if (oldParent) {
// 			oldParent->RemoveEscort(*this);
// 	
// 		parent = ship;
// 		if (ship) {
// 			ship->AddEscort(*this);
// 	}
// 	
// 	
// 	
// 	bool Ship::CanPickUp(const Flotsam &flotsam) const
// 	{
// 		if (this == flotsam.Source()) {
// 			return false;
// 		if (government == flotsam.SourceGovernment() && (!personality.Harvests() || personality.IsAppeasing())) {
// 			return false;
// 		return cargo.Free() >= flotsam.UnitSize();
// 	}
// 	
// 	
// 	
// 	shared_ptr<Ship> Ship::GetParent() const
// 	{
// 		return parent.lock();
// 	}
// 	
// 	
// 	
// 	const vector<weak_ptr<Ship>> &Ship::GetEscorts() const
// 	{
// 		return escorts;
// 	}
// 	
// 	
// 	
// 	int Ship::GetLingerSteps() const
// 	{
// 		return lingerSteps;
// 	}
// 	
// 	
// 	
// 	void Ship::Linger()
// 	{
// 		++lingerSteps;
// 	}
// 	
// 	
// 	
// 	// Check if this ship has been in a different system from the player for so
// 	// long that it should be "forgotten." Also eliminate ships that have no
// 	// system set because they just entered a fighter bay. Clear the hyperspace
// 	// targets of ships that can't enter hyperspace.
// 	bool Ship::StepFlags()
// 	{
// 		forget += !isInSystem;
// 		isThrusting = false;
// 		isReversing = false;
// 		isSteering = false;
// 		steeringDirection = 0.;
// 		if ((!isSpecial && forget >= 1000) || !currentSystem) {
// 
// 			MarkForRemoval();
// 			return true;
// 		}
// 		isInSystem = false;
// 		if (!fuel || !(navigation.HasHyperdrive() || navigation.HasJumpDrive())) {
// 			hyperspaceSystem = nullptr;
// 		return false;
// 	}
// 	
// 	
// 	
// 	// Step ship destruction logic. Returns 1 if the ship has been destroyed, -1 if it is being
// 	// destroyed, or 0 otherwise.
// 	int Ship::StepDestroyed(vector<Visual> &visuals, list<shared_ptr<Flotsam>> &flotsam)
// 	{
// 		if (!IsDestroyed()) {
// 			return 0;
// 	
// 		// Make sure the shields are zero, as well as the hull.
// 		shields = 0.;
// 	
// 		// Once we've created enough little explosions, die.
// 		if (explosionCount == explosionTotal || forget) {
// 
// 			if (IsYours() && Preferences::Has("Extra fleet status messages")) {
// 				Messages::Add("Your ship \"" + Name() + "\" has been destroyed.", Messages::Importance::Highest);
// 	
// 			if (!forget) {
// 
// 				const Effect *effect = GameData::Effects().Get("smoke");
// 				float size = Width() + Height();
// 				float scale = .03 * size + .5;
// 				float radius = .2 * size;
// 				int debrisCount = attributes.Mass() * .07;
// 	
// 				// Estimate how many new visuals will be added during destruction.
// 				visuals.reserve(visuals.size() + debrisCount + explosionTotal + finalExplosions.size());
// 	
// 				for (int i = 0; i < debrisCount; ++i) {
// 
// 					Angle angle = Angle::Random();
// 					Point effectVelocity = velocity + angle.Unit() * (scale * Random::Real());
// 					Point effectPosition = position + radius * angle.Unit();
// 	
// 					visuals.emplace_back(*effect, std::move(effectPosition), std::move(effectVelocity), std::move(angle));
// 				}
// 	
// 				for (unsigned i = 0; i < explosionTotal / 2; ++i) {
// 					CreateExplosion(visuals, true);
// 				for (const auto &it : finalExplosions) {
// 					visuals.emplace_back(*it.first, position, velocity, angle);
// 				// For everything in this ship's cargo hold there is a 25% chance
// 				// that it will survive as flotsam.
// 				for (const auto &it : cargo.Commodities()) {
// 					Jettison(it.first, Random::Binomial(it.second, .25));
// 				for (const auto &it : cargo.Outfits()) {
// 					Jettison(it.first, Random::Binomial(it.second, .25));
// 				// Ammunition has a default 5% chance to survive as flotsam.
// 				for (const auto &it : outfits) {
// 
// 					float flotsamChance = it.first->Get("flotsam chance");
// 					if (flotsamChance > 0.) {
// 						Jettison(it.first, Random::Binomial(it.second, flotsamChance));
// 					// 0 valued 'flotsamChance' means default, which is 5% for ammunition.
// 					// At this point, negative values are the only non-zero values possible.
// 					// Negative values override the default chance for ammunition
// 					// so the outfit cannot be dropped as flotsam.
// 					} else if (it.first->Category() == "Ammunition" && !flotsamChance) {
// 						Jettison(it.first, Random::Binomial(it.second, .05));
// 				}
// 				for (shared_ptr<Flotsam> &it : jettisoned) {
// 					it->Place(*this);
// 				flotsam.splice(flotsam.end(), jettisoned);
// 	
// 				// Any ships that failed to launch from this ship are destroyed.
// 				for (Bay &bay : bays) {
// 					if (bay.ship) {
// 						bay.ship->Destroy();
// 			}
// 			energy = 0.;
// 			heat = 0.;
// 			ionization = 0.;
// 			scrambling = 0.;
// 			fuel = 0.;
// 			velocity = Point();
// 			MarkForRemoval();
// 			return 1;
// 		}
// 	
// 		// If the ship is dead, it first creates explosions at an increasing
// 		// rate, then disappears in one big explosion.
// 		++explosionRate;
// 		if (Random::Int(1024) < explosionRate) {
// 			CreateExplosion(visuals);
// 	
// 		// Handle hull "leaks."
// 		for (const Leak &leak : leaks) {
// 			if (GetMask().IsLoaded() && leak.openPeriod > 0 && !Random::Int(leak.openPeriod)) {
// 
// 				activeLeaks.push_back(leak);
// 				const auto &outlines = GetMask().Outlines();
// 				const vector<Point> &outline = outlines[Random::Int(outlines.size())];
// 				int i = Random::Int(outline.size() - 1);
// 	
// 				// Position the leak along the outline of the ship, facing "outward."
// 				activeLeaks.back().location = (outline[i] + outline[i + 1]) * .5;
// 				activeLeaks.back().angle = Angle(outline[i] - outline[i + 1]) + Angle(90.);
// 			}
// 		for (Leak &leak : activeLeaks) {
// 			if (leak.effect) {
// 
// 				// Leaks always "flicker" every other frame.
// 				if (Random::Int(2)) {
// 					visuals.emplace_back(*leak.effect,
// 						angle.Rotate(leak.location) + position,
// 						velocity,
// 						leak.angle + angle);
// 	
// 				if (leak.closePeriod > 0 && !Random::Int(leak.closePeriod)) {
// 					leak.effect = nullptr;
// 			}
// 		return -1;
// 	}
// 	
// 	
// 	
// 	// Generate energy, heat, etc. (This is called by Move().)
// 	void Ship::DoGeneration()
// 	{
// 		// First, allow any carried ships to do their own generation.
// 		for (const Bay &bay : bays) {
// 			if (bay.ship) {
// 				bay.ship->DoGeneration();
// 	
// 		// Shield and hull recharge. This uses whatever energy is left over from the
// 		// previous frame, so that it will not steal energy from movement, etc.
// 		if (!isDisabled) {
// 
// 			// Priority of repairs:
// 			// 1. Ship's own hull
// 			// 2. Ship's own shields
// 			// 3. Hull of carried fighters
// 			// 4. Shields of carried fighters
// 			// 5. Transfer of excess energy and fuel to carried fighters.
// 	
// 			const float hullAvailable = attributes.Get("hull repair rate")
// 				* (1. + attributes.Get("hull repair multiplier"));
// 			const float hullEnergy = (attributes.Get("hull energy")
// 				* (1. + attributes.Get("hull energy multiplier"))) / hullAvailable;
// 			const float hullFuel = (attributes.Get("hull fuel")
// 				* (1. + attributes.Get("hull fuel multiplier"))) / hullAvailable;
// 			const float hullHeat = (attributes.Get("hull heat")
// 				* (1. + attributes.Get("hull heat multiplier"))) / hullAvailable;
// 			float hullRemaining = hullAvailable;
// 			if (!hullDelay) {
// 				DoRepair(hull, hullRemaining, attributes.Get("hull"), energy, hullEnergy, fuel, hullFuel, heat, hullHeat);
// 	
// 			const float shieldsAvailable = attributes.Get("shield generation")
// 				* (1. + attributes.Get("shield generation multiplier"));
// 			const float shieldsEnergy = (attributes.Get("shield energy")
// 				* (1. + attributes.Get("shield energy multiplier"))) / shieldsAvailable;
// 			const float shieldsFuel = (attributes.Get("shield fuel")
// 				* (1. + attributes.Get("shield fuel multiplier"))) / shieldsAvailable;
// 			const float shieldsHeat = (attributes.Get("shield heat")
// 				* (1. + attributes.Get("shield heat multiplier"))) / shieldsAvailable;
// 			float shieldsRemaining = shieldsAvailable;
// 			if (!shieldDelay) {
// 				DoRepair(shields, shieldsRemaining, attributes.Get("shields"),
// 					energy, shieldsEnergy, fuel, shieldsFuel, heat, shieldsHeat);
// 	
// 			if (!bays.empty()) {
// 
// 				// If this ship is carrying fighters, determine their repair priority.
// 				vector<pair<float, Ship *>> carried;
// 				for (const Bay &bay : bays) {
// 					if (bay.ship) {
// 						carried.emplace_back(1. - bay.ship->Health(), bay.ship.get());
// 				sort(carried.begin(), carried.end(), (isYours && Preferences::Has(FIGHTER_REPAIR))
// 					// Players may use a parallel strategy, to launch fighters in waves.
// 					? [] (const pair<float, Ship *> &lhs, const pair<float, Ship *> &rhs)
// 						{ return lhs.first > rhs.first; }
// 					// The default strategy is to prioritize the healthiest ship first, in
// 					// order to get fighters back out into the battle as soon as possible.
// 					: [] (const pair<float, Ship *> &lhs, const pair<float, Ship *> &rhs)
// 						{ return lhs.first < rhs.first; }
// 				);
// 	
// 				// Apply shield and hull repair to carried fighters.
// 				for (const pair<float, Ship *> &it : carried) {
// 
// 					Ship &ship = *it.second;
// 					if (!hullDelay) {
// 						DoRepair(ship.hull, hullRemaining, ship.attributes.Get("hull"),
// 							energy, hullEnergy, heat, hullHeat, fuel, hullFuel);
// 					if (!shieldDelay) {
// 						DoRepair(ship.shields, shieldsRemaining, ship.attributes.Get("shields"),
// 							energy, shieldsEnergy, heat, shieldsHeat, fuel, shieldsFuel);
// 				}
// 	
// 				// Now that there is no more need to use energy for hull and shield
// 				// repair, if there is still excess energy, transfer it.
// 				float energyRemaining = energy - attributes.Get("energy capacity");
// 				float fuelRemaining = fuel - attributes.Get("fuel capacity");
// 				for (const pair<float, Ship *> &it : carried) {
// 
// 					Ship &ship = *it.second;
// 					if (energyRemaining > 0.) {
// 						DoRepair(ship.energy, energyRemaining, ship.attributes.Get("energy capacity"));
// 					if (fuelRemaining > 0.) {
// 						DoRepair(ship.fuel, fuelRemaining, ship.attributes.Get("fuel capacity"));
// 				}
// 	
// 				// Carried ships can recharge energy from their parent's batteries,
// 				// if they are preparing for deployment. Otherwise, they replenish the
// 				// parent's batteries.
// 				for (const pair<float, Ship *> &it : carried) {
// 
// 					Ship &ship = *it.second;
// 					if (ship.HasDeployOrder()) {
// 						DoRepair(ship.energy, energy, ship.attributes.Get("energy capacity"));
// 					} else
// 						DoRepair(energy, ship.energy, attributes.Get("energy capacity"));
// 				}
// 			}
// 			// Decrease the shield and hull delays by 1 now that shield generation
// 			// and hull repair have been skipped over.
// 			shieldDelay = max(0, shieldDelay - 1);
// 			hullDelay = max(0, hullDelay - 1);
// 		}
// 	
// 		// Handle ionization effects, etc.
// 		shields -= discharge;
// 		hull -= corrosion;
// 		energy -= ionization;
// 		fuel -= leakage;
// 		heat += burning;
// 		// TODO: Mothership gives status resistance to carried ships?
// 		if (ionization) {
// 
// 			float ionResistance = attributes.Get("ion resistance");
// 			float ionEnergy = attributes.Get("ion resistance energy") / ionResistance;
// 			float ionFuel = attributes.Get("ion resistance fuel") / ionResistance;
// 			float ionHeat = attributes.Get("ion resistance heat") / ionResistance;
// 			DoStatusEffect(isDisabled, ionization, ionResistance,
// 				energy, ionEnergy, fuel, ionFuel, heat, ionHeat);
// 		}
// 	
// 		if (scrambling) {
// 
// 			float scramblingResistance = attributes.Get("scramble resistance");
// 			float scramblingEnergy = attributes.Get("scramble resistance energy") / scramblingResistance;
// 			float scramblingFuel = attributes.Get("scramble resistance fuel") / scramblingResistance;
// 			float scramblingHeat = attributes.Get("scramble resistance heat") / scramblingResistance;
// 			DoStatusEffect(isDisabled, scrambling, scramblingResistance,
// 				energy, scramblingEnergy, fuel, scramblingFuel, heat, scramblingHeat);
// 		}
// 	
// 		if (disruption) {
// 
// 			float disruptionResistance = attributes.Get("disruption resistance");
// 			float disruptionEnergy = attributes.Get("disruption resistance energy") / disruptionResistance;
// 			float disruptionFuel = attributes.Get("disruption resistance fuel") / disruptionResistance;
// 			float disruptionHeat = attributes.Get("disruption resistance heat") / disruptionResistance;
// 			DoStatusEffect(isDisabled, disruption, disruptionResistance,
// 				energy, disruptionEnergy, fuel, disruptionFuel, heat, disruptionHeat);
// 		}
// 	
// 		if (slowness) {
// 
// 			float slowingResistance = attributes.Get("slowing resistance");
// 			float slowingEnergy = attributes.Get("slowing resistance energy") / slowingResistance;
// 			float slowingFuel = attributes.Get("slowing resistance fuel") / slowingResistance;
// 			float slowingHeat = attributes.Get("slowing resistance heat") / slowingResistance;
// 			DoStatusEffect(isDisabled, slowness, slowingResistance,
// 				energy, slowingEnergy, fuel, slowingFuel, heat, slowingHeat);
// 		}
// 	
// 		if (discharge) {
// 
// 			float dischargeResistance = attributes.Get("discharge resistance");
// 			float dischargeEnergy = attributes.Get("discharge resistance energy") / dischargeResistance;
// 			float dischargeFuel = attributes.Get("discharge resistance fuel") / dischargeResistance;
// 			float dischargeHeat = attributes.Get("discharge resistance heat") / dischargeResistance;
// 			DoStatusEffect(isDisabled, discharge, dischargeResistance,
// 				energy, dischargeEnergy, fuel, dischargeFuel, heat, dischargeHeat);
// 		}
// 	
// 		if (corrosion) {
// 
// 			float corrosionResistance = attributes.Get("corrosion resistance");
// 			float corrosionEnergy = attributes.Get("corrosion resistance energy") / corrosionResistance;
// 			float corrosionFuel = attributes.Get("corrosion resistance fuel") / corrosionResistance;
// 			float corrosionHeat = attributes.Get("corrosion resistance heat") / corrosionResistance;
// 			DoStatusEffect(isDisabled, corrosion, corrosionResistance,
// 				energy, corrosionEnergy, fuel, corrosionFuel, heat, corrosionHeat);
// 		}
// 	
// 		if (leakage) {
// 
// 			float leakResistance = attributes.Get("leak resistance");
// 			float leakEnergy = attributes.Get("leak resistance energy") / leakResistance;
// 			float leakFuel = attributes.Get("leak resistance fuel") / leakResistance;
// 			float leakHeat = attributes.Get("leak resistance heat") / leakResistance;
// 			DoStatusEffect(isDisabled, leakage, leakResistance,
// 				energy, leakEnergy, fuel, leakFuel, heat, leakHeat);
// 		}
// 	
// 		if (burning) {
// 
// 			float burnResistance = attributes.Get("burn resistance");
// 			float burnEnergy = attributes.Get("burn resistance energy") / burnResistance;
// 			float burnFuel = attributes.Get("burn resistance fuel") / burnResistance;
// 			float burnHeat = attributes.Get("burn resistance heat") / burnResistance;
// 			DoStatusEffect(isDisabled, burning, burnResistance,
// 				energy, burnEnergy, fuel, burnFuel, heat, burnHeat);
// 		}
// 	
// 		// When ships recharge, what actually happens is that they can exceed their
// 		// maximum capacity for the rest of the turn, but must be clamped to the
// 		// maximum here before they gain more. This is so that, for example, a ship
// 		// with no batteries but a good generator can still move.
// 		energy = min(energy, attributes.Get("energy capacity"));
// 		fuel = min(fuel, attributes.Get("fuel capacity"));
// 	
// 		heat -= heat * HeatDissipation();
// 		if (heat > MaximumHeat()) {
// 
// 			isOverheated = true;
// 			float heatRatio = Heat() / (1. + attributes.Get("overheat damage threshold"));
// 			if (heatRatio > 1.) {
// 				hull -= attributes.Get("overheat damage rate") * heatRatio;
// 		} else if (heat < .9 * MaximumHeat()) {
// 			isOverheated = false;
// 	
// 		float maxShields = attributes.Get("shields");
// 		shields = min(shields, maxShields);
// 		float maxHull = attributes.Get("hull");
// 		hull = min(hull, maxHull);
// 	
// 		isDisabled = isOverheated || hull < MinimumHull() || (!crew && RequiredCrew());
// 	
// 		// Update ship supply levels.
// 		if (isDisabled) {
// 			PauseAnimation();
// 		} else
// 		{
// 			// Ramscoops work much better when close to the system center.
// 			// Carried fighters can't collect fuel or energy this way.
// 			if (currentSystem) {
// 
// 				float scale = .2 + 1.8 / (.001 * position.Length() + 1);
// 				fuel += currentSystem->RamscoopFuel(attributes.Get("ramscoop"), scale);
// 	
// 				float solarScaling = currentSystem->SolarPower() * scale;
// 				energy += solarScaling * attributes.Get("solar collection");
// 				heat += solarScaling * attributes.Get("solar heat");
// 			}
// 	
// 			float coolingEfficiency = CoolingEfficiency();
// 			energy += attributes.Get("energy generation") - attributes.Get("energy consumption");
// 			fuel += attributes.Get("fuel generation");
// 			heat += attributes.Get("heat generation");
// 			heat -= coolingEfficiency * attributes.Get("cooling");
// 	
// 			// Convert fuel into energy and heat only when the required amount of fuel is available.
// 			if (attributes.Get("fuel consumption") <= fuel) {
// 
// 				fuel -= attributes.Get("fuel consumption");
// 				energy += attributes.Get("fuel energy");
// 				heat += attributes.Get("fuel heat");
// 			}
// 	
// 			// Apply active cooling. The fraction of full cooling to apply equals
// 			// your ship's current fraction of its maximum temperature.
// 			float activeCooling = coolingEfficiency * attributes.Get("active cooling");
// 			if (activeCooling > 0. && heat > 0. && energy >= 0.) {
// 
// 				// Handle the case where "active cooling"
// 				// does not require any energy.
// 				float coolingEnergy = attributes.Get("cooling energy");
// 				if (coolingEnergy) {
// 
// 					float spentEnergy = min(energy, coolingEnergy * min(1., Heat()));
// 					heat -= activeCooling * spentEnergy / coolingEnergy;
// 					energy -= spentEnergy;
// 				} else
// 					heat -= activeCooling * min(1., Heat());
// 			}
// 		}
// 	
// 		// Don't allow any levels to drop below zero.
// 		shields = max(0., shields);
// 		energy = max(0., energy);
// 		fuel = max(0., fuel);
// 		heat = max(0., heat);
// 	}
// 	
// 	
// 	
// 	void Ship::DoPassiveEffects(vector<Visual> &visuals, list<shared_ptr<Flotsam>> &flotsam)
// 	{
// 		// Adjust the error in the pilot's targeting.
// 		personality.UpdateConfusion(firingCommands.IsFiring());
// 	
// 		// Handle ionization effects, etc.
// 		if (ionization) {
// 			CreateSparks(visuals, "ion spark", ionization * .05);
// 		if (scrambling) {
// 			CreateSparks(visuals, "scramble spark", scrambling * .05);
// 		if (disruption) {
// 			CreateSparks(visuals, "disruption spark", disruption * .1);
// 		if (slowness) {
// 			CreateSparks(visuals, "slowing spark", slowness * .1);
// 		if (discharge) {
// 			CreateSparks(visuals, "discharge spark", discharge * .1);
// 		if (corrosion) {
// 			CreateSparks(visuals, "corrosion spark", corrosion * .1);
// 		if (leakage) {
// 			CreateSparks(visuals, "leakage spark", leakage * .1);
// 		if (burning) {
// 			CreateSparks(visuals, "burning spark", burning * .1);
// 	}
// 	
// 	
// 	
// 	void Ship::DoJettison(list<shared_ptr<Flotsam>> &flotsam)
// 	{
// 		// Jettisoned cargo effects (only for ships in the current system).
// 		if (!jettisoned.empty() && !forget) {
// 
// 			jettisoned.front()->Place(*this);
// 			flotsam.splice(flotsam.end(), jettisoned, jettisoned.begin());
// 		}
// 	}
// 	
// 	
// 	
// 	void Ship::DoCloakDecision()
// 	{
// 		if (isInvisible) {
// 			return;
// 	
// 		// If you are forced to decloak (e.g. by running out of fuel) you can't
// 		// initiate cloaking again until you are fully decloaked.
// 		if (!cloak) {
// 			cloakDisruption = max(0., cloakDisruption - 1.);
// 	
// 		float cloakingSpeed = attributes.Get("cloak");
// 		bool canCloak = (!isDisabled && cloakingSpeed > 0. && !cloakDisruption
// 			&& fuel >= attributes.Get("cloaking fuel")
// 			&& energy >= attributes.Get("cloaking energy"));
// 	
// 		if (commands.Has(Command::CLOAK) && canCloak) {
// 
// 			cloak = min(1., cloak + cloakingSpeed);
// 			fuel -= attributes.Get("cloaking fuel");
// 			energy -= attributes.Get("cloaking energy");
// 			heat += attributes.Get("cloaking heat");
// 		} else if (cloakingSpeed) {
// 
// 			cloak = max(0., cloak - cloakingSpeed);
// 			// If you're trying to cloak but are unable to (too little energy or
// 			// fuel) you're forced to decloak fully for one frame before you can
// 			// engage cloaking again.
// 			if (commands.Has(Command::CLOAK)) {
// 				cloakDisruption = max(cloakDisruption, 1.);
// 		} else
// 			cloak = 0.;
// 	}
// 	
// 	
// 	
// 	bool Ship::DoHyperspaceLogic(vector<Visual> &visuals)
// 	{
// 		if (!hyperspaceSystem && !hyperspaceCount) {
// 			return false;
// 	
// 		// Don't apply external acceleration while jumping.
// 		acceleration = Point();
// 	
// 		// Enter hyperspace.
// 		int direction = hyperspaceSystem ? 1 : -1;
// 		hyperspaceCount += direction;
// 		// Number of frames it takes to enter or exit hyperspace.
// 		static const int HYPER_C = 100;
// 		// Rate the ship accelerate and slow down when exiting hyperspace.
// 		static const float HYPER_A = 2.;
// 		static const float HYPER_D = 1000.;
// 		if (hyperspaceSystem) {
// 			fuel -= hyperspaceFuelCost / HYPER_C;
// 	
// 		// Create the particle effects for the jump drive. This may create 100
// 		// or more particles per ship per turn at the peak of the jump.
// 		if (isUsingJumpDrive && !forget) {
// 
// 			float sparkAmount = hyperspaceCount * Width() * Height() * .000006;
// 			const map<const Effect *, int> &jumpEffects = attributes.JumpEffects();
// 			if (jumpEffects.empty()) {
// 				CreateSparks(visuals, "jump drive", sparkAmount);
// 			} else
// 			{
// 				// Spread the amount of particle effects created among all jump effects.
// 				sparkAmount /= jumpEffects.size();
// 				for (const auto &effect : jumpEffects) {
// 					CreateSparks(visuals, effect.first, sparkAmount);
// 			}
// 		}
// 	
// 		if (hyperspaceCount == HYPER_C) {
// 
// 			SetSystem(hyperspaceSystem);
// 			hyperspaceSystem = nullptr;
// 			targetSystem = nullptr;
// 			// Check if the target planet is in the destination system or not.
// 			const Planet *planet = (targetPlanet ? targetPlanet->GetPlanet() : nullptr);
// 			if (!planet || planet->IsWormhole() || !planet->IsInSystem(currentSystem)) {
// 				targetPlanet = nullptr;
// 			// Check if your parent has a target planet in this system.
// 			shared_ptr<Ship> parent = GetParent();
// 			if (!targetPlanet && parent && parent->targetPlanet) {
// 
// 				planet = parent->targetPlanet->GetPlanet();
// 				if (planet && !planet->IsWormhole() && planet->IsInSystem(currentSystem)) {
// 					targetPlanet = parent->targetPlanet;
// 			}
// 			direction = -1;
// 	
// 			// If you have a target planet in the destination system, exit
// 			// hyperspace aimed at it. Otherwise, target the first planet that
// 			// has a spaceport.
// 			Point target;
// 			// Except when you arrive at an extra distance from the target,
// 			// in that case always use the system-center as target.
// 			float extraArrivalDistance = isUsingJumpDrive
// 				? currentSystem->ExtraJumpArrivalDistance() : currentSystem->ExtraHyperArrivalDistance();
// 	
// 			if (extraArrivalDistance == 0) {
// 
// 				if (targetPlanet) {
// 					target = targetPlanet->Position();
// 				} else
// 				{
// 					for (const StellarObject &object : currentSystem->Objects()) {
// 						if (object.HasSprite() && object.HasValidPlanet() {
// 								&& object.GetPlanet()->HasSpaceport())
// 						{
// 							target = object.Position();
// 							break;
// 						}
// 				}
// 			}
// 	
// 			if (isUsingJumpDrive) {
// 
// 				position = target + Angle::Random().Unit() * (300. * (Random::Real() + 1.) + extraArrivalDistance);
// 				return true;
// 			}
// 	
// 			// Have all ships exit hyperspace at the same distance so that
// 			// your escorts always stay with you.
// 			float distance = (HYPER_C * HYPER_C) * .5 * HYPER_A + HYPER_D;
// 			distance += extraArrivalDistance;
// 			position = (target - distance * angle.Unit());
// 			position += hyperspaceOffset;
// 			// Make sure your velocity is in exactly the direction you are
// 			// traveling in, so that when you decelerate there will not be a
// 			// sudden shift in direction at the end.
// 			velocity = velocity.Length() * angle.Unit();
// 		}
// 		if (!isUsingJumpDrive) {
// 
// 			velocity += (HYPER_A * direction) * angle.Unit();
// 			if (!hyperspaceSystem) {
// 
// 				// Exit hyperspace far enough from the planet to be able to land.
// 				// This does not take drag into account, so it is always an over-
// 				// estimate of how long it will take to stop.
// 				// We start decelerating after rotating about 150 degrees (that
// 				// is, about acos(.8) from the proper angle). So:
// 				// Stopping distance = .5*a*(v/a)^2 + (150/turn)*v.
// 				// Exit distance = HYPER_D + .25 * v^2 = stopping distance.
// 				float exitV = max(HYPER_A, MaxVelocity());
// 				float a = (.5 / Acceleration() - .25);
// 				float b = 150. / TurnRate();
// 				float discriminant = b * b - 4. * a * -HYPER_D;
// 				if (discriminant > 0.) {
// 
// 					float altV = (-b + sqrt(discriminant)) / (2. * a);
// 					if (altV > 0. && altV < exitV) {
// 						exitV = altV;
// 				}
// 				// If current velocity is less than or equal to targeted velocity
// 				// consider the hyperspace exit done.
// 				const Point facingUnit = angle.Unit();
// 				if (velocity.Dot(facingUnit) <= exitV) {
// 
// 					velocity = facingUnit * exitV;
// 					hyperspaceCount = 0;
// 				}
// 			}
// 		}
// 		position += velocity;
// 		if (GetParent() && GetParent()->currentSystem == currentSystem) {
// 
// 			hyperspaceOffset = position - GetParent()->position;
// 			float length = hyperspaceOffset.Length();
// 			if (length > 1000.) {
// 				hyperspaceOffset *= 1000. / length;
// 		}
// 	
// 		return true;
// 	}
// 	
// 	
// 	
// 	bool Ship::DoLandingLogic()
// 	{
// 		if (!landingPlanet && zoom >= 1.f) {
// 			return false;
// 	
// 		// Don't apply external acceleration while landing.
// 		acceleration = Point();
// 	
// 		// If a ship was disabled at the very moment it began landing, do not
// 		// allow it to continue landing.
// 		if (isDisabled) {
// 			landingPlanet = nullptr;
// 	
// 		float landingSpeed = attributes.Get("landing speed");
// 		landingSpeed = landingSpeed > 0 ? landingSpeed : .02f;
// 		// Special ships do not disappear forever when they land; they
// 		// just slowly refuel.
// 		if (landingPlanet && zoom) {
// 
// 			// Move the ship toward the center of the planet while landing.
// 			if (GetTargetStellar()) {
// 				position = .97 * position + .03 * GetTargetStellar()->Position();
// 			zoom -= landingSpeed;
// 			if (zoom < 0.f) {
// 
// 				// If this is not a special ship, it ceases to exist when it
// 				// lands on a true planet. If this is a wormhole, the ship is
// 				// instantly transported.
// 				if (landingPlanet->IsWormhole()) {
// 
// 					SetSystem(&landingPlanet->GetWormhole()->WormholeDestination(*currentSystem));
// 					for (const StellarObject &object : currentSystem->Objects()) {
// 						if (object.GetPlanet() == landingPlanet) {
// 							position = object.Position();
// 					SetTargetStellar(nullptr);
// 					SetTargetSystem(nullptr);
// 					landingPlanet = nullptr;
// 				} else if (!isSpecial || personality.IsFleeing()) {
// 
// 					MarkForRemoval();
// 					return true;
// 				}
// 	
// 				zoom = 0.f;
// 			}
// 		}
// 		// Only refuel if this planet has a spaceport.
// 		} else if (fuel >= attributes.Get("fuel capacity") {
// 				|| !landingPlanet || !landingPlanet->HasSpaceport())
// 		{
// 			zoom = min(1.f, zoom + landingSpeed);
// 			SetTargetStellar(nullptr);
// 			landingPlanet = nullptr;
// 		} else
// 			fuel = min(fuel + 1., attributes.Get("fuel capacity"));
// 	
// 		// Move the ship at the velocity it had when it began landing, but
// 		// scaled based on how small it is now.
// 		if (zoom > 0.f) {
// 			position += velocity * zoom;
// 	
// 		return true;
// 	}
// 	
// 	
// 	
// 	void Ship::DoInitializeMovement()
// 	{
// 		// If you're disabled, you can't initiate landing or jumping.
// 		if (isDisabled) {
// 			return;
// 	
// 		if (commands.Has(Command::LAND) && CanLand()) {
// 			landingPlanet = GetTargetStellar()->GetPlanet();
// 		} else if (commands.Has(Command::JUMP) && IsReadyToJump()) {
// 
// 			hyperspaceSystem = GetTargetSystem();
// 			pair<JumpType, float> jumpUsed = navigation.GetCheapestJumpType(hyperspaceSystem);
// 			isUsingJumpDrive = (jumpUsed.first == JumpType::JUMP_DRIVE);
// 			hyperspaceFuelCost = jumpUsed.second;
// 		}
// 	}
// 	
// 	
// 	
// 	void Ship::StepPilot()
// 	{
// 		int requiredCrew = RequiredCrew();
// 	
// 		if (pilotError) {
// 			--pilotError;
// 		} else if (pilotOkay) {
// 			--pilotOkay;
// 		} else if (isDisabled) {
// 
// 			// If the ship is disabled, don't show a warning message due to missing crew.
// 		} else if (requiredCrew && static_cast<int>(Random::Int(requiredCrew)) >= Crew()) {
// 
// 			pilotError = 30;
// 			if (isYours || (personality.IsEscort() && Preferences::Has("Extra fleet status messages"))) {
// 
// 				if (parent.lock()) {
// 					Messages::Add("The " + name + " is moving erratically because there are not enough crew to pilot it."
// 						, Messages::Importance::Low);
// 				} else
// 					Messages::Add("Your ship is moving erratically because you do not have enough crew to pilot it."
// 						, Messages::Importance::Low);
// 			}
// 		} else
// 			pilotOkay = 30;
// 	}
// 	
// 	
// 	
// 	// This ship is not landing or entering hyperspace. So, move it. If it is
// 	// disabled, all it can do is slow down to a stop.
// 	void Ship::DoMovement(bool &isUsingAfterburner)
// 	{
// 		isUsingAfterburner = false;
// 	
// 		float mass = InertialMass();
// 		float slowMultiplier = 1. / (1. + slowness * .05);
// 	
// 		if (isDisabled) {
// 			velocity *= 1. - Drag() / mass;
// 		} else if (!pilotError) {
// 
// 			if (commands.Turn()) {
// 
// 				// Check if we are able to turn.
// 				float cost = attributes.Get("turning energy");
// 				if (cost > 0. && energy < cost * fabs(commands.Turn())) {
// 					commands.SetTurn(commands.Turn() * energy / (cost * fabs(commands.Turn())));
// 	
// 				cost = attributes.Get("turning shields");
// 				if (cost > 0. && shields < cost * fabs(commands.Turn())) {
// 					commands.SetTurn(commands.Turn() * shields / (cost * fabs(commands.Turn())));
// 	
// 				cost = attributes.Get("turning hull");
// 				if (cost > 0. && hull < cost * fabs(commands.Turn())) {
// 					commands.SetTurn(commands.Turn() * hull / (cost * fabs(commands.Turn())));
// 	
// 				cost = attributes.Get("turning fuel");
// 				if (cost > 0. && fuel < cost * fabs(commands.Turn())) {
// 					commands.SetTurn(commands.Turn() * fuel / (cost * fabs(commands.Turn())));
// 	
// 				cost = -attributes.Get("turning heat");
// 				if (cost > 0. && heat < cost * fabs(commands.Turn())) {
// 					commands.SetTurn(commands.Turn() * heat / (cost * fabs(commands.Turn())));
// 	
// 				if (commands.Turn()) {
// 
// 					isSteering = true;
// 					steeringDirection = commands.Turn();
// 					// If turning at a fraction of the full rate (either from lack of
// 					// energy or because of tracking a target), only consume a fraction
// 					// of the turning energy and produce a fraction of the heat.
// 					float scale = fabs(commands.Turn());
// 	
// 					shields -= scale * attributes.Get("turning shields");
// 					hull -= scale * attributes.Get("turning hull");
// 					energy -= scale * attributes.Get("turning energy");
// 					fuel -= scale * attributes.Get("turning fuel");
// 					heat += scale * attributes.Get("turning heat");
// 					discharge += scale * attributes.Get("turning discharge");
// 					corrosion += scale * attributes.Get("turning corrosion");
// 					ionization += scale * attributes.Get("turning ion");
// 					scrambling += scale * attributes.Get("turning scramble");
// 					leakage += scale * attributes.Get("turning leakage");
// 					burning += scale * attributes.Get("turning burn");
// 					slowness += scale * attributes.Get("turning slowing");
// 					disruption += scale * attributes.Get("turning disruption");
// 	
// 					angle += commands.Turn() * TurnRate() * slowMultiplier;
// 				}
// 			}
// 			float thrustCommand = commands.Has(Command::FORWARD) - commands.Has(Command::BACK);
// 			float thrust = 0.;
// 			if (thrustCommand) {
// 
// 				// Check if we are able to apply this thrust.
// 				float cost = attributes.Get((thrustCommand > 0.) ?
// 					"thrusting energy" : "reverse thrusting energy");
// 				if (cost > 0. && energy < cost) {
// 					thrustCommand *= energy / cost;
// 	
// 				cost = attributes.Get((thrustCommand > 0.) ?
// 					"thrusting shields" : "reverse thrusting shields");
// 				if (cost > 0. && shields < cost) {
// 					thrustCommand *= shields / cost;
// 	
// 				cost = attributes.Get((thrustCommand > 0.) ?
// 					"thrusting hull" : "reverse thrusting hull");
// 				if (cost > 0. && hull < cost) {
// 					thrustCommand *= hull / cost;
// 	
// 				cost = attributes.Get((thrustCommand > 0.) ?
// 					"thrusting fuel" : "reverse thrusting fuel");
// 				if (cost > 0. && fuel < cost) {
// 					thrustCommand *= fuel / cost;
// 	
// 				cost = -attributes.Get((thrustCommand > 0.) ?
// 					"thrusting heat" : "reverse thrusting heat");
// 				if (cost > 0. && heat < cost) {
// 					thrustCommand *= heat / cost;
// 	
// 				if (thrustCommand) {
// 
// 					// If a reverse thrust is commanded and the capability does not
// 					// exist, ignore it (do not even slow under drag).
// 					isThrusting = (thrustCommand > 0.);
// 					isReversing = !isThrusting && attributes.Get("reverse thrust");
// 					thrust = attributes.Get(isThrusting ? "thrust" : "reverse thrust");
// 					if (thrust) {
// 
// 						float scale = fabs(thrustCommand);
// 	
// 						shields -= scale * attributes.Get(isThrusting ? "thrusting shields" : "reverse thrusting shields");
// 						hull -= scale * attributes.Get(isThrusting ? "thrusting hull" : "reverse thrusting hull");
// 						energy -= scale * attributes.Get(isThrusting ? "thrusting energy" : "reverse thrusting energy");
// 						fuel -= scale * attributes.Get(isThrusting ? "thrusting fuel" : "reverse thrusting fuel");
// 						heat += scale * attributes.Get(isThrusting ? "thrusting heat" : "reverse thrusting heat");
// 						discharge += scale * attributes.Get(isThrusting ? "thrusting discharge" : "reverse thrusting discharge");
// 						corrosion += scale * attributes.Get(isThrusting ? "thrusting corrosion" : "reverse thrusting corrosion");
// 						ionization += scale * attributes.Get(isThrusting ? "thrusting ion" : "reverse thrusting ion");
// 						scrambling += scale * attributes.Get(isThrusting ? "thrusting scramble" :
// 							"reverse thrusting scramble");
// 						burning += scale * attributes.Get(isThrusting ? "thrusting burn" : "reverse thrusting burn");
// 						leakage += scale * attributes.Get(isThrusting ? "thrusting leakage" : "reverse thrusting leakage");
// 						slowness += scale * attributes.Get(isThrusting ? "thrusting slowing" : "reverse thrusting slowing");
// 						disruption += scale * attributes.Get(isThrusting ? "thrusting disruption" : "reverse thrusting disruption");
// 	
// 						acceleration += angle.Unit() * (thrustCommand * thrust / mass);
// 					}
// 				}
// 			}
// 			bool applyAfterburner = (commands.Has(Command::AFTERBURNER) || (thrustCommand > 0. && !thrust))
// 					&& !CannotAct();
// 			if (applyAfterburner) {
// 
// 				thrust = attributes.Get("afterburner thrust");
// 				float shieldCost = attributes.Get("afterburner shields");
// 				float hullCost = attributes.Get("afterburner hull");
// 				float energyCost = attributes.Get("afterburner energy");
// 				float fuelCost = attributes.Get("afterburner fuel");
// 				float heatCost = -attributes.Get("afterburner heat");
// 	
// 				float dischargeCost = attributes.Get("afterburner discharge");
// 				float corrosionCost = attributes.Get("afterburner corrosion");
// 				float ionCost = attributes.Get("afterburner ion");
// 				float scramblingCost = attributes.Get("afterburner scramble");
// 				float leakageCost = attributes.Get("afterburner leakage");
// 				float burningCost = attributes.Get("afterburner burn");
// 	
// 				float slownessCost = attributes.Get("afterburner slowing");
// 				float disruptionCost = attributes.Get("afterburner disruption");
// 	
// 				if (thrust && shields >= shieldCost && hull >= hullCost
// 					&& energy >= energyCost && fuel >= fuelCost && heat >= heatCost)
// 				{
// 					shields -= shieldCost;
// 					hull -= hullCost;
// 					energy -= energyCost;
// 					fuel -= fuelCost;
// 					heat -= heatCost;
// 	
// 					discharge += dischargeCost;
// 					corrosion += corrosionCost;
// 					ionization += ionCost;
// 					scrambling += scramblingCost;
// 					leakage += leakageCost;
// 					burning += burningCost;
// 	
// 					slowness += slownessCost;
// 					disruption += disruptionCost;
// 	
// 					acceleration += angle.Unit() * thrust / mass;
// 	
// 					// Only create the afterburner effects if the ship is in the player's system.
// 					isUsingAfterburner = !forget;
// 				}
// 			}
// 		}
// 		if (acceleration) {
// 
// 			acceleration *= slowMultiplier;
// 			Point dragAcceleration = acceleration - velocity * (Drag() / mass);
// 			// Make sure dragAcceleration has nonzero length, to avoid divide by zero.
// 			if (dragAcceleration) {
// 
// 				// What direction will the net acceleration be if this drag is applied?
// 				// If the net acceleration will be opposite the thrust, do not apply drag.
// 				dragAcceleration *= .5 * (acceleration.Unit().Dot(dragAcceleration.Unit()) + 1.);
// 	
// 				// A ship can only "cheat" to stop if it is moving slow enough that
// 				// it could stop completely this frame. This is to avoid overshooting
// 				// when trying to stop and ending up headed in the other direction.
// 				if (commands.Has(Command::STOP)) {
// 
// 					// How much acceleration would it take to come to a stop in the
// 					// direction normal to the ship's current facing? This is only
// 					// possible if the acceleration plus drag vector is in the
// 					// opposite direction from the velocity vector when both are
// 					// projected onto the current facing vector, and the acceleration
// 					// vector is the larger of the two.
// 					float vNormal = velocity.Dot(angle.Unit());
// 					float aNormal = dragAcceleration.Dot(angle.Unit());
// 					if ((aNormal > 0.) != (vNormal > 0.) && fabs(aNormal) > fabs(vNormal)) {
// 						dragAcceleration = -vNormal * angle.Unit();
// 				}
// 				velocity += dragAcceleration;
// 			}
// 			acceleration = Point();
// 		}
// 	}
// 	
// 	
// 	
// 	void Ship::StepTargeting()
// 	{
// 		// Boarding:
// 		shared_ptr<const Ship> target = GetTargetShip();
// 		// If this is a fighter or drone and it is not assisting someone at the
// 		// moment, its boarding target should be its parent ship.
// 		if (CanBeCarried() && !(target && target == GetShipToAssist())) {
// 			target = GetParent();
// 		if (target && !isDisabled) {
// 
// 			Point dp = (target->position - position);
// 			float distance = dp.Length();
// 			Point dv = (target->velocity - velocity);
// 			float speed = dv.Length();
// 			isBoarding = (distance < 50. && speed < 1. && commands.Has(Command::BOARD));
// 			if (isBoarding && !CanBeCarried()) {
// 
// 				if (!target->IsDisabled() && government->IsEnemy(target->government)) {
// 					isBoarding = false;
// 				} else if (target->IsDestroyed() || target->IsLanding() || target->IsHyperspacing() {
// 						|| target->GetSystem() != GetSystem())
// 					isBoarding = false;
// 			}
// 			if (isBoarding && !pilotError) {
// 
// 				Angle facing = angle;
// 				bool left = target->Unit().Cross(facing.Unit()) < 0.;
// 				float turn = left - !left;
// 	
// 				// Check if the ship will still be pointing to the same side of the target
// 				// angle if it turns by this amount.
// 				facing += TurnRate() * turn;
// 				bool stillLeft = target->Unit().Cross(facing.Unit()) < 0.;
// 				if (left != stillLeft) {
// 					turn = 0.;
// 				angle += TurnRate() * turn;
// 	
// 				velocity += dv.Unit() * .1;
// 				position += dp.Unit() * .5;
// 	
// 				if (distance < 10. && speed < 1. && (CanBeCarried() || !turn)) {
// 
// 					if (cloak) {
// 
// 						// Allow the player to get all the way to the end of the
// 						// boarding sequence (including locking on to the ship) but
// 						// not to actually board, if they are cloaked.
// 						if (isYours) {
// 							Messages::Add("You cannot board a ship while cloaked.", Messages::Importance::High);
// 					} else
// 					{
// 						isBoarding = false;
// 						bool isEnemy = government->IsEnemy(target->government);
// 						if (isEnemy && Random::Real() < target->Attributes().Get("self destruct")) {
// 
// 							Messages::Add("The " + target->DisplayModelName() + " \"" + target->Name()
// 								+ "\" has activated its self-destruct mechanism.", Messages::Importance::High);
// 							GetTargetShip()->SelfDestruct();
// 						} else
// 							hasBoarded = true;
// 					}
// 				}
// 			}
// 		}
// 	
// 		// Clear your target if it is destroyed. This is only important for NPCs,
// 		// because ordinary ships cease to exist once they are destroyed.
// 		target = GetTargetShip();
// 		if (target && target->IsDestroyed() && target->explosionCount >= target->explosionTotal) {
// 			targetShip.reset();
// 	}
// 	
// 	
// 	
// 	// Finally, move the ship and create any movement visuals.
// 	void Ship::DoEngineVisuals(vector<Visual> &visuals, bool isUsingAfterburner)
// 	{
// 		if (isUsingAfterburner && !Attributes().AfterburnerEffects().empty()) {
// 			for (const EnginePoint &point : enginePoints) {
// 
// 				Point pos = angle.Rotate(point) * Zoom() + position;
// 				// Stream the afterburner effects outward in the direction the engines are facing.
// 				Point effectVelocity = velocity - 6. * angle.Unit();
// 				for (auto &&it : Attributes().AfterburnerEffects()) {
// 					for (int i = 0; i < it.second; ++i) {
// 						visuals.emplace_back(*it.first, pos, effectVelocity, angle);
// 			}
// 	}
// 	
// 	
// 	
// 	// Add escorts to this ship. Escorts look to the parent ship for movement
// 	// cues and try to stay with it when it lands or goes into hyperspace.
// 	void Ship::AddEscort(Ship &ship)
// 	{
// 		escorts.push_back(ship.shared_from_this());
// 	}
// 	
// 	
// 	
// 	void Ship::RemoveEscort(const Ship &ship)
// 	{
// 		auto it = escorts.begin();
// 		for ( ; it != escorts.end(); ++it) {
// 			if (it->lock().get() == &ship) {
// 
// 				escorts.erase(it);
// 				return;
// 			}
// 	}
// 	
// 	
// 	
// 	float Ship::MinimumHull() const
// 	{
// 		if (neverDisabled) {
// 			return 0.;
// 	
// 		float maximumHull = attributes.Get("hull");
// 		float absoluteThreshold = attributes.Get("absolute threshold");
// 		if (absoluteThreshold > 0.) {
// 			return absoluteThreshold;
// 	
// 		float thresholdPercent = attributes.Get("threshold percentage");
// 		float transition = 1 / (1 + 0.0005 * maximumHull);
// 		float minimumHull = maximumHull * (thresholdPercent > 0.
// 			? min(thresholdPercent, 1.) : 0.1 * (1. - transition) + 0.5 * transition);
// 	
// 		return max(0., floor(minimumHull + attributes.Get("hull threshold")));
// 	}
// 	
// 	
// 	
// 	void Ship::CreateExplosion(vector<Visual> &visuals, bool spread)
// 	{
// 		if (!HasSprite() || !GetMask().IsLoaded() || explosionEffects.empty()) {
// 			return;
// 	
// 		// Bail out if this loops enough times, just in case.
// 		for (int i = 0; i < 10; ++i) {
// 
// 			Point point((Random::Real() - .5) * Width(),
// 				(Random::Real() - .5) * Height());
// 			if (GetMask().Contains(point, Angle())) {
// 
// 				// Pick an explosion.
// 				int type = Random::Int(explosionTotal);
// 				auto it = explosionEffects.begin();
// 				for ( ; it != explosionEffects.end(); ++it) {
// 
// 					type -= it->second;
// 					if (type < 0) {
// 						break;
// 				}
// 				Point effectVelocity = velocity;
// 				if (spread) {
// 
// 					float scale = .04 * (Width() + Height());
// 					effectVelocity += Angle::Random().Unit() * (scale * Random::Real());
// 				}
// 				visuals.emplace_back(*it->first, angle.Rotate(point) + position, std::move(effectVelocity), angle);
// 				++explosionCount;
// 				return;
// 			}
// 		}
// 	}
// 	
// 	
// 	
// 	// Place a "spark" effect, like ionization or disruption.
// 	void Ship::CreateSparks(vector<Visual> &visuals, const string &name, float amount)
// 	{
// 		CreateSparks(visuals, GameData::Effects().Get(name), amount);
// 	}
// 	
// 	
// 	
// 	void Ship::CreateSparks(vector<Visual> &visuals, const Effect *effect, float amount)
// 	{
// 		if (forget) {
// 			return;
// 	
// 		// Limit the number of sparks, depending on the size of the sprite.
// 		amount = min(amount, Width() * Height() * .0006);
// 		// Preallocate capacity, in case we're adding a non-trivial number of sparks.
// 		visuals.reserve(visuals.size() + static_cast<int>(amount));
// 	
// 		while(true)
// 		{
// 			amount -= Random::Real();
// 			if (amount <= 0.) {
// 				break;
// 	
// 			Point point((Random::Real() - .5) * Width(),
// 				(Random::Real() - .5) * Height());
// 			if (GetMask().Contains(point, Angle())) {
// 				visuals.emplace_back(*effect, angle.Rotate(point) + position, velocity, angle);
// 		}
// 	}
// 	
// 	
// 	
// 	float Ship::CalculateAttraction() const
// 	{
// 		return max(0., .4 * sqrt(attributes.Get("cargo space")) - 1.8);
// 	}
// 	
// 	
// 	
// 	float Ship::CalculateDeterrence() const
// 	{
// 		float tempDeterrence = 0.;
// 		for (const Hardpoint &hardpoint : Weapons()) {
// 			if (hardpoint.GetOutfit()) {
// 
// 				const Outfit *weapon = hardpoint.GetOutfit();
// 				if (weapon->Ammo() && weapon->AmmoUsage() && !OutfitCount(weapon->Ammo())) {
// 					continue;
// 				float strength = weapon->ShieldDamage() + weapon->HullDamage()
// 					+ (weapon->RelativeShieldDamage() * attributes.Get("shields"))
// 					+ (weapon->RelativeHullDamage() * attributes.Get("hull"));
// 				tempDeterrence += .12 * strength / weapon->Reload();
// 			}
// 		return tempDeterrence;
// 	}

	public function getAttribute($attributeName) {
		return $this->attributes->get($attributeName);
	}

	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = parent::toJSON(true);
		
		$jsonArray['baseShipId'] = $this->base?->getId();
		$jsonArray['trueModelName'] = $this->trueModelName;
		$jsonArray['displayModelName'] = $this->displayModelName;
		$jsonArray['pluralModelName'] = $this->pluralModelName;
		$jsonArray['variantName'] = $this->variantName;
		$jsonArray['noun'] = $this->noun;
		$jsonArray['description'] = $this->description;
		$jsonArray['thumbnailId'] = $this->thumbnail?->getId();
		$jsonArray['name'] = $this->name;
		$jsonArray['canBeCarried'] = $this->canBeCarried;
		$jsonArray['neverDisabled'] = $this->neverDisabled;
		$jsonArray['isCapturable'] = $this->isCapturable;
		$jsonArray['isInvisible'] = $this->isInvisible;
		$jsonArray['customSwizzle'] = $this->customSwizzle;
		$jsonArray['cloak'] = $this->cloak;
		$jsonArray['cloakDisruption'] = $this->cloakDisruption;
		$jsonArray['antiMissileRange'] = $this->antiMissileRange;
		$jsonArray['weaponRadius'] = $this->weaponRadius;
		$jsonArray['cargoScan'] = $this->cargoScan;
		$jsonArray['outfitScan'] = $this->outfitScan;
		$jsonArray['attributesOutfit'] = $this->attributes?->toJSON(true);
		$jsonArray['baseAttributesOutfit'] = $this->baseAttributes?->toJSON(true);
		$jsonArray['addAttributes'] = $this->addAttributes;
		$jsonArray['shields'] = $this->shields;
		$jsonArray['hull'] = $this->hull;
		
		if (count($this->getOutfits()) == 0 && $this->base && count($this->base->getOutfits()) > 0) {
			$jsonArray['outfits'] = $this->base->getOutfits();
		} else {
			$jsonArray['outfits'] = $this->getOutfits();
		}
		
		$jsonArray['hardpoints'] = [];
		foreach ($this->hardpoints as $Hardpoint) {
			$jsonArray['hardpoints'] []= $Hardpoint->toJSON(true);
		}
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
	
	public function setFromJSON(string|array $jsonArray): void {
		if (!is_array($jsonArray)) {
			$jsonArray = json_decode($jsonArray, true);
		}
		parent::setFromJSON($jsonArray);
		
		if ($jsonArray['baseName']) {
			$this->base = GameData::Ships()[$jsonArray['baseName']];
		}
		$this->trueModelName = $jsonArray['trueModelName'];
		$this->displayModelName = $jsonArray['displayModelName'];
		$this->pluralModelName = $jsonArray['pluralModelName'];
		$this->variantName = $jsonArray['variantName'];
		$this->noun = $jsonArray['noun'];
		$this->description = $jsonArray['description'];
		if ($jsonArray['thumbnail']) {
			$this->thumbnail = SpriteSet::Get($jsonArray['thumbnail']['name']);
		}
		$this->name = $jsonArray['name'] ?: $this->trueModelName;
		$this->canBeCarried = $jsonArray['canBeCarried'];
		$this->neverDisabled = $jsonArray['neverDisabled'];
		$this->isCapturable = $jsonArray['isCapturable'];
		$this->isInvisible = $jsonArray['isInvisible'];
		$this->customSwizzle = $jsonArray['customSwizzle'];
		$this->cloak = $jsonArray['cloak'];
		$this->cloakDisruption = $jsonArray['cloakDisruption'];
		$this->antiMissileRange = $jsonArray['antiMissileRange'];
		$this->weaponRadius = $jsonArray['weaponRadius'];
		$this->cargoScan = $jsonArray['cargoScan'];
		$this->outfitScan = $jsonArray['outfitScan'];
		$this->attributes = new Outfit();
		$this->attributes->setFromJSON($jsonArray['attributesOutfit']);
		$this->baseAttributes = new Outfit();
		$this->baseAttributes->setFromJSON($jsonArray['baseAttributesOutfit']);
		$this->addAttributes = $jsonArray['addAttributes'];
		$this->shields = $jsonArray['shields'] ?: $this->baseAttributes->get('shields');
		$this->hull = $jsonArray['hull'] ?: $this->baseAttributes->get('hull');
		$this->mass = $this->baseAttributes->getMass();
		$this->cost = $this->baseAttributes->getCost();
		$this->crew = $this->baseAttributes->get('required crew');
		$this->bunks = $this->baseAttributes->get('bunks');
		
		$this->outfits = $jsonArray['outfits'];
		
		foreach ($jsonArray['hardpoints'] as $hardpointArray) {
			$Hardpoint = new Hardpoint(point: new Point($hardpointArray['point']['x'], $hardpointArray['point']['y']),ship: $this, outfit: $hardpointArray['equippedOutfit'] ? GameData::Outfits()[$hardpointArray['equippedOutfit']] : null, isUnder: $hardpointArray['isUnder'], baseAngle: new Angle($hardpointArray['baseAngle']), isParallel: $hardpointArray['isParallel'], isTurret: $hardpointArray['isTurret']);
			$this->hardpoints []= $Hardpoint;
		}
		
	}

    public function getOutfits(): array {
        if (count($this->outfits) == 0) {
			foreach ($this->shipOutfits as $ShipOutfit) {
				$this->outfits[$ShipOutfit->getOutfit()->getTrueName()] = $ShipOutfit->getCount();
			}
		}
		
		return $this->outfits;
    }

    /**
     * @return Collection<int, ShipOutfit>
     */
    public function getShipOutfits(): Collection
    {
        return $this->shipOutfits;
    }

    public function addShipOutfit(ShipOutfit $shipOutfit): static
    {
        if (!$this->shipOutfits->contains($shipOutfit)) {
            $this->shipOutfits->add($shipOutfit);
            $shipOutfit->setShip($this);
        }

        return $this;
    }

    public function removeShipOutfit(ShipOutfit $shipOutfit): static
    {
        if ($this->shipOutfits->removeElement($shipOutfit)) {
            // set the owning side to null (unless already changed)
            if ($shipOutfit->getShip() === $this) {
                $shipOutfit->setShip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Hardpoint>
     */
    public function getHardpoints(): Collection
    {
        return $this->hardpoints;
    }

    public function addHardpoint(Hardpoint $hardpoint): static
    {
        if (!$this->hardpoints->contains($hardpoint)) {
            $this->hardpoints->add($hardpoint);
            $hardpoint->setShip($this);
        }

        return $this;
    }

    public function removeHardpoint(Hardpoint $hardpoint): static
    {
        if ($this->hardpoints->removeElement($hardpoint)) {
            // set the owning side to null (unless already changed)
            if ($hardpoint->getShip() === $this) {
                $hardpoint->setShip(null);
            }
        }

        return $this;
    }

}

	// The hull may spring a "leak" (venting atmosphere, flames, blood, etc.)
// when the ship is dying.
class Leak {
	public function __construct(?Effect $effect = null) {
		$this->effect = $effect;
	}

	public ?Effect $effect = null;
	public Point $location;
	public Angle $angle;
	public int $openPeriod = 60;
	public int $closePeriod = 60;
};

class Bay {
// public:
// 	Bay(float x, float y, std::string category) : point(x * .5, y * .5), category(std::move(category)) {}
// 	Bay(Bay &&) = default;
// 	Bay &operator=(Bay &&) = default;
// 	~Bay() = default;
// 
// 	// Copying a bay does not copy the ship inside it.
// 	Bay(const Bay &b) : point(b.point), category(b.category), side(b.side),
// 		facing(b.facing), launchEffects(b.launchEffects) {}
// 	Bay &operator=(const Bay &b) { return *this = Bay(b); }

	public Point $point;
	public Ship $ship;
	public string $category = '';

	public int $side = 0;
	const INSIDE = 0;
	const OVER = 1;
	const UNDER = 2;

	// The angle at which the carried ship will depart, relative to the carrying ship.
	public Angle $facing;

	// The launch effect(s) to be simultaneously played when the bay's ship launches.
	public array $launchEffects = []; // vector<const Effect *>
	
	public function __construct(?float $x = null, ?float $y = null, ?string $category = null, ?Bay $bay = null) {
		if ($x !== null) {
			$this->point = new Point($x * 0.5, $y * 0.5);
			$this->category = $category;
		} else if ($bay != null) {
			$this->point = new Point($bay->point->X(), $bay->point->Y());
			$this->ship = $bay->ship;
			$this->category = $bay->category;
			$this->side = $bay->side;
			$this->launchEffects = $bay->launchEffects;
		}
		$this->facing = new Angle();
	}
}

class EnginePoint extends Point {
	public int $side = 0;
	const UNDER = 0;
	const OVER = 1;

	public int $steering = 0;
	const NONE = 0;
	const LEFT = 1;
	const RIGHT = 2;

	public float $zoom;
	public Angle $facing;
	
	public function __construct(float $x, float $y, float $zoom) {
		parent::__construct($x, $y);
		$this->zoom = $zoom;
		$this->facing = new Angle();
	}
};