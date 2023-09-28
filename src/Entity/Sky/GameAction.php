<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use ApiPlatform\Metadata\ApiResource;

use App\Entity\DataNode;
use App\Entity\DataWriter;

// A GameAction represents what happens when a Mission or Conversation reaches
// a certain milestone. This can include when the Mission is offered, accepted,
// declined, completed, or failed, or when a Conversation reaches an "action" node.
// GameActions might include giving the player payment or a special item,
// modifying condition flags, or queueing a GameEvent to occur. Any new mechanics
// added to GameAction should be able to be safely executed while in a
// Conversation.
#[ORM\Entity]
#[ORM\Table(name: 'GameAction')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class GameAction {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'boolean')]
	private bool $isEmpty = true;
	#[ORM\Column(type: 'text')]
	private string $logText = '';
	#[ORM\Column(type: 'text')]
	private string $specialLogTextStr;
	private array $specialLogText = []; // map<string, map<string, string>>
	
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\EventTrigger', mappedBy: 'gameAction', cascade: ['persist'])]
	private Collection $eventTriggers;
	
	private array $events = []; // map<const GameEvent *, pair<int, int>> change to $eventName => ['event'=>$event, 'minDays'=>$minDays, 'maxDays'=>$maxDays]
	private array $giftShips = []; // vector<ShipManager>
	private array $giftOutfits = []; // map<const Outfit *, int> change to $outfitName => ['outfit'=>$outfit, 'giftCount'=>$count]
	
	private int $payment = 0;
	private int $paymentMultiplier = 0;
	private int $fine = 0;
	
	// When this action is performed, the missions with these names fail.
	private array $fail = []; // set<string>
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', inversedBy: 'conversationAction', cascade: ['persist'])]
	private ConditionSet $conditions;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\Node', inversedBy: 'actions', cascade: ['persist'])]
	public Node $conversationNode;
	
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\MissionAction', mappedBy: 'action', cascade: ['persist'])]
	private MissionAction $missionAction;
	
	#[ORM\Column(type: 'string')]
	private string $sourceName = '';
	#[ORM\Column(type: 'string')]
	private string $sourceFile = '';
	#[ORM\Column(type: 'string')]
	private string $sourceVersion = '';
	
	// Construct and Load() at the same time.
	public function __construct(?DataNode $node = null, ?string $missionName = null) {
		$this->conditions = new ConditionSet();
		$this->eventTriggers = new ArrayCollection();
		if ($node !== null && $missionName !== null) {
			$this->load($node, $missionName);
		}
	}
	
	public function getId(): int {
		return $this->id;
	}
	public function setId(int $id): self {
		$this->id = $id;
		return $this;
	}
	
	public function getIsEmpty(): bool {
		return $this->isEmpty;
	}
	public function setIsEmpty(bool $isEmpty): self {
		$this->isEmpty = $isEmpty;
		return $this;
	}
	
	public function getLogText(): string {
		return $this->logText;
	}
	public function setLogText(string $logText): self {
		$this->logText = $logText;
		return $this;
	}
	
	public function getSpecialLogTextStr(): string {
		return $this->specialLogTextStr;
	}
	public function setSpecialLogTextStr(string $specialLogTextStr): self {
		$this->specialLogTextStr = $specialLogTextStr;
		return $this;
	}
	
	public function getEventTriggers(): Collection {
		return $this->eventTriggers;
	}
	public function setEventTriggers(Collection $eventTriggers): self {
		$this->eventTriggers = $eventTriggers;
		return $this;
	}
	
	public function getConditions(): ConditionSet {
		return $this->conditions;
	}
	public function setConditions(ConditionSet $conditions): self {
		$this->conditions = $conditions;
		return $this;
	}
	
	public function getEvents(): array {
		return $this->events;
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
	
	public function load(DataNode $node, string $missionName) {
		if ($node->getSourceName()) {
			$this->sourceName = $node->getSourceName();
			$this->sourceFile = $node->getSourceFile();
			$this->sourceVersion = $node->getSourceVersion();
		}
		foreach ($node as $child) {
			$this->loadSingle($child, $missionName);
		}
	}
	
	// Load a single child at a time, used for streamlining MissionAction::Load.
	public function loadSingle(DataNode $child, string $missionName) {
		$isEmpty = false;
	
		$key = $child->getToken(0);
		$hasValue = ($child->size() >= 2);
	
		if ($key == "log") {
			$isSpecial = ($child->size() >= 3);
			if ($isSpecial && !isset($this->specialLogText[$child->getToken(1)])) {
				$this->specialLogText[$child->getToken(1)] = [];
			}
			if ($isSpecial && !isset($this->specialLogText[$child->getToken(1)][$child->getToken(2)])) {
				$this->specialLogText[$child->getToken(1)][$child->getToken(2)] = '';
			}
			$text = ($isSpecial ? $this->specialLogText[$child->getToken(1)][$child->getToken(2)] : $this->logText);
			Dialog::ParseTextNode($child, $isSpecial ? 3 : 1, $text);
		} else if (($key == "give" || $key == "take") && $child->size() >= 3 && $child->getToken(1) == "ship") {
			$ship = new ShipManager();
			$ship->load($child);
			$this->giftShips []= $ship;
		} else if ($key == "outfit" && $hasValue) {
			$count = ($child->size() < 3 ? 1 : intval($child->getValue(2)));
			if ($count) {
				// TODO: I don't think you can use an object as as array index in PHP; probably need to use a unique ID (outfit name?) and then store the outfit and the count
				$outfitName = $child->getToken(1);
				$outfit = GameData::Outfits()[$outfitName];
				$this->giftOutfits[$outfitName] = ['outfit'=>$outfit, 'giftCount'=>$count];
			} else {
				$child->printTrace("Error: Skipping invalid outfit quantity:");
			}
		} else if ($key == "payment") {
			if ($child->size() == 1) {
				$this->paymentMultiplier += 150;
			}
			if ($child->size() >= 2) {
				$this->payment += $child->getValue(1);
			}
			if ($child->size() >= 3) {
				$this->paymentMultiplier += $child->getValue(2);
			}
		} else if ($key == "fine" && $hasValue) {
			$value = $child->getValue(1);
			if ($value > 0) {
				$this->fine += $value;
			} else {
				$child->printTrace("Error: Skipping invalid \"fine\" with non-positive value:");
			}
		} else if ($key == "event" && $hasValue) {
			$minDays = ($child->size() >= 3 ? $child->getValue(2) : 0);
			$maxDays = ($child->size() >= 4 ? $child->getValue(3) : $minDays);
			if ($maxDays < $minDays) {
				$tmp = $minDays;
				$minDays = $maxDays;
				$maxDays = $tmp;
			}
			$eventName = $child->getToken(1);
			$event = GameData::Events()[$eventName];
			$this->events[$eventName] = ['event'=>$event, 'minDays'=>$minDays, 'maxDays'=>$maxDays];
		} else if ($key == "fail") {
			$toFail = $child->size() >= 2 ? $child->getToken(1) : $missionName;
			if ($toFail == '') {
				$child->printTrace("Error: Skipping invalid \"fail\" with no mission:");
			} else {
				if (!in_array($toFail, $this->fail)) {
					$this->fail []= $toFail;
				}
			}
		} else {
			$this->conditions->add($child);
		}
	}
	
	public function save(DataWriter $out): void {
		if ($this->logText != '') {
			$out->write("log");
			$out->beginChild();
			//{
				// Break the text up into paragraphs.
				$paragraphs = explode("\n	", $this->logText);
				foreach ($paragraphs as $line) {
					$out->write($line);
				}
			//}
			$out->endChild();
		}
		foreach ($this->specialLogText as $key => $specialText) {
			foreach ($specialText as $specialKey => $specialVal) {
				$out->write(["log", $key, $specialKey]);
				$out->beginChild();
				//{
					// Break the text up into paragraphs.
					$paragraphs = explode("\n	", $specialVal);
					foreach ($paragraphs as $line) {
						$out->write($line);
					}
				//}
				$out->endChild();
			}
		}
		foreach ($this->giftShips as $ship) {
			$ship->save($out);
		}
		foreach ($this->giftOutfits as $outfitName => $outfitData) {
			$out->write(["outfit", $outfitName, $outfitData['giftCount']]);
		}
		if ($this->payment) {
			$out->write("payment", $this->payment);
		}
		if ($this->fine) {
			$out->write("fine", $this->fine);
		}
		foreach ($this->events as $eventName => $eventData) {
			$out->write(["event", $eventName, $eventData['minDays'], $eventData['maxDays']]);
		}
		foreach ($this->fail as $failName) {
			$out->write(["fail", $failName]);
		}
	
		$this->conditions->save($out);
	}
	
	// Check this template or instantiated GameAction to see if any used content
	// is not fully defined (e.g. plugin removal, typos in names, etc.).
	public function validate(): string {
		// Events which get activated by this action must be valid.
		foreach ($this->events as $eventName => $eventData) {
			$reason = $eventData['event']->isValid();
			if ($reason != '') {
				return "event \"" . $eventName . "\" - Reason: " . $reason;
			}
		}
	
		// Transferred content must be defined & valid.
		foreach ($this->giftShips as $shipManager) {
			if (!$shipManager->getShipModel()->isValid()) {
				return "gift ship model \"" . $shipManager->getShipModel()->getVariantName() . "\"";
			}
		}
		foreach ($this->giftOutfits as $outfitName => $outfitData) {
			if (!($outfitData['outfit']->isDefined())) {
				return "gift outfit \"" . $outfitName . "\"";
			}
		}
	
		// It is OK for this action to try to fail a mission that does not exist.
		// (E.g. a plugin may be designed for interoperability with other plugins.)
	
		return "";
	}
	
	public function isEmpty(): bool {
		return $this->isEmpty;
	}
	
	public function getPayment(): int {
		return $this->payment;
	}
	
	public function getFine(): int {
		return $this->fine;
	}
	
	public function getOutfits(): array {
		return $this->giftOutfits;
	}
	
	public function getShips(): array {
		return $this->giftShips;
	}
	
	public function getConversationNode(): Node {
		return $this->conversationNode;
	}
	
	public function setConversationNode(Node $node) {
		$this->conversationNode = $node;
	}
	// 
	// // Perform the specified tasks.
	// void GameAction::Do(PlayerInfo &player, UI *ui) const
	// {
	// 	if(!logText.empty())
	// 		player.AddLogEntry(logText);
	// 	for(auto &&it : specialLogText)
	// 		for(auto &&eit : it.second)
	// 			player.AddSpecialLog(it.first, eit.first, eit.second);
	// 
	// 	// If multiple outfits, ships are being transferred, first remove the ships,
	// 	// then the outfits, before adding any new ones.
	// 	for(auto &&it : giftShips)
	// 		if(!it.Giving())
	// 			it.Do(player);
	// 	for(auto &&it : giftOutfits)
	// 		if(it.second < 0)
	// 			DoGift(player, it.first, it.second, ui);
	// 	for(auto &&it : giftOutfits)
	// 		if(it.second > 0)
	// 			DoGift(player, it.first, it.second, ui);
	// 	for(auto &&it : giftShips)
	// 		if(it.Giving())
	// 			it.Do(player);
	// 
	// 	if(payment)
	// 	{
	// 		// Conversation actions don't block a mission from offering if a
	// 		// negative payment would drop the player's account balance below
	// 		// zero, so negative payments need to be handled.
	// 		int64_t account = player.Accounts().Credits();
	// 		// If the payment is negative and the player doesn't have enough
	// 		// in their account, then the player's credits are reduced to 0.
	// 		if(account + payment >= 0)
	// 			player.Accounts().AddCredits(payment);
	// 		else if(account > 0)
	// 			player.Accounts().AddCredits(-account);
	// 		// If a MissionAction has a negative payment that can't be met
	// 		// then this action won't offer, so MissionAction payment behavior
	// 		// is unchanged.
	// 	}
	// 	if(fine)
	// 		player.Accounts().AddFine(fine);
	// 
	// 	for(const auto &it : events)
	// 		player.AddEvent(*it.first, player.GetDate() + it.second.first);
	// 
	// 	if(!fail.empty())
	// 	{
	// 		// If this action causes this or any other mission to fail, mark that
	// 		// mission as failed. It will not be removed from the player's mission
	// 		// list until it is safe to do so.
	// 		for(const Mission &mission : player.Missions())
	// 			if(fail.count(mission.Identifier()))
	// 				player.FailMission(mission);
	// 	}
	// 
	// 	// Check if applying the conditions changes the player's reputations.
	// 	conditions.Apply(player.Conditions());
	// }
	
	public function instantiate(array /* map<string, string> */ $subs, int $jumps, int $payload): GameAction {
		$result = new GameAction();
		$result->isEmpty = $this->isEmpty;
	
		foreach ($this->events as $eventName => $eventData) {
			// Allow randomization of event times. The second value in the pair is
			// always greater than or equal to the first, so Random::Int() will
			// never be called with a value less than 1.
			$day = rand($eventData['minDays'], $eventData['maxDays'] + 1);
			$result->events[$eventName] = ['event'=>$eventData['event'], 'minDays'=>$day, 'maxDays'=>$day];
		}
	
		$result->giftShips = $this->giftShips;
		$result->giftOutfits = $this->giftOutfits;
	
		$result->payment = $this->payment + ($jumps + 1) * $payload * $this->paymentMultiplier;
		if ($result->payment) {
			$subs["<payment>"] = Format::CreditString(abs($result->payment));
		}
	
		$result->fine = $this->fine;
		if ($result->fine) {
			$subs["<fine>"] = Format::CreditString($result->fine);
		}
	
		if ($this->logText != '') {
			$result->logText = Format::Replace($this->logText, $subs);
		}
		foreach ($this->specialLogText as $key => $specialText) {
			foreach ($specialText as $specialKey => $specialVal) {
				$result->specialLogText[$key][$specialKey] = Format::Replace($specialVal, $subs);
			}
		}
	
		$result->fail = $this->fail;
	
		$result->conditions = $this->conditions;
	
		return $result;
	}
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->specialLogTextStr = json_encode($this->specialLogText);
		$handledEvents = [];
		foreach ($this->eventTriggers as $EventTrigger) {
			$Event = $EventTrigger->getEvent();
			$handled = false;
			if ($Event && isset($this->events[$Event->getName()])) {
				$eventData = $this->events[$Event->getName()];
				if ($EventTrigger->getMinDays() != $eventData['minDays']) {
					$EventTrigger->setMinDays($eventData['minDays']);
				}
				if ($EventTrigger->getMaxDays() != $eventData['maxDays']) {
					$EventTrigger->setMaxDays($eventData['maxDays']);
				}
				$handledEvents []= $Event->getName();
				$handled = true;
			}
			if (!$handled) {
				$eventArgs->getObjectManager()->remove($EventTrigger);
			}
		}
		foreach ($this->events as $eventName => $eventData) {
			if (in_array($eventName, $handledEvents)) {
				continue;
			}
			$EventTrigger = new EventTrigger();
			$EventTrigger->setGameAction($this);
			$EventTrigger->setEvent($eventData['event']);
			$EventTrigger->setMinDays($eventData['minDays']);
			$EventTrigger->setMaxDays($eventData['maxDays']);
			$this->eventTriggers []= $EventTrigger;
		}
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->specialLogText = json_decode($this->specialLogTextStr, true);
		foreach ($this->eventTriggers as $EventTrigger) {
			$Event = $EventTrigger->getEvent();
			$this->events[$Event->getName()] = ['event'=>$Event, 'minDays'=>$EventTrigger->getMinDays(), 'maxDays'=>$EventTrigger->getMaxDays()];
		}
	}
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		$jsonArray['isEmpty'] = $this->isEmpty;
		$jsonArray['logText'] = $this->logText;
		$jsonArray['specialLogText'] = $this->specialLogText;
		
		$jsonArray['events'] = [];
		foreach ($this->events as $eventName => $eventData) {
			$jsonArray['events'][$eventName] = ['minDays'=>$eventData['minDays'], 'maxDays'=>$eventData['maxDays']];
		}
		
		$jsonArray['source'] = ['name'=>$this->sourceName,'file'=>$this->sourceFile,'version'=>$this->sourceVersion];
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}

}