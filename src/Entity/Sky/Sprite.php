<?php

namespace App\Entity\Sky;

class Sprite {
	public string $name = '';
	public int $frameRate = 0;
	public int $frameTime = 0;
	public int $delay = 0;
	public int $startFrame = 0;
	public bool $randomStartFrame = false;
	public bool $noRepeat = false;
	public bool $rewind = false;
}