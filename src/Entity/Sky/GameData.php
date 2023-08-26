<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;
use App\Entity\Files;
use App\Entity\TemplatedArray;

class GameData {
	
	private static array $sources = []; // vector<string>
	
	private static ?UniverseObjects $objects = null;
	
	private static ?Government $playerGovernment = null;
	
	public static function Init(bool $reinit = false): void {
		if (self::$objects == null && !$reinit) {
			self::$objects = new UniverseObjects();
			self::$playerGovernment = self::$objects->governments['Escort'];
		}
	}
	
	public static function SetSources(array $sources): void {
		self::$sources = $sources;
	}
	
	public static function Sources(): array {
		return self::$sources;
	}
	public static function Objects(): ?UniverseObjects {
		return self::$objects;
	}
	
	public static function Colors(): TemplatedArray {
		if (count(self::$objects->colors) == 0 && self::PlayerGovernment() != null) {
			self::$objects->colors->initContents();
		}
		return self::$objects->colors;
	}
	public static function Commodities(): TemplatedArray {
		if (count(self::$objects->trade->getCommodities()) == 0 && self::PlayerGovernment() != null) {
			self::$objects->trade->getCommodities()->initContents();
		}
		return self::$objects->trade->getCommodities();
	}
	public static function Conversations(): TemplatedArray {
		if (count(self::$objects->conversations) == 0 && self::PlayerGovernment() != null) {
			self::$objects->conversations->initContents();
		}
		return self::$objects->conversations;
	}
	public static function Effects(): TemplatedArray {
		if (count(self::$objects->effects) == 0 && self::PlayerGovernment() != null) {
			self::$objects->effects->initContents();
		}
		return self::$objects->effects;
	}
	public static function Events(): TemplatedArray {
		if (count(self::$objects->events) == 0 && self::PlayerGovernment() != null) {
			self::$objects->events->initContents();
		}
		return self::$objects->events;
	}
	public static function Fleets(): TemplatedArray {
		if (count(self::$objects->fleets) == 0 && self::PlayerGovernment() != null) {
			self::$objects->fleets->initContents();
		}
		return self::$objects->fleets;
	}
	public static function Formations(): TemplatedArray {
		if (count(self::$objects->formations) == 0 && self::PlayerGovernment() != null) {
			self::$objects->formations->initContents();
		}
		return self::$objects->formations;
	}
	public static function Galaxies(): TemplatedArray {
		if (count(self::$objects->galaxies) == 0 && self::PlayerGovernment() != null) {
			self::$objects->galaxies->initContents();
		}
		return self::$objects->galaxies;
	}
	public static function Governments(): TemplatedArray {
		if (count(self::$objects->governments) == 0 && self::PlayerGovernment() != null) {
			self::$objects->governments->initContents();
		}
		return self::$objects->governments;
	}
	public static function Hazards(): TemplatedArray {
		if (count(self::$objects->hazards) == 0 && self::PlayerGovernment() != null) {
			self::$objects->hazards->initContents();
		}
		return self::$objects->hazards;
	}
	public static function Interfaces(): TemplatedArray {
		if (count(self::$objects->interfaces) == 0 && self::PlayerGovernment() != null) {
			self::$objects->interfaces->initContents();
		}
		return self::$objects->interfaces;
	}
	public static function Minables(): TemplatedArray {
		if (count(self::$objects->minables) == 0 && self::PlayerGovernment() != null) {
			self::$objects->minables->initContents();
		}
		return self::$objects->minables;
	}
	public static function Missions(): TemplatedArray {
		if (count(self::$objects->missions) == 0 && self::PlayerGovernment() != null) {
			self::$objects->missions->initContents();
		}
		return self::$objects->missions;
	}
	public static function SpaceportNews(): TemplatedArray {
		if (count(self::$objects->spaceportNews) == 0 && self::PlayerGovernment() != null) {
			self::$objects->spaceportNews->initContents();
		}
		return self::$objects->spaceportNews;
	}
	public static function Outfits(): TemplatedArray {
		if (count(self::$objects->outfits) == 0 && self::PlayerGovernment() != null) {
			self::$objects->outfits->initContents();
		}
		return self::$objects->outfits;
	}
	public static function Persons(): TemplatedArray {
		if (count(self::$objects->persons) == 0 && self::PlayerGovernment() != null) {
			self::$objects->persons->initContents();
		}
		return self::$objects->persons;
	}
	public static function Phrases(): TemplatedArray {
		if (count(self::$objects->phrases) == 0 && self::PlayerGovernment() != null) {
			self::$objects->phrases->initContents();
		}
		return self::$objects->phrases;
	}
	public static function Planets(): TemplatedArray {
		if (count(self::$objects->planets) == 0 && self::PlayerGovernment() != null) {
			self::$objects->planets->initContents();
		}
		return self::$objects->planets;
	}
	public static function Ships(): TemplatedArray {
		if (count(self::$objects->ships) == 0 && self::PlayerGovernment() != null) {
			self::$objects->ships->initContents();
		}
		return self::$objects->ships;
	}
	public static function Systems(): TemplatedArray {
		if (count(self::$objects->systems) == 0 && self::PlayerGovernment() != null) {
			self::$objects->systems->initContents();
		}
		return self::$objects->systems;
	}
	public static function Wormholes(): TemplatedArray {
		if (count(self::$objects->wormholes) == 0 && self::PlayerGovernment() != null) {
			self::$objects->wormholes->initContents();
		}
		return self::$objects->wormholes;
	}
	public static function Outfitters(): TemplatedArray {
		return self::$objects->outfitSales;
	}
	public static function Shipyards(): TemplatedArray {
		return self::$objects->shipSales;
	}
	
	public static function GetPolitics(): Politics {
		return self::$objects->politics;
	}
	
	public static function PlayerGovernment(): Government {
		if (self::$objects == null) {
			self::Init();
		}
		if (self::$playerGovernment == null) {
			self::$playerGovernment = self::$objects->governments['Escort'];
		}
		return self::$playerGovernment;
	}
	
	public static function FindImages(): array { // map<string, shared_ptr<ImageSet>>
		$images = array();
		foreach (self::$sources as $source) {
			// All names will only include the portion of the path that comes after
			// this directory prefix.
			$directoryPath = $source . "images/";
			$start = strlen($directoryPath);
	
			$imageFiles = Files::RecursiveList($directoryPath);
			foreach ($imageFiles as $path) {
				if (ImageSet::IsImage($path)) {
					$name = ImageSet::Name(substr($path, $start));
					
					if (!isset($images[$name])) {
						$images[$name] = new ImageSet($name);
					}
					$images[$name]->add($path);
				}
			}
		}
		return $images;
	}
}