<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;
use App\Entity\DataWriter;

// Class representing any object in the game that has a position, velocity, and
// facing direction and usually also has a sprite.
#[ORM\Entity]
#[ORM\Table(name: 'Body')]
#[ORM\HasLifecycleCallbacks]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "subclass", type: "string")]
#[ORM\DiscriminatorMap(['body' => Body::class, 'effect' => Effect::class, 'stellarObject' => StellarObject::class, 'ship' => Ship::class, 'minable' => Minable::class])]
class Body {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	protected int $id;
	
	#[ORM\Column(type: 'string', name: 'positionStr')]
	protected string $positionStr = '';
	#[ORM\Column(type: 'string', name: 'velocityStr')]
	protected string $velocityStr = '';
	#[ORM\Column(type: 'integer', name: 'angleDegrees')]
	protected int $angleDegrees = 0;
	
	// Basic positional attributes.
	protected Point $position;
	protected Point $velocity;
	protected Angle $angle;
	// A zoom of 1 means the sprite should be drawn at half size. For objects
	// whose sprites should be full size, use zoom = 2.
	#[ORM\Column(type: 'float', name: 'zoom')]
	protected float $zoom = 1.0;
	
	#[ORM\Column(type: 'float', name: 'scale')]
	protected float $scale = 1.0;
	
	// Animation parameters.
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sprite', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'spriteId')]
	protected ?Sprite $sprite = null;
	// Allow objects based on this one to adjust their frame rate and swizzle.
	#[ORM\Column(type: 'integer', name: 'swizzle')]
	protected int $swizzle = 0;
	
	
	#[ORM\Column(type: 'float', name: 'frameRate')]
	protected float $frameRate = 2.0 / 60.0;
	
	#[ORM\Column(type: 'integer', name: 'delay')]
	protected int $delay = 0;
	
	// The chosen frame will be (step * frameRate) + frameOffset.
	#[ORM\Column(type: 'float', name: 'frameOffset')]
	protected float $frameOffset = 0.0;
	
	#[ORM\Column(type: 'boolean', name: 'startAtZero')]
	protected bool $startAtZero = false;
	
	#[ORM\Column(type: 'boolean', name: 'randomize')]
	protected bool $randomize = false;
	
	#[ORM\Column(type: 'boolean', name: 'repeatAnimation')]
	protected bool $repeat = true;
	
	#[ORM\Column(type: 'boolean', name: 'rewind')]
	protected bool $rewind = false;
	
	#[ORM\Column(type: 'integer', name: 'pause')]
	protected int $pause = 0;
	

	// Record when this object is marked for removal from the game.
	protected bool $shouldBeRemoved = false;
	
	// Cache the frame calculation so it doesn't have to be repeated if given
	// the same step over and over again.
	protected int $currentStep = -1;
	protected float $frame = 0.0;
	
// 		// Constructors.
// 		Body() = default;
// 		Body(const Sprite *sprite, Point position, Point velocity = Point(), Angle facing = Angle(), double zoom = 1.);
// 		Body(const Body &sprite, Point position, Point velocity = Point(), Angle facing = Angle(), double zoom = 1.);
// 	
// 		// Check that this Body has a sprite and that the sprite has at least one frame.
// 		bool HasSprite() const;
// 		// Access the underlying Sprite object.
// 		const Sprite *GetSprite() const;
// 		// Get the dimensions of the sprite.
// 		double Width() const;
// 		double Height() const;
// 		// Get the farthest a part of this sprite can be from its center.
// 		double Radius() const;
// 		// Which color swizzle should be applied to the sprite?
// 		int GetSwizzle() const;
// 		// Get the sprite frame and mask for the given time step.
// 		float GetFrame(int step = -1) const;
// 		const Mask &GetMask(int step = -1) const;
// 	
// 		// Positional attributes.
// 		const Point &Position() const;
// 		const Point &Velocity() const;
// 		const Angle &Facing() const;
// 		Point Unit() const;
// 		double Zoom() const;
// 		double Scale() const;
// 	
// 		// Check if this object is marked for removal from the game.
// 		bool ShouldBeRemoved() const;
// 	
// 		// Store the government here too, so that collision detection that is based
// 		// on the Body class can figure out which objects will collide.
// 		const Government *GetGovernment() const;
// 	
// 		// Sprite serialization.
// 		void LoadSprite(const DataNode &node);
// 		void SaveSprite(DataWriter &out, const std::string &tag = "sprite") const;
// 		// Set the sprite.
// 		void SetSprite(const Sprite *sprite);
// 		// Set the color swizzle.
// 		void SetSwizzle(int swizzle);
// 	
// 	
// 	protected:
// 		// Adjust the frame rate.
// 		void SetFrameRate(float framesPerSecond);
// 		void AddFrameRate(float framesPerSecond);
// 		void PauseAnimation();
// 		// Mark this object to be removed from the game.
// 		void MarkForRemoval();
// 		// Mark that this object should not be removed (e.g. a launched fighter).
// 		void UnmarkForRemoval();
// 	
// 	
// 
// 	
// 		// Government, for use in collision checks.
// 		const Government *government = nullptr;
// 	
// 	
// 	protected:
// 		// Set what animation step we're on. This affects future calls to GetMask()
// 		// and GetFrame().
// 		void SetStep(int step) const;
	
	// Constructor, based on a Sprite.
	public function __construct(Sprite|Body|null $sprite = null, ?Point $position = null, ?Point $velocity = null, ?Angle $facing = null, ?float $zoom = null) {
		$this->position = new Point();
		$this->velocity = new Point();
		$this->angle = new Angle();
		if ($sprite != null) {
			if ($sprite instanceof Sprite) {
				$this->randomize = true;
				$this->sprite = $sprite;
			} else {
				$this->copyFrom($sprite);
			}
			$this->position = $position;
			$this->velocity = $velocity;
			$this->angle = $facing;
			$this->zoom = $zoom;
		}
	}
	
	protected function copyFrom($body) {
		$this->position = $body->position;
		$this->velocity = $body->velocity;
		$this->angle = $body->angle;
		$this->zoom = $body->zoom;
		$this->scale = $body->scale;
		$this->sprite = $body->sprite;
		$this->swizzle = $body->swizzle;
		$this->frameRate = $body->frameRate;
		$this->delay = $body->delay;
		$this->frameOffset = $body->frameOffset;
		$this->startAtZero = $body->startAtZero;
		$this->randomize = $body->randomize;
		$this->repeat = $body->repeat;
		$this->rewind = $body->rewind;
		$this->pause = $body->pause;
		$this->shouldBeRemoved = $body->shouldBeRemoved;
		$this->currentStep = $body->currentStep;
		$this->frame = $body->frame;
	}
	
	// Check that this Body has a sprite and that the sprite has at least one frame.
	public function hasSprite(): bool {
		return ($this->sprite != null && $this->sprite->getFrames() > 0);
	}
	
	// Access the underlying Sprite object.
	public function getSprite(): Sprite {
		return $this->sprite;
	}
	
	// Get the width of this object, in world coordinates (i.e. taking zoom and scale into account).
	public function getWidth(): float {
		return $this->sprite != null ? (0.5 * $this->zoom) * $this->scale * $this->sprite->getWidth() : 0.0;
	}
	
	// Get the height of this object, in world coordinates (i.e. taking zoom and scale into account).
	public function getHeight(): float {
		return $this->sprite != null ? (0.5 * $this->zoom) * $this->scale * $this->sprite->getHeight() : 0.0;
	}
	
	// Get the farthest a part of this sprite can be from its center.
	public function getRadius(): float {
		return .5 * (new Point($this->getWidth(), $this->Height())).getLength();
	}
	
	// Which color swizzle should be applied to the sprite?
	public function getSwizzle(): int {
		return $swizzle;
	}
	
	// Get the frame index for the given time step. If no time step is given, this
	// will return the frame from the most recently given step.
	public function getFrame(int $step): float {
		if ($step >= 0) { 
			$this->setStep($step);
		}
	
		return $this->frame;
	}
	
	// Get the mask for the given time step. If no time step is given, this will
	// return the mask from the most recently given step.
	// public function getMask(int $step): Mask {
	// 	if ($step >= 0) {
	// 		$this->setStep($step);
	// 	}
	// 
	// 	$emptyMask = new Mask();
	// 	$current = round($this->frame);
	// 	if (!$this->sprite || $current < 0) {
	// 		return $emptyMask;
	// 	}
	// 
	// 	$masks = GameData::GetMaskManager()->getMasks($this->sprite, $this->getScale());
	// 
	// 	// Assume that if a masks array exists, it has the right number of frames.
	// 	return count($masks) == 0 ? $emptyMask : $masks[$current % count($masks)];
	// }
	
	// Position, in world coordinates (zero is the system center).
	public function getPosition(): Point {
		return $this->position;
	}
	
	// Velocity, in pixels per second.
	public function getVelocity(): Point {
		return $this->velocity;
	}
	
	// Direction this Body is facing in.
	public function getFacing(): Angle {
		return $this->angle;
	}
	
	// Unit vector in the direction this body is facing. This represents the scale
	// and transform that should be applied to the sprite before drawing it.
	public function getUnit(): Point {
		return $this->angle->getUnit() * (.5 * $this->getZoom());
	}
	
	// Zoom factor. This controls how big the sprite should be drawn.
	public function getZoom(): float {
		return max($this->zoom, 0.0);
	}
	
	public function getScale(): float {
		return floatval($this->scale);
	}
	
	public function getFrameOffset(): int {
		return $this->frameOffset;
	}
	
	public function getRewind(): bool {
		return $this->rewind;
	}
	
	// Check if this object is marked for removal from the game.
	public function getShouldBeRemoved(): bool {
		return $this->shouldBeRemoved;
	}
	
	// Store the government here too, so that collision detection that is based
	// on the Body class can figure out which objects will collide.
	public function getGovernment(): Government {
		return $this->government;
	}
	
	// Load the sprite specification, including all animation attributes.
	public function loadSprite(DataNode $node) {
		if ($node->size() < 2) {
			return;
		}
		$this->sprite = SpriteSet::Get($node->getToken(1));
	
		// The only time the animation does not start on a specific frame is if no
		// start frame is specified and it repeats. Since a frame that does not
		// start at zero starts when the game started, it does not make sense for it
		// to do that unless it is repeating endlessly.
		foreach ($node as $child) {
			if ($child->getToken(0) == "frame rate" && $child->size() >= 2 && $child->getValue(1) >= 0.) {
				$this->frameRate = $child->getValue(1) / 60.;
			} else if ($child->getToken(0) == "frame time" && $child->size() >= 2 && $child->getValue(1) > 0.) {
				$this->frameRate = 1. / $child->getValue(1);
			} else if ($child->getToken(0) == "delay" && $child->size() >= 2 && $child->getValue(1) > 0.) {
				$this->delay = $child->getValue(1);
			} else if ($child->getToken(0) == "scale" && $child->size() >= 2 && $child->getValue(1) > 0.) {
				$this->scale = floatval($child->getValue(1));
			} else if ($child->getToken(0) == "start frame" && $child->size() >= 2) {
				$this->frameOffset += floatval($child->getValue(1));
				$this->startAtZero = true;
			} else if ($child->getToken(0) == "random start frame") {
				$this->randomize = true;
			} else if ($child->getToken(0) == "no repeat") {
				$this->repeat = false;
				$this->startAtZero = true;
			} else if ($child->getToken(0) == "rewind") {
				$this->rewind = true;
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	
		if ($this->scale != 1.0) {
//			GameData::GetMaskManager()->registerScale($this->sprite, $this->getScale());
		}
	}
	
	// Save the sprite specification, including all animation attributes.
	public function saveSprite(DataWriter $out, string $tag=''): void {
		if (!$this->sprite) {
			return;
		}
		
		if (!$tag) {
			$tag = 'sprite';
		}
	
		$out->write([$tag, $this->sprite->getName()]);
		$out->beginChild();
		//{
			if ($this->frameRate != (2.0 / 60.0)) {
				$out->write(["frame rate", $this->frameRate * 60.0]);
			}
			if ($this->delay) {
				$out->write(["delay", $this->delay]);
			}
			if ($this->scale != 1.0) {
				$out->write(["scale", $this->scale]);
			}
			if ($this->randomize) {
				$out->write("random start frame");
			}
			if (!$this->repeat) {
				$out->write("no repeat");
			}
			if ($this->rewind) {
				$out->write("rewind");
			}
		//}
		$out->endChild();
	}
	
	
	
	// Set the sprite.
	public function setSprite(Sprite $sprite): void {
		$this->sprite = $sprite;
		$this->currentStep = -1;
	}
	
	// Set the color swizzle.
	public function setSwizzle(int $swizzle): void {
		$this->swizzle = $swizzle;
	}
	
	public function getFrameRate(): float {
		return $this->frameRate;
	}
	
	// Set the frame rate of the sprite. This is used for objects that just specify
	// a sprite instead of a full animation data structure.
	public function setFrameRate(float $framesPerSecond) {
		$this->frameRate = $framesPerSecond / 60.0;
	}
	
	// Add the given amount to the frame rate.
	public function addFrameRate(float $framesPerSecond): void {
		$this->frameRate += $framesPerSecond / 60.0;
	}
	
	public function pauseAnimation(): void {
		$this->pause++;
	}
	
	// Mark this object to be removed from the game.
	public function markForRemoval(): void {
		$this->shouldBeRemoved = true;
	}
	
	// Mark this object to not be removed from the game.
	public function unmarkForRemoval(): void {
		$this->shouldBeRemoved = false;
	}
	
	// Set the current time step.
	public function setStep(int $step): void {
		// If the animation is paused, reduce the step by however many frames it has
		// been paused for.
		$step -= $this->pause;
	
		// If the step is negative or there is no sprite, do nothing. This updates
		// and caches the mask and the frame so that if further queries are made at
		// this same time step, we don't need to redo the calculations.
		if ($step == $this->currentStep || $step < 0 || !$this->sprite || $this->sprite->getFrames() == 0) {
			return;
		}
		$this->currentStep = $step;
	
		// If the sprite only has one frame, no need to animate anything.
		$frames = $this->sprite->getFrames();
		if ($frames <= 1.0) {
			$this->frame = 0.0;
			return;
		}
		$lastFrame = $frames - 1.0;
		// This is the number of frames per full cycle. If rewinding, a full cycle
		// includes the first and last frames once and every other frame twice.
		$cycle = ($this->rewind ? 2.0 * $lastFrame : $frames) + $this->delay;
	
		// If this is the very first step, fill in some values that we could not set
		// until we knew the sprite's frame count and the starting step.
		if ($this->randomize) {
			$this->randomize = false;
			// The random offset can be a fractional frame.
			$this->frameOffset += (mt_rand() / mt_getrandmax()) * $cycle;
		} else if ($this->startAtZero) {
			$this->startAtZero = false;
			// Adjust frameOffset so that this step's frame is exactly 0 (no fade).
			$this->frameOffset -= $this->frameRate * $step;
		}
	
		// Figure out what fraction of the way in between frames we are. Avoid any
		// possible floating-point glitches that might result in a negative frame.
		$this->frame = max(0.0, $this->frameRate * $step + $this->frameOffset);
		// If repeating, wrap the frame index by the total cycle time.
		if ($this->repeat) {
			$this->frame = fmod($this->frame, $cycle);
		}
	
		if (!$this->rewind) {
			// If not repeating, frame should never go higher than the index of the
			// final frame.
			if (!$this->repeat) {
				$this->frame = min($this->frame, $this->lastFrame);
			} else if ($this->frame >= $this->frames) {
				// If we're in the delay portion of the loop, set the frame to 0.
				$this->frame = 0.0;
			}
		} else if ($this->frame >= $this->lastFrame) {
			// In rewind mode, once you get to the last frame, count backwards.
			// Regardless of whether we're repeating, if the frame count gets to
			// be less than 0, clamp it to 0.
			$this->frame = max(0.0, $this->lastFrame * 2.0 - $this->frame);
		}
	}
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->positionStr = json_encode($this->position);
		$this->velocityStr = json_encode($this->velocity);
		$this->angleDegrees = $this->angle->getDegrees();
	}

	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$positionArray = json_decode($this->positionStr, true);
		$this->position = new Point($positionArray['x'], $positionArray['y']);
		$velocityArray = json_decode($this->velocityStr, true);
		$this->velocity = new Point($velocityArray['x'], $velocityArray['y']);
		$this->angle = new Angle($this->angleDegrees);
	}
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		$jsonArray['subclass'] = self::class;
		$jsonArray['zoom'] = $this->zoom;
		$jsonArray['scale'] = $this->scale;
		$jsonArray['spriteId'] = $this->sprite?->getId();
		$jsonArray['swizzle'] = $this->swizzle;
		$jsonArray['frameRate'] = $this->frameRate;
		$jsonArray['delay'] = $this->delay;
		$jsonArray['frameOffset'] = $this->frameOffset;
		$jsonArray['startAtZero'] = $this->startAtZero;
		$jsonArray['randomize'] = $this->randomize;
		$jsonArray['rewind'] = $this->rewind;
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
	
	public function setFromJSON(string|array $jsonArray): void {
		if (!is_array($jsonArray)) {
			$jsonArray = json_decode($jsonArray, true);
		}
		
		$this->id = $jsonArray['id'];
		$this->zoom = $jsonArray['zoom'];
		$this->scale = $jsonArray['scale'];
		if ($jsonArray['sprite']) {
			$this->sprite = SpriteSet::Get($jsonArray['sprite']['name']);
		}
		$this->swizzle = $jsonArray['swizzle'];
		$this->frameRate = $jsonArray['frameRate'];
		$this->delay = $jsonArray['delay'];
		$this->frameOffset = $jsonArray['frameOffset'];
		$this->startAtZero = $jsonArray['startAtZero'];
		$this->randomize = $jsonArray['randomize'];
		$this->rewind = $jsonArray['rewind'];
	}

}