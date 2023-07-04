<?php

namespace App\Entity\Sky;

class Government {
	public string $name;
	public string $displayName;
	public int $swizzle;
	public array|string $color;
	public int $playerStartRep;
	public ?int $playerMinRep = null;
	public ?int $playerMaxRep = null;
	public float $penaltyAssist = -0.1;
	public float $penaltyDisable = 0.5;
	public float $penaltyBoard = 0.3;
	public float $penaltyCapture = 1;
	public float $penaltyDestroy = 1;
	public float $penaltyAtrocity = 10;
	public float $penaltyScan = 0;
	public float $penaltyProvoke = 0;
	public array $foreignPenalties = array();
	public array $customPenalties = array();
	public bool $provokeOnScan = false;
	public float $crewAttack = 1.0;
	public float $crewDefense = 2.0;
	public array $attitudes = array();
	public float $bribe;
	public float $fine;
	public string $deathSentenceName;
	public bool $sendUntranslatedHails = false;
	public string $friendlyHailName;
	public string $friendlyDisabledHailName;
	public string $hostileHailName;
	public string $hostileDisabledHailName;
	public ?string $language = null;
	public string $raidName;
	public array $enforcementZones = array();
}