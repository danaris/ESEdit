<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

use App\Service\SkyService;

class SkyController extends AbstractController {
	
	public function __construct(protected LoggerInterface $logger,
								protected SkyService $skyService) {
	}
	
	private function loadSkyData() {
		
		$this->skyService->loadImageDB();
		
		$this->skyService->importData();
		
		$this->skyService->saveConfig();
		
	}
	
	#[Route('/sky', name: 'SkyEditHome')]
	public function index(): Response {
		$this->loadSkyData();
		$data = array();
		$data['data'] = $this->skyService->getData();
		
		return $this->render('sky/index.html.twig', $data);
	}
	
	#[Route('/skyImage/{imagePath}', name: 'SkyImagePath', requirements: ['imagePath' => '.+'])]
	public function image(Request $request, $imagePath): Response {
		$fullPath = $this->skyService->imageBasePath.$imagePath;
		return new BinaryFileResponse($fullPath);
	}
	
	#[Route('/sky/data/{dataType}', name: 'SkyDataView')]
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
	
    #[Route('/sky/galaxy', name: 'SkyEditGalaxy')]
    public function galaxy(): Response {
		$this->loadSkyData();
		
		$data = array();
		$data['data'] = print_r($data, true);
		$data['images'] = $this->skyService->getImages();
		$data['errors'] = $this->skyService->getErrors();
		$data = array_merge($data, $this->skyService->getData());
		
		foreach ($data['systems'] as $systemName => $system) {
			foreach ($system->objects as $object) {
				$planet = null;
				if ($object->name) {
					$planet = $data['planets'][$object->name];
				}
				if ($planet && $planet->wormhole) {
					if ($system->hidden || $system->inaccessible) {
						$this->logger->info('Found wormhole "'.$planet->wormhole.'", but in hidden system "'.$systemName.'"');
						continue 2;
					}
					if (isset($data['wormholes'][$planet->wormhole])) {
						$data['wormholes'][$planet->wormhole]->active = true;
						$this->logger->info('Wormhole "'.$planet->wormhole.'" activated by system "'.$systemName.'"');
					} else {
						$this->logger->info('Could not find wormhole named ['.$planet->wormhole.']');
					}
				}
			}
		}
		
		$data['testSystem'] = $data['systems']['Heia Due'];
		
		//error_log('Known colors: '.print_r($this->skyService->getData()['colors'],true));
		
        return $this->render('sky/galaxy.html.twig', $data);
    }
}
