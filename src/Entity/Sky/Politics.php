<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class Politics {
	private array $reputationWith = []; //std::map<const Government *, double>
	private array $provoked = []; //std::set<const Government *> 
	private array $bribed = []; //std::set<const Government *> 
	private array $bribedPlanets = []; //std::map<const Planet *, bool> 
	private array $dominatedPlanets = []; //std::set<const Planet *> 
	private array $fined = []; //std::set<const Government *> 
	
	// Check if the ship evades being cargo scanned.
	public static function EvadesCargoScan(Ship $ship): bool {
		// Illegal goods can be hidden inside legal goods to avoid detection.
		$contraband = $ship->getCargo()->getIllegalCargoAmount();
		$netIllegalCargo = $contraband - $ship.getAttributes()["scan concealment"];
		if ($netIllegalCargo <= 0) {
			return true;
		}

		$legalGoods = $ship.getCargo().getUsed() - $contraband;
		$illegalRatio = $legalGoods ? max(1., 2. * $netIllegalCargo / $legalGoods) : 1.;
		$scanChance = $illegalRatio / (1. + $ship->getAttributes()["scan interference"]);
		return Random::Real() > $scanChance;
	}

	// Check if the ship evades being outfit scanned.
	public static function EvadesOutfitScan(Ship $ship) {
		return $ship->getAttributes()["inscrutable"] > 0. ||
				Random::Real() > 1. / (1. + $ship->getAttributes()["scan interference"]);
	}
	
	// Reset to the initial political state defined in the game data.
	public function reset(): void {
		$this->reputationWith = [];
		$this->dominatedPlanets = [];
		$this->resetDaily();
	
		foreach (GameData::Governments() as $governmentName => $government) {
			$this->reputationWith[$governmentName] = $government->getInitialPlayerReputation();
			// Disable fines for today (because the game was just loaded, so any fines
			// were already checked for when you first landed).
			$this->fined []= $government;
		}
	}
	
	public function isEnemy(Government $first, Government $second): bool {
		if ($first == $second) {
			return false;
		}
	
		// Just for simplicity, if one of the governments is the player, make sure
		// it is the first one.
		if ($second->isPlayer()) {
			$temp = $first;
			$first = $second;
			$second = $temp;
		}
		if ($first->isPlayer()) {
			if (isset($this->bribed[$second->getName()])) {
				return false;
			}
			if (isset($this->provoked[$second->getName()])) {
				return true;
			}
	
			$rep = 0;
			if (isset($this->reputationWith[$second->getName()])) {
				$rep = $this->reputationWith[$second->getName()];
			}
			return $rep < 0.;
		}
	
		// Neither government is the player, so the question of enemies depends only
		// on the attitude matrix.
		return ($first->getAttitudeToward($second) < 0. || $second->getAttitudeToward($first) < 0.);
	}
	
	// Commit the given "offense" against the given government (which may not
	// actually consider it to be an offense). This may result in temporary
	// hostilities (if the even type is PROVOKE), or a permanent change to your
	// reputation.
	public function offend(Government $gov, int $eventType, int $count): void {
		if($gov->isPlayer()) {
			return;
		}
	
		foreach (GameData::Governments() as $other) {
			$weight = $other->getAttitudeToward($gov);
	
			// You can provoke a government even by attacking an empty ship, such as
			// a drone (count = 0, because count = crew).
			if ($eventType & ShipEvent::PROVOKE) {
				if ($weight > 0.) {
					// If you bribe a government but then attack it, the effect of
					// your bribe is canceled out.
					unset($this->bribed[$other->getName()]);
					$this->provoked []= $other->getName();
				}
			}
			if ($count && abs($weight) >= .05) {
				// Weights less than 5% should never cause permanent reputation
				// changes. This is to allow two governments to be hostile or
				// friendly without the player's behavior toward one of them
				// influencing their reputation with the other.
				$penalty = ($count * $weight) * $other->getPenaltyFor($eventType, $gov);
				if ($eventType & ShipEvent::ATROCITY && $weight > 0) {
					Politics::SetReputation($other, min(0., $this->reputationWith[$other->getName()]));
				}
	
				Politics::AddReputation($other, -$penalty);
			}
		}
	}
	// 
	// // Bribe the given government to be friendly to you for one day.
	// void Politics::Bribe(const Government *gov)
	// {
	// 	bribed.insert(gov);
	// 	provoked.erase(gov);
	// 	fined.insert(gov);
	// }
	
	// Check if the given ship can land on the given planet.
	public function canLand(Planet $planet, ?Ship $ship = null): bool {
		if ($ship) {
			if (!$planet || !$planet->getSystem()) {
				return false;
			}
			if (!$planet->isInhabited()) {
				return true;
			}
		
			$gov = $ship->getGovernment();
			if(!$gov->isPlayer()) {
				return !$this->isEnemy($gov, $planet->getGovernment());
			}
		}
	
		if (!$planet || !$planet->getSystem()) {
			return false;
		}
		if (!$planet->isInhabited()) {
			return true;
		}
		if (in_array($planet, $this->dominatedPlanets)) {
			return true;
		}
		if (in_array($planet, $this->bribedPlanets)) {
			return true;
		}
		if (in_array($planet->getGovernment(), $planet->getGovernment())) {
			return false;
		}
		
		return $this->reputation($planet->GetGovernment()) >= $planet->getRequiredReputation();
	}
	
	
	public function canUseServices(Planet $planet): bool {
		if (!$planet || !$planet->getSystem()) {
			return false;
		}
		if (in_array($planet, $this->dominatedPlanets)) {
			return true;
		}
		
		if (in_array($planet, $this->bribedPlanets)) {
			return true;
		}
	
		return $this->getReputation($planet->getGovernment()) >= $planet->getRequiredReputation();
	}
	// 
	// // Bribe a planet to let the player's ships land there.
	// void Politics::BribePlanet(const Planet *planet, bool fullAccess)
	// {
	// 	bribedPlanets[planet] = fullAccess;
	// }
	// 
	// void Politics::DominatePlanet(const Planet *planet, bool dominate)
	// {
	// 	if(dominate)
	// 		dominatedPlanets.insert(planet);
	// 	else
	// 		dominatedPlanets.erase(planet);
	// }
	
	public function hasDominated(Planet $planet): bool {
		return in_array($planet, $this->dominatedPlanets);
	}
	// 
	// // Check to see if the player has done anything they should be fined for.
	// string Politics::Fine(PlayerInfo &player, const Government *gov, int scan, const Ship *target, double security)
	// {
	// 	// Do nothing if you have already been fined today, or if you evade
	// 	// detection.
	// 	if(fined.count(gov) || Random::Real() > security || !gov->GetFineFraction())
	// 		return "";
	// 
	// 	string reason;
	// 	int64_t maxFine = 0;
	// 	for(const shared_ptr<Ship> &ship : player.Ships())
	// 	{
	// 		if(target && target != &*ship)
	// 			continue;
	// 		if(ship->GetSystem() != player.GetSystem())
	// 			continue;
	// 
	// 		int failedMissions = 0;
	// 
	// 		if((!scan || (scan & ShipEvent::SCAN_CARGO)) && !EvadesCargoScan(*ship))
	// 		{
	// 			int64_t fine = ship->Cargo().IllegalCargoFine(gov);
	// 			if((fine > maxFine && maxFine >= 0) || fine < 0)
	// 			{
	// 				maxFine = fine;
	// 				reason = " for carrying illegal cargo.";
	// 
	// 				for(const Mission &mission : player.Missions())
	// 				{
	// 					if(mission.IsFailed())
	// 						continue;
	// 
	// 					// Append the illegalCargoMessage from each applicable mission, if available
	// 					string illegalCargoMessage = mission.IllegalCargoMessage();
	// 					if(!illegalCargoMessage.empty())
	// 					{
	// 						reason = ".\n\t";
	// 						reason.append(illegalCargoMessage);
	// 					}
	// 					// Fail any missions with illegal cargo and "Stealth" set
	// 					if(mission.IllegalCargoFine() > 0 && mission.FailIfDiscovered())
	// 					{
	// 						player.FailMission(mission);
	// 						++failedMissions;
	// 					}
	// 				}
	// 			}
	// 		}
	// 		if((!scan || (scan & ShipEvent::SCAN_OUTFITS)) && !EvadesOutfitScan(*ship))
	// 			for(const auto &it : ship->Outfits())
	// 				if(it.second)
	// 				{
	// 					int fine = gov->Fines(it.first);
	// 					if(gov->Condemns(it.first))
	// 						fine = -1;
	// 					if((fine > maxFine && maxFine >= 0) || fine < 0)
	// 					{
	// 						maxFine = fine;
	// 						reason = " for having illegal outfits installed on your ship.";
	// 					}
	// 				}
	// 		if(failedMissions && maxFine > 0)
	// 		{
	// 			reason += "\n\tYou failed " + Format::Number(failedMissions) + ((failedMissions > 1) ? " missions" : " mission")
	// 				+ " after your illegal cargo was discovered.";
	// 		}
	// 	}
	// 
	// 	if(maxFine < 0)
	// 	{
	// 		gov->Offend(ShipEvent::ATROCITY);
	// 		if(!scan)
	// 			reason = "atrocity";
	// 		else
	// 			reason = "After scanning your ship, the " + gov->GetName()
	// 				+ " captain hails you with a grim expression on his face. He says, "
	// 				"\"I'm afraid we're going to have to put you to death " + reason + " Goodbye.\"";
	// 	}
	// 	else if(maxFine > 0)
	// 	{
	// 		// Scale the fine based on how lenient this government is.
	// 		maxFine = lround(maxFine * gov->GetFineFraction());
	// 		reason = "The " + gov->GetName() + " authorities fine you "
	// 			+ Format::CreditString(maxFine) + reason;
	// 		player.Accounts().AddFine(maxFine);
	// 		fined.insert(gov);
	// 	}
	// 	return reason;
	// }
	
	// Get or set your reputation with the given government.
	public function getReputation(Government $gov): float {
		if (isset($this->reputationWith[$gov->getName()])) {
			return $this->reputationWith[$gov->getName()];
		}
		return 0.;
	}
	
	public function addReputation(Government $gov, float $value): void {
		$this->setReputation($gov, $this->reputationWith[$gov] + $value);
	}
	
	public function setReputation(Government $gov, float $value): void {
		$value = min($value, $gov->getReputationMax());
		$value = max($value, $gov->getReputationMin());
		$this->reputationWith[$gov->getName()] = $value;
	}
	
	// Reset any temporary provocation (typically because a day has passed).
	public function resetDaily(): void {
		$this->provoked = [];
		$this->bribed = [];
		$this->bribedPlanets = [];
		$this->fined = [];
	}

}