<?php

namespace App\Entity\Sky;

class Angle {
	private int $angle = 0;

	// Suppose you want to be able to turn 360 degrees in one second. Then you are
	// turning 6 degrees per time step. If the Angle lookup is 2^16 steps, then 6
	// degrees is 1092 steps, and your turn speed is accurate to +- 0.05%. That seems
	// plenty accurate to me. At that step size, the lookup table is exactly 1 MB.
	const STEPS = 0x10000;
	const MASK = self::STEPS - 1;
	const DEG_TO_STEP = self::STEPS / 360.;
	const STEP_TO_RAD = M_PI / (self::STEPS / 2);
	const TO_DEG = 180. / M_PI;
	
	// Get a random angle.
	public static function Random(?float $range = null) {
		if ($range === null) {
			return new Angle(intval(Random::Int(self::STEPS)));
		} else {
			$mod = intval(abs($range) * self::DEG_TO_STEP) + 1;
			return new Angle($mod ? Random::Int($mod) & self::MASK : 0);
		}
	}
	
	// Construct an Angle from the given angle in degrees.
	public function __construct(?float $degrees = null, ?Point $point = null) {
		if ($degrees === null && $point !== null) {
			$degrees = self::TO_DEG * atan2($point->X(), -$point->Y());
		}
		if ($degrees !== null) {
			$this->angle = round($degrees * self::DEG_TO_STEP) & self::MASK;
		}
	}
	
	public function plus(Angle $other): Angle {
		$result = new Angle();
		$result->angle = $this->angle + $other->angle;
		return $result;
	}
	
	public function asPlus(Angle $other) {
		$this->angle += $other->angle;
		$this->angle &= self::MASK;
		return $this;
	}
	
	// Angle Angle::operator-(const Angle &other) const
	// {
	// 	Angle result = *this;
	// 	result -= other;
	// 	return result;
	// }
	// 
	// 
	// 
	// Angle &Angle::operator-=(const Angle &other)
	// {
	// 	angle -= other.angle;
	// 	angle &= MASK;
	// 	return *this;
	// }
	// 
	// 
	// 
	// Angle Angle::operator-() const
	// {
	// 	return Angle((-angle) & MASK);
	// }
	// 
	// 
	// 
	// // Get a unit vector in the direction of this angle.
	// Point Angle::Unit() const
	// {
	// 	// The very first time this is called, create a lookup table of unit vectors.
	// 	static std::vector<Point> cache;
	// 	if(cache.empty())
	// 	{
	// 		cache.reserve(STEPS);
	// 		for(int i = 0; i < STEPS; ++i)
	// 		{
	// 			double radians = i * STEP_TO_RAD;
	// 			// The graphics use the usual screen coordinate system, meaning that
	// 			// positive Y is down rather than up. Angles are clock angles, i.e.
	// 			// 0 is 12:00 and angles increase in the clockwise direction. So, an
	// 			// angle of 0 degrees is pointing in the direction (0, -1).
	// 			cache.emplace_back(sin(radians), -cos(radians));
	// 		}
	// 	}
	// 	return cache[angle];
	// }
	// 
	// 
	// 
	// Convert an angle back to a value in degrees.
	public function getDegrees(): float {
		// Most often when this function is used, it's in settings where it makes
		// sense to return an angle in the range [-180, 180) rather than in the
		// Angle's native range of [0, 360).
		return $this->angle / self::DEG_TO_STEP - 360. * ($this->angle >= self::STEPS / 2);
	}
	// 
	// 
	// 
	// // Return a point rotated by this angle around (0, 0).
	// Point Angle::Rotate(const Point &point) const
	// {
	// 	// If using the normal mathematical coordinate system, this would be easier.
	// 	// Since we're not, the math is a tiny bit less elegant:
	// 	Point unit = Unit();
	// 	return Point(-unit.Y() * point.X() - unit.X() * point.Y(),
	// 		-unit.Y() * point.Y() + unit.X() * point.X());
	// }
	// 
	// 
	// 
	// // Constructor using Angle's internal representation.
	// Angle::Angle(int32_t angle)
	// 	: angle(angle)
	// {
	// }

}