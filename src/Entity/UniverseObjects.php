<?php
// 
// namespace App\Entity;
// 
// use App\Entity\Sky\Color;
// use App\Entity\Sky\Conversation;
// use App\Entity\Sky\Effect;
// use App\Entity\Sky\GameEvent;
// use App\Entity\Sky\Fleet;
// use App\Entity\Sky\Galaxy;
// use App\Entity\Sky\Government;
// use App\Entity\Sky\Hazard;
// use App\Entity\Sky\ESInterface;
// use App\Entity\Sky\Minable;
// use App\Entity\Sky\Mission;
// use App\Entity\Sky\News;
// use App\Entity\Sky\Outfit;
// use App\Entity\Sky\Person;
// use App\Entity\Sky\Phrase;
// use App\Entity\Sky\Planet;
// use App\Entity\Sky\Ship;
// use App\Entity\Sky\System;
// use App\Entity\Sky\Sale;
// use App\Entity\Sky\Wormhole;
// 
// class UniverseObjects {
// 	protected array $colors = []; // Color
// 	protected array $conversations = []; // Conversation
// 	protected array $effects = []; // Effect
// 	protected array $events = []; // GameEvent
// 	protected array $fleets = []; // Fleet
// 	protected array $galaxies = []; // Galaxy
// 	protected array $governments = []; // Government
// 	protected array $hazards = []; // Hazard
// 	protected array $interfaces = []; // Interface
// 	protected array $minables = []; // Minable
// 	protected array $missions = []; // Mission
// 	protected array $news = []; // News
// 	protected array $outfits = []; // Outfit
// 	protected array $persons = []; // Person
// 	protected array $phrases = []; // Phrase
// 	protected array $planets = []; // Planet
// 	protected array $ships = []; // Ship
// 	protected array $systems = []; // System
// 	protected array $shipSales = []; // Sale<Ship>
// 	protected array $outfitSales = []; // Sale<Outfit>
// 	protected array $wormholes = []; // Wormhole
// 	
// 	protected array $categories = [];
// 	protected array $disabled = [];
// 	
// 	private float $progress;
// 	
// 	private function dirList($path): array {
// 		$pathArray = [];
// 		if (substr($path, -1) != '/') {
// 			$path .= '/';
// 		}
// 		$dirList = scandir($path);
// 		foreach ($dirList as $filename) {
// 			$filePath = $path . $filename;
// 			if (is_dir($filePath)) {
// 				$pathArray = array_merge($pathArray, $this->dirList($filePath));
// 			} else {
// 				$pathArray []= $filePath;
// 			}
// 		}
// 		
// 		return $pathArray;
// 	}
// 	
// 	public function load(array $sources, bool $debugMode) {
// 
// 		$files = [];
// 		foreach ($sources as $source) {
// 			// Iterate through the paths starting with the last directory given. That
// 			// is, things in folders near the start of the path have the ability to
// 			// override things in folders later in the path.
// 			$list = $this->dirList($source . 'data/');
// 			for ($i = count($list) - 1; $i >= 0; $i--) {
// 				$files []= $list[$i];
// 			}
// 		}
// 
// 		foreach ($files as $path) {
// 			$this->loadFile($path, $debugMode);
// 		}
// 		$this->finishLoading();
// 	}
// 	
// 	
// 	
// 	public function loadFile(string $path, bool $debugMode) {
// 		// This is an ordinary file. Check to see if it is an image.
// 		if (strlen($path) < 4 || substr($path, -4) != '.txt') {
// 			return;
// 		}
// 	
// 		$data = new DataFile($path);
// 		if ($debugMode) {
// 			error_log("Parsing: " . $path);
// 		}
// 		
// 		$categoryTypes = ['ship', 'bay type', 'outfit', 'series'];
// 		$canDisable = ["mission", "event", "person"];
// 	
// 		foreach ($data as $node) {
// 			$key = $node.getToken(0);
// 			if (key == "color" && $node->size() >= 5) {
// 				$color = new Color();
// 				$color->load($node->getValue(2), $node->getValue(3), $node->getValue(4), $node->size() >= 6 ? $node->getValue(5) : 1.);
// 				$this->colors[$node->getToken(1)] = $color;
// 			} else if ($key == "conversation" && $node.size() >= 2) {
// 				$conversation = new Conversation();
// 				$conversation->load($node);
// 				$this->conversations[$node->getToken(1)] = $conversation;
// 			} else if ($key == "effect" && $node.size() >= 2) {
// 				$effect = new Effect();
// 				$effect->load($node);
// 				$this->effects[$node.getToken(1)];
// 			} else if ($key == "event" && $node->size() >= 2) {
// 				$event = new Event();
// 				$event->load($node);
// 				$this->events[$node->getToken(1)] = $event;
// 			} else if ($key == "fleet" && $node->size() >= 2) {
// 				$fleet = new Fleet();
// 				$fleet->load($node);
// 				$this->fleets[$node->getToken(1)] = $fleet;
// 			} else if ($key == "formation" && $node->size() >= 2) {
// 				$formation = new Formation();
// 				$formation->load($node);
// 				$this->formations[$node->getToken(1)] = $formation;
// 			} else if ($key == "galaxy" && $node->size() >= 2) {
// 				$galaxy = new Galaxy();
// 				$galaxy->load($node);
// 				$this->galaxies[$node->getToken(1)] = $galaxy;
// 			} else if ($key == "government" && $node->size() >= 2) {
// 				$government = new Government();
// 				$government->load($node);
// 				$this->governments[$node->getToken(1)] = $government;
// 			} else if ($key == "hazard" && $node->size() >= 2) {
// 				$hazard = new Hazard();
// 				$hazard->load($node);
// 				$this->hazards[$node->getToken(1)] = $hazard;
// 			} else if ($key == "interface" && $node->size() >= 2) {
// 				$interface = new Interface();
// 				$interface->load($node);
// 				$this->interfaces[$node->getToken(1)] = $interface;
// 			} else if ($key == "minable" && $node->size() >= 2) {
// 				$minable = new Minable();
// 				$minable->load($node);
// 				$this->minables[$node->getToken(1)] = $minable;
// 			} else if ($key == "mission" && $node->size() >= 2) {
// 				$mission = new Mission();
// 				$mission->load($node);
// 				$this->missions[$node->getToken(1)] = $mission;
// 			} else if ($key == "outfit" && $node->size() >= 2) {
// 				$outfit = new Outfit();
// 				$outfit->load($node);
// 				$this->outfits[$node->getToken(1)] = $outfit;
// 			} else if ($key == "outfitter" && $node->size() >= 2) {
// 				$outfitSale = new OutfitSale();
// 				$outfitSale->load($node, $outfits);
// 				$this->outfitSales[$node->getToken(1)] = $outfitSale;
// 			} else if ($key == "person" && $node->size() >= 2) {
// 				$person = new Person();
// 				$person->load($node);
// 				$this->persons[$node->getToken(1)] = $person;
// 			} else if ($key == "phrase" && $node->size() >= 2) {
// 				$phrase = new Phrase();
// 				$phrase->load($node);
// 				$this->phrases[$node->getToken(1)] = $phrase;
// 			} else if ($key == "planet" && $node->size() >= 2) {
// 				$planet = new Planet();
// 				$planet->load($node, $wormholes);
// 				$this->planets[$node->getToken(1)] = $planet;
// 			} else if ($key == "ship" && $node->size() >= 2) {
// 				// Allow multiple named variants of the same ship model.
// 				$name = $node->getToken(($node->size() > 2) ? 2 : 1);
// 				$ship = new Ship();
// 				$ship->load($node);
// 				$this->ships[$name] = $ship;
// 			} else if ($key == "shipyard" && $node->size() >= 2) {
// 				$sale = new ShipSale();
// 				$sale->load($node, $ships);
// 				$this->shipSales[$node.getToken(1)] = $sale;
// 			} else if ($key == "system" && $node->size() >= 2) {
// 				$system = new System();
// 				$system->load($node, $planets);
// 				$this->systems[$node->getToken(1)] = $system;
// 			} else if($key == "trade") {
// 				$this->trade->load($node);
// 			} else if ($key == "news" && $node->size() >= 2) {
// 				$story = new News();
// 				$story->load($node);
// 				$this->news[$node->getToken(1)] = $story;
// 			} else if ($key == "category" && $node->size() >= 2) {
// 				$categoryName = $node->getToken(1);
// 				if (!in_array($categoryName, $categoryTypes)) {
// 					$node->printTrace("Skipping unrecognized category type:");
// 					continue;
// 				}
// 				$this->categories[$categoryName]->load($node);
// 			} else if ($key == "wormhole" && $node->size() >= 2) {
// 				$wormhole = new Wormhole();
// 				$wormhole->load($node);
// 				$wormholes[$node->getToken(1)] = $wormhole;
// 			} else if ($key == "disable" && $node->size() >= 2) {
// 				
// 				$category = $node->getToken(1);
// 				if (in_array($category, $canDisable)) {
// 					if ($node->hasChildren()) {
// 						foreach ($node as $child) {
// 							$this->disabled[$category] []= $child->getToken(0);
// 						}
// 					}
// 					if ($node->size() >= 3) {
// 						for ($index = 2; $index < $node->size(); ++$index) {
// 							$this->disabled[$category] []= $child->getToken($index);
// 						}
// 					}
// 				} else {
// 					$node->printTrace("Invalid use of keyword \"disable\" for class \"" . $category . "\"");
// 				}
// 			} else {
// 				$node->printTrace("Skipping unrecognized root object:");
// 			}
// 		}
// 	}
// 	
// 	public function finishLoading() {
// 		foreach ($this->planets as $planet) {
// 			$planet->finishLoading($this->wormholes);
// 		}
// 	
// 		// Now that all data is loaded, update the neighbor lists and other
// 		// system information. Make sure that the default jump range is among the
// 		// neighbor distances to be updated.
// 		neighborDistances.insert(System::DEFAULT_NEIGHBOR_DISTANCE);
// 		UpdateSystems();
// 	
// 		// And, update the ships with the outfits we've now finished loading.
// 		for(auto &&it : ships)
// 			it.second.FinishLoading(true);
// 		for(auto &&it : persons)
// 			it.second.FinishLoading();
// 	
// 		// Calculate minable values.
// 		for(auto &&it : minables)
// 			it.second.FinishLoading();
// 	
// 		for(auto &&it : startConditions)
// 			it.FinishLoading();
// 		// Remove any invalid starting conditions, so the game does not use incomplete data.
// 		startConditions.erase(remove_if(startConditions.begin(), startConditions.end(),
// 				[](const StartConditions &it) noexcept -> bool { return !it.IsValid(); }),
// 			startConditions.end()
// 		);
// 	
// 		// Process any disabled game objects.
// 		for(const auto &category : disabled)
// 		{
// 			if(category.first == "mission")
// 				for(const string &name : category.second)
// 					missions.Get(name)->NeverOffer();
// 			else if(category.first == "event")
// 				for(const string &name : category.second)
// 					events.Get(name)->Disable();
// 			else if(category.first == "person")
// 				for(const string &name : category.second)
// 					persons.Get(name)->NeverSpawn();
// 			else
// 				Logger::LogError("Unhandled \"disable\" keyword of type \"" + category.first + "\"");
// 		}
// 	
// 		// Sort all category lists.
// 		for(auto &list : categories)
// 			list.second.Sort();
// 	}
// 	
// 	
// 	
// 	// Update the neighbor lists and other information for all the systems.
// 	// (This must be done any time a GameEvent creates or moves a system.)
// 	void UniverseObjects::UpdateSystems()
// 	{
// 		for(auto &it : systems)
// 		{
// 			// Skip systems that have no name.
// 			if(it.first.empty() || it.second.Name().empty())
// 				continue;
// 			it.second.UpdateSystem(systems, neighborDistances);
// 	
// 			// If there were changes to a system there might have been a change to a legacy
// 			// wormhole which we must handle.
// 			for(const auto &object : it.second.Objects())
// 				if(object.GetPlanet())
// 					planets.Get(object.GetPlanet()->TrueName())->FinishLoading(wormholes);
// 		}
// 	}
// 	
// 	
// 	
// 	// Check for objects that are referred to but never defined. Some elements, like
// 	// fleets, don't need to be given a name if undefined. Others (like outfits and
// 	// planets) are written to the player's save and need a name to prevent data loss.
// 	void UniverseObjects::CheckReferences()
// 	{
// 		// Parse all GameEvents for object definitions.
// 		auto deferred = map<string, set<string>>{};
// 		for(auto &&it : events)
// 		{
// 			// Stock GameEvents are serialized in MissionActions by name.
// 			if(it.second.Name().empty())
// 				NameAndWarn("event", it);
// 			else
// 			{
// 				// Any already-named event (i.e. loaded) may alter the universe.
// 				auto definitions = GameEvent::DeferredDefinitions(it.second.Changes());
// 				for(auto &&type : definitions)
// 					deferred[type.first].insert(type.second.begin(), type.second.end());
// 			}
// 		}
// 	
// 		// Stock conversations are never serialized.
// 		for(const auto &it : conversations)
// 			if(it.second.IsEmpty())
// 				Warn("conversation", it.first);
// 		// The "default intro" conversation must invoke the prompt to set the player's name.
// 		if(!conversations.Get("default intro")->IsValidIntro())
// 			Logger::LogError("Error: the \"default intro\" conversation must contain a \"name\" $node.");
// 		// Effects are serialized as a part of ships.
// 		for(auto &&it : effects)
// 			if(it.second.Name().empty())
// 				NameAndWarn("effect", it);
// 		// Fleets are not serialized. Any changes via events are written as DataNodes and thus self-define.
// 		for(auto &&it : fleets)
// 		{
// 			// Plugins may alter stock fleets with new variants that exclusively use plugin ships.
// 			// Rather than disable the whole fleet due to these non-instantiable variants, remove them.
// 			it.second.RemoveInvalidVariants();
// 			if(!it.second.IsValid() && !deferred["fleet"].count(it.first))
// 				Warn("fleet", it.first);
// 		}
// 		// Government names are used in mission NPC blocks and LocationFilters.
// 		for(auto &&it : governments)
// 			if(it.second.GetTrueName().empty() && !NameIfDeferred(deferred["government"], it))
// 				NameAndWarn("government", it);
// 		// Minables are not serialized.
// 		for(const auto &it : minables)
// 			if(it.second.TrueName().empty())
// 				Warn("minable", it.first);
// 		// Stock missions are never serialized, and an accepted mission is
// 		// always fully defined (though possibly not "valid").
// 		for(const auto &it : missions)
// 			if(it.second.Name().empty())
// 				Warn("mission", it.first);
// 	
// 		// News are never serialized or named, except by events (which would then define them).
// 	
// 		// Outfit names are used by a number of classes.
// 		for(auto &&it : outfits)
// 			if(it.second.TrueName().empty())
// 				NameAndWarn("outfit", it);
// 		// Outfitters are never serialized.
// 		for(const auto &it : outfitSales)
// 			if(it.second.empty() && !deferred["outfitter"].count(it.first))
// 				Logger::LogError("Warning: outfitter \"" + it.first + "\" is referred to, but has no outfits.");
// 		// Phrases are never serialized.
// 		for(const auto &it : phrases)
// 			if(it.second.Name().empty())
// 				Warn("phrase", it.first);
// 		// Planet names are used by a number of classes.
// 		for(auto &&it : planets)
// 			if(it.second.TrueName().empty() && !NameIfDeferred(deferred["planet"], it))
// 				NameAndWarn("planet", it);
// 		// Ship model names are used by missions and depreciation.
// 		for(auto &&it : ships)
// 			if(it.second.TrueModelName().empty())
// 			{
// 				it.second.SetTrueModelName(it.first);
// 				Warn("ship", it.first);
// 			}
// 		// Shipyards are never serialized.
// 		for(const auto &it : shipSales)
// 			if(it.second.empty() && !deferred["shipyard"].count(it.first))
// 				Logger::LogError("Warning: shipyard \"" + it.first + "\" is referred to, but has no ships.");
// 		// System names are used by a number of classes.
// 		for(auto &&it : systems)
// 			if(it.second.Name().empty() && !NameIfDeferred(deferred["system"], it))
// 				NameAndWarn("system", it);
// 		// Hazards are never serialized.
// 		for(const auto &it : hazards)
// 			if(!it.second.IsValid())
// 				Warn("hazard", it.first);
// 		// Wormholes are never serialized.
// 		for(const auto &it : wormholes)
// 			if(it.second.Name().empty())
// 				Warn("wormhole", it.first);
// 	
// 		// Formation patterns are not serialized, but their usage is.
// 		for(auto &&it : formations)
// 			if(it.second.Name().empty())
// 				NameAndWarn("formation", it);
// 		// Any stock colors should have been loaded from game data files.
// 		for(const auto &it : colors)
// 			if(!it.second.IsLoaded())
// 				Warn("color", it.first);
// 	}
// 
// }