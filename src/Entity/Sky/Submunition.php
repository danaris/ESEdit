<?php

namespace App\Entity\Sky;

class Submunition {
	public string $name = '';
	public int $count = 0;
	public ?float $facing = null;
	public ?Point $offset;
}