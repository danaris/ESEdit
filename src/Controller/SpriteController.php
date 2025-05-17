<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Service\ESDataService;

/** @deprecated */
class SpriteController extends AbstractController {

    public function __construct(protected ESDataService $dataService) {

    }

    #[Route('/sprite/{spriteName}', name: 'Sprite', requirements: ['spriteName'=>'.+'])]
    public function sprites(Request $request, string $spriteName): Response {
        $sprites = $this->dataService->spriteData();

        $sprite = $sprites[$spriteName];

        $mode = $request->query->has('hiDPI') && $request->query->get('hiDPI') == 'true' ? 'hiDPI' : 'standard';
        $frame = $request->query->has('frame') ? intval($request->query->get('frame')) : 0;
        $swizzleMask = $request->query->has('swizzleMask') ? $request->query->get('swizzleMask') == 'true' : false;

        $rawPath = '';
        $rawPath = $sprite['paths'][$mode][$frame];
        $path = str_replace($this->dataService->pathsSearch, $this->dataService->pathsReplace, $rawPath);

        if ($swizzleMask) {
            $dirname = pathinfo($path, PATHINFO_DIRNAME);
            $filename = pathinfo($path, PATHINFO_FILENAME);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $path = $dirname.'/'.$filename.'@sw.'.$extension;
        }
        
        return new BinaryFileResponse($path);
    }

    #[Route(path: '/data/{dataType}.json', name: 'DataJSON')]
	public function dataJSON(Request $request, string $dataType): Response {
		$filename = $_ENV['ES_DATA_ROOT'].'/'.$dataType.'.json';
        $jsonDataStr = file_get_contents($filename);
        $jsonData = json_decode($jsonDataStr);

        return new JsonResponse($jsonData);
	}
}
