<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class FleetCargo {
	private int $cargo = 3;
	private array $commodities = []; //vector<string>
	private array $outfitters = []; //set<const Sale<Outfit> *>
	
	// namespace {
	// 	// Construct a list of all outfits for sale in this system and its linked neighbors.
	// 	Sale<Outfit> GetOutfitsForSale(const System *here)
	// 	{
	// 		auto outfits = Sale<Outfit>();
	// 		if(here)
	// 		{
	// 			for(const StellarObject &object : here->Objects())
	// 			{
	// 				const Planet *planet = object.GetPlanet();
	// 				if(planet && planet->IsValid() && planet->HasOutfitter())
	// 					outfits.Add(planet->Outfitter());
	// 			}
	// 		}
	// 		return outfits;
	// 	}
	// 
	// 	// Construct a list of varying numbers of outfits that were either specified for
	// 	// this fleet directly, or are sold in this system or its linked neighbors.
	// 	vector<const Outfit *> OutfitChoices(const set<const Sale<Outfit> *> &outfitters, const System *hub, int maxSize)
	// 	{
	// 		auto outfits = vector<const Outfit *>();
	// 		if(maxSize > 0)
	// 		{
	// 			auto choices = Sale<Outfit>();
	// 			// If no outfits were directly specified, choose from those sold nearby.
	// 			if(outfitters.empty() && hub)
	// 			{
	// 				choices = GetOutfitsForSale(hub);
	// 				for(const System *other : hub->Links())
	// 					choices.Add(GetOutfitsForSale(other));
	// 			}
	// 			else
	// 				for(const auto outfitter : outfitters)
	// 					choices.Add(*outfitter);
	// 
	// 			if(!choices.empty())
	// 			{
	// 				for(const auto outfit : choices)
	// 				{
	// 					double mass = outfit->Mass();
	// 					// Avoid free outfits, massless outfits, and those too large to fit.
	// 					if(mass > 0. && mass < maxSize && outfit->Cost() > 0)
	// 					{
	// 						// Also avoid outfits that add space (such as Outfits / Cargo Expansions)
	// 						// or modify bunks.
	// 						// TODO: Specify rejection criteria in datafiles as ConditionSets or similar.
	// 						const auto &attributes = outfit->Attributes();
	// 						if(attributes.Get("outfit space") > 0.
	// 								|| attributes.Get("cargo space") > 0.
	// 								|| attributes.Get("bunks"))
	// 							continue;
	// 
	// 						outfits.push_back(outfit);
	// 					}
	// 				}
	// 			}
	// 		}
	// 		// Sort this list of choices ascending by mass, so it can be easily trimmed to just
	// 		// the outfits that fit as the ship's free space decreases.
	// 		sort(outfits.begin(), outfits.end(), [](const Outfit *a, const Outfit *b)
	// 			{ return a->Mass() < b->Mass(); });
	// 		return outfits;
	// 	}
	// 
	// 	// Add a random commodity from the list to the ship's cargo.
	// 	void AddRandomCommodity(Ship &ship, int freeSpace, const vector<string> &commodities)
	// 	{
	// 		int index = Random::Int(GameData::Commodities().size());
	// 		if(!commodities.empty())
	// 		{
	// 			// If a list of possible commodities was given, pick one of them at
	// 			// random and then double-check that it's a valid commodity name.
	// 			const string &name = commodities[Random::Int(commodities.size())];
	// 			for(const auto &it : GameData::Commodities())
	// 				if(it.name == name)
	// 				{
	// 					index = &it - &GameData::Commodities().front();
	// 					break;
	// 				}
	// 		}
	// 
	// 		const Trade::Commodity &commodity = GameData::Commodities()[index];
	// 		int amount = Random::Int(freeSpace) + 1;
	// 		ship.Cargo().Add(commodity.name, amount);
	// 	}
	// 
	// 	// Add a random outfit from the list to the ship's cargo.
	// 	void AddRandomOutfit(Ship &ship, int freeSpace, const vector<const Outfit *> &outfits)
	// 	{
	// 		if(outfits.empty())
	// 			return;
	// 		int index = Random::Int(outfits.size());
	// 		const Outfit *picked = outfits[index];
	// 		int maxQuantity = floor(static_cast<double>(freeSpace) / picked->Mass());
	// 		int amount = Random::Int(maxQuantity) + 1;
	// 		ship.Cargo().Add(picked, amount);
	// 	}
	// }
	// 
	// 
	// 
	public function load(DataNode $node): void
	{
		foreach ($node as $child) {
			$this->loadSingle($child);
		}
	}
	
	
	
	public function loadSingle(DataNode $node): void
	{
		if ($node->size() < 2) {
			$node->printTrace("Error: Expected key to have a value:");
		} else if ($node->getToken(0) == "cargo") {
				$this->cargo = $node->getValue(1);
		} else if ($node->getToken(0) == "commodities") {
			$this->commodities = [];
			for ($i = 1; $i < $node->size(); ++$i) {
				$this->commodities []= $node->getToken($i);
			}
		} else if($node->getToken(0) == "outfitters") {
			// $this->outfitters = [];
			// for (i = 1; i < $node->size(); ++$i) {
			// 	$this->outfitters []= GameData::Outfitters()[$node->getToken($i)];
			// }
		} else {
			$node->printTrace("Skipping unrecognized attribute:");
		}
	}
	// 
	// 
	// 
	// // Choose the cargo associated with this ship.
	// // If outfits were specified, but not commodities, do not pick commodities.
	// // If commodities were specified, but not outfits, do not pick outfits.
	// // If neither or both were specified, choose commodities more often.
	// // Also adds a random amount of extra crew in addition to the required crew,
	// // up to the number of bunks remaining after required crew.
	// void FleetCargo::SetCargo(Ship *ship) const
	// {
	// 	const bool canChooseOutfits = commodities.empty() || !outfitters.empty();
	// 	const bool canChooseCommodities = outfitters.empty() || !commodities.empty();
	// 	// Populate the possible outfits that may be chosen.
	// 	int free = ship->Cargo().Free();
	// 	auto outfits = OutfitChoices(outfitters, ship->GetSystem(), free);
	// 
	// 	// Choose random outfits or commodities to transport.
	// 	for(int i = 0; i < cargo; ++i)
	// 	{
	// 		if(free <= 0)
	// 			break;
	// 		// Remove any outfits that do not fit into remaining cargo.
	// 		if(canChooseOutfits && !outfits.empty())
	// 			outfits.erase(remove_if(outfits.begin(), outfits.end(),
	// 					[&free](const Outfit *a) { return a->Mass() > free; }),
	// 				outfits.end());
	// 
	// 		if(canChooseCommodities && canChooseOutfits)
	// 		{
	// 			if(Random::Real() < .8)
	// 				AddRandomCommodity(*ship, free, commodities);
	// 			else
	// 				AddRandomOutfit(*ship, free, outfits);
	// 		}
	// 		else if(canChooseCommodities)
	// 			AddRandomCommodity(*ship, free, commodities);
	// 		else
	// 			AddRandomOutfit(*ship, free, outfits);
	// 
	// 		free = ship->Cargo().Free();
	// 	}
	// 	int extraCrew = ship->Attributes().Get("bunks") - ship->RequiredCrew();
	// 	if(extraCrew > 0)
	// 		ship->AddCrew(Random::Int(extraCrew + 1));
	// }

}