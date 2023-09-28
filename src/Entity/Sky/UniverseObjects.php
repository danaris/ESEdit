<?php

namespace App\Entity\Sky;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreFlushEventArgs;

use App\Entity\DataFile;
use App\Entity\DataNode;
use App\Entity\TemplatedArray;

use App\Service\TemplatedArrayService;

class UniverseObjects {
	public TemplatedArray $colors; // Color
	public TemplatedArray $conversations; // Conversation
	public TemplatedArray $effects; // Effect
	public TemplatedArray $events; // GameEvent
	public TemplatedArray $fleets; // Fleet
	public TemplatedArray $galaxies; // Galaxy
	public TemplatedArray $governments; // Government
	public TemplatedArray $hazards; // Hazard
	public TemplatedArray $interfaces; // Interface
	public TemplatedArray $minables; // Minable
	public TemplatedArray $missions; // Mission
	public TemplatedArray $news; // News
	public TemplatedArray $outfits; // Outfit
	public TemplatedArray $persons; // Person
	public TemplatedArray $phrases; // Phrase
	public TemplatedArray $planets; // Planet
	public TemplatedArray $ships; // Ship
	public TemplatedArray $systems; // System
	public TemplatedArray $shipSales; // Sale<Ship>
	public TemplatedArray $outfitSales; // Sale<Outfit>
	public TemplatedArray $wormholes; // Wormhole
	
	public Trade $trade;
	public Politics $politics;
	public array $startConditions = []; //vector<StartConditions>
	
	public array $categories = [];
	public array $disabled = [];
	
	public array $neighborDistances = [];
	
	private float $progress;

	private array $helpMessages = [];
	private array $tooltips = [];
	
	// TGC added
	private array $nodesByType = [];
	private array $readNodesByType = [];
	private array $missionRelations = [];
	
	private ?EntityManagerInterface $em = null;
	
	public function __construct() {
		$this->colors = TemplatedArrayService::Instance()->createTemplatedArray(Color::class);
		$this->conversations = TemplatedArrayService::Instance()->createTemplatedArray(Conversation::class);
		$this->effects = TemplatedArrayService::Instance()->createTemplatedArray(Effect::class);
		$this->events = TemplatedArrayService::Instance()->createTemplatedArray(GameEvent::class);
		$this->fleets = TemplatedArrayService::Instance()->createTemplatedArray(Fleet::class, 'fleetName');
		$this->galaxies = TemplatedArrayService::Instance()->createTemplatedArray(Galaxy::class);
		$this->governments = TemplatedArrayService::Instance()->createTemplatedArray(Government::class);
		$this->hazards = TemplatedArrayService::Instance()->createTemplatedArray(Hazard::class);
		$this->interfaces = TemplatedArrayService::Instance()->createTemplatedArray(ESInterface::class);
		$this->minables = TemplatedArrayService::Instance()->createTemplatedArray(Minable::class);
		$this->missions = TemplatedArrayService::Instance()->createTemplatedArray(Mission::class);
		$this->news = TemplatedArrayService::Instance()->createTemplatedArray(News::class);
		$this->outfits = TemplatedArrayService::Instance()->createTemplatedArray(Outfit::class, 'trueName');
		$this->persons = TemplatedArrayService::Instance()->createTemplatedArray(Person::class);
		$this->phrases = TemplatedArrayService::Instance()->createTemplatedArray(Phrase::class);
		$this->planets = TemplatedArrayService::Instance()->createTemplatedArray(Planet::class);
		$this->ships = TemplatedArrayService::Instance()->createTemplatedArray(Ship::class);
		$this->systems = TemplatedArrayService::Instance()->createTemplatedArray(System::class);
		$this->shipSales = TemplatedArrayService::Instance()->createTemplatedArray(Sale::class);
		$this->outfitSales = TemplatedArrayService::Instance()->createTemplatedArray(Sale::class);
		$this->wormholes = TemplatedArrayService::Instance()->createTemplatedArray(Wormhole::class, 'trueName');
		$this->trade = new Trade();
		$this->politics = new Politics();
	}
	
	private function dirList($path): array {
		$pathArray = [];
		if (substr($path, -1) != '/') {
			$path .= '/';
		}
		$dirList = scandir($path);
		foreach ($dirList as $filename) {
			if ($filename[0] == '.') {
				continue;
			}
			$filePath = $path . $filename;
			if (is_dir($filePath)) {
				$pathArray = array_merge($pathArray, $this->dirList($filePath));
			} else {
				$pathArray []= $filePath;
			}
		}
		
		return $pathArray;
	}
	
	public function load(array $sources, bool $debugMode, ?EntityManagerInterface $em = null) {
		
		if ($em) {
			$this->em = $em;
		}

		$fileSources = [];
		foreach ($sources as $source) {
			// Iterate through the paths starting with the last directory given. That
			// is, things in folders near the start of the path have the ability to
			// override things in folders later in the path.
			$list = $this->dirList($source['dir'] . 'data/');
			for ($i = count($list) - 1; $i >= 0; $i--) {
				$fileSources []= ['name'=>$source['name'], 'file'=>$list[$i], 'version'=>$source['version'], 'dir'=>$source['dir']];
			}
		}

		foreach ($fileSources as $sourceInfo) {
			$this->loadFile($sourceInfo, $debugMode);
		}
		$this->loadObjects();
		$this->finishLoading();
	}
	
	public function loadFile(array $sourceInfo, bool $debugMode) {
		// This is an ordinary file. Check to see if it is an image.
		if (strlen($sourceInfo['file']) < 4 || substr($sourceInfo['file'], -4) != '.txt') {
			return;
		}
	
		$data = new DataFile($sourceInfo);
		if ($debugMode) {
			error_log("Parsing: " . $sourceInfo['file']);
		}
		
		foreach ($data as $node) {
			$key = $node->getToken(0);
			if (!isset($this->nodesByType[$key])) {
				$this->nodesByType[$key] = [];
			}
			$this->nodesByType[$key] []= $node;
		}
	}
	
	public function loadObjects() {
		
		$categoryTypes = ['ship', 'bay type', 'outfit', 'series'];
		$canDisable = ["mission", "event", "person"];
		
		$typeOrder = ['color','effect','outfit','ship','trade','government','planet','wormhole','system','event','mission'];
		
		foreach ($this->nodesByType as $key => $nodes) {
			if (!in_array($key, $typeOrder)) {
				$typeOrder []= $key;
			}
		}
		
		foreach ($typeOrder as $key) {
			$nodes = $this->nodesByType[$key];
			//error_log('Now loading in '.$key.' objects...');
			
			foreach ($nodes as $node) {
				if ($node->size() > 1) {
					$nodeName = $node->getToken(1);
				} else {
					$nodeName = '(unnamed '.$key.')';
				}
				//error_log('...node with name '.$nodeName);
				if ($key == "color" && $node->size() >= 5) {
					$this->colors[$node->getToken(1)]->load($node->getValue(2), $node->getValue(3), $node->getValue(4), $node->size() >= 6 ? $node->getValue(5) : 1.);
					$this->colors[$node->getToken(1)]->name = $node->getToken(1);
				} else if ($key == "conversation" && $node->size() >= 2) {
					$this->conversations[$node->getToken(1)]->load($node);
				} else if ($key == "effect" && $node->size() >= 2) {
					$this->effects[$node->getToken(1)]->load($node);
				} else if ($key == "event" && $node->size() >= 2) {
					$this->events[$node->getToken(1)]->load($node);
				} else if ($key == "fleet" && $node->size() >= 2) {
					// $fleet = new Fleet();
					// $fleet->load($node);
					// $this->fleets[$node->getToken(1)] = $fleet;
				} else if ($key == "formation" && $node->size() >= 2) {
					// $formation = new Formation();
					// $formation->load($node);
					// $this->formations[$node->getToken(1)] = $formation;
				} else if ($key == "galaxy" && $node->size() >= 2) {
					$this->galaxies[$node->getToken(1)]->load($node);
				} else if ($key == "government" && $node->size() >= 2) {
					$this->governments[$node->getToken(1)]->load($node);
				} else if ($key == "hazard" && $node->size() >= 2) {
//					$this->hazards[$node->getToken(1)]->load($node);
				} else if ($key == "interface" && $node->size() >= 2) {
//					$this->interfaces[$node->getToken(1)]->load($node);
				} else if ($key == "minable" && $node->size() >= 2) {
					$this->minables[$node->getToken(1)]->load($node);
				} else if ($key == "mission" && $node->size() >= 2) {
					$this->missions[$node->getToken(1)]->load($node);
				} else if ($key == "outfit" && $node->size() >= 2) {
					$this->outfits[$node->getToken(1)]->load($node);
				} else if ($key == "outfitter" && $node->size() >= 2) {
					$this->outfitSales[$node->getToken(1)]->load($node, $this->outfits);
				} else if ($key == "person" && $node->size() >= 2) {
					// $person = new Person();
					// $person->load($node);
					// $this->persons[$node->getToken(1)] = $person;
				} else if ($key == "phrase" && $node->size() >= 2) {
					$this->phrases[$node->getToken(1)]->load($node);
				} else if ($key == "planet" && $node->size() >= 2) {
					$this->planets[$node->getToken(1)]->load($node, $this->wormholes);
				} else if ($key == "ship" && $node->size() >= 2) {
					// Allow multiple named variants of the same ship model.
					$name = $node->getToken(($node->size() > 2) ? 2 : 1);
					$this->ships[$name]->load($node);
				} else if ($key == "shipyard" && $node->size() >= 2) {
					$this->shipSales[$node->getToken(1)]->load($node, $this->ships);
				} else if ($key == "system" && $node->size() >= 2) {
					$this->systems[$node->getToken(1)]->load($node, $this->planets);
				} else if($key == "trade") {
					$this->trade->load($node);
				} else if ($key == "news" && $node->size() >= 2) {
//					$this->news[$node->getToken(1)]->load($node);
				} else if ($key == "category" && $node->size() >= 2) {
					// $categoryName = $node->getToken(1);
					// if (!in_array($categoryName, $categoryTypes)) {
					// 	$node->printTrace("Skipping unrecognized category type:");
					// 	continue;
					// }
					// $this->categories[$categoryName]->load($node);
				} else if (($key == "tip" || $key == "help") && $node->Size() >= 2) {
					$text = $key == "tip" ? 'tooltips' : 'helpMessages';
					$name = $node->getToken(1);
					$this->$text[$name] = '';
					foreach ($node as $child) {
						if ($this->$text[$name] != '') {
							$this->$text[$name] .= "\n";
							$word = $child->getToken(0);
							if (strlen($word) > 0 && $word[0] != '	') {
								$this->$text[$name] .= '	';
							}
						}
						$this->$text[$name] .= $child->getToken(0);
					}
				//}
				// else if(key == "substitutions" && node.HasChildren())
				// 	substitutions.Load(node);
				// else if(key == "gamerules" && node.HasChildren())
				// 	gamerules.Load(node);
				} else if ($key == "wormhole" && $node->size() >= 2) {
					error_log('Loading wormhole with name '.$node->getToken(1));
					$this->wormholes[$node->getToken(1)]->load($node);
				} else if ($key == "disable" && $node->size() >= 2) {
					$category = $node->getToken(1);
					if (in_array($category, $canDisable)) {
						if ($node->hasChildren()) {
							foreach ($node as $child) {
								$this->disabled[$category] []= $child->getToken(0);
							}
						}
						if ($node->size() >= 3) {
							for ($index = 2; $index < $node->size(); ++$index) {
								$this->disabled[$category] []= $child->getToken($index);
							}
						}
					} else {
						$node->printTrace("Invalid use of keyword \"disable\" for class \"" . $category . "\"");
					}
				} else {
					$node->printTrace("Skipping unrecognized root object:");
				}
			}
		}
	}
	
	public function finishLoading(EntityManagerInterface $em = null) {
		if ($em) {
			$this->em = $em;
		}
		foreach ($this->planets as $planet) {
			$planet->finishLoading($this->wormholes);
		}
	
		// Now that all data is loaded, update the neighbor lists and other
		// system information. Make sure that the default jump range is among the
		// neighbor distances to be updated.
		$this->neighborDistances []= System::DEFAULT_NEIGHBOR_DISTANCE;
		$this->updateSystems();
	
		// And, update the ships with the outfits we've now finished loading.
		foreach ($this->ships as $ship) {
			$ship->finishLoading(true);
		}
		// foreach ($this->persons as $person) {
		// 	$person->finishLoading();
		// }
	
		// Calculate minable values.
		// foreach ($this->minables as $minable) {
		// 	$minable->finishLoading();
		// }
		
		// Make sure all mission NPCs have their missions correctly set
		foreach ($this->missions as $Mission) {
			foreach ($Mission->getNPCs() as $NPC) {
				if ($NPC->getMission() == null) {
					error_log('NPC for mission "'.$Mission->getTrueName().'" was incorrectly set; fixing');
					$NPC->setMission($Mission);
				}
			}
		}
	
		foreach ($this->startConditions as $cond) {
			$cond->finishLoading();
		}
		// Remove any invalid starting conditions, so the game does not use incomplete data.
		foreach ($this->startConditions as $condIndex => $cond) {
			if (!$cond->isValid()) {
				unset($this->startConditions[$condIndex]);
			}
		}
	
		// Process any disabled game objects.
		foreach ($this->disabled as $category => $disObjects) {
			if ($category == "mission") {
				foreach ($disObjects as $name) {
					$this->missions[$name]->neverOffer();
				}
			} else if ($category == "event") {
				foreach ($disObjects as $name) {
					$this->events[$name]->disable();
				}
			} else if ($category == "person") {
				foreach ($disObjects as $name) {
					$this->persons[$name]->neverSpawn();
				}
			} else {
				error_log("Unhandled \"disable\" keyword of type \"" . $category . "\"");
			}
		}
	
		// Sort all category lists.
		foreach ($this->categories as $list) {
			$list->sort();
		}
		
		$this->calculateMissionRelations();
		
		$eventArgs = new PreFlushEventArgs($this->em);
		
		$postProcessObjects = ['systems', 'outfits', 'governments', 'wormholes'];
		foreach ($postProcessObjects as $ppName) {
			foreach ($this->$ppName as $PPObject) {
				$PPObject->toDatabase($eventArgs);
			}
		}
		SpriteSet::PostProcess($eventArgs);
	}
	
	public function calculateMissionRelations(): void {
		$missionNames = array_keys($this->missions->getContents());
		$doneStr = ': done';
		$offeredStr = ': offered';
		$failedStr = ': failed';
		$activeStr = ': active';
		$doneLength = strlen($doneStr);
		$offeredLength = strlen($offeredStr);
		$failedLength = strlen($failedStr);
		$activeLength = strlen($activeStr);
		$missionsDone = array_map(function($missionName) use ($doneStr) {
			return $missionName . $doneStr;
		}, $missionNames);
		$missionsOffered = array_map(function($missionName) use ($offeredStr) {
			return $missionName . $offeredStr;
		}, $missionNames);
		$missionsFailed = array_map(function($missionName) use ($failedStr) {
			return $missionName . $failedStr;
		}, $missionNames);
		$missionsActive = array_map(function($missionName) use ($activeStr) {
			return $missionName . $activeStr;
		}, $missionNames);
		$eventStr = 'event: ';
		
		$missionStatusArray = ['done'=> ['length'=>$doneLength, 'names'=>$missionsDone], 'offered'=> ['length'=>$offeredLength, 'names'=>$missionsOffered], 'failed'=> ['length'=>$failedLength, 'names'=>$missionsFailed], 'active'=> ['length'=>$activeLength, 'names'=>$missionsActive]];
		
		foreach ($this->missions as $missionName=>$mission) {
			foreach ($mission->getToOffer()->getExpressions() as $toOfferExpression) {
				foreach ($toOfferExpression->getLeft()->getTokens() as $leftToken) {
					foreach ($missionStatusArray as $statusName => $statusInfo) {
						// Check that to offer requires "has" each of the possible mission statuses for all the missions
						if (in_array($leftToken, $statusInfo['names'])) {
							$prereqName = substr($leftToken, 0, strlen($leftToken) - $statusInfo['length']);
							$prereqMission = $this->missions[$prereqName];
							if ($toOfferExpression->getOp() == '!=' && $toOfferExpression->getRight()->getTokens()[0] == '0') {
								$prereqMission->unlocksOn[$missionName] = ['type'=>'mission','name'=>$missionName,'on'=>$statusName, 'mission'=>$mission];
								
								$mission->isUnlockedBy[$prereqName] = ['type'=>'mission','name'=>$prereqName,'on'=>$statusName, 'mission'=>$prereqMission];
							} else if ($toOfferExpression->getOp() == '==' && $toOfferExpression->getRight()->getTokens()[0] == '0') {
								$prereqMission->blocksOn[$missionName] = ['type'=>'mission','name'=>$missionName,'on'=>$statusName, 'mission'=>$mission];
								
								$mission->isBlockedBy[$prereqName] = ['type'=>'mission','name'=>$prereqName,'on'=>$statusName, 'mission'=>$prereqMission];
							}
						} else if (substr($leftToken, 0, strlen($eventStr)) == $eventStr) {
							$eventName = substr($leftToken, strlen($eventStr));
							if ($toOfferExpression->getOp() == '!=' && $toOfferExpression->getRight()->getTokens()[0] == '0') {
								$mission->isUnlockedBy[$eventName] = ['type'=>'event','name'=>$eventName];
							} else if ($toOfferExpression->getOp() == '==' && $toOfferExpression->getRight()->getTokens()[0] == '0') {
								$mission->isBlockedBy[$eventName] = ['type'=>'event','name'=>$eventName];
							} else {
								$mission->isUnlockedBy[$eventName] = ['type'=>'event','name'=>$eventName, 'on' => $toOfferExpression->getOp().' '.$toOfferExpression->getRight()->getTokens()[0]];
							}
						} else {
							if ($toOfferExpression->getOp() == '!=' && $toOfferExpression->getRight()->getTokens()[0] == '0') {
								$mission->isUnlockedBy[$leftToken] = ['type'=>'attribute', 'name'=>$leftToken];
							} else if ($toOfferExpression->getOp() == '==' && $toOfferExpression->getRight()->getTokens()[0] == '0') {
								$mission->isBlockedBy[$leftToken] = ['type'=>'attribute', 'name'=>$leftToken];
							} else {
								$mission->isUnlockedBy[$leftToken] = ['type'=>'attribute','name'=>$leftToken, 'on' => $toOfferExpression->getOp().' '.$toOfferExpression->getRight()->getTokens()[0]];
							}
						}
					}
				}
			}
			
			foreach ($mission->getActions() as $trigger => $action) {
				foreach ($action->getAction()->getEvents() as $eventName => $eventData) {
					if (!isset($mission->triggersEventsOn[$trigger])) {
						$mission->triggersEventsOn[$trigger] = [];
					}
					$mission->triggersEventsOn[$trigger] []= ['name'=>$eventName, 'minDays'=>$eventData['minDays'], 'maxDays' => $eventData['maxDays']];
				}
			}
		}
	}
	
	// Update the neighbor lists and other information for all the systems.
	// (This must be done any time a GameEvent creates or moves a system.)
	public function updateSystems(): void {
		foreach ($this->systems->getContents() as $name => $system) {
			// Skip systems that have no name.
			if ($name == '' || $system->getName() == '') {
				continue;
			}
			$system->updateSystem($this->systems, $this->neighborDistances);
	
			// If there were changes to a system there might have been a change to a legacy
			// wormhole which we must handle.
			foreach ($system->getObjects() as $object) {
				if ($object->getPlanet()) {
					$this->planets[$object->getPlanet()->getTrueName()]->finishLoading($this->wormholes);
				}
			}
		}
	}
	
	
	
	// // Check for objects that are referred to but never defined. Some elements, like
	// // fleets, don't need to be given a name if undefined. Others (like outfits and
	// // planets) are written to the player's save and need a name to prevent data loss.
	// void UniverseObjects::CheckReferences()
	// {
	// 	// Parse all GameEvents for object definitions.
	// 	auto deferred = map<string, set<string>>{};
	// 	for(auto &&it : events)
	// 	{
	// 		// Stock GameEvents are serialized in MissionActions by name.
	// 		if(it.second.Name().empty())
	// 			NameAndWarn("event", it);
	// 		else
	// 		{
	// 			// Any already-named event (i.e. loaded) may alter the universe.
	// 			auto definitions = GameEvent::DeferredDefinitions(it.second.Changes());
	// 			for(auto &&type : definitions)
	// 				deferred[type.first].insert(type.second.begin(), type.second.end());
	// 		}
	// 	}
	// 
	// 	// Stock conversations are never serialized.
	// 	for(const auto &it : conversations)
	// 		if(it.second.IsEmpty())
	// 			Warn("conversation", it.first);
	// 	// The "default intro" conversation must invoke the prompt to set the player's name.
	// 	if(!conversations.Get("default intro")->IsValidIntro())
	// 		Logger::LogError("Error: the \"default intro\" conversation must contain a \"name\" $node.");
	// 	// Effects are serialized as a part of ships.
	// 	for(auto &&it : effects)
	// 		if(it.second.Name().empty())
	// 			NameAndWarn("effect", it);
	// 	// Fleets are not serialized. Any changes via events are written as DataNodes and thus self-define.
	// 	for(auto &&it : fleets)
	// 	{
	// 		// Plugins may alter stock fleets with new variants that exclusively use plugin ships.
	// 		// Rather than disable the whole fleet due to these non-instantiable variants, remove them.
	// 		it.second.RemoveInvalidVariants();
	// 		if(!it.second.IsValid() && !deferred["fleet"].count(it.first))
	// 			Warn("fleet", it.first);
	// 	}
	// 	// Government names are used in mission NPC blocks and LocationFilters.
	// 	for(auto &&it : governments)
	// 		if(it.second.GetTrueName().empty() && !NameIfDeferred(deferred["government"], it))
	// 			NameAndWarn("government", it);
	// 	// Minables are not serialized.
	// 	for(const auto &it : minables)
	// 		if(it.second.TrueName().empty())
	// 			Warn("minable", it.first);
	// 	// Stock missions are never serialized, and an accepted mission is
	// 	// always fully defined (though possibly not "valid").
	// 	for(const auto &it : missions)
	// 		if(it.second.Name().empty())
	// 			Warn("mission", it.first);
	// 
	// 	// News are never serialized or named, except by events (which would then define them).
	// 
	// 	// Outfit names are used by a number of classes.
	// 	for(auto &&it : outfits)
	// 		if(it.second.TrueName().empty())
	// 			NameAndWarn("outfit", it);
	// 	// Outfitters are never serialized.
	// 	for(const auto &it : outfitSales)
	// 		if(it.second.empty() && !deferred["outfitter"].count(it.first))
	// 			Logger::LogError("Warning: outfitter \"" + it.first + "\" is referred to, but has no outfits.");
	// 	// Phrases are never serialized.
	// 	for(const auto &it : phrases)
	// 		if(it.second.Name().empty())
	// 			Warn("phrase", it.first);
	// 	// Planet names are used by a number of classes.
	// 	for(auto &&it : planets)
	// 		if(it.second.TrueName().empty() && !NameIfDeferred(deferred["planet"], it))
	// 			NameAndWarn("planet", it);
	// 	// Ship model names are used by missions and depreciation.
	// 	for(auto &&it : ships)
	// 		if(it.second.TrueModelName().empty())
	// 		{
	// 			it.second.SetTrueModelName(it.first);
	// 			Warn("ship", it.first);
	// 		}
	// 	// Shipyards are never serialized.
	// 	for(const auto &it : shipSales)
	// 		if(it.second.empty() && !deferred["shipyard"].count(it.first))
	// 			Logger::LogError("Warning: shipyard \"" + it.first + "\" is referred to, but has no ships.");
	// 	// System names are used by a number of classes.
	// 	for(auto &&it : systems)
	// 		if(it.second.Name().empty() && !NameIfDeferred(deferred["system"], it))
	// 			NameAndWarn("system", it);
	// 	// Hazards are never serialized.
	// 	for(const auto &it : hazards)
	// 		if(!it.second.IsValid())
	// 			Warn("hazard", it.first);
	// 	// Wormholes are never serialized.
	// 	for(const auto &it : wormholes)
	// 		if(it.second.Name().empty())
	// 			Warn("wormhole", it.first);
	// 
	// 	// Formation patterns are not serialized, but their usage is.
	// 	for(auto &&it : formations)
	// 		if(it.second.Name().empty())
	// 			NameAndWarn("formation", it);
	// 	// Any stock colors should have been loaded from game data files.
	// 	for(const auto &it : colors)
	// 		if(!it.second.IsLoaded())
	// 			Warn("color", it.first);
	// }

}