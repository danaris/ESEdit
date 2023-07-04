<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use App\Entity\TaskDefinition;

use App\Entity\Sky\Color;
use App\Service\SkyService;

class SkyExtension extends AbstractExtension {
	
	public function __construct(protected SkyService $skyService) {
		
	}
	
	public function getFilters() {
		$filters = array();
		
		$filters []= new TwigFilter('cssNameEsc', function ($string) {
			$escString = str_replace([' ','(',')','\''],['-','_','_','-'], $string);
			
			return $escString;
		});
		
		return $filters;
	}
	
	public function getFunctions() {
		$functions = array();
		$functions []= new TwigFunction('colorToCSS', [$this, 'colorToCSS']);
		$functions []= new TwigFunction('skyBasePath', [$this, 'basePath']);
		$functions []= new TwigFunction('skyPlugins', [$this, 'skyPlugins']);
		
		return $functions;
	}
	
	function basePath() {
		return $this->skyService->basePath;
	}
	
	function skyPlugins() {
		return $this->skyService->plugins;
	}
	
	function colorToCSS($skyColor) {
		$skyColorCSS = 'white';
		//error_log('Converting ['.print_r($skyColor,true).'] to CSS');
		if ($skyColor instanceof Color) {
			$skyColor = [$skyColor->red, $skyColor->green, $skyColor->blue];
		}
		$colorData = $this->skyService->getData();
		if (is_array($skyColor)) {
		//	error_log('Is array, checking if it needs conversion');
			if (($skyColor[0] > 0 && $skyColor[0] < 1) || ($skyColor[1] > 0 && $skyColor[1] < 1) || ($skyColor[2] > 0 && $skyColor[2] < 1)) {
				$skyColor = $this->skyService->floatToIntColor($skyColor);
			}
		} else if (isset($colorData['colors'][$skyColor])) {
			//error_log('Is named, converting from '.print_r($colorData['colors'][$skyColor], true));
			$skyColor = $this->colorToCSS($colorData['colors'][$skyColor]);
		} else {
		//	error_log('Can\'t find it in the list!');
		}
		
		if (is_array($skyColor)) {
			//error_log('Final conversion to string from '.print_r($skyColor, true));
			$skyColorCSS = 'rgb('.$skyColor[0].','.$skyColor[1].','.$skyColor[2].')';
		} else {
			$skyColorCSS = $skyColor;
		}
		
		return $skyColorCSS;
	}
}