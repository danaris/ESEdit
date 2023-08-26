<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class Random {
	// Seed the generator (e.g. to make it produce exactly the same random
	// numbers it produced previously).
	public static function Seed(int $seed): void {
		mt_srand($seed);
	}
	
	public static function Int(?int $upperBound = null): int {
		if ($upperBound === null) {
			$upperBound = mt_getrandmax();
		}
		return mt_rand(0, $upperBound);
	}
	
	public static function Real(): float {
		return (float)mt_rand() / (float)mt_getrandmax();
	}
	// 
	// // Return the expected number of failures before k successes, when the
	// // probability of success is p. The mean value will be k / (1 - p).
	// uint32_t Random::Polya(uint32_t k, double p)
	// {
	// 	negative_binomial_distribution<uint32_t> polya(k, p);
	// #ifndef __linux__
	// 	lock_guard<mutex> lock(workaroundMutex);
	// #endif
	// 	return polya(gen);
	// }
	// 
	// 
	// 
	// // Get a number from a binomial distribution (i.e. integer bell curve).
	// uint32_t Random::Binomial(uint32_t t, double p)
	// {
	// 	binomial_distribution<uint32_t> binomial(t, p);
	// #ifndef __linux__
	// 	lock_guard<mutex> lock(workaroundMutex);
	// #endif
	// 	return binomial(gen);
	// }
	
	// Get a normally distributed number with standard or specified mean and stddev.
	// reimplementation courtesy of KEINOS https://www.php.net/manual/en/function.stats-rand-gen-normal.php
	public static function Normal(float $mean, float $sigma): float {
		$x = mt_rand() / mt_getrandmax();
		$y = mt_rand() / mt_getrandmax();
	
		return sqrt(-2 * log($x)) * cos(2 * pi() * $y) * $sigma + $mean;
	}

}