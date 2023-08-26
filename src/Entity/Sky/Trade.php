<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

// Class representing all the commodities that are available to trade. Each
// commodity has a certain normal price range, and can also specify specific
// items that are a kind of that commodity, so that a mission can have you
// deliver, say, "eggs" or "frozen meat" instead of generic "food".
class Trade {
	private array $commodities = []; //vector<Commodity> 
	private array $specialCommodities = []; //vector<Commodity> 
	
	public function load(DataNode $node) {
		foreach ($node as $child) {
			if ($child->getToken(0) == "commodity" && $child->size() >= 2) {
				$isSpecial = ($child->size() < 4);
				$list = ($isSpecial ? $this->specialCommodities : $this->commodities);
				$commodityName = $child->getToken(1);
				if (in_array($commodityName, $list)) {
					$commodity = $list[$commodityName];
				} else {
					$commodity = new Commodity();
					$list[$commodityName] = $commodity;
				}
				
				$commodity->name = $commodityName;
				if (!$isSpecial) {
					$commodity->low = $child->getValue(2);
					$commodity->high = $child->getValue(3);
				}
				foreach ($child as $grand) {
					$commodity->items []= $grand->getToken(0);
				}
			} else if ($child->getToken(0) == "clear") {
				$this->commodities = [];
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	}
	
	public function getCommodities(): array {
		return $this->commodities;
	}
	
	public function getSpecialCommodities(): array {
		return $this->specialCommodities;
	}
}

class Commodity {
	public string $name = '';
	public int $low = 0;
	public int $high = 0;
	public array $items = []; //vector<string>
};