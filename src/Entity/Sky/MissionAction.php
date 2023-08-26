<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;
use App\Entity\DataWriter;

#[ORM\Entity]
#[ORM\Table(name: 'MissionAction')]
class MissionAction {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string', name: 'triggerName')]
	private string $trigger = '';

	#[ORM\Column(type: 'string', name: 'system')]
	private string $system = '';

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\LocationFilter', inversedBy: 'systemFilterMissionAction', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'systemFilterId')]
	private LocationFilter $systemFilter;

	#[ORM\Column(type: 'text', name: 'dialogText')]
	private string $dialogText = '';

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Phrase', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'dialogPhraseId')]
	private Phrase $dialogPhrase; //ExclusiveItem<Phrase>
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Conversation', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'conversationId')]
	private Conversation $conversation; //ExclusiveItem<Conversation>
	
	// Outfits that are required to be owned (or not) for this action to be performable.
	private array $requiredOutfits = []; //map<const Outfit *, int>
	
	// Tasks this mission action performs, such as modifying accounts, inventory, or conditions.
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\GameAction', inversedBy: 'missionAction', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'actionId')]
	private GameAction $action;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Mission', inversedBy: 'didEnter', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'didEnterMissionId')]
	private ?Mission $didEnterMission = null;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Mission', inversedBy: 'genericOnEnter', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'genericOnEnterMissionId')]
	private ?Mission $genericOnEnterMission = null;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Mission', inversedBy: 'onEnter', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'onEnterMissionId')]
	private ?Mission $onEnterMission = null;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Mission', inversedBy: 'actions', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'missionId')]
	private ?Mission $mission = null;
	
	// public static function CountInCargo(Outfit $outfit, PlayerInfo $player) {
	// 	int available = 0;
	// 	// If landed, all cargo from available ships is pooled together.
	// 	if (player.GetPlanet()) {
	// 		available += player.Cargo().Get(outfit);
	// 	// Otherwise only count outfits in the cargo holds of in-system ships.
	// 	} else
	// 	{
	// 		const System *here = player.GetSystem();
	// 		for (const auto &ship : player.Ships()) {
	// 		{
	// 			if (ship->IsDisabled() || ship->IsParked()) {
	// 				continue;
	// 			if (ship->GetSystem() == here || (ship->CanBeCarried() {
	// 					&& !ship->GetSystem() && ship->GetParent()->GetSystem() == here))
	// 				available += ship->Cargo().Get(outfit);
	// 		}
	// 	}
	// 	return available;
	// }
	
	// Construct and Load() at the same time.
	public function __construct(?DataNode $node = null, string $missionName = '') {
		$this->action = new GameAction();
		$this->systemFilter = new LocationFilter();
		$this->conversation = new Conversation();
		$this->dialogPhrase = new Phrase();
		if ($node && $missionName) {
			$this->load($node, $missionName);
		}
	}
	
	public function getAction(): GameAction {
		return $this->action;
	}
	
	public function load(DataNode $node, string $missionName) {
		if ($node->size() >= 2) {
			$this->trigger = $node->getToken(1);
		}
		if ($node->size() >= 3) {
			$this->system = $node->getToken(2);
		}
	
		foreach ($node as $child) {
			$this->loadSingle($child, $missionName);
		}
	}
	
	public function loadSingle(DataNode $child, string $missionName) {
		$key = $child->getToken(0);
		$hasValue = ($child->size() >= 2);
	
		if ($key == "dialog") {
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
		} else if ($key == "conversation" && $child->hasChildren()) {
			$this->conversation = new Conversation($child, $missionName);
		} else if ($key == "conversation" && $hasValue) {
			$this->conversation = GameData::Conversations()[$child->getToken(1)];
		} else if ($key == "require" && $hasValue) {
			$count = ($child->size() < 3 ? 1 : intval($child->getValue(2)));
			if ($count >= 0) {
				$this->requiredOutfits[$child->getToken(1)] = $count;
			} else {
				$child->printTrace("Error: Skipping invalid \"require\" count:");
			}
		// The legacy syntax "outfit <outfit> 0" means "the player must have this outfit installed."
		} else if ($key == "outfit" && $child->size() >= 3 && $child->getToken(2) == "0") {
			$child->printTrace("Warning: Deprecated use of \"outfit\" with count of 0. Use \"require <outfit>\" instead:");
			$this->requiredOutfits[$child->getToken(1)] = 1;
		} else if ($key == "system") {
			if ($this->system == '' && $child->hasChildren()) {
				$this->systemFilter->load($child);
			} else {
				$child->printTrace("Error: Unsupported use of \"system\" LocationFilter:");
			}
		} else {
			$this->action->loadSingle($child, $missionName);
		}
	}
	
	// Note: the Save() function can assume this is an instantiated mission, not
	// a template, so it only has to save a subset of the data.
	public function save(DataWriter $out): void {
		if ($this->system == '') {
			$out->write(["on", $this->trigger]);
		} else {
			$out->write(["on", $this->trigger, $this->system]);
		}
		$out->beginChild();
		//{
			$this->saveBody($out);
		//}
		$out->endChild();
	}
	
	
	
	public function saveBody(DataWriter $out): void {
		if (!$this->systemFilter->isEmpty()) {
			$out->write("system");
			// LocationFilter indentation is handled by its Save method.
			$this->systemFilter->save($out);
		}
		if ($this->dialogText != '') {
			$out->write("dialog");
			$out->beginChild();
			//{
				// Break the text up into paragraphs.
				$lines = explode("\n	", $this->dialogText);
				foreach ($lines as $line) {
					$out->write($line);
				}
			//}
			$out->endChild();
		}
		if (!$this->conversation->isEmpty()) {
			$this->conversation->save($out);
		}
		foreach ($this->requiredOutfits as $outfitName => $outfitCount) {
			$out->write("require", $outfitName, $outfitCount);
		}
	
		$this->action->save($out);
	}
// 	
// 	// Check this template or instantiated MissionAction to see if any used content
// 	// is not fully defined (e.g. plugin removal, typos in names, etc.).
// 	string MissionAction::Validate() const
// 	{
// 		// Any filter used to control where this action triggers must be valid.
// 		if (!systemFilter.IsValid()) {
// 			return "system location filter";
// 	
// 		// Stock phrases that generate text must be defined.
// 		if (dialogPhrase.IsStock() && dialogPhrase->IsEmpty()) {
// 			return "stock phrase";
// 	
// 		// Stock conversations must be defined.
// 		if (conversation.IsStock() && conversation->IsEmpty()) {
// 			return "stock conversation";
// 	
// 		// Conversations must have valid actions.
// 		string reason = conversation->Validate();
// 		if (!reason.empty()) {
// 			return reason;
// 	
// 		// Required content must be defined & valid.
// 		for (auto &&outfit : requiredOutfits) {
// 			if (!outfit.first->IsDefined()) {
// 				return "required outfit \"" + outfit.first->TrueName() + "\"";
// 	
// 		return action.Validate();
// 	}
// 	
// 	
// 	
// 	const string &MissionAction::DialogText() const
// 	{
// 		return dialogText;
// 	}
// 	
// 	
// 	
// 	// Check if this action can be completed right now. It cannot be completed
// 	// if it takes away money or outfits that the player does not have.
// 	bool MissionAction::CanBeDone(const PlayerInfo &player, const shared_ptr<Ship> &boardingShip) const
// 	{
// 		if (player.Accounts().Credits() < -Payment()) {
// 			return false;
// 	
// 		const Ship *flagship = player.Flagship();
// 		for (auto &&it : action.Outfits()) {
// 
// 			// If this outfit is being given, the player doesn't need to have it.
// 			if (it.second > 0) {
// 				continue;
// 	
// 			// Outfits may always be taken from the flagship. If landed, they may also be taken from
// 			// the collective cargohold of any in-system, non-disabled escorts (player.Cargo()). If
// 			// boarding, consider only the flagship's cargo hold. If in-flight, show mission status
// 			// by checking the cargo holds of ships that would contribute to player.Cargo if landed.
// 			int available = flagship ? flagship->OutfitCount(it.first) : 0;
// 			available += boardingShip ? flagship->Cargo().Get(it.first)
// 					: CountInCargo(it.first, player);
// 	
// 			if (available < -it.second) {
// 				return false;
// 		}
// 	
// 		for (auto &&it : action.Ships()) {
// 			if (!it.CanBeDone(player)) {
// 				return false;
// 	
// 		for (auto &&it : requiredOutfits) {
// 
// 			// Maps are not normal outfits; they represent the player's spatial awareness.
// 			int mapSize = it.first->Get("map");
// 			if (mapSize > 0) {
// 
// 				bool needsUnmapped = it.second == 0;
// 				// This action can't be done if it requires an unmapped region, but the region is
// 				// mapped, or if it requires a mapped region but the region is not mapped.
// 				if (needsUnmapped == player.HasMapped(mapSize)) {
// 					return false;
// 				continue;
// 			}
// 	
// 			// Requiring the player to have 0 of this outfit means all ships and all cargo holds
// 			// must be checked, even if the ship is disabled, parked, or out-of-system.
// 			if (!it.second) {
// 
// 				// When landed, ships pool their cargo into the player's cargo.
// 				if (player.GetPlanet() && player.Cargo().Get(it.first)) {
// 					return false;
// 	
// 				for (const auto &ship : player.Ships()) {
// 					if (!ship->IsDestroyed()) {
// 						if (ship->OutfitCount(it.first) || ship->Cargo().Get(it.first)) {
// 							return false;
// 			} else
// 			{
// 				// Required outfits must be present on the player's flagship or
// 				// in the cargo holds of able ships at the player's location.
// 				int available = flagship ? flagship->OutfitCount(it.first) : 0;
// 				available += boardingShip ? flagship->Cargo().Get(it.first)
// 						: CountInCargo(it.first, player);
// 	
// 				if (available < it.second) {
// 					return false;
// 			}
// 		}
// 	
// 		// An `on enter` MissionAction may have defined a LocationFilter that
// 		// specifies the systems in which it can occur.
// 		if (!systemFilter.IsEmpty() && !systemFilter.Matches(player.GetSystem())) {
// 			return false;
// 		return true;
// 	}
// 	
// 	
// 	
// 	bool MissionAction::RequiresGiftedShip(const string &shipId) const
// 	{
// 		for (auto &&it : action.Ships()) {
// 			if (it.Id() == shipId) {
// 				return true;
// 		return false;
// 	}
// 	
// 	
// 	
// 	void MissionAction::Do(PlayerInfo &player, UI *ui, const System *destination,
// 		const shared_ptr<Ship> &ship, const bool isUnique) const
// 	{
// 		bool isOffer = (trigger == "offer");
// 		if (!conversation->IsEmpty() && ui) {
// 
// 			// Conversations offered while boarding or assisting reference a ship,
// 			// which may be destroyed depending on the player's choices.
// 			ConversationPanel *panel = new ConversationPanel(player, *conversation, destination, ship, isOffer);
// 			if (isOffer) {
// 				panel->SetCallback(&player, &PlayerInfo::MissionCallback);
// 			// Use a basic callback to handle forced departure outside of `on offer`
// 			// conversations.
// 			} else
// 				panel->SetCallback(&player, &PlayerInfo::BasicCallback);
// 			ui->Push(panel);
// 		} else if (!dialogText.empty() && ui) {
// 
// 			map<string, string> subs;
// 			GameData::GetTextReplacements().Substitutions(subs, player.Conditions());
// 			subs["<first>"] = player.FirstName();
// 			subs["<last>"] = player.LastName();
// 			if (player.Flagship()) {
// 				subs["<ship>"] = player.Flagship()->Name();
// 			string text = Format::Replace(dialogText, subs);
// 	
// 			// Don't push the dialog text if this is a visit action on a nonunique
// 			// mission; on visit, nonunique dialogs are handled by PlayerInfo as to
// 			// avoid the player being spammed by dialogs if they have multiple
// 			// missions active with the same destination (e.g. in the case of
// 			// stacking bounty jobs).
// 			if (isOffer) {
// 				ui->Push(new Dialog(text, player, destination));
// 			} else if (isUnique || trigger != "visit") {
// 				ui->Push(new Dialog(text));
// 		} else if (isOffer && ui) {
// 			player.MissionCallback(Conversation::ACCEPT);
// 	
// 		action.Do(player, ui);
// 	}
// 	
// 	
// 	
// 	// Convert this validated template into a populated action.
// 	MissionAction MissionAction::Instantiate(map<string, string> &subs, const System *origin,
// 		int jumps, int64_t payload) const
// 	{
// 		MissionAction result;
// 		result.trigger = trigger;
// 		result.system = system;
// 		// Convert any "distance" specifiers into "near <system>" specifiers.
// 		result.systemFilter = systemFilter.SetOrigin(origin);
// 	
// 		result.requiredOutfits = requiredOutfits;
// 	
// 		string previousPayment = subs["<payment>"];
// 		string previousFine = subs["<fine>"];
// 		result.action = action.Instantiate(subs, jumps, payload);
// 	
// 		// Create any associated dialog text from phrases, or use the directly specified text.
// 		string dialogText = !dialogPhrase->IsEmpty() ? dialogPhrase->Get() : this->dialogText;
// 		if (!dialogText.empty()) {
// 			result.dialogText = Format::Replace(Phrase::ExpandPhrases(dialogText), subs);
// 	
// 		if (!conversation->IsEmpty()) {
// 			result.conversation = ExclusiveItem<Conversation>(conversation->Instantiate(subs, jumps, payload));
// 	
// 		// Restore the "<payment>" and "<fine>" values from the "on complete" condition, for
// 		// use in other parts of this mission.
// 		if (result.Payment() && (trigger != "complete" || !previousPayment.empty())) {
// 			subs["<payment>"] = previousPayment;
// 		if (result.action.Fine() && trigger != "complete") {
// 			subs["<fine>"] = previousFine;
// 	
// 		return result;
// 	}
	
	
	
	public function getPayment(): int {
		return action.Payment();
	}

}