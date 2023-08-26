<?php

namespace App\Service;

use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Sky\Color;
use App\Entity\Sky\Condition;
use App\Entity\Sky\Description;
use App\Entity\Sky\Galaxy;
use App\Entity\Sky\GameData;
use App\Entity\Sky\Government;
use App\Entity\Sky\Link;
use App\Entity\Sky\LocationFilter;
use App\Entity\Sky\Mission;
use App\Entity\Sky\Planet;
use App\Entity\Sky\Point;
use App\Entity\Sky\Ramscoop;
use App\Entity\Sky\SpriteSet;
use App\Entity\Sky\System;
use App\Entity\Sky\SystemObject;
use App\Entity\Sky\UniverseObjects;
use App\Entity\Sky\Wormhole;

class SkyService {
	
	protected array $images = array();
	protected array $elements = array();
	protected array $data = array();
	protected array $errors = array();
	public string $basePath = ''; 
	public string $dataBasePath = '';
	public string $imageBasePath = '';
	
	public array $plugins = array();
	
	protected string $dataCachePath = '/var/cache/skyEdit.skyData';
	protected string $universeCachePath = '/var/cache/skyEditUniverse.skyData';
	protected string $imageCachePath = '/var/cache/skyEditImages.skyData';
	protected string $configPath = '/var/cache/skyEdit.config';
	
	protected array $config = array();
	
	protected bool $debug = false;
	
	public function __construct(protected LoggerInterface $logger,
								protected SerializerInterface $serializer,
								protected EntityManagerInterface $em,
								$projectDir) {
		$this->basePath = $_ENV['DATA_PATH'];
		$this->dataBasePath = $_ENV['DATA_PATH'].'data/';
		$this->imageBasePath = $_ENV['DATA_PATH'].'images/';
		$this->configPath = $projectDir.$this->configPath;
		$this->dataCachePath = $projectDir.$this->dataCachePath;
		$this->universeCachePath = $projectDir.$this->universeCachePath;
		$this->imageCachePath = $projectDir.$this->imageCachePath;
		
		$taService = TemplatedArrayService::Instance();
		$taService->setEntityManager($this->em);
		
		$this->data['galaxies'] = array();
		$this->data['systems'] = array();
		$this->data['governments'] = array();
		$this->data['wormholes'] = array();
		$this->data['colors'] = array();
		$this->data['planets'] = array();
		
		$this->data['missions'] = array();
	}
	
	public function truncateTables($tableNames = array(), $cascade = false) {
		$connection = $this->em->getConnection();
		$platform = $connection->getDatabasePlatform();
		$connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
		foreach ($tableNames as $name) {
			$connection->executeUpdate($platform->getTruncateTableSQL($name,$cascade));
		}
		$connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');
	}
	
	public function loadUniverseFromFiles($checkCache = true, $loadImages = true) {
		// This should be a reliable method to tell if we've at least got something loaded
		$this->logger->debug('LUFF checking player government');
		$MyGov = GameData::PlayerGovernment();
		if ($MyGov->getSwizzle() != 5) {
			$this->logger->debug('LUFF need to load stuff');
			$loaded = false;
			if ($checkCache) {
				if (file_exists($this->universeCachePath)) {
					$universeObjects = unserialize(file_get_contents($this->universeCachePath));
					GameData::SetObjects($universeObjects);
					$loaded = true;
				}
			}
			$this->truncateTables(['Body', 'Color', 'ConditionSet', 'Conversation', 'ConversationElement', 'ConversationNode', 'Effect', 'EventTrigger', 'Expression', 'FlareSound', 'FlareSprite', 'GameAction', 'GameEvent', 'Government', 'GovernmentPenalty', 'JumpSound', 'LocationFilter', 'Mission', 'MissionAction', 'NPC', 'Outfit', 'OutfitAttributes', 'OutfitEffect', 'OutfitPenalty', 'Phrase', 'Planet', 'Ship', 'Sound', 'Sprite', 'SubExpression', 'System', 'SystemLink', 'TextReplacements', 'Wormhole', 'WormholeLink', 'locationfilter_government', 'locationfilter_system']);
			if (!$loaded) {
				$sources = ['/Users/tcollett/Development/ThirdParty/endless-sky/'];
				GameData::SetSources($sources);
				//$this->loadImageDB();		
				$images = GameData::FindImages();
				
				// From the name, strip out any frame number, plus the extension.
				foreach ($images as $imageName => $ImageSet) {
					// This should never happen, but just in case:
					if (!$ImageSet) {
						continue;
					}
				
					// Reduce the set of images to those that are valid.
					$ImageSet->validateFrames();
				}
				SpriteSet::SetImageData($images);
				GameData::Objects()->load($sources, true, $this->em);
				
				// $universeSerial = serialize(GameData::Objects());
				// file_put_contents($this->universeCachePath, $universeSerial);
			} else if ($loadImages) {
				$this->loadImageDB();
				SpriteSet::SetImageData($this->images);
			}
			$this->logger->debug('LUFF finishing loading');
			GameData::Objects()->finishLoading($this->em);
			$this->em->flush();
		}
		$this->logger->debug('LUFF done');
	}
	
	public function getImages() {
		return $this->images;
	}
	
	public function getElements() {
		return $this->elements;
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function clearDataCache() {
		unlink($this->dataCachePath);
	}
	
	public function clearImageCache() {
		unlink($this->imageCachePath);
	}
	
	public function addPlugin($name, $path) {
		if (substr($path, -1)) {
			$path .= '/';
		}
		$plugin = ['name'=>$name,'basePath'=>$path];
		$this->plugins []= $this->prepPlugin($plugin);
	}
	
	public function removePlugin($name) {
		$pluginIndex = -1;
		for ($i=0; $i<count($this->plugins); $i++) {
			if ($this->plugins['name'] == $name) {
				$pluginIndex = $i;
			}
		}
		if ($pluginIndex > -1) {
			array_splice($this->plugins, $pluginIndex, 1);
		}
	}
	
	public function prepPlugin($plugin) {
		$plugin['dataBasePath'] = $plugin['basePath'].'data/';
		$plugin['imageBasePath'] = $plugin['basePath'].'images/';
		return $plugin;
	}
	
	public function loadConfig() {
		$this->config = json_decode(file_get_contents($this->configPath), true);
		if (isset($this->config['basePath'])) {
			$this->basePath = $this->config['basePath'];
			$this->dataBasePath = $this->basePath.'data/';
			$this->imageBasePath = $this->basePath.'images/';
		}
		if (isset($this->config['plugins'])) {
			foreach ($this->config['plugins'] as $plugin) {
				$plugin = $this->prepPlugin($plugin);
				$this->plugins []= $plugin;
			}
		}
	}
	
	public function saveConfig() {
		$saveConfig = array();
		$saveConfig['basePath'] = $this->basePath;
		$savePlugins = array();
		$configPlugins = $this->plugins;
		foreach ($configPlugins as $plugin) {
			$savePlugin = array();
			$savePlugin['name'] = $plugin['name'];
			$savePlugin['basePath'] = $plugin['basePath'];
			$savePlugins []= $savePlugin;
		}
		$saveConfig['plugins'] = $savePlugins;
		
		$configJSON = json_encode($saveConfig);
		file_put_contents($this->configPath, $configJSON);
	}
	
	function loadImageDB() {
		if (file_exists($this->imageCachePath)) {
			$this->images = json_decode(file_get_contents($this->imageCachePath), true);
		} else {
			$this->buildImageDB();
			$imageJSON = json_encode($this->images);
			file_put_contents($this->imageCachePath, $imageJSON);
		}
	}
	
	function buildImageDB() {
		if (count($this->config) == 0) {
			$this->loadConfig();
		}
		$this->images = $this->processImageDir($this->imageBasePath, '');
		foreach ($this->plugins as $plugin) {
			$this->images = array_merge($this->images, $this->processImageDir($plugin['imageBasePath'], ''));
		}
	}
	
	function processImageDir($baseDir, $imageDir) {
		if (strlen($imageDir) > 0 && substr($imageDir, -1) != '/') {
			$imageDir .= '/';
		}
		$imagesHere = array();
		$imageFiles = scandir($baseDir.$imageDir);
		foreach ($imageFiles as $filename) {
			if (substr($filename, 0, 1) == '.') {
				continue;
			}
			$imageFile = $imageDir.$filename;
			//$this->logger->info('Found image '.$imageFile);
			$imagePath = $baseDir.$imageFile;
			if (is_dir($imagePath)) {
				//$this->logger->info('Is directory, recursing');
				$imagesHere = array_merge($imagesHere, $this->processImageDir($baseDir, $imageFile));
			} else {
				$strippedFilename = ImageSet::Name($filename);
				//$strippedFilename = str_replace(['+'], [''], $pathInfo['filename']);
				$imageKey = $imageDir.$strippedFilename;
				$imageSize = getimagesize($imagePath);
				$imagesHere[$imageKey] = ['path'=>$imageFile, 'width'=>$imageSize[0], 'height'=>$imageSize[1]];
			}
		}
		return $imagesHere;
	}
	
	function processDataDir($baseDir, $dataDir) {
		//$this->logger->info('Processing data from '.$dataDir);
		if (strlen($dataDir) > 0 && substr($dataDir, -1) != '/') {
			$dataDir .= '/';
		}
		$dataHere = array();
		$dataFiles = scandir($baseDir.$dataDir);
		foreach ($dataFiles as $filename) {
			if (substr($filename, 0, 1) == '.') {
				continue;
			}
			$dataFile = $dataDir.$filename;
			//$this->logger->info('Found data '.$dataFile);
			$dataPath = $baseDir.$dataFile;
			if (is_dir($dataPath)) {
				//$this->logger->info('Is directory, recursing');
				$dataHere = array_merge($dataHere, $this->processDataDir($baseDir, $dataFile));
			} else {
				$dataHere = array_merge($dataHere, $this->processDataFile($dataPath));
			}
		}
		return $dataHere;
	}
	
	function loadData() {
		if (file_exists($this->dataCachePath)) {
			$this->elements = json_decode(file_get_contents($this->dataCachePath), true);
		} else {
			$this->importData(false);
			$dataJSON = json_encode($this->elements);
			file_put_contents($this->dataCachePath, $dataJSON);
		}
	}
	
	function importData($checkCache=true) {
		if (count($this->config) == 0) {
			$this->loadConfig();
		}
		
		if (!$checkCache) {
			$this->elements = $this->processDataDir($this->dataBasePath, '');
			$dataJSON = json_encode($this->elements);
			file_put_contents($this->dataCachePath, $dataJSON);
		} else {
			$this->loadData();
		}
		foreach ($this->plugins as $plugin) {
			$this->elements = array_merge($this->elements, $this->processDataDir($plugin['dataBasePath'], ''));
		}
		
		//$this->logger->info('Got '.count($data).' elements');
		
		$galaxies = $this->data['galaxies'];
		$systems = $this->data['systems'];
		$governments = $this->data['governments'];
		$wormholes = $this->data['wormholes'];
		$colors = $this->data['colors'];
		$planets = $this->data['planets'];
		
		$missions = $this->data['missions'];
		
		foreach ($this->elements as $dKey => $element) {
			if ($dKey == '_filename') {
				//$this->logger->info('Data from '.$element);
				continue;
			}
			//$this->logger->info('Data of type '.$element['_type']);
			switch ($element['_type']) {
				case 'galaxy':
					$thisGalaxy = new Galaxy();
					$thisGalaxy->name = $element['_name'];
					foreach ($element as $elKey => $elVal) {
						if ($elKey == 'pos') {
							$thisGalaxy->pos = new Point();
							$thisGalaxy->pos->x = floatval($elVal[0][0]);
							$thisGalaxy->pos->y = floatval($elVal[0][1]);
						} else if ($elKey == 'sprite') {
							if (isset($elVal[0]['_name'])) {
								$thisGalaxy->sprite = $elVal[0]['_name'];
								if (isset($elVal[0]['scale'])) {
									$thisGalaxy->spriteScale = $elVal[0]['scale'][0][0];
								}
							} else {
								$thisGalaxy->sprite = $elVal[0][0];
							}
						}
					}
					$galaxies[$thisGalaxy->name] = $thisGalaxy;
					break;
				case 'system':
					$thisSystem = new System();
					foreach ($element as $elKey => $elVal) {
						if ($elKey == '_name') {
							if (isset($systems[$elVal])) {
								$thisSystem = $systems[$elVal];
							} else {
								$thisSystem->name = $elVal;
							}
							//$this->logger->info('Processing system ['.$thisSystem['name'].']');
						} else if ($elKey == 'pos') {
							$thisSystem->pos = new Point();
							$thisSystem->pos->x = floatval($elVal[0][0]);
							$thisSystem->pos->y = floatval($elVal[0][1]);
						} else if ($elKey == 'link') {
							foreach ($elVal as $lKey => $link) {
								if ($lKey == 'link') {
									continue;
								}
								//$this->logger->info('Found link to '.print_r($link, true));
								$sysLink = new Link();
								$sysLink->from = $thisSystem->name;
								$sysLink->to = $link[0];
								$thisSystem->links []= $sysLink;
							}
						} else if ($elKey == 'government') {
							$thisSystem->government = $elVal[0][0];
						} else if ($elKey == 'object') {
							//$this->logger->info('System objects: ['.print_r($elVal, true).']');
							$this->arrayToSystemObject($thisSystem, null, $elVal);
							//$this->logger->info('**Finished objects for system '.$thisSystem->name.'**');
						} else if ($elKey == 'hidden') {
							$thisSystem->hidden = true;
						} else if ($elKey == 'attributes') {
							for ($i=0; $i<count($elVal[0]); $i++) {
								if (!isset($elVal[0][$i])) {
									continue;
								}
								$thisSystem->attributes []= $elVal[0][$i];
							}
						} else if ($elKey == 'habitable') {
							$thisSystem->habitable = intval($elVal[0][0]);
						} else if ($elKey == 'belt') {
							$thisSystem->belt = intval($elVal[0][0]);
						} else if ($elKey == 'ramscoop') {
							$thisSystem->ramscoop = $this->arrayToRamscoop($elVal[0]);
						} else if ($elKey == 'invisible fence') {
							$thisSystem->invisibleFence = intval($elVal[0][0]);
						} else if ($elKey == 'jump range') {
							$thisSystem->jumpRange = intval($elVal[0][0]);
						} else if ($elKey == 'starfield density') {
							$thisSystem->starfieldDensity = floatval($elVal[0][0]);
						} else if ($elKey == 'haze') {
							$thisSystem->haze = $elVal[0][0];
						} else if ($elKey == 'music') {
							$thisSystem->music = $elVal[0][0];
						}
					}
					$systems[$thisSystem->name] = $thisSystem;
					break;
				case 'government':
					$thisGovernment = new Government();
					//$this->logger->info('Processing government element ['.print_r($element, true).']');
					foreach ($element as $elKey => $elVal) {
						//$this->logger->info(' - Government key ['.$elKey.']');
						if ($elKey == '_name') {
							$thisGovernment->name = $elVal;
						} else if ($elKey == 'color') {
							//$this->logger->info(' -- Color with value ['.print_r($elVal, true).']');
							if (count($elVal[0]) == 2) {
								$thisGovernment->color = $elVal[0][0];
							} else {
								$thisGovernment->color = $this->floatToIntColor($elVal[0]);
							}
						}
					}
					$governments[$thisGovernment->name] = $thisGovernment;
					break;
				case 'color':
					$thisColor = new Color();
					//$this->logger->info('Processing color element ['.print_r($element, true).']');
					foreach ($element as $elKey => $elVal) {
						if ($elKey == '_name') {
							$thisColor->name = $elVal;
						} else if ($elKey == '_params') {
							$color = $this->floatToIntColor($elVal);
							$thisColor->red = $color[0];
							$thisColor->green = $color[1];
							$thisColor->blue = $color[2];
						}
					}
					$colors[$thisColor->name] = $thisColor;
					break;
				case 'wormhole':
					$thisWormhole = new Wormhole();
					//$this->logger->info('Processing wormhole element ['.print_r($element, true).']');
					foreach ($element as $elKey => $elVal) {
						if ($elKey == '_name') {
							$thisWormhole->name = $elVal;
						} else if ($elKey == 'link') {
							//$this->logger->info(' -- Link with value ['.print_r($elVal, true).']');
							foreach ($elVal as $lKey => $link) {
								if ($lKey == '_type') {
									continue;
								}
								$thisLink = new Link();
								$thisLink->from = $link[0];
								$thisLink->to = $link[1];
								$thisWormhole->links []= $thisLink;
							}
						} else if ($elKey == 'color') {
							if (count($elVal[0]) == 2) {
								$thisWormhole->color = $elVal[0][0];
							} else {
								$thisWormhole->color = $this->floatToIntColor($elVal[0]);
							}
						}
					}
					$wormholes[$thisWormhole->name] = $thisWormhole;
					break;
				case 'planet':
					$thisPlanet = new Planet();
					foreach ($element as $elKey => $elVal) {
						if ($elKey == '_name') {
							$thisPlanet->name = $elVal;
						} else if ($elKey == 'attributes') {
							for ($i=0; $i<count($elVal[0]); $i++) {
								if (!isset($elVal[0][$i])) {
									continue;
								}
								$thisPlanet->attributes []= $elVal[0][$i];
							}
						} else if ($elKey == 'required attributes') {
							for ($i=0; $i<count($elVal[0]); $i++) {
								if (!isset($elVal[0][$i])) {
									continue;
								}
								$thisPlanet->requiredAttributes []= $elVal[0][$i];
							}
						} else if ($elKey == 'landscape') {
							$thisPlanet->landscape = $elVal[0][0];
						} else if ($elKey == 'music') {
							$thisPlanet->music = $elVal[0][0];
						} else if ($elKey == 'description') {
							$thisPlanet->description->addText($elVal[0][0]);
						} else if ($elKey == 'spaceport') {
							$thisPlanet->spaceport->addText($elVal[0][0]);
						} else if ($elKey == 'shipyard') {
							$thisPlanet->shipyards []= $elVal[0][0];
						} else if ($elKey == 'outfitter') {
							$thisPlanet->outfitters []= $elVal[0][0];
						} else if ($elKey == 'required reputation') {
							$thisPlanet->requiredReputation = intval($elVal[0][0]);
						} else if ($elKey == 'bribe') {
							$thisPlanet->bribe = floatval($elVal[0][0]);
						} else if ($elKey == 'security') {
							$thisPlanet->security = floatval($elVal[0][0]);
						} else if ($elKey == 'wormhole') {
							$thisPlanet->wormhole = $elVal[0][0];
						} else if ($elKey == 'tribute') {
							$thisPlanet->tributeAmount = intval($elVal[0]['_name']);
							if (isset($elVal['threshold'])) {
								$thisPlanet->tributeThreshold = intval($elVal['threshold'][0][0]);
							}
							if (isset($elVal['fleet'])) {
								foreach ($elVal['fleet'] as $fKey => $fleet) {
									if ($fKey == '_type') {
										continue;
									}
									$thisPlanet->tributeFleets []= ['name'=>$fleet[0],'count'=>$fleet[1]];
								}
							}
						}
					}
					$planets[$thisPlanet->name] = $thisPlanet;
					break;
				// case 'mission':
				// 	$thisMission = new Mission();
				// 	foreach ($element as $elKey => $elVal) {
				// 		$this->logger->debug('Mission element with key ['.$elKey.'] and value ['.print_r($elVal,true).']');
				// 		if ($elKey == '_name') {
				// 			$this->logger->debug(' - setting the name');
				// 			if (isset($missions[$elVal])) {
				// 				$thisMission = $missions[$elVal];
				// 			} else {
				// 				$thisMission->name = $elVal;
				// 			}
				// 			//$this->logger->info('Processing mission ['.$thisMission['name'].']');
				// 		} else if ($elKey == 'name') {
				// 			$this->logger->debug(' - setting the display name');
				// 			$thisMission->displayName = $elVal[0][0];
				// 		} else if ($elKey == 'description') {
				// 			$this->logger->debug(' - setting the desc');
				// 			$thisMission->description->addText($elVal[0][0]);
				// 		} else if ($elKey == 'blocked') {
				// 			$this->logger->debug(' - setting block message');
				// 			$thisMission->blocked->addText($elVal[0][0]);
				// 		} else if ($elKey == 'deadline') {
				// 			$this->logger->debug(' - setting the deadline');
				// 			$deadlineArray = array();
				// 			if (isset($elVal[0][0])) {
				// 				$deadlineArray['days'] = $elVal[0][0];
				// 			} else {
				// 				$deadlineArray['days'] = 0;
				// 			}
				// 			if (isset($elVal[0][1]))  {
				// 				$deadlineArray['multiplier'] = $elVal[0][1];
				// 			} else {
				// 				$deadlineArray['multiplier'] = 2;
				// 			}
				// 			if ($thisMission->deadline) {
				// 				$deadlineArray['days'] += $thisMission->deadline['days'];
				// 				$deadlineArray['multiplier'] += $thisMission->deadline['multiplier'];
				// 			}
				// 			$thisMission->deadline = $deadlineArray;
				// 		} else if ($elKey == 'illegal') {
				// 			$this->logger->debug(' - setting the illegality');
				// 			$illegalArray = array();
				// 			$illegalArray['fine'] = $elVal[0][0];
				// 			if (isset($elVal[0][1])) {
				// 				$illegalArray['message'] = $elVal[0][1];
				// 			}
				// 			
				// 			$thisMission->illegal = $illegalArray;
				// 		} else if ($elKey == 'cargo') {
				// 			$this->logger->debug(' - setting the cargo');
				// 			$cargoArray = array();
				// 			$cargoArray['type'] = $elVal[0][0];
				// 			$cargoArray['count'] = $elVal[0][1];
				// 			if (isset($elVal[0][2])) {
				// 				$cargoArray['chanceCount'] = $elVal[0][2];
				// 			}
				// 			if (isset($elVal[0][3])) {
				// 				$cargoArray['chance'] = $elVal[0][3];
				// 			}
				// 			$thisMission->cargo = $cargoArray;
				// 		} else if ($elKey == 'stealth') {
				// 			$this->logger->debug(' - setting stealth');
				// 			$thisMission->stealth = true;
				// 		} else if ($elKey == 'invisible') {
				// 			$this->logger->debug(' - setting invisible');
				// 			$thisMission->invisible = true;
				// 		} else if ($elKey == 'priority') {
				// 			$this->logger->debug(' - setting priority');
				// 			$thisMission->priority = true;
				// 		} else if ($elKey == 'minor') {
				// 			$this->logger->debug(' - setting minor');
				// 			$thisMission->minor = true;
				// 		} else if ($elKey == 'infiltrating') {
				// 			$this->logger->debug(' - setting '.$elKey);
				// 			$thisMission->infiltrating = true;
				// 		} else if (in_array($elKey, ['job', 'landing', 'assisting', 'boarding', 'shipyard', 'outfitter'])) {
				// 			$this->logger->debug(' - setting offeredAt to '.$elKey);
				// 			$thisMission->offeredAt = $elKey;
				// 			if ($elKey == 'boarding' && isset($elVal[0]['override capture'])) {
				// 				$thisMission->overrideCapture = true;
				// 			}
				// 		} else if ($elKey == 'apparent payment') {
				// 			$this->logger->debug(' - setting '.$elKey);
				// 			$thisMission->apparentPayment = $elVal[0][0];
				// 		} else if ($elKey == 'repeat') {
				// 			$this->logger->debug(' - setting '.$elKey);
				// 			if (isset($elVal[0][0])) {
				// 				$thisMission->repeat = $elVal[0][0];
				// 			} else {
				// 				$thisMission->repeat = -1;
				// 			}
				// 		} else if ($elKey == 'clearance') {
				// 			$this->logger->debug(' - setting '.$elKey);
				// 			if (isset($elVal[0][0])) {
				// 				$thisMission->clearance = new Description();
				// 				$thisMission->clearance->addText($elVal[0][0]);
				// 			} else {
				// 				$thisMission->clearance = true;
				// 			}
				// 		} else if ($elKey == 'source') {
				// 			$this->logger->debug(' - setting '.$elKey);
				// 			if (count($elVal[0]) == 2 && isset($elVal[0][0])) {
				// 				$thisMission->sourcePlanet = $elVal[0][0];
				// 			} else {
				// 				$thisMission->sourceLocations = $this->arrayToLocationFilter($elVal[0]);
				// 			}
				// 		} else if ($elKey == 'destination') {
				// 			$this->logger->debug(' - setting '.$elKey);
				// 			if (count($elVal[0]) == 2 && isset($elVal[0][0])) {
				// 				$thisMission->destinationPlanet = $elVal[0][0];
				// 			} else {
				// 				$thisMission->destinationLocations = $this->arrayToLocationFilter($elVal[0]);
				// 			}
				// 		} else if ($elKey == 'waypoint') {
				// 			$this->logger->debug(' - setting '.$elKey.'s');
				// 			foreach ($elVal as $wKey => $wVal) {
				// 				if ($wKey == '_type') {
				// 					continue;
				// 				}
				// 				if (isset($wVal[0])) {
				// 					$thisMission->waypoints []= $wVal[0];
				// 				} else {
				// 					$thisMission->waypoints []= $this->arrayToLocationFilter($wVal);
				// 				}
				// 			}
				// 		} else if ($elKey == 'stopover') {
				// 			$this->logger->debug(' - setting '.$elKey.'s');
				// 			foreach ($elVal as $wKey => $wVal) {
				// 				if ($wKey == '_type') {
				// 					continue;
				// 				}
				// 				if (isset($wVal[0])) {
				// 					$thisMission->stopovers []= $wVal[0];
				// 				} else {
				// 					$thisMission->stopovers []= $this->arrayToLocationFilter($wVal);
				// 				}
				// 			}
				// 		} else if ($elKey == 'to') {
				// 			$this->logger->debug(' - setting '.$elKey.' conditions');
				// 			foreach ($elVal as $toKey => $toVal) {
				// 				if ($toKey != '_type') {
				// 					$thisMission->to[$toVal['_name']] = $this->arrayToConditionList($toVal);
				// 				}
				// 			}
				// 		} else {
				// 			$this->logger->debug(' - don\'t recognize it');
				// 		}
				// 	}
				// 	$missions[$thisMission->name] = $thisMission;
				// 	break;
				default:
					if (isset($element['_type'])) {
						$elType = $element['_type'];
					} else {
						$elType = $dKey;
					}
					if (!isset($this->data[$elType])) {
						$this->data[$elType] = array();
					}
					$thisOne = array();
					foreach ($element as $elKey => $elVal) {
						if ($elKey == '_name') {
							$thisOne['name'] = $elVal;
						} else {
							$thisOne[$elKey] = $elVal;
						}
					}
					$this->data[$elType] []= $thisOne;
					break;
			}
		}
		
		foreach ($wormholes as $wormholeName => $wormhole) {
			//$this->logger->info('Post-processing wormhole '.$wormhole['name']);
			foreach ($wormhole->links as $linkId => $link) {
				//$this->logger->info('Calculating angle from '.$link['from'].' to '.$link['to']);
				$fromSystem = $systems[$link->from];
				$toSystem = $systems[$link->to];
				$angle = $this->angleBetween($fromSystem->pos, $toSystem->pos);
				$wormholes[$wormholeName]->links[$linkId]->angle = $angle;
			}
		}
		
		$this->data['galaxies'] = $galaxies;
		$this->data['systems'] = $systems;
		$this->data['colors'] = $colors;
		$this->data['governments'] = $governments;
		$this->data['wormholes'] = $wormholes;
		$this->data['planets'] = $planets;
		
		$this->data['missions'] = $missions;
	}
	
	public function arrayToRamscoop($array) {
		$ramscoop = new Ramscoop();
		if ($array['universal'] == 0) {
			$ramscoop->universal = false;
		} else {
			$ramscoop->universal = true;
		}
		$ramscoop->addend = floatval($array['addend']);
		$ramscoop->multiplier = floatval($array['multiplier']);
		
		return $ramscoop;
	}
	
	public function arrayToConditionList($array) {
		$conditions = array();
		
		foreach ($array as $key => $val) {
			if ($key == 'never') {
				$condition = new Condition();
				$condition->never = true;
				
				$conditions []= $condition;
				
				continue;
			}
			if ($key == 'has') {
				$attribute = $val[0][0];
				$test = '!=';
				$testVal = 0;
			} else if ($key == 'not') {
				$attribute = $val[0][0];
				$test = '==';
				$testVal = 0;
			} else if ($key == 'or') {
				$this->logger->debug('-- Processing "or" condition with value '.print_r($val, true));
				$condition = new Condition();
				$condition->or = $this->arrayToConditionList($val[0]);
				$conditions []= $condition;
				
				continue;
			} else if ($key == 'and') {
				$this->logger->debug('-- Processing "and" condition with value '.print_r($val, true));
				$condition = new Condition();
				$condition->and = $this->arrayToConditionList($val[0]);
				$conditions []= $condition;
				
				continue;
			} else if (in_array($key, ['_depth','_name'])) {
				continue;
			} else {
				$attribute = $key;
				$this->logger->debug('-- Getting info for condition with key '.$key.' and data '.print_r($val,true));
				$test = $val[0][0];
				$testVal = $val[0][1];
			}
			$condition = new Condition();
			$condition->attribute = $attribute;
			$condition->test = $test;
			$condition->val = floatval($testVal);
			
			$conditions []= $condition;
		}
		
		return $conditions;
	}
	
	public function arrayToLocationFilter($array) {
		$distCalcKeys = ['all wormholes','only unrestricted wormholes','no wormholes','assumes jump drive'];
		foreach ($array as $fKey => $filter) {
			$thisFilter = new LocationFilter();
			if ($fKey == '_type') {
				continue;
			}
			$this->logger->info('Handling location filter ['.print_r($filter, true).']');
			if (isset($filter['not'])) {
				if (count($filter['not']) == 1) {
					foreach ($filter['not'] as $notKey1 => $notVals) {
						if ($notKey1 == '_type') {
							continue;
						}
						if ($notVals[0] == 'near') {
							$thisFilter->nearModifier = 'not';
							$thisFilter->nearSystem = $notVals[1];
							if (isset($notVals[2])) {
								$thisFilter->nearSystemMin = intval($notVals[2]);
							}
							if (isset($notVals[3])) {
								$thisFilter->nearSystemMax = intval($notVals[3]);
							}
							foreach ($distCalcKeys as $dcKey) {
								if (isset($notVals[$dcKey])) {
									if (!isset($thisFilter->nearDistanceCalculationSettings)) {
										$thisFilter->nearDistanceCalculationSettings = array();
									}
									$thisFilter->nearDistanceCalculationSettings []= $dcKey;
								}
							}
						} else if ($notVals[0] == 'distance') {
							$thisFilter->distanceModifier = 'not';
							$thisFilter->distanceSystem = $notVals[1];
							if (isset($notVals[2])) {
								$thisFilter->distanceSystemMin = intval($notVals[2]);
							}
							if (isset($notVals[3])) {
								$thisFilter->distanceSystemMax = intval($notVals[3]);
							}
							foreach ($distCalcKeys as $dcKey) {
								if (isset($notVals[$dcKey])) {
									if (!isset($thisFilter->distanceDistanceCalculationSettings)) {
										$thisFilter->distanceDistanceCalculationSettings = array();
									}
									$thisFilter->distanceDistanceCalculationSettings []= $dcKey;
								}
							}
						} else {
							$notType = $notVals[0].'Not';
							$thisFilter->$notType = array();
							foreach ($filter['not'][0] as $notKey => $notVal) {
								if (!is_numeric($notKey) || $notKey < 1) {
									continue;
								}
								$thisFilter->$notType []= $notVal;
							}
						}
					}
				} else {
					$thisFilter->not = $this->arrayToLocationFilter($notVal);
				}
			}
			if (isset($filter['neighbor'])) {
				if (count($filter['neighbor']) == 1) {
					foreach ($filter['neighbor'] as $neighborKey1 => $neighborVals) {
						if ($neighborKey1 == '_type') {
							continue;
						}
						if ($neighborVals[0] == 'near') {
							$thisFilter->nearModifier = 'neighbor';
							$thisFilter->nearSystem = $neighborVals[1];
							if (isset($neighborVals[2])) {
								$thisFilter->nearSystemMin = intval($neighborVals[2]);
							}
							if (isset($neighborVals[3])) {
								$thisFilter->nearSystemMax = intval($neighborVals[3]);
							}
							foreach ($distCalcKeys as $dcKey) {
								if (isset($neighborVals[$dcKey])) {
									if (!isset($thisFilter->nearDistanceCalculationSettings)) {
										$thisFilter->nearDistanceCalculationSettings = array();
									}
									$thisFilter->nearDistanceCalculationSettings []= $dcKey;
								}
							}
						} else if ($neighborVals[0] == 'distance') {
							$thisFilter->distanceModifier = 'neighbor';
							$thisFilter->distanceSystem = $neighborVals[1];
							if (isset($neighborVals[2])) {
								$thisFilter->distanceSystemMin = intval($neighborVals[2]);
							}
							if (isset($neighborVals[3])) {
								$thisFilter->distanceSystemMax = intval($neighborVals[3]);
							}
							foreach ($distCalcKeys as $dcKey) {
								if (isset($neighborVals[$dcKey])) {
									if (!isset($thisFilter->distanceDistanceCalculationSettings)) {
										$thisFilter->distanceDistanceCalculationSettings = array();
									}
									$thisFilter->distanceDistanceCalculationSettings []= $dcKey;
								}
							}
						} else {
							$neighborType = $neighborVals[0].'neighbor';
							$thisFilter->$neighborType = array();
							foreach ($filter['neighbor'][0] as $neighborKey => $neighborVal) {
								if (!is_numeric($neighborKey) || $neighborKey < 1) {
									continue;
								}
								$thisFilter->$neighborType []= $neighborVal;
							}
						}
					}
				} else {
					$thisFilter->neighbor = $this->arrayToLocationFilter($neighborVal);
				}
			}
			if (isset($filter['attributes'])) {
				if (!isset($thisFilter->attributes)) {
					$thisFilter->attributes = array();
				}
				if (count($filter['attributes']) == 1) {
					foreach ($filter['attributes'][0] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->attributes []= $attrVal;
					}
				} else {
					foreach ($filter['attributes'] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->attributes []= $attrVal[0];
					}
				}
			}
			if (isset($filter['planet'])) {
				if (!isset($thisFilter->planet)) {
					$thisFilter->planets = array();
				}
				if (count($filter['planet']) == 1) {
					foreach ($filter['planet'][0] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->planets []= $attrVal;
					}
				} else {
					foreach ($filter['planet'] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->planets []= $attrVal[0];
					}
				}
			}
			if (isset($filter['system'])) {
				if (!isset($thisFilter->system)) {
					$thisFilter->systems = array();
				}
				if (count($filter['system']) == 1) {
					foreach ($filter['system'][0] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->systems []= $attrVal;
					}
				} else {
					foreach ($filter['system'] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->systems []= $attrVal[0];
					}
				}
			}
			if (isset($filter['government'])) {
				if (!isset($thisFilter->government)) {
					$thisFilter->governments = array();
				}
				if (count($filter['government']) == 1) {
					foreach ($filter['government'][0] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->governments []= $attrVal;
					}
				} else {
					foreach ($filter['government'] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->governments []= $attrVal[0];
					}
				}
			}
			if (isset($filter['outfits'])) {
				if (!isset($thisFilter->outfit)) {
					$thisFilter->outfits = array();
				}
				if (count($filter['outfits']) == 1) {
					foreach ($filter['outfits'][0] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->outfits []= $attrVal;
					}
				} else {
					foreach ($filter['outfits'] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->outfits []= $attrVal[0];
					}
				}
			}
			if (isset($filter['category'])) {
				if (!isset($thisFilter->category)) {
					$thisFilter->categories = array();
				}
				if (count($filter['category']) == 1) {
					foreach ($filter['category'][0] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->categories []= $attrVal;
					}
				} else {
					foreach ($filter['category'] as $attrKey => $attrVal) {
						if (!is_numeric($attrKey)) {
							continue;
						}
						$thisFilter->categories []= $attrVal[0];
					}
				}
			}
			if (isset($filter['near'])) {
				$thisFilter->nearSystem = $filter['near'][0][0];
				if (isset($filter['near'][0][1])) {
					$thisFilter->nearSystemMin = intval($filter['near'][0][1]);
				}
				if (isset($filter['near'][0][2])) {
					$thisFilter->nearSystemMax = intval($filter['near'][0][1]);
				}
				foreach ($distCalcKeys as $dcKey) {
					if (isset($filter['near'][$dcKey])) {
						if (!isset($thisFilter->nearDistanceCalculationSettings)) {
							$thisFilter->nearDistanceCalculationSettings = array();
						}
						$thisFilter->nearDistanceCalculationSettings []= $dcKey;
					}
				}
			}
			if (isset($filter['distance'])) {
				$thisFilter->distanceSystem = $filter['distance'][0][0];
				if (isset($filter['distance'][0][1])) {
					$thisFilter->distanceSystemMin = intval($filter['distance'][0][1]);
				}
				if (isset($filter['distance'][0][2])) {
					$thisFilter->distanceSystemMax = intval($filter['distance'][0][1]);
				}
				foreach ($distCalcKeys as $dcKey) {
					if (isset($filter['distance'][$dcKey])) {
						if (!isset($thisFilter->distanceDistanceCalculationSettings)) {
							$thisFilter->distanceDistanceCalculationSettings = array();
						}
						$thisFilter->distanceDistanceCalculationSettings []= $dcKey;
					}
				}
			}
		}
		
		return $thisFilter;
	}
	
	public function arrayToSystemObject($system, $parent, $array) {
		foreach ($array as $oKey => $object) {
			$thisObject = new SystemObject();
			if ($oKey == '_type') {
				continue;
			} else if ($oKey == '_name') {
				//$this->logger->info(' ---- System object named '.$elVal.' is weird -----');
				$thisObject->name = $elVal;
				$system->inhabited = true;
				continue;
			}
			//$this->logger->info('Handling system object ['.print_r($object, true).']');
			//$thisObject = array();
			//$this->logger->info('Object sprite is ['.print_r($object['sprite'], true).']');
			if (isset($object['sprite'][0]['_name'])) {
				$thisObject->sprite = $object['sprite'][0]['_name'];
				$thisObject->spriteScale = floatval($object['sprite'][0]['scale'][0][0]);
			} else {
				$thisObject->sprite = $object['sprite'][0][0];
			}
			$thisName = $thisObject->sprite;
			if (isset($object['distance'])) {
				$thisObject->distance = floatval($object['distance'][0][0]);
			}
			if (isset($object['period'])) {
				$thisObject->period = floatval($object['period'][0][0]);
			}
			if (isset($object['offset'])) {
				$thisObject->offset = floatval($object['offset'][0][0]);
			}
			if (isset($object['_name'])) {
				$thisObject->name = $object['_name'];
				$system->inhabited = true;
				//$this->logger->info(' -* Because it has a planet named '.$thisObject->name.', system '.$system->name.' is inhabited');
				$thisName = $thisObject->name;
			}
			if (isset($object['object'])) {
				//$this->logger->info(' - Processing child of '.$thisName.'...');
				$this->arrayToSystemObject($system, $thisObject, $object['object']);
			}
			if ($parent) {
				$parentName = '';
				if ($parent->name) {
					$parentName = $parent->name;
				} else {
					$parentName = $parent->sprite;
				}
				//$this->logger->info(' - Adding system object ['.print_r($thisObject, true).'] to parent '.$parentName);
				$parent->getChildren() []= $thisObject;
			} else {
				$system->objects []= $thisObject;
				//$this->logger->info(' - Adding system object ['.print_r($thisObject, true).'] to system '.$system->name.' (has '.count($system->objects).' object(s) now)');
			}
		}
	}
	
	public function angleBetween($fromPoint, $toPoint) {
		
		$rise = $toPoint->y - $fromPoint->y;
		$run = $toPoint->x - $fromPoint->x;
		if ($run == 0) {
			if ($rise > 0) {
				return 90;
			} else {
				return 270;
			}
		}
		
		$slope = $rise / $run;
		
		$angle = atan($slope);
		
		$angleDeg = rad2deg($angle);
		if ($rise < 0 && $run > 0) {
			$angleDeg += 360;
		} else if ($rise < 0 || $run < 0) {
			$angleDeg += 180;
		}
		
		return $angleDeg;
	}
	
	function processDataFile($fullPath) {
		$data = file_get_contents($fullPath);
		$lines = explode("\n", $data);
		$elements = array();
		$elements['_filename'] = substr($fullPath, strlen($this->dataBasePath));
		$sectionLines = array();
		for ($i=0; $i<count($lines); $i++) {
			$line = $lines[$i];
			$startSection = false;
			if (strlen($line) > 0 && trim($line) != '' && $line[0] != '	') { // If the line isn't blank, and doesn't start with a tab, then it's the start of a section
				//$this->logger->info('Starting new section for line ['.$line.']');
				if ($line[0] == '#') { // It's a comment; ignore it
					continue;
				}
				$startSection = true;
			}
			if (count($sectionLines) > 0 && ($i >= count($lines) - 1 || $startSection)) { // We process what we've collected as a single section if a new section starts, or we hit the end
				list($sectionType, $section, $linesUsed) = $this->processSection($sectionLines);
				$section['_type'] = $sectionType;
				$elements []= $section;
				$sectionLines = array();
				if ($linesUsed > 0) {
					//$this->logger->info('Adding '.$linesUsed.' to $i');
					$i += $linesUsed;
				}
			}
			if (trim($line) == '' || $line[0] == '#') {
				continue;
			}
			//$this->logger->info('Adding line ['.$line.'] to active section');
			$sectionLines []= $line;
		}
		
		return $elements;
	}
	
	function processSectionTop($line) {
		$thisElement = array();
		list($depth, $tokens) = $this->tokenize($line);
		if (count($tokens) == 0) {
			return [null, null];
		}
		if (count($tokens) > 1) {
			$thisElement['_name'] = $tokens[1];
			if (count($tokens) > 2) {
				$thisElement['_params'] = array();
				for ($j=2; $j<count($tokens); $j++) {
					$thisElement['_params'] []= $tokens[$j];
				}
			}
		}
		// if ($depth == 0 && $tokens[0] == 'system' && $thisElement['_name'] == 'Sadalsuud') {
		// 	$this->debug = true;
		// } else if ($tokens[0] == 'system') {
		// 	if ($this->debug) {
		// 		$this->logger->debug('** Disabling debug because I found a system named ['.$thisElement['_name'].']');
		// 	}
		// 	$this->debug = false;
		// }
		$thisElement['_depth'] = $depth;
		return [$tokens[0], $thisElement];
	}
	
	function processLeaf($line) {
		$thisLeaf = array();
		list($depth, $tokens) = $this->tokenize($line);
		if (count($tokens) == 0) {
			return [null, null];
		}
		for ($j=1; $j<count($tokens); $j++) {
			$thisLeaf []= $tokens[$j];
		}
		if ($this->debug) {
			$this->logger->debug('Leaf ('.$depth.') '.$tokens[0].' ['.print_r($thisLeaf, true).']');
		}
		$thisLeaf['_depth'] = $depth;
		return [$tokens[0], $thisLeaf];
	}
	
	function processSection($lines) {
		if ($this->debug) {
			$this->logger->info('Processing section with '.count($lines).' lines...');
		}
		$thisElement = array();
		$prevDepth = -1;
		$curDepth = 0;
		$nextDepth = -1;
		$branchAtDepth = array();
		$sectionType = 'notype';
		for ($i=0; $i<count($lines); $i++) {
			$line = $lines[$i];
			//$this->logger->info('Line '.$i.' is ['.$line.']');
			if (count($thisElement) == 0 && $sectionType == 'notype') {
				list($sectionType, $thisElement) = $this->processSectionTop($line);
				if (!$sectionType) {
					$thisElement = array();
					continue;
				}
				if ($this->debug) {
					$this->logger->info('Section has type '.$sectionType);
				}
				if ($i < count($lines) - 1) {
					for ($k=0; $k<strlen($lines[$i+1]); $k++) {
						if ($lines[$i+1][$k] != '	') {
							$nextDepth = $k;
							break;
						}
					}
					continue;
				} else {
					if ($this->debug) {
						$this->logger->info('Just a one-liner');
					}
					return [$sectionType, $thisElement, 0];
				}
			} else {
				$curDepth = $nextDepth;
			
				if ($i < count($lines) - 1) {
					for ($k=0; $k<strlen($lines[$i+1]); $k++) {
						if ($lines[$i+1][$k] != '	') {
							$nextDepth = $k;
							break;
						}
					}
				}
			}
			if ($this->debug) {
				$this->logger->info('Starting with depth '.$curDepth.', next '.$nextDepth);
			}
			if ($nextDepth <= $curDepth) {
				list($leafType, $leaf) = $this->processLeaf($line);
				if ($leafType) {
					if (isset($thisElement[$leafType])) {
						if (is_array($thisElement[$leafType])) {
							$thisElement[$leafType] []= $leaf;
						} else {
							$oldLeaf = $thisElement[$leafType];
							$thisElement[$leafType] = [$oldLeaf, $leaf, '_type'=>$leafType];
						}
					} else {
						$thisElement[$leafType] = [$leaf, '_type'=>$leafType];
					}
				}
				//$this->logger->info('Leaf of type '.$leafType);
				if ($nextDepth < $curDepth) { // This should include the case where $nextDepth is -1, which should be a blank line ending the whole element
					if ($this->debug) {
						$this->logger->info('Returning this to parent');
					}
					return [$sectionType, $thisElement, $i];
				}
			} else {
				if ($this->debug) {
					$this->logger->info('Going a level deeper');
				}
				$subLines = array_slice($lines, $i);
				list($subSectionType, $subSection, $linesUsed) = $this->processSection($subLines);
				if ($linesUsed > 0) {
					if ($this->debug) {
						$this->logger->info(' - Adding '.$linesUsed.' to $i');
					}
					$i += $linesUsed;
				} else if ($linesUsed < 0) {
					if ($this->debug) {
						$this->logger->info(' - That was all');
					}
					$i = count($lines);
				}
				if (isset($thisElement[$subSectionType])) {
					if (is_array($thisElement[$subSectionType])) {
						$thisElement[$subSectionType] []= $subSection;
					} else {
						$oldSection = $thisElement[$subSectionType];
						$thisElement[$subSectionType] = [$oldSection, $subSection, '_type'=>$subSectionType];
					}
				} else {
					$thisElement[$subSectionType] = [$subSection, '_type'=>$subSectionType];
				}
				if ($i < count($lines) - 1) {
					for ($k=0; $k<strlen($lines[$i+1]); $k++) {
						if ($lines[$i+1][$k] != '	') {
							$nextDepth = $k;
							break;
						}
					}
				}
				if ($this->debug) {
					$this->logger->debug('Cur element ('.$sectionType.') depth '.$thisElement['_depth'].'; curDepth: '.$curDepth.'; nextDepth: '.$nextDepth);
				}
				if ($nextDepth < $curDepth) {
					//$this->logger->info('Hit a cliff, returning more');
					return [$sectionType, $thisElement, $i];
				}
			}
		}
		
		return [$sectionType, $thisElement, -1];
	}
	
	function tokenize($line) {
		$tokens = array();
		$curTokenId = -1;
		$indexInToken = 0;
		$curDelimiter = null;
		$finishedToken = true;
		$validDelimiters = ['`','"'];
		$whitespace = [' ','	', "\n"];
		$depth = 0;
		for ($i=0; $i<strlen($line); $i++) {
			if ($curTokenId == -1 && $line[$i] == '	') {
				$depth++;
				continue;
			}
			if ($line[$i] == '#') { // It's a comment; we're done
				return [$depth, $tokens];
			}
			if (!$curDelimiter) {
				//error_log(' - - + No delimiter');
				if (in_array($line[$i],$validDelimiters)) {
					//error_log(' - - + Found delimiter ['.$line[$i].']');
					$curDelimiter = $line[$i];
					$curTokenId++;
					$tokens[$curTokenId] = '';
					$finishedToken = false;
					$indexInToken = 0;
					continue;
				}
			} else {
				if (in_array($line[$i], $validDelimiters)) {
					$finishedToken = true;
					$curDelimiter = null;
					continue;
				}
			}
			if (!$curDelimiter && in_array($line[$i], $whitespace)) {
				if (!$finishedToken) {
					$finishedToken = true;
				}
				continue;
			} else if ($finishedToken && !in_array($line[$i], $whitespace)) {
				$finishedToken = false;
				$curTokenId++;
				$tokens[$curTokenId] = '';
				$indexInToken = 0;
			}
			$tokens[$curTokenId] .= $line[$i];
		}
		return [$depth, $tokens];
	}
	
	function floatToIntColor($colorArray) {
		$redF = floatval($colorArray[0]);
		$greenF = floatval($colorArray[1]);
		$blueF = floatval($colorArray[2]);
		$redI = round(255 * $redF);
		$greenI = round(255 * $greenF);
		$blueI = round(255 * $blueF);
		return [$redI,$greenI,$blueI];
	}
	
}