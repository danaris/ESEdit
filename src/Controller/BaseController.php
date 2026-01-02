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

class BaseController extends AbstractController {

	#[Route(path: '/conversation', name: 'BasicConversation')]
	public function BasicConversation(Request $request): Response {
		return $this->render('basicConversation.html.twig');
	}

	#[Route(path: '/cGalaxy', name: 'CanvasGalaxy')]
	public function cGalaxy(Request $request): Response {
		return $this->render('cGalaxy.html.twig');
	}

	#[Route(path: '/es/parse', name: 'ESParse')]
	public function esParse(Request $request): Response {
		return $this->render('es/parse.html.twig');
	}

	#[Route(path: '/es/sprites', name: 'ESLoadSprites')]
	public function esSprites(Request $request): Response {
		return $this->render('es/sprites.html.twig');
	}

	#[Route(path: '/es/data/{filePath}', name: 'ESDataPath', requirements: ['filePath'=>'.+'])]
	public function esDataPath(Request $request, string $filePath): Response {
		$filename = $_ENV['ES_ROOT'].'/data/'.$filePath;

		return new BinaryFileResponse($filename);
	}

	#[Route(path: '/es/dataFiles', name: 'ESDataFiles')]
	public function esDataFiles(Request $request): Response {
		$path = $_ENV['ES_ROOT'].'/data/';
		$directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::FOLLOW_SYMLINKS);
		$filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
			// Skip hidden files and directories.
			if ($current->getFilename()[0] === '.') {
				return FALSE;
			}
			if (!$current->isDir()) {
				// Only consume files of interest.
				return $current->getExtension() == 'txt' || $current->getExtension() == 'TXT';
			}

			return true;
		});
		$iterator = new \RecursiveIteratorIterator($filter);
		$files = array();
		$pathLen = mb_strlen($path);
		foreach ($iterator as $info) {
			$fullPath = $info->getPathname();
			$relPath = mb_substr($fullPath, $pathLen);
			$files[] = $relPath;
		}

		return $this->json($files);
	}

	protected static array $imageExtensions = ['png', 'jpg', 'jpeg', 'jpe', 'avif', 'avifs'];
	#[Route(path: '/es/spriteFiles', name: 'ESSpriteFiles')]
	public function esSpriteFiles(Request $request): Response {
		$path = $_ENV['ES_ROOT'].'/images/';
		
		$directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::FOLLOW_SYMLINKS);
		$filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
			// Skip hidden files and directories.
			if ($current->getFilename()[0] === '.') {
				return FALSE;
			}
			if (!$current->isDir()) {
				// Only consume files of interest.
				return in_array($current->getExtension(), BaseController::$imageExtensions);
			}

			return true;
		});
		$iterator = new \RecursiveIteratorIterator($filter);
		$files = array();
		$pathLen = mb_strlen($path);
		foreach ($iterator as $info) {
			$fullPath = $info->getPathname();
			$relPath = mb_substr($fullPath, $pathLen);
			$files[] = $relPath;
		}

		return $this->json($files);
	}

	#[Route(path: '/es/sprite/{path}', name: 'ESSpriteFile', requirements: ['path' => '.+'])]
	public function esImage(Request $request, string $path): Response {
		$fullPath = $_ENV['ES_ROOT'].'/images/'.$path;
		$type = pathinfo($fullPath)['extension'];
		$response = new BinaryFileResponse($fullPath);
		$response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
		$response->headers->set('Content-Type', 'image/'.$type);

		// Cache the image files each for a week
		$expires = (60*60*24*7);
		$response->headers->set('Cache-Control', 'public, max-age='.$expires);

		return $response;
	}

}