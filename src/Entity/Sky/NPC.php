<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;
use App\Entity\DataWriter;
use App\Entity\TemplatedArray;

use App\Service\TemplatedArrayService;

enum NPCTrigger {
	case KILL; 
	case BOARD; 
	case ASSIST; 
	case DISABLE; 
	case SCAN_CARGO; 
	case SCAN_OUTFITS; 
	case CAPTURE; 
	case PROVOKE;
};

#[ORM\Entity]
#[ORM\Table(name: 'NPC')]
class NPC {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	// The government of the ships in this NPC:
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Government')]
	#[ORM\JoinColumn(nullable: true, name: 'governmentId')]
	private ?Government $government = null;
	private Personality $personality;
	
	// The cargo ships in this NPC will be able to carry.
	private FleetCargo $cargo;
	private bool $overrideFleetCargo = false;
	
	private EsUuid $uuid;
	
	// Start out in a location matching this filter, or in a particular system:
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\LocationFilter', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'locationId')]
	private LocationFilter $location;
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\System')]
	#[ORM\JoinColumn(nullable: true, name: 'systemId')]
	private ?System $system = null;
	#[ORM\Column(type: 'boolean')]
	private bool $isAtDestination = false;
	// Start out landed on this planet.
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Planet')]
	#[ORM\JoinColumn(nullable: true, name: 'planetId')]
	private ?Planet $planet = null;
	
	// Dialog or conversation to show when all requirements for this NPC are met:
	#[ORM\Column(type: 'text')]
	private string $dialogText = '';
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\Phrase', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'phraseId')]
	private Phrase $dialogPhrase; //ExclusiveItem<Phrase>
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\Conversation', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'conversationId')]
	private Conversation $conversation; //ExclusiveItem<Conversation>
	
	// Conditions that must be met in order for this NPC to be placed or despawned:
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'toSpawnId')]
	private ConditionSet $toSpawn;
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'toDespawnId')]
	private ConditionSet $toDespawn;
	// Once true, the NPC will be spawned on takeoff and its success state will influence
	// the parent mission's ability to be completed.
	#[ORM\Column(type: 'boolean')]
	private bool $passedSpawnConditions = false;
	// Once true, the NPC will be despawned on landing and it will no longer contribute to
	// the parent mission's ability to be completed or failed.
	#[ORM\Column(type: 'boolean')]
	private bool $passedDespawnConditions = false;
	// Whether we have actually checked spawning conditions yet. (This
	// will generally be true, except when reloading a save.)
	#[ORM\Column(type: 'boolean')]
	private bool $checkedSpawnConditions = false;
	
	// The ships may be listed individually or referred to as a fleet, and may
	// be customized or just refer to stock objects:
	private array $ships = []; // list<shared_ptr<Ship>>
	private array $stockShips = []; // list<Ship *>
	private array $shipNames = []; // list<string>
	private array $fleets = []; // list<ExclusiveItem<Fleet>>
	
	// This must be done to each ship in this set to complete the mission:
	#[ORM\Column(type: 'integer')]
	private int $succeedIf = 0;
	#[ORM\Column(type: 'integer')]
	private int $failIf = 0;
	#[ORM\Column(type: 'boolean')]
	private bool $mustEvade = false;
	#[ORM\Column(type: 'boolean')]
	private bool $mustAccompany = false;
	// The ShipEvent actions that have been done to each ship.
	private array $shipEvents = []; // map<Ship *, int>
	
	// The NPCActions that this NPC can run on certain events/triggers.
	private TemplatedArray $npcActions; // map<Trigger, NPCAction>
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Mission', inversedBy: 'npcs', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'missionId')]
	private $mission;
	
	public static array $triggerNames = [
		"kill" => NPCTrigger::KILL,
		"board" => NPCTrigger::BOARD,
		"assist" => NPCTrigger::ASSIST,
		"disable" => NPCTrigger::DISABLE,
		"scan cargo" => NPCTrigger::SCAN_CARGO,
		"scan outfits" => NPCTrigger::SCAN_OUTFITS,
		"capture" => NPCTrigger::CAPTURE,
		"provoke" => NPCTrigger::PROVOKE
	];
	// 
	// 
	// namespace {
	// 	string TriggerToText(NPC::Trigger trigger)
	// 	{
	// 		switch(trigger)
	// 		{
	// 			case NPC::Trigger::KILL:
	// 				return "on kill";
	// 			case NPC::Trigger::BOARD:
	// 				return "on board";
	// 			case NPC::Trigger::ASSIST:
	// 				return "on assist";
	// 			case NPC::Trigger::DISABLE:
	// 				return "on disable";
	// 			case NPC::Trigger::SCAN_CARGO:
	// 				return "on 'scan cargo'";
	// 			case NPC::Trigger::SCAN_OUTFITS:
	// 				return "on 'scan outfits'";
	// 			case NPC::Trigger::CAPTURE:
	// 				return "on capture";
	// 			case NPC::Trigger::PROVOKE:
	// 				return "on provoke";
	// 			default:
	// 				return "unknown trigger";
	// 		}
	// 	}
	// }
	
	// Construct and Load() at the same time.
	public function __construct(?DataNode $node = null, ?string $missionName = '') {
		$this->personality = new Personality();
		$this->cargo = new FleetCargo();
		$this->uuid = new EsUuid();
		$this->location = new LocationFilter();
		$this->toSpawn = new ConditionSet();
		$this->toDespawn = new ConditionSet();
		$this->npcActions = TemplatedArrayService::Instance()->createTemplatedArray(NPCAction::class, 'trigger');
		if ($node && $missionName) {
			$this->load($node, $missionName);
		}
	}
	
	
	
	public function load(DataNode $node, string $missionName) {
		// Any tokens after the "npc" tag list the things that must happen for this
		// mission to succeed.
		for ($i = 1; $i < $node->size(); ++$i) {

			if ($node->getToken($i) == "save") {
				$this->failIf |= ShipEvent::DESTROY;
			} else if ($node->getToken($i) == "kill") {
				$this->succeedIf |= ShipEvent::DESTROY;
			} else if ($node->getToken($i) == "board") {
				$this->succeedIf |= ShipEvent::BOARD;
			} else if ($node->getToken($i) == "assist") {
				$this->succeedIf |= ShipEvent::ASSIST;
			} else if ($node->getToken($i) == "disable") {
				$this->succeedIf |= ShipEvent::DISABLE;
			} else if ($node->getToken($i) == "scan cargo") {
				$this->succeedIf |= ShipEvent::SCAN_CARGO;
			} else if ($node->getToken($i) == "scan outfits") {
				$this->succeedIf |= ShipEvent::SCAN_OUTFITS;
			} else if ($node->getToken($i) == "capture") {
				$this->succeedIf |= ShipEvent::CAPTURE;
			} else if ($node->getToken($i) == "provoke") {
				$this->succeedIf |= ShipEvent::PROVOKE;
			} else if ($node->getToken($i) == "evade") {
				$this->mustEvade = true;
			} else if ($node->getToken($i) == "accompany") {
				$this->mustAccompany = true;
			} else {
				$node->printTrace("Warning: Skipping unrecognized NPC completion condition \"" . $node->getToken($i) . "\":");
			}
		}
	
		// Check for incorrect objective combinations.
		if ($this->failIf & ShipEvent::DESTROY && ($this->succeedIf & ShipEvent::DESTROY || $this->succeedIf & ShipEvent::CAPTURE)) {
			$node->printTrace("Error: conflicting NPC mission objective to save and destroy or capture.");
		}
		if ($this->mustEvade && $this->mustAccompany) {
			$node->printTrace("Warning: NPC mission objective to accompany and evade is synonymous with kill.");
		}
		if ($this->mustEvade && ($this->succeedIf & ShipEvent::DESTROY || $this->succeedIf & ShipEvent::CAPTURE)) {
			$node->printTrace("Warning: redundant NPC mission objective to evade and destroy or capture.");
		}
	
		foreach ($node as $child) {
			if ($child->getToken(0) == "system") {
				if ($child->size() >= 2) {
					if ($child->getToken(1) == "destination") {
						$this->isAtDestination = true;
					} else {
						$this->system = GameData::Systems()[$child->getToken(1)];
					}
				} else {
					$this->location->load($child);
				}
			} else if ($child->getToken(0) == "uuid" && $child->size() >= 2) {
				$this->uuid = EsUuid::FromString($child->getToken(1));
			} else if ($child->getToken(0) == "planet" && $child->size() >= 2) {
				$this->planet = GameData::Planets()[$child->getToken(1)];
			} else if ($child->getToken(0) == "succeed" && $child->size() >= 2) {
				$this->succeedIf = $child->getValue(1);
			} else if ($child->getToken(0) == "fail" && $child->size() >= 2) {
				$this->failIf = $child->getValue(1);
			} else if ($child->getToken(0) == "evade") {
				$this->mustEvade = true;
			} else if ($child->getToken(0) == "accompany") {
				$this->mustAccompany = true;
			} else if ($child->getToken(0) == "government" && $child->size() >= 2) {
				$this->government = GameData::Governments()[$child->getToken(1)];
			} else if ($child->getToken(0) == "personality") {
				$this->personality->load($child);
			} else if ($child->getToken(0) == "cargo settings" && $child->hasChildren()) {
				$this->cargo->load($child);
				$this->overrideFleetCargo = true;
			} else if ($child->getToken(0) == "dialog") {
				$hasValue = ($child->size() > 1);
				// Dialog text may be supplied from a stock named phrase, a
				// private unnamed phrase, or directly specified.
				if ($hasValue && $child->getToken(1) == "phrase") {
					if (!$child->hasChildren() && $child->size() == 3) {
						$this->dialogPhrase = GameData::Phrases()[$child->getToken(2)];
					} else {
						$child->printTrace("Skipping unsupported dialog phrase syntax:");
					}
				} else if (!$hasValue && $child->hasChildren() && $child[0]->getToken(0) == "phrase") {
					$firstGrand = $child[0];
					if ($firstGrand->size() == 1 && $firstGrand->hasChildren()) {
						$this->dialogPhrase = new Phrase($firstGrand);
					} else {
						$firstGrand->printTrace("Skipping unsupported dialog phrase syntax:");
					}
				} else {
					Dialog::ParseTextNode($child, 1, $this->dialogText);
				}
			} else if ($child->getToken(0) == "conversation" && $child->hasChildren()) {
				$this->conversation = new Conversation($child);
			} else if ($child->getToken(0) == "conversation" && $child->size() > 1) {
				$this->conversation = GameData::Conversations()[$child->getToken(1)];
			} else if ($child->getToken(0) == "to" && $child->size() >= 2) {
				if ($child->getToken(1) == "spawn") {
					$this->toSpawn->load($child);
				} else if ($child->getToken(1) == "despawn") {
					$this->toDespawn->load($child);
				} else {
					$child->printTrace("Skipping unrecognized attribute:");
				}
			} else if ($child->getToken(0) == "on" && $child->size() >= 2) {
				if (!isset(self::$triggerNames[$child->getToken(1)])) {
					$child->printTrace("Skipping unrecognized attribute:");
				} else {
					$this->npcActions[self::$triggerNames[$child->getToken(1)]->name]->load($child, $missionName);	
				}
			} else if ($child->getToken(0) == "ship") {
				if ($child->hasChildren() && $child->size() == 2) {
					// Loading an NPC from a save file, or an entire ship specification.
					// The latter may result in references to non-instantiated outfits.
					$ship = new Ship($child);
					$this->ships []= $ship;
					foreach ($child as $grand) {
						if ($grand->getToken(0) == "actions" && $grand->size() >= 2) {
							$this->shipEvents[$ship->get()] = $grand->getValue(1);
						}
					}
				} else if ($child->size() >= 2) {
					// Loading a ship managed by GameData, i.e. "base models" and variants.
					$this->stockShips []= GameData::Ships()[$child->getToken(1)];
					$this->shipNames []= $child->getToken(1 + ($child->size() > 2));
				} else {
					$message = "Error: Skipping unsupported use of a ship token and child nodes: ";
					if ($child->size() >= 3) {
						$message .= "to both name and customize a ship, create a variant and then reference it here.";
					} else {
						$message .= "the \'ship\' token must be followed by the name of a ship, e.g. ship \"Bulk Freighter\"";
					}
					$child->printTrace($message);
				}
			} else if ($child->getToken(0) == "fleet") {
				if ($child->hasChildren()) {
					$fleet = new Fleet($child);
					$this->fleets []= $fleet;
					if ($child->size() >= 2) {
						// Copy the custom fleet in lieu of reparsing the same DataNode.
						$numAdded = $child->getValue(1);
						for ($i = 1; $i < $numAdded; ++$i) {
							$this->fleets []= $fleet;
						}
					}
				} else if ($child->size() >= 2) {
					$fleet = GameData::Fleets()[$child->getToken(1)];
					if ($child->size() >= 3 && $child->getValue(2) > 1.) {
						for ($i=0; $i < $child->getValue(2); $i++) {
							$this->fleets []= $fleet;
						}
					} else {
						$this->fleets []= $fleet;
					}
				}
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	
		// Empty spawning conditions imply that an instantiated NPC has spawned (or
		// if this is an NPC template, that any NPCs created from this will spawn).
		$this->passedSpawnConditions = $this->toSpawn->isEmpty();
		// (Any non-empty `toDespawn` set is guaranteed to evaluate to false, otherwise the NPC would never
		// have been serialized. Thus, `passedDespawnConditions` is always false if the NPC is being Loaded.)
	
		// Since a ship's government is not serialized, set it now.
		foreach ($this->ships as $ship) {
			$this->ship->setGovernment($this->government);
			$this->ship->setPersonality($this->personality);
			$this->ship->setIsSpecial();
			$this->ship->finishLoading(false);
		}
	}
	
	
	
// 	// Note: the Save() function can assume this is an instantiated NPC, not
// 	// a template, so fleets will be replaced by individual ships already.
// 	void NPC::Save(DataWriter &out) const
// 	{
// 		// If this NPC should no longer appear in-game, don't serialize it.
// 		if (passedDespawnConditions) {
// 			return;
// 	
// 		out.Write("npc");
// 		out.BeginChild();
// 		{
// 			out.Write("uuid", uuid.ToString());
// 			if (succeedIf) {
// 				out.Write("succeed", succeedIf);
// 			if (failIf) {
// 				out.Write("fail", failIf);
// 			if (mustEvade) {
// 				out.Write("evade");
// 			if (mustAccompany) {
// 				out.Write("accompany");
// 	
// 			// Only save out spawn conditions if they have yet to be met.
// 			// This is so that if a player quits the game and returns, NPCs that
// 			// were spawned do not then become despawned because they no longer
// 			// pass the spawn conditions.
// 			if (!toSpawn.IsEmpty() && !passedSpawnConditions) {
// 
// 				out.Write("to", "spawn");
// 				out.BeginChild();
// 				{
// 					toSpawn.Save(out);
// 				}
// 				out.EndChild();
// 			}
// 			if (!toDespawn.IsEmpty()) {
// 
// 				out.Write("to", "despawn");
// 				out.BeginChild();
// 				{
// 					toDespawn.Save(out);
// 				}
// 				out.EndChild();
// 			}
// 	
// 			for (auto &it : npcActions) {
// 				it.second.Save(out);
// 	
// 			if (government) {
// 				out.Write("government", government->GetTrueName());
// 			personality.Save(out);
// 	
// 			if (!dialogText.empty()) {
// 
// 				out.Write("dialog");
// 				out.BeginChild();
// 				{
// 					// Break the text up into paragraphs.
// 					for (const string &line : Format::Split(dialogText, "\n\t")) {
// 						out.Write(line);
// 				}
// 				out.EndChild();
// 			}
// 			if (!conversation->IsEmpty()) {
// 				conversation->Save(out);
// 	
// 			for (const shared_ptr<Ship> &ship : ships) {
// 
// 				ship->Save(out);
// 				auto it = shipEvents.find(ship.get());
// 				if (it != shipEvents.end() && it->second) {
// 
// 					// Append an "actions" tag to the end of the ship data.
// 					out.BeginChild();
// 					{
// 						out.Write("actions", it->second);
// 					}
// 					out.EndChild();
// 				}
// 			}
// 		}
// 		out.EndChild();
// 	}
// 	
// 	
// 	
// 	string NPC::Validate(bool asTemplate) const
// 	{
// 		// An NPC with no government will take the player's government
// 	
// 		// NPC templates have certain fields to validate that instantiated NPCs do not:
// 		if (asTemplate) {
// 
// 			// A location filter may be used to set the starting system.
// 			// If given, it must be able to resolve to a valid system.
// 			if (!location.IsValid()) {
// 				return "location filter";
// 	
// 			// A null system reference is allowed, since it will be set during
// 			// instantiation if not given explicitly.
// 			if (system && !system->IsValid()) {
// 				return "system \"" + system->Name() + "\"";
// 	
// 			// A planet is optional, but if given must be valid.
// 			if (planet && !planet->IsValid()) {
// 				return "planet \"" + planet->TrueName() + "\"";
// 	
// 			// If a stock phrase or conversation is given, it must not be empty.
// 			if (dialogPhrase.IsStock() && dialogPhrase->IsEmpty()) {
// 				return "stock phrase";
// 			if (conversation.IsStock() && conversation->IsEmpty()) {
// 				return "stock conversation";
// 	
// 			// NPC fleets, unlike stock fleets, do not need a valid government
// 			// since they will unconditionally inherit this NPC's government.
// 			for (auto &&fleet : fleets) {
// 				if (!fleet->IsValid(false)) {
// 					return fleet.IsStock() ? "stock fleet" : "custom fleet";
// 		}
// 	
// 		// Ships must always be valid.
// 		for (auto &&ship : ships) {
// 			if (!ship->IsValid()) {
// 				return "ship \"" + ship->Name() + "\"";
// 		for (auto &&ship : stockShips) {
// 			if (!ship->IsValid()) {
// 				return "stock model \"" + ship->VariantName() + "\"";
// 	
// 		return "";
// 	}
// 	
// 	
// 	
// 	const EsUuid &NPC::UUID() const noexcept
// 	{
// 		return uuid;
// 	}
// 	
// 	
// 	
// 	// Update spawning and despawning for this NPC.
// 	void NPC::UpdateSpawning(const PlayerInfo &player)
// 	{
// 		checkedSpawnConditions = true;
// 		// The conditions are tested every time this function is called until
// 		// they pass. This is so that a change in a player's conditions don't
// 		// cause an NPC to "un-spawn" or "un-despawn." Despawn conditions are
// 		// only checked after the spawn conditions have passed so that an NPC
// 		// doesn't "despawn" before spawning in the first place.
// 		if (!passedSpawnConditions) {
// 			passedSpawnConditions = toSpawn.Test(player.Conditions());
// 	
// 		// It is allowable for an NPC to pass its spawning conditions and then immediately pass its despawning
// 		// conditions. (Any such NPC will never be spawned in-game.)
// 		if (passedSpawnConditions && !toDespawn.IsEmpty() && !passedDespawnConditions) {
// 			passedDespawnConditions = toDespawn.Test(player.Conditions());
// 	}
// 	
// 	
// 	
// 	// Determine if this NPC should be placed in-flight.
// 	bool NPC::ShouldSpawn() const
// 	{
// 		return passedSpawnConditions && !passedDespawnConditions;
// 	}
// 	
// 	
// 	
// 	// Get the ships associated with this set of NPCs.
// 	const list<shared_ptr<Ship>> NPC::Ships() const
// 	{
// 		return ships;
// 	}
// 	
// 	
// 	
// 	// Handle the given ShipEvent.
// 	void NPC::Do(const ShipEvent &event, PlayerInfo &player, UI *ui, bool isVisible)
// 	{
// 		// First, check if this ship is part of this NPC. If not, do nothing. If it
// 		// is an NPC and it just got captured, replace it with a destroyed copy of
// 		// itself so that this class thinks the ship is destroyed.
// 		shared_ptr<Ship> ship;
// 		int type = event.Type();
// 		for (shared_ptr<Ship> &ptr : ships) {
// 			if (ptr == event.Target()) {
// 
// 				// If a mission ship is captured, let it live on under its new
// 				// ownership but mark our copy of it as destroyed. This must be done
// 				// before we check the mission's success status because otherwise
// 				// momentarily reactivating a ship you're supposed to evade would
// 				// clear the success status and cause the success message to be
// 				// displayed a second time below.
// 				if (event.Type() & ShipEvent::CAPTURE) {
// 
// 					Ship *copy = new Ship(*ptr);
// 					copy->SetUUID(ptr->UUID());
// 					copy->Destroy();
// 					shipEvents[copy] = shipEvents[ptr.get()];
// 					// Count this ship as destroyed, as well as captured.
// 					type |= ShipEvent::DESTROY;
// 					ptr.reset(copy);
// 				}
// 				ship = ptr;
// 				break;
// 			}
// 		if (!ship) {
// 			return;
// 	
// 		// Determine if this NPC is already in the succeeded state,
// 		// regardless of whether it will despawn on the next landing.
// 		bool alreadySucceeded = HasSucceeded(player.GetSystem(), false);
// 		bool alreadyFailed = HasFailed();
// 	
// 		// If this event was "ASSIST", the ship is now known as not disabled.
// 		if (type == ShipEvent::ASSIST) {
// 			shipEvents[ship.get()] &= ~(ShipEvent::DISABLE);
// 	
// 		// Certain events only count towards the NPC's status if originated by
// 		// the player: scanning, boarding, assisting, capturing, or provoking.
// 		if (!event.ActorGovernment() || !event.ActorGovernment()->IsPlayer()) {
// 			type &= ~(ShipEvent::SCAN_CARGO | ShipEvent::SCAN_OUTFITS | ShipEvent::ASSIST
// 					| ShipEvent::BOARD | ShipEvent::CAPTURE | ShipEvent::PROVOKE);
// 	
// 		// Determine if this event is new for this ship.
// 		bool newEvent = ~(shipEvents[ship.get()]) & type;
// 		// Apply this event to the ship and any ships it is carrying.
// 		shipEvents[ship.get()] |= type;
// 		for (const Ship::Bay &bay : ship->Bays()) {
// 			if (bay.ship) {
// 				shipEvents[bay.ship.get()] |= type;
// 	
// 		// Run any mission actions that trigger on this event.
// 		DoActions(event, newEvent, player, ui);
// 	
// 		// Check if the success status has changed. If so, display a message.
// 		if (isVisible && !alreadyFailed && HasFailed()) {
// 			Messages::Add("Mission failed.", Messages::Importance::Highest);
// 		} else if (ui && !alreadySucceeded && HasSucceeded(player.GetSystem(), false)) {
// 
// 			// If "completing" this NPC displays a conversation, reference
// 			// it, to allow the completing event's target to be destroyed.
// 			if (!conversation->IsEmpty()) {
// 				ui->Push(new ConversationPanel(player, *conversation, nullptr, ship));
// 			if (!dialogText.empty()) {
// 				ui->Push(new Dialog(dialogText));
// 		}
// 	}
// 	
// 	
// 	
// 	bool NPC::HasSucceeded(const System *playerSystem, bool ignoreIfDespawnable) const
// 	{
// 		// If this NPC has not yet spawned, or has fully despawned, then ignore its
// 		// objectives. An NPC that will despawn on landing is allowed to still enter
// 		// a "completed" state and trigger related completion events.
// 		if (checkedSpawnConditions && (!passedSpawnConditions
// 				|| (ignoreIfDespawnable && passedDespawnConditions)))
// 			return true;
// 	
// 		if (HasFailed()) {
// 			return false;
// 	
// 		// Evaluate the status of each ship in this NPC block. If it has `accompany`
// 		// and is alive then it cannot be disabled and must be in the player's system.
// 		// If the NPC block has `evade`, the ship can be disabled, destroyed, captured,
// 		// or not present.
// 		if (mustEvade || mustAccompany) {
// 			for (const shared_ptr<Ship> &ship : ships) {
// 
// 				auto it = shipEvents.find(ship.get());
// 				// If a derelict ship has not received any ShipEvents, it is immobile.
// 				bool isImmobile = ship->GetPersonality().IsDerelict();
// 				// The success status calculation can only be based on recorded
// 				// events (and the current system).
// 				if (it != shipEvents.end()) {
// 
// 					// Captured or destroyed ships have either succeeded or no longer count.
// 					if (it->second & (ShipEvent::DESTROY | ShipEvent::CAPTURE)) {
// 						continue;
// 					// A ship that was disabled is considered 'immobile'.
// 					isImmobile = (it->second & ShipEvent::DISABLE);
// 					// If this NPC is 'derelict' and has no ASSIST on record, it is immobile.
// 					isImmobile |= ship->GetPersonality().IsDerelict()
// 						&& !(it->second & ShipEvent::ASSIST);
// 				}
// 				bool isHere = false;
// 				// If this ship is being carried, check the parent's system.
// 				if (!ship->GetSystem() && ship->CanBeCarried() && ship->GetParent()) {
// 					isHere = ship->GetParent()->GetSystem() == playerSystem;
// 				} else
// 					isHere = (!ship->GetSystem() || ship->GetSystem() == playerSystem);
// 				if ((isHere && !isImmobile) ^ mustAccompany) {
// 					return false;
// 			}
// 	
// 		if (!succeedIf) {
// 			return true;
// 	
// 		for (const shared_ptr<Ship> &ship : ships) {
// 
// 			auto it = shipEvents.find(ship.get());
// 			if (it == shipEvents.end() || (it->second & succeedIf) != succeedIf) {
// 				return false;
// 		}
// 	
// 		return true;
// 	}
// 	
// 	
// 	
// 	// Check if the NPC is supposed to be accompanied and is not.
// 	bool NPC::IsLeftBehind(const System *playerSystem) const
// 	{
// 		if (HasFailed()) {
// 			return true;
// 		if (!mustAccompany) {
// 			return false;
// 	
// 		for (const shared_ptr<Ship> &ship : ships) {
// 			if (ship->IsDisabled() || ship->GetSystem() != playerSystem) {
// 				return true;
// 	
// 		return false;
// 	}
// 	
// 	
// 	
// 	bool NPC::HasFailed() const
// 	{
// 		// An unspawned NPC, one which will despawn on landing, or that has
// 		// already despawned, is not considered "failed."
// 		if (!passedSpawnConditions || passedDespawnConditions) {
// 			return false;
// 	
// 		for (const auto &it : shipEvents) {
// 
// 			if (it.second & failIf) {
// 				return true;
// 	
// 			// If we still need to perform an action on this NPC, then that ship
// 			// being destroyed should cause the mission to fail.
// 			if ((~it.second & succeedIf) && (it.second & ShipEvent::DESTROY)) {
// 				return true;
// 		}
// 	
// 		return false;
// 	}
// 	
// 	
// 	
// 	// Create a copy of this NPC but with the fleets replaced by the actual
// 	// ships they represent, wildcards in the conversation text replaced, etc.
// 	NPC NPC::Instantiate(map<string, string> &subs, const System *origin, const System *destination,
// 			int jumps, int64_t payload) const
// 	{
// 		NPC result;
// 		result.government = government;
// 		if (!result.government) {
// 			result.government = GameData::PlayerGovernment();
// 		result.personality = personality;
// 		result.succeedIf = succeedIf;
// 		result.failIf = failIf;
// 		result.mustEvade = mustEvade;
// 		result.mustAccompany = mustAccompany;
// 	
// 		result.passedSpawnConditions = passedSpawnConditions;
// 		result.toSpawn = toSpawn;
// 		result.toDespawn = toDespawn;
// 	
// 		// Instantiate the actions.
// 		string reason;
// 		auto ait = npcActions.begin();
// 		for ( ; ait != npcActions.end(); ++ait) {
// 
// 			reason = ait->second.Validate();
// 			if (!reason.empty()) {
// 				break;
// 		}
// 		if (ait != npcActions.end()) {
// 
// 			Logger::LogError("Instantiation Error: Action \"" + TriggerToText(ait->first) +
// 					"\" in NPC uses invalid " + std::move(reason));
// 			return result;
// 		}
// 		for (const auto &it : npcActions) {
// 			result.npcActions[it.first] = it.second.Instantiate(subs, origin, jumps, payload);
// 	
// 		// Pick the system for this NPC to start out in.
// 		result.system = system;
// 		if (!result.system && !location.IsEmpty()) {
// 			result.system = location.PickSystem(origin);
// 		if (!result.system) {
// 			result.system = (isAtDestination && destination) ? destination : origin;
// 		// If a planet was specified in the template, it must be in this system.
// 		if (planet && result.system->FindStellar(planet)) {
// 			result.planet = planet;
// 	
// 		// Convert fleets into instances of ships.
// 		for (const shared_ptr<Ship> &ship : ships) {
// 
// 			// This ship is being defined from scratch.
// 			result.ships.push_back(make_shared<Ship>(*ship));
// 			result.ships.back()->FinishLoading(true);
// 		}
// 		auto shipIt = stockShips.begin();
// 		auto nameIt = shipNames.begin();
// 		for ( ; shipIt != stockShips.end() && nameIt != shipNames.end(); ++shipIt, ++nameIt) {
// 
// 			result.ships.push_back(make_shared<Ship>(**shipIt));
// 			result.ships.back()->SetName(*nameIt);
// 		}
// 		for (const ExclusiveItem<Fleet> &fleet : fleets) {
// 			fleet->Place(*result.system, result.ships, false, !overrideFleetCargo);
// 		// Ships should either "enter" the system or start out there.
// 		for (const shared_ptr<Ship> &ship : result.ships) {
// 
// 			ship->SetGovernment(result.government);
// 			ship->SetIsSpecial();
// 			ship->SetPersonality(result.personality);
// 			if (result.personality.IsDerelict()) {
// 				ship->Disable();
// 	
// 			if (personality.IsEntering()) {
// 				Fleet::Enter(*result.system, *ship);
// 			} else if (result.planet) {
// 
// 				// A valid planet was specified in the template, so these NPCs start out landed.
// 				ship->SetSystem(result.system);
// 				ship->SetPlanet(result.planet);
// 			} else
// 				Fleet::Place(*result.system, *ship);
// 		}
// 	
// 		// Set the cargo for each ship in the NPC if the NPC itself has cargo settings.
// 		if (overrideFleetCargo) {
// 			for (auto ship : result.ships) {
// 				cargo.SetCargo(&*ship);
// 	
// 		// String replacement:
// 		if (!result.ships.empty()) {
// 
// 			subs["<npc>"] = result.ships.front()->Name();
// 			subs["<npc model>"] = result.ships.front()->DisplayModelName();
// 		}
// 		// Do string replacement on any dialog or conversation.
// 		string dialogText = !dialogPhrase->IsEmpty() ? dialogPhrase->Get() : this->dialogText;
// 		if (!dialogText.empty()) {
// 			result.dialogText = Format::Replace(Phrase::ExpandPhrases(dialogText), subs);
// 	
// 		if (!conversation->IsEmpty()) {
// 			result.conversation = ExclusiveItem<Conversation>(conversation->Instantiate(subs));
// 	
// 		return result;
// 	}
// 	
// 	
// 	
// 	// Handle any NPC mission actions that may have been triggered by a ShipEvent.
// 	void NPC::DoActions(const ShipEvent &event, bool newEvent, PlayerInfo &player, UI *ui)
// 	{
// 		// Map the ShipEvent that was received to the Triggers it could flip.
// 		static const map<int, vector<Trigger>> eventTriggers = {
// 			{ShipEvent::DESTROY, {KILL}},
// 			{ShipEvent::BOARD, {BOARD}},
// 			{ShipEvent::ASSIST, {ASSIST}},
// 			{ShipEvent::DISABLE, {DISABLE}},
// 			{ShipEvent::SCAN_CARGO, {SCAN_CARGO}},
// 			{ShipEvent::SCAN_OUTFITS, {SCAN_OUTFITS}},
// 			{ShipEvent::CAPTURE, {CAPTURE}},
// 			{ShipEvent::PROVOKE, {PROVOKE}},
// 		};
// 	
// 		int type = event.Type();
// 	
// 		// Ships are capable of receiving multiple DESTROY events. Only
// 		// handle the first such event, because a ship can't actually be
// 		// destroyed multiple times.
// 		if (type == ShipEvent::DESTROY && !newEvent) {
// 			return;
// 	
// 		// Get the actions for the Triggers that could potentially run.
// 		auto triggers = eventTriggers.find(type);
// 		if (triggers == eventTriggers.end()) {
// 			return;
// 	
// 		for (Trigger trigger : triggers->second) {
// 
// 			auto it = npcActions.find(trigger);
// 			if (it == npcActions.end()) {
// 				continue;
// 	
// 			// The PROVOKE Trigger only requires a single ship to receive the
// 			// event in order to run. All other Triggers require that all ships
// 			// be affected.
// 			if (trigger == PROVOKE || all_of(ships.begin(), ships.end(),
// 					[&](const shared_ptr<Ship> &ship) -> bool
// 					{
// 						auto it = shipEvents.find(ship.get());
// 						return it != shipEvents.end() && it->second & type;
// 					}))
// 			{
// 				it->second.Do(player, ui);
// 			}
// 		}
// 	}

}