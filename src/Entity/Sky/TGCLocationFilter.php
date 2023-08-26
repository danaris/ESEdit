<?php

namespace App\Entity\Sky;

class TGCLocationFilter {
	public ?array $planetNot;
	public ?array $planetNeighbor;
	public ?array $planets;
	public ?array $systemNot;
	public ?array $systemNeighbor;
	public ?array $systems;
	public ?array $governmentNot;
	public ?array $governmentNeighbor;
	public ?array $governments;
	public ?array $attributeNot;
	public ?array $attributeNeighbor;
	public ?array $attributes;
	public ?array $outfitNot;
	public ?array $outfitNeighbor;
	public ?array $outfits;
	public ?array $categoryNot;
	public ?array $categoryNeighbor;
	public ?array $categories;
	
	public ?string $nearModifier;
	public ?string $nearSystem;
	public ?int $nearSystemMin;
	public ?int $nearSystemMax;
	public ?array $nearDistanceCalculationSettings;
	public ?string $distanceModifier;
	public ?string $distanceSystem;
	public ?int $distanceSystemMin;
	public ?int $distanceSystemMax;
	public ?array $distanceDistanceCalculationSettings;
	public ?LocationFilter $not;
	public ?LocationFilter $neighbour;
}