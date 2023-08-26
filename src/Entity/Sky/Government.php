<?php

namespace App\Entity\Sky;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;

// Class representing a government. Each ship belongs to some government, and
// attacking that ship will provoke its ally governments and reduce your
// reputation with them, but increase your reputation with that ship's enemies.
// The ships for each government are identified by drawing them with a different
// color "swizzle." Some government's ships can also be easier or harder to
// bribe than others.
#[ORM\Entity]
#[ORM\Table(name: 'Government')]
#[ORM\HasLifecycleCallbacks]
class Government {
	#[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

	#[ORM\Column(type: 'string', name: 'name')]
    private string $name = '';

	#[ORM\Column(type: 'string', name: 'displayName')]
    private string $displayName = '';

	#[ORM\Column(type: 'integer', name: 'swizzle')]
    private int $swizzle = 0;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Color', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, name: 'colorId')]
    private ?Color $color = null;
 // ExclusiveItem<Color>
	
	#[ORM\Column(type: 'string')]
    private string $attitudeTowardStr = '';
	private array $attitudeToward = []; // vector<float>
	
	// These are both pretty niche and not really worth worrying about right now
	private array $trusted = []; // set<const Government *>
	private array $customPenalties = []; // map<unsigned, map<int, float>>
	
	#[ORM\Column(type: 'float', name: 'initialPlayerReputation')]
    private float $initialPlayerReputation = 0.;

	#[ORM\Column(type: 'float', name: 'reputationMax')]
    private float $reputationMax = PHP_FLOAT_MAX;

	#[ORM\Column(type: 'float', name: 'reputationMin')]
    private float $reputationMin = PHP_FLOAT_MIN;

	#[ORM\OneToMany(mappedBy: 'government', targetEntity: GovernmentPenalty::class, orphanRemoval: true, cascade: ['persist'])]
	private Collection $penaltyForObject;
	private array $penaltyFor = []; // map<int, float>
	
	// This object takes care of both illegals and atrocities
	#[ORM\OneToMany(mappedBy: 'government', targetEntity: OutfitPenalty::class, orphanRemoval: true, cascade: ['persist'])]
	private Collection $outfitPenalties;
	private array $illegals = []; // map<const Outfit*, int>
	private array $atrocities = []; // map<const Outfit*, bool>
	#[ORM\Column(type: 'float', name: 'bribe')]
    private float $bribe = 0.;

	#[ORM\Column(type: 'float', name: 'fine')]
    private float $fine = 1.;

	private array $enforcementZones = []; // vector<LocationFilter>
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Conversation', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, name: 'deathSentenceId')]
    private ?Conversation $deathSentence = null;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Phrase', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, name: 'friendlyHailId')]
    private ?Phrase $friendlyHail = null;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Phrase', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, name: 'friendlyDisabledHailId')]
    private ?Phrase $friendlyDisabledHail = null;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Phrase', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, name: 'hostileHailId')]
    private ?Phrase $hostileHail = null;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Phrase', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, name: 'hostileDisabledHailId')]
    private ?Phrase $hostileDisabledHail = null;

	#[ORM\Column(type: 'string', name: 'language')]
    private string $language = '';

	#[ORM\Column(type: 'boolean', name: 'sendUntranslatedHails')]
    private bool $sendUntranslatedHails = false;

	#[ORM\Column(type: 'float', name: 'crewAttack')]
    private float $crewAttack = 1.;

	#[ORM\Column(type: 'float', name: 'crewDefense')]
    private float $crewDefense = 2.;

	#[ORM\Column(type: 'boolean', name: 'provokedOnScan')]
    private bool $provokedOnScan = false;

	private array $raidFleets = []; // vector<RaidFleet>
	
	// If a government appears in this set, and the reputation with this government is affected by actions,
	// and events performed against that government, use the penalties that government applies for the
	// action instead of this governments own penalties.
	private array $useForeignPenaltiesFor = []; // set<unsigned>
	
	public static int $nextID = 0;
	
	// Load ShipEvent strings and corresponding numerical values into a map.
	public static function LoadPenaltyHelper(DataNode $node, array &$penalties): void {
    	foreach ($node as $child) {
    		if ($child->size() >= 2) {
    			$key = $child->getToken(0);
    			if ($key == "assist") {
    				$penalties[ShipEvent::ASSIST] = $child->getValue(1);
    			} else if ($key == "disable") {
    				$penalties[ShipEvent::DISABLE] = $child->getValue(1);
    			} else if ($key == "board") {
    				$penalties[ShipEvent::BOARD] = $child->getValue(1);
    			} else if ($key == "capture") {
    				$penalties[ShipEvent::CAPTURE] = $child->getValue(1);
    			} else if ($key == "destroy") {
    				$penalties[ShipEvent::DESTROY] = $child->getValue(1);
    			} else if ($key == "scan") {
    				$penalties[ShipEvent::SCAN_OUTFITS] = $child->getValue(1);
    				$penalties[ShipEvent::SCAN_CARGO] = $child->getValue(1);
    			} else if ($key == "provoke") {
    				$penalties[ShipEvent::PROVOKE] = $child->getValue(1);
    			} else if ($key == "atrocity") {
    				$penalties[ShipEvent::ATROCITY] = $child->getValue(1);
    			} else {
    				$child->printTrace("Skipping unrecognized attribute:");
    			}
    		} else {
    			$child->printTrace("Skipping unrecognized attribute:");
    		}
    	}
    }

	// Determine the penalty for the given ShipEvent based on the values in the given map.
	public static function PenaltyHelper(int $eventType, array &$penalties) {
    	$penalty = 0.;
    	foreach ($penalties as $penaltyIndex => $penaltyVal) {
    		if ($eventType & $penaltyIndex) {
    			$penalty += $penaltyVal;
    		}
    	}
    	return $penalty;
    }
	
	// Default constructor.
	public function __construct() {
    	// Default penalties:
    	$this->penaltyFor[ShipEvent::ASSIST] = -0.1;
    	$this->penaltyFor[ShipEvent::DISABLE] = 0.5;
    	$this->penaltyFor[ShipEvent::BOARD] = 0.3;
    	$this->penaltyFor[ShipEvent::CAPTURE] = 1.;
    	$this->penaltyFor[ShipEvent::DESTROY] = 1.;
    	$this->penaltyFor[ShipEvent::SCAN_OUTFITS] = 0.;
    	$this->penaltyFor[ShipEvent::SCAN_CARGO] = 0.;
    	$this->penaltyFor[ShipEvent::PROVOKE] = 0.;
    	$this->penaltyFor[ShipEvent::ATROCITY] = 10.;
    
    	$this->id = self::$nextID++;
        $this->penaltyForObject = new ArrayCollection();
        $this->outfitPenalties = new ArrayCollection();
    }
	
	// Load a government's definition from a file.
	public function load(DataNode $node) {
    	if ($node->size() >= 2) {
    		$this->name = $node->getToken(1);
    		if ($this->displayName == '') {
    			$this->displayName = $this->name;
    		}
    	}
    
    	// For the following keys, if this data node defines a new value for that
    	// key, the old values should be cleared (unless using the "add" keyword).
    	$shouldOverwrite = ["raid"];
    
    	foreach ($node as $child) {
    		$remove = $child->getToken(0) == "remove";
    		$add = $child->getToken(0) == "add";
    		if (($add || $remove) && $child->size() < 2) {
    			$child->printTrace("Skipping " . $child->getToken(0) . " with no key given:");
    			continue;
    		}
    
    		$key = $child->getToken(($add || $remove) ? 1 : 0);
    		$valueIndex = ($add || $remove) ? 2 : 1;
    		$hasValue = $child->size() > $valueIndex;
    
    		// Check for conditions that require clearing this key's current value.
    		// "remove <key>" means to clear the key's previous contents.
    		// "remove <key> <value>" means to remove just that value from the key.
    		$removeAll = ($remove && !$hasValue);
    		// If this is the first entry for the given key, and we are not in "add"
    		// or "remove" mode, its previous value should be cleared.
    		$overwriteAll = (!$add && !$remove && isset($shouldOverwrite[$key]));
    
    		if ($removeAll || $overwriteAll) {
    			if ($key == "provoked on scan") {
    				$this->provokedOnScan = false;
    			} else if ($key == "reputation") {
    				foreach ($child as $grand) {
    					$grandKey = $grand->getToken(0);
    					if ($grandKey == "max") {
    						$this->reputationMax = PHP_FLOAT_MAX;
    					} else if ($grandKey == "min") {
    						$this->reputationMin = PHP_FLOAT_MIN;
    					}
    				}
    			} else if ($key == "raid") {
    				$this->raidFleets.clear();
    			} else if ($key == "display name") {
    				$this->displayName = $this->name;
    			} else if ($key == "death sentence") {
    				$this->deathSentence = null;
    			} else if ($key == "friendly hail") {
    				$this->friendlyHail = null;
    			} else if ($key == "friendly disabled hail") {
    				$this->friendlyDisabledHail = null;
    			} else if ($key == "hostile hail") {
    				$this->hostileHail = null;
    			} else if ($key == "hostile disabled hail") {
    				$this->hostileDisabledHail = null;
    			} else if ($key == "language") {
    				$this->language = '';
    			} else if ($key == "send untranslated hails") {
    				$this->sendUntranslatedHails = false;
    			} else if ($key == "trusted") {
    				$this->trusted = [];
    			} else if ($key == "enforces") {
    				$this->enforcementZones = [];
    			} else if ($key == "custom penalties for") {
    				$this->customPenalties = [];
    			} else if ($key == "foreign penalties for") {
    				$this->useForeignPenaltiesFor = [];
    			} else if ($key == "illegals") {
    				$this->illegals = [];
    			} else if ($key == "atrocities") {
    				$this->atrocities = [];
    			} else {
    				$child->printTrace("Cannot \"remove\" the given key:");
    			}
    
    			// If not in "overwrite" mode, move on to the next node.
    			if ($overwriteAll) {
    				unset($shouldOverwrite[$key]);
    			} else {
    				continue;
    			}
    		}
    
    		if ($key == "raid") {
    			$fleet = GameData::Fleets()[$child->getToken($valueIndex)];
    			if ($remove) {
    				foreach ($this->raidFleets as $fleetKey => $raidFleet) {
    					if ($raidFleet->getFleet() == $fleet) {
    						unset($this->raidFleets[$fleetKey]);
    					}
    				}
    			} else {
    				$raidFleet = new RaidFleet($fleet, $child->size() > ($valueIndex + 1) ? $child->getValue($valueIndex + 1) : 2.,
    					$child->size() > ($valueIndex + 2) ? $child->getValue($valueIndex + 2) : 0.);
    			}
    		// Handle the attributes which cannot have a value removed.
    		} else if ($remove) {
    			$child->printTrace("Cannot \"remove\" a specific value from the given key:");
    		} else if ($key == "attitude toward") {
    			foreach ($child as $grand) {
    				if ($grand->size() >= 2) {
    					$gov = GameData::Governments()[$grand->getToken(0)];
    					$this->attitudeToward[$gov->id] = $grand->getValue(1);
    				} else {
    					$grand->printTrace("Skipping unrecognized attribute:");
    				}
    			}
    		} else if ($key == "reputation") {
    			foreach ($child as $grand) {
    				$grandKey = $grand->getToken(0);
    				$hasGrandValue = $grand->size() >= 2;
    				if ($grandKey == "player reputation" && $hasGrandValue) {
    					$this->initialPlayerReputation = $add ? $this->initialPlayerReputation + $child->getValue($valueIndex) : $child->getValue($valueIndex);
    				} else if ($grandKey == "max" && $hasGrandValue) {
    					$this->reputationMax = $add ? $this->reputationMax + $grand->getValue($valueIndex) : $grand->getValue($valueIndex);
    				} else if ($grandKey == "min" && $hasGrandValue) {
    					$this->reputationMin = $add ? $this->reputationMin + $grand->getValue($valueIndex) : $grand->getValue($valueIndex);
    				} else {
    					$grand->printTrace("Skipping unrecognized attribute:");
    				}
    			}
    		} else if ($key == "trusted") {
    			$clearTrusted = count($this->trusted) > 0;
    			foreach ($child as $grand) {
    				$remove = $grand->getToken(0) == "remove";
    				$add = $grand->getToken(0) == "add";
    				if (($add || $remove) && $grand->size() < 2) {
    					$grand->printTrace("Warning: Skipping invalid \"" + $child->getToken(0) + "\" tag:");
    					continue;
    				}
    				if ($clearTrusted && !$add && !$remove) {
    					$this->trusted = [];
    					$clearTrusted = false;
    				}
    				$gov = GameData::Governments()[$grand->getToken($remove || $add)];
    				if ($gov) {
    					if ($remove) {
    						$this->trusted.erase($gov);
    					} else
    						$this->trusted.insert($gov);
    				} else {
    					$grand->printTrace("Skipping unrecognized government:");
    				}
    			}
    		} else if ($key == "penalty for") {
    			self::LoadPenaltyHelper($child, $this->penaltyFor);
    		} else if ($key == "custom penalties for") {
    			foreach ($child as $grand) {
    				if ($grand->getToken(0) == "remove" && $grand->size() >= 2) {
    					$this->customPenalties[GameData::Governments()[$grand->getToken(1)]->id] = [];
    				} else {
    					$govId = GameData::Governments()[$grand->getToken(0)]->id;
    					if (isset($this->customPenalties[$govId])) {
    						$pens = $this->customPenalties[$govId];
    					} else {
    						$pens = [];
    					}
    					self::LoadPenaltyHelper($grand, $pens);
    				}
    			}
    		} else if ($key == "illegals") {
    			if (!$add) {
    				$this->illegals = [];
    			}
    			foreach ($child as $grand) {
    				if ($grand->size() >= 2) {
    					if ($grand->getToken(0) == "ignore") {
    						$this->illegals[GameData::Outfits()[$grand->getToken(1)]->getTrueName()] = 0;
    					} else {
    						$this->illegals[GameData::Outfits()[$grand->getToken(0)]->getTrueName()] = $grand->getValue(1);
    					}
    				} else if ($grand->size() >= 3 && $grand->getToken(0) == "remove") {
    					if (!isset($this->illegals[GameData::Outfits()[$grand->getToken(1)]->getTrueName()])) {
    						$grand->printTrace("Invalid remove, outfit not found in existing illegals:");
    					} else {
    						unset($this->illegals[GameData::Outfits()[$grand->getToken(1)]->getTrueName()]);
    					}
    				} else
    					$grand->printTrace("Skipping unrecognized attribute:");
    				}
    		} else if ($key == "atrocities") {
    			if (!$add) {
    				$this->atrocities = [];
    			}
    			foreach ($child as $grand) {
    				if ($grand->size() >= 2) {
    					if ($grand->getToken(0) == "remove") {
    						if (!isset($this->atrocities[GameData::Outfits()[$grand->getToken(1)]->getTrueName()])) {
    							$grand->printTrace("Invalid remove, outfit not found in existing atrocities:");
    						} else {
    							unset($this->atrocities[GameData::Outfits()[$grand->getToken(1)]->getTrueName()]);
    						}
    					} else if ($grand->getToken(0) == "ignore") {
    						$this->atrocities[GameData::Outfits()[$grand->getToken(1)]->getTrueName()] = false;
    					}
    				} else {
    					$this->atrocities[GameData::Outfits()[$grand->getToken(0)]->getTrueName()] = true;
    				}
    			}
    		} else if ($key == "enforces" && $child->hasChildren()) {
    			$this->enforcementZones.emplace_back($child);
    		} else if ($key == "provoked on scan") {
    			$this->provokedOnScan = true;
    		} else if ($key == "foreign penalties for") {
    			foreach ($child as $grand) {
    				$this->useForeignPenaltiesFor []= GameData::Governments()[$grand->getToken(0)]->id;
    			}
    		} else if ($key == "send untranslated hails") {
    			$this->sendUntranslatedHails = true;
    		} else if (!$hasValue) {
    			$child->printTrace("Error: Expected key to have a value:");
    		} else if ($key == "player reputation") {
    			$this->initialPlayerReputation = $add ? $this->initialPlayerReputation + $child->getValue($valueIndex) : $child->getValue($valueIndex);
    		} else if ($key == "crew attack") {
    			$this->crewAttack = max(0., $add ? $child->getValue($valueIndex) + $this->crewAttack : $child->getValue($valueIndex));
    		} else if ($key == "crew defense") {
    			$this->crewDefense = max(0., $add ? $child->getValue($valueIndex) + $this->crewDefense : $child->getValue($valueIndex));
    		} else if ($key == "bribe") {
    			$this->bribe = $add ? $this->bribe + $child->getValue($valueIndex) : $child->getValue($valueIndex);
    		} else if ($key == "fine") {
    			$this->fine = $add ? $this->fine + $child->getValue($valueIndex) : $child->getValue($valueIndex);
    		} else if ($add) {
    			$child->printTrace("Error: Unsupported use of add:");
    		} else if ($key == "display name") {
    			$this->displayName = $child->getToken($valueIndex);
    		} else if ($key == "swizzle") {
    			$this->swizzle = $child->getValue($valueIndex);
    		} else if ($key == "color") {
    			if ($child->size() >= 3 + $valueIndex) {
    				$this->color = new Color($child->getValue($valueIndex), $child->getValue($valueIndex + 1), $child->getValue($valueIndex + 2));
					$this->color->name = 'government: '.$this->name;
    			} else if ($child->size() >= 1 + $valueIndex) {
    				$this->color = GameData::Colors()[$child->getToken($valueIndex)];
					$this->color->name = $child->getToken($valueIndex);
    			}
    		} else if ($key == "death sentence") {
    			$this->deathSentence = GameData::Conversations()[$child->getToken($valueIndex)];
    		} else if ($key == "friendly hail") {
    			$this->friendlyHail = GameData::Phrases()[$child->getToken($valueIndex)];
    		} else if ($key == "friendly disabled hail") {
    			$this->friendlyDisabledHail = GameData::Phrases()[$child->getToken($valueIndex)];
    		} else if ($key == "hostile hail") {
    			$this->hostileHail = GameData::Phrases()[$child->getToken($valueIndex)];
    		} else if ($key == "hostile disabled hail") {
    			$this->hostileDisabledHail = GameData::Phrases()[$child->getToken($valueIndex)];
    		} else if ($key == "language") {
    			$this->language = $child->getToken($valueIndex);
    		} else if ($key == "enforces" && $child->getToken($valueIndex) == "all") {
    			$this->enforcementZones = [];
    			$child->printTrace("Warning: Deprecated use of \"enforces all\". Use \"remove enforces\" instead:");
    		} else {
    			$child->printTrace("Skipping unrecognized attribute:");
    		}
    	}
    
    	// Ensure reputation minimum is not above the
    	// maximum, and set reputation again to enforce limtis.
    	if ($this->reputationMin > $this->reputationMax) {
    		$this->reputationMin = $this->reputationMax;
    	}
    	$this->setReputation($this->getReputation());
    
    	// Default to the standard disabled hail messages.
    	if (!$this->friendlyDisabledHail) {
    		$this->friendlyDisabledHail = GameData::Phrases()["friendly disabled"];
    	}
    	if (!$this->hostileDisabledHail) {
    		$this->hostileDisabledHail = GameData::Phrases()["hostile disabled"];
    	}
    }
	
	// Get the display name of this government.
	public function getName(): string {
    	return $this->displayName;
    }
	
	// Set / Get the name used for this government in the data files.
	public function setName(string $trueName): void {
    	$this->name = $trueName;
    }
	
	public function getTrueName(): string {
    	return $this->name;
    }
	
	// Get the color swizzle to use for ships of this government.
	public function getSwizzle(): int {
    	return $this->swizzle;
    }
	
	// Get the color to use for displaying this government on the map.
	public function getColor(): ?Color {
    	return $this->color;
    }
	
	// Get the government's initial disposition toward other governments or
	// toward the player.
	public function getAttitudeToward(Government $other): float {
    	if (!$other) {
    		return 0.;
    	}
    	if ($other == $this) {
    		return 1.;
    	}
    
    	if (!isset($this->attitudeToward[$other->id])) {
    		return 0.;
    	}
    
    	return $this->attitudeToward[$other->id];
    }
	
	public function getInitialPlayerReputation(): float {
    	return $this->initialPlayerReputation;
    }
	
	// Get the amount that your reputation changes for the given offense against the given government.
	// The given value should be a combination of one or more ShipEvent values.
	// Returns 0 if the Government is null.
	public function getPenaltyFor(int $eventType, Government $other): float {
    	if (!$other) {
    		return 0.;
    	}
    
    	if ($other == $this) {
    		return self::PenaltyHelper($eventType, $this->penaltyFor);
    	}
    
    	$id = $other->id;
    	$penaltyGov = isset($this->useForeignPenaltiesFor[$id]) ? 'other' : 'this';
    
    	if (!isset($this->customPenalties[$id])) {
    		return self::PenaltyHelper($eventType, $$penaltyGov->penaltyFor);
    	}
    	
    	$tempPenalties = $$penaltyGov->penaltyFor;
    	foreach ($this->customPenalties[$id] as $penaltyIndex => $penaltyData) {
    		$tempPenalties[$penaltyIndex] = $penaltyData;
    	}
    	return self::PenaltyHelper($eventType, $tempPenalties);
    }
	
	// In order to successfully bribe this government you must pay them this
	// fraction of your fleet's value. (Zero means they cannot be bribed.)
	public function getBribeFraction(): float {
    	return $this->bribe;
    }
	
	public function getFineFraction(): float {
    	return $this->fine;
    }
	
	public function trusts(Government $government): bool {
    	return $government == $this || isset($this->trusted[$government]);
    }
	
	// Returns true if this government has no enforcement restrictions, or if the
	// indicated planet or system matches at least one enforcement zone.
	public function canEnforce(System|Planet $location): bool {
    	foreach ($this->enforcementZones as $filter) {
    		if ($filter->matches($location)) {
    			return true;
    		}
    	}
    	return (count($this->enforcementZones) == 0);
    }
	
	public function getDeathSentence(): Conversation {
    	return $this->deathSentence;
    }
	
	// Get a hail message (which depends on whether this is an enemy government
	// and if the ship is disabled).
	public function getHail(bool $isDisabled): string {
    	$phrase = null;
    
    	if ($this->isEnemy()) {
    		$phrase = $isDisabled ? $this->hostileDisabledHail : $this->hostileHail;
    	} else {
    		$phrase = $isDisabled ? $this->friendlyDisabledHail : $this->friendlyHail;
    	}
    
    	return $phrase ? $phrase->get() : "";
    }
	
	// Find out if this government speaks a different language.
	public function getLanguage(): string {
    	return $this->language;
    }
	
	// Find out if this government should send custom hails even if the player does not know its language.
	public function getSendUntranslatedHails(): bool {
    	return $this->sendUntranslatedHails;
    }
	
	// Pirate raids in this government's systems use these fleet definitions. If
	// it is empty, there are no pirate raids.
	// The second attribute denotes the minimal and maximal attraction required for the fleet to appear.
	//const vector<Government::RaidFleet>
	public function getRaidFleets(): array {
    	return $this->raidFleets;
    }
	
	// Check if, according to the politics stored by GameData, this government is
	// an enemy of the given government right now.
	public function isEnemy(?Government $other = null): bool {
    	if (!$other) {
    		$other = GameData::PlayerGovernment();
    	}
    	return GameData::GetPolitics()->isEnemy($this, $other);
    }
	
	// Check if this is the player government.
	public function isPlayer(): bool {
    	return ($this == GameData::PlayerGovernment());
    }
	
	// Commit the given "offense" against this government (which may not
	// actually consider it to be an offense). This may result in temporary
	// hostilities (if the even type is PROVOKE), or a permanent change to your
	// reputation.
	public function offend(int $eventType, int $count): void {
    	GameData::GetPolitics()->offend($this, $eventType, $count);
    }
	
	// Bribe this government to be friendly to you for one day.
	public function bribe(): void {
    	GameData::GetPolitics()->bribe($this);
    }
	
	// Check to see if the player has done anything they should be fined for.
	// Each government can only fine you once per day.
	public function fine(PlayerInfo $player, int $scan, Ship $target, float $security): string {
    	return GameData::GetPolitics()->fine($player, $this, $scan, $target, $security);
    }
	
	public function condemns(Outfit $outfit): bool {
    	$isAtrocity = false;
    	$found = false;
    	if (isset($this->atrocities[$outfit->getName()])) {
    		$isAtrocity = $this->atrocities[$outfit->getName()]['atrocity'];
    		$found = true;
    	}
    	return ($found && $isAtrocity) || (!$found && $outfit->get("atrocity") > 0.);
    }
	
	public function getFines(Outfit $outfit): int {
    	// If this government doesn't fine anything it won't fine this outfit.
    	if (!$this->fine) {
    		return 0;
    	}
    
    	foreach ($this->illegals as $outfitName => $illegalData) {
    		if ($outfitName == $outfit->getName()) {
    			return $illegalData['illegal'];
    		}
    	}
    	return $outfit->get("illegal");
    }
	
	public function getFinesContents(Ship $ship): bool {
    	foreach ($ship->getOutfits() as $outfit) {
    		if ($this->getFines($outfit) || $this->condemns($outfit)) {
    			return true;
    		}
    	}
    
    	return $ship->getCargo()->getIllegalCargoFine($this);
    }
	
	// Get or set the player's reputation with this government.
	public function getReputation(): float {
    	return GameData::GetPolitics()->getReputation($this);
    }
	
	public function getReputationMax(): float {
    	return $this->reputationMax;
    }
	
	public function getReputationMin(): float {
    	return $this->reputationMin;
    }
	
	public function addReputation(float $value): void {
    	GameData::GetPolitics()->addReputation($this, $value);
    }
	
	public function setReputation(float $value): void {
    	GameData::GetPolitics()->setReputation($this, $value);
    }
	
	public function getCrewAttack(): float {
    	return $this->crewAttack;
    }
	
	public function getCrewDefense(): float {
    	return $this->crewDefense;
    }
	
	public function isProvokedOnScan(): bool {
    	return $this->provokedOnScan;
    }
	
	public function toJSON($justArray=false): array|string {
    	$jsonArray = ['name' => $this->name];
    	
    	$jsonArray['displayName'] = $this->displayName;
    	$jsonArray['swizzle'] = $this->swizzle;
    	$jsonArray['color'] = $this->color ? $this->color->toJSON(true) : (new Color())->toJSON(true);
    	
    	$jsonArray['attitudeToward'] = $this->attitudeToward; // vector<float>
    	$jsonArray['trusted'] = $this->trusted; // set<const Government *>
    	$jsonArray['customPenalties'] = $this->customPenalties; // map<unsigned, map<int, float>>
    	$jsonArray['initialPlayerReputation'] = $this->initialPlayerReputation;
    	$jsonArray['reputationMax'] = $this->reputationMax;
    	$jsonArray['reputationMin'] = $this->reputationMin;
    	$jsonArray['penaltyFor'] = $this->penaltyFor; // map<int, float>
    	$jsonArray['illegals'] = $this->illegals; // map<const Outfit*, int>
    	$jsonArray['atrocities'] = $this->atrocities; // map<const Outfit*, bool>
    	$jsonArray['bribe'] = $this->bribe;
    	$jsonArray['fine'] = $this->fine;
    	$jsonArray['enforcementZones'] = $this->enforcementZones; // vector<LocationFilter>
    	$jsonArray['language'] = $this->language;
    	$jsonArray['sendUntranslatedHails'] = $this->sendUntranslatedHails;
    	$jsonArray['raidFleets'] = $this->raidFleets; // vector<RaidFleet>
    	$jsonArray['crewAttack'] = $this->crewAttack;
    	$jsonArray['crewDefense'] = $this->crewDefense;
    	$jsonArray['provokedOnScan'] = $this->provokedOnScan;
    	
    	if ($justArray) {
    		return $jsonArray;
    	}
    	return json_encode($jsonArray);
    }

    /**
     * @return Collection<int, GovernmentPenalty>
     */
    public function getPenaltyForObject(): Collection
    {
        return $this->penaltyForObject;
    }

    public function addPenaltyForObject(GovernmentPenalty $penaltyForObject): self
    {
        if (!$this->penaltyForObject->contains($penaltyForObject)) {
            $this->penaltyForObject->add($penaltyForObject);
            $penaltyForObject->setGovernment($this);
        }

        return $this;
    }

    public function removePenaltyForObject(GovernmentPenalty $penaltyForObject): self
    {
        if ($this->penaltyForObject->removeElement($penaltyForObject)) {
            // set the owning side to null (unless already changed)
            if ($penaltyForObject->getGovernment() === $this) {
                $penaltyForObject->setGovernment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OutfitPenalty>
     */
    public function getOutfitPenalties(): Collection
    {
        return $this->outfitPenalties;
    }

    public function addOutfitPenalty(OutfitPenalty $outfitPenalty): self
    {
        if (!$this->outfitPenalties->contains($outfitPenalty)) {
            $this->outfitPenalties->add($outfitPenalty);
            $outfitPenalty->setGovernment($this);
        }

        return $this;
    }

    public function removeOutfitPenalty(OutfitPenalty $outfitPenalty): self
    {
        if ($this->outfitPenalties->removeElement($outfitPenalty)) {
            // set the owning side to null (unless already changed)
            if ($outfitPenalty->getGovernment() === $this) {
                $outfitPenalty->setGovernment(null);
            }
        }

        return $this;
    }
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->attitudeTowardStr = json_encode($this->attitudeToward);
		$handledPenaltyTypes = [];
		foreach ($this->penaltyForObject as $Penalty) {
			if (isset($this->penaltyFor[$Penalty->getEventType()]) && $this->penaltyFor[$Penalty->getEventType()] == $Penalty->getPenalty()) {
				$handledPenaltyTypes []= $Penalty->getEventType();
			} else {
				$eventArgs->getEntityManager()->remove($Penalty);
			}
		}
		foreach ($this->penaltyFor as $eventType => $penaltyAmount) {
			if (in_array($eventType, $handledPenaltyTypes)) {
				continue;
			}
			$Penalty = new GovernmentPenalty();
			$Penalty->setGovernment($this);
			$Penalty->setEventType($eventType);
			$Penalty->setPenalty($penaltyAmount);
			$this->penaltyForObject []= $Penalty;
			$eventArgs->getEntityManager()->persist($Penalty);
		}
		$handledOutfits = [];
		foreach ($this->outfitPenalties as $Penalty) {
			if ($Penalty->getPenaltyType() == 'illegal') {
				if (isset($this->illegals[$Penalty->getOutfit()->getTrueName()]) && $this->illegals[$Penalty->getOutfit()->getTrueName()] == $Penalty->getPenalty()) {
					$handledOutfits []= $Penalty->getOutfit()->getTrueName();
				} else {
					$eventArgs->getEntityManager()->remove($Penalty);
				}
			} else if ($Penalty->getPenaltyType() == 'atrocity') {
				if (isset($this->illegals[$Penalty->getOutfit()->getTrueName()])) {
					$handledOutfits []= $Penalty->getOutfit()->getTrueName();
				} else {
					$eventArgs->getEntityManager()->remove($Penalty);
				}
			} else {
				$eventArgs->getEntityManager()->remove($Penalty);
			}
		}
		foreach ($this->illegals as $illegalName => $illegalFine) {
			if (in_array($illegalName, $handledOutfits)) {
				continue;
			}
			$Penalty = new OutfitPenalty();
			$Penalty->setGovernment($this);
			$Penalty->setPenaltyType('illegal');
			$Outfit = GameData::Outfits()[$illegalName];
			$Penalty->setOutfit($Outfit);
			$Penalty->setPenalty($illegalFine);
			$this->outfitPenalties []= $Penalty;
		}
		foreach ($this->atrocities as $atrocityName => $atrocify) {
			if (in_array($atrocityName, $handledOutfits)) {
				continue;
			}
			$Penalty = new OutfitPenalty();
			$Penalty->setGovernment($this);
			$Penalty->setPenaltyType('atrocity');
			$Outfit = GameData::Outfits()[$atrocityName];
			$Penalty->setOutfit($Outfit);
			$this->outfitPenalties []= $Penalty;
		}
		
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->attitudeToward = json_decode($this->attitudeTowardStr, true);
		foreach ($this->penaltyForObject as $Penalty) {
			$this->penaltyFor[$Penalty->getEventType()] = $Penalty->getPenalty();
		}
		foreach ($this->outfitPenalties as $Penalty) {
			if ($Penalty->getPenaltyType() == 'illegal') {
				$this->illegals[$Penalty->getOutfit()->getTrueName()] = $Penalty->getPenalty();
			} else if ($Penalty->getPenaltyType() == 'atrocity') {
				$this->atrocities[$Penalty->getOutfit()->getTrueName()] = true;
			}
		}
	}

}

class RaidFleet {
	public function __construct(private Fleet $fleet,
    							private float $minAttraction,
    							private float $maxAttraction) {
    	
    }
	
	public function getFleet(): Fleet {
    	return $this->fleet;
    }
	
	public function getMinAttraction(): float {
    	return $this->minAttraction;
    }
	
	public function getMaxAttraction(): float {
    	return $this->maxAttraction;
    }
}
