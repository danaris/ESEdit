<?php

namespace App\Service;

class ESDataService {

	protected array $missions = [];
	protected array $outfits = [];
	protected array $ships = [];
	protected array $sprites = [];
	protected array $data = [];

	public array $pathsSearch = ['@root','@config','@save'];
	public array $pathsReplace = [];

	public function __construct() {
		$this->pathsReplace = [$_ENV['ES_ROOT'],$_ENV['ES_CONFIG'],$_ENV['ES_CONFIG'].'/saves'];
	}

	public function spriteData(): array {
		if ($this->sprites == []) {
			$spriteJSONPath = $_ENV['ES_DATA_ROOT'].'/sprites.json';
			$spriteJSON = file_get_contents($spriteJSONPath);

			$spriteData = json_decode($spriteJSON, true);

			$this->sprites = $spriteData['sprites'];
		}

		return $this->sprites;
	}

	public function data(): array {
		if ($this->data == []) {
			$jsonFiles = glob($_ENV['ES_DATA_ROOT'].'/*.json');
			foreach ($jsonFiles as $file) {
				$fileParts = pathinfo($file);
				$dataType = $fileParts['filename'];
				$json = file_get_contents($file);
				$data = json_decode($json, true);
				if (isset($data[$dataType])) {
					$data = $data[$dataType];
				}
				$this->data[$dataType] = $data;
			}
		}

		return $this->data;
	}

	public function missionData(string $missionName): array {
		if (!isset($this->missions[$missionName])) {
			$missionFilename = $_ENV['ES_DATA_ROOT'].'/mission/'.$missionName.'.json';
			$missionJson = file_get_contents($missionFilename);
			$missionData = json_decode($missionJson, true);

			$this->missions[$missionName] = $missionData;
		}

		return $this->missions[$missionName];
	}

	public function outfitData(string $outfitName): array {
		if (!isset($this->outfits[$outfitName])) {
			$outfitFilename = $_ENV['ES_DATA_ROOT'].'/outfit/'.$outfitName.'.json';
			$outfitJson = file_get_contents($outfitFilename);
			$outfitData = json_decode($outfitJson, true);

			$this->outfits[$outfitName] = $outfitData;
		}

		return $this->outfits[$outfitName];
	}

	public function shipData(string $shipName): array {
		if (!isset($this->ships[$shipName])) {
			$shipFilename = $_ENV['ES_DATA_ROOT'].'/ship/'.$shipName.'.json';
			$shipJson = file_get_contents($shipFilename);
			$shipData = json_decode($shipJson, true);

			$this->ships[$shipName] = $shipData;
		}

		return $this->ships[$shipName];
	}

	public function spriteToBasePath(array $sprite, bool $hiDPI = false): string {
		if ($hiDPI) {
			$paths = $sprite['paths']['hiDPI'];
		} else {
			$paths = $sprite['paths']['standard'];
		}

		$path = $paths[0];
		$path = str_replace(['@root/images','@config/images'],['',''],$path);

		return $path;
	}

	public function colorToCSS(array $jsonColor): string {
		$red = $jsonColor['red'];
		$green = $jsonColor['green'];
		$blue = $jsonColor['blue'];
		$alpha = $jsonColor['alpha'];
		$color = ['red' => round(255 * $red), 'green' => round(255 * $green), 'blue' => round(255 * $blue), 'alpha' => $alpha];
		return 'rgba('.$color['red'].', '.$color['green'].', '.$color['blue'].', '.$color['alpha'].')';
	}

	public function outfitAttributes(): array {
		$outfitNames = $this->data()['outfitNames'];

		$attributes = ["hit force","shield damage","hull damage","disabled damage","minable damage","fuel damage","heat damage","energy damage","ion damage","weapon jamming damage","disruption damage","slowing damage","discharge damage","corrosion damage","leak damage","burn damage","relative shield damage","relative hull damage","relative disabled damage","relative minable damage","relative fuel damage","relative heat damage","relative energy damage"];
		$haveBasic = false;
		foreach ($outfitNames as $name) {
			$outfit = $this->outfitData($name);

			if (!$haveBasic) {
				foreach ($outfit as $attribute => $val) {
					if (!is_numeric($val)) {
						continue;
					}
					$attributes []= $attribute;
				}
				$haveBasic = true;
			}

			foreach ($outfit['attributes']['_store'] as $attribute => $val) {
				if (!in_array($attribute, $attributes)) {
					$attributes []= $attribute;
				}
			}
		}

		sort($attributes);

		return $attributes;
	}

}