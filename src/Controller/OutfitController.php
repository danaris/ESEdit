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
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Psr\Log\LoggerInterface;

use App\Service\ESDataService;

class OutfitController extends AbstractController {

	private string $spriteBasePath;

	public function __construct(protected LoggerInterface $logger,
								protected ESDataService $dataService,
								protected string $projectDir) {
		$this->spriteBasePath = $this->projectDir.'/public/sprites/';
	}

	#[Route(path: '/outfits', name: 'Outfits')]
	public function outfits(Request $request): Response {
		$esData = $this->dataService->data();

		$data = array();
		$data['outfitNames'] = $esData['outfitNames'];

		sort($data['outfitNames']);

		if ($request->query->has('format') && $request->query->get('format') == 'json') {
			return $this->json($data['outfitNames']);
		} else {
			return $this->render('outfits.html.twig', $data);
		}
	}

	#[Route(path: '/outfit/{outfitName}', name: 'OutfitByName', requirements: ['outfitName'=>'.+'])]
	public function outfitByName(Request $request, string $outfitName): Response {
		$outfitData = $this->dataService->outfitData($outfitName);

		$data = array();
		$data['outfit'] = $outfitData;

		return $this->render('outfit.html.twig', $data);
	}

	#[Route(path: '/outfitRaw/{outfitName}.json', name: 'OutfitJSON', requirements: ['outfitName'=>'.+'])]
	public function outfitJSON(Request $request, string $outfitName): Response {
		$outfitData = $this->dataService->outfitData($outfitName);

		return $this->json($outfitData);
	}

	#[Route(path: '/outfitCompare', name: 'OutfitCompare')]
	public function outfitCompare(Request $request): Response {
		$outfitNames = $this->dataService->data()['outfitNames'];
		$outfitAttributes = $this->dataService->outfitAttributes();

		$data = [];
		$data['outfitNames'] = $outfitNames;
		$data['outfitAttributes'] = $outfitAttributes;

		return $this->render('outfitCompare.html.twig', $data);
	}

	#[Route(path: '/outfitAttrCompare', name: 'OutfitAttributeCompare')]
	public function outfitAttrCompare(Request $request, ChartBuilderInterface $chartBuilder): Response {
		$xAttr = $request->request->get('xAttr');
		$yAttr = $request->request->get('yAttr');

		$outfitNames = $this->dataService->data()['outfitNames'];

		$chart = $chartBuilder->createChart(Chart::TYPE_SCATTER);
		$chartData = [];
		$minX = 99999999;
		$maxX = -99999999;
		$minY = 99999999;
		$maxY = -99999999;
		foreach ($outfitNames as $name) {
			$outfit = $this->dataService->outfitData($name);
			$x = null;
			if (isset($outfit[$xAttr])) {
				$x = $outfit[$xAttr];
			} else if (isset($outfit['attributes']['_store'][$xAttr])) {
				$x = $outfit['attributes']['_store'][$xAttr];
			} else {
				continue;
			}
			if ($x < $minX) {
				$minX = $x;
			}
			if ($x > $maxX) {
				$maxX = $x;
			}
			$y = null;
			if (isset($outfit[$yAttr])) {
				$y = $outfit[$yAttr];
			} else if (isset($outfit['attributes']['_store'][$yAttr])) {
				$y = $outfit['attributes']['_store'][$yAttr];
			} else {
				continue;
			}
			if ($y < $minY) {
				$minY = $y;
			}
			if ($y > $maxY) {
				$maxY = $y;
			}
			$chartData []= ['x'=>$x, 'y'=>$y];
		}

		$chart->setData(['datasets' => [
			'label'=>$xAttr.' vs '.$yAttr,
			'data'=>$chartData,
			'backgroundColor'=>'rgb(32, 64, 192)'
		]]);

		$chart->setOptions([
			'scales'=>[
				'y'=>['suggestedMin'=>$minY, 'suggestedMax'=>$maxY],
				'x'=>['suggestedMin'=>$minX, 'suggestedMax'=>$maxX]
			]
		]);

		$data = [];
		$data['chart'] = $chart;

		return $this->render('outfitCompareAttr.html.twig', $data);
	}

}