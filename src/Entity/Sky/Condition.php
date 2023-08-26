<?php

namespace App\Entity\Sky;

class Condition {
	public string $attribute;
	public string $test;
	public float $val;
	public bool $never = false;
	
	public array $and = array();
	public array $or = array();
}