<?php

namespace App\Entity\Sky;

class Weapon {
	public Sprite $sprite = '';
	
	public string $hardpointSprite = '';
	public Point $hardpointOffset = 0;
	public string $sound = '';
	public string $ammo = '';
	public int $ammoCount = 1;
	public string $icon = '';
	public ?Effect $fireEffect = null;
	public int $fireEffectEffectCount = 1;
	public ?Effect $liveEffect = null;
	public int $liveEffectEffectCount = 1;
	public ?Effect $hitEffect = null;
	public int $hitEffectEffectCount = 1;
	public ?Effect $targetEffect = null;
	public int $targetEffectEffectCount = 1;
	public ?Effect $dieEffect = null;
	public int $dieEffectEffectCount = 1;
	public array $submunitions = array();
	
	public bool $stream = false;
	public bool $cluster = false;
	public bool $safe = false;
	public bool $phasing = false;
	public bool $noDamageScaling = false;
	public bool $gravitational = false;
	public bool $parallel = false;
	
	public float $lifetime = 0.0;
	public float $randomLifetime = 0.0;
	public float $velocity = 0.0;
	public float $randomVelocity = 0.0;
	public float $acceleration = 0.0;
	public float $drag = 0.0;
	public float $turn = 0.0;
	public float $inaccuracy = 0.0;
	// TODO: inaccuracy subnodes: triangular, uniform, narrow|medium|wide, inverted
	public float $turretTurn = 0.0;
	public float $rangeOverride = 0.0;
	public float $velocityOverride = 0.0;
	
	public float $firingEnergy = 0.0;
	public float $firingHeat = 0.0;
	public float $firingShields = 0.0;
	public float $firingHull = 0.0;
	public float $firingFuel = 0.0;
	public float $firingDischarge = 0.0;
	public float $firingCorrosion = 0.0;
	public float $firingIon = 0.0;
	public float $firingScramble = 0.0;
	public float $firingLeakage = 0.0;
	public float $firingBurn = 0.0;
	public float $firingSlowing = 0.0;
	public float $firingDisruption = 0.0;
	
	public float $relativeFiringEnergy = 0.0;
	public float $relativeFiringHeat = 0.0;
	public float $relativeFiringShields = 0.0;
	public float $relativeFiringHull = 0.0;
	public float $relativeFiringFuel = 0.0;
	
	public int $reload = 0;
	public int $burstCount = 0;
	public int $burstReload = 0;
	public int $homing = 0; // 0: none, 1: facing only, 2: dumb, 3: turn back, 4: intercept
	
	public float $infraredTracking = 0.0;
	public float $opticalTracking = 0.0;
	public float $radarTracking = 0.0;
	public float $tracking = 0.0;
	
	public float $missileStrength = 0.0;
	public float $antiMissile = 0.0;
	
	public float $splitRange = 0.0;
	public float $triggerRadius = 0.0;
	public float $blastRadius = 0.0;
	public float $hitForce = 0.0;
	public float $piercing = 0.0;
	public float $damageDropoff = 0.0;
	public float $dropoffModifier = 0.0;
	
	public float $shieldDamage = 0.0;
	public float $hullDamage = 0.0;
	public float $disabledDamage = 0.0;
	public float $minableDamage = 0.0;
	public float $heatDamage = 0.0;
	public float $fuelDamage = 0.0;
	public float $energyDamage = 0.0;
	
	public float $relativeShieldDamage = 0.0;
	public float $relativeHullDamage = 0.0;
	public float $relativeDisabledDamage = 0.0;
	public float $relativeMinableDamage = 0.0;
	public float $relativeHeatDamage = 0.0;
	public float $relativeFuelDamage = 0.0;
	public float $relativeEnergyDamage = 0.0;
	
	public float $ionDamage = 0.0;
	public float $scramblingDamage = 0.0;
	public float $disruptionDamage = 0.0;
	public float $slowingDamage = 0.0;
	public float $dischargeDamage = 0.0;
	public float $corrosionDamage = 0.0;
	public float $leakDamage = 0.0;
	public float $burnDamage = 0.0;
	
}