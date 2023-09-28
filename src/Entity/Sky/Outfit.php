<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;
use App\Entity\Sky\OutfitAttributes;

#[ORM\Entity]
#[ORM\Table(name: 'Outfit')]
#[ORM\HasLifecycleCallbacks]
class Outfit extends Weapon {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	protected int $id;
	
	#[ORM\Column(type: 'boolean', name: 'isDefined')]
	protected bool $isDefined = false;
	
	#[ORM\Column(type: 'string', name: 'trueName')]
	protected string $trueName = '';
	
	#[ORM\Column(type: 'string', name: 'displayName')]
	protected string $displayName = '';
	
	#[ORM\Column(type: 'string', name: 'pluralName')]
	protected string $pluralName = '';
	
	#[ORM\Column(type: 'string', name: 'category')]
	protected string $category = '';
	
	// The series that this outfit is a part of and its index within that series.
	// Used for sorting within shops.
	#[ORM\Column(type: 'string', name: 'series')]
	protected string $series = '';
	
	#[ORM\Column(type: 'integer', name: 'seriesIndex')]
	protected int $index = -1;
	
	#[ORM\Column(type: 'text', name: 'description')]
	protected string $description = '';
	
	#[ORM\Column(type: 'bigint', name: 'cost')]
	protected int $cost = 0;
	
	#[ORM\Column(type: 'float', name: 'mass')]
	protected float $mass = 0.;
	
	// Licenses needed to purchase this item.
	protected array $licenses = []; //vector<string>
	
	#[ORM\OneToMany(mappedBy: 'outfit', targetEntity: FlareSprite::class, orphanRemoval: true, cascade: ['persist'])]
	protected Collection $flareSpriteCollection;
	// The integers in these pairs/maps indicate the number of
	// sprites/effects/sounds to be placed/played.
	protected array $flareSprites = []; // vector<pair<Body, int>>
	protected array $reverseFlareSprites = []; // vector<pair<Body, int>>
	protected array $steeringFlareSprites = []; // vector<pair<Body, int>>
	
	#[ORM\OneToMany(mappedBy: 'outfit', targetEntity: FlareSound::class, orphanRemoval: true, cascade: ['persist'])]
	protected Collection $flareSoundCollection;
	// These maps are changed to arrays with the name as the key, and the value being ['val'=>Sound or Effect, 'count'=>int]
	protected array $flareSounds = []; // map<const Sound *, int>
	protected array $reverseFlareSounds = []; // map<const Sound *, int>
	protected array $steeringFlareSounds = []; // map<const Sound *, int>
	
	#[ORM\OneToMany(mappedBy: 'outfit', targetEntity: OutfitEffect::class, orphanRemoval: true, cascade: ['persist'])]
	protected Collection $effectCollection;
	protected array $afterburnerEffects = []; // map<const Effect *, int>
	protected array $jumpEffects = []; // map<const Effect *, int>
	
	#[ORM\OneToMany(mappedBy: 'outfit', targetEntity: JumpSound::class, orphanRemoval: true, cascade: ['persist'])]
	protected Collection $jumpSoundCollection;
	protected array $hyperSounds = []; // map<const Sound *, int>
	protected array $hyperInSounds = []; // map<const Sound *, int>
	protected array $hyperOutSounds = []; // map<const Sound *, int>
	protected array $jumpSounds = []; // map<const Sound *, int>
	protected array $jumpInSounds = []; // map<const Sound *, int>
	protected array $jumpOutSounds = []; // map<const Sound *, int>
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sprite', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'thumbnailId')]
	protected ?Sprite $thumbnail = null;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sprite', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'flotsamId')]
	protected ?Sprite $flotsamSprite = null;

    #[ORM\OneToMany(mappedBy: 'outfit', targetEntity: OutfitPenalty::class, orphanRemoval: true, cascade: ['persist'])]
    protected Collection $penalties;

    #[ORM\OneToMany(mappedBy: 'outfit', targetEntity: OutfitAttributes::class, orphanRemoval: true, cascade: ['persist'])]
    protected Collection $outfitAttributes;
	protected array $attributes = [];

    #[ORM\ManyToMany(targetEntity: Sale::class, mappedBy: 'outfits')]
    private Collection $outfitters;
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		if ($this->isWeapon) {
			parent::toDatabase($eventArgs);
		}
		$em = $eventArgs->getObjectManager();
		$handledAttributes = [];
		foreach ($this->outfitAttributes as $OutfitAttribute) {
			$handled = false;
			if (isset($this->attributes[$OutfitAttribute->getName()])) {
				$OutfitAttribute->setValue($this->attributes[$OutfitAttribute->getName()]);
				$handled = true;
				$handledAttributes []= $OutfitAttribute->getName();
			}
			if (!$handled) {
				$em->remove($OutfitAttribute);
			}
		}
		foreach ($this->attributes as $name => $val) {
			if (in_array($name, $handledAttributes)) {
				continue;
			}
			$OutfitAttribute = new OutfitAttributes();
			$OutfitAttribute->setOutfit($this);
			$OutfitAttribute->setName($name);
			$OutfitAttribute->setValue($val);
			$this->outfitAttributes []= $OutfitAttribute;
		}
		$flareSpriteMap = ['thrust'=>'flareSprites', 'reverse'=>'reverseFlareSprites', 'steering'=>'steeringFlareSprites'];
		foreach ($this->flareSpriteCollection as $fsId => $FlareSprite) {
			$handled = false;
			$type = $flareSpriteMap[$FlareSprite->getType()];
			foreach ($this->$type as $flareSpriteData) {
				if ($FlareSprite->getSprite() == $flareSpriteData['body']) {
					$FlareSprite->setCount($flareSpriteData['count']);
					$handled = true;
				}
			}
			if (!$handled) {
				$em->remove($FlareSprite);
				$FlareSprite = null;
			}
		}
		foreach ($flareSpriteMap as $typeName => $arrayName) {
			foreach ($this->$arrayName as $flareSpriteData) {
				$FlareSprite = new FlareSprite();
				$FlareSprite->setOutfit($this);
				$FlareSprite->setType($typeName);
				$FlareSprite->setSprite($flareSpriteData['body']);
				$FlareSprite->setCount($flareSpriteData['count']);
				$this->flareSpriteCollection []= $FlareSprite;
			}
		}
		$flareSoundMap = ['thrust'=>'flareSounds', 'reverse'=>'reverseFlareSounds', 'steering'=>'steeringFlareSounds'];
		foreach ($this->flareSoundCollection as $fsId => $FlareSound) {
			$handled = false;
			$type = $flareSoundMap[$FlareSound->getType()];
			foreach ($this->$type as $flareSoundData) {
				if ($FlareSound->getSound() == $flareSoundData['val']) {
					$FlareSound->setCount($flareSoundData['count']);
					$handled = true;
				}
			}
			if (!$handled) {
				$em->remove($FlareSound);
			}
		}
		foreach ($flareSoundMap as $typeName => $arrayName) {
			foreach ($this->$arrayName as $flareSoundData) {
				$FlareSound = new FlareSound();
				$FlareSound->setOutfit($this);
				$FlareSound->setType($typeName);
				$FlareSound->setSound($flareSoundData['val']);
				$FlareSound->setCount($flareSoundData['count']);
				$this->flareSoundCollection []= $FlareSound;
			}
		}
		foreach ($this->effectCollection as $oeId => $OutfitEffect) {
			$handled = false;
			$type = $OutfitEffect->getType().'Effects';
			foreach ($this->$type as $effectData) {
				if ($OutfitEffect->getEffect() == $effectData['val']) {
					$OutfitEffect->setCount($effectData['count']);
					$handled = true;
				}
			}
			if (!$handled) {
				$em->remove($OutfitEffect);
			}
		}
		foreach (['afterburner','jump'] as $typeName) {
			$arrayName = $typeName.'Effects';
			foreach ($this->$arrayName as $effectData) {
				$OutfitEffect = new OutfitEffect();
				$OutfitEffect->setOutfit($this);
				$OutfitEffect->setType($typeName);
				$OutfitEffect->setEffect($effectData['val']);
				$OutfitEffect->setCount($effectData['count']);
				$this->effectCollection []= $OutfitEffect;
			}
		}
		$jumpSoundMap = ['jump'=>'jumpSounds', 'jumpIn'=>'jumpInSounds', 'jumpOut'=>'jumpOutSounds', 'hyper'=>'hyperSounds', 'hyperIn'=>'hyperInSounds', 'hyperOut'=>'hyperOutSounds'];
		foreach ($this->jumpSoundCollection as $jsId => $JumpSound) {
			$handled = false;
			$type = $jumpSoundMap[$JumpSound->getType()];
			foreach ($this->$type as $jumpSoundData) {
				if ($JumpSound->getSound() == $jumpSoundData['val']) {
					$JumpSound->setCount($jumpSoundData['count']);
					$handled = true;
				}
			}
			if (!$handled) {
				$em->remove($JumpSound);
			}
		}
		foreach ($jumpSoundMap as $typeName => $arrayName) {
			foreach ($this->$arrayName as $jumpSoundData) {
				$JumpSound = new JumpSound();
				$JumpSound->setOutfit($this);
				$JumpSound->setType($typeName);
				$JumpSound->setSound($jumpSoundData['val']);
				$JumpSound->setCount($jumpSoundData['count']);
				$this->jumpSoundCollection []= $JumpSound;
			}
		}
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		if ($this->isWeapon) {
			parent::fromDatabase($eventArgs);
		}
		foreach ($this->outfitAttributes as $OutfitAttribute) {
			$this->attributes[$OutfitAttribute->getName()] = $OutfitAttribute->getValue();
		}
		$flareSpriteMap = ['thrust'=>'flareSprites', 'reverse'=>'reverseFlareSprites', 'steering'=>'steeringFlareSprites'];
		foreach ($this->flareSpriteCollection as $FlareSprite) {
			$type = $flareSpriteMap[$FlareSprite->getType()];
			$this->$type []= ['body'=>$FlareSprite->getSprite(), 'count'=>$FlareSprite->getCount()];
		}
		$flareSoundMap = ['thrust'=>'flareSounds', 'reverse'=>'reverseFlareSounds', 'steering'=>'steeringFlareSounds'];
		foreach ($this->flareSoundCollection as $FlareSound) {
			$type = $flareSoundMap[$FlareSound->getType()];
			$this->$type []= ['val'=>$FlareSound->getSound(), 'count'=>$FlareSound->getCount()];
		}
		foreach ($this->effectCollection as $OutfitEffect) {
			$type = $OutfitEffect->getType().'Effects';
			$this->$type []= ['val'=>$OutfitEffect->getEffect(), 'count'=>$OutfitEffect->getCount()];
		}
		foreach ($this->jumpSoundCollection as $JumpSound) {
			$type = $JumpSound->getType().'Sounds';
			$this->$type []= ['val'=>$JumpSound->getSound(), 'count'=>$JumpSound->getCount()];
		}
	}

	const EPS = 0.0000000001;
	
		// A mapping of attribute names to specifically-allowed minimum values. Based on the
		// specific usage of the attribute, the allowed minimum value is chosen to avoid
		// disallowed or undesirable behaviors (such as dividing by zero).
	const MINIMUM_OVERRIDES = [
	    // Attributes which are present and map to zero may have any value.
	    "shield generation" => 0.0,
	    "shield energy" => 0.0,
	    "shield fuel" => 0.0,
	    "shield heat" => 0.0,
	    "hull repair rate" => 0.0,
	    "hull energy" => 0.0,
	    "hull fuel" => 0.0,
	    "hull heat" => 0.0,
	    "hull threshold" => 0.0,
	    "energy generation" => 0.0,
	    "energy consumption" => 0.0,
	    "fuel generation" => 0.0,
	    "fuel consumption" => 0.0,
	    "fuel energy" => 0.0,
	    "fuel heat" => 0.0,
	    "heat generation" => 0.0,
	    "flotsam chance" => 0.0,
	    
	    "thrusting shields" => 0.0,
	    "thrusting hull" => 0.0,
	    "thrusting energy" => 0.0,
	    "thrusting fuel" => 0.0,
	    "thrusting heat" => 0.0,
	    "thrusting discharge" => 0.0,
	    "thrusting corrosion" => 0.0,
	    "thrusting ion" => 0.0,
	    "thrusting leakage" => 0.0,
	    "thrusting burn" => 0.0,
	    "thrusting disruption" => 0.0,
	    "thrusting slowing" => 0.0,
	    
	    "turning shields" => 0.0,
	    "turning hull" => 0.0,
	    "turning energy" => 0.0,
	    "turning fuel" => 0.0,
	    "turning heat" => 0.0,
	    "turning discharge" => 0.0,
	    "turning corrosion" => 0.0,
	    "turning ion" => 0.0,
	    "turning leakage" => 0.0,
	    "turning burn" => 0.0,
	    "turning disruption" => 0.0,
	    "turning slowing" => 0.0,
	    
	    "reverse thrusting shields" => 0.0,
	    "reverse thrusting hull" => 0.0,
	    "reverse thrusting energy" => 0.0,
	    "reverse thrusting fuel" => 0.0,
	    "reverse thrusting heat" => 0.0,
	    "reverse thrusting discharge" => 0.0,
	    "reverse thrusting corrosion" => 0.0,
	    "reverse thrusting ion" => 0.0,
	    "reverse thrusting leakage" => 0.0,
	    "reverse thrusting burn" => 0.0,
	    "reverse thrusting disruption" => 0.0,
	    "reverse thrusting slowing" => 0.0,
	    
	    "afterburner shields" => 0.0,
	    "afterburner hull" => 0.0,
	    "afterburner energy" => 0.0,
	    "afterburner fuel" => 0.0,
	    "afterburner heat" => 0.0,
	    "afterburner discharge" => 0.0,
	    "afterburner corrosion" => 0.0,
	    "afterburner ion" => 0.0,
	    "afterburner leakage" => 0.0,
	    "afterburner burn" => 0.0,
	    "afterburner disruption" => 0.0,
	    "afterburner slowing" => 0.0,
	    
	    "cooling energy" => 0.0,
	    "discharge resistance energy" => 0.0,
	    "discharge resistance fuel" => 0.0,
	    "discharge resistance heat" => 0.0,
	    "corrosion resistance energy" => 0.0,
	    "corrosion resistance fuel" => 0.0,
	    "corrosion resistance heat" => 0.0,
	    "ion resistance energy" => 0.0,
	    "ion resistance fuel" => 0.0,
	    "ion resistance heat" => 0.0,
	    "scramble resistance energy" => 0.0,
	    "scramble resistance fuel" => 0.0,
	    "scramble resistance heat" => 0.0,
	    "leak resistance energy" => 0.0,
	    "leak resistance fuel" => 0.0,
	    "leak resistance heat" => 0.0,
	    "burn resistance energy" => 0.0,
	    "burn resistance fuel" => 0.0,
	    "burn resistance heat" => 0.0,
	    "disruption resistance energy" => 0.0,
	    "disruption resistance fuel" => 0.0,
	    "disruption resistance heat" => 0.0,
	    "slowing resistance energy" => 0.0,
	    "slowing resistance fuel" => 0.0,
	    "slowing resistance heat" => 0.0,
	    "crew equivalent" => 0.0,
	    
	    // "Protection" attributes appear in denominators and are incremented by 1.
	    "shield protection" => -0.99,
	    "hull protection" => -0.99,
	    "energy protection" => -0.99,
	    "fuel protection" => -0.99,
	    "heat protection" => -0.99,
	    "piercing protection" => -0.99,
	    "force protection" => -0.99,
	    "discharge protection" => -0.99,
	    "drag reduction" => -0.99,
	    "corrosion protection" => -0.99,
	    "inertia reduction" => -0.99,
	    "ion protection" => -0.99,
	    "scramble protection" => -0.99,
	    "leak protection" => -0.99,
	    "burn protection" => -0.99,
	    "disruption protection" => -0.99,
	    "slowing protection" => -0.99,
	    
	    // "Multiplier" attributes appear in numerators and are incremented by 1.
	    "hull repair multiplier" => -1.0,
	    "hull energy multiplier" => -1.0,
	    "hull fuel multiplier" => -1.0,
	    "hull heat multiplier" => -1.0,
	    "shield generation multiplier" => -1.0,
	    "shield energy multiplier" => -1.0,
	    "shield fuel multiplier" => -1.0,
	    "shield heat multiplier" => -1.0
	];
	
	public static function AddFlareSprites(array /*vector<pair<Body, int>>*/ &$thisFlares, array /*pair<Body, int>*/ $it, int $count): void {
	    $flare = null;
	    foreach ($thisFlares as $flareData) {
        	if ($flareData['body']->getSprite() == $it['body']->getSprite()) {
        		$flareData['count'] += $count * $it['count'];
        		return;
        	}
	    }
	    
	    $thisFlares []= ['body'=>$it['body'], 'count'=>$it['count'] * $count];
	}
	
	// Used to add the contents of one outfit's map to another, while also
	// erasing any key with a value of zero.
	public static function MergeMaps(array &$thisMap, array &$otherMap, int $count): void {
	    foreach ($otherMap as $otherName => $otherData) {
        	if (!isset($thisMap[$otherName])) {
        		$thisMap[$otherName] = ['val'=>$otherData['val'], 'count'=>$otherData['count'] * $count];
        	} else {
        		$thisMap[$otherName]['count'] += $otherData['count'] * $count;
        	}
        	// ?? I don't entirely understand what the original was doing here; it seems like it would only ever trigger 
        	//	if an entry was made in these maps with a null pointer as its key
        	if ($thisMap[$otherName]['val'] == null) {
        		unset($thisMap[$otherName]);
        	}
	    }
	}
	
	public function __construct(?DataNode $node = null) {
	    parent::__construct();
	    if ($node) {
        	$this->load($node);
	    }
		$this->penalties = new ArrayCollection();
		$this->outfitAttributes = new ArrayCollection();
		$this->flareSpriteCollection = new ArrayCollection();
		$this->flareSoundCollection = new ArrayCollection();
		$this->effectCollection = new ArrayCollection();
		$this->jumpSoundCollection = new ArrayCollection();
                 $this->outfitters = new ArrayCollection();
	}
	
	public function load(DataNode $node): void {
	    if ($node->size() >= 2) {
        	$this->trueName = $node->getToken(1);
	    }
		if ($node->getSourceName()) {
			$this->sourceName = $node->getSourceName();
			$this->sourceFile = $node->getSourceFile();
			$this->sourceVersion = $node->getSourceVersion();
		}
		
		// if ($this->trueName == 'attributes') {
		// 	error_log('Found the attributes outfit!');
		// }
	
	    $this->isDefined = true;
	
	    foreach ($node as $child) {
        	if ($child->getToken(0) == "display name" && $child->size() >= 2) {
        		$this->displayName = $child->getToken(1);
        	} else if ($child->getToken(0) == "category" && $child->size() >= 2) {
        		$this->category = $child->getToken(1);
        	} else if ($child->getToken(0) == "series" && $child->size() >= 2) {
        		$this->series = $child->getToken(1);
        	} else if ($child->getToken(0) == "index" && $child->size() >= 2) {
        		$this->index = $child->getValue(1);
        	} else if ($child->getToken(0) == "plural" && $child->size() >= 2) {
        		$this->pluralName = $child->getToken(1);
        	} else if ($child->getToken(0) == "flare sprite" && $child->size() >= 2) {
        		$flareSprite = ['body'=>new Body(), 'count'=>1];
        		$this->flareSprites []= $flareSprite;
        		$flareSprite['body']->loadSprite($child);
        	} else if ($child->getToken(0) == "reverse flare sprite" && $child->size() >= 2) {
        		$reverseFlareSprite = ['body'=>new Body(), 'count'=>1];
        		$this->reverseFlareSprites []= $reverseFlareSprite;
        		$reverseFlareSprite['body']->loadSprite($child);
        	} else if ($child->getToken(0) == "steering flare sprite" && $child->size() >= 2) {
        		$steeringFlareSprite = ['body'=>new Body(), 'count'=>1];
        		$this->steeringFlareSprites []= $steeringFlareSprite;
        		$steeringFlareSprite['body']->loadSprite($child);
        	} else if ($child->getToken(0) == "flare sound" && $child->size() >= 2) {
        		$soundName = $child->getToken(1);
        		if (!isset($this->flareSounds[$soundName])) {
        			// $this->flareSounds[$soundName] = ['val'=>Audio::Get($soundName), 'count'=>1];
        		} else {
        			$this->flareSounds[$soundName]['count']++;
        		}
        	} else if ($child->getToken(0) == "reverse flare sound" && $child->size() >= 2) {
        		$soundName = $child->getToken(1);
        		if (!isset($this->reverseFlareSounds[$soundName])) {
        			// $this->reverseFlareSounds[$soundName] = ['val'=>Audio::Get($soundName), 'count'=>1];
        		} else {
        			$this->reverseFlareSounds[$soundName]['count']++;
        		}
        	} else if ($child->getToken(0) == "steering flare sound" && $child->size() >= 2) {
        		$soundName = $child->getToken(1);
        		if (!isset($this->steeringFlareSounds[$soundName])) {
        			// $this->steeringFlareSounds[$soundName] = ['val'=>Audio::Get($soundName), 'count'=>1];
        		} else {
        			$this->steeringFlareSounds[$soundName]['count']++;
        		}
        	} else if ($child->getToken(0) == "afterburner effect" && $child->size() >= 2) {
        		$effectName = $child->getToken(1);
        		if (!isset($this->afterburnerEffects[$effectName])) {
        			$this->afterburnerEffects[$effectName] = ['val'=>GameData::Effects()[$effectName], 'count'=>1];
        		} else {
        			$this->afterburnerEffects[$effectName]['count']++;
        		}
        	} else if ($child->getToken(0) == "jump effect" && $child->size() >= 2) {
        		$effectName = $child->getToken(1);
        		if (!isset($this->jumpEffects[$effectName])) {
        			$this->jumpEffects[$effectName] = ['val'=>GameData::Effects()[$effectName], 'count'=>1];
        		} else {
        			$this->jumpEffects[$effectName]['count']++;
        		}
        	} else if ($child->getToken(0) == "hyperdrive sound" && $child->size() >= 2) {
        		$soundName = $child->getToken(1);
        		if (!isset($this->hyperSounds[$soundName])) {
        			//$this->hyperSounds[$soundName] = ['val'=>Audio::Get($soundName), 'count'=>1];
        		} else {
        			$this->hyperSounds[$soundName]['count']++;
        		}
        	} else if ($child->getToken(0) == "hyperdrive in sound" && $child->size() >= 2) {
        		$soundName = $child->getToken(1);
        		if (!isset($this->hyperInSounds[$soundName])) {
        			//$this->hyperInSounds[$soundName] = ['val'=>Audio::Get($soundName), 'count'=>1];
        		} else {
        			$this->hyperInSounds[$soundName]['count']++;
        		}
        	} else if ($child->getToken(0) == "hyperdrive out sound" && $child->size() >= 2) {
        		$soundName = $child->getToken(1);
        		if (!isset($this->hyperOutSounds[$soundName])) {
        			//$this->hyperOutSounds[$soundName] = ['val'=>Audio::Get($soundName), 'count'=>1];
        		} else {
        			$this->hyperOutSounds[$soundName]['count']++;
        		}
        	} else if ($child->getToken(0) == "jump sound" && $child->size() >= 2) {
        		$soundName = $child->getToken(1);
        		if (!isset($this->jumpSounds[$soundName])) {
        			//$this->jumpSounds[$soundName] = ['val'=>Audio::Get($soundName), 'count'=>1];
        		} else {
        			$this->jumpSounds[$soundName]['count']++;
        		}
        	} else if ($child->getToken(0) == "jump in sound" && $child->size() >= 2) {
        		$soundName = $child->getToken(1);
        		if (!isset($this->jumpInSounds[$soundName])) {
        			//$this->jumpInSounds[$soundName] = ['val'=>Audio::Get($soundName), 'count'=>1];
        		} else {
        			$this->jumpInSounds[$soundName]['count']++;
        		}
        	} else if ($child->getToken(0) == "jump out sound" && $child->size() >= 2) {
        		$soundName = $child->getToken(1);
        		if (!isset($this->jumpOutSounds[$soundName])) {
        			//$this->jumpOutSounds[$soundName] = ['val'=>Audio::Get($soundName), 'count'=>1];
        		} else {
        			$this->jumpOutSounds[$soundName]['count']++;
        		}
        	} else if ($child->getToken(0) == "flotsam sprite" && $child->size() >= 2) {
        		$this->flotsamSprite = SpriteSet::Get($child->getToken(1));
        	} else if ($child->getToken(0) == "thumbnail" && $child->size() >= 2) {
        		$this->thumbnail = SpriteSet::Get($child->getToken(1));
        	} else if ($child->getToken(0) == "weapon") {
        		$this->loadWeapon($child);
        	} else if ($child->getToken(0) == "ammo" && $child->size() >= 2) {
        		// Non-weapon outfits can have ammo so that storage outfits
        		// properly remove excess ammo when the storage is sold, instead
        		// of blocking the sale of the outfit until the ammo is sold first.
				$AmmoOutfit = GameData::Outfits()[$child->getToken(1)];
        		$this->ammo[$AmmoOutfit->getTrueName()] = 0;
        	} else if ($child->getToken(0) == "description" && $child->size() >= 2) {
        		$this->description .= $child->getToken(1);
        		$this->description .= "\n";
        	} else if ($child->getToken(0) == "cost" && $child->size() >= 2) {
        		$this->cost = $child->getValue(1);
        	} else if ($child->getToken(0) == "mass" && $child->size() >= 2) {
        		$this->mass = $child->getValue(1);
        	} else if ($child->getToken(0) == "licenses" && ($child->hasChildren() || $child->size() >= 2)) {
        		// Add any new licenses that were specified "inline".
        		if ($child->size() >= 2) {
        			foreach ($child->getTokens() as $token) {
        				if (!in_array($token, $this->licenses)) {
        					$this->licenses []= $token;
        				}
        			}
        		}
        		
        		// Add any new licenses that were specified as an indented list.
        		foreach ($child as $grand) {
        			if (!in_array($grand->getToken(0), $this->licenses)) {
        				$this->licenses []= $grand->getToken(0);
        			}
        		}
        	} else if ($child->getToken(0) == "jump range" && $child->size() >= 2) {
        		// Jump range must be positive.
        		$this->attributes[$child->getToken(0)] = max(0.0, $child->getValue(1));
        	} else if ($child->size() >= 2) {
				// if ($child->getToken(0) == "hull") {
				// 	error_log('Found a hull attribute for '.$this->trueName.' at '.$child->getValue(1));
				// }
        		$this->attributes[$child->getToken(0)] = $child->getValue(1);
        	} else {
        		$child->printTrace("Skipping unrecognized attribute:");
        	}
	    }
	
	    if ($this->displayName == '') {
        	$this->displayName = $this->trueName;
	    }
	
	    // If no plural name has been defined, append an 's' to the name and use that.
	    // If the name ends in an 's' or 'z', and no plural name has been defined, print a
	    // warning since an explicit plural name is always required in this case.
	    // Unless this outfit definition isn't declared with the `outfit` keyword,
	    // because then this is probably being done in `add attributes` on a ship,
	    // so the name doesn't matter.
	    if ($this->displayName != '' && $this->pluralName == '') {
        	$this->pluralName = $this->displayName . 's';
        	if ((substr($this->displayName, -1) == 's' || substr($this->displayName, -1) == 'z') && $node->getToken(0) == "outfit") {
        		$node->printTrace("Warning: explicit plural name definition required, but none is provided. Defaulting to \"" . $this->pluralName . "\".");
        	}
	    }
	
	    // Only outfits with the jump drive and jump range attributes can
	    // use the jump range, so only keep track of the jump range on
	    // viable outfits.
	    // if (isset($this->attributes["jump drive"]) && isset($this->attributes["jump range"])) {
        // 	GameData::AddJumpRange($this->attributes["jump range"]);
	    // }
	
	    // Legacy support for turrets that don't specify a turn rate:
	    if ($this->isWeapon() && isset($this->attributes["turret mounts"]) && !$this->getTurretTurn() && !$this->getAntiMissile()) {
        	$this->setTurretTurn(4.0);
        	$node->printTrace("Warning: Deprecated use of a turret without specified \"turret turn\":");
	    }
	    // Convert any legacy cargo / outfit scan definitions into power & speed,
	    // so no runtime code has to check for both.
	    $this->convertScan("outfit", $node);
	    $this->convertScan("cargo", $node);
	}
	
	public function copy(): Outfit {
		$Copy = new Outfit();
		parent::copyTo($Copy);
		$Copy->isDefined = $this->isDefined;
		$Copy->trueName = $this->trueName.' Copy';
		$Copy->displayName = $this->displayName;
		$Copy->pluralName = $this->pluralName;
		$Copy->category = $this->category;
		$Copy->series = $this->series;
		$Copy->index = $this->index;
		$Copy->description = $this->description;
		$Copy->cost = $this->cost;
		$Copy->mass = $this->mass;
		$Copy->flareSprites = $this->flareSprites;
		$Copy->reverseFlareSprites = $this->reverseFlareSprites;
		$Copy->steeringFlareSprites = $this->steeringFlareSprites;
		$Copy->flareSounds = $this->flareSounds;
		$Copy->reverseFlareSounds = $this->reverseFlareSounds;
		$Copy->steeringFlareSounds = $this->steeringFlareSounds;
		$Copy->afterburnerEffects = $this->afterburnerEffects;
		$Copy->jumpEffects = $this->jumpEffects;
		$Copy->hyperSounds = $this->hyperSounds;
		$Copy->hyperInSounds = $this->hyperInSounds;
		$Copy->hyperOutSounds = $this->hyperOutSounds;
		$Copy->thumbnail = $this->thumbnail;
		$Copy->flotsamSprite = $this->flotsamSprite;
		$Copy->attributes = [];
		foreach ($this->attributes as $attrName => $attrVal) {
			$Copy->attributes[$attrName] = $attrVal;
		}
		foreach ($this->penalties as $OutfitPenalty) {
			$Copy->penalties []= $OutfitPenalty;
		}
		
		return $Copy;
	}
	
	protected function convertScan($kind, $node): void {
	    $label = $kind . " scan";
	    $initial = 0.0;
	    if (isset($this->attributes[$label])) {
        	$initial = $this->attributes[$label];
        	$this->attributes[$label] = 0.;
        	$node->printTrace("Warning: Deprecated use of \"" . $label . "\" instead of \"" . $label . " power\" and \"" . $label . " speed\":");
	    
        	// A scan value of 300 is equivalent to a scan power of 9.
        	$this->attributes[$label + " power"] += $initial * $initial * .0001;
        	// The default scan speed of 1 is unrelated to the magnitude of the scan value.
        	// It may have been already specified, and if so, should not be increased.
        	if (!$this->attributes[$label + " efficiency"]) {
        		$this->attributes[$label + " efficiency"] = 15.;
        	}
	    }
	    
	    // Similar check for scan speed which is replaced with scan efficiency.
	    $label .= " speed";
	    $initial = 0.0;
	    if (isset($this->attributes[$label])) {
        	$initial = $this->attributes[$label];
        	$this->attributes[$label] = 0.;
        	$node->printTrace("Warning: Deprecated use of \"" . $label . "\" instead of \"" . $kind . " scan efficiency\":");
        	// A reasonable update is 15x the previous value, as the base scan time
        	// is 10x what it was before scan efficiency was introduced, along with
        	// ships which are larger or further away also increasing the scan time.
        	$this->attributes[$kind + " scan efficiency"] += $initial * 15.;
	    }
	}
	
	// Check if this outfit has been defined via Outfit::Load (vs. only being referred to).
	public function isDefined(): bool {
	    return $this->isDefined;
	}
	
	// When writing to the player's save, the reference name is used even if this
	// outfit was not fully defined (i.e. belongs to an inactive plugin).
	public function getTrueName(): string {
	    return $this->trueName;
	}
	public function setTrueName(string $trueName): void {
		$this->trueName = $trueName;
		if ($trueName == 'attributes') {
			error_log('Made an attributes outfit!');
		}
	}
	
	public function getDisplayName(): string {
	    return $this->displayName;
	}
	public function setDisplayName(string $displayName): void {
		$this->displayName = $displayName;
	}
	
	public function setName(string $name): void {
	    $this->trueName = $name;
	}
	
	public function getPluralName(): string {
	    return $this->pluralName;
	}
	public function setPluralName(string $pluralName): void {
		$this->pluralName = $pluralName;
	}
	
	public function getCategory(): string {
	    return $this->category;
	}
	
	public function getSeries(): string {
	    return $this->series;
	}
	
	public function getIndex(): int {
	    return $this->index;
	}
	
	public function Description(): string {
	    return $this->description;
	}
	
	// Get the licenses needed to purchase this outfit.
	public function Licenses(): array {
	    return $this->licenses;
	}
	
	// Get the image to display in the outfitter when buying this item.
	public function getThumbnail(): Sprite {
	    return $this->thumbnail;
	}
	
	public function get(string $attribute): float {
		if (isset($this->attributes[$attribute])) {
	        return $this->attributes[$attribute];
		} else if (isset($this->$attribute) && is_float($this->$attribute)) {
			return $this->$attribute;
		} else {
			return 0.0;
		}
	}
	
	public function getAttributes(): array {
	    return $this->attributes;
	}
	
	public function getCost(): int {
		return $this->cost;
	}
	
	public function getMass(): float {
		return $this->mass;
	}
	
	// Determine whether the given number of instances of the given outfit can
	// be added to a ship with the attributes represented by this instance. If
	// not, return the maximum number that can be added.
	public function canAdd(Outfit &$other, int $count): int {
	    foreach ($other->getAttributes() as $attrName => $attrVal) {
        	// The minimum allowed value of most attributes is 0. Some attributes
        	// have special functionality when negative, though, and are therefore
        	// allowed to have values less than 0.
        	$minimum = 0.0;
        	if (isset(Outfit::MINIMUM_OVERRIDES[$attrName])) {
        		$minimum = Outfit::MINIMUM_OVERRIDES[$attrName];
        		if ($minimum == 0.0) {
        			continue;
        		}
        	}
	
        	// Only automatons may have a "required crew" of 0.
        	if ($attrName == "required crew") {
        		$minimum = !($this->attributes["automaton"] != 0 || $other->getAttributes()["automaton"] != 0);
        	}
	
        	$value = $attrVal;
        	// Allow for rounding errors:
        	if ($value + $attrVal * $count < $minimum - Outfit::EPS) {
        		$count = ($value - $minimum) / -$attrVal + Outfit::EPS;
        	}
	    }
	
	    return $count;
	}
	
	// For tracking a combination of outfits in a ship: add the given number of
	// instances of the given outfit to this outfit.
	public function add(Outfit $other, int $count = 1): void {
	    $this->cost += $other->cost * $count;
	    $this->mass += $other->mass * $count;
	    foreach ($other->attributes as $attrName => $attrVal) {
        	if (!isset($this->attributes[$attrName])) {
        		$this->attributes[$attrName] = 0.0;
        	}
        	$this->attributes[$attrName] += $attrVal * $count;
        	if (abs($this->attributes[$attrName]) < Outfit::EPS) {
        		$this->attributes[$attrName] = 0.0;
        	}
	    }
	
	    foreach ($other->flareSprites as $flareData) {
        	Outfit::AddFlareSprites($this->flareSprites, $flareData, $count);
	    }
	    foreach ($other->reverseFlareSprites as $flareData) {
        	Outfit::AddFlareSprites($this->reverseFlareSprites, $flareData, $count);
	    }
	    foreach ($other->steeringFlareSprites as $flareData) {
        	Outfit::AddFlareSprites($this->steeringFlareSprites, $flareData, $count);
	    }
	    Outfit::MergeMaps($this->flareSounds, $other->flareSounds, $count);
	    Outfit::MergeMaps($this->reverseFlareSounds, $other->reverseFlareSounds, $count);
	    Outfit::MergeMaps($this->steeringFlareSounds, $other->steeringFlareSounds, $count);
	    Outfit::MergeMaps($this->afterburnerEffects, $other->afterburnerEffects, $count);
	    Outfit::MergeMaps($this->jumpEffects, $other->jumpEffects, $count);
	    Outfit::MergeMaps($this->hyperSounds, $other->hyperSounds, $count);
	    Outfit::MergeMaps($this->hyperInSounds, $other->hyperInSounds, $count);
	    Outfit::MergeMaps($this->hyperOutSounds, $other->hyperOutSounds, $count);
	    Outfit::MergeMaps($this->jumpSounds, $other->jumpSounds, $count);
	    Outfit::MergeMaps($this->jumpInSounds, $other->jumpInSounds, $count);
	    Outfit::MergeMaps($this->jumpOutSounds, $other->jumpOutSounds, $count);
	}
	
	// Modify this outfit's attributes.
	public function set(string $attribute, float $value): void {
	    $this->attributes[$attribute] = $value;
	}
	
	// Get this outfit's engine flare sprite, if any.
	public function getFlareSprites(): array {
	    return $this->flareSprites;
	}
	
	public function getReverseFlareSprites(): array {
	    return $this->reverseFlareSprites;
	}
	
	public function getSteeringFlareSprites(): array {
	    return $this->steeringFlareSprites;
	}
	
	public function getFlareSounds(): array {
	    return $this->flareSounds;
	}
	
	public function getReverseFlareSounds(): array {
	    return $this->reverseFlareSounds;
	}
	
	public function getSteeringFlareSounds(): array {
	    return $this->steeringFlareSounds;
	}
	
	// Get the afterburner effect, if any.
	public function getAfterburnerEffects(): array {
	    return $this->afterburnerEffects;
	}
	
	// Get this outfit's jump effects and sounds, if any.
	public function getJumpEffects(): array {
	    return $this->jumpEffects;
	}
	
	public function getHyperSounds(): array {
	    return $this->hyperSounds;
	}
	
	public function getHyperInSounds(): array {
	    return $this->hyperInSounds;
	}
	
	public function getHyperOutSounds(): array {
	    return $this->hyperOutSounds;
	}
	
	public function getJumpSounds(): array {
	    return $this->jumpSounds;
	}
	
	public function getJumpInSounds(): array {
	    return $this->jumpInSounds;
	}
	
	public function getJumpOutSounds(): array {
	    return $this->jumpOutSounds;
	}
	
	// Get the sprite this outfit uses when dumped into space.
	public function getFlotsamSprite(): Sprite {
	    return $this->flotsamSprite;
	}

    /**
     * @return Collection<int, OutfitPenalty>
     */
    public function getPenalties(): Collection
    {
        return $this->penalties;
    }

    public function addPenalty(OutfitPenalty $penalty): self
    {
        if (!$this->penalties->contains($penalty)) {
            $this->penalties->add($penalty);
            $penalty->setOutfit($this);
        }

        return $this;
    }

    public function removePenalty(OutfitPenalty $penalty): self
    {
        if ($this->penalties->removeElement($penalty)) {
            // set the owning side to null (unless already changed)
            if ($penalty->getOutfit() === $this) {
                $penalty->setOutfit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OutfitAttributes>
     */
    public function getOutfitAttributes(): Collection
    {
        return $this->outfitAttributes;
    }

    public function addOutfitAttribute(OutfitAttributes $outfitAttribute): self
    {
        if (!$this->outfitAttributes->contains($outfitAttribute)) {
            $this->outfitAttributes->add($outfitAttribute);
            $outfitAttribute->setOutfit($this);
        }

        return $this;
    }

    public function removeOutfitAttribute(OutfitAttributes $outfitAttribute): self
    {
        if ($this->outfitAttributes->removeElement($outfitAttribute)) {
            // set the owning side to null (unless already changed)
            if ($outfitAttribute->getOutfit() === $this) {
                $outfitAttribute->setOutfit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FlareSprite>
     */
    public function getFlareSpriteCollection(): Collection
    {
        return $this->flareSpriteCollection;
    }

    public function addFlareSpriteCollection(FlareSprite $flareSpriteCollection): self
    {
        if (!$this->flareSpriteCollection->contains($flareSpriteCollection)) {
            $this->flareSpriteCollection->add($flareSpriteCollection);
            $flareSpriteCollection->setOutfit($this);
        }

        return $this;
    }

    public function removeFlareSpriteCollection(FlareSprite $flareSpriteCollection): self
    {
        if ($this->flareSpriteCollection->removeElement($flareSpriteCollection)) {
            // set the owning side to null (unless already changed)
            if ($flareSpriteCollection->getOutfit() === $this) {
                $flareSpriteCollection->setOutfit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FlareSound>
     */
    public function getFlareSoundCollection(): Collection
    {
        return $this->flareSoundCollection;
    }

    public function addFlareSoundCollection(FlareSound $flareSoundCollection): self
    {
        if (!$this->flareSoundCollection->contains($flareSoundCollection)) {
            $this->flareSoundCollection->add($flareSoundCollection);
            $flareSoundCollection->setOutfit($this);
        }

        return $this;
    }

    public function removeFlareSoundCollection(FlareSound $flareSoundCollection): self
    {
        if ($this->flareSoundCollection->removeElement($flareSoundCollection)) {
            // set the owning side to null (unless already changed)
            if ($flareSoundCollection->getOutfit() === $this) {
                $flareSoundCollection->setOutfit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OutfitEffect>
     */
    public function getEffectCollection(): Collection
    {
        return $this->effectCollection;
    }

    public function addEffectCollection(OutfitEffect $effectCollection): self
    {
        if (!$this->effectCollection->contains($effectCollection)) {
            $this->effectCollection->add($effectCollection);
            $effectCollection->setOutfit($this);
        }

        return $this;
    }

    public function removeEffectCollection(OutfitEffect $effectCollection): self
    {
        if ($this->effectCollection->removeElement($effectCollection)) {
            // set the owning side to null (unless already changed)
            if ($effectCollection->getOutfit() === $this) {
                $effectCollection->setOutfit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, JumpSound>
     */
    public function getJumpSoundCollection(): Collection
    {
        return $this->jumpSoundCollection;
    }

    public function addJumpSoundCollection(JumpSound $jumpSoundCollection): self
    {
        if (!$this->jumpSoundCollection->contains($jumpSoundCollection)) {
            $this->jumpSoundCollection->add($jumpSoundCollection);
            $jumpSoundCollection->setOutfit($this);
        }

        return $this;
    }

    public function removeJumpSoundCollection(JumpSound $jumpSoundCollection): self
    {
        if ($this->jumpSoundCollection->removeElement($jumpSoundCollection)) {
            // set the owning side to null (unless already changed)
            if ($jumpSoundCollection->getOutfit() === $this) {
                $jumpSoundCollection->setOutfit(null);
            }
        }

        return $this;
    }
	
	public function toJSON(bool $justArray=false): string|array {
		if ($this->isWeapon) {
			$jsonArray = parent::toJSON(true);
		} else {
			$jsonArray = [];
		}
		
		$jsonArray['trueName'] = $this->trueName;
		$jsonArray['displayName'] = $this->displayName;
		$jsonArray['pluralName'] = $this->pluralName;
		$jsonArray['category'] = $this->category;
		$jsonArray['series'] = $this->series;
		$jsonArray['index'] = $this->index;
		$jsonArray['description'] = $this->description;
		$jsonArray['cost'] = $this->cost;
		$jsonArray['mass'] = $this->mass;
		
		$jsonArray['thumbnailId'] = $this->thumbnail?->getId();
		$jsonArray['flotsamSprite'] = $this->flotsamSprite?->getId();
		
		$jsonArray['attributes'] = $this->attributes;
		
		$jsonArray['outfitters'] = [];
		foreach ($this->outfitters as $Outfitter) {
			$jsonArray['outfitters'] []= $Outfitter->getName();
		}
		
		$jsonArray['flareSprites'] = [];
		foreach ($this->flareSprites as $flareSpriteData) {
			$jsonArray['flareSprites'] []= ['body'=>$flareSpriteData['body']->toJSON(true),'count'=>$flareSpriteData['count']];
		}
		$jsonArray['reverseFlareSprites'] = [];
		foreach ($this->reverseFlareSprites as $flareSpriteData) {
			$jsonArray['reverseFlareSprites'] []= ['body'=>$flareSpriteData['body']->toJSON(true),'count'=>$flareSpriteData['count']];
		}
		$jsonArray['steeringFlareSprites'] = [];
		foreach ($this->steeringFlareSprites as $flareSpriteData) {
			$jsonArray['steeringFlareSprites'] []= ['body'=>$flareSpriteData['body']->toJSON(true),'count'=>$flareSpriteData['count']];
		}
		
		$jsonArray['flareSounds'] = [];
		foreach ($this->flareSounds as $flareSoundData) {
			$jsonArray['flareSounds'] []= ['sound'=>$flareSoundData['val']->toJSON(true),'count'=>$flareSoundData['count']];
		}
		$jsonArray['reverseFlareSounds'] = [];
		foreach ($this->reverseFlareSounds as $flareSoundData) {
			$jsonArray['reverseFlareSounds'] []= ['sound'=>$flareSoundData['val']->toJSON(true),'count'=>$flareSoundData['count']];
		}
		$jsonArray['steeringFlareSounds'] = [];
		foreach ($this->steeringFlareSounds as $flareSoundData) {
			$jsonArray['steeringFlareSounds'] []= ['sound'=>$flareSoundData['val']->toJSON(true),'count'=>$flareSoundData['count']];
		}
		
		$jsonArray['jumpEffects'] = [];
		foreach ($this->jumpEffects as $effectData) {
			$jsonArray['jumpEffects'] []= ['effect'=>$effectData['val']->toJSON(true),'count'=>$effectData['count']];
		}
		$jsonArray['afterburnerEffects'] = [];
		foreach ($this->afterburnerEffects as $effectData) {
			$jsonArray['afterburnerEffects'] []= ['effect'=>$effectData['val']->toJSON(true),'count'=>$effectData['count']];
		}
		
		$jsonArray['hyperSounds'] = [];
		foreach ($this->hyperSounds as $hyperSoundData) {
			$jsonArray['hyperSounds'] []= ['sound'=>$hyperSoundData['val']->toJSON(true),'count'=>$hyperSoundData['count']];
		}
		$jsonArray['hyperInSounds'] = [];
		foreach ($this->hyperInSounds as $hyperSoundData) {
			$jsonArray['hyperInSounds'] []= ['sound'=>$hyperSoundData['val']->toJSON(true),'count'=>$hyperSoundData['count']];
		}
		$jsonArray['hyperOutSounds'] = [];
		foreach ($this->hyperOutSounds as $hyperSoundData) {
			$jsonArray['hyperOutSounds'] []= ['sound'=>$hyperSoundData['val']->toJSON(true),'count'=>$hyperSoundData['count']];
		}
		$jsonArray['jumpSounds'] = [];
		foreach ($this->jumpSounds as $jumpSoundData) {
			$jsonArray['jumpSounds'] []= ['sound'=>$jumpSoundData['val']->toJSON(true),'count'=>$jumpSoundData['count']];
		}
		$jsonArray['jumpInSounds'] = [];
		foreach ($this->jumpInSounds as $jumpSoundData) {
			$jsonArray['jumpInSounds'] []= ['sound'=>$jumpSoundData['val']->toJSON(true),'count'=>$jumpSoundData['count']];
		}
		$jsonArray['jumpOutSounds'] = [];
		foreach ($this->jumpOutSounds as $jumpSoundData) {
			$jsonArray['jumpOutSounds'] []= ['sound'=>$jumpSoundData['val']->toJSON(true),'count'=>$jumpSoundData['count']];
		}
		
		$jsonArray['source'] = ['name'=>$this->sourceName,'file'=>$this->sourceFile,'version'=>$this->sourceVersion];
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
	
	public function setFromJSON(string|array $jsonArray): void {
		if (!is_array($jsonArray)) {
			$jsonArray = json_decode($jsonArray, true);
		}
		
		$this->trueName = $jsonArray['trueName'];
		$this->displayName = $jsonArray['displayName'];
		$this->pluralName = $jsonArray['pluralName'];
		$this->category = $jsonArray['category'];
		$this->series = $jsonArray['series'];
		$this->index = $jsonArray['index'];
		$this->description = $jsonArray['description'];
		$this->cost = $jsonArray['cost'];
		$this->mass = $jsonArray['mass'];
		
		if ($jsonArray['thumbnail']) {
			$this->thumbnail = SpriteSet::Get($jsonArray['thumbnail']['name']);
		}
		if ($jsonArray['flotsamSprite']) {
			$this->flotsamSprite = SpriteSet::Get($jsonArray['flotsamSprite']['name']);
		}
		
		$this->attributes = $jsonArray['attributes'];
		
		$this->flareSprites = $jsonArray['flareSprites'];
		$this->reverseFlareSprites = $jsonArray['reverseFlareSprites'];
		$this->steeringFlareSprites = $jsonArray['steeringFlareSprites'];
		
		$this->flareSounds = $jsonArray['flareSounds'];
		$this->reverseFlareSounds = $jsonArray['reverseFlareSounds'];
		$this->steeringFlareSounds = $jsonArray['steeringFlareSounds'];
		
		$this->jumpEffects = $jsonArray['jumpEffects'];
		$this->afterburnerEffects = $jsonArray['afterburnerEffects'];
		
		$this->hyperSounds = $jsonArray['hyperSounds'];
		$this->hyperInSounds = $jsonArray['hyperInSounds'];
		$this->hyperOutSounds = $jsonArray['hyperOutSounds'];
		$this->jumpSounds = $jsonArray['jumpSounds'];
		$this->jumpInSounds = $jsonArray['jumpInSounds'];
		$this->jumpOutSounds = $jsonArray['jumpOutSounds'];
	}

    /**
     * @return Collection<int, Sale>
     */
    public function getOutfitters(): Collection
    {
        return $this->outfitters;
    }

    public function addOutfitter(Sale $outfitter): static
    {
        if (!$this->outfitters->contains($outfitter)) {
            $this->outfitters->add($outfitter);
            $outfitter->addOutfit($this);
        }

        return $this;
    }

    public function removeOutfitter(Sale $outfitter): static
    {
        if ($this->outfitters->removeElement($outfitter)) {
            $outfitter->removeOutfit($this);
        }

        return $this;
    }

}