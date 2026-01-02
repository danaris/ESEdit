<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use ESLib\Color;
use ESLib\Files;
use ESLib\GameData;
use ESLib\Government;
use ESLib\System;

class SkyExtController extends AbstractController {

	public function __construct() {
		Files::init('eslib', '-r', '/Users/tcollett/Development/ESLib/endless-sky/', '-c', '/Users/tcollett/Development/ESLib/config/');
	}

	function loadData() {
		GameData::beginLoad(false, true, false);

		GameData::spriteTest();
		}

	#[Route("/mapTest", name: "MapTest")]
	public function mapTest(Request $request): Response {
		$this->loadData();
		$data = [];
		$data['systems'] = [];
		foreach (GameData::allSystems() as $systemName => $System) {
			$data['systems'][$systemName] = $System;
		}

		$data['governments'] = [];
		foreach (GameData::allGovernments() as $governmentName => $Government) {
			error_log('Got government: ['.$Government.']');
			$data['governments'][$governmentName] = $Government;
		}

		$data['galaxies'] = [];
		foreach (GameData::allGalaxies() as $galaxyName => $Galaxy) {
			$data['galaxies'][$galaxyName] = $Galaxy;
		}

		return $this->render('galaxy.html.twig', $data);
	}

	#[Route('/map', name: 'MapWithLibrary')]
	public function map(Request $request): Response {
		return $this->render('newGalaxy.html.twig');
	}

	#[Route('/data/galaxies.js', name: 'SkyExtGalaxiesJS')]
	public function galaxiesJS(Request $request): Response {
		$this->loadData();
		$galaxies = [];
		foreach (GameData::allGalaxies() as $galaxyName => $Galaxy) {
			$jsonArray = [];
			error_log('Galaxy '.$galaxyName.', adding name');
			$jsonArray['name'] = $galaxyName;
			error_log(' - Adding position');
			$jsonArray['position'] = $Galaxy->getPosition();
			error_log('- Adding sprite');
			$jsonArray['sprite'] = $Galaxy->getSprite()?->getName();
			error_log('- Adding to array');
			$galaxies[$galaxyName] = $jsonArray;
		}

		error_log('Rendering');

		$response = $this->render('sky/data/galaxies.js.twig', ['galaxies'=>$galaxies]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');

		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);

		error_log('Returning response');
		return $response;
	}

	#[Route('/data/sprites.js', name: 'SkyExtSpritesJS')]
	public function spritesJS(Request $request): Response {
		$this->loadData();

		$sprites = [];
		$allSprites = GameData::allSprites();
		error_log('Got all sprites');
		foreach ($allSprites as $spriteName => $Sprite) {
			error_log('Index name is ['.$spriteName.']; sprite name is ['.$Sprite->getName().']');
			$sprite = [];
			$sprite['name'] = $spriteName;
			$sprite['width'] = $Sprite->getWidth();
			$sprite['height'] = $Sprite->getHeight();
			$sprite['frames'] = $Sprite->getFrames();
			$sprite['center'] = $Sprite->getCenter();
			$sprite['paths'] = [];
			for ($i=0; $i<$sprite['frames']; $i++) {
				$sprite['paths'] []= $Sprite->getPath($i);
			}
			$sprites[$spriteName] = $sprite;
		}

		$response = $this->render('sky/data/sprites.ext.js.twig', ['sprites'=>$sprites]);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'application/json');

		// Cache the data files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);

		return $response;
	}

}