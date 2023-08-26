<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Entity\DataNode;
use App\Entity\DataWriter;
use App\Entity\TemplatedArray;

use App\Service\TemplatedArrayService;

enum Location : string {
	case SPACEPORT = "spaceport"; 
	case LANDING = "landing"; 
	case JOB = "job"; 
	case ASSISTING = "assisting"; 
	case BOARDING = "boarding"; 
	case SHIPYARD = "shipyard"; 
	case OUTFITTER = "outfitter";
};
enum Trigger : int {
	case COMPLETE = 0;
	case OFFER = 1;
	case ACCEPT = 2;
	case DECLINE = 3;
	case FAIL = 4;
	case ABORT = 5;
	case DEFER = 6;
	case VISIT = 7;
	case STOPOVER = 8;
	case WAYPOINT = 9;
	case DAILY = 10;
	case DISABLED = 11;
};

#[ORM\Entity]
#[ORM\Table(name: 'Mission')]
#[ORM\HasLifecycleCallbacks]
class Mission {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string', name: 'name')]
	private string $name = '';
	
	#[ORM\Column(type: 'string', name: 'displayName')]
	private string $displayName = '';
	
	#[ORM\Column(type: 'text', name: 'description')]
	private string $description = '';
	
	#[ORM\Column(type: 'text', name: 'blocked')]
	private string $blocked = '';
	
	#[ORM\Column(type: 'string', name: 'location')]
	private string $locationStr = Location::SPACEPORT->value;
	private Location $location = Location::SPACEPORT;
	
	private EsUuid $uuid;
	
	#[ORM\Column(type: 'boolean', name: 'hasFailed')]
	private bool $hasFailed = false;
	
	#[ORM\Column(type: 'boolean', name: 'isVisible')]
	private bool $isVisible = true;
	
	#[ORM\Column(type: 'boolean', name: 'hasPriority')]
	private bool $hasPriority = false;
	
	#[ORM\Column(type: 'boolean', name: 'isMinor')]
	private bool $isMinor = false;
	
	#[ORM\Column(type: 'boolean', name: 'autosave')]
	private bool $autosave = false;
	
	#[ORM\Column(type: 'boolean', name: 'overridesCapture')]
	private bool $overridesCapture = false;
	
	#[ORM\Column(type: 'integer', name: 'deadline')]
	private int $deadlineInt;
	private Date $deadline;
	
	#[ORM\Column(type: 'integer', name: 'expectedJumps')]
	private int $expectedJumps = 0;
	
	#[ORM\Column(type: 'integer', name: 'deadlineBase')]
	private int $deadlineBase = 0;
	
	#[ORM\Column(type: 'integer', name: 'deadlineMultiplier')]
	private int $deadlineMultiplier = 0;
	
	#[ORM\Column(type: 'text', name: 'clearance')]
	private string $clearance = '';
	
	#[ORM\Column(type: 'boolean', name: 'ignoreClearance')]
	private bool $ignoreClearance = false;
	
	#[ORM\Column(type: 'boolean', name: 'hasFullClearance')]
	private bool $hasFullClearance = true;
	
	#[ORM\Column(type: 'integer', name: 'repeatCount')]
	private int $repeat = 1;
	
	#[ORM\Column(type: 'string', name: 'cargo')]
	private string $cargo = '';
	
	#[ORM\Column(type: 'integer', name: 'cargoSize')]
	private int $cargoSize = 0;
	
	// Parameters for generating random cargo amounts:
	#[ORM\Column(type: 'integer', name: 'cargoLimit')]
	private int $cargoLimit = 0;
	
	#[ORM\Column(type: 'float', name: 'cargoProb')]
	private float $cargoProb = 0.;
	
	#[ORM\Column(type: 'integer', name: 'illegalCargoFine')]
	private int $illegalCargoFine = 0;
	
	#[ORM\Column(type: 'text', name: 'illegalCargoMessage')]
	private string $illegalCargoMessage = '';
	
	#[ORM\Column(type: 'boolean', name: 'failIfDiscovered')]
	private bool $failIfDiscovered = false;
	
	#[ORM\Column(type: 'integer', name: 'passengers')]
	private int $passengers = 0;
	
	// Parameters for generating random passenger amounts:
	#[ORM\Column(type: 'integer', name: 'passengerLimit')]
	private int $passengerLimit = 0;
	
	#[ORM\Column(type: 'float', name: 'passengerProb')]
	private float $passengerProb = 0.;
	
	#[ORM\Column(type: 'integer', name: 'paymentApparent')]
	private int $paymentApparent = 0;
	
	#[ORM\Column(type: 'string', name: 'distanceCalcSettings')]
	private string $distanceCalcString = '';
	private DistanceCalculationSettings $distanceCalcSettings;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\LocationFilter', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'clearanceFilterId')]
	private LocationFilter $clearanceFilter;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'toOfferId')]
	private ConditionSet $toOffer;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'toAcceptId')]
	private ConditionSet $toAccept;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'toCompleteId')]
	private ConditionSet $toComplete;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'toFailId')]
	private ConditionSet $toFail;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Planet', inversedBy: 'sourcedMissions', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'sourceId')]
	private ?Planet $source = null;
	
	// The ship this mission originated from, if it is a boarding mission.
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Ship', inversedBy: 'sourcedMissions', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'sourceShipId')]
	private ?Ship $sourceShip = null;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\LocationFilter', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'sourceFilterId')]
	private LocationFilter $sourceFilter;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Planet', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'destinationId')]
	private ?Planet $destination = null;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\LocationFilter', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'destinationFilterId')]
	private LocationFilter $destinationFilter;
	
	// Systems that must be visited:
	#[ORM\JoinTable(name: 'missionWaypoints')]
	#[ORM\JoinColumn(name: 'missionId', referencedColumnName: 'id')]
	#[ORM\InverseJoinColumn(name: 'waypointSystemId', referencedColumnName: 'id', unique: false)]
	#[ORM\ManyToMany(targetEntity: 'App\Entity\Sky\System', cascade: ['persist'])]
	private Collection $waypoints; // set<const System *>
	
	#[ORM\JoinTable(name: 'missionWaypointFilters')]
	#[ORM\JoinColumn(name: 'missionId', referencedColumnName: 'id')]
	#[ORM\InverseJoinColumn(name: 'waypointFilterId', referencedColumnName: 'id', unique: false)]
	#[ORM\ManyToMany(targetEntity: 'App\Entity\Sky\LocationFilter', cascade: ['persist'])]
	private Collection $waypointFilters; // list<LocationFilter>
	
	#[ORM\JoinTable(name: 'missionStopovers')]
	#[ORM\JoinColumn(name: 'missionId', referencedColumnName: 'id')]
	#[ORM\InverseJoinColumn(name: 'stopoverPlanetId', referencedColumnName: 'id', unique: false)]
	#[ORM\ManyToMany(targetEntity: 'App\Entity\Sky\Planet', cascade: ['persist'])]
	private Collection $stopovers; // set<const Planet *>
	
	#[ORM\JoinTable(name: 'missionStopoverFilters')]
	#[ORM\JoinColumn(name: 'missionId', referencedColumnName: 'id')]
	#[ORM\InverseJoinColumn(name: 'stopoverFilterId', referencedColumnName: 'id', unique: false)]
	#[ORM\ManyToMany(targetEntity: 'App\Entity\Sky\LocationFilter', cascade: ['persist'])]
	private Collection $stopoverFilters; // list<LocationFilter>
	
	#[ORM\JoinTable(name: 'missionVisitedStopovers')]
	#[ORM\JoinColumn(name: 'missionId', referencedColumnName: 'id')]
	#[ORM\InverseJoinColumn(name: 'visitedStopoverPlanetId', referencedColumnName: 'id', unique: false)]
	#[ORM\ManyToMany(targetEntity: 'App\Entity\Sky\Planet', cascade: ['persist'])]
	private Collection $visitedStopovers; // set<const Planet *>
	
	#[ORM\JoinTable(name: 'missionVisitedWaypoints')]
	#[ORM\JoinColumn(name: 'missionId', referencedColumnName: 'id')]
	#[ORM\InverseJoinColumn(name: 'visitedWaypointSystemId', referencedColumnName: 'id', unique: false)]
	#[ORM\ManyToMany(targetEntity: 'App\Entity\Sky\System', cascade: ['persist'])]
	private Collection $visitedWaypoints; // set<const System *>
	
	// User-defined text replacements unique to this mission:
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\TextReplacements', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'substitutionsId')]
	private TextReplacements $substitutions;
	
	// NPCs:
	
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\NPC', mappedBy: 'mission', cascade: ['persist'])]
	private Collection $npcs; // list<NPC>
	
	// Actions to perform:
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\MissionAction', mappedBy: 'mission', cascade: ['persist'])]
	private Collection $actionCollection;
	private TemplatedArray $actions; // map<Trigger, MissionAction>
	
	// "on enter" actions may name a specific system, or rely on matching a
	// LocationFilter in order to designate the matched system.
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\MissionAction', mappedBy: 'onEnterMission', cascade: ['persist'])]
	private Collection $onEnterCollection;
	private TemplatedArray $onEnter; // map<const System *, MissionAction>
	
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\MissionAction', mappedBy: 'genericOnEnterMission', cascade: ['persist'])]
	private Collection $genericOnEnter; // list<MissionAction>
	
	// Track which `on enter` MissionActions have triggered.
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\MissionAction', mappedBy: 'didEnterMission', cascade: ['persist'])]
	private Collection $didEnter; // set<const MissionAction *>

	// TGC added
	#[ORM\Column(type: 'string', name: 'fromFile')]
	public string $fromFile = '';
	public array $isUnlockedBy = [];
	public array $unlocksOn = [];
	public array $isBlockedBy = [];
	public array $blocksOn = [];
	public array $triggersEventsOn = [];
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->deadlineInt = $this->deadline->getDate();
		$this->locationStr = $this->location->value;
		$this->distanceCalcString = DistanceCalculationSettings::StringFromSettings($this->distanceCalcSettings);
		$this->actionCollection = new ArrayCollection();
		foreach ($this->actions as $trigger => $Action) {
			$this->actionCollection []= $Action;
		}
		$this->onEnterCollection = new ArrayCollection();
		foreach ($this->onEnter as $systemName => $Action) {
			$this->onEnterCollection []= $Action;
		}
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->deadline = new Date($this->deadlineInt, 0, 0);
		$this->location = Location::from($this->locationStr);
		$this->distanceCalcSettings = DistanceCalculationSettings::SettingsFromString($this->distanceCalcString);
		$this->actions = TemplatedArrayService::Instance()->createTemplatedArray(MissionAction::class, 'trigger');
		foreach ($this->actionCollection as $Action) {
			$this->actions[$Action->getTrigger()] = $Action;
		}
		$this->onEnter = TemplatedArrayService::Instance()->createTemplatedArray(MissionAction::class, 'system');
		foreach ($this->onEnterCollection as $Action) {
			$this->onEnter[$Action->getSystem()] = $Action;
		}
	}
	
	private static array $triggerNames = [
		"complete" => Trigger::COMPLETE,
		"offer" => Trigger::OFFER,
		"accept" => Trigger::ACCEPT,
		"decline" => Trigger::DECLINE,
		"fail" => Trigger::FAIL,
		"abort" => Trigger::ABORT,
		"defer" => Trigger::DEFER,
		"visit" => Trigger::VISIT,
		"stopover" => Trigger::STOPOVER,
		"waypoint" => Trigger::WAYPOINT,
		"daily" => Trigger::DAILY,
		"disabled" => Trigger::DISABLED
	];
	
	public static function PickCommodity(System $from, System $to): Commodity {
		$weight = [];
		$total = 0;
		foreach (GameData::Commodities() as $commodity) {
			// For every 100 credits in profit you can make, double the chance
			// of this commodity being chosen.
			$profit = $to->trade($commodity->name) - $from->trade($commodity->name);
			$w = intval(max(1, 100. * pow(2., $profit * .01)));
			$weight []= $w;
			$total += $w;
		}
		$total += !$total;
		// Pick a random commodity based on those weights.
		$r = rand(0, $total);
		for ($i = 0; $i < count($weight); ++$i) {
			$r -= $weight[$i];
			if ($r < 0) {
				return GameData::Commodities()[$i];
			}
		}
		// Control will never reach here, but to satisfy the compiler:
		return null;
	}

	// If a source, destination, waypoint, or stopover supplies more than one explicit choice
	// or a mixture of explicit choice and location filter, print a warning.
	public static function ParseMixedSpecificity(DataNode $node, string $kind, int $expected): void {
		if ($node->size() >= $expected + 1) {
			$node->printTrace("Warning: use a location filter to choose from multiple " . $kind . "s:");
		}
		if ($node->hasChildren()) {
			$node->printTrace("Warning: location filter ignored due to use of explicit " . $kind . ":");
		}
	}

	public static function TriggerToText(Trigger $trigger): string {
		switch ($trigger) {
			case Trigger::ABORT:
				return "on abort";
			case Trigger::ACCEPT:
				return "on accept";
			case Trigger::COMPLETE:
				return "on complete";
			case Trigger::DECLINE:
				return "on decline";
			case Trigger::DEFER:
				return "on defer";
			case Trigger::FAIL:
				return "on fail";
			case Trigger::OFFER:
				return "on offer";
			case Trigger::STOPOVER:
				return "on stopover";
			case Trigger::VISIT:
				return "on visit";
			case Trigger::WAYPOINT:
				return "on waypoint";
			case Trigger::DAILY:
				return "on daily";
			case Trigger::DISABLED:
				return "on disabled";
			default:
				return "unknown trigger";
		}
	}
	
	// Construct and Load() at the same time.
	public function __construct(?DataNode $node = null) {
		$this->uuid = new EsUuid();
		$this->deadline = new Date(0, 0, 0);
		$this->distanceCalcSettings = new DistanceCalculationSettings();
		$this->clearanceFilter = new LocationFilter();
		$this->toOffer = new ConditionSet();
		$this->toAccept = new ConditionSet();
		$this->toComplete = new ConditionSet();
		$this->toFail = new ConditionSet();
		$this->sourceFilter = new LocationFilter();
		$this->destinationFilter = new LocationFilter();
		$this->substitutions = new TextReplacements();
		$this->actions = TemplatedArrayService::Instance()->createTemplatedArray(MissionAction::class, 'trigger');
		$this->onEnter = TemplatedArrayService::Instance()->createTemplatedArray(MissionAction::class, 'system');
		$this->genericOnEnter = new ArrayCollection();
		$this->waypoints = new ArrayCollection();
		$this->waypointFilters = new ArrayCollection();
		$this->stopovers = new ArrayCollection();
		$this->stopoverFilters = new ArrayCollection();
		$this->visitedWaypoints = new ArrayCollection();
		$this->visitedStopovers = new ArrayCollection();
		if ($node) {
			$this->load($node);
		}
	}
	
	public function getToOffer(): ConditionSet {
		return $this->toOffer;
	}
	
	public function getActions(): TemplatedArray {
		return $this->actions;
	}
	
	// Load a mission, either from the game data or from a saved game.
	public function load(DataNode $node) {
		// All missions need a name.
		if ($node->size() < 2) {
			$node->printTrace("Error: No name specified for mission:");
			return;
		}
		// If a mission object is "loaded" twice, that is most likely an error (e.g.
		// due to a plugin containing a mission with the same name as the base game
		// or another plugin). This class is not designed to allow merging or
		// overriding of mission data from two different definitions.
		if ($this->name != '') {
			$node->printTrace("Error: Duplicate definition of mission:");
			return;
		}
		$this->name = $node->getToken(1);
		if ($node->fromFile) {
			$this->fromFile = $node->fromFile;
		}
	
		foreach ($node as $child) {
			if ($child->getToken(0) == "name" && $child->size() >= 2) {
				$this->displayName = $child->getToken(1);
			} else if ($child->getToken(0) == "uuid" && $child->size() >= 2) {
				$this->uuid = EsUuid::FromString($child->getToken(1));
			} else if ($child->getToken(0) == "description" && $child->size() >= 2) {
				$this->description = $child->getToken(1);
			} else if ($child->getToken(0) == "blocked" && $child->size() >= 2) {
				$this->blocked = $child->getToken(1);
			} else if ($child->getToken(0) == "deadline" && $child->size() >= 4) {
				$this->deadline = new Date($child->getValue(1), $child->getValue(2), $child->getValue(3));
			} else if ($child->getToken(0) == "deadline") {
				if ($child->size() == 1) {
					$this->deadlineMultiplier += 2;
				}
				if ($child->size() >= 2) {
					$this->deadlineBase += $child->getValue(1);
				}
				if ($child->size() >= 3) {
					$this->deadlineMultiplier += $child->getValue(2);
				}
			} else if ($child->getToken(0) == "distance calculation settings" && $child->hasChildren()) {
				$this->distanceCalcSettings->load($child);
			} else if ($child->getToken(0) == "cargo" && $child->size() >= 3) {
				$this->cargo = $child->getToken(1);
				$this->cargoSize = $child->getValue(2);
				if ($child->size() >= 4) {
					$this->cargoLimit = $child->getValue(3);
				}
				if ($child->size() >= 5) {
					$this->cargoProb = $child->getValue(4);
				}
	
				foreach ($child as $grand) {
					if (!$this->parseContraband($grand)) {
						$grand->printTrace("Skipping unrecognized attribute:");
					} else {
						$grand->printTrace("Warning: Deprecated use of \"stealth\" and \"illegal\" as a child of \"cargo\". They are now mission-level properties:");
					}
				}
			} else if ($child->getToken(0) == "passengers" && $child->size() >= 2) {
				$this->passengers = $child->getValue(1);
				if ($child->size() >= 3) {
					$this->passengerLimit = $child->getValue(2);
				}
				if ($child->size() >= 4) {
					$this->passengerProb = $child->getValue(3);
				}
			} else if ($child->getToken(0) == "apparent payment" && $child->size() >= 2) {
				$this->paymentApparent = $child->getValue(1);
			} else if ($this->parseContraband($child)) {
				// This was an "illegal" or "stealth" entry. It has already been
				// parsed, so nothing more needs to be done here.
			} else if ($child->getToken(0) == "invisible") {
				$this->isVisible = false;
			} else if ($child->getToken(0) == "priority") {
				$this->hasPriority = true;
			} else if ($child->getToken(0) == "minor") {
				$this->isMinor = true;
			} else if ($child->getToken(0) == "autosave") {
				$this->autosave = true;
			} else if ($child->getToken(0) == "job") {
				$this->location = Location::JOB;
			} else if ($child->getToken(0) == "landing") {
				$this->location = Location::LANDING;
			} else if ($child->getToken(0) == "assisting") {
				$this->location = Location::ASSISTING;
			} else if ($child->getToken(0) == "boarding") {
				$this->location = Location::BOARDING;
				foreach ($child as $grand) {
					if ($grand->getToken(0) == "override capture") {
						$this->overridesCapture = true;
					} else {
						$grand->printTrace("Skipping unrecognized attribute:");
					}
				}
			} else if ($child->getToken(0) == "shipyard") {
				$this->location = SHIPYARD;
			} else if ($child->getToken(0) == "outfitter") {
				$this->location = OUTFITTER;
			} else if ($child->getToken(0) == "repeat") {
				$this->repeat = ($child->size() == 1 ? 0 : intval($child->getValue(1)));
			} else if ($child->getToken(0) == "clearance") {
				$this->clearance = ($child->size() == 1 ? "auto" : $child->getToken(1));
				$this->clearanceFilter->load($child);
			} else if ($child->size() == 2 && $child->getToken(0) == "ignore" && $child->getToken(1) == "clearance") {
				$this->ignoreClearance = true;
			} else if ($child->getToken(0) == "infiltrating") {
				$this->hasFullClearance = false;
			} else if ($child->getToken(0) == "failed") {
				$this->hasFailed = true;
			} else if ($child->getToken(0) == "to" && $child->size() >= 2) {
				if ($child->getToken(1) == "offer") {
					$this->toOffer->load($child);
				} else if ($child->getToken(1) == "complete") {
					$this->toComplete->load($child);
				} else if ($child->getToken(1) == "fail") {
					$this->toFail->load($child);
				} else if ($child->getToken(1) == "accept") {
					$this->toAccept->load($child);
				} else {
					$child->printTrace("Skipping unrecognized attribute:");
				}
			} else if ($child->getToken(0) == "source" && $child->size() >= 2) {
				$this->source = GameData::Planets()[$child->getToken(1)];
				$this->parseMixedSpecificity($child, "planet", 2);
			} else if ($child->getToken(0) == "source") {
				$this->sourceFilter->load($child);
			} else if ($child->getToken(0) == "destination" && $child->size() == 2) {
				$this->destination = GameData::Planets()[$child->getToken(1)];
				$this->parseMixedSpecificity($child, "planet", 2);
			} else if ($child->getToken(0) == "destination") {
				$this->destinationFilter->load($child);
			} else if ($child->getToken(0) == "waypoint" && $child->size() >= 2) {
				$visited = $child->size() >= 3 && $child->getToken(2) == "visited";
				$set = $visited ? $this->visitedWaypoints : $this->waypoints;
				$set []= GameData::Systems()[$child->getToken(1)];
				$this->parseMixedSpecificity($child, "system", 2 + $visited);
			} else if ($child->getToken(0) == "waypoint" && $child->hasChildren()) {
				$filter = new LocationFilter($child);
				$this->waypointFilters []= $filter;
			} else if ($child->getToken(0) == "stopover" && $child->size() >= 2) {
				$visited = $child->size() >= 3 && $child->getToken(2) == "visited";
				$set = $visited ? $this->visitedStopovers : $this->stopovers;
				$set []= GameData::Planets()[$child->getToken(1)];
				$this->parseMixedSpecificity($child, "planet", 2 + $visited);
			} else if ($child->getToken(0) == "stopover" && $child->hasChildren()) {
				$filter = new LocationFilter($child);
				$this->stopoverFilters []= $filter;
			} else if ($child->getToken(0) == "substitutions" && $child->hasChildren()) {
				$this->substitutions->load($child);
			} else if ($child->getToken(0) == "npc") {
				$npc = new NPC($child, $this->name);
				$npcs []= $npc;
			} else if ($child->getToken(0) == "on" && $child->size() >= 2 && $child->getToken(1) == "enter") {
				// "on enter" nodes may either name a specific system or use a LocationFilter
				// to control the triggering system.
				if ($child->size() >= 3) {
					$action = $this->onEnter[GameData::Systems()[$child->getToken(2)]->getName()];
					$action->load($child, $this->name);
				} else {
					$action = new MissionAction($child, $this->name);
					$this->genericOnEnter []= $action;
				}
			} else if ($child->getToken(0) == "on" && $child->size() >= 2) {
				if (!isset(self::$triggerNames[$child->getToken(1)])) {
					$child->printTrace("Skipping unrecognized attribute:");
				} else {
					$trigger = self::$triggerNames[$child->getToken(1)];
					$this->actions[$trigger->name]->load($child, $this->name);
				}	
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	
		if ($this->displayName == '') {
			$this->displayName = $this->name;
		}
		if ($this->hasPriority && $this->location == Location::LANDING) {
			$node->printTrace("Warning: \"priority\" tag has no effect on \"landing\" missions:");
		}
	}
	
	// Save a mission. It is safe to assume that any mission that is being saved
	// is already "instantiated," so only a subset of the data must be saved.
	public function save(DataWriter $out, string $tag = ''): void {
		if ($tag == '') {
			$out->write($this->name);
		} else {
			$out->write([$tag, $this->name]);
		}
		$out->beginChild(); 
		//{
			$out->write(["name", $this->displayName]);
			$out->write(["uuid", $this->uuid]);
			if ($this->description != '') {
				$out->write(["description", $this->description]);
			}
			if ($this->blocked != '') {
				$out->write(["blocked", $this->blocked]);
			}
			if ($this->deadline) {
				$out->write(["deadline", $this->deadline->getDay(), $this->deadline->getMonth(), $this->deadline->getYear()]);
			}
			if ($this->cargoSize) {
				$out->write(["cargo", $this->cargo, $this->cargoSize]);
			}
			if ($this->passengers) {
				$out->write(["passengers", $this->passengers]);
			}
			if ($this->paymentApparent) {
				$out->write(["apparent payment", $this->paymentApparent]);
			}
			if ($this->illegalCargoFine) {
				$out->write(["illegal", $this->illegalCargoFine, $this->illegalCargoMessage]);
			}
			if ($this->failIfDiscovered) {
				$out->write("stealth");
			}
			if (!$this->isVisible) {
				$out->write("invisible");
			}
			if ($this->hasPriority) {
				$out->write("priority");
			}
			if ($this->isMinor) {
				$out->write("minor");
			}
			if ($this->autosave) {
				$out->write("autosave");
			}
			if ($this->location == Location::LANDING) {
				$out->write("landing");
			}
			if ($this->location == Location::ASSISTING) {
				$out->write("assisting");
			}
			if ($this->location == Location::BOARDING) {
				$out->write("boarding");
				if ($this->overridesCapture) {
					$out->beginChild(); 
					//{
						$out->write("override capture");
					//}
					$out->endChild();
				}
			}
			if ($this->location == Location::JOB) {
				$out->write("job");
			}
			if ($this->clearance == '') {
				$out->write(["clearance", $this->clearance]);
				$this->clearanceFilter->save($out);
			}
			if ($this->ignoreClearance) {
				$out->write(["ignore", "clearance"]);
			}
			if (!$this->hasFullClearance) {
				$out->write("infiltrating");
			}
			if ($this->hasFailed) {
				$out->write("failed");
			}
			if ($this->repeat != 1) {
				$out->write(["repeat", $this->repeat]);
			}
			if (!$this->toOffer->isEmpty()) {
				$out->write(["to", "offer"]);
				$out->beginChild(); 
				//{
					$this->toOffer->save($out);
				//}
				$out->endChild();
			}
			if (!$this->toAccept->isEmpty()) {
				$out->write(["to", "accept"]);
				$out->beginChild();
				//{
					$this->toAccept->save($out);
				//}
				$out->endChild();
			}
			if (!$this->toComplete->isEmpty()) {
				$out->write(["to", "complete"]);
				$out->beginChild();
				//{
					$this->toComplete->save($out);
				//}
				$out->endChild();
			}
			if (!$this->toFail->isEmpty()) {
				$out->write(["to", "fail"]);
				$out->beginChild();
				//{
					$this->toFail->save($out);
				//}
				$out->endChild();
			}
			if ($this->destination) {
				$out->write(["destination", $this->destination->getName()]);
			}
			foreach ($this->waypoints as $system) {
				$out->write(["waypoint", $system->getName()]);
			}
			foreach ($this->visitedWaypoints as $system) {
				$out->write(["waypoint", $system->getName(), "visited"]);
			}
			foreach ($this->stopovers as $planet) {
				$out->write(["stopover", $planet->getTrueName()]);
			}
			foreach ($this->visitedStopovers as $planet) {
				$out->write(["stopover", $planet->getTrueName(), "visited"]);
			}
			foreach ($this->npcs as $npc) {
				$npc->save($out);
			}
	
			// Save all the actions, because this might be an "available mission" that
			// has not been received yet but must still be included in the saved game.
			foreach ($this->actions as $triggerName => $action) {
				$action->save($out);
			}
			// Save any "on enter" actions that have not been performed.
			foreach ($this->onEnter as $action) {
				if (!in_array($action, $this->didEnter)) {
					$action->save($out);
				}
			}
			foreach ($this->genericOnEnter as $action) {
				if (!in_array($action, $this->didEnter)) {
					$action->save($out);
				}
			}
		//}
		$out->endChild();
	}
	
	public function neverOffer(): void {
		// Add the equivalent "never" condition, `"'" != 0`.
		$this->toOffer->add(firstToken:"has", secondToken:"'");
	}
	
	// Basic mission information.
	public function getUUID(): EsUuid {
		return $this->uuid;
	}
	
	public function getName(): string {
		return $this->displayName;
	}
	
	public function getDescription(): string {
		return $this->description;
	}
	
	// Check if this mission should be shown in your mission list. If not, the
	// player will not know this mission exists (which is sometimes useful).
	public function isVisible(): bool {
		return $this->isVisible;
	}
	
	// Check if this instantiated mission uses any systems, planets, or ships that are
	// not fully defined. If everything is fully defined, this is a valid mission.
	public function isValid(): bool {
		// Planets must be defined and in a system. However, a source system does not necessarily exist.
		if ($this->source && !$this->source->IsValid()) {
			return false;
		}
		// Every mission is required to have a destination.
		if (!$this->destination || !$this->destination->IsValid()) {
			return false;
		}
		// All stopovers must be valid.
		foreach ($this->getStopovers() as $planet) {
			if (!$planet->isValid()) {
				return false;
			}
		}
		foreach ($this->getVisitedStopovers() as $planet) {
			if (!$planet->isValid()) {
				return false;
			}
		}
	
		// Systems must have a defined position.
		foreach ($this->getWaypoints() as $system) {
			if (!$system->isValid()) {
				return false;
			}
		}
		foreach ($this->getVisitedWaypoints() as $system) {
			if (!$system->isValid()) {
				return false;
			}
		}
	
		// Actions triggered when entering a system should reference valid systems.
		foreach ($this->onEnter as $systemName => $action) {
			$system = GameData::Systems()[$systemName];
			if (!$system || !$system->isValid() || !$action->validate() == '') {
				return false;
			}
		}
		foreach ($this->actions as $triggerName => $action) {
			if (!$action->validate() == '') {
				return false;
			}
		}
		// Generic "on enter" may use a LocationFilter that exclusively references invalid content.
		foreach ($this->genericOnEnter as $action) {
			if (!$action->validate() == '') {
				return false;
			}
		}
		if (!$this->clearanceFilter->isValid()) {
			return false;
		}
	
		// The instantiated NPCs should also be valid.
		foreach ($this->getNPCs() as $npc) {
			if (!$npc->validate() == '') {
				return false;
			}
		}
	
		return true;
	}
	
	// Check if this mission has high priority. If any high-priority missions
	// are available, no others will be shown at landing or in the spaceport.
	// This is to be used for missions that are part of a series.
	public function hasPriority(): bool {
		return $this->hasPriority;
	}
	
	// Check if this mission is a "minor" mission. Minor missions will only be
	// offered if no other missions (minor or otherwise) are being offered.
	public function isMinor(): bool {
		return $this->isMinor;
	}
	
	public function isAtLocation(Location $location): bool {
		return ($this->location == $location);
	}
	
	// Information about what you are doing.
	public function getSourceShip(): Ship {
		return $this->sourceShip;
	}
	
	public function getDestination(): Planet {
		return $this->destination;
	}
	
	public function getWaypoints(): array {
		return $this->waypoints->toArray();
	}
	
	public function getVisitedWaypoints(): array {
		return $this->visitedWaypoints->toArray();
	}
	
	public function getStopovers(): array {
		return $this->stopovers->toArray();
	}
	
	public function getVisitedStopovers(): array {
		return $this->visitedStopovers->toArray();
	}
	
	public function getCargo(): string {
		return $this->cargo;
	}
	
	public function getCargoSize(): int {
		return $this->cargoSize;
	}
	
	public function getIllegalCargoFine(): int {
		return $this->illegalCargoFine;
	}
	
	public function getIllegalCargoMessage(): string {
		return $this->illegalCargoMessage;
	}
	
	public function getFailIfDiscovered(): bool {
		return $this->failIfDiscovered;
	}
	
	public function getPassengers(): int {
		return $this->passengers;
	}
	
	public function getDisplayedPayment(): int {
		return $this->paymentApparent ? $this->paymentApparent : $this->getAction(Trigger::COMPLETE)->getPayment();
	}
	
	public function getExpectedJumps(): int {
		return $this->expectedJumps;
	}
	
	// The mission must be completed by this deadline (if there is a deadline).
	public function getDeadline(): Date {
		return $this->deadline;
	}
	
	// If this mission's deadline was before the given date and it has not been
	// marked as failing already, mark it and return true.
	public function checkDeadline(Date $today) {
		if (!$this->hasFailed && $this->deadline && $this->deadline < $today) {
			$this->hasFailed = true;
			return true;
		}
		return false;
	}
	
	// Check if you have special clearance to land on your destination.
	public function hasClearance(Planet $planet): bool {
		if ($this->clearance == '') {
			return false;
		}
		if ($planet == $this->destination || $this->stopovers->contains($planet) || $this->visitedStopovers->contains($planet)) {
			return true;
		}
		return (!$this->clearanceFilter->isEmpty() && $this->clearanceFilter->matches($planet));
	}
	
	// Get the string to be shown in the destination planet's hailing dialog. If
	// this is "auto", you don't have to hail them to get landing permission.
	public function getClearanceMessage(): string {
		return $this->clearance;
	}
	
	// Check whether we have full clearance to land and use the planet's
	// services, or whether we are landing in secret ("infiltrating").
	public function getHasFullClearance(): bool {
		return $this->hasFullClearance;
	}
	
	// Check if it's possible to offer or complete this mission right now.
	public function canOffer(PlayerInfo $player, Ship $boardingShip): bool {
		if ($this->location == Location::BOARDING || $this->location == Location::ASSISTING) {
			if (!$boardingShip) {
				return false;
			}
	
			if (!$this->sourceFilter->matches($boardingShip)) {
				return false;
			}
		} else {
			if ($this->source && $this->source != $player->getPlanet()) {
				return false;
			}
	
			if (!$this->sourceFilter->matches($player->getPlanet())) {
				return false;
			}
		}
	
		$playerConditions = $player->getConditions();
		if (!$this->toOffer->test($playerConditions)) {
			return false;
		}
	
		if (!$this->toFail->isEmpty() && $this->toFail->test($playerConditions)) {
			return false;
		}
		
		$conditionCounts = array_count_values(array_keys($playerConditions));
		if ($this->repeat && $conditionCounts[$this->name . ": offered"] >= $this->repeat) {
			return false;
		}
		
		if (isset($this->actions[Trigger::OFFER->name]) && !$this->actions[Trigger::OFFER->name]->canBeDone($player, $boardingShip)) {
			return false;
		}
		if (isset($this->actions[Trigger::ACCEPT->name]) && !$this->actions[Trigger::ACCEPT->name]->canBeDone($player, $boardingShip)) {
			return false;
		}
		if (isset($this->actions[Trigger::DECLINE->name]) && !$this->actions[Trigger::DECLINE->name]->canBeDone($player, $boardingShip)) {
			return false;
		}
		if (isset($this->actions[Trigger::DEFER->name]) && !$this->actions[Trigger::DEFER->name]->canBeDone($player, $boardingShip)) {
			return false;
		}
	
		return true;
	}
	
	public function canAccept(PlayerInfo $player): bool {
		$playerConditions = $player->getConditions();
		if (!$this->toAccept->test($playerConditions)) {
			return false;
		}
	
		if (isset($this->actions[Trigger::OFFER]) && !$this->actions[Trigger::OFFER]->canBeDone($player)) {
			return false;
		}
		if (isset($this->actions[Trigger::ACCEPT]) && !$this->actions[Trigger::ACCEPT]->canBeDone($player)) {
			return false;
		}
		return $this->hasSpace($player);
	}
	
	public function hasSpace(PlayerInfo $player): bool {
		$extraCrew = 0;
		if ($player->getFlagship()) {
			$extraCrew = $player->getFlagship()->getCrew() - $player->getFlagship()->getRequiredCrew();
		}
		return ($this->cargoSize <= $player->getCargo()->getFree() + $player->getCargo()->getCommoditiesSize()
			&& $this->passengers <= $player->getCargo()->getBunksFree() + $extraCrew);
	}
	
	// Check if this mission's cargo can fit entirely on the referenced ship.
	public function shipHasSpace(Ship $ship): bool {
		return ($this->cargoSize <= $ship->getCargo()->getFree() && $this->passengers <= $ship->getCargo()->getBunksFree());
	}
	
	public function canComplete(PlayerInfo $player): bool {
		if ($player->getPlanet() != $this->destination) {
			return false;
		}
	
		return $this->isSatisfied($player);
	}
	
	// This function dictates whether missions on the player's map are shown in
	// bright or dim text colors, and may be called while in-flight or landed.
	public function isSatisfied(PlayerInfo $player): bool {
		if (count($this->waypoints) > 0 || count($this->stopovers) > 0) {
			return false;
		}
	
		// Test the completion conditions for this mission.
		if (!$this->toComplete->test($player->getConditions())) {
			return false;
		}
	
		// Determine if any fines or outfits that must be transferred, can.
		if (isset($this->actions[Trigger::COMPLETE]) && !$this->actions[Trigger::COMPLETE]->canBeDone($player)) {
			return false;
		}
	
		// NPCs which must be accompanied or evaded must be present (or not),
		// and any needed scans, boarding, or assisting must also be completed.
		foreach ($this->npcs as $npc) {
			if (!$npc->hasSucceeded($player->getSystem())) {
				return false;
			}
		}
	
		// If any of the cargo for this mission is being carried by a ship that is
		// not in this system, the mission cannot be completed right now.
		foreach ($player->getShips() as $ship) {
			// Skip in-system ships, and carried ships whose parent is in-system.
			if ($ship->getSystem() == $player->getSystem() || (!$ship->getSystem() && $ship->canBeCarried()
					&& $ship->getParent() && $ship->getParent()->getSystem() == $player->getSystem())) {
				continue;
			}
	
			if ($ship->getCargo()->getPassengers($this)) {
				return false;
			}
			// Check for all mission cargo, including that which has 0 mass.
			$cargo = $ship->getCargo()->getMissionCargo();
			if (isset($cargo[$this->name])) {
				return false;
			}
		}
	
		return true;
	}
	
	public function getOverridesCapture(): bool {
		return $this->overridesCapture;
	}
	
	// Mark a mission failed (e.g. due to a "fail" action in another mission).
	public function fail(): void {
		$this->hasFailed = true;
	}
	
	// Check if this mission recommends that the game be autosaved when it is
	// accepted. This should be set for main story line missions that have a
	// high chance of failing, such as escort missions.
	public function getRecommendsAutosave(): bool {
		return $this->autosave;
	}
	
	// Check if this mission is unique, i.e. not something that will be offered
	// over and over again in different variants.
	public function isUnique(): bool {
		return ($this->repeat == 1);
	}
	
	// Get a list of NPCs associated with this mission. Every time the player
	// takes off from a planet, they should be added to the active ships.
	public function getNPCs(): array {
		return $this->npcs;
	}
	
	// Get the internal name used for this mission. This name is unique and is
	// never modified by string substitution, so it can be used in condition
	// variables, etc.
	public function getIdentifier(): string {
		return $this->name;
	}
	
	// Get a specific mission action from this mission.
	// If a mission action is not found for the given trigger, returns an empty
	// mission action.
	public function getAction(Trigger $trigger): MissionAction {
		if (isset($this->actions[$trigger->name])) {
			return $this->actions[$trigger->name];
		} else {
			return new MissionAction();
		}
	}
	
	// For legacy code, contraband definitions can be placed in two different
	// locations, so move that parsing out to a helper function.
	public function parseContraband(DataNode $node): bool {
		if ($node->getToken(0) == "illegal" && $node->size() == 2) {
			$this->illegalCargoFine = $node->getValue(1);
		} else if ($node->getToken(0) == "illegal" && $node->size() == 3) {
			$this->illegalCargoFine = $node->getValue(1);
			$this->illegalCargoMessage = $node->getToken(2);
		} else if ($node->getToken(0) == "stealth") {
			$this->failIfDiscovered = true;
		} else {
			return false;
		}
	
		return true;
	}

}