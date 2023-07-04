<?php

namespace App\Entity\Sky;

class SystemObject {
	public string $name = '';
	public string $sprite;
	public float $spriteScale = 1;
	public float $distance = 0;
	public float $period = 0;
	public float $offset = 0;
	public array $hazards = array();
	public array $children = array();
}