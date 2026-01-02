<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\TaskDefinition;

use App\Entity\DataWriter;
use App\Entity\Sky\Government;
use App\Entity\Sky\Point as SkyPoint;
use App\Entity\Sky\Color;
use App\Entity\Sky\System;
use App\Entity\Sky\GameData;
use App\Entity\Sky\Sprite;
use App\Entity\Sky\SpriteSet;
use App\Entity\Sky\StellarObject;
use App\Service\SkyService;

class SkyExtension extends AbstractExtension {

	public function __construct(protected SkyService $skyService,
								protected EntityManagerInterface $em,
								protected LoggerInterface $logger) {
		$this->skyService->loadUniverseFromFiles();
	}

	public function getFilters() {
		$filters = array();

		$filters []= new TwigFilter('cssNameEsc', [$this, 'cssNameEsc']);

		return $filters;
	}

	function cssNameEsc($string) {
		$escString = str_replace([' ','(',')','\''],['-','_','_','-'], $string);

		return $escString;
	}

	public function getFunctions() {
		$functions = array();
		$functions []= new TwigFunction('colorToCSS', [$this, 'colorToCSS']);
		$functions []= new TwigFunction('skyBasePath', [$this, 'basePath']);
		$functions []= new TwigFunction('skyPlugins', [$this, 'skyPlugins']);
		$functions []= new TwigFunction('angleBetween', [$this, 'angleBetween']);
		$functions []= new TwigFunction('systemInfo', [$this, 'systemInfo']);
		$functions []= new TwigFunction('imageFor', [$this, 'imageFor']);
		$functions []= new TwigFunction('stellarObjectPosition', [$this, 'stellarObjectPosition']);
		$functions []= new TwigFunction('skyGovernments', [$this, 'skyGovernments']);
		$functions []= new TwigFunction('createLabel', [$this, 'createLabel']);
		$functions []= new TwigFunction('esToSvg', [$this, 'esToSvg']);
		$functions []= new TwigFunction('writeObject', [$this, 'writeObject']);
		$functions []= new TwigFunction('spritePath', [$this, 'spritePath']);
		$functions []= new TwigFunction('errorLog', [$this, 'errorLog']);
		$functions []= new TwigFunction('extSpritePath', [$this, 'extSpritePath']);
		$functions []= new TwigFunction('labelFromIndex', [$this, 'labelFromIndex']);
		$functions []= new TwigFunction('nodeIsBranch', [$this, 'nodeIsBranch']);

		return $functions;
	}

	function basePath() {
		return $this->skyService->basePath;
	}

	function skyPlugins() {
		return $this->skyService->plugins;
	}

	// function colorToCSS($skyColor) {
	// 	$skyColorCSS = 'white';
	// 	error_log('Converting ['.print_r($skyColor,true).']('.get_class($skyColor).') to CSS');
	// 	if ($skyColor instanceof Color) {
	// 		$skyColor = [$skyColor->red, $skyColor->green, $skyColor->blue];
	// 	} else if ($skyColor instanceof ESLib\Color || get_class($skyColor) == "ESLib\\Color") {
	// 		error_log('Color is from extension');
	// 		$skyColor = $skyColor->get();
	// 	}
	// 	$colorData = $this->skyService->getData();
	// 	if (is_array($skyColor)) {
	// 	//	error_log('Is array, checking if it needs conversion');
	// 		if (($skyColor[0] > 0 && $skyColor[0] < 1) || ($skyColor[1] > 0 && $skyColor[1] < 1) || ($skyColor[2] > 0 && $skyColor[2] < 1)) {
	// 			$skyColor = $this->skyService->floatToIntColor($skyColor);
	// 		}
	// 	} else if (isset($colorData['colors'][$skyColor])) {
	// 		//error_log('Is named, converting from '.print_r($colorData['colors'][$skyColor], true));
	// 		$skyColor = $this->colorToCSS($colorData['colors'][$skyColor]);
	// 	} else {
	// 	//	error_log('Can\'t find it in the list!');
	// 	}

	// 	if (is_array($skyColor)) {
	// 		//error_log('Final conversion to string from '.print_r($skyColor, true));
	// 		$skyColorCSS = 'rgb('.$skyColor[0].','.$skyColor[1].','.$skyColor[2].')';
	// 	} else {
	// 		$skyColorCSS = $skyColor;
	// 	}

	// 	return $skyColorCSS;
	// }

	public function angleBetween(SkyPoint $fromPoint, SkyPoint $toPoint) {

		$rise = $toPoint->Y() - $fromPoint->Y();
		$run = $toPoint->X() - $fromPoint->X();
		if ($run == 0) {
			if ($rise > 0) {
				return 90;
			} else {
				return 270;
			}
		}

		$slope = $rise / $run;

		$angle = atan($slope);

		$angleDeg = rad2deg($angle);
		if ($rise < 0 && $run > 0) {
			$angleDeg += 360;
		} else if ($rise < 0 || $run < 0) {
			$angleDeg += 180;
		}

		return $angleDeg;
	}

	public function systemInfo(string $systemName): System {
		return GameData::Systems()[$systemName];
	}

	public function skyGovernments(): array {
		//return GameData::Governments()->getContents();
		return $this->em->getRepository(Government::class)->findAll();
	}

	public function imageFor(string $imageName): Sprite {
		// if (isset($this->skyService->getImages()[$imageName])) {
		// 	return $this->skyService->getImages()[$imageName];
		// }
		// return [];
		return SpriteSet::Get($imageName);
	}

	public function spritePath(Sprite $Sprite, int $frameIndex=0): string {
		$framePaths = $Sprite->getFramePaths();
		if (isset($framePaths[$frameIndex])) {
			$SpritePath = $framePaths[$frameIndex];
			$imagePath = $SpritePath->getPath();
		} else {
			$imagePath = 'outfits/unknown.png';
		}

		return '/skyImage/'.$imagePath;
	}

	public function esToSvg(SkyPoint $point, float $size): SkyPoint {
		return new SkyPoint($point->X() + $size/2, $point->Y() + $size/2);
	}

	public function stellarObjectPosition(System $system, StellarObject $object, int $time): SkyPoint {
		$center = new SkyPoint();
		if ($object->parent) {
			$center = $this->stellarObjectPosition($system, $object->parent, $time);
		}
		$angle = $object->offset + $time * $object->speed;
		$posX = $center->X() + $object->distance * cos(deg2rad($angle));
		$posY = $center->Y() + $object->distance * sin(deg2rad($angle));
		return new SkyPoint($posX, $posY);
	}
	public static array $labelLineAngles = [60., 120., 300., 240.];
	public static float $labelLineLength = 60.;
	public static float $labelInnerSpace = 10.;
	public static float $labelLineGap = 12;
	public static float $labelGap = 20.;
	public static float $labelMinDistance = 30.;

	public function createLabel(System $system, StellarObject $object, int $time, float $viewSize): array {

		$this->logger->debug('Creating label for '.($object->planet ? $object->planet->getTrueName() : $object->getSprite()->getName()).' in '.$system->getName());

		$position = $this->esToSvg($this->stellarObjectPosition($system, $object, $time), $viewSize);
		$this->logger->debug('Base position is '.$position);
		$radius = $object->getSprite()->getWidth() / 2;
		$innerRadius = round($radius + self::$labelInnerSpace);
		$outerRadius = round($innerRadius + self::$labelLineGap);
		// The angle of the outer ring should be reduced by just enough that the
		// circumference is reduced by 6 pixels.
		$innerAngle = self::$labelLineAngles[1];
		//$outerAngle = $innerAngle - 360. * self::$labelGap / (2. * M_PI * $radius);
		$outerAngle = $innerAngle + self::$labelGap;

		$this->logger->debug('For inner ring, radius is '.$innerRadius.' and angle is from '.$innerAngle.' to '.$outerAngle);
		// $innerStart = new SkyPoint(round($position->X() + $innerRadius * cos(deg2rad($innerAngle))), round($position->Y() + $innerRadius * sin(deg2rad($innerAngle))));
		// $innerEnd = new SkyPoint(round($position->X() + $innerRadius * cos(deg2rad($outerAngle))), round($position->Y() + $innerRadius * sin(deg2rad($outerAngle))));
		// $outerStart = new SkyPoint(round($position->X() + $outerRadius * cos(deg2rad($innerAngle))), round($position->Y() + $outerRadius * sin(deg2rad($innerAngle))));
		// $outerEnd = new SkyPoint(round($position->X() + $outerRadius * cos(deg2rad($outerAngle))), round($position->Y() + $outerRadius * sin(deg2rad($outerAngle))));
		//
		// $this->logger->debug('Inner ring will be drawn from '.$innerStart.' to '.$innerEnd);
		//
		// $label['innerStart'] = $innerStart->X().' '.$innerStart->Y();
		// $label['outerStart'] = $outerStart->X().' '.$outerStart->Y();
		// $label['innerPath'] = 'A'.$innerRadius.' '.$innerRadius.' '.$innerAngle.' 1 1 '.$innerEnd->X().' '.$innerEnd->Y();
		// $label['outerPath'] = 'A'.$outerRadius.' '.$outerRadius.' '.$innerAngle.' 1 1 '.$outerEnd->X().' '.$outerEnd->Y();
		$lineStart = $this->polarToCartesian($position->X(), $position->Y(), $outerRadius, $outerAngle);
		$lineEnd = $this->polarToCartesian($position->X(), $position->Y(), $outerRadius + self::$labelLineLength, $outerAngle);
		$label['innerArc'] = $this->describeArc($position->X(), $position->Y(), $innerRadius, $innerAngle, $outerAngle);
		$label['outerArc'] = $this->describeArc($position->X(), $position->Y(), $outerRadius, $innerAngle, $outerAngle);

		//$lineEnd = new SkyPoint($position->X() + ($innerRadius + self::$labelLineLength) * cos(deg2rad($innerAngle)), $position->Y() + ($innerRadius + self::$labelLineLength) * sin(deg2rad($innerAngle)));

		$label['lineStart'] = $lineStart;
		$label['lineEnd'] = $lineEnd;

		$text = $object->planet->getName().' ('.$object->planet->getGovernment()->getName().')';
		$charHeight = 40;
		$charWidth = $charHeight / 2;
		$textWidth = $charWidth * strlen($text);
		$textXOffset = -$textWidth / 2;
		$textYOffset = $charHeight / 2;
		$label['textStart'] = new SkyPoint($lineEnd->X() + $textXOffset, $lineEnd->Y() + $textYOffset);

		// Point unit = Angle(innerAngle).Unit();
		// RingShader::Draw(position, radius + INNER_SPACE, 2.3f, .9f, color, 0.f, innerAngle);
		// RingShader::Draw(position, radius + INNER_SPACE + GAP, 1.3f, .6f, color, 0.f, outerAngle);
		//
		// if(!name.empty())
		// {
		// 	Point from = position + (radius + INNER_SPACE + LINE_GAP) * unit;
		// 	Point to = from + LINE_LENGTH * unit;
		// 	LineShader::Draw(from, to, 1.3f, color);
		//
		// 	double nameX = to.X() + (direction < 2 ? 2. : -bigFont.Width(name) - 2.);
		// 	bigFont.DrawAliased(name, nameX, to.Y() - .5 * bigFont.Height(), color);
		//
		// 	double governmentX = to.X() + (direction < 2 ? 4. : -font.Width(government) - 4.);
		// 	font.DrawAliased(government, governmentX, to.Y() + .5 * bigFont.Height() + 1., color);
		// }
		// Angle barbAngle(innerAngle + 36.);
		// for(int i = 0; i < hostility; ++i)
		// {
		// 	barbAngle += Angle(800. / (radius + 25.));
		// 	PointerShader::Draw(position, barbAngle.Unit(), 15.f, 15.f, radius + 25., color);
		// }

		return $label;
	}

	function polarToCartesian($centerX, $centerY, $radius, $angleInDegrees) {
	  $angleInRadians = ($angleInDegrees-90) * M_PI / 180.0;

	  return new SkyPoint(round($centerX + ($radius * cos($angleInRadians))), round($centerY + ($radius * sin($angleInRadians))));
	}

	function describeArc($x, $y, $radius, $startAngle, $endAngle) {

		$start = $this->polarToCartesian($x, $y, $radius, $endAngle);
		$end = $this->polarToCartesian($x, $y, $radius, $startAngle);

		$largeArcFlag = ($endAngle - $startAngle) % 360 <= 180 ? "1" : "0";

		$d = "M".$start->X().' '.$start->Y().' A'.$radius.' '.$radius.' 0 '.$largeArcFlag.' 1 '.$end->X().' '.$end->Y();

		return $d;
	}

	function writeObject($object): string {
		$stringWriter = new DataWriter('');
		$object->save($stringWriter);

		return $stringWriter->getString();
	}

	function errorLog(string $string): void {
		error_log('From Twig: '.$string);
	}

	// public function extSpritePath(\ESLib\Sprite $Sprite, int $frameIndex=0): string {
	// 	error_log('extSpritePath(): Getting path for sprite '.$Sprite->getName().' frame '.$frameIndex);
	// 	$path = $Sprite->getPath($frameIndex);
	// 	error_log('exxtSpritePath(): Got path ['.$path.']');
	// 	if ($path) {
	// 		return $path;
	// 	} else {
	// 		return 'outfits/unknown.png';
	// 	}
	// }

	public function labelFromIndex(array $labels, int $index): ?string {
		foreach ($labels as $labelName => $labelIndex) {
			if ($labelIndex == $index) {
				return $labelName;
			}
		}

		return null;
	}

	public function nodeIsBranch(array $node): bool {
		return count($node['conditions']) == 0 && count($node['elements']) > 1;
	}

}