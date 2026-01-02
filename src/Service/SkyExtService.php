<?php

namespace App\Service;

use ESLib\Color;
use ESLib\Files;
use ESLib\Galaxy;
use ESLib\GameData;
use ESLib\Government;
use ESLib\Planet;
use ESLib\Sprite;
use ESLib\StellarObject;
use ESLib\System;
use ESLib\Wormhole;

class SkyExtService {

	public function colorToJSON(Color $Color, string $colorName = ""): array {
		$colorArray = $Color->get();
		$jsonArray = ['name'=>$colorName, 'red'=>$colorArray[0], 'green'=>$colorArray[1], 'blue'=>$colorArray[2], 'alpha'=>$colorArray[3]];

		return $jsonArray;
	}

	public function galaxyToJSON(Galaxy $Galaxy, string $galaxyName = ""): array {
		$jsonArray = [];
		$jsonArray['name'] = $galaxyName;
		$jsonArray['position'] = $Galaxy->getPosition();
		$jsonArray['sprite'] = $Galaxy->getSprite()?->getName();

		return $jsonArray;
	}

	public function planetToJSON(Planet $Planet): array {
		$jsonArray = ['name'=>$Planet->getName()];
		$jsonArray['description'] = $Planet->getDescription();
		//$jsonArray['spaceport'] = $Planet->spaceport;
		$jsonArray['landscape'] = $Planet->getLandscape() ? $Planet->getLandscape()->getName() : '';
		$jsonArray['music'] = $Planet->getMusicName();

		$jsonArray['attributes'] = $Planet->getAttributes();
		$jsonArray['shipSales'] = [];
		// foreach ($Planet->getShipSales() as $Shipyard) {
		// 	$jsonArray['shipSales'] []= $Shipyard->getName();
		// }
		$jsonArray['outfitSales'] = [];
		// foreach ($Planet->getOutfitSales() as $Outfitter) {
		// 	$jsonArray['outfitSales'] []= $Outfitter->getName();
		// }

		error_log('Getting government name for '.$Planet->getName());
		$jsonArray['government'] = $Planet->getGovernment() ? $Planet->getGovernment()->getTrueName() : null;
		error_log('Got ['.$jsonArray['government'].']');
		$jsonArray['requiredReputation'] = $Planet->getRequiredReputation();
		$jsonArray['bribeFraction'] = $Planet->getBribeFraction();
		$jsonArray['security'] = $Planet->getSecurity();
		$jsonArray['inhabited'] = $Planet->isInhabited();
		$jsonArray['customSecurity'] = $Planet->hasCustomSecurity();
		//$jsonArray['requiredAttributes'] = $Planet->requiredAttributes;
		// $jsonArray['tribute'] = $Planet->tribute;
		// $jsonArray['defenseThreshold'] = $Planet->defenseThreshold;
		// $jsonArray['defenseFleets'] = [];
		// foreach ($Planet->defenseFleets as $fleet) {
		// 	$jsonArray['defenseFleets'] []= $fleet->getName();
		// }

		$jsonArray['wormhole'] = $Planet->isWormhole() ? $Planet->getWormhole()?->getName() : '';
		$jsonArray['systems'] = [];
		foreach ($Planet->getSystems() as $System) {
			$jsonArray['systems'] []= $System->getName();
		}

		//$jsonArray['source'] = ['name'=>$Planet->sourceName,'file'=>$Planet->sourceFile,'version'=>$Planet->sourceVersion];

		error_log('Done with planet');

		return $jsonArray;
	}

	public function spriteToJSON(Sprite $Sprite): array {
		$jsonArray = [];
		$jsonArray['name'] = $Sprite->getName();
		$jsonArray['width'] = $Sprite->getWidth();
		$jsonArray['height'] = $Sprite->getHeight();
		$jsonArray['frames'] = $Sprite->getFrames();
		$jsonArray['center'] = $Sprite->getCenter();
		$jsonArray['paths'] = [];
		for ($i=0; $i<$jsonArray['frames']; $i++) {
			$jsonArray['paths'] []= $Sprite->getPath($i);
		}

		return $jsonArray;
	}

	public function systemToJSON(System $System): array {
		$jsonArray = [];

		$jsonArray['name'] = $System->getName();
		$jsonArray['position'] = $System->getPosition();
		$jsonArray['government'] = $System->getGovernment() ? $System->getGovernment()->getTrueName() : 'Uninhabited';
		//$jsonArray['music'] = $this->music;
		$jsonArray['hidden'] = $System->isHidden();
		$jsonArray['inaccessible'] = $System->isInaccessible();
		$jsonArray['inhabited'] = $System->isInhabited();
		//$jsonArray['universalRamscoop'] = $this->universalRamscoop;
		//$jsonArray['ramscoopAddend'] = $this->ramscoopAddend;
		//$jsonArray['ramscoopMultiplier'] = $this->ramscoopMultiplier;
		//$jsonArray['hazeId'] = $this->haze ? $this->haze->getId() : null;
		//$jsonArray['invisibleFenceRadius'] = $this->invisibleFenceRadius;
		//$jsonArray['jumpRange'] = $this->jumpRange;
		$jsonArray['attributes'] = $System->getAttributes();

		//error_log('-% Setting links');
		$jsonArray['links'] = [];
		foreach ($System->getLinks() as $ToSystem) {
			$jsonArray['links'] []= $ToSystem->getName();
		}
		//error_log('-% Setting objects');
		$jsonArray['objects'] = [];
		foreach ($System->getObjects() as $index => $StellarObject) {
			$jsonArray['objects'][$index] = $this->stellarObjectToJSON($StellarObject);
		}
		foreach ($jsonArray['objects'] as $index => $objectArray) {
			if ($objectArray['parentIndex'] != -1) {
				$jsonArray['objects'][$objectArray['parentIndex']]['children'] []= $index;
			}
		}
		//error_log('-% Setting wormholes');
		// $jsonArray['wormholeFromLinks'] = [];
		// foreach ($this->wormholeFromLinks as $WormholeLink) {
		// 	$jsonArray['wormholeFromLinks'] []= ['wormhole'=>$WormholeLink->getWormhole()->getTrueName(), 'toSystem'=>$WormholeLink->getToSystem()->getName()];
		// }
		//error_log('-% Setting neighbors');
		// $jsonArray['neighbors'] = [];
		// foreach ($this->neighbors as $distance => $neighbors) {
		// 	$jsonArray['neighbors'][$distance] = [];
		// 	foreach ($neighbors as $NeighborSystem) {
		// 		$jsonArray['neighbors'][$distance] []= $NeighborSystem->getName();
		// 	}
		// }
		//error_log('-% Returning');

		// $jsonArray['source'] = ['name'=>$this->sourceName,'file'=>$this->sourceFile,'version'=>$this->sourceVersion];

		return $jsonArray;
	}

	public function stellarObjectToJSON(StellarObject $Object): array {
		$jsonArray = [];

		//error_log('--% Setting object basic data for '.$this->sprite?->getName());
		$jsonArray['planet'] = $Object->getPlanet()?->getName();
		$jsonArray['distance'] = $Object->getDistance();
		//$jsonArray['speed'] = $this->speed;
		//$jsonArray['offset'] = $this->offset;
		//$jsonArray['index'] = $this->index;
		$jsonArray['isStar'] = $Object->isStar();
		$jsonArray['isStation'] = $Object->isStation();
		$jsonArray['isMoon'] = $Object->isMoon();
		$jsonArray['parentIndex'] = $Object->getParent();
		//error_log('--% Setting children for '.$this->sprite?->getName());
		$jsonArray['children'] = [];
		// foreach ($this->children as $ChildObject) {
		// 	if ($ChildObject->getParent() == null || $ChildObject->getParent() == $ChildObject) {
		// 		continue;
		// 	}
		// 	$jsonArray['children'] []= $ChildObject->toJSON(true);
		// }

		return $jsonArray;
	}

	public function governmentToJSON(Government $Government): array {
		$jsonArray = ['name' => $Government->getTrueName()];

		$jsonArray['displayName'] = $Government->getName();
		// $jsonArray['swizzle'] = $this->swizzle;
		$jsonArray['color'] = $Government->getColor() ? $this->colorToJSON($Government->getColor()) : $this->colorToJSON(new Color());

		// $jsonArray['attitudeToward'] = $this->attitudeToward; // vector<float>
		// $jsonArray['trusted'] = $this->trusted; // set<const Government *>
		// $jsonArray['customPenalties'] = $this->customPenalties; // map<unsigned, map<int, float>>
		// $jsonArray['initialPlayerReputation'] = $this->initialPlayerReputation;
		// $jsonArray['reputationMax'] = $this->reputationMax;
		// $jsonArray['reputationMin'] = $this->reputationMin;
		// $jsonArray['penaltyFor'] = $this->penaltyFor; // map<int, float>
		// $jsonArray['illegals'] = $this->illegals; // map<const Outfit*, int>
		// $jsonArray['atrocities'] = $this->atrocities; // map<const Outfit*, bool>
		// $jsonArray['bribe'] = $this->bribe;
		// $jsonArray['fine'] = $this->fine;
		// $jsonArray['enforcementZones'] = $this->enforcementZones; // vector<LocationFilter>
		// $jsonArray['language'] = $this->language;
		// $jsonArray['sendUntranslatedHails'] = $this->sendUntranslatedHails;
		// $jsonArray['raidFleets'] = $this->raidFleets; // vector<RaidFleet>
		// $jsonArray['crewAttack'] = $this->crewAttack;
		// $jsonArray['crewDefense'] = $this->crewDefense;
		// $jsonArray['provokedOnScan'] = $this->provokedOnScan;

		// $jsonArray['source'] = ['name'=>$this->sourceName,'file'=>$this->sourceFile,'version'=>$this->sourceVersion];

		return $jsonArray;
	}

	public function wormholeToJSON(Wormhole $Wormhole): array {
		$jsonArray = [];
		$jsonArray['isAutogenerated'] = $Wormhole->isAutogenerated();

		$jsonArray['planet'] = $Wormhole->getPlanet()->getName();
		$jsonArray['name'] = $Wormhole->getName();
		$jsonArray['mappable'] = $Wormhole->isMappable();
		$jsonArray['linkColor'] = $this->colorToJSON($Wormhole->getLinkColor(), 'Wormhole '.$Wormhole->getName().' color');

		$jsonArray['links'] = [];
		foreach ($Wormhole->getLinks() as $fromName => $toName) {
			$jsonArray['links'][$fromName] = $toName;
		}

		// $jsonArray['source'] = ['name'=>$Wormhole->sourceName,'file'=>$Wormhole->sourceFile,'version'=>$Wormhole->sourceVersion];

		return $jsonArray;
	}

}