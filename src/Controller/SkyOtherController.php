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

use ESLib\Files;
use ESLib\GameData;
use ESLib\Plugin;

class SkyOtherController extends AbstractController {

	protected $spritePath = '/Users/tcollett/Development/ESLib/endless-sky/';

	#[Route('/map', name: 'SkyExtMap')]
	public function map(Request $request): Response {
		return $this->render('ext-map.html.twig');
	}

	// #[Route('/sprites/{path}', name: 'SpritePath', requirements: ['path' => '.+'])]
	// public function image(Request $request, string $path): Response {
	// 	$response = new BinaryFileResponse($this->spritePath . $path);
	// 	$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
	// 	$type = substr($path, -3);
	// 	$response->headers->set('Content-Type', 'image/'.$type);

	// 	// Cache the image files each for a week
	// 	$expires = (60*60*24*7);
	// 	$response->headers->set('Cache-Control', 'public, max-age='.$expires);

	// 	return $response;
	// }

	#[Route('/plugins', name: 'Plugins')]
	public function plugins(Request $request): Response {
		Files::init('eslib', '-r', '/Users/tcollett/Development/ESLib/endless-sky/', '-c', '/Users/tcollett/Development/ESLib/config/');
		GameData::loadSources();

		$data = [];
		$data['plugins'] = GameData::allPlugins();

		return $this->render('plugin/list.html.twig', $data);
	}
}