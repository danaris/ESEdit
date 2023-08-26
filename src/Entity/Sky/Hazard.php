<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class Hazard extends Weapon {
	private string $name = '';
	private int $period = 1;
	private int $minDuration = 1;
	private int $maxDuration = 1;
	private float $minStrength = 1.;
	private float $maxStrength = 1.;
	private float $minRange = 0.;
	// Hazards given no range only extend out to the invisible fence defined in AI.cpp.
	private float $maxRange = 10000.;
	private bool $systemWide = false;
	private bool $deviates = true;
	
	private array $environmentalEffects = []; //map<const Effect *, float>
	
	public function load(DataNode $node): void {
		if ($node->size() < 2) {
			return;
		}
		$name = $node->getToken(1);
	
		foreach ($node as $child) {
			$key = $child->getToken(0);
			if ($key == "weapon") {
				$this->loadWeapon($child);
			} else if ($key == "constant strength") {
				$this->deviates = false;
			} else if ($key == "system-wide") {
				$this->systemWide = true;
			} else if ($child->size() < 2) {
				$child->printTrace("Skipping hazard attribute with no value specified:");
			} else if ($key == "period") {
				$this->period = max(1, intval($child->getValue(1)));
			} else if ($key == "duration") {
				$this->minDuration = max(0, intval($child->getValue(1)));
				$this->maxDuration = max($this->minDuration, ($child->size() >= 3 ? intval($child->getValue(2)) : 0));
			} else if ($key == "strength") {
				$this->minStrength = max(0., $child->getValue(1));
				$this->maxStrength = max($this->minStrength, ($child->size() >= 3) ? $child->getValue(2) : 0.);
			} else if ($key == "range") {
				$this->minRange = max(0., ($child->size() >= 3) ? $child->getValue(1) : 0.);
				$this->maxRange = max($this->minRange, ($child->size() >= 3) ? $child->getValue(2) : $child->getValue(1));
			} else if ($key == "environmental effect") {
				// Fractional counts may be accepted, since the real count gets multiplied by the strength
				// of the hazard. The resulting real count will then be rounded down to the nearest int
				// to determine the number of effects that appear.
				$count = ($child->size() >= 3) ? floatval($child->getValue(2)) : 1.0;
				$effectName = $child->getToken(1);
				$effect = GameData::Effects()[$effectName];
				if (!isset($this->environmentalEffects[$effectName])) {
					$this->environmentalEffects[$effectName] = ['effect'=>$effect, 'count'=>0.0];
				}
				$this->environmentalEffects[$effectName]['count'] += $count;
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	}
	
	
	// 
	// // Whether this hazard has a valid definition.
	// bool Hazard::IsValid() const
	// {
	// 	return !name.empty();
	// }
	// 
	// 
	// 
	// // The name of the hazard in the data files.
	// const string &Hazard::Name() const
	// {
	// 	return name;
	// }
	// 
	// 
	// 
	// // Does the strength of this hazard deviate over time?
	// bool Hazard::Deviates() const
	// {
	// 	return deviates;
	// }
	// 
	// 
	// 
	// // How often this hazard deals its damage while active.
	// int Hazard::Period() const
	// {
	// 	return period;
	// }
	// 
	// 
	// 
	// // Generates a random integer between the minimum and maximum duration of this hazard.
	// int Hazard::RandomDuration() const
	// {
	// 	return minDuration + (maxDuration <= minDuration ? 0 : Random::Int(maxDuration - minDuration));
	// }
	// 
	// 
	// 
	// 
	// // Generates a random double between the minimum and maximum strength of this hazard.
	// double Hazard::RandomStrength() const
	// {
	// 	return minStrength + (maxStrength <= minStrength ? 0. : (maxStrength - minStrength) * Random::Real());
	// }
	// 
	// 
	// 
	// bool Hazard::SystemWide() const
	// {
	// 	return systemWide;
	// }
	// 
	// 
	// 
	// // The minimum and maximum distances from the origin in which this hazard has an effect.
	// double Hazard::MinRange() const
	// {
	// 	return minRange;
	// }
	// 
	// 
	// 
	// double Hazard::MaxRange() const
	// {
	// 	return maxRange;
	// }
	// 
	// 
	// 
	// // Visuals to be created while this hazard is active.
	// const map<const Effect *, float> &Hazard::EnvironmentalEffects() const
	// {
	// 	return environmentalEffects;
	// }

}