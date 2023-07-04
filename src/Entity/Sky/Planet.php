<?php

namespace App\Entity\Sky;

class Planet {
	public string $name;
	public array $attributes = array();
	public array $requiredAttributes = array();
	public string $landscape;
	public string $music = '';
	public Description $description;
	public Description $spaceport;
	public array $shipyards = array();
	public array $outfitters = array();
	public int $requiredReputation;
	public float $bribe;
	public float $security;
	public string $wormhole = '';
	public int $tributeAmount;
	public int $tributeThreshold;
	public array $tributeFleets = array();
	public array $tributeFleetCounts = array();
	
	public function __construct() {
		$this->description = new Description();
		$this->spaceport = new Description();
	}
}