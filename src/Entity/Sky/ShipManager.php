<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;
use App\Entity\DataWriter;

class ShipManager {
	private ?Ship $model = null;
	private string $name = '';
	private string $id = '';
	private int $count = 1;
	private bool $taking = false;
	private bool $unconstrained = false;
	private bool $requireOutfits = false;
	private bool $takeOutfits = false;
	
	public function load(DataNode $node): void {
		if ($node->size() < 3 || $node->getToken(1) != "ship") {

			$node->printTrace("Error: Skipping unrecognized node.");
			return;
		}
		$this->taking = $node->getToken(0) == "take";
		$this->model = GameData::Ships()[$node->getToken(2)];
		if ($node->size() >= 4) {
			$this->name = $node->getToken(3);
		}
	
		foreach ($node as $child) {
			$key = $child->getToken(0);
			$hasValue = $child->size() > 1;
			if ($key == "id" && $hasValue) {
				$this->id = $child->getToken(1);
			} else if ($key == "count" && $hasValue) {
				$val = $child->getValue(1);
				if ($val <= 0) {
					$child->printTrace("Error: \"count\" must be a non-zero, positive number.");
				} else {
					$this->count = $val;
				}
			} else if ($this->taking) {
				if ($key == "unconstrained") {
					$this->unconstrained = true;
				} else if ($key == "with outfits") {
					$this->takeOutfits = true;
				} else if ($key == "require outfits") {
					$this->requireOutfits = true;
				} else {
					$child->printTrace("Error: Skipping unrecognized token.");
				}
			} else {
				$child->printTrace("Error: Skipping unrecognized token.");
			}
		}
	
		if ($this->taking && $this->id != '' && $this->count > 1) {
			$node->printTrace("Error: Use of \"id\" to refer to the ship is only supported when \"count\" is equal to 1.");
		}
	}

	public function save(DataWriter $out): void {
		$out->write([$this->isGiving() ? "give" : "take", "ship", $this->model->getVariantName(), $this->name]);
		$out->beginChild();
		{
			$out->write(["count", $this->count]);
			if ($this->id != '') {
				$out->write("id", $this->id);
			}
			if ($this->unconstrained) {
				$out->write("unconstrained");
			}
			if ($this->takeOutfits) {
				$out->write("with outfits");
			}
			if ($this->requireOutfits) {
				$out->write("require outfits");
			}
		}
		$out->endChild();
	}
// 	
// 	
// 	
// 	bool ShipManager::CanBeDone(const PlayerInfo &player) const
// 	{
// 		// If we are giving ships there are no conditions to meet.
// 		return Giving() || static_cast<int>(SatisfyingShips(player).size()) == count;
// 	}
// 	
// 	
// 	
// 	void ShipManager::Do(PlayerInfo &player) const
// 	{
// 		if (model->TrueModelName().empty()) {
// 			return;
// 	
// 		string shipName;
// 		if (Giving()) {
// 
// 			for (int i = 0; i < count; ++i) {
// 				shipName = player.GiftShip(model, name, id)->Name();
// 		} else
// 		{
// 			auto toTake = SatisfyingShips(player);
// 			if (toTake.size() == 1) {
// 				shipName = toTake.begin()->get()->Name();
// 			for (const auto &ship : toTake) {
// 				player.TakeShip(ship.get(), model, takeOutfits);
// 		}
// 		Messages::Add((count == 1 ? "The " + model->DisplayModelName() + " \"" + shipName + "\" was " :
// 			to_string(count) + " " + model->PluralModelName() + " were ") +
// 			(Giving() ? "added to" : "removed from") + " your fleet.", Messages::Importance::High);
// 	}
// 	
// 	
// 	
// 	const Ship *ShipManager::ShipModel() const
// 	{
// 		return model;
// 	}
// 	
// 	
// 	
// 	const string &ShipManager::Id() const
// 	{
// 		return id;
// 	}
	
	public function isGiving(): bool {
		return !$this->taking;
	}
// 	
// 	
// 	
// 	vector<shared_ptr<Ship>> ShipManager::SatisfyingShips(const PlayerInfo &player) const
// 	{
// 		const System *here = player.GetSystem();
// 		const auto shipToTakeId = player.GiftedShips().find(id);
// 		bool foundShip = shipToTakeId != player.GiftedShips().end();
// 		vector<shared_ptr<Ship>> satisfyingShips;
// 	
// 		for (const auto &ship : player.Ships()) {
// 
// 			if (ship->TrueModelName() != model->TrueModelName()) {
// 				continue;
// 			if (!unconstrained) {
// 
// 				if (ship->GetSystem() != here) {
// 					continue;
// 				if (ship->IsDisabled()) {
// 					continue;
// 				if (ship->IsParked()) {
// 					continue;
// 			}
// 			if (!id.empty()) {
// 
// 				if (!foundShip) {
// 					continue;
// 				if (ship->UUID() != shipToTakeId->second) {
// 					continue;
// 			}
// 			if (!name.empty() && name != ship->Name()) {
// 				continue;
// 			bool hasRequiredOutfits = true;
// 			// If "with outfits" or "requires outfits" is specified,
// 			// this ship must have each outfit specified in that variant definition.
// 			if (requireOutfits) {
// 				for (const auto &it : model->Outfits()) {
// 
// 					const auto &outfit = ship->Outfits().find(it.first);
// 					int amountEquipped = (outfit != ship->Outfits().end() ? outfit->second : 0);
// 					if (it.second > amountEquipped) {
// 
// 						hasRequiredOutfits = false;
// 						break;
// 					}
// 				}
// 			if (hasRequiredOutfits) {
// 				satisfyingShips.emplace_back(ship);
// 			// We do not want any more ships than is specified.
// 			if (static_cast<int>(satisfyingShips.size()) >= count) {
// 				break;
// 		}
// 	
// 		return satisfyingShips;
// 	}

}