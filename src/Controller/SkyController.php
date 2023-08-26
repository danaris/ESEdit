<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Service\SkyService;

use App\Entity\DataNode;
use App\Entity\DataFile;
use App\Entity\DataWriter;
use App\Entity\Sky\Mission;
use App\Entity\Sky\GameData;
use App\Entity\Sky\Government;
use App\Entity\Sky\System;
use App\Entity\Sky\Galaxy;
use App\Entity\Sky\Wormhole;
use App\Entity\Sky\Planet;
use App\Entity\Sky\Color;
use App\Entity\Sky\Ship;
use App\Entity\Sky\Sprite;

class SkyController extends AbstractController {
	
	public function __construct(protected LoggerInterface $logger,
								protected SkyService $skyService,
								protected EntityManagerInterface $em) {
		$skyService->loadUniverseFromFiles();
	}
	
	private function loadSkyData() {
		
		$this->skyService->loadImageDB();
		
		$this->skyService->importData();
		
		$this->skyService->saveConfig();
		
	}
	
	#[Route('/sky', name: 'SkyEditHome')]
	public function index(): Response {
		$data = [];
		
		return $this->render('sky/index.html.twig', $data);
	}
	
	// #[Route('/skyImage/{imagePath}', name: 'SkyImagePath', requirements: ['imagePath' => '.+'])]
	// public function image(Request $request, $imagePath): Response {
	// 	if (substr($imagePath, 0, 6) == '/Users') {
	// 		$fullPath = $imagePath;
	// 	} else {
	// 		$fullPath = $this->skyService->imageBasePath.$imagePath;
	// 	}
	// 	return new BinaryFileResponse($fullPath);
	// }
	#[Route('/skyImage/{spriteId}/{frameId}', name: 'SkyImagePath', requirements: ['spriteId' => '\d+'])]
	public function image(Request $request, int $spriteId, int $frameId = 0): Response {
		$Sprite = $this->em->getRepository(Sprite::class)->find($spriteId);
		$spritePath = $Sprite->getPath($frameId);
		if ($spritePath) {
			$response = new BinaryFileResponse($spritePath);
			$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
			$type = substr($Sprite->getPath(), -3);
			$response->headers->set('Content-Type', 'image/'.$type);
			
			// Cache the image files each for a week
			$expires = (60*60*24*7);
			$response->headers->set('Cache-Control', 'public, max-age='.$expires);
			
			return $response;
		} else {
			throw new NotFoundHttpException('Frame '.$frameId.' of sprite ID '.$spriteId.' was not found.');
		}
	}
	
	#[Route('/sky/dataview/{dataType}', name: 'SkyDataView')]
	public function data(Request $request, $dataType): Response {
		$this->loadSkyData();
		$skyData = $this->skyService->getData();
		$data = array();
		$dataToShow = $skyData[$dataType];
		$data['dataType'] = $dataType;
		$data['dataToShow'] = $dataToShow;
		
		return $this->render('sky/data.html.twig', $data);
	}
	
	#[Route('/sky/action/addPlugin', name: 'SkyAddPlugin')]
	public function addPlugin(Request $request): Response {
		$pluginName = $request->request->get('pluginName');
		$pluginPath = $request->request->get('pluginPath');
		$this->skyService->addPlugin($pluginName, $pluginPath);
		$this->loadSkyData();
		
		return $this->json(['success'=>true]);
	}
	
	#[Route('/sky/action/removePlugin', name: 'SkyRemovePlugin')]
	public function removePlugin(Request $request): Response {
		$pluginName = $request->request->get('pluginName');
		$this->skyService->removePlugin($pluginName);
		$this->loadSkyData();
		
		return $this->json(['success'=>true]);
	}
	
	#[Route('/sky/action/clearDataCache', name: 'SkyClearDataCache')]
	public function clearDataCache(Request $request): Response {
		$this->skyService->clearDataCache();
		
		return $this->json(['success'=>true]);
	}
	
	#[Route('/sky/action/clearImageCache', name: 'SkyClearImageCache')]
	public function clearImageCache(Request $request): Response {
		$this->skyService->clearImageCache();
		
		return $this->json(['success'=>true]);
	}
	
	#[Route('/sky/missions', name: 'SkyMissions')]
	public function missions(Request $request): Response {
		$this->loadSkyData();
		
		
	}
	
	#[Route('/sky/missionNames', name: 'SkyMissionNames')]
	public function missionNames(Request $request): Response {
		$this->loadSkyData();
		
		$missions = $this->skyService->getData()['missions'];
		
		usort($missions, function($a, $b) {
			if ($a->name > $b->name) {
				return 1;
			} else if ($a->name < $b->name) {
				return -1;
			} else {
				return 0;
			}
		});
		
		return $this->render('sky/missionNames.html.twig', ['missions'=>$missions]);
	}
	
	#[Route('/sky/data/governmentColors.css', name: 'SkyGovColorsCSS')]
	public function govColorsCSS(Request $request): Response {
		$govQ = $this->em->createQuery('Select g from App\Entity\Sky\Government g index by g.name');
		
		$data = ['governments' => $govQ->getResult()];
		
		$response = $this->render('sky/data/govColors.css.twig', $data);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'text/css');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/data/sprites.js', name: 'SkySpritesJS')]
	public function spritesJS(Request $request): Response {
		$spriteQ = $this->em->createQuery('Select s from App\Entity\Sky\Sprite s index by s.id');
		$sprites = [];
		foreach ($spriteQ->getResult() as $Sprite) {
			$sprites[$Sprite->getId()] = $Sprite->toJSON(true);
		}
		
		$response = $this->render('sky/data/sprites.js.twig', ['sprites'=>$sprites]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/data/governments.js', name: 'SkyGovernmentsJS')]
	public function governmentsJS(Request $request): Response {
		$govQ = $this->em->createQuery('Select g from App\Entity\Sky\Government g index by g.name');
		$governments = [];
		foreach ($govQ->getResult() as $Government) {
			$governments[$Government->getName()] = $Government->toJSON(true);
		}
		
		$response = $this->render('sky/data/governments.js.twig', ['governments'=>$governments]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/data/systems.js', name: 'SkySystemsJS')]
	public function systemsJS(Request $request): Response {
		$sysQ = $this->em->createQuery('Select s from App\Entity\Sky\System s index by s.name');
		$systems = [];
		foreach ($sysQ->getResult() as $System) {
			$this->logger->debug('JSONifying '.$System->getName());
			$systems[$System->getName()] = $System->toJSON(true);
		}
		
		$response = $this->render('sky/data/systems.js.twig', ['systems'=>$systems]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/data/planets.js', name: 'SkyPlanetsJS')]
	public function planetsJS(Request $request): Response {
		$planetQ = $this->em->createQuery('Select s from App\Entity\Sky\Planet s index by s.name');
		$planets = [];
		foreach ($planetQ->getResult() as $Planet) {
			if ($Planet->isWormhole()) {
				continue;
			}
			$planets[$Planet->getName()] = $Planet->toJSON(true);
		}
		
		$response = $this->render('sky/data/planets.js.twig', ['planets'=>$planets]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/data/galaxies.js', name: 'SkyGalaxiesJS')]
	public function galaxiesJS(Request $request): Response {
		$galQ = $this->em->createQuery('Select s from App\Entity\Sky\Galaxy s index by s.name');
		$galaxies = [];
		foreach ($galQ->getResult() as $Galaxy) {
			$galaxies[$Galaxy->getName()] = $Galaxy->toJSON(true);
		}
		
		$response = $this->render('sky/data/galaxies.js.twig', ['galaxies'=>$galaxies]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/data/wormholes.js', name: 'SkyWormholesJS')]
	public function wormholesJS(Request $request): Response {
		$wormholeQ = $this->em->createQuery('Select s from App\Entity\Sky\Wormhole s index by s.trueName');
		$wormholes = [];
		foreach ($wormholeQ->getResult() as $Wormhole) {
			$wormholes[$Wormhole->getTrueName()] = $Wormhole->toJSON(true);
		}
		
		$response = $this->render('sky/data/wormholes.js.twig', ['wormholes'=>$wormholes]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/data/colors.js', name: 'SkyColorsJS')]
	public function colorsJS(Request $request): Response {
		$colorQ = $this->em->createQuery('Select s from App\Entity\Sky\Color s index by s.name');
		$colors = [];
		foreach ($colorQ->getResult() as $Color) {
			$colors[$Color->name] = $Color->toJSON(true);
		}
		
		$response = $this->render('sky/data/colors.js.twig', ['colors'=>$colors]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/data/outfits.js', name: 'SkyOutfitsJS')]
	public function outfitsJS(Request $request): Response {
		$outfitQ = $this->em->createQuery('Select s from App\Entity\Sky\Outfit s index by s.trueName');
		$outfits = [];
		foreach ($outfitQ->getResult() as $Outfit) {
			$outfits[str_replace('"', '\"', $Outfit->getTrueName())] = $Outfit->toJSON(true);
		}
		
		$response = $this->render('sky/data/outfits.js.twig', ['outfits'=>$outfits]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/data/ships.js', name: 'SkyShipsJS')]
	public function shipsJS(Request $request): Response {
		$shipQ = $this->em->createQuery('Select s from App\Entity\Sky\Ship s');
		$ships = [];
		foreach ($shipQ->getResult() as $Ship) {
			$ships[$Ship->getId()] = $Ship->toJSON(true);
		}
		
		$response = $this->render('sky/data/ships.js.twig', ['ships'=>$ships]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');
		
		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);
		
		return $response;
	}
	
	#[Route('/sky/map', name: 'SkyEditMap')]
	public function map(Request $request): Response {
		$data = [];
		
		$galQ = $this->em->createQuery('Select g from App\Entity\Sky\Galaxy g index by g.name');
		$data['galaxies'] = $galQ->getResult();
		
		return $this->render('sky/map.html.twig', $data);
	}
	
	#[Route('/sky/ships', name: 'SkyEditShips')]
	public function ships(Request $request): Response {
		return $this->render('sky/ships.html.twig');
	}
	
	#[Route('/sky/ship/{shipId}', name: 'SkyEditShip')]
	public function ship(Request $request, int $shipId): Response {
		$data = [];
		$Ship = $this->em->getRepository(Ship::class)->find($shipId);
		$data['Ship'] = $Ship;
		return $this->render('sky/ship.html.twig', $data);
	}
	
	#[Route('/sky/writeShip', name: 'SkyEditWriteShip')]
	public function writeShip(Request $request): Response {
		if (!$request->request->has('ship')) {
			$this->addFlash('error','No ship specified for writing!');
			return new RedirectResponse($this->generateUrl('SkyEditHome'));
		}
		
		$Ship = new Ship();
		$Ship->setFromJSON($request->request->get('ship'));
		$stringWriter = new DataWriter('');
		$Ship->save($stringWriter);
		
		$response = new Response($stringWriter->getString());
		$response->headers->set('Content-Type','text/plain');
		
		return $response;
	}
	
    #[Route('/sky/galaxy', name: 'SkyEditGalaxy')]
    public function galaxy(): Response {
		
		$data = array();
		// $data['governments'] = GameData::Governments();
		// $data['systems'] = GameData::Systems();
		// $data['galaxies'] = GameData::Galaxies();
		// $data['wormholes'] = GameData::Wormholes();
		// $data['colors'] = GameData::Colors();
		$this->logger->debug('Getting governments');
		$govQ = $this->em->createQuery('Select g from App\Entity\Sky\Government g index by g.name');
		$data['governments'] = $govQ->getResult();
		$this->logger->debug('Getting systems');
		$sysQ = $this->em->createQuery('Select s from App\Entity\Sky\System s index by s.name');
		$data['systems'] = $sysQ->getResult();
		$this->logger->debug('Getting galaxies');
		$galQ = $this->em->createQuery('Select g from App\Entity\Sky\Galaxy g index by g.name');
		$data['galaxies'] = $galQ->getResult();
		$this->logger->debug('Getting wormholes');
		$wormQ = $this->em->createQuery('Select w from App\Entity\Sky\Wormhole w index by w.trueName');
		$data['wormholes'] = $wormQ->getResult();
		$this->logger->debug('Getting colors');
		$colQ = $this->em->createQuery('Select c from App\Entity\Sky\Color c index by c.name');
		$data['colors'] = $colQ->getResult();
		$this->logger->debug('Getting links');
		$linkQ = $this->em->createQuery('Select l from App\Entity\Sky\SystemLink l');
		$data['links'] = [];
		$this->logger->debug('Processing links');
		foreach ($linkQ->getResult() as $Link) {
			if (!isset($data['links'][$Link->getFromSystem()->getName()])) {
				$data['links'][$Link->getFromSystem()->getName()] = [];
			}
			$data['links'][$Link->getFromSystem()->getName()] []= $Link->getToSystem();
		}
		
		$data['activeWormholes'] = array();
		
		$this->logger->debug('Marking active wormholes');
		
		foreach ($data['systems'] as $System) {
			foreach ($System->getObjects() as $object) {
				if ($object->planet && $object->planet->isWormhole()) {
					$data['activeWormholes'][$object->planet->getTrueName()] = false;
					if ($System->isHidden() || $System->isInaccessible()) {
						$this->logger->info('Found wormhole "'.$object->planet->getTrueName().'", but in hidden system "'.$System->getName().'"');
						continue 2;
					}
					if ($object->planet->getWormhole()) {
						$data['activeWormholes'][$object->planet->getTrueName()] = true;
						$this->logger->info('Wormhole "'.$object->planet->getTrueName().'" activated by system "'.$System->getName().'"');
					} else {
						$this->logger->info('Could not find wormhole named ['.$object->planet->getTrueName().']');
					}
				}
			}
		}
		
		//$data['testSystem'] = $data['systems']['Heia Due'];
		
		//error_log('Known colors: '.print_r($this->skyService->getData()['colors'],true));
		
		$this->logger->debug('Rendering');
        return $this->render('sky/galaxy.html.twig', $data);
    }
	
	#[Route('/sky/system/{systemName}/{startDay}', name: 'SkyEditSystem')]
	public function system(Request $request, string $systemName, int $startDay = -1): Response {
		$this->skyService->loadUniverse();
		$this->skyService->loadImageDB();
		
		$data = array();
		$data['systemName'] = $systemName;
		if ($startDay == -1) {
			$startDay = rand(1, 365 * 4);
		}
		$data['startDay'] = $startDay;
		
		return $this->render('sky/systemFrame.html.twig', $data);
	}
	
	#[Route('/sky/planetInfo', name: 'SkyPlanetInfo')]
	public function planetInfo(Request $request): Response {
		$planetName = $request->request->get('planetName');
		if (!$planetName) {
			$planetName = $request->query->get('planetName');
		}
		$this->skyService->loadUniverse();
		$planet = GameData::Planets()[$planetName];
		if ($planet) {
			$jsonArray = $planet->toJSON(true);
		} else {
			$jsonArray = [];
		}
		return $this->json(['planet'=>$jsonArray]);
	}
	
	#[Route('/sky/loadtest', name: "LoadTest")]
	public function loadTest(Request $request): Response {
		GameData::Init();
		GameData::Objects()->load(['/Users/tcollett/Development/ThirdParty/endless-sky/'], true);
		//$file = new DataFile('/Users/tcollett/Development/ThirdParty/endless-sky/data/human/deep missions.txt');
		
		//$testMission = new Mission();
		
		$out = '';
		
		$writer = new DataWriter();
		
		$test = GameData::Missions()["Deep: Syndicate Convoy"];
		$test->save($writer, 'test');
		
		$out = $test->getName()."\n".$writer->getString();
		
		$response = new Response($out);
		$response->headers->set('Content-Type','text/plain');
		return $response;
	}
	
	#[Route('/sky/missiontest', name: "MissionTest")]
	public function missionTest(Request $request): Response {
		$this->skyService->loadUniverse();
		
		$data = array();
		$missions = GameData::Missions();
		$data['missions'] = $missions;
		
		// $prerequisites = array();
		// $reversePrerequisites = array();
		// $missionNames = array_keys($missions->getContents());
		// $missionsDone = array_map(function($missionName) {
		// 	return $missionName . ': done';
		// }, $missionNames);
		// $doneLength = strlen(': done');
		// foreach ($missions as $missionName => $mission) {
		// 	foreach ($mission->getToOffer()->getExpressions() as $toOfferExpression) {
		// 		foreach ($toOfferExpression->getLeft()->getTokens() as $leftToken) {
		// 			if (in_array($leftToken, $missionsDone) && $toOfferExpression->getOp() == '!=' && $toOfferExpression->getRight()->getTokens()[0] == '0') {
		// 				$prereqName = substr($leftToken, 0, strlen($leftToken) - $doneLength);
		// 				error_log('Mission '.$prereqName.' is a prerequisite of '.$missionName);
		// 				if (!isset($prerequisites[$missionName])) {
		// 					$prerequisites[$missionName] = array();
		// 				}
		// 				$prerequisites[$missionName] []= $missions[$prereqName];
		// 				if (!isset($reversePrerequisites[$prereqName])) {
		// 					$reversePrerequisites[$prereqName] = array();
		// 				}
		// 				$reversePrerequisites[$prereqName] []= $mission;
		// 			} else {
		// 				error_log('Got a non-prereq toOffer: '.$toOfferExpression->getLeft()->getTokens()[0].' '.$toOfferExpression->getOp().' '.$toOfferExpression->getRight()->getTokens()[0]);
		// 			}
		// 		}
		// 	}
		// }
		// 
		// $data['prerequisites'] = $prerequisites;
		// $data['revPrerequisites'] = $reversePrerequisites;
		
		return $this->render('sky/missiontest.html.twig', $data);
	}
}
