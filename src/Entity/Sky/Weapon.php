<?php

namespace App\Entity\Sky;

use App\Entity\WeaponDamage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'Weapon')]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "subclass", type: "string")]
#[ORM\DiscriminatorMap(['weapon' => Weapon::class, 'outfit' => Outfit::class])]
class Weapon {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	protected int $id;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Body', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false)]
	protected Body $sprite;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Body', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false)]
	protected Body $hardpointSprite;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sound')]
	#[ORM\JoinColumn(nullable: true)]
	protected ?Sound $sound = null;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sprite', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true)]
	protected ?Sprite $icon = null;
	
	// Fire, die and hit effects.
	// All maps changed to key on effect name, with value ['effect'=>Effect, 'count'=>int]
	protected array $fireEffects = []; // map<const Effect *, int> 
	protected array $liveEffects = []; // map<const Effect *, int> 
	protected array $hitEffects = []; // map<const Effect *, int> 
	protected array $targetEffects = []; // map<const Effect *, int> 
	protected array $dieEffects = []; // map<const Effect *, int> 
	protected array $submunitions = []; // vector<Submunition>
	
	// This stores whether or not the weapon has been loaded.
	#[ORM\Column(type: 'boolean', name: 'isWeapon')]
	protected bool $isWeapon = false;
	
	#[ORM\Column(type: 'boolean', name: 'isStreamed')]
	protected bool $isStreamed = false;
	
	#[ORM\Column(type: 'boolean', name: 'isSafe')]
	protected bool $isSafe = false;
	
	#[ORM\Column(type: 'boolean', name: 'isPhasing')]
	protected bool $isPhasing = false;
	
	#[ORM\Column(type: 'boolean', name: 'isDamageScaled')]
	protected bool $isDamageScaled = true;
	
	#[ORM\Column(type: 'boolean', name: 'isGravitational')]
	protected bool $isGravitational = false;
	
	// Guns and missiles are by default aimed a converged point at the
	// maximum weapons range in front of the ship. When either the installed
	// weapon or the gun-port (or both) have the isParallel attribute set
	// to true, then this convergence will not be used and the weapon will
	// be aimed directly in the gunport angle/direction.
	#[ORM\Column(type: 'boolean', name: 'isParallel')]
	protected bool $isParallel = false;
	
	// Attributes.
	#[ORM\Column(type: 'integer', name: 'lifetime')]
	protected int $lifetime = 0;
	
	#[ORM\Column(type: 'integer', name: 'randomLifetime')]
	protected int $randomLifetime = 0;
	
	#[ORM\Column(type: 'float', name: 'reload')]
	protected float $reload = 1.;
	
	#[ORM\Column(type: 'float', name: 'burstReload')]
	protected float $burstReload = 1.;
	
	#[ORM\Column(type: 'integer', name: 'burstCount')]
	protected int $burstCount = 1;
	
	#[ORM\Column(type: 'integer', name: 'homing')]
	protected int $homing = 0;
	
	
	#[ORM\Column(type: 'integer', name: 'missileStrength')]
	protected int $missileStrength = 0;
	
	#[ORM\Column(type: 'integer', name: 'antiMissile')]
	protected int $antiMissile = 0;
	
	
	#[ORM\Column(type: 'float', name: 'velocity')]
	protected float $velocity = 0.;
	
	#[ORM\Column(type: 'float', name: 'randomVelocity')]
	protected float $randomVelocity = 0.;
	
	#[ORM\Column(type: 'float', name: 'acceleration')]
	protected float $acceleration = 0.;
	
	#[ORM\Column(type: 'float', name: 'drag')]
	protected float $drag = 0.;
	
	#[ORM\Column(type: 'string')]
	protected string $hardpointOffsetString = '';
	protected Point $hardpointOffset;
	
	#[ORM\Column(type: 'float', name: 'turn')]
	protected float $turn = 0.;
	
	#[ORM\Column(type: 'float', name: 'inaccuracy')]
	protected float $inaccuracy = 0.;
	
	#[ORM\Column(type: 'float', name: 'turretTurn')]
	protected float $turretTurn = 0.;
	
	
	#[ORM\Column(type: 'float', name: 'tracking')]
	protected float $tracking = 0.;
	
	#[ORM\Column(type: 'float', name: 'opticalTracking')]
	protected float $opticalTracking = 0.;
	
	#[ORM\Column(type: 'float', name: 'infraredTracking')]
	protected float $infraredTracking = 0.;
	
	#[ORM\Column(type: 'float', name: 'radarTracking')]
	protected float $radarTracking = 0.;
	
	
	#[ORM\Column(type: 'float', name: 'firingEnergy')]
	protected float $firingEnergy = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingForce')]
	protected float $firingForce = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingFuel')]
	protected float $firingFuel = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingHeat')]
	protected float $firingHeat = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingHull')]
	protected float $firingHull = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingShields')]
	protected float $firingShields = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingIon')]
	protected float $firingIon = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingScramble')]
	protected float $firingScramble = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingSlowing')]
	protected float $firingSlowing = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingDisruption')]
	protected float $firingDisruption = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingDischarge')]
	protected float $firingDischarge = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingCorrosion')]
	protected float $firingCorrosion = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingLeak')]
	protected float $firingLeak = 0.;
	
	#[ORM\Column(type: 'float', name: 'firingBurn')]
	protected float $firingBurn = 0.;
	
	
	#[ORM\Column(type: 'float', name: 'relativeFiringEnergy')]
	protected float $relativeFiringEnergy = 0.;
	
	#[ORM\Column(type: 'float', name: 'relativeFiringHeat')]
	protected float $relativeFiringHeat = 0.;
	
	#[ORM\Column(type: 'float', name: 'relativeFiringFuel')]
	protected float $relativeFiringFuel = 0.;
	
	#[ORM\Column(type: 'float', name: 'relativeFiringHull')]
	protected float $relativeFiringHull = 0.;
	
	#[ORM\Column(type: 'float', name: 'relativeFiringShields')]
	protected float $relativeFiringShields = 0.;
	
	
	#[ORM\Column(type: 'float', name: 'splitRange')]
	protected float $splitRange = 0.;
	
	#[ORM\Column(type: 'float', name: 'triggerRadius')]
	protected float $triggerRadius = 0.;
	
	#[ORM\Column(type: 'float', name: 'blastRadius')]
	protected float $blastRadius = 0.;
	
	#[ORM\Column(type: 'float', name: 'safeRange')]
	protected float $safeRange = 0.;
	
	#[ORM\Column(type: 'float', name: 'piercing')]
	protected float $piercing = 0.0;
	
	
	#[ORM\Column(type: 'float', name: 'rangeOverride')]
	protected float $rangeOverride = 0.0;
	
	#[ORM\Column(type: 'float', name: 'velocityOverride')]
	protected float $velocityOverride = 0.0;
	
	
	#[ORM\Column(type: 'boolean', name: 'hasDamageDropoff')]
	protected bool $hasDamageDropoff = false;
	
	#[ORM\Column(type: 'float', name: 'damageDropoffModifier')]
	protected float $damageDropoffModifier = 0.0;
	
	
	// Cache the calculation of these $values, for faster access.
	#[ORM\Column(type: 'boolean', name: 'calculatedDamage')]
	protected bool $calculatedDamage = true;
	
	#[ORM\Column(type: 'boolean', name: 'doesDamage')]
	protected bool $doesDamage = false;
	
	#[ORM\Column(type: 'float', name: 'totalLifetime')]
	protected float $totalLifetime = -1.0;
	
	
	protected array $ammo = []; //pair<const Outfit*, int>
	protected array $damage = []; // float array
	protected array $damageDropoffRange = [0.0, 0.0]; // pair<float, float>
	// A pair representing the distribution type of this weapon's inaccuracy
	// and whether it is inverted
	protected array $inaccuracyDistribution;

    #[ORM\OneToMany(mappedBy: 'weapon', targetEntity: WeaponDamage::class, orphanRemoval: true, cascade: ['persist'])]
    protected Collection $weaponDamage;

    #[ORM\OneToMany(mappedBy: 'weapon', targetEntity: AmmoOutfit::class, orphanRemoval: true, cascade: ['persist'])]
    protected Collection $ammoOutfits; // pair<DistributionType, bool>
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->hardpointOffsetString = json_encode($this->hardpointOffset);
		foreach ($this->damage as $damageType => $damageAmount) {
			$WeaponDamage = new WeaponDamage();
			$WeaponDamage->setWeapon($this);
			$WeaponDamage->setType($damageType);
			$WeaponDamage->setDamage($damageAmount);
			$this->weaponDamage []= $WeaponDamage;
		}
		foreach ($this->ammo as $outfitName => $count) {
			$AmmoOutfit = new AmmoOutfit();
			$AmmoOutfit->setWeapon($this);
			$AmmoOutfit->setOutfit(GameData::Outfits()[$outfitName]);
			$AmmoOutfit->setAmount($count);
			$this->ammoOutfits []= $AmmoOutfit;
		}
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$hardpointArray = json_decode($this->hardpointOffsetString, true);
		$this->hardpointOffset = new Point($hardpointArray['x'], $hardpointArray['y']);
		$this->damage = [];
		foreach ($this->weaponDamage as $WeaponDamage) {
			$this->damage[$WeaponDamage->getType()] = $WeaponDamage->getDamage();
		}
		$this->ammo = [];
		foreach ($this->ammoOutfits as $AmmoOutfit) {
			$this->ammo[$AmmoOutfit->getOutfit()->getTrueName()] = $AmmoOutfit->getAmount();
		}
	}
	
	const DAMAGE_TYPES = 23;
	const HIT_FORCE = 0;
	// Normal damage types:
	const SHIELD_DAMAGE = 1;
	const HULL_DAMAGE = 2;
	const DISABLED_DAMAGE = 3;
	const MINABLE_DAMAGE = 4;
	const FUEL_DAMAGE = 5;
	const HEAT_DAMAGE = 6;
	const ENERGY_DAMAGE = 7;
	// Status effects:
	const ION_DAMAGE = 8;
	const WEAPON_JAMMING_DAMAGE = 9;
	const DISRUPTION_DAMAGE = 10;
	const SLOWING_DAMAGE = 11;
	const DISCHARGE_DAMAGE = 12;
	const CORROSION_DAMAGE = 13;
	const LEAK_DAMAGE = 14;
	const BURN_DAMAGE = 15;
	// Relative damage types:
	const RELATIVE_SHIELD_DAMAGE = 16;
	const RELATIVE_HULL_DAMAGE = 17;
	const RELATIVE_DISABLED_DAMAGE = 18;
	const RELATIVE_MINABLE_DAMAGE = 19;
	const RELATIVE_FUEL_DAMAGE = 20;
	const RELATIVE_HEAT_DAMAGE = 21;
	const RELATIVE_ENERGY_DAMAGE = 22;
	
	public function __construct() {
		$this->hardpointOffset = new Point(0.0, 0.0);
		$this->inaccuracyDistribution = [DistributionType::Triangular, false];
		$this->sprite = new Body();
		$this->hardpointSprite = new Body();
		for ($i = 0; $i < self::DAMAGE_TYPES; $i++) {
			$this->damage[$i] = 0.0;
		}
                                         $this->weaponDamage = new ArrayCollection();
                                         $this->ammoOutfits = new ArrayCollection();
	}
	
	// Accessors
	public function getId(): int {
		return $this->id;
	}
	public function getLifetime(): int {
		return $this->lifetime;
	}
	public function getRandomLifetime(): int {
		return $this->randomLifetime;
	}
	public function getReload(): float {
		return $this->reload;
	}
	public function getBurstReload(): float {
		return $this->burstReload;
	}
	public function getBurstCount(): int {
		return $this->burstCount;
	}
	public function getHoming(): int {
		return $this->homing;
	}
	
	public function getMissileStrength(): int {
		return $this->missileStrength;
	}
	public function getAntiMissile(): int {
		return $this->antiMissile;
	}
	public function getIsStreamed(): bool {
		return $this->isStreamed;
	}
	
	public function getVelocity(): float {
		return $this->velocity;
	}
	public function getRandomVelocity(): float {
		return $this->randomVelocity;
	}
	public function getWeightedVelocity(): float { 
		return ($this->velocityOverride > 0.) ? $this->velocityOverride : $this->velocity; 
	}
	public function getAcceleration(): float {
		return $this->acceleration;
	}
	public function getDrag(): float {
		return $this->drag;
	}
	public function getHardpointOffset(): Point { 
		return $this->hardpointOffset; 
	}
	
	public function getTurn(): float {
		return $this->turn;
	}
	public function getTurretTurn(): float {
		return $this->turretTurn;
	}
	
	public function getTracking(): float {
		return $this->tracking;
	}
	public function getOpticalTracking(): float {
		return $this->opticalTracking;
	}
	public function getInfraredTracking(): float {
		return $this->infraredTracking;
	}
	public function getRadarTracking(): float {
		return $this->radarTracking;
	}
	
	public function getFiringEnergy(): float {
		return $this->firingEnergy;
	}
	public function getFiringForce(): float {
		return $this->firingForce;
	}
	public function getFiringFuel(): float {
		return $this->firingFuel;
	}
	public function getFiringHeat(): float {
		return $this->firingHeat;
	}
	public function getFiringHull(): float {
		return $this->firingHull;
	}
	public function getFiringShields(): float {
		return $this->firingShields;
	}
	public function getFiringIon(): float {
		return $this->firingIon;
	}
	public function getFiringScramble(): float {
		return $this->firingScramble;
	}
	public function getFiringSlowing(): float {
		return $this->firingSlowing;
	}
	public function getFiringDisruption(): float {
		return $this->firingDisruption;
	}
	public function getFiringDischarge(): float {
		return $this->firingDischarge;
	}
	public function getFiringCorrosion(): float {
		return $this->firingCorrosion;
	}
	public function getFiringLeak(): float {
		return $this->firingLeak;
	}
	public function getFiringBurn(): float {
		return $this->firingBurn;
	}
	
	public function getRelativeFiringEnergy(): float {
		return $this->relativeFiringEnergy;
	}
	public function getRelativeFiringHeat(): float {
		return $this->relativeFiringHeat;
	}
	public function getRelativeFiringFuel(): float {
		return $this->relativeFiringFuel;
	}
	public function getRelativeFiringHull(): float {
		return $this->relativeFiringHull;
	}
	public function getRelativeFiringShields(): float {
		return $this->relativeFiringShields;
	}
	
	public function getPiercing(): float {
		return $this->piercing;
	}
	
	public function getSplitRange(): float {
		return $this->splitRange;
	}
	public function getTriggerRadius(): float {
		return $this->triggerRadius;
	}
	public function getBlastRadius(): float {
		return $this->blastRadius;
	}
	public function getSafeRange(): float {
		return $this->safeRange;
	}
	public function getHitForce(): float {
		return $this->totalDamage(self::HIT_FORCE);
	}
	
	public function getIsSafe(): bool {
		return $this->isSafe;
	}
	public function getIsPhasing(): bool {
		return $this->isPhasing;
	}
	public function getIsDamageScaled(): bool {
		return $this->isDamageScaled;
	}
	public function getIsGravitational(): bool {
		return $this->isGravitational;
	}
	
	public function getShieldDamage(): float {
		return $this->totalDamage(self::SHIELD_DAMAGE);
	}
	public function getHullDamage(): float {
		return $this->totalDamage(self::HULL_DAMAGE);
	}
	public function getDisabledDamage(): float {
		return $this->totalDamage(self::DISABLED_DAMAGE);
	}
	public function getMinableDamage(): float {
		return $this->totalDamage(self::MINABLE_DAMAGE);
	}
	public function getFuelDamage(): float {
		return $this->totalDamage(self::FUEL_DAMAGE);
	}
	public function getHeatDamage(): float {
		return $this->totalDamage(self::HEAT_DAMAGE);
	}
	public function getEnergyDamage(): float {
		return $this->totalDamage(self::ENERGY_DAMAGE);
	}
	
	public function getIonDamage(): float {
		return $this->totalDamage(self::ION_DAMAGE);
	}
	public function getScramblingDamage(): float {
		return $this->totalDamage(self::WEAPON_JAMMING_DAMAGE);
	}
	public function getDisruptionDamage(): float {
		return $this->totalDamage(self::DISRUPTION_DAMAGE);
	}
	public function getSlowingDamage(): float {
		return $this->totalDamage(self::SLOWING_DAMAGE);
	}
	public function getDischargeDamage(): float {
		return $this->totalDamage(self::DISCHARGE_DAMAGE);
	}
	public function getCorrosionDamage(): float {
		return $this->totalDamage(self::CORROSION_DAMAGE);
	}
	public function getLeakDamage(): float {
		return $this->totalDamage(self::LEAK_DAMAGE);
	}
	public function getBurnDamage(): float {
		return $this->totalDamage(self::BURN_DAMAGE);
	}
	
	public function getRelativeShieldDamage(): float {
		return $this->totalDamage(self::RELATIVE_SHIELD_DAMAGE);
	}
	public function getRelativeHullDamage(): float {
		return $this->totalDamage(self::RELATIVE_HULL_DAMAGE);
	}
	public function getRelativeDisabledDamage(): float {
		return $this->totalDamage(self::RELATIVE_DISABLED_DAMAGE);
	}
	public function getRelativeMinableDamage(): float {
		return $this->totalDamage(self::RELATIVE_MINABLE_DAMAGE);
	}
	public function getRelativeFuelDamage(): float {
		return $this->totalDamage(self::RELATIVE_FUEL_DAMAGE);
	}
	public function getRelativeHeatDamage(): float {
		return $this->totalDamage(self::RELATIVE_HEAT_DAMAGE);
	}
	public function getRelativeEnergyDamage(): float {
		return $this->totalDamage(self::RELATIVE_ENERGY_DAMAGE);
	}
	
	public function doesDamage(): bool { 
		if (!$this->calculatedDamage) {
			$this->totalDamage(0);
		}
		return $this->doesDamage; 
	}
	
	public function getHasDamageDropoff(): bool {
		return $this->hasDamageDropoff;
	}
	
	// Load from a "weapon" node, either in an outfit or in a ship (explosion).
	public function loadWeapon(DataNode $node): void {
		$this->isWeapon = true;
		$isClustered = false;
		$this->calculatedDamage = false;
		$this->doesDamage = false;
		$safeRangeOverriden = false;
		$disabledDamageSet = false;
		$minableDamageSet = false;
		$relativeDisabledDamageSet = false;
		$relativeMinableDamageSet = false;
	
		foreach ($node as $child) {
			$key = $child->getToken(0);
			if ($key == "stream") {
				$this->isStreamed = true;
			} else if ($key == "cluster") {
				$isClustered = true;
			} else if ($key == "safe") {
				$this->isSafe = true;
			} else if ($key == "phasing") {
				$this->isPhasing = true;
			} else if ($key == "no damage scaling") {
				$this->isDamageScaled = false;
			} else if ($key == "parallel") {
				$this->isParallel = true;
			} else if ($key == "gravitational") {
				$this->isGravitational = true;
			} else if ($child->size() < 2) {
				$child->printTrace("Skipping weapon attribute with no $value specified:");
			} else if ($key == "sprite") {
				$this->sprite->loadSprite($child);
			} else if ($key == "hardpoint sprite") {
				$this->hardpointSprite->loadSprite($child);
			} else if ($key == "sound") {
				// $this->sound = Audio::Get($child->getToken(1));
			} else if ($key == "ammo") {
				$usage = ($child->size() >= 3) ? $child->getValue(2) : 1;
				$AmmoOutfit = GameData::Outfits()[$child->getToken(1)];
				$this->ammo[$AmmoOutfit->getTrueName()] = max(0, $usage);
			} else if ($key == "icon") {
				$this->icon = SpriteSet::Get($child->getToken(1));
			} else if ($key == "fire effect") {
				$count = ($child->size() >= 3) ? $child->getValue(2) : 1;
				$effect = GameData::Effects()[$child->getToken(1)];
				if (!isset($this->fireEffects[$effect->getName()])) {
					$this->fireEffects[$effect->getName()] = ['effect'=>$effect, 'count'=>0];
				}
				$this->fireEffects[$effect->getName()]['count'] += $count;
			} else if ($key == "live effect") {
				$count = ($child->size() >= 3) ? $child->getValue(2) : 1;
				$effect = GameData::Effects()[$child->getToken(1)];
				if (!isset($this->liveEffects[$effect->getName()])) {
					$this->liveEffects[$effect->getName()] = ['effect'=>$effect, 'count'=>0];
				}
				$this->liveEffects[$effect->getName()]['count'] += $count;
			} else if ($key == "hit effect") {
				$count = ($child->size() >= 3) ? $child->getValue(2) : 1;
				$effect = GameData::Effects()[$child->getToken(1)];
				if (!isset($this->hitEffects[$effect->getName()])) {
					$this->hitEffects[$effect->getName()] = ['effect'=>$effect, 'count'=>0];
				}
				$this->hitEffects[$effect->getName()]['count'] += $count;
			} else if ($key == "target effect") {
				$count = ($child->size() >= 3) ? $child->getValue(2) : 1;
				$effect = GameData::Effects()[$child->getToken(1)];
				if (!isset($this->targetEffects[$effect->getName()])) {
					$this->targetEffects[$effect->getName()] = ['effect'=>$effect, 'count'=>0];
				}
				$this->targetEffects[$effect->getName()]['count'] += $count;
			} else if ($key == "die effect") {
				$count = ($child->size() >= 3) ? $child->getValue(2) : 1;
				$effect = GameData::Effects()[$child->getToken(1)];
				if (!isset($this->dieEffects[$effect->getName()])) {
					$this->dieEffects[$effect->getName()] = ['effect'=>$effect, 'count'=>0];
				}
				$this->dieEffects[$effect->getName()]['count'] += $count;
			} else if ($key == "submunition") {
				$submunition = new Submunition(GameData::Outfits()[$child->getToken(1)], ($child->size() >= 3) ? $child->getValue(2) : 1);
				$this->submunitions []= $submunition;
				foreach ($child as $grand) {
					if (($grand->size() >= 2) && ($grand->getToken(0) == "facing")) {
						$submunition->facing = new Angle($grand->getValue(1));
					} else if (($grand->size() >= 3) && ($grand->getToken(0) == "offset")) {
						$submunition->offset = new Point($grand->getValue(1), $grand->getValue(2));
					} else {
						$child->printTrace("Skipping unknown or incomplete submunition attribute:");
					}
				}
			} else if ($key == "inaccuracy") {
				$this->inaccuracy = $child->getValue(1);
				foreach ($child as $grand) {
					for ($j = 0; $j < $grand->size(); ++$j) {
						$token = $grand->getToken($j);
	
						if ($token == "inverted") {
							$this->inaccuracyDistribution[1] = true;
						} else if ($token == "triangular") {
							$this->inaccuracyDistribution[0] = DistributionType::Triangular;
						} else if ($token == "uniform") {
							$this->inaccuracyDistribution[0] = DistributionType::Uniform;
						} else if ($token == "narrow") {
							$this->inaccuracyDistribution[0] = DistributionType::Narrow;
						} else if ($token == "medium") {
							$this->inaccuracyDistribution[0] = DistributionType::Medium;
						} else if ($token == "wide") {
							$this->inaccuracyDistribution[0] = DistributionType::Wide;
						} else {
							$grand->printTrace("Skipping unknown distribution attribute:");
						}
					}
				}
			} else {
				$value = $child->getValue(1);
				if ($key == "lifetime") {
					$this->lifetime = max(0., $value);
				} else if ($key == "random lifetime") {
					$this->randomLifetime = max(0., $value);
				} else if ($key == "reload") {
					$this->reload = max(1., $value);
				} else if ($key == "burst reload") {
					$this->burstReload = max(1., $value);
				} else if ($key == "burst count") {
					$this->burstCount = max(1., $value);
				} else if ($key == "homing") {
					$this->homing = $value;
				} else if ($key == "missile strength") {
					$this->missileStrength = max(0., $value);
				} else if ($key == "anti-missile") {
					$this->antiMissile = max(0., $value);
				} else if ($key == "velocity") {
					$this->velocity = $value;
				} else if ($key == "random velocity") {
					$this->randomVelocity = $value;
				} else if ($key == "acceleration") {
					$this->acceleration = $value;
				} else if ($key == "drag") {
					$this->drag = $value;
				} else if ($key == "hardpoint offset") {
					// A single $value specifies the y-offset, while two $values
					// specifies an x & y offset, e.g. for an asymmetric hardpoint.
					// The point is specified in traditional XY orientation, but must
					// be inverted along the y-dimension for internal use.
					if ($child->size() == 2) {
						$this->hardpointOffset = new Point(0., -$value);
					} else if ($child->size() == 3) {
						$this->hardpointOffset = new Point($value, -$child->getValue(2));
					} else {
						$child->printTrace("Unsupported \"" . $key . "\" specification:");
					}
				} else if ($key == "turn") {
					$this->turn = $value;
				} else if ($key == "turret turn") {
					$this->turretTurn = $value;
				} else if ($key == "tracking") {
					$this->tracking = max(0., min(1., $value));
				} else if ($key == "optical tracking") {
					$this->opticalTracking = max(0., min(1., $value));
				} else if ($key == "infrared tracking") {
					$this->infraredTracking = max(0., min(1., $value));
				} else if ($key == "radar tracking") {
					$this->radarTracking = max(0., min(1., $value));
				} else if ($key == "firing energy") {
					$this->firingEnergy = $value;
				} else if ($key == "firing force") {
					$this->firingForce = $value;
				} else if ($key == "firing fuel") {
					$this->firingFuel = $value;
				} else if ($key == "firing heat") {
					$this->firingHeat = $value;
				} else if ($key == "firing hull") {
					$this->firingHull = $value;
				} else if ($key == "firing shields") {
					$this->firingShields = $value;
				} else if ($key == "firing ion") {
					$this->firingIon = $value;
				} else if ($key == "firing scramble") {
					$this->firingScramble = $value;
				} else if ($key == "firing slowing") {
					$this->firingSlowing = $value;
				} else if ($key == "firing disruption") {
					$this->firingDisruption = $value;
				} else if ($key == "firing discharge") {
					$this->firingDischarge = $value;
				} else if ($key == "firing corrosion") {
					$this->firingCorrosion = $value;
				} else if ($key == "firing leak") {
					$this->firingLeak = $value;
				} else if ($key == "firing burn") {
					$this->firingBurn = $value;
				} else if ($key == "relative firing energy") {
					$this->relativeFiringEnergy = $value;
				} else if ($key == "relative firing heat") {
					$this->relativeFiringHeat = $value;
				} else if ($key == "relative firing fuel") {
					$this->relativeFiringFuel = $value;
				} else if ($key == "relative firing hull") {
					$this->relativeFiringHull = $value;
				} else if ($key == "relative firing shields") {
					$this->relativeFiringShields = $value;
				} else if ($key == "split range") {
					$this->splitRange = max(0., $value);
				} else if ($key == "trigger radius") {
					$this->triggerRadius = max(0., $value);
				} else if ($key == "blast radius") {
					$this->blastRadius = max(0., $value);
				} else if ($key == "safe range override") {
					$this->safeRange = max(0., $value);
					$safeRangeOverriden = true;
				} else if ($key == "shield damage") {
					$this->damage[self::SHIELD_DAMAGE] = $value;
				} else if ($key == "hull damage") {
					$this->damage[self::HULL_DAMAGE] = $value;
				} else if ($key == "disabled damage") {
					$this->damage[self::DISABLED_DAMAGE] = $value;
					$disabledDamageSet = true;
				} else if ($key == "minable damage") {
					$this->damage[self::MINABLE_DAMAGE] = $value;
					$minableDamageSet = true;
				} else if ($key == "fuel damage") {
					$this->damage[self::FUEL_DAMAGE] = $value;
				} else if ($key == "heat damage") {
					$this->damage[self::HEAT_DAMAGE] = $value;
				} else if ($key == "energy damage") {
					$this->damage[self::ENERGY_DAMAGE] = $value;
				} else if ($key == "ion damage") {
					$this->damage[self::ION_DAMAGE] = $value;
				} else if ($key == "scrambling damage") {
					$this->damage[self::WEAPON_JAMMING_DAMAGE] = $value;
				} else if ($key == "disruption damage") {
					$this->damage[self::DISRUPTION_DAMAGE] = $value;
				} else if ($key == "slowing damage") {
					$this->damage[self::SLOWING_DAMAGE] = $value;
				} else if ($key == "discharge damage") {
					$this->damage[self::DISCHARGE_DAMAGE] = $value;
				} else if ($key == "corrosion damage") {
					$this->damage[self::CORROSION_DAMAGE] = $value;
				} else if ($key == "leak damage") {
					$this->damage[self::LEAK_DAMAGE] = $value;
				} else if ($key == "burn damage") {
					$this->damage[self::BURN_DAMAGE] = $value;
				} else if ($key == "relative shield damage") {
					$this->damage[self::RELATIVE_SHIELD_DAMAGE] = $value;
				} else if ($key == "relative hull damage") {
					$this->damage[self::RELATIVE_HULL_DAMAGE] = $value;
				} else if ($key == "relative disabled damage") {
					$this->damage[self::RELATIVE_DISABLED_DAMAGE] = $value;
					$relativeDisabledDamageSet = true;
				} else if ($key == "relative minable damage") {
					$this->damage[self::RELATIVE_MINABLE_DAMAGE] = $value;
					$relativeMinableDamageSet = true;
				} else if ($key == "relative fuel damage") {
					$this->damage[self::RELATIVE_FUEL_DAMAGE] = $value;
				} else if ($key == "relative heat damage") {
					$this->damage[self::RELATIVE_HEAT_DAMAGE] = $value;
				} else if ($key == "relative energy damage") {
					$this->damage[self::RELATIVE_ENERGY_DAMAGE] = $value;
				} else if ($key == "hit force") {
					$this->damage[self::HIT_FORCE] = $value;
				} else if ($key == "piercing") {
					$this->piercing = max(0., $value);
				} else if ($key == "range override") {
					$this->rangeOverride = max(0., $value);
				} else if ($key == "velocity override") {
					$this->velocityOverride = max(0., $value);
				} else if ($key == "damage dropoff") {
					$this->hasDamageDropoff = true;
					$maxDropoff = ($child->size() >= 3) ? $child->getValue(2) : 0.;
					$this->damageDropoffRange = [max(0., $value), $maxDropoff];
				} else if ($key == "dropoff modifier") {
					$this->damageDropoffModifier = max(0., $value);
				} else {
					$child->printTrace("Unrecognized weapon attribute: \"" . $key . "\":");
				}
			}
		}
		// Disabled damage defaults to hull damage instead of 0.
		if (!$disabledDamageSet) {
			$this->damage[self::DISABLED_DAMAGE] = $this->damage[self::HULL_DAMAGE];
		}
		if (!$relativeDisabledDamageSet) {
			$this->damage[self::RELATIVE_DISABLED_DAMAGE] = $this->damage[self::RELATIVE_HULL_DAMAGE];
		}
		// Minable damage defaults to hull damage instead of 0.
		if (!$minableDamageSet) {
			$this->damage[self::MINABLE_DAMAGE] = $this->damage[self::HULL_DAMAGE];
		}
		if (!$relativeMinableDamageSet) {
			$this->damage[self::RELATIVE_MINABLE_DAMAGE] = $this->damage[self::RELATIVE_HULL_DAMAGE];
		}
	
		// Sanity checks:
		if ($this->burstReload > $this->reload) {
			$this->burstReload = $this->reload;
		}
		if ($this->damageDropoffRange[0] > $this->damageDropoffRange[1]) {
			$this->damageDropoffRange[1] = $this->getRange();
		}
	
		// Weapons of the same type will alternate firing (streaming) rather than
		// firing all at once (clustering) if the weapon is not an anti-missile and
		// is not vulnerable to anti-missile, or has the "stream" attribute.
		$this->isStreamed |= !($this->getMissileStrength() || $this->getAntiMissile());
		$this->isStreamed &= !$isClustered;
	
		// Support legacy missiles with no tracking type defined:
		if ($this->homing && !$this->tracking && !$this->opticalTracking && !$this->infraredTracking && !$this->radarTracking) {
			$this->tracking = 1.;
			$node->printTrace("Warning: Deprecated use of \"homing\" without use of \"[optical|infrared|radar] tracking.\"");
		}
	
		// Convert the "live effect" counts from occurrences per projectile lifetime
		// into chance of occurring per frame.
		if ($this->lifetime <= 0) {
			$this->liveEffects = [];
		}
		foreach ($this->liveEffects as $effectName => $effectData) {
			if (!$effectData['count'] == 0) {
				//it = liveEffects.erase(it);
				// I think this is what this does?
				unset($this->liveEffects[$effectName]);
			} else {
				$effectData['count'] = max(1, $this->lifetime / $effectData['count']);
			}
		}
	
		// Only when the weapon is not safe and has a blast radius is safeRange needed,
		// except if it is already overridden.
		if (!$this->isSafe && $this->blastRadius > 0 && !$safeRangeOverriden) {
			$this->safeRange = ($this->blastRadius + $this->triggerRadius);
		}
	}
	
	public function isWeapon(): bool {
		return $this->isWeapon;
	}
	
	// Get assets used by this weapon.
	public function getWeaponSprite(): Body {
		return $this->sprite;
	}
	
	public function getHardpointSprite(): Body {
		return $this->hardpointSprite;
	}
	
	public function getWeaponSound(): Sound {
		return $this->sound;
	}
	
	public function getAmmo(): Outfit {
		return $this->ammo[0];
	}
	
	public function getAmmoUsage(): int {
		return $this->ammo[1];
	}
	
	public function isParallel(): bool {
		return $this->isParallel;
	}
	
	public function getIcon(): Sprite {
		return $this->icon;
	}
	
	// Effects to be created at the start or end of the weapon's lifetime.
	public function getFireEffects(): array {
		return $this->fireEffects;
	}
	
	public function getLiveEffects(): array {
		return $this->liveEffects;
	}
	
	public function getHitEffects(): array {
		return $this->hitEffects;
	}
	
	public function getTargetEffects(): array {
		return $this->targetEffects;
	}
	
	public function getDieEffects(): array {
		return $this->dieEffects;
	}
	
	public function getSubmunitions(): array {
		return $this->submunitions;
	}
	
	public function getTotalLifetime(): float {
		if ($this->rangeOverride) {
			return $this->rangeOverride / $this->getWeightedVelocity();
		}
		if ($this->totalLifetime < 0.) {
			$this->totalLifetime = 0.;
			foreach ($this->submunitions as $submunition) {
				$this->totalLifetime = max($this->totalLifetime, $submunition->getWeapon()->getTotalLifetime());
			}
			$this->totalLifetime += $this->lifetime;
		}
		return $this->totalLifetime;
	}
	
	public function getRange(): float {
		return ($this->rangeOverride > 0) ? $this->rangeOverride : $this->getWeightedVelocity() * $this->getTotalLifetime();
	}
	
	// Calculate the fraction of full damage that this weapon deals given the
	// distance that the projectile traveled if it has a damage dropoff range.
	public function getDamageDropoff(float $distance): float {
		$minDropoff = $this->damageDropoffRange[0];
		$maxDropoff = $this->damageDropoffRange[1];
	
		if ($distance <= $minDropoff) {
			return 1.;
		}
		if ($distance >= $maxDropoff) {
			return $this->damageDropoffModifier;
		}
		// Damage modification is linear between the min and max dropoff points.
		$slope = (1 - $this->damageDropoffModifier) / ($minDropoff - $maxDropoff);
		return $slope * ($distance - $minDropoff) + 1;
	}
	
	// Legacy support: allow turret outfits with no turn rate to specify a
	// default turnrate.
	public function setTurretTurn(float $rate) {
		$this->turretTurn = $rate;
	}
	
	public function totalDamage(int $index): float {
		if (!$this->calculatedDamage) {
			$this->calculatedDamage = true;
			for ($i = 0; $i < self::DAMAGE_TYPES; ++$i) {
				foreach ($this->submunitions as $submunition) {
					$this->damage[$i] += $submunition->weapon->totalDamage($i) * $submunition->count;
				}
				$this->doesDamage |= ($this->damage[$i] > 0.);
			}
		}
		return $this->damage[$index];
	}
	
	public function getInaccuracyDistribution(): array {
		return $this->inaccuracyDistribution;
	}
	
	public function getInaccuracy(): float {
		return $this->inaccuracy;
	}
	
	public function copyTo(Weapon $Copy): void {
		$Copy->sprite = $this->sprite;
		$Copy->hardpointSprite = $this->hardpointSprite;
		$Copy->sound = $this->sound;
		$Copy->icon = $this->icon;
		$Copy->isWeapon = $this->isWeapon;
		$Copy->isStreamed = $this->isStreamed;
		$Copy->isSafe = $this->isSafe;
		$Copy->isPhasing = $this->isPhasing;
		$Copy->isDamageScaled = $this->isDamageScaled;
		$Copy->isGravitational = $this->isGravitational;
		$Copy->isParallel = $this->isParallel;
		$Copy->lifetime = $this->lifetime;
		$Copy->randomLifetime = $this->randomLifetime;
		$Copy->reload = $this->reload;
		$Copy->burstReload = $this->burstReload;
		$Copy->burstCount = $this->burstCount;
		$Copy->homing = $this->homing;
		$Copy->missileStrength = $this->missileStrength;
		$Copy->antiMissile = $this->antiMissile;
		$Copy->velocity = $this->velocity;
		$Copy->randomVelocity = $this->randomVelocity;
		$Copy->acceleration = $this->acceleration;
		$Copy->drag = $this->drag;
		$Copy->hardpointOffset = $this->hardpointOffset;
		$Copy->turn = $this->turn;
		$Copy->inaccuracy = $this->inaccuracy;
		$Copy->turretTurn = $this->turretTurn;
		$Copy->tracking = $this->tracking;
		$Copy->opticalTracking = $this->opticalTracking;
		$Copy->infraredTracking = $this->infraredTracking;
		$Copy->radarTracking = $this->radarTracking;
		$Copy->firingEnergy = $this->firingEnergy;
		$Copy->firingForce = $this->firingForce;
		$Copy->firingFuel = $this->firingFuel;
		$Copy->firingHeat = $this->firingHeat;
		$Copy->firingHull = $this->firingHull;
		$Copy->firingShields = $this->firingShields;
		$Copy->firingIon = $this->firingIon;
		$Copy->firingScramble = $this->firingScramble;
		$Copy->firingSlowing = $this->firingSlowing;
		$Copy->firingDisruption = $this->firingDisruption;
		$Copy->firingDischarge = $this->firingDischarge;
		$Copy->firingCorrosion = $this->firingCorrosion;
		$Copy->firingLeak = $this->firingLeak;
		$Copy->firingBurn = $this->firingBurn;
		$Copy->relativeFiringEnergy = $this->relativeFiringEnergy;
		$Copy->relativeFiringHeat = $this->relativeFiringHeat;
		$Copy->relativeFiringFuel = $this->relativeFiringFuel;
		$Copy->relativeFiringHull = $this->relativeFiringHull;
		$Copy->relativeFiringShields = $this->relativeFiringShields;
		$Copy->splitRange = $this->splitRange;
		$Copy->triggerRadius = $this->triggerRadius;
		$Copy->blastRadius = $this->blastRadius;
		$Copy->safeRange = $this->safeRange;
		$Copy->piercing = $this->piercing;
		$Copy->rangeOverride = $this->rangeOverride;
		$Copy->velocityOverride = $this->velocityOverride;
		$Copy->hasDamageDropoff = $this->hasDamageDropoff;
		$Copy->damageDropoffModifier = $this->damageDropoffModifier;
		$Copy->calculatedDamage = $this->calculatedDamage;
		$Copy->doesDamage = $this->doesDamage;
		$Copy->totalLifetime = $this->totalLifetime;
		$Copy->damageDropoffRange = $this->damageDropoffRange;
		
		$Copy->damage = [];
		foreach ($this->weaponDamage as $WeaponDamage) {
			$Copy->damage[$WeaponDamage->getType()] = $WeaponDamage->getDamage();
		}
		$Copy->ammo = [];
		foreach ($this->ammoOutfits as $AmmoOutfit) {
			$Copy->ammo[$AmmoOutfit->getOutfit()->getTrueName()] = $AmmoOutfit->getAmount();
		}
	}
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['sprite'] = $this->sprite->toJSON(true);
		$jsonArray['hardpointSprite'] = $this->hardpointSprite->toJSON(true);
		$jsonArray['soundId'] = $this->sound?->getId();
		$jsonArray['iconId'] = $this->icon?->getId();
		$jsonArray['isWeapon'] = $this->isWeapon;
		$jsonArray['isStreamed'] = $this->isStreamed;
		$jsonArray['isSafe'] = $this->isSafe;
		$jsonArray['isPhasing'] = $this->isPhasing;
		$jsonArray['isDamageScaled'] = $this->isDamageScaled;
		$jsonArray['isGravitational'] = $this->isGravitational;
		$jsonArray['isParallel'] = $this->isParallel;
		$jsonArray['lifetime'] = $this->lifetime;
		$jsonArray['randomLifetime'] = $this->randomLifetime;
		$jsonArray['reload'] = $this->reload;
		$jsonArray['burstReload'] = $this->burstReload;
		$jsonArray['burstCount'] = $this->burstCount;
		$jsonArray['homing'] = $this->homing;
		$jsonArray['missileStrength'] = $this->missileStrength;
		$jsonArray['antiMissile'] = $this->antiMissile;
		$jsonArray['velocity'] = $this->velocity;
		$jsonArray['randomVelocity'] = $this->randomVelocity;
		$jsonArray['acceleration'] = $this->acceleration;
		$jsonArray['drag'] = $this->drag;
		$jsonArray['hardpointOffset'] = ['x'=>$this->hardpointOffset->X(), 'y'=>$this->hardpointOffset->Y()];
		$jsonArray['turn'] = $this->turn;
		$jsonArray['inaccuracy'] = $this->inaccuracy;
		$jsonArray['turretTurn'] = $this->turretTurn;
		$jsonArray['tracking'] = $this->tracking;
		$jsonArray['opticalTracking'] = $this->opticalTracking;
		$jsonArray['infraredTracking'] = $this->infraredTracking;
		$jsonArray['radarTracking'] = $this->radarTracking;
		$jsonArray['firingEnergy'] = $this->firingEnergy;
		$jsonArray['firingForce'] = $this->firingForce;
		$jsonArray['firingFuel'] = $this->firingFuel;
		$jsonArray['firingHeat'] = $this->firingHeat;
		$jsonArray['firingHull'] = $this->firingHull;
		$jsonArray['firingShields'] = $this->firingShields;
		$jsonArray['firingIon'] = $this->firingIon;
		$jsonArray['firingScramble'] = $this->firingScramble;
		$jsonArray['firingSlowing'] = $this->firingSlowing;
		$jsonArray['firingDisruption'] = $this->firingDisruption;
		$jsonArray['firingDischarge'] = $this->firingDischarge;
		$jsonArray['firingCorrosion'] = $this->firingCorrosion;
		$jsonArray['firingLeak'] = $this->firingLeak;
		$jsonArray['firingBurn'] = $this->firingBurn;
		$jsonArray['relativeFiringEnergy'] = $this->relativeFiringEnergy;
		$jsonArray['relativeFiringHeat'] = $this->relativeFiringHeat;
		$jsonArray['relativeFiringFuel'] = $this->relativeFiringFuel;
		$jsonArray['relativeFiringHull'] = $this->relativeFiringHull;
		$jsonArray['relativeFiringShields'] = $this->relativeFiringShields;
		$jsonArray['splitRange'] = $this->splitRange;
		$jsonArray['triggerRadius'] = $this->triggerRadius;
		$jsonArray['blastRadius'] = $this->blastRadius;
		$jsonArray['safeRange'] = $this->safeRange;
		$jsonArray['piercing'] = $this->piercing;
		$jsonArray['rangeOverride'] = $this->rangeOverride;
		$jsonArray['velocityOverride'] = $this->velocityOverride;
		$jsonArray['hasDamageDropoff'] = $this->hasDamageDropoff;
		$jsonArray['damageDropoffModifier'] = $this->damageDropoffModifier;
		$jsonArray['calculatedDamage'] = $this->calculatedDamage;
		$jsonArray['doesDamage'] = $this->doesDamage;
		$jsonArray['totalLifetime'] = $this->totalLifetime;
		$jsonArray['damageDropoffRange'] = $this->damageDropoffRange;
		
		$jsonArray['damage'] = [];
		foreach ($this->weaponDamage as $WeaponDamage) {
			$jsonArray['damage'][$WeaponDamage->getType()] = $WeaponDamage->getDamage();
		}
		$jsonArray['ammo'] = [];
		foreach ($this->ammoOutfits as $AmmoOutfit) {
			$jsonArray['ammo'][$AmmoOutfit->getOutfit()->getTrueName()] = $AmmoOutfit->getAmount();
		}
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}

    /**
     * @return Collection<int, WeaponDamage>
     */
    public function getWeaponDamage(): Collection
    {
        return $this->weaponDamage;
    }

    public function addWeaponDamage(WeaponDamage $weaponDamage): static
    {
        if (!$this->weaponDamage->contains($weaponDamage)) {
            $this->weaponDamage->add($weaponDamage);
            $weaponDamage->setWeapon($this);
        }

        return $this;
    }

    public function removeWeaponDamage(WeaponDamage $weaponDamage): static
    {
        if ($this->weaponDamage->removeElement($weaponDamage)) {
            // set the owning side to null (unless already changed)
            if ($weaponDamage->getWeapon() === $this) {
                $weaponDamage->setWeapon(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AmmoOutfit>
     */
    public function getAmmoOutfits(): Collection
    {
        return $this->ammoOutfits;
    }

    public function addAmmoOutfit(AmmoOutfit $ammoOutfit): static
    {
        if (!$this->ammoOutfits->contains($ammoOutfit)) {
            $this->ammoOutfits->add($ammoOutfit);
            $ammoOutfit->setWeapon($this);
        }

        return $this;
    }

    public function removeAmmoOutfit(AmmoOutfit $ammoOutfit): static
    {
        if ($this->ammoOutfits->removeElement($ammoOutfit)) {
            // set the owning side to null (unless already changed)
            if ($ammoOutfit->getWeapon() === $this) {
                $ammoOutfit->setWeapon(null);
            }
        }

        return $this;
    }
}


class Submunition {
	public function __construct(?Weapon $weapon = null, int $count = 0) {
		$this->weapon = $weapon;
		$this->count = $count;
		
		$this->facing = new Angle();
		$this->offset = new Point();
	}

	public ?Weapon $weapon = null;
	public int $count = 0;
	// The angular offset from the source projectile, relative to its current facing.
	public Angle $facing;
	// The base offset from the source projectile's position, relative to its current facing.
	public Point $offset;
};