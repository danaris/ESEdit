<?php

namespace App\Entity\Sky;

class Effect {
	public string $name = '';
	public Sprite $sprite;
	
	public string $sound = '';
	public int $lifetime = 0;
	public int $randomLifetime = 0;
	public float $velocityScale = 0.0;
	public float $randomVelocity = 0.0;
	public float $randomAngle = 0.0;
	public float $randomSpin = 0.0;
	public float $randomFrameRate = 0.0;
	public float $absoluteVelocity = 0.0;
	public float $absoluteAngle = 0.0;
	
}