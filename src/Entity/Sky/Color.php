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
	public ?string $name = '';
	#[ORM\Column(type: 'float')]
	public float $red = 0.0;
	#[ORM\Column(type: 'float')]
	public float $green = 0.0;
	#[ORM\Column(type: 'float')]
	public float $blue = 0.0;
	#[ORM\Column(type: 'float')]
	public float $alpha = 0.0;
	
	#[ORM\Column(type: 'string')]
	private string $sourceName = '';
	#[ORM\Column(type: 'string')]
	private string $sourceFile = '';
	#[ORM\Column(type: 'string')]
	private string $sourceVersion = '';
	
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
	
	public function getSourceName(): string {
		return $this->sourceName;
	}
	public function setSourceName(string $sourceName): self {
		$this->sourceName = $sourceName;
		return $this;
	}
	
	public function getSourceFile(): string {
		return $this->sourceFile;
	}
	public function setSourceFile(string $sourceFile): self {
		$this->sourceFile = $sourceFile;
		return $this;
	}
	
	public function getSourceVersion(): string {
		return $this->sourceVersion;
	}
	public function setSourceVersion(string $sourceVersion): self {
		$this->sourceVersion = $sourceVersion;
		return $this;
	}
	
	public function toJSON($justArray=false): array|string {
		$jsonArray = ['name'=>$this->name, 'red'=>$this->red, 'green'=>$this->green, 'blue'=>$this->blue, 'alpha'=>$this->alpha];
		if ($justArray) {
			return $jsonArray;
		}
		
		$jsonArray['source'] = ['name'=>$this->sourceName,'file'=>$this->sourceFile,'version'=>$this->sourceVersion];
		return json_encode($jsonArray);
	}
}