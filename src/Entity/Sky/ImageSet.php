<?php

namespace App\Entity\Sky;

class ImageSet {
	// Name of the sprite that will be initialized with these images.
	private string $name;
	// Paths to all the images that were discovered during loading.
	private array $framePaths = []; // map<size_t, string>
	// Paths that comprise a valid animation sequence of 1 or more frames.
	private array $paths = []; // vector<string>
	// Data loaded from the images:
	private $buffer;
	private array $masks; // vector<Mask>
	
	// TGC added
	private int $width = 0;
	private int $height = 0;
	
	private array $source;
	
	// Determine whether the given path is to an @2x image.
	public static function Is2x(string $path): bool {
		if (strlen($path) < 7) {
			return false;
		}

		$pos = strlen($path) - 7;
		return ($path[$pos] == '@' && $path[$pos + 1] == '2' && $path[$pos + 2] == 'x');
	}
	
	// Check if the given character is a valid blending mode.
	public static function IsBlend(string $c): bool {
		return ($c == '-' || $c == '~' || $c == '+' || $c == '=');
	}

	// Determine whether the given path or name is to a sprite for which a
	// collision mask ought to be generated.
	public static function IsMasked(string $path): bool {
		if (strlen($path) >= 5 && substr($path, 0, 5) == "ship/") {
			return true;
		}
		if (strlen($path) >= 9 && substr($path, 0, 9) == "asteroid/") {
			return true;
		}

		return false;
	}
	
	// Get the character index where the sprite name in the given path ends.
	public static function NameEnd(string $path): int {
		// The path always ends in a three-letter extension, ".png" or ".jpg".
		// In addition, 3 more characters may be taken up by an @2x label.
		$end = strlen($path) - (self::Is2x($path) ? 7 : 4);
		// This should never happen, but just in case:
		if(!$end) {
			return 0;
		}

		// Skip any numbers at the end of the name.
		$pos = $end;
		while (--$pos) {
			if (mb_ord($path[$pos]) < mb_ord('0') || mb_ord($path[$pos]) > mb_ord('9')) {
				break;
			}
		}

		// If there is not a blending mode specifier before the numbers, they
		// are part of the sprite name, not a frame index.
		return (self::IsBlend($path[$pos]) ? $pos : $end);
	}
	
	// Get the frame index from the given path.
	public static function FrameIndex(string $path): int {
		// Get the character index where the "name" portion of the path ends.
		// A path's format is always: <name>(<blend><frame>)(@2x).(png|jpg)
		$i = self::NameEnd($path);

		// If the name contains a frame index, it must be separated from the name
		// by a character indicating the additive blending mode.
		if (!self::IsBlend($path[$i])) {
			return 0;
		}

		$frame = 0;
		// The path ends in an extension, so there's no need to check for going off
		// the end of the string in this loop; we're guaranteed to hit a non-digit.
		for (++$i; mb_ord($path[$i]) >= mb_ord('0') && mb_ord($path[$i]) <= mb_ord('9'); ++$i) {
			$frame = ($frame * 10) + intval($path[$i]);
		}

		return $frame;
	}
	
	// Add consecutive frames from the given map to the given vector. Issue warnings for missing or mislabeled frames.
	public static function AddValid(array &$frameData, array &$sequence, string &$prefix, bool $is2x): void {
		if (count($frameData) == 0) {
			return;
		}
		// Valid animations (or stills) begin with frame 0.
		if (array_keys($frameData)[0] != 0) {
			error_log($prefix . "ignored " . ($is2x ? "@2x " : "") . "frame " . array_keys($frameData)[0] . " (" . count($frameData) . " ignored in total). Animations must start at frame 0.");
			return;
		}

		$sequence = [];
		// Find the first frame that is not a single increment over the previous frame.
		$last = -1;
		foreach ($frameData as $frameIndex => $frame) {
			if ($last + 1 == $frameIndex) {
				$last = $frameIndex;
				
				$sequence[$frameIndex] = $frame;
			} else {
				break;
			}
		}
		$count = $frameIndex + 1;
		
		// If `next` is not the end, then there was at least one discontinuous frame.
		if ($count != count($frameData)) {
			$ignored = count($frameData) - $count;
			error_log($prefix . "missing " . ($is2x ? "@2x " : "") . "frame " . $frameIndex . " (" . $ignored . ($ignored > 1 ? " frames" : " frame") . " ignored in total).");
		}
	}
	
	// Check if the given path is to an image of a valid file type.
	public static function IsImage(string $path): bool {
		if (strlen($path) < 4) {
			return false;
		}
	
		$ext = substr($path, -4);
		return ($ext == ".png" || $ext == ".jpg" || $ext == ".PNG" || $ext == ".JPG");
	}
	
	// Get the base name for the given path. The path should be relative to one
	// of the source image directories, not a full filesystem path.
	public static function Name(string $path): string {
		return substr($path, 0, self::NameEnd($path));
	}
	
	// Determine whether the given path or name is for a sprite whose loading
	// should be deferred until needed.
	public static function IsDeferred(string $path): bool {
		if (strlen($path) >= 5 && substr($path, 0, 5) != "land/") {
			return true;
		}
	
		return false;
	}
	
	public function __construct(string $name) {
		$this->name = $name;
		$this->framePaths[0] = array();
		$this->framePaths[1] = array();
		$this->paths[0] = array();
		$this->paths[1] = array();
	}
	
	// Get the name of the sprite for this image set.
	public function getName(): string {
		return $this->name;
	}
	
	public function getWidth(): int {
		return $this->width;
	}
	
	public function getHeight(): int {
		return $this->height;
	}
	
	// Whether this image set is empty, i.e. has no images.
	public function isEmpty(): bool {
		return count($this->framePaths[0]) == 0 && count($this->framePaths[1]) == 0;
	}
	
	// Add a single image to this set. Assume the name of the image has already
	// been checked to make sure it belongs in this set.
	public function add(string $path): void {
		// Determine which frame of the sprite this image will be.
		$is2x = self::Is2x($path) ? 1 : 0;
		$frame = self::FrameIndex($path);
		// Store the requested path.
		$this->framePaths[$is2x][$frame] = $path;
		
		if ($this->width == 0) {
			$imageSize = getimagesize($_ENV['DATA_PATH'].'images/'.$path);
			$this->width = $imageSize[0];
			$this->height = $imageSize[1];
		}
	}
	
	public function setSource(array $source): void {
		$this->source = $source;
	}
	public function getSource(): array {
		return $this->source;
	}
	
	// Reduce all given paths to frame images into a sequence of consecutive frames.
	public function validateFrames(): void {
		$prefix = "Sprite \"" . $this->name . "\": ";
		self::AddValid($this->framePaths[0], $this->paths[0], $prefix, false);
		self::AddValid($this->framePaths[1], $this->paths[1], $prefix, true);
		$this->framePaths[0] = [];
		$this->framePaths[1] = [];
	
		// Drop any @2x paths that will not be used.
		if (count($this->paths[1]) > count($this->paths[0])) {
			error_log($prefix . (count($this->paths[1]) - count($this->paths[0])) . " extra frames for the @2x sprite will be ignored.");
			for ($i = count($this->paths[1]) - 1; $i > count($this->paths[0]); $i--) {
				unset($this->paths[1][$i]);
			}
		}
	}
	
	public function getFrameCount(): int {
		return count($this->paths[0]);
	}
	
	public function getFramePath(int $i, bool $for2x = false): string {
		$setIndex = 0;
		if ($for2x) {
			$setIndex = 1;
		}
		if (!isset($this->paths[$setIndex][$i])) {
			return '';
		}
		return $this->paths[$setIndex][$i];
	}
	
	// // Load all the frames. This should be called in one of the image-loading
	// // worker threads. This also generates collision masks if needed.
	// public function load(): void {
	// 	// Determine how many frames there will be, total. The image buffers will
	// 	// not actually be allocated until the first image is loaded (at which point
	// 	// the sprite's dimensions will be known).
	// 	$frames = count($this->paths[0]);
	// 	buffer[0].Clear(frames);
	// 	buffer[1].Clear(frames);
	// 
	// 	// Check whether we need to generate collision masks.
	// 	bool makeMasks = IsMasked(name);
	// 	if(makeMasks)
	// 		masks.resize(frames);
	// 
	// 	// Load the 1x sprites first, then the 2x sprites, because they are likely
	// 	// to be in separate locations on the disk. Create masks if needed.
	// 	for(size_t i = 0; i < frames; ++i)
	// 	{
	// 		if(!buffer[0].Read(paths[0][i], i))
	// 			Logger::LogError("Failed to read image data for \"" + name + "\" frame #" + to_string(i));
	// 		else if(makeMasks)
	// 		{
	// 			masks[i].Create(buffer[0], i);
	// 			if(!masks[i].IsLoaded())
	// 				Logger::LogError("Failed to create collision mask for \"" + name + "\" frame #" + to_string(i));
	// 		}
	// 	}
	// 	// Now, load the 2x sprites, if they exist. Because the number of 1x frames
	// 	// is definitive, don't load any frames beyond the size of the 1x list.
	// 	for(size_t i = 0; i < frames && i < paths[1].size(); ++i)
	// 		if(!buffer[1].Read(paths[1][i], i))
	// 		{
	// 			Logger::LogError("Removing @2x frames for \"" + name + "\" due to read error");
	// 			buffer[1].Clear();
	// 			break;
	// 		}
	// 
	// 	// Warn about a "high-profile" image that will be blurry due to rendering at 50% scale.
	// 	bool willBlur = (buffer[0].Width() & 1) || (buffer[0].Height() & 1);
	// 	if(willBlur && (
	// 			(name.length() > 5 && !name.compare(0, 5, "ship/"))
	// 			|| (name.length() > 7 && !name.compare(0, 7, "outfit/"))
	// 			|| (name.length() > 10 && !name.compare(0, 10, "thumbnail/"))
	// 	))
	// 		Logger::LogError("Warning: image \"" + name + "\" will be blurry since width and/or height are not even ("
	// 			+ to_string(buffer[0].Width()) + "x" + to_string(buffer[0].Height()) + ").");
	// }
	// 
	// 
	// 
	// // Create the sprite and upload the image data to the GPU. After this is
	// // called, the internal image buffers and mask vector will be cleared, but
	// // the paths are saved in case the sprite needs to be loaded again.
	// void ImageSet::Upload(Sprite *sprite)
	// {
	// 	// Load the frames (this will clear the buffers).
	// 	sprite->AddFrames(buffer[0], false);
	// 	sprite->AddFrames(buffer[1], true);
	// 	GameData::GetMaskManager().SetMasks(sprite, std::move(masks));
	// 	masks.clear();
	// }

}