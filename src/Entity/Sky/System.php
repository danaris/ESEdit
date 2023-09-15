<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use ApiPlatform\Metadata\ApiResource;

use App\Entity\DataNode;
use App\Entity\TemplatedArray;

use App\Service\TemplatedArrayService;

/**
 * A single star system on the Endless Sky galaxy map
 */
#[ORM\Entity]
#[ORM\Table(name: 'System')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class System {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	const STAR = "You cannot land on a star!";
	const HOTPLANET = "This planet is too hot to land on.";
	const COLDPLANET = "This planet is too cold to land on.";
	const UNINHABITEDPLANET = "This planet doesn't have anywhere you can land.";
	const HOTMOON = "This moon is too hot to land on.";
	const COLDMOON = "This moon is too cold to land on.";
	const UNINHABITEDMOON = "This moon doesn't have anywhere you can land.";
	const STATION = "This station cannot be docked with.";
	
	const DEFAULT_NEIGHBOR_DISTANCE = 100.0;
	
	#[ORM\Column(type: 'boolean', name: 'isDefined')]
	private bool $isDefined = false;
	
	#[ORM\Column(type: 'boolean', name: 'hasPosition')]
	private bool $hasPosition = false;
	
	// Name and position (within the star map) of this system.
	#[ORM\Column(type: 'string', name: 'name')]
	private string $name = '';
	
	#[ORM\Column]
	private string $positionStr;
	private Point $position;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Government')]
	#[ORM\JoinColumn(nullable: true, name: 'governmentId')]
	private ?Government $government = null;
	
	#[ORM\Column(type: 'string', name: 'music')]
	private string $music = '';
	
	// All possible hyperspace links to other systems.
	private array $links = []; //set<const System *>
	// Only those hyperspace links to other systems that are accessible.
	private array $accessibleLinks = []; //set<const System *>
	// Other systems that can be accessed from this system via a jump drive at various jump ranges.
	private array $neighbors = []; //map<float, set<const System *>>
	
	// Defines whether this system can be seen when not linked. A hidden system will
	// not appear when in view range, except when linked to a visited system.
	#[ORM\Column(type: 'boolean', name: 'hidden')]
	private bool $hidden = false;
	
	// Defines whether this system can be accessed or interacted with in any way.
	#[ORM\Column(type: 'boolean', name: 'inaccessible')]
	private bool $inaccessible = false;
	
	// Defines whether this system provides ramscoop even to ships that do not have any.
	#[ORM\Column(type: 'boolean', name: 'universalRamscoop')]
	private bool $universalRamscoop = true;
	
	// A value that is added to the ramscoop. It can be positive or negative.
	#[ORM\Column(type: 'float', name: 'ramscoopAddend')]
	private float $ramscoopAddend = 0.;
	
	// A multiplier applied to ramscoop in the system.
	#[ORM\Column(type: 'float', name: 'ramscoopMultiplier')]
	private float $ramscoopMultiplier = 1.;
	
	// Stellar objects, listed in such an order that an object's parents are
	// guaranteed to appear before it (so that if we traverse the vector in
	// order, updating positions, an object's parents will already be at the
	// proper position before that object is updated).
	#[ORM\OneToMany(mappedBy: 'system', targetEntity: StellarObject::class, cascade: ['persist'], fetch: 'EAGER')]
	private Collection $objects; //vector<StellarObject>
	private array $asteroids = []; //vector<Asteroid>
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sprite', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'hazeId')]
	private ?Sprite $haze = null;
	
	private array $fleets = []; //vector<RandomEvent<Fleet>>
	private array $hazards = []; //vector<RandomEvent<Hazard>>
	#[ORM\Column(type: 'float', name: 'habitable')]
	private float $habitable = 1000.;
	
	private array $belts = []; //WeightedList<float>
	#[ORM\Column(type: 'float', name: 'invisibleFenceRadius')]
	private float $invisibleFenceRadius = 10000.;
	
	#[ORM\Column(type: 'float', name: 'jumpRange')]
	private float $jumpRange = 0.;
	
	#[ORM\Column(type: 'float', name: 'solarPower')]
	private float $solarPower = 0.;
	
	#[ORM\Column(type: 'float', name: 'solarWind')]
	private float $solarWind = 0.;
	
	#[ORM\Column(type: 'float', name: 'starfieldDensity')]
	private float $starfieldDensity = 1.;
	
	#[ORM\Column(type: 'integer', name: 'minimumFleetPeriod')]
	private int $minimumFleetPeriod = 0;
	
	// The amount of additional distance that ships will arrive away from the
	// system center when entering this system through a hyperspace link.
	// Negative values are allowed, causing ships to jump beyond their target.
	#[ORM\Column(type: 'float', name: 'extraHyperArrivalDistance')]
	private float $extraHyperArrivalDistance = 0.;
	
	// The amount of additional distance that ships will arrive away from the
	// system center when entering this system through a jumpdrive jump.
	// Jump drives use a circle around the target for targeting, so a value below
	// 0 doesn't have the same meaning as for hyperdrives. Negative values will
	// be interpreted as positive values.
	#[ORM\Column(type: 'float', name: 'extraJumpArrivalDistance')]
	private float $extraJumpArrivalDistance = 0.;
	
	// The minimum distances from the system center to jump out of the system.
	#[ORM\Column(type: 'float', name: 'jumpDepartureDistance')]
	private float $jumpDepartureDistance = 0.;
	
	#[ORM\Column(type: 'float', name: 'hyperDepartureDistance')]
	private float $hyperDepartureDistance = 0.;
	
	// Commodity prices.
	private TemplatedArray $trade; //map<string, Price>
	
	// Attributes, for use in location filters.
	#[ORM\Column(type: 'string', name: 'attributesStr')]
	private string $attributesStr = '';
	private array $attributes = [];

    #[ORM\OneToMany(mappedBy: 'fromSystem', targetEntity: WormholeLink::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $wormholeFromLinks;

    #[ORM\OneToMany(mappedBy: 'toSystem', targetEntity: WormholeLink::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $wormholeToLinks; //set<string>
	
	#[ORM\OneToMany(mappedBy: 'fromSystem', targetEntity: SystemLink::class, orphanRemoval: true, cascade: ['persist'])]
	private PersistentCollection|ArrayCollection $fromLinks;
	
	#[ORM\OneToMany(mappedBy: 'toSystem', targetEntity: SystemLink::class, orphanRemoval: true, cascade: ['persist'])]
	private Collection $toLinks;

    #[ORM\OneToMany(mappedBy: 'fromSystem', targetEntity: SystemNeighbor::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $systemNeighbors;
	
	#[ORM\Column(type: 'string')]
	private string $sourceName = '';
	#[ORM\Column(type: 'string')]
	private string $sourceFile = '';
	#[ORM\Column(type: 'string')]
	private string $sourceVersion = '';
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->positionStr = json_encode($this->position);
		$this->attributesStr = json_encode($this->attributes);
		
		// $handledLinks = [];
		// foreach ($this->fromLinks as $FromLink) {
		// 	$handled = false;
		// 	$ToSystem = $FromLink->getToSystem();
		// 	if (in_array($ToSystem, $this->links) || in_array($ToSystem->getName(), $this->links)) {
		// 		$handled = true;
		// 		$handledLinks []= $FromLink->getToSystem()->getName();
		// 	}
		// 	if (!$handled) {
		// 		$eventArgs->getEntityManager()->remove($FromLink);
		// 	}
		// }
		// foreach ($this->links as $ToSystem) {
		// 	if (is_string($ToSystem)) {
		// 		$ToSystem = GameData::Systems()[$ToSystem];
		// 	}
		// 	if (in_array($ToSystem->getName(), $handledLinks)) {
		// 		continue;
		// 	}
		// 	$FromLink = new SystemLink();
		// 	$FromLink->setFromSystem($this);
		// 	$FromLink->setToSystem($ToSystem);
		// 	$eventArgs->getEntityManager()->persist($FromLink);
		// 	$this->fromLinks []= $FromLink;
		// }
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$positionArray = json_decode($this->positionStr, true);
		$this->position = new Point($positionArray['x'], $positionArray['y']);
		$this->attributes = json_decode($this->attributesStr);
		foreach ($this->systemNeighbors as $NeighbourInfo) {
			if (!isset($this->neighbors[$NeighbourInfo->getDistance()])) {
				$this->neighbors[$NeighbourInfo->getDistance()] = [];
			}
			$this->neighbors[$NeighbourInfo->getDistance()] []= $NeighbourInfo->getToSystem();
		}
		// foreach ($this->fromLinks as $FromLink) {
		// 	$this->links []= $FromLink->getToSystem();
		// }
	}
	
	public function __construct() {
		$this->position = new Point();
		$this->trade = TemplatedArrayService::Instance()->createTemplatedArray(Price::class);
		$this->wormholeFromLinks = new ArrayCollection();
		$this->wormholeToLinks = new ArrayCollection();
		$this->fromLinks = new ArrayCollection();
		$this->toLinks = new ArrayCollection();
		$this->objects = new ArrayCollection();
		$this->systemNeighbors = new ArrayCollection();
	}
	
	// Load a system's description.
	public function load(DataNode $node, TemplatedArray &$planets) {
		if ($node->size() < 2) {
			return;
		}
		$this->name = $node->getToken(1);
		$this->isDefined = true;
		if ($node->getSourceName()) {
			$this->sourceName = $node->getSourceName();
			$this->sourceFile = $node->getSourceFile();
			$this->sourceVersion = $node->getSourceVersion();
		}
	
		// For the following keys, if this data node defines a new value for that
		// key, the old values should be cleared (unless using the "add" keyword).
		$shouldOverwrite = ["asteroids", "attributes", "belt", "fleet", "link", "object", "hazard"];
	
		foreach ($node as $child) {
			// Check for the "add" or "remove" keyword.
			$add = ($child->getToken(0) == "add");
			$remove = ($child->getToken(0) == "remove");
			if (($add || $remove) && $child->size() < 2) {
				$child->printTrace("Skipping " + $child->getToken(0) + " with no key given:");
				continue;
			}
	
			// Get the key and value (if any).
			$key = $child->getToken(($add || $remove) ? 1 : 0);
			$valueIndex = ($add || $remove) ? 2 : 1;
			$hasValue = ($child->size() > $valueIndex);
			$value = $child->getToken($hasValue ? $valueIndex : 0);
	
			// Check for conditions that require clearing this key's current value.
			// "remove <key>" means to clear the key's previous contents.
			// "remove <key> <value>" means to remove just that value from the key.
			// "remove object" should only remove all if the node lacks children, as the children
			// of an object node are its values.
			$removeAll = ($remove && !$hasValue && !($key == "object" && $child->hasChildren()));
			// If this is the first entry for the given key, and we are not in "add"
			// or "remove" mode, its previous value should be cleared.
			$overwriteAll = (!$add && !$remove && in_array($key, $shouldOverwrite));
			$overwriteAll |= (!$add && !$remove && $key == "minables" && in_array('asteroids', $shouldOverwrite));
			// Clear the data of the given type.
			if ($removeAll || $overwriteAll) {
				// Clear the data of the given type.
				if ($key == "government") {
					$this->government = null;
				} else if ($key == "music") {
					$this->music = [];
				} else if ($key == "attributes") {
					$this->attributes = [];
				} else if ($key == "link") {
					$this->links = [];
				} else if ($key == "asteroids" || $key == "minables") {
					$this->asteroids = [];
				} else if ($key == "haze") {
					$this->haze = null;
				} else if ($key == "starfield density") {
					$this->starfieldDensity = 1.;
				} else if ($key == "ramscoop") {
					$this->universalRamscoop = true;
					$this->ramscoopAddend = 0.;
					$this->ramscoopMultiplier = 1.;
				} else if ($key == "trade") {
					$this->trade = [];
				} else if ($key == "fleet") {
					$this->fleets = [];
				} else if ($key == "hazard") {
					$this->hazards = [];
				} else if ($key == "belt") {
					$this->belts = [];
				} else if ($key == "object") {
					// Make sure any planets that were linked to this system know
					// that they are no longer here.
					foreach ($this->objects as $object) {
						if ($object->planet) {
							$planets[$object->planet->getTrueName()]->removeSystem($this);
						}
					}
	
					$this->objects->clear();
				} else if ($key == "hidden") {
					$this->hidden = false;
				} else if ($key == "inaccessible") {
					$this->inaccessible = false;
				}
	
				// If not in "overwrite" mode, move on to the next node.
				if ($overwriteAll) {
					$overwriteKey = $key;
					if ($key == 'minables') {
						$overwriteKey = 'asteroids';
					}
					$owIndex = array_search($overwriteKey, $shouldOverwrite);
					array_splice($shouldOverwrite, $owIndex, 1);
				} else {
					continue;
				}
			}
	
			// Handle the attributes without values.
			if ($key == "hidden") {
				$this->hidden = true;
			} else if ($key == "inaccessible") {
				$this->inaccessible = true;
			} else if ($key == "ramscoop") {
				foreach ($child as $grand) {
					$key = $grand->getToken(0);
					$hasValue = $grand->size() >= 2;
					if ($key == "universal" && $hasValue) {
						$this->universalRamscoop = $grand->boolValue(1);
					} else if ($key == "addend" && $hasValue) {
						$this->ramscoopAddend = $grand->getValue(1);
					} else if ($key == "multiplier" && $hasValue) {
						$this->ramscoopMultiplier = $grand->getValue(1);
					} else {
						$child->printTrace("Skipping unrecognized attribute:");
					}
				}
			} else if (!$hasValue && $key != "object") {
				$child->printTrace("Error: Expected key to have a value:");
				continue;
			// Handle the attributes which can be "removed."
			} else if ($key == "attributes") {
				if ($remove) {
					for ($i = $valueIndex; $i < $child->size(); ++$i) {
						unset($this->attributes[$child->getToken($i)]);
					}
				} else {
					for ($i = $valueIndex; $i < $child->size(); ++$i) {
						$this->attributes []= $child->getToken($i);
					}
				}
			} else if ($key == "link") {
				if ($remove) {
					$linkIndex = array_search($value, $this->links);
					if ($linkIndex !== false) {
						array_splice($this->links, $linkIndex, 1);
					}
				} else {
					//error_log('Defining link from '.$this->name.' to '.$value);
					$this->links []= $value;
				}
			} else if ($key == "asteroids") {
				if ($remove) {
					foreach ($this->asteroids as $index => $asteroid) {
						if ($asteroid->getName() == $value) {
							unset($this->asteroids[$index]);
							break;
						}
					}
				} else if ($child->size() >= 4) {
					$asteroid = new Asteroid(name: $value, count:$child->getValue($valueIndex + 1), energy:$child->getValue($valueIndex + 2));
					$this->asteroids []= $asteroid;
				} 
			} else if ($key == "minables") {
				$type = GameData::Minables()[$value];
				if ($remove) {
					foreach ($this->asteroids as $index => $asteroid) {
						if ($asteroid->getType() == $type) {
							unset($this->asteroids[$index]);
							break;
						}
					}
				} else if ($child->size() >= 4) {
					$asteroid = new Asteroid(type: $type, count:$child->getValue($valueIndex + 1), energy:$child->getValue($valueIndex + 2));
					$this->asteroids []= $asteroid;
				}
			} else if ($key == "fleet") {
				$fleet = GameData::Fleets()[$value];
				if ($remove) {
					foreach ($this->fleets as $index => $fleetData) {
						if ($fleetData['fleet'] == $fleet) {
							unset($this->fleets[$index]);
							break;
						}
					}
				} else {
					$fleetData = ['fleet'=>$fleet, 'period'=>$child->getValue($valueIndex + 1)];
					$this->fleets []= $fleetData;
				}
			} else if ($key == "hazard") {
				// $hazard = GameData::Hazards()[$value];
				// if ($remove) {
				// 	foreach ($this->hazards as $index => $hazardData) {
				// 		if ($hazardData['hazard']->get() == $hazard) {
				// 			unset($this->hazards[$index]);
				// 			break;
				// 		}
				// 	}
				// } else {
				// 	$hazardData = ['hazard'=>$hazard, 'period'=>$child->getValue($valueIndex + 1)];
				// 	$this->hazards []= $hazardData;
				// }
			} else if ($key == "belt") {
				// TODO: WeightedLists are a bit weird; we'll get to those later
				// $radius = $child->getValue($valueIndex);
				// if ($remove) {
				// 	erase(belts, radius);
				// } else {
				// {
				// 	int weight = ($child->size() >= valueIndex + 2) ? max<int>(1, $child->getValue($valueIndex + 1)) : 1;
				// 	belts.emplace_back(weight, radius);
				// }
			} else if ($key == "object") {
				if ($remove) {
					$toRemoveTemplate = new StellarObject();
					foreach ($child as $grand) {
						$this->loadObjectHelper($grand, $toRemoveTemplate, true);
					}
					
					$toRemoveIndices = [];
					foreach ($this->objects as $index => $object) {
						if ($toRemoveTemplate->getSprite() == $object->getSprite() &&
							$toRemoveTemplate->getDistance() == $object->distance &&
							$toRemoveTemplate->getSpeed() == $object->speed &&
							$toRemoveTemplate->getOffset() == $object->offset) {
							$toRemoveIndices []= $index;
						} 
					}
					
					if (count($toRemoveIndices) == 0) {
						$child->printTrace("Warning: Did not find matching object for specified operation:");
					}
					
					foreach ($toRemoveIndices as $index) {
						$object = $this->objects[$index];
						if ($object->planet) {
							$planets[$object->planet->getTrueName()]->removeSystem($this);
						}
						unset($this->objects[$index]);
					}
				} else {
					$this->loadObject($child, $planets, null);
				}
			// Handle the attributes which cannot be "removed."
			} else if ($remove) {
				$child->printTrace("Cannot \"remove\" a specific value from the given key:");
				continue;
			} else if ($key == "pos" && $child->size() >= 3) {
				$this->position->set($child->getValue($valueIndex), $child->getValue($valueIndex + 1));
				$this->hasPosition = true;
			} else if ($key == "government") {
				$this->government = GameData::Governments()[$value];
			} else if ($key == "music") {
				$this->music = $value;
			} else if ($key == "habitable") {
				$this->habitable = $child->getValue($valueIndex);
			} else if ($key == "jump range") {
				$this->jumpRange = max(0., $child->getValue($valueIndex));
			} else if ($key == "haze") {
				$this->haze = SpriteSet::Get($value);
			} else if ($key == "starfield density") {
				$this->starfieldDensity = $child->getValue($valueIndex);
			} else if ($key == "trade" && $child->size() >= 3) {
				$this->trade[$value]->setBase($child->getValue($valueIndex + 1));
				$this->trade[$value]->setName($value);
			} else if ($key == "arrival") {
				if ($child->size() >= 2) {
					$this->extraHyperArrivalDistance = $child->getValue(1);
					$this->extraJumpArrivalDistance = abs($child->getValue(1));
				}
				foreach ($child as $grand) {
					$type = $grand->getToken(0);
					if ($type == "link" && $grand->size() >= 2) {
						$this->extraHyperArrivalDistance = $grand->getValue(1);
					} else if ($type == "jump" && $grand->size() >= 2) {
						$this->extraJumpArrivalDistance = abs($grand->getValue(1));
					} else {
						$grand->printTrace("Warning: Skipping unsupported arrival distance limitation:");
					}
				}
			} else if ($key == "departure") {
				if ($child->size() >= 2) {
					$this->jumpDepartureDistance = $child->getValue(1);
					$this->hyperDepartureDistance = abs($child->getValue(1));
				}
				foreach ($child as $grand) {
					$type = $grand->getToken(0);
					if ($type == "link" && $grand->size() >= 2) {
						$this->hyperDepartureDistance = $grand->getValue(1);
					} else if ($type == "jump" && $grand->size() >= 2) {
						$this->jumpDepartureDistance = abs($grand->getValue(1));
					} else {
						$grand->printTrace("Warning: Skipping unsupported departure distance limitation:");
					}
				}
			} else if ($key == "invisible fence" && $child->size() >= 2) {
				$this->invisibleFenceRadius = max(0., $child->getValue(1));
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	
		// Set planet messages based on what zone they are in.
		foreach ($this->objects as $object) {
			if ($object->message || $object->planet) {
				continue;
			}
	
			$root = $object;
			$times = 0;
			while ($root->getParent()) {
				$root = $root->getParent();
				$times++;
				if ($times > 3) {
					error_log('Tried going back '.$times.' times');
				}
			}
	
			$fraction = $root->distance / $this->habitable;
			if ($object->isStar) {
				$object->message = self::STAR;
			} else if ($object->isStation) {
				$object->message = self::STATION;
			} else if ($object->isMoon) {
				if ($fraction < .5) {
					$object->message = self::HOTMOON;
				} else if ($fraction >= 2.) {
					$object->message = self::COLDMOON;
				} else {
					$object->message = self::UNINHABITEDMOON;
				}
			} else {
				if ($fraction < .5) {
					$object->message = self::HOTPLANET;
				} else if ($fraction >= 2.) {
					$object->message = self::COLDPLANET;
				} else {
					$object->message = self::UNINHABITEDPLANET;
				}
			}
		}
		// Print a warning if this system wasn't explicitly given a position.
		if (!$this->hasPosition) {
			$node->printTrace("Warning: system will be ignored due to missing position:");
		}
		// Systems without an asteroid belt defined default to a radius of 1500.
		if (count($this->belts) == 0) {
			// TODO: we still don't have the infrastructure for this
			//belts.emplace_back(1, 1500.);
		}
	}
	
	// Update any information about the system that may have changed due to events,
	// or because the game was started, e.g. neighbors, solar wind and power, or
	// if the system is inhabited.
	public function updateSystem(TemplatedArray &$systems, array $neighborDistances): void {
		//$this->accessibleLinks = [];
		$this->neighbors = [];
	
		// Some systems in the game may be considered inaccessible. If this system is inaccessible,
		// then it shouldn't have accessible links or jump neighbors.
		if ($this->inaccessible) {
			return;
		}
	
		// If linked systems are inaccessible, then they shouldn't be a part of the accessible links
		// set that gets used for navigation and other purposes.
		// foreach ($this->links as $ToSystem) {
		// 	if (is_string($ToSystem)) {
		// 		$ToSystem = GameData::Systems()[$ToSystem];
		// 	}
		// 	if ($ToSystem && !$ToSystem->isInaccessible()) {
		// 		error_log('Adding link from '.$this->name.' to '.$ToSystem->getName());
		// 		$this->accessibleLinks []= $ToSystem;
		// 	} else {
		// 		error_log('Link from '.$this->name.' to '.$ToSystem->getName().' is inaccessible');
		// 	}
		// }
		
		foreach ($this->links as $ToSystem) {
			if (is_string($ToSystem)) {
				$ToSystem = GameData::Systems()[$ToSystem];
			}
			// if (in_array($ToSystem->getName(), $handledLinks)) {
			// 	continue;
			// }
			$FromLink = new SystemLink();
			$FromLink->setFromSystem($this);
			$FromLink->setToSystem($ToSystem);
			if (!$ToSystem->isInaccessible()) {
				$FromLink->setAccessible(true);
			} else {
				error_log('Link from '.$this->name.' to '.$ToSystem->getName().' is inaccessible');
				$FromLink->setAccessible(false);
			}
			//$eventArgs->getEntityManager()->persist($FromLink);
			$this->fromLinks []= $FromLink;
		}
	
		// Neighbors are cached for each system for the purpose of quicker
		// pathfinding. If this system has a static jump range then that
		// is the only range that we need to create jump neighbors for, but
		// otherwise we must create a set of neighbors for every potential
		// jump range that can be encountered.
		if ($this->jumpRange) {
                                                   
			$this->updateNeighbors($systems, $this->jumpRange);
			// Systems with a static jump range must also create a set for
			// the DEFAULT_NEIGHBOR_DISTANCE to be returned for those systems
			// which are visible from it.
			$this->updateNeighbors($systems, self::DEFAULT_NEIGHBOR_DISTANCE);
		} else {
			foreach ($neighborDistances as $distance) {
				$this->updateNeighbors($systems, $distance);
			}
		}
		
		foreach ($this->neighbors as $distance => $neighborSystems) {
			foreach ($neighborSystems as $ToSystem) {
				$NeighbourInfo = new SystemNeighbor();
				$NeighbourInfo->setFromSystem($this);
				$NeighbourInfo->setToSystem($ToSystem);
				$NeighbourInfo->setDistance($distance);
				$this->systemNeighbors []= $NeighbourInfo;
			}
		}
	
		// Calculate the solar power and solar wind.
		// 		$this->solarPower = 0.;
		// 		$this->solarWind = 0.;
		// 		foreach ($this->objects as $object) {
		// 
		// 			$this->solarPower += GameData::SolarPower($object->getSprite());
		// 			$this->solarWind += GameData::SolarWind($object->getSprite());
		// 		}
	
		// Systems only have a single auto-attribute, "uninhabited." It is set if
		// the system has no inhabited planets that are accessible to all ships.
		if ($this->isInhabited(null)) {
			$attrIndex = array_search('uninhabited', $this->attributes);
			if ($attrIndex !== false) {
				array_splice($this->attributes, $attrIndex, 1);
			}
		} else {
			$this->attributes []= "uninhabited";
		}
	
		// Calculate the smallest arrival period of a fleet (or 0 if no fleets arrive)
		$this->minimumFleetPeriod = PHP_INT_MAX;
		foreach ($this->fleets as $fleetData) {
			$this->minimumFleetPeriod = min($this->minimumFleetPeriod, $fleetData['period']);
		}
		if ($this->minimumFleetPeriod == PHP_INT_MAX) {
			$this->minimumFleetPeriod = 0;
		}
	}
// 	
// 	// Modify a system's links.
// 	void System::Link(System *other)
// 	{
// 		links.insert(other);
// 		other->links.insert(this);
// 		// accessibleLinks will be updated when UpdateSystem is called.
// 	}
// 	
// 	void System::Unlink(System *other)
// 	{
// 		links.erase(other);
// 		other->links.erase(this);
// 		// accessibleLinks will be updated when UpdateSystem is called.
// 	}

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
	
	// Check that this system has been loaded and given a position.
	public function isValid(): bool {
		return $this->isDefined && $this->hasPosition;
	}
	
	// Get this system's name.
	public function getName(): string {
		return $this->name;
	}
	
	public function setName(string $name): void {
		$this->name = $name;
	}
	
	// Get this system's position in the star map.
	public function getPosition(): Point {
		return $this->position;
	}
	
	// Get this system's government.
	public function getGovernment(): Government {;
		return $this->government ? $this->government : new Government();
	}
	// 
	// 
	// // Get the name of the ambient audio to play in this system.
	// const string &System::MusicName() const
	// {
	// 	return music;
	// }
	// 
	// Get the list of "attributes" of the planet.
	public function getAttributes(): array { // set<string>
		return $this->attributes;
	}
	
	// Get a list of systems you can travel to through hyperspace from here.
	public function getLinks(): array { // set<const System *>
		if (!$this->inaccessible && count($this->accessibleLinks) == 0) {
			$criteria = Criteria::create()
				->where(Criteria::expr()->eq('accessible', true));
			
			$links = $this->fromLinks->matching($criteria);
			foreach ($links as $FromLink) {
				$this->accessibleLinks []= $FromLink->getToSystem();
			}
		}
		return $this->accessibleLinks;
	}
	// 
	// // Get a list of systems that can be jumped to from here with the given
	// // jump distance, whether or not there is a direct hyperspace link to them.
	// // If this system has its own jump range, then it will always return the
	// // systems within that jump range instead of the jump range given.
	// const set<const System *> &System::JumpNeighbors(double neighborDistance) const
	// {
	// 	static const set<const System *> EMPTY;
	// 	const auto it = neighbors.find(jumpRange ? jumpRange : neighborDistance);
	// 	return it == neighbors.end() ? EMPTY : it->second;
	// }
	// 
	// // Defines whether this system can be seen when not linked. A hidden system will
	// // not appear when in view range, except when linked to a visited system.
	public function isHidden(): bool {
		return $this->hidden;
	}
	// 
	// // Return how much ramscoop is generated by this system, depending on the given ship ramscoop value.
	// double System::RamscoopFuel(double shipRamscoop, double scale) const
	// {
	// 	// Even if a ship has no ramscoop, it can harvest a tiny bit of fuel by flying close to the star,
	// 	// provided the system allows it. Both the system and the gamerule must allow the universal ramscoop
	// 	// in order for it to function.
	// 	double universal = 0.05 * scale * universalRamscoop * GameData::GetGamerules().UniversalRamscoopActive();
	// 	return max(0., SolarWind() * .03 * scale * ramscoopMultiplier * (sqrt(shipRamscoop) + universal) + ramscoopAddend);
	// }
	
	// Defines whether this system can be accessed or interacted with in any way.
	public function isInaccessible(): bool {
		return $this->inaccessible;
	}
// 	
// 	// Additional travel distance to target for ships entering through hyperspace.
// 	double System::ExtraHyperArrivalDistance() const
// 	{
// 		return extraHyperArrivalDistance;
// 	}
// 	
// 	// Additional travel distance to target for ships entering using a jumpdrive.
// 	double System::ExtraJumpArrivalDistance() const
// 	{
// 		return extraJumpArrivalDistance;
// 	}
// 	
// 	double System::JumpDepartureDistance() const
// 	{
// 		return jumpDepartureDistance;
// 	}
// 	
// 	double System::HyperDepartureDistance() const
// 	{
// 		return hyperDepartureDistance;
// 	}
// 	
// 	// Get a list of systems you can "see" from here, whether or not there is a
// 	// direct hyperspace link to them.
// 	const set<const System *> &System::VisibleNeighbors() const
// 	{
// 		static const set<const System *> EMPTY;
// 		const auto it = neighbors.find(DEFAULT_NEIGHBOR_DISTANCE);
// 		return it == neighbors.end() ? EMPTY : it->second;
// 	}
// 	
// 	// Move the stellar objects to their positions on the given date.
// 	void System::SetDate(const Date &date)
// 	{
// 		double now = date.DaysSinceEpoch();
// 	
// 		for (StellarObject &object : objects) {
// 
// 			// "offset" is used to allow binary orbits; the second object is offset
// 			// by 180 degrees.
// 			object.angle = Angle(now * object.speed + object.offset);
// 			object.position = object.angle.Unit() * object.distance;
// 	
// 			// Because of the order of the vector, the parent's position has always
// 			// been updated before this loop reaches any of its children, so:
// 			if (object.parent >= 0) {
// 				object.position += objects[object.parent].position;
// 	
// 			if (object.position) {
// 				object.angle = Angle(object.position);
// 	
// 			if (object.planet) {
// 				object.planet->ResetDefense();
// 		}
// 	}
// 	
	// Get the stellar object locations on the most recently set date.
	public function getObjects(): array {
		return $this->objects->toArray();
	}
	
	public function getObjectsByIndex(): array {
		$objectArray = [];
		foreach ($this->objects as $Object) {
			$objectArray[$Object->getIndex()] = $Object;
		}
		
		return $objectArray;
	}
// 	
	// Get the stellar object (if any) for the given planet.
	public function findStellar(?Planet $planet): ?StellarObject {
		if ($planet) {
			foreach ($this->objects as $object) {
				if ($object->getPlanet() == $planet) {
					return $object;
				}
			}
		}
	
		return null;
	}
// 	
// 	// Get the habitable zone's center.
// 	double System::HabitableZone() const
// 	{
// 		return habitable;
// 	}
// 	
// 	// Get the radius of an asteroid belt.
// 	double System::AsteroidBeltRadius() const
// 	{
// 		return belts.Get();
// 	}
// 	
// 	// Get the list of asteroid belts.
// 	const WeightedList<double> &System::AsteroidBelts() const
// 	{
// 		return belts;
// 	}
// 	
// 	// Get the system's invisible fence radius.
// 	double System::InvisibleFenceRadius() const
// 	{
// 		return invisibleFenceRadius;
// 	}
// 	
// 	// Get how far ships can jump from this system.
// 	double System::JumpRange() const
// 	{
// 		return jumpRange;
// 	}
// 	
// 	// Get the rate of solar collection and ramscoop refueling.
// 	double System::SolarPower() const
// 	{
// 		return solarPower;
// 	}
// 	
// 	double System::SolarWind() const
// 	{
// 		return solarWind;
// 	}
// 	
// 	double System::StarfieldDensity() const
// 	{
// 		return starfieldDensity;
// 	}
// 	
	// Check if this system is inhabited.
	public function isInhabited(?Ship $ship = null): bool {
		foreach ($this->objects as $object) {
			if ($object->hasSprite() && $object->hasValidPlanet()) {
				$planet = $object->getPlanet();
				if (!$planet->isWormhole() && $planet->isInhabited() && $planet->isAccessible($ship)) {
					return true;
				}
			}
		}
		return false;
	}
// 	
// 	// Check if ships of the given government can refuel in this system.
// 	bool System::HasFuelFor (const Ship &ship) const
// 	{
// 		for (const StellarObject &object : objects) {
// 			if (object.HasSprite() && object.HasValidPlanet() && object.GetPlanet()->HasFuelFor (ship)) {
// 				return true;
// 	
// 		return false;
// 	}
// 	
// 	// Check whether you can buy or sell ships in this system.
// 	bool System::HasShipyard() const
// 	{
// 		for (const StellarObject &object : objects) {
// 			if (object.HasSprite() && object.HasValidPlanet() && object.GetPlanet()->HasShipyard()) {
// 				return true;
// 	
// 		return false;
// 	}
// 	
// 	// Check whether you can buy or sell ship outfits in this system.
// 	bool System::HasOutfitter() const
// 	{
// 		for (const StellarObject &object : objects) {
// 			if (object.HasSprite() && object.HasValidPlanet() && object.GetPlanet()->HasOutfitter()) {
// 				return true;
// 	
// 		return false;
// 	}
// 	
// 	// Get the specification of how many asteroids of each type there are.
// 	const vector<System::Asteroid> &System::Asteroids() const
// 	{
// 		return asteroids;
// 	}
// 	
	// Get the background haze sprite for this system.
	public function getHaze(): ?Sprite {
		return $this->haze;
	}
// 	
// 	// Get the price of the given commodity in this system.
// 	int System::Trade(const string &commodity) const
// 	{
// 		auto it = trade.find(commodity);
// 		return (it == trade.end()) ? 0 : it->second.price;
// 	}
// 	
// 	bool System::HasTrade() const
// 	{
// 		return !trade.empty();
// 	}
// 	
// 	// Update the economy.
// 	void System::StepEconomy()
// 	{
// 		for (auto &it : trade) {
// 
// 			it.second.exports = EXPORT * it.second.supply;
// 			it.second.supply *= KEEP;
// 			it.second.supply += Random::Normal() * VOLUME;
// 			it.second.Update();
// 		}
// 	}
// 	
// 	void System::SetSupply(const string &commodity, double tons)
// 	{
// 		auto it = trade.find(commodity);
// 		if (it == trade.end()) {
// 			return;
// 	
// 		it->second.supply = tons;
// 		it->second.Update();
// 	}
// 	
// 	double System::Supply(const string &commodity) const
// 	{
// 		auto it = trade.find(commodity);
// 		return (it == trade.end()) ? 0 : it->second.supply;
// 	}
// 	
// 	double System::Exports(const string &commodity) const
// 	{
// 		auto it = trade.find(commodity);
// 		return (it == trade.end()) ? 0 : it->second.exports;
// 	}
// 	
// 	// Get the probabilities of various fleets entering this system.
// 	const vector<RandomEvent<Fleet>> &System::Fleets() const
// 	{
// 		return fleets;
// 	}
// 	
// 	// Get the probabilities of various hazards in this system.
// 	const vector<RandomEvent<Hazard>> &System::Hazards() const
// 	{
// 		return hazards;
// 	}
	
	// Check how dangerous this system is (credits worth of enemy ships jumping
	// in per frame).
	public function getDanger(): float {
		$danger = 0.;
		foreach ($this->fleets as $fleetData) {
			$gov = $fleetData['fleet']->getGovernment();
			if ($gov && $gov->isEnemy()) {
				$danger += $$fleetData['fleet']->get()->getStrength() / $$fleetData['fleet']->getPeriod();
			}
		}
		return $danger;
	}
	
	public function getMinimumFleetPeriod(): int {
		return $this->minimumFleetPeriod;
	}
	
	public function loadObject(DataNode $node, TemplatedArray &$planets, ?StellarObject $parent): void {
		$index = count($this->objects);
		$object = new StellarObject();
		$object->setSystem($this);
		$object->setIndex($index);
		$this->objects []= $object;
		if ($parent) {
			$parent->addChild($object);
			$object->setParentIndex($parent->getIndex());
		}
	
		$isAdded = ($node->getToken(0) == "add");
		if ($node->size() >= 2 + $isAdded) {
			$planet = $planets[$node->getToken(1 + $isAdded)];
			$object->planet = $planet;
			$planet->setSystem($this);
		}
	
		foreach ($node as $child) {
			if ($child->getToken(0) == "hazard" && $child->size() >= 3) {
				$hazard = GameData::Hazards()[$child->getToken(1)];
				for ($i=0; $i<intval($child->getValue(2)); $i++) {
					$object->hazards []= $hazard;
				}
			} else if ($child->getToken(0) == "object") {
				$this->loadObject($child, $planets, $object);
			} else {
				$this->loadObjectHelper($child, $object, false);
			}
		}
	}
	
	public function loadObjectHelper(DataNode $node, StellarObject &$object, bool $removing): void {
		$key = $node->getToken(0);
		$hasValue = ($node->size() >= 2);
		if ($key == "sprite" && $hasValue) {
			$object->loadSprite($node);
			if ($removing) {
				return;
			}
			$object->isStar = substr($node->getToken(1), 0, 5) == "star/";
			if (!$object->isStar) {
				$object->isStation = substr($node->getToken(1), 0, 14) == "planet/station";
				$object->isMoon = (!$object->isStation && $object->getParent() && !$object->getParent()->isStar);
			}
		} else if ($key == "distance" && $hasValue) {
			$object->distance = $node->getValue(1);
		} else if ($key == "period" && $hasValue) {
			$object->speed = 360. / $node->getValue(1);
		} else if ($key == "offset" && $hasValue) {
			$object->offset = $node->getValue(1);
		} else if ($removing && ($key == "hazard" || $key == "object")) {
			$node->printTrace("Key \"" + $key + "\" cannot be removed from an object:");
		} else {
			$node->printTrace("Skipping unrecognized attribute:");
		}
	}
	
	// Once the star map is fully loaded or an event has changed systems
	// or links, figure out which stars are "neighbors" of this one, i.e.
	// close enough to see or to reach via jump drive.
	public function updateNeighbors(TemplatedArray &$systems, float $distance): void {
		if (!isset($this->neighbors[$distance])) {
			$this->neighbors[$distance] = [];
		}
		// Every accessible star system that is linked to this one is automatically a neighbor,
		// even if it is farther away than the maximum distance.
		foreach ($this->getLinks() as $linkSystem) {
			$this->neighbors[$distance] []= $linkSystem;
		}
	
		// Any other star system that is within the neighbor distance is also a
		// neighbor.
		foreach ($systems as $otherSystem) {
			// Skip systems that have no name or that are inaccessible.
			if ($otherSystem->getName() == '' || $otherSystem->isInaccessible()) {
				continue;
			}
	
			if ($otherSystem != $this && $otherSystem->getPosition()->getDistance($this->position) <= $distance) {
				$this->neighbors[$distance] []= $otherSystem;
			}
		}
	}

    /**
     * @return Collection<int, WormholeLink>
     */
    public function getWormholeFromLinks(): Collection
    {
        return $this->wormholeFromLinks;
    }

    public function addWormholeFromLink(WormholeLink $wormholeFromLink): self
    {
        if (!$this->wormholeFromLinks->contains($wormholeFromLink)) {
            $this->wormholeFromLinks->add($wormholeFromLink);
            $wormholeFromLink->setFromSystem($this);
        }

        return $this;
    }

    public function removeWormholeFromLink(WormholeLink $wormholeFromLink): self
    {
        if ($this->wormholeFromLinks->removeElement($wormholeFromLink)) {
            // set the owning side to null (unless already changed)
            if ($wormholeFromLink->getFromSystem() === $this) {
                $wormholeFromLink->setFromSystem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WormholeLink>
     */
    public function getWormholeToLinks(): Collection
    {
        return $this->wormholeToLinks;
    }

    public function addWormholeToLink(WormholeLink $wormholeToLink): self
    {
        if (!$this->wormholeToLinks->contains($wormholeToLink)) {
            $this->wormholeToLinks->add($wormholeToLink);
            $wormholeToLink->setToSystem($this);
        }

        return $this;
    }

    public function removeWormholeToLink(WormholeLink $wormholeToLink): self
    {
        if ($this->wormholeToLinks->removeElement($wormholeToLink)) {
            // set the owning side to null (unless already changed)
            if ($wormholeToLink->getToSystem() === $this) {
                $wormholeToLink->setToSystem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SystemNeighbor>
     */
    public function getSystemNeighbors(): Collection
    {
        return $this->systemNeighbors;
    }

    public function addSystemNeighbor(SystemNeighbor $systemNeighbor): static
    {
        if (!$this->systemNeighbors->contains($systemNeighbor)) {
            $this->systemNeighbors->add($systemNeighbor);
            $systemNeighbor->setFromSystem($this);
        }

        return $this;
    }

    public function removeSystemNeighbor(SystemNeighbor $systemNeighbor): static
    {
        if ($this->systemNeighbors->removeElement($systemNeighbor)) {
            // set the owning side to null (unless already changed)
            if ($systemNeighbor->getFromSystem() === $this) {
                $systemNeighbor->setFromSystem(null);
            }
        }

        return $this;
    }
	
	public function toJSON($justArray=false): string|array {
		$jsonArray = [];
		
		//error_log('-% Setting basic data');
		$jsonArray['name'] = $this->name;
		$jsonArray['position'] = ['x'=>$this->position->X(), 'y'=>$this->position->Y()];
		$jsonArray['government'] = $this->government ? $this->government->getTrueName() : 'Uninhabited';
		$jsonArray['music'] = $this->music;
		$jsonArray['hidden'] = $this->hidden;
		$jsonArray['inaccessible'] = $this->inaccessible;
		$jsonArray['inhabited'] = $this->isInhabited(null);
		$jsonArray['universalRamscoop'] = $this->universalRamscoop;
		$jsonArray['ramscoopAddend'] = $this->ramscoopAddend;
		$jsonArray['ramscoopMultiplier'] = $this->ramscoopMultiplier;
		$jsonArray['hazeId'] = $this->haze ? $this->haze->getId() : null;
		$jsonArray['invisibleFenceRadius'] = $this->invisibleFenceRadius;
		$jsonArray['jumpRange'] = $this->jumpRange;
		$jsonArray['attributes'] = $this->attributes;
		
		//error_log('-% Setting links');
		$jsonArray['links'] = [];
		foreach ($this->getLinks() as $ToSystem) {
			$jsonArray['links'] []= $ToSystem->getName();
		}
		//error_log('-% Setting objects');
		$jsonArray['objects'] = [];
		foreach ($this->getObjects() as $StellarObject) {
			if (!$StellarObject->getParent()) {
				$jsonArray['objects'] []= $StellarObject->toJSON(true);
			}
		}
		//error_log('-% Setting wormholes');
		$jsonArray['wormholeFromLinks'] = [];
		foreach ($this->wormholeFromLinks as $WormholeLink) {
			$jsonArray['wormholeFromLinks'] []= ['wormhole'=>$WormholeLink->getWormhole()->getTrueName(), 'toSystem'=>$WormholeLink->getToSystem()->getName()];
		}
		//error_log('-% Setting neighbors');
		$jsonArray['neighbors'] = [];
		foreach ($this->neighbors as $distance => $neighbors) {
			$jsonArray['neighbors'][$distance] = [];
			foreach ($neighbors as $NeighborSystem) {
				$jsonArray['neighbors'][$distance] []= $NeighborSystem->getName();
			}
		}
		//error_log('-% Returning');
		
		$jsonArray['source'] = ['name'=>$this->sourceName,'file'=>$this->sourceFile,'version'=>$this->sourceVersion];
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}
}

function erf($x) {
		$pi = 3.1415927;
		$a = (8*($pi - 3))/(3*$pi*(4 - $pi));
		$x2 = $x * $x;

		$ax2 = $a * $x2;
		$num = (4/$pi) + $ax2;
		$denom = 1 + $ax2;

		$inner = (-$x2)*$num/$denom;
		$erf2 = 1 - exp($inner);

		return sqrt($erf2);
}

#[ORM\Entity]
#[ORM\Table(name: 'Price')]
class Price {
	const KEEP = .89;
	const EXPORT = .10;
	// Standard deviation of the daily production of each commodity:
	const VOLUME = 2000.;
	// Above this supply amount, price differences taper off:
	const LIMIT = 20000.;
	
	public function setBase(int $base): void {
		$this->base = $base;
		$this->price = $base;
	}
	
	public function setName(string $name): void {
		$this->name = $name;
	}
	
	public function update(): void {
		$this->price = $this->base + intval(-100. * erf($this->supply / self::LIMIT));
	}
	
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string')]
	private string $name = '';

	#[ORM\Column(type: 'integer')]
	public int $base = 0;
	#[ORM\Column(type: 'integer')]
	public int $price = 0;
	#[ORM\Column(type: 'float')]
	public float $supply = 0.;
	#[ORM\Column(type: 'float')]
	public float $exports = 0.;
};

#[ORM\Entity]
#[ORM\Table(name: 'Asteroid')]
class Asteroid {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string')]
	private string $name = '';
	private ?Minable $type = null;
	#[ORM\Column(type: 'integer')]
	private int $count;
	#[ORM\Column(type: 'float')]
	private float $energy;

	public function __construct(?string $name = null, ?Minable $type = null, int $count = 0, float $energy = 0.0) {
		$this->count = $count;
		$this->energy = $energy;
		if ($name) {
			$this->name = $name;
		} else if ($type) {
			$this->type = $type;
		}
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function getType(): ?Minable {
		return $this->type;
	}
	
	public function getCount(): int {
		return $this->count;
	}
	
	public function getEnergy(): float {
		return $this->energy;
	}

};