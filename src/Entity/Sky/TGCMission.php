<?php

namespace App\Entity\Sky;

class TGCMission {
	public string $name = '';
	public string $displayName = '';
	public Description $description;
	public ?int $repeat = null;
	public bool $stealth = false;
	public bool $invisible = false;
	public bool $infiltrating = false;
	public bool $priority = false; // Mutually exclusive with minor
	public bool $minor = false; // Mutually exclusive with priority
	public bool $overrideCapture = false;
	public string $offeredAt; // job | landing | assisting | boarding | shipyard | outfitter
	public ?int $apparentPayment = null;
	public Description $blocked;
	public mixed $clearance;
	public string $sourcePlanet;
	public LocationFilter $sourceLocations;
	public string $destinationPlanet;
	public LocationFilter $destinationLocations;
	public ?array $on = null; // array('offer','complete','accept','decline','defer','fail','abort','visit','stopover','waypoint','enter <system>', 'daily')
	public ?array $to = null; // array('offer','complete','fail','accept')
	public ?array $deadline = null; // array('days'=>int, 'multiplier'=>float);
	public ?array $cargo = null; // array('type'=>string,'count'=>int,'chanceCount'=>int,'chance'=>float)
	public ?array $passengers = null; // array('count','chanceCount', 'chance')
	public ?array $illegal = null; // array('fine','message')
	public ?array $distanceCalculationSettings = null; // array('all wormholes','only unrestricted wormholes','assumes jump drive')
	public array $waypoints = array();
	public array $stopovers = array();
	public array $substitutions = array();
	public array $npcs = array();
	
	public function __construct() {
		$this->description = new Description();
		$this->blocked = new Description();
	}
}