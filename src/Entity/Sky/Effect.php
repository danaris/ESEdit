<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'Effect')]
#[ORM\HasLifecycleCallbacks]
class Effect extends Body {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	protected int $id;
	
	#[ORM\Column(type: 'string')]
	protected string $name;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sound')]
	#[ORM\JoinColumn(nullable: true, name: 'soundId')]
	protected ?Sound $sound = null;
	
	// Parameters used for randomizing spin and velocity. The random angle is
	// added to the parent angle, and then a random velocity in that direction
	// is added to the parent velocity.
	#[ORM\Column(type: 'float')]
	protected float $velocityScale = 1.;
	#[ORM\Column(type: 'float')]
	protected float $randomVelocity = 0.;
	#[ORM\Column(type: 'float')]
	protected float $randomAngle = 0.;
	#[ORM\Column(type: 'float')]
	protected float $randomSpin = 0.;
	#[ORM\Column(type: 'float')]
	protected float $randomFrameRate = 0.;
	// Absolute values are independent of the parent Body if specified.
	#[ORM\Column(type: 'float', name: 'absoluteAngleDegrees')]
	protected float $absoluteAngleDegrees;
	protected Angle $absoluteAngle;
	#[ORM\Column(type: 'boolean')]
	protected bool $hasAbsoluteAngle = false;
	#[ORM\Column(type: 'float')]
	protected float $absoluteVelocity = 0.;
	#[ORM\Column(type: 'boolean')]
	protected bool $hasAbsoluteVelocity = false;
	
	#[ORM\Column(type: 'float')]
	protected int $lifetime = 0;
	#[ORM\Column(type: 'float')]
	protected int $randomLifetime = 0;
	
	public function __construct() {
		parent::__construct();
		$this->absoluteAngle = new Angle();
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function setName(string $name) {
		$this->name = $name;
	}
	
	public function load(DataNode $node) {
		if ($node->size() > 1) {
			$this->name = $node->getToken(1);
		}
		
		foreach ($node as $child) {
			if ($child->getToken(0) == "sprite") {
				$this->loadSprite($child);
			} else if ($child->getToken(0) == "sound" && $child->size() >= 2) {
//				$this->sound = Audio::Get($child->getToken(1));
			} else if ($child->getToken(0) == "lifetime" && $child->size() >= 2) {
				$this->lifetime = $child->getValue(1);
			} else if ($child->getToken(0) == "random lifetime" && $child->size() >= 2) {
				$this->randomLifetime = $child->getValue(1);
			} else if ($child->getToken(0) == "velocity scale" && $child->size() >= 2) {
				$this->velocityScale = $child->getValue(1);
			} else if ($child->getToken(0) == "random velocity" && $child->size() >= 2) {
				$this->randomVelocity = $child->getValue(1);
			} else if ($child->getToken(0) == "random angle" && $child->size() >= 2) {
				$this->randomAngle = $child->getValue(1);
			} else if ($child->getToken(0) == "random spin" && $child->size() >= 2) {
				$this->randomSpin = $child->getValue(1);
			} else if ($child->getToken(0) == "random frame rate" && $child->size() >= 2) {
				$this->randomFrameRate = $child->getValue(1);
			} else if ($child->getToken(0) == "absolute angle" && $child->size() >= 2) {
				$this->absoluteAngle = new Angle($child->getValue(1));
				$this->hasAbsoluteAngle = true;
			} else if ($child->getToken(0) == "absolute velocity" && $child->size() >= 2) {
				$this->absoluteVelocity = $child->getValue(1);
				$this->hasAbsoluteVelocity = true;
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	}
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->absoluteAngleDegrees = $this->absoluteAngle->getDegrees();
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->absoluteAngle = new Angle($this->absoluteAngleDegrees);
	}
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['velocityScale'] = $this->velocityScale;
		$jsonArray['randomVelocity'] = $this->randomVelocity;
		$jsonArray['randomAngle'] = $this->randomAngle;
		$jsonArray['randomSpin'] = $this->randomSpin;
		$jsonArray['randomFrameRate'] = $this->randomFrameRate;
		$jsonArray['absoluteAngleDegrees'] = $this->absoluteAngleDegrees;
		$jsonArray['hasAbsoluteAngle'] = $this->hasAbsoluteAngle;
		$jsonArray['absoluteVelocity'] = $this->absoluteVelocity;
		$jsonArray['hasAbsoluteVelocity'] = $this->hasAbsoluteVelocity;
	
		$jsonArray['lifetime'] = $this->lifetime;
		$jsonArray['randomLifetime'] = $this->randomLifetime;
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
}