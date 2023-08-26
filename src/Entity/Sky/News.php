<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class News {
	private LocationFilter $location;
	private ConditionSet $toShow;
	
	private Phrase $names;
	private array $portraits = []; //vector<const Sprite *>
	private Phrase $messages;
	
	public function __construct() {
		$this->location = new LocationFilter();
		$this->toShow = new ConditionSet();
		$this->names = new Phrase();
		$this->messages = new Phrase();
	}
	
	public function load(DataNode $node) {
		foreach ($node as $child) {

			$add = ($child->getToken(0) == "add");
			$remove = ($child->getToken(0) == "remove");
			if (($add || $remove) && $child->size() < 2) {
				$child->printTrace("Skipping " . $child->getToken(0) . " with no key given:");
				continue;
			}
			// Get the key and value (if any).
			$tag = $child->getToken(($add || $remove) ? 1 : 0);
			$valueIndex = ($add || $remove) ? 2 : 1;
			$hasValue = $child->size() > $valueIndex;
			if ($tag == "location") {
				if ($remove) {
					$this->location = new LocationFilter();
				} else {
					$this->location->load($child);
				}
			} else if ($tag == "name") {
				if ($remove) {
					$this->names = new Phrase();
				} else {
					$this->names->load($child);
				}
			} else if ($tag == "portrait") {
				if ($remove && !$hasValue) {
					$this->portraits = [];
				} else if ($remove) {
					// Collect all values to be $removed.
					$toRemove = [];
					for ($i = $valueIndex; $i < $child->size(); ++$i) {
						$toRemove []= SpriteSet::Get($child->getToken($i));
					}
	
					// Erase them in unison.
					foreach ($this->portraits as $index => $sprite) {
						if (in_array($sprite, $toRemove)) {
							unset($this->portraits[$index]);
						}
					}
				} else {
					for ($i = $valueIndex; $i < $child->size(); ++$i) {
						$this->portraits []= SpriteSet::Get($child->getToken($i));
					}
					foreach ($child as $grand) {
						$this->portraits []= SpriteSet::Get($grand->getToken(0));
					}
				}
			} else if ($tag == "message") {
				if ($remove) {
					$this->messages = new Phrase();
				} else {
					$this->messages->load($child);
				}
			} else if ($tag == "to" && $hasValue && $child->getToken($valueIndex) == "show") {
				if ($remove) {
					$this->toShow = new ConditionSet();
				} else {
					$this->toShow->load($child);
				}
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	}
	
	
	// 
	// bool News::IsEmpty() const
	// {
	// 	return messages.IsEmpty() || names.IsEmpty();
	// }
	// 
	// 
	// 
	// // Check if this news item is available given the player's planet and conditions.
	// bool News::Matches(const Planet *planet, const ConditionsStore &conditions) const
	// {
	// 	// If no location filter is specified, it should never match. This can be
	// 	// used to create news items that are never shown until an event "activates"
	// 	// them by specifying their location.
	// 	// Similarly, by updating a news item with "remove location", it can be deactivated.
	// 	return location.IsEmpty() ? false : (location.Matches(planet) && $this->toShow.Test(conditions));
	// }
	// 
	// 
	// 
	// // Get the speaker's name.
	// string News::Name() const
	// {
	// 	return names.Get();
	// }
	// 
	// 
	// 
	// // Pick a portrait at random out of the possible options.
	// const Sprite *News::Portrait() const
	// {
	// 	return portraits.empty() ? nullptr : portraits[Random::Int(portraits.size())];
	// }
	// 
	// 
	// 
	// // Get the speaker's message, chosen randomly.
	// string News::Message() const
	// {
	// 	return messages.Get();
	// }

}