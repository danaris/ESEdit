<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class Distribution {
	const SMOOTHNESS_TABLE = [DistributionType::Narrow => .13, DistributionType::Medium => .234, DistributionType::Wide => .314];
	
	public static function ManipulateNormal(float $smoothness, bool $inverted): float {
		// Center values within [0, 1] so that fractional retention begins to accumulate
		// at the endpoints (rather than at the center) of the distribution.
		$randomFactor = Random::Normal(.5, $smoothness);
		// Retain only the fractional information, to keep all absolute values within [0, 1].
		$randomFactor = $randomFactor - floor($randomFactor);

		// Shift negative values into [0, 1] to create redundancy at the endpoints
		if ($randomFactor < 0.) {
			++$randomFactor;
		}

		// Invert probabilities so that endpoints are most probable.
		if ($inverted) {
			if ($randomFactor > .5) {
				$randomFactor -= .5;
			} else if ($randomFactor < .5) {
				$randomFactor += .5;
			}
		}

		// Transform from [0, 1] to [-1, 1] so that the return value can be simply used.
		$randomFactor *= 2;
		--$randomFactor;

		return $randomFactor;
	}
	
	public static function GenerateInaccuracy(float $value, array $distribution) {
		// Check if there is any inaccuracy to apply
		if(!$value) {
			return new Angle();
		}
	
		switch($distribution['type']) {
			case DistributionType::Uniform:
				return new Angle(2 * (Random::Real() - .5) * $value);
			case DistributionType::Narrow:
			case DistributionType::Medium:
			case DistributionType::Wide:
				return new Angle($value * self::ManipulateNormal(self::SMOOTHNESS_TABLE[$distribution['type']],
						$distribution['inverted']));
			case DistributionType::Triangular:
			default:
				return new Angle((Random::Real() - Random::Real()) * $value);
		}
	}
}