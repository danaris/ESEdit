<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'Galaxy')]
#[ORM\HasLifecycleCallbacks]
class Galaxy {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string')]
	public string $name = '';
	#[ORM\Column(type: 'string')]
	private string $positionStr = '';
	private Point $position;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sprite', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'spriteId')]
	private ?Sprite $sprite = null;
	
	public function __construct() {
		$this->position = new Point();
	}
	
	public function load(DataNode $node): void {
		$this->name = $node->getToken(1);
		foreach ($node as $child) {
			$remove = $child->getToken(0) == "remove";
			$keyIndex = $remove;
			$hasKey = $child->size() > $keyIndex;
			$key = $hasKey ? $child->getToken($keyIndex) : $child->getToken(0);
	
			if ($remove && $hasKey) {
				if ($key == "sprite") {
					$this->sprite = null;
				} else {
					$child->printTrace("Skipping unsupported use of \"remove\":");
				}
			} else if ($key == "pos" && $child->size() >= 3) {
				$this->position = new Point($child->getValue(1), $child->getValue(2));
			} else if ($key == "sprite" && $child->size() >= 2) {
				$this->sprite = SpriteSet::Get($child->getToken(1));
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function getPosition(): Point {
		return $this->position;
	}
	
	public function getSprite(): ?Sprite {
		return $this->sprite;
	}
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->positionStr = json_encode($this->position);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$positionArray = json_decode($this->positionStr, true);
		$this->position = new Point($positionArray['x'], $positionArray['y']);
	}
	
	public function toJSON(bool $justArray = false): array|string {
		$jsonArray = [];
		
		$jsonArray['name'] = $this->name;
		$jsonArray['position'] = $this->position->toJSON(true);
		$jsonArray['spriteId'] = $this->sprite?->getId();
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}
}