<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'Minable')]
#[ORM\HasLifecycleCallbacks]
class Minable extends Body {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	protected int $id;
	
	#[ORM\Column(type: 'string')]
	protected string $name = '';
	#[ORM\Column(type: 'string')]
	protected string $displayName = '';
	#[ORM\Column(type: 'string')]
	protected string $noun = '';
	// Current angular position relative to the focus of the elliptical orbit,
	// in radians. An angle of zero is the periapsis point.
	#[ORM\Column(type: 'float')]
	protected float $theta = 0.0;
	// Eccentricity of the orbit. 0 is circular and 1 is a parabola.
	#[ORM\Column(type: 'float')]
	protected float $eccentricity = 0.0;
	// Angular momentum (radius^2 * angular velocity) will always be conserved.
	// The object's mass can be ignored, because it is a constant.
	#[ORM\Column(type: 'float')]
	protected float $angularMomentum = 0.0;
	// Scale of the orbit. This is the orbital radius when theta is 90 degrees.
	// The periapsis and apoapsis radii are scale / (1 +- eccentricity).
	#[ORM\Column(type: 'float')]
	protected float $orbitScale = 0.0;
	// Rotation of the orbit - that is, the angle of periapsis - in radians.
	#[ORM\Column(type: 'float')]
	protected float $rotation = 0.0;
	// Rate of spin of the object.
	#[ORM\Column(type: 'float')]
	protected float $spinDegrees = 0.0;
	protected Angle $spin;
	
	// Cache the current orbital radius. It can be calculated from theta and the
	// parameters above, but this avoids having to calculate every radius twice.
	#[ORM\Column(type: 'float')]
	protected float $radius = 0.0;
	
	// Remaining "hull" strength of the object, before it is destroyed.
	#[ORM\Column(type: 'float')]
	protected float $hull = 1000.;
	// The hull value that this object starts at.
	#[ORM\Column(type: 'float')]
	protected float $maxHull = 1000.;
	// A random amount of hull that gets added to the object.
	#[ORM\Column(type: 'float')]
	protected float $randomHull = 0.;
	// Material released when this object is destroyed. Each payload item only
	// has a 25% chance of surviving, meaning that usually the yield is much
	// lower than the defined limit but occasionally you get quite lucky.
	protected array $payload = []; // map<const Outfit *, int>
	// Explosion effects created when this object is destroyed.
	protected array $explosions = []; // map<const Effect *, int>
	// The expected value of the payload of this minable.
	#[ORM\Column(type: 'integer')]
	protected int $value = 0;
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		parent::toDatabase($eventArgs);
		$this->spinDegrees = $this->spin->getDegrees();
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		parent::fromDatabase($eventArgs);
		$this->spin = new Angle($this->spinDegrees);
	}
	
	public function __construct() {
		parent::__construct();
		$this->spin = new Angle();
	}
	
	// // Load a definition of a minable object.
	public function load(DataNode $node) {
		// Set the name of this minable, so we know it has been loaded.
		if ($node->size() >= 2) {
			$this->name = $node->getToken(1);
		}
		if ($node->getSourceName()) {
			$this->sourceName = $node->getSourceName();
			$this->sourceFile = $node->getSourceFile();
			$this->sourceVersion = $node->getSourceVersion();
		}
	
		foreach ($node as $child) {
			if ($child->getToken(0) == "display name" && $child->size() >= 2) {
				$this->displayName = $child->getToken(1);
			} else if ($child->getToken(0) == "noun" && $child->size() >= 2) {
				$this->noun = $child->getToken(1);
			// A full sprite definition (frame rate, etc.) is not needed, because
			// the frame rate will be set randomly and it will always be looping.
			} else if ($child->getToken(0) == "sprite" && $child->size() >= 2) {
				$this->setSprite(SpriteSet::Get($child->getToken(1)));
			} else if ($child->getToken(0) == "hull" && $child->size() >= 2) {
				$this->hull = $child->getValue(1);
			} else if ($child->getToken(0) == "random hull" && $child->size() >= 2) {
				$this->randomHull = max(0., $child->getValue(1));
			} else if (($child->getToken(0) == "payload" || $child->getToken(0) == "explode") && $child->size() >= 2) {
				$count = ($child->size() == 2 ? 1 : $child->getValue(2));
				$outfitName = $child->getToken(1);
				$outfit = GameData::Outfits()[$outfitName];
				if ($child->getToken(0) == "payload") {
					if (!isset($this->payload[$outfitName])) {
						$this->payload[$outfitName] = ['outfit'=>$outfit, 'count'=>0];
					}
					$this->payload[$outfitName]['count'] += $count;
				} else {
					if (!isset($this->explosions[$outfitName])) {
						$this->explosions[$outfitName] = ['outfit'=>$outfit, 'count'=>0];
					}
					$this->explosions[$outfitName]['count'] += $count;
				}
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	
		if ($this->displayName == '') {
			$this->displayName = Format::Capitalize($this->name);
		}
		if($this->noun == '') {
			$this->noun = "Asteroid";
		}
	}
	// 
	// 
	// 
	// // Calculate the expected payload value of this Minable after all outfits have been fully loaded.
	// void Minable::FinishLoading()
	// {
	// 	for(const auto &it : payload)
	// 		value += it.first->Cost() * it.second * 0.25;
	// }
	// 
	// 
	// 
	// const string &Minable::TrueName() const
	// {
	// 	return name;
	// }
	// 
	// 
	// 
	// const string &Minable::DisplayName() const
	// {
	// 	return displayName;
	// }
	// 
	// 
	// 
	// const string &Minable::Noun() const
	// {
	// 	return noun;
	// }
	// 
	// 
	// 
	// // Place a minable object with up to the given energy level, on a random
	// // orbit and a random position along that orbit.
	// void Minable::Place(double energy, double beltRadius)
	// {
	// 	// Note: there's no closed-form equation for orbital position as a function
	// 	// of time, so either I need to use Newton's method to get high precision
	// 	// (which, for a game would be overkill) or something will drift over time.
	// 	// If that drift caused the orbit to decay, that would be a problem, which
	// 	// rules out just applying gravity as a force from the system center.
	// 
	// 	// Instead, each orbit is defined by an ellipse equation:
	// 	// 1 / radius = constant * (1 + eccentricity * cos(theta)).
	// 
	// 	// The only thing that will change over time is theta, the "true anomaly."
	// 	// That way, the orbital period will only be approximate (which does not
	// 	// really matter) but the orbit itself will never decay.
	// 
	// 	// Generate random orbital parameters. Limit eccentricity so that the
	// 	// objects do not spend too much time far away and moving slowly.
	// 	eccentricity = Random::Real() * .6;
	// 
	// 	// Since an object is moving slower at apoapsis than at periapsis, it is
	// 	// more likely to start out there. So, rather than a uniform distribution of
	// 	// angles, favor ones near 180 degrees. (Note: this is not the "correct"
	// 	// equation; it is just a reasonable approximation.)
	// 	theta = Random::Real();
	// 	double curved = (pow(asin(theta * 2. - 1.) / (.5 * PI), 3.) + 1.) * .5;
	// 	theta = (eccentricity * curved + (1. - eccentricity) * theta) * 2. * PI;
	// 
	// 	// Now, pick the orbital "scale" such that, relative to the "belt radius":
	// 	// periapsis distance (scale / (1 + e)) is no closer than .4: scale >= .4 * (1 + e)
	// 	// apoapsis distance (scale / (1 - e)) is no farther than 4.: scale <= 4. * (1 - e)
	// 	// periapsis distance is no farther than 1.3: scale <= 1.3 * (1 + e)
	// 	// apoapsis distance is no closer than .8: scale >= .8 * (1 - e)
	// 	double sMin = max(.4 * (1. + eccentricity), .8 * (1. - eccentricity));
	// 	double sMax = min(4. * (1. - eccentricity), 1.3 * (1. + eccentricity));
	// 	orbitScale = (sMin + Random::Real() * (sMax - sMin)) * beltRadius;
	// 
	// 	// At periapsis, the object should have this velocity:
	// 	double maximumVelocity = (Random::Real() + 2. * eccentricity) * .5 * energy;
	// 	// That means that its angular momentum is equal to:
	// 	angularMomentum = (maximumVelocity * orbitScale) / (1. + eccentricity);
	// 
	// 	// Start the object off with a random facing angle and spin rate.
	// 	angle = Angle::Random();
	// 	spin = Angle::Random(energy) - Angle::Random(energy);
	// 	SetFrameRate(Random::Real() * 4. * energy + 5.);
	// 	// Choose a random direction for the angle of periapsis.
	// 	rotation = Random::Real() * 2. * PI;
	// 
	// 	// Calculate the object's initial position.
	// 	radius = orbitScale / (1. + eccentricity * cos(theta));
	// 	position = radius * Point(cos(theta + rotation), sin(theta + rotation));
	// 
	// 	// Add a random amount of hull value to the object.
	// 	hull += Random::Real() * randomHull;
	// 	maxHull = hull;
	// }
	// 
	// 
	// 
	// // Move the object forward one step. If it has been reduced to zero hull, it
	// // will "explode" instead of moving, creating flotsam and explosion effects.
	// // In that case it will return false, meaning it should be deleted.
	// bool Minable::Move(vector<Visual> &visuals, list<shared_ptr<Flotsam>> &flotsam)
	// {
	// 	if(hull < 0)
	// 	{
	// 		// This object has been destroyed. Create explosions and flotsam.
	// 		double scale = .1 * Radius();
	// 		for(const auto &it : explosions)
	// 		{
	// 			for(int i = 0; i < it.second; ++i)
	// 			{
	// 				// Add a random velocity.
	// 				Point dp = (Random::Real() * scale) * Angle::Random().Unit();
	// 
	// 				visuals.emplace_back(*it.first, position + 2. * dp, velocity + dp, angle);
	// 			}
	// 		}
	// 		for(const auto &it : payload)
	// 		{
	// 			if(it.second < 1)
	// 				continue;
	// 
	// 			// Each payload object has a 25% chance of surviving. This creates
	// 			// a distribution with occasional very good payoffs.
	// 			for(int amount = Random::Binomial(it.second, .25); amount > 0; amount -= Flotsam::TONS_PER_BOX)
	// 			{
	// 				flotsam.emplace_back(new Flotsam(it.first, min(amount, Flotsam::TONS_PER_BOX)));
	// 				flotsam.back()->Place(*this);
	// 			}
	// 		}
	// 		return false;
	// 	}
	// 
	// 	// Spin the object.
	// 	angle += spin;
	// 
	// 	// Advance the object forward one step.
	// 	theta += angularMomentum / (radius * radius);
	// 	radius = orbitScale / (1. + eccentricity * cos(theta));
	// 
	// 	// Calculate the new position.
	// 	Point newPosition(radius * cos(theta + rotation), radius * sin(theta + rotation));
	// 	// Calculate the velocity this object is moving at, so that its motion blur
	// 	// will be rendered correctly.
	// 	velocity = newPosition - position;
	// 	position = newPosition;
	// 
	// 	return true;
	// }
	// 
	// 
	// 
	// // Damage this object (because a projectile collided with it).
	// void Minable::TakeDamage(const Projectile &projectile)
	// {
	// 	hull -= projectile.GetWeapon().MinableDamage() + projectile.GetWeapon().RelativeMinableDamage() * maxHull;
	// }
	// 
	// 
	// 
	// double Minable::Hull() const
	// {
	// 	return min(1., hull / maxHull);
	// }
	// 
	// 
	// 
	// // Determine what flotsam this asteroid will create.
	// const map<const Outfit *, int> &Minable::Payload() const
	// {
	// 	return payload;
	// }
	// 
	// 
	// 
	// // Get the expected value of the flotsams this minable will create when destroyed.
	// const int64_t &Minable::GetValue() const
	// {
	// 	return value;
	// }

}