<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

enum WormholeStrategy : string {
	// Disallow use of any wormholes.
	case NONE = "no wormholes";
	// Disallow use of wormholes which the player cannot access, such as in
	// the case of a wormhole that requires an attribute to use.
	case ONLY_UNRESTRICTED = "only unrestricted wormholes";
	// Allow use of all wormholes.
	case ALL = "all wormholes";
};

class DistanceCalculationSettings {
	private WormholeStrategy $wormholeStrategy = WormholeStrategy::NONE;
	private bool $assumesJumpDrive = false;
	
	public static function StringFromSettings(DistanceCalculationSettings $settings): string {
		return json_encode(['strategy'=>$settings->getWormholeStrat()->value, 'jumpDrive'=>$settings->getAssumesJumpDrive()]);
	}
	
	public static function SettingsFromString(string $string): DistanceCalculationSettings {
		$settingsArray = json_decode($string, true);
		return new DistanceCalculationSettings(strategy: $settingsArray['strategy'], jumpDrive: $settingsArray['jumpDrive']);
	}
	
	public function __construct(?DataNode $node = null, ?string $strategy = null, ?bool $jumpDrive = null) {
		if ($node) {
			$this->load($node);
		} else if ($strategy !== null) {
			if ($strategy == "no wormholes") {
				$this->wormholeStrategy = WormholeStrategy::NONE;
			} else if ($strategy == "only unrestricted wormholes") {
				$this->wormholeStrategy = WormholeStrategy::ONLY_UNRESTRICTED;
			} else if ($strategy == "all wormholes") {
				$this->wormholeStrategy = WormholeStrategy::ALL;
			}
			$this->assumesJumpDrive = $jumpDrive == true;
		}
	}
	
	public function load(DataNode $node): void {
		foreach ($node as $child) {
			$key = $child->getToken(0);
			if ($key == "no wormholes") {
				$this->wormholeStrategy = WormholeStrategy::NONE;
			} else if ($key == "only unrestricted wormholes") {
				$this->wormholeStrategy = WormholeStrategy::ONLY_UNRESTRICTED;
			} else if ($key == "all wormholes") {
				$this->wormholeStrategy = WormholeStrategy::ALL;
			} else if ($key == "assumes jump drive") {
				$this->assumesJumpDrive = true;
			} else {
				$child->printTrace("Invalid distance calculation setting:");
			}
		}
	}
	
	public function getWormholeStrat(): WormholeStrategy {
		return $this->wormholeStrategy;
	}
	
	public function getAssumesJumpDrive(): bool {
		return $this->assumesJumpDrive;
	}
	
	// bool DistanceCalculationSettings::operator!=(const DistanceCalculationSettings &other) const
	// {
	// 	if(wormholeStrategy != other.wormholeStrategy)
	// 		return true;
	// 	return assumesJumpDrive != other.assumesJumpDrive;
	// }
}