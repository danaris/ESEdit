<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

use App\Service\ESDataService;

class ShipController extends AbstractController {

    private string $spriteBasePath;

	public function __construct(protected LoggerInterface $logger,
								protected ESDataService $dataService,
								protected string $projectDir) {
		$this->spriteBasePath = $this->projectDir.'/public/sprites/';
	}

	#[Route(path: '/ships', name: 'Ships')]
	public function ships(Request $request): Response {
		$esData = $this->dataService->data();

		$data = array();
		$data['shipNames'] = $esData['shipNames'];

		sort($data['shipNames']);

		return $this->render('ships.html.twig', $data);
	}

	#[Route(path: '/ship/{shipName}', name: 'ShipByName')]
	public function shipByName(Request $request, string $shipName): Response {
		$shipData = $this->dataService->shipData($shipName);

		$data = array();
		$data['ship'] = $shipData;
        $data['shipName'] = $shipName;

		return $this->render('ship.html.twig', $data);
	}

	#[Route(path: '/shipRaw/{shipName}.json', name: 'ShipJSON')]
	public function shipJSON(Request $request, string $shipName): Response {
		$shipData = $this->dataService->shipData($shipName);

		return $this->json($shipData);
	}
}
