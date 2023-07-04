<?php

namespace App\Entity\Sky;

class Outfit {
	public string $name = '';
	public string $displayName = '';
	public string $category = '';
	public string $flareSprite = '';
	public string $reverseFlareSprite = '';
	public string $steeringFlareSprite = '';
	public string $flareSound = '';
	public string $reverseFlareSound = '';
	public string $steeringFlareSound = '';
	public string $afterburnerEffect = '';
	public string $thumbnail = '';
	public string $flotsamSprite = '';
	public float $flotsamChance = 0.0;
	public array $licenses = array();
	public string $jumpEffect = '';
	public string $jumpSound = ''; // my ship
	public string $jumpInSound = ''; // other ships arriving
	public string $jumpOutSound = ''; // other ships leaving
	public string $hyperdriveSound = ''; // my ship
	public string $hyperdriveInSound = ''; // other ships arriving
	public string $hyperdriveOutSound = ''; // other ships leaving
	public Description $description;
	
	public int $cost = 0;
	public int $mass = 0;
	public int $outfitSpace = 0;
	public int $engineCapacity = 0;
	public int $weaponCapacity = 0;
	public int $cargoSpace = 0;
	public bool $atrocity = false;
	public int $illegal = 0; // fine amount
	public int $shields = 0; // discouraged, balance issues
	public float $shieldGeneration = 0.0;
	public float $shieldEnergy = 0.0;
	public float $shieldHeat = 0.0;
	public float $shieldFuel = 0.0;
	public float $shieldDelay = 0.0;
	public float $depletedShieldDelay = 0.0;
	public float $highShieldPermeability = 0.0;
	public float $lowShieldPermeaibility = 0.0;
	public int $hull = 0; // discouraged, balance issues
	public float $hullRepairRate = 0.0;
	public float $hullEnergy = 0.0;
	public float $hullHeat = 0.0;
	public float $hullFuel = 0.0;
	public float $repairDelay = 0.0;
	public float $disabledRepairDelay = 0.0;
	
	public float $absoluteThreshold = 0.0; // for disabling; should generally be used only on a ship
	public float $thresholdPercentage = 0.0;
	public float $hullThreshold = 0.0;
	
	public float $shieldGenerationMultiplier = 0.0;
	public float $shieldEnergyMultiplier = 0.0;
	public float $shieldHeatMultiplier = 0.0;
	public float $shieldFuelMultiplier = 0.0;
	public float $hullRepairMultiplier = 0.0;
	public float $hullEnergyMultiplier = 0.0;
	public float $hullHeatMultiplier = 0.0;
	public float $hullFuelMultiplier = 0.0;
	
	public float $energyCapacity = 0.0;
	public float $energyGeneration = 0.0;
	public float $energyConsumption = 0.0;
	public float $heatGeneration = 0.0;
	
	public float $ramscoop = 0.0;
	public float $solarCollection = 0.0;
	public float $solarHeat = 0.0;
	
	public float $fuelCapacity = 0.0;
	public float $fuelConsumption = 0.0;
	public float $fuelEnergy = 0.0;
	public float $fuelHeat = 0.0;
	public float $fuelGeneration = 0.0;
	
	public float $thrust = 0.0;
	public float $thrustingEnergy = 0.0;
	public float $thrustingHeat = 0.0;
	public float $thrustingShields = 0.0;
	public float $thrustingHull = 0.0;
	public float $thrustingFuel = 0.0;
	public float $thrustingDischarge = 0.0;
	public float $thrustingCorrosion = 0.0;
	public float $thrustingIon = 0.0;
	public float $thrustingScramble = 0.0;
	public float $thrustingLeakage = 0.0;
	public float $thrustingBurn = 0.0;
	public float $thrustingSlowing = 0.0;
	public float $thrustingDisruption = 0.0;
	
	public float $turn = 0.0;
	public float $turningEnergy = 0.0;
	public float $turningHeat = 0.0;
	public float $turningShields = 0.0;
	public float $turningHull = 0.0;
	public float $turningFuel = 0.0;
	public float $turningDischarge = 0.0;
	public float $turningCorrosion = 0.0;
	public float $turningIon = 0.0;
	public float $turningScramble = 0.0;
	public float $turningLeakage = 0.0;
	public float $turningBurn = 0.0;
	public float $turningSlowing = 0.0;
	public float $turningDisruption = 0.0;
	
	public float $reverseThrust = 0.0;
	public float $reverseThrustingEnergy = 0.0;
	public float $reverseThrustingHeat = 0.0;
	public float $reverseThrustingShields = 0.0;
	public float $reverseThrustingHull = 0.0;
	public float $reverseThrustingFuel = 0.0;
	public float $reverseThrustingDischarge = 0.0;
	public float $reverseThrustingCorrosion = 0.0;
	public float $reverseThrustingIon = 0.0;
	public float $reverseThrustingScramble = 0.0;
	public float $reverseThrustingLeakage = 0.0;
	public float $reverseThrustingBurn = 0.0;
	public float $reverseThrustingSlowing = 0.0;
	public float $reverseThrustingDisruption = 0.0;
	
	public float $afterburner = 0.0;
	public float $afterburnerEnergy = 0.0;
	public float $afterburnerHeat = 0.0;
	public float $afterburnerShields = 0.0;
	public float $afterburnerHull = 0.0;
	public float $afterburnerFuel = 0.0;
	public float $afterburnerDischarge = 0.0;
	public float $afterburnerCorrosion = 0.0;
	public float $afterburnerIon = 0.0;
	public float $afterburnerScramble = 0.0;
	public float $afterburnerLeakage = 0.0;
	public float $afterburnerBurn = 0.0;
	public float $afterburnerSlowing = 0.0;
	public float $afterburnerDisruption = 0.0;
	
	public float $cooling = 0.0;
	public float $activeCooling = 0.0;
	public float $coolingEnergy = 0.0;
	public float $heatDissipation = 0.0; // Discouraged
	public float $heatCapacity = 0.0;
	public float $overheatDamageRate = 0.0;
	public float $overheatDamageThreshold = 0.0;
	public float $coolingInefficiency = 0.0;
	
	public float $atmosphereScan = 0.0;
	public float $cargoScanPower = 0.0;
	public float $cargoScanEfficiency = 0.0;
	public float $outfitScanPower = 0.0;
	public float $outfitScanEfficiency = 0.0;
	public float $scanInterference = 0.0;
	public float $scanBrightness = 0.0;
	public float $scanConcealment = 0.0;
	public bool $inscrutable = false;
	public float $asteroidScanPower = 0.0;
	public float $tacticalScanPower = 0.0;
	
	public float $captureAttack = 0.0;
	public float $captureDefense = 0.0;
	public bool $unplunderable = false;
	
	public bool $hyperdrive = false;
	public float $scramDrive = 0.0;
	public bool $jumpDrive = false;
	public float $jumpSpeed = 0.0;
	public float $jumpFuel = 0.0;
	public float $jumpMassCost = 0.0;
	public float $jumpBaseMass = 0.0;
	public float $jumpRange = 0.0;
	
	public int $bunks = 0;
	public int $requiredCrew = 0;
	public int $crewEquivalent = 0;
	
	public int $gunPorts = 0;
	public int $turretMounts = 0;
	
	public float $cloak = 0.0;
	public float $cloakingEnergy = 0.0;
	public float $cloakingFuel = 0.0;
	public float $cloakingHeat = 0.0;
	
	public float $disruptionResistance = 0.0;
	public float $disruptionResistanceEnergy = 0.0;
	public float $disruptionResistanceHeat = 0.0;
	public float $disruptionResistanceFuel = 0.0;
	public float $ionResistance = 0.0;
	public float $ionResistanceEnergy = 0.0;
	public float $ionResistanceHeat = 0.0;
	public float $ionResistanceFuel = 0.0;
	public float $scrambleResistance = 0.0;
	public float $scrambleResistanceEnergy = 0.0;
	public float $scrambleResistanceHeat = 0.0;
	public float $scrambleResistanceFuel = 0.0;
	public float $slowingResistance = 0.0;
	public float $slowingResistanceEnergy = 0.0;
	public float $slowingResistanceHeat = 0.0;
	public float $slowingResistanceFuel = 0.0;
	public float $dischargeResistance = 0.0;
	public float $dischargeResistanceEnergy = 0.0;
	public float $dischargeResistanceHeat = 0.0;
	public float $dischargeResistanceFuel = 0.0;
	public float $corrosionResistance = 0.0;
	public float $corrosionResistanceEnergy = 0.0;
	public float $corrosionResistanceHeat = 0.0;
	public float $corrosionResistanceFuel = 0.0;
	public float $leakResistance = 0.0;
	public float $leakResistanceEnergy = 0.0;
	public float $leakResistanceHeat = 0.0;
	public float $leakResistanceFuel = 0.0;
	public float $burnResistance = 0.0;
	public float $burnResistanceEnergy = 0.0;
	public float $burnResistanceHeat = 0.0;
	public float $burnResistanceFuel = 0.0;
	public float $piercingResistance = 0.0;
	
	public float $disruptionProtection = 0.0;
	public float $energyProtection = 0.0;
	public float $forceProtection = 0.0;
	public float $fuelProtection = 0.0;
	public float $heatProtection = 0.0;
	public float $hullProtection = 0.0;
	public float $ionProtection = 0.0;
	public float $scrambleProtection = 0.0;
	public float $piercingProtection = 0.0;
	public float $shieldProtection = 0.0;
	public float $slowingProtection = 0.0;
	public float $dischargeProtection = 0.0;
	public float $corrosionProtection = 0.0;
	public float $leakProtection = 0.0;
	public float $burnProtection = 0.0;
	
	public int $maintenanceCosts = 0;
	public int $operatingCosts = 0;
	public int $income = 0;
	public int $operatingIncome = 0;
	
	public string $ammo = '';
	public float $drag = 0.0; // Discouraged
	public float $dragReduction = 0.0;
	public float $inertiaReduction = 0.0;
	public ?int $installable = null; // Special: false is -1
	public ?int $minable = null; // true is 1
	public int $map = 0;
	
	public float $radarJamming = 0.0;
	public float $opticalJamming = 0.0;
	public float $selfDestruct = 0.0;
	public float $landingSpeed = 0.0;
	
	public Weapon $weapon;
	
	public function __construct($name, $category) {
		$this->name = $name;
		$this->category = $category;
		if ($this->category == 'Ammunition') {
			$this->flotsamChance = 0.05;
		}
	}