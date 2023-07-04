<?php

namespace App\Entity\Sky;

class Wormhole {
	public string $name;
	public string $displayName;
	public bool $mappable = false;
	public array $links = array();
	public array|string $color;
	
	public bool $active = false;
}