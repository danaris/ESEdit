<?php

namespace App\Entity\Sky;

use App\Entity\TemplatedArray;
use App\Service\TemplatedArrayService;

class SpriteSet {
	private static TemplatedArray $sprites;
	
	private static array $imageData = []; // string => ImageSet
	
	private static function Init() {
		self::$sprites = TemplatedArrayService::Instance()->createTemplatedArray(Sprite::class);
	}
	
	public static function Get(string $name): Sprite {
		return SpriteSet::Modify($name);
	}
	
	public static function CheckReferences(): void {
		if (!isset(self::$sprites)) {
			self::Init();
		}
		foreach ($this->sprites as $spriteName => $sprite) {
			if ($sprite->getHeight() == 0 && $sprite->getWidth() == 0) {
				// Landscapes are allowed to still be empty.
				if (substr($spriteName, 0, 5) == "land/") {
					error_log("Warning: image \"" . $spriteName . "\" is referred to, but has no pixels.");
				}
			}
		}
	}
	
	public static function Modify(string $name) {
		if (!isset(self::$sprites)) {
			self::Init();
		}
		if (!isset(self::$sprites->getContents()[$name])) {
			$sprite = new Sprite($name);
			self::$sprites[$name] = $sprite;
			if (isset(self::$imageData[$name])) {
				$ImageSet = self::$imageData[$name];
				$sprite->setWidth($ImageSet->getWidth());
				$sprite->setHeight($ImageSet->getHeight());
				$sprite->setFrames($ImageSet->getFrameCount());
				for ($i=0; $i<$ImageSet->getFrameCount(); $i++) {
					$SpritePath = new SpritePath();
					$SpritePath->setSprite($sprite);
					$SpritePath->setPathIndex($i);
					$SpritePath->setPath($ImageSet->getFramePath($i));
					$SpritePath->setIs2x(false);
					$sprite->getFramePaths() []= $SpritePath;
				}
				//$sprite->setPath($ImageSet->getFramePath(0));
			}
		}
		return self::$sprites[$name];
	}
	
	public static function SetImageData(array $imageData) {
		self::$imageData = $imageData;
	}
	
	public static function PostProcess($eventArgs) {
		foreach (self::$sprites as $spriteName => $Sprite) {
			$Sprite->toDatabase($eventArgs);
		}
	}
}