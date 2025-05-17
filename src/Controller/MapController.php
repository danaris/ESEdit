<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

use App\Service\ESDataService;

class MapController extends AbstractController {

	private string $spriteBasePath;

	public function __construct(protected LoggerInterface $logger,
								protected ESDataService $dataService,
								protected string $projectDir) {
		$this->spriteBasePath = $this->projectDir.'/public/sprites/';
	}

	#[Route(path: '/map', name: 'GalaxyMap')]
	public function index(Request $request): Response {
		//$esData = $this->dataService->data();

		$data = array();
		$data['mapLayers'] = [
			['name'=>'map','displayName'=>'Galaxy Images', 'controllable'=>0, 'iconUrl'=>'', 'extraInfo'=>'']
		];

		return $this->render('map.html.twig', $data);
	}

	#[Route(path: '/s-assets/sRegister.js', name: 'SymfonyAssetRegister')]
	public function registerAssets(Request $request): Response {
		$response = $this->render('sRegister.js.twig');
		$response->headers->set('Content-Type', 'text/javascript');

		return $response;
	}

	#[Route(path: '/map/galaxyData.json', name: 'GalaxyMapData')]
	public function galaxyData(Request $request): Response {
		$esData = $this->dataService->data();

		$galaxyData = array();
		$governmentColors = array();
		foreach ($esData['governments'] as $govName => $gov) {
			$red = $gov['color']['red'];
			$green = $gov['color']['green'];
			$blue = $gov['color']['blue'];
			$alpha = $gov['color']['alpha'];
			$color = $this->dataService->colorToCSS($gov['color']); //['red' => round(255 * $red), 'green' => round(255 * $green), 'blue' => round(255 * $blue), 'alpha' => $alpha];
			$governmentColors[$govName] = $color;
		}

		$milkyWayWidth = 0;
		$milkyWayHeight = 0;
		foreach ($esData['galaxies'] as $galaxyName => $galaxy) {
			$spriteName = $galaxy['sprite'];
			if (!$spriteName) {
				continue;
			}
			$sprite = $esData['sprites'][$spriteName];
			$path = $this->dataService->spriteToBasePath($sprite);
			$spriteInfo = getimagesize($this->spriteBasePath.$path);
			$myWidth = $spriteInfo[0];
			$myHeight = $spriteInfo[1];
			if ($galaxyName == 'Milky Way') {
				$milkyWayWidth = $myWidth;
				$milkyWayHeight = $myHeight;
			}
			$xPos = ($milkyWayWidth / 2) + $galaxy['position']['x'] - ($myWidth / 2);
			$yPos = ($milkyWayHeight / 2) + $galaxy['position']['y'] - ($myHeight / 2);
			$mapImage = [
				'type'=>'image',
				'id'=>$galaxyName,
				'layer'=>'galaxy',
				'width'=>$spriteInfo[0],
				'height'=>$spriteInfo[1],
				'x'=>$xPos,
				'y'=>$yPos,
				'value'=>'/sprites/'.$path
			];
			$galaxyData []= $mapImage;
		}

		$foundLinks = [];

		foreach ($esData['systems'] as $systemName => $system) {
			$xPos = ($milkyWayWidth / 2) + $system['position']['x'] - 2;
			$yPos = ($milkyWayHeight / 2) + $system['position']['y'] - 2;
			if ($system['government']) {
				$govColor = $governmentColors[$system['government']];
			} else {
				$govColor = $governmentColors['Uninhabited'];
			}
			$systemData = [
				'type'=>'system',
				'name'=>$systemName,
				'layer'=>'systems',
				'x'=>$xPos,
				'y'=>$yPos,
				'color' => $govColor,
				'systemData' => $system
			];
			$systemNameData = [
				'id'=>str_replace([' ',"'"], ['_','-'], $systemName),
				'type'=>'systemName',
				'layer'=>'systems',
				'fontSize'=>'14px',
				'x'=>$xPos,
				'y'=>$yPos,
				'value'=>$systemName
			];

			if (!isset($foundLinks[$systemName])) {
				$foundLinks[$systemName] = [];
			}
			foreach ($system['links'] as $otherSystemName) {
				if (!in_array($otherSystemName, $foundLinks[$systemName])) {
					$otherSystem = $esData['systems'][$otherSystemName];
					$xPos2 = ($milkyWayWidth / 2) + $otherSystem['position']['x'] - 2;
					$yPos2 = ($milkyWayHeight / 2) + $otherSystem['position']['y'] - 2;
					$systemLink = [
						'id' => 'link_'.$systemName.'-'.$otherSystemName,
						'type' => 'systemLink',
						'x1' => $xPos,
						'y1' => $yPos,
						'x2' => $xPos2,
						'y2' => $yPos2,
						'fromSystem' => $systemName,
						'toSystem' => $otherSystemName
					];

					$foundLinks[$systemName] []= $otherSystemName;
					if (!isset($foundLinks[$otherSystemName])) {
						$foundLinks[$otherSystemName] = [];
					}
					$foundLinks[$otherSystemName] []= $systemName;

					$galaxyData []= $systemLink;;
				}
			}

			foreach ($esData['wormholes'] as $wormholeName => $wormhole) {
				$links = $wormhole['links'];
				$color = $this->dataService->colorToCSS($wormhole['linkColor']);
				$i = 0;
				foreach ($links as $linkFrom => $linkTo) {
					$fromSystem = $esData['systems'][$linkFrom];
					$toSytem = $esData['systems'][$linkTo];
					$xPos = ($milkyWayWidth / 2) + $fromSystem['position']['x'] - 2;
					$yPos = ($milkyWayHeight / 2) + $fromSystem['position']['y'] - 2;
					$xPos2 = ($milkyWayWidth / 2) + $toSytem['position']['x'] - 2;
					$yPos2 = ($milkyWayHeight / 2) + $toSytem['position']['y'] - 2;

					$wormholeData = [
						'id' => $wormholeName.$i,
						'layer' => 'map',
						'type' => 'wormhole',
						'name' => $wormhole['displayName'],
						'color' => $color,
						'x1' => $xPos,
						'y1' => $yPos,
						'x2' => $xPos2,
						'y2' => $yPos2
					];

					$galaxyData []= $wormholeData;
					$i++;
				}

			}

			$galaxyData []= $systemData;
			$galaxyData []= $systemNameData;
		}

		$response = $this->json($galaxyData);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');

		// Cache the data file for an hour
		$expires = (60*60);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);

		return $response;
	}

	#[Route(path: '/map/planetData/{planetName}.json', name: 'MapPlanetData')]
	public function planetData(Request $request, string $planetName): Response {
		$esData = $this->dataService->data();

		$planet = $esData['planets'][$planetName];

		if (!$planetName || !$planet) {
			return $this->json(['error'=>'No planet named "'.$planetName.'" found.']);
		}

		return $this->json($planet);
	}

}