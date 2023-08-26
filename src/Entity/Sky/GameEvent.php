<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'GameEvent')]
#[HasLifecycleCallbacks]
class GameEvent {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column]
	private int $dateInt = 0;
	private Date $date;
	#[ORM\Column(type: 'string')]
	private string $name = '';
	#[ORM\Column(type: 'boolean')]
	private bool $isDisabled = false;
	#[ORM\Column(type: 'boolean')]
	private bool $isDefined = false;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', cascade: ['persist'])]
	private ConditionSet $conditionsToApply;
	private array $changes; // list<DataNode>
	private array $systemsToVisit; //vector<const System *> 
	private array $planetsToVisit; //vector<const Planet *>
	private array $systemsToUnvisit; //vector<const System *>
	private array $planetsToUnvisit; //vector<const Planet *>
	
	
	public function load(DataNode $node) {
		// todo
	}
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->dateInt = $this->date->getDate();
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->date = new Date($this->dateInt, 0, 0);
	}
}