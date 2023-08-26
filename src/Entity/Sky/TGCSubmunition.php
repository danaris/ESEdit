<?php

namespace App\Entity\Sky;

class TGCSubmunition {
	public string $name = '';
	public int $count = 0;
	public ?float $facing = null;
	public ?Point $offset;
}