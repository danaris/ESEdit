<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Color')]
class Color {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string', nullable: true)]
	public ?string $name;
	#[ORM\Column(type: 'float')]
	public float $red = 0.0;
	#[ORM\Column(type: 'float')]
	public float $green = 0.0;
	#[ORM\Column(type: 'float')]
	public float $blue = 0.0;
	#[ORM\Column(type: 'float')]
	public float $alpha = 0.0;
	
	public function __construct(float $red = 0.0, 
						 float $green = 0.0, 
						 float $blue = 0.0, 
						 float $alpha = 1.0) {
		$this->red = $red;
		$this->green = $green;
		$this->blue = $blue;
		$this->alpha = $alpha;
	}
	
	public function load(float $red, 
						 float $green, 
						 float $blue, 
						 float $alpha) {
		$this->red = $red;
		$this->green = $green;
		$this->blue = $blue;
		$this->alpha = $alpha;
	}
	
	public function toJSON($justArray=false): array|string {
		$jsonArray = ['red'=>$this->red, 'green'=>$this->green, 'blue'=>$this->blue, 'alpha'=>$this->alpha];
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}
}