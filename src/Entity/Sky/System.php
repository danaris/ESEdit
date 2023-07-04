<?php

namespace App\Entity\Sky;

class System {
	public string $name;
	public bool $hidden = false;
	public bool $inaccessible = false;
	public Point $pos;
	public string $government = '';
	public int $linkArrival;
	public int $jumpArrival;
	public int $linkDeparture = 0;
	public int $jumpDeparture = 0;
	public int $habitable;
	public int $belt;
	public Ramscoop $ramscoop;
	public int $invisibleFence = -1;
	public int $jumpRange = -1;
	public float $starfieldDensity = 1;
	public string $haze = '';
	public string $music = '';
	public array $attributes = array();
	public array $links = array();
	public array $asteroids = array();
	public array $minables = array();
	public array $trade = array();
	public array $objects = array();
	public array $fleets = array();
	public array $hazards = array();
	public bool $inhabited = false;
	
	public function __construct() {
		$this->point = new Point(0,0);
	}
}