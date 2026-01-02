class ESObject {
	static dataKey = null;
	constructor(jsonData = null) {
		this.data = {};

		if (jsonData) {
			this.data = JSON.parse(jsonData);
		}

		this.sourceName = '';
		this.sourcePath = '';
	}

	initFromJSON(jsonData) {
		this.data = JSON.parse(jsonData);
	}
}

class ESPoint {
	constructor(x = 0, y = 0) {
		this.x = x ?? 0;
		this.y = y ?? 0;
	}

	toString() {
		return '(' + this.x + ', ' + this.y + ')';
	}
}
class ESSet {
	constructor(objConstructor) {
		this.data = {};
		this.objConstructor = objConstructor;

		this.keyIndex = 0;
		this.done = false;
		this.value = null;
	}

	get(key) {
		if (this.data[key] == undefined) {
			this.data[key] = new this.objConstructor();
			this.data[key].indexName = key;
		}

		return this.data[key];
	}

	set(key, val) {
		this.data[key] = val;
		val.indexName = key;
	}

	next() {
		this.keyIndex++;
		if (this.keyIndex >= Object.keys(this.data).length - 1) {
			this.done = true;
			this.value = null;
		} else {
			this.done = false;
			this.value = this.data[Object.keys(this.data)[this.keyIndex]];
		}
		return this;
	}

	return(val) {
		this.value = val;
		this.done = true;
		this.keyIndex = -1;

		return this;
	}

	[Symbol.iterator]() {
		return this;
	}
}

class ESGameData {
	constructor() {
		this.objects = {};
		this.fileNodes = {};
	}
	register(objName, objType) {
		this.objects[objName] = new ESSet(objType);
	}

	loadFile(path) {
		var gameData = this;
		var dataFile = new ESDataFile(path, function() { 
			gameData.fileNodes[path] = this.root; 
			for (var node of this.root.children) {
				let key = node.getTokenAt(0);
				let hasValue = node.getSize() >= 2;
				var loaded = false;
				for (var typeName of Object.keys(gameData.objects)) {
					var typeSet = gameData.objects[typeName];
					if (typeSet.objConstructor.dataKey == key) {
						let nodeName = node.getTokenAt(1);
						console.log('Attempting to load node of type ' + key + ' with name ' + nodeName);
						typeSet.get(nodeName).load(node);
						loaded = true;
					}
				}
				if (!loaded) {
					console.log('Did not load node of type ' + key);
				}
			}
		});
	}
}

class ESGalaxy extends ESObject {
	static dataKey = 'galaxy';
	constructor(jsonData = null) {
		super(jsonData);

		this.indexName = '';
		this.data.position = new ESPoint();
		this.data.spriteName = '';
	}

	load(node) {
		this.indexName = node.getTokenAt(1);
		
		this.sourceName = node.sourceFile.source;
		this.sourcePath = node.sourceFile.filePath;
		for (var child of node.children) {
			var remove = child.getTokenAt(0) == "remove";
			var keyIndex = remove ? 1 : 0;
			var hasKey = child.getSize() > keyIndex;
			var key = hasKey ? child.getTokenAt(keyIndex) : child.getTokenAt(0);

			if (remove && hasKey) {
				if (key == "sprite") {
					this.data.spriteName = null;
				} else {
					child.printTrace("Skipping unsupported use of \"remove\":");
				}
			} else if (key == "pos" && child.getSize() >= 3) {
				this.data.position = new ESPoint(child.getValueAt(1), child.getValueAt(2));
			} else if (key == "sprite" && child.getSize() >= 2) {
				this.data.spriteName  = child.getTokenAt(1);//= SpriteSet::Get(child.Token(1));
			} else {
				child.printTrace("Skipping unrecognized attribute:");
			}
		}
	}
}

class ESSystem extends ESObject {
	static dataKey = 'system';
	constructor(jsonData = null) {
		super(jsonData);

		this.data.trueName = '';
		// Name and position (within the star map) of this system.
		this.data.displayName = '';
		this.data.position = new ESPoint();
		this.data.governmentName = '';
		this.data.mapIcons = []; // Vector of Sprites
		this.data.music = '';

		// All possible hyperspace links to other systems.
		this.data.links = []; // Set of Systems
		// Only those hyperspace links to other systems that are accessible.
		this.data.accessibleLinks = [];
		// Other systems that can be accessed from this system via a jump drive at various jump ranges.
		this.data.neighbors = {} // std::map<double, std::set<const System *>> ;

		// Defines whether this system can be seen when not linked. A hidden system will
		// not appear when in view range, except when linked to a visited system.
		this.data.hidden = false;
		// Defines whether a system can be remembered when out of view.
		this.data.shrouded = false;
		// Defines whether this system can be accessed or interacted with in any way.
		this.data.inaccessible = false;

		// Defines whether this system provides ramscoop even to ships that do not have any.
		this.data.universalRamscoop = true;
		// A value that is added to the ramscoop. It can be positive or negative.
		this.data.ramscoopAddend = 0.;
		// A multiplier applied to ramscoop in the system.
		this.data.ramscoopMultiplier = 1.;

		// Stellar objects, listed in such an order that an object's parents are
		// guaranteed to appear before it (so that if we traverse the vector in
		// order, updating positions, an object's parents will already be at the
		// proper position before that object is updated).
		this.data.objects = []; // std::vector<StellarObject>
		this.data.asteroids = []; // std::vector<Asteroid> 
		this.data.payloads = []; // std::set<const Outfit *> 
		this.data.hazeSpriteName = '';
		this.data.fleets = []; // std::vector<RandomEvent<Fleet>> 
		this.data.hazards = []; // std::vector<RandomEvent<Hazard>> 
		this.data.habitable = 1000.;
		this.data.belts = []; // WeightedList<double> 
		this.data.invisibleFenceRadius = 10000.;
		this.data.jumpRange = 0.;
		this.data.starfieldDensity = 1.;
		this.data.minimumFleetPeriod = 0;

		this.data.raidFleets = []; // std::vector<RaidFleet> 
		this.data.noRaids = false;

		// The amount of additional distance that ships will arrive away from the
		// system center when entering this system through a hyperspace link.
		// Negative values are allowed, causing ships to jump beyond their target.
		this.data.extraHyperArrivalDistance = 0.;
		// The amount of additional distance that ships will arrive away from the
		// system center when entering this system through a jumpdrive jump.
		// Jump drives use a circle around the target for targeting, so a value below
		// 0 doesn't have the same meaning as for hyperdrives. Negative values will
		// be interpreted as positive values.
		this.data.extraJumpArrivalDistance = 0.;

		// The minimum distances from the system center to jump out of the system.
		this.data.jumpDepartureDistance = 0.;
		this.data.hyperDepartureDistance = 0.;

		// Commodity prices.
		this.data.trade = {}; // std::map<std::string, Price> 

		// Attributes, for use in location filters.
		this.data.attributes = []; // std::set<std::string> 
	}
}

class ESAsteroid {
	// Type is the name of a Minable
	constructor(name = '', count = -1, energy = -1, type = '') {
		this.name = name;
		this.count = count;
		this.energy = energy;
		this.type = type;
	}
	
	getName() {
		return this.name;
	}
	getType() {
		return this.type;
	}
	getCount() {
		return this.count;
	}
	getEnergy() {
		return this.energy;
	}
}

class ESAngle {
	constructor(degrees = 0, point = null) {
		this.degrees = degrees;
		if (point) {
			this.degrees = (180 / Math.PI) * Math.atan2(point.X, -point.Y);
		}
	}
}

class ESSwizzle extends ESObject {
	static dataKey = 'swizzle';
	static identityMatrix = [
		1, 0, 0, 0,
		0, 1, 0, 0,
		0, 0, 1, 0,
		0, 0, 0, 1];
	static none() {
		return ESSwizzle.identityMatrix;
	}
	constructor(identity = false, loaded = false, overrideMask = false, matrix = ESSwizzle.identityMatrix) {
		this.identity = identity;
		this.loaded = loaded;
		this.overrideMask = overrideMask;
		this.matrix = matrix;
		this.name = '';
	}

	load(node) {
		this.name = node.getTokenAt(1);

		this.sourceName = node.sourceFile.source;
		this.sourcePath = node.sourceFile.filePath;

		for (var child of node.children) {
			var key = child.getTokenAt(0);

			// The corresponding row of the matrix for each channel name.
			var channelIndices = {
				"red": 0,
				"green": 1,
				"blue": 2,
				"alpha": 3
			};
			let channelCount = Object.keys(channelIndices).length;

			var channelIndex = channelIndices[key] ?? -1;

			if(channelIndex != -1) {
				// Fill in the row of the matrix for the channel.
				// We subtract one to account for the name being in the node.
				var channelStartIndex = channelIndex * channelCount;
				var elementNum = Math.min(child.getSize() - 1, 4);
				for (var i = 0; i < elementNum; i++) {
					matrix[channelStartIndex + i] = child.getValueAt(i + 1);
				}
			} else if(key == "override") {
				overrideMask = true;
			} else {
				child.printTrace("Unrecognized attribute in swizzle definition:");
			}
		}

		// Special-case flag for when applying a swizzle would do nothing at all.
		identity = matrix == ESSwizzle.identityMatrix;
		loaded = true;
	}
}

class ESBody extends ESObject {
	constructor() {
		// Basic positional attributes.
		this.data.position = new ESPoint();
		this.data.velocity = new ESPoint();
		this.data.angle = new ESAngle();
		this.data.scale = new ESPoint(1., 1.);
		this.data.center = new ESPoint();
		this.data.rotatedCenter = new ESPoint();
		// A zoom of 1 means the sprite should be drawn at half size. For objects
		// whose sprites should be full size, use zoom = 2.
		this.data.zoom = 1.;

		this.data.alpha = 1.;
		// The maximum distance at which the body is visible, and at which it becomes invisible again.
		this.data.distanceVisible = 0.;
		this.data.distanceInvisible = 0.;

		// Government, for use in collision checks.
		this.data.governmentName = '';

		// Animation parameters.
		this.data.spriteName = '';
		// Allow objects based on this one to adjust their frame rate and swizzle.
		this.data.swizzle = ESSwizzle.none();
		this.data.inheritsParentSwizzle = false;

		this.data.frameRate = 2. / 60.;
		this.data.delay = 0;
		// The chosen frame will be (step * frameRate) + frameOffset.
		this.frameOffset = 0.;
		this.startAtZero = false;
		this.randomize = false;
		this.data.repeat = true;
		this.data.rewind = false;
		this.data.pause = 0;

		// Cache the frame calculation so it doesn't have to be repeated if given
		// the same step over and over again.
		this.currentStep = -1;
		this.frame = 0.;
	}
	// Check that this Body has a sprite and that the sprite has at least one frame.
	hasSprite() {
		return this.data.spriteName != '';
	}
	getSprite() {
		// TODO
	}
	// Get the dimensions of the sprite.
	getWidth() {
		// TODO (relies on sprite)
	}
	getHeight() {
		// TODO (relies on sprite)
	}
	// Get the farthest a part of this sprite can be from its center.
	getRadius() {
		// TODO (relies on sprite)
	}
	// Which color swizzle should be applied to the sprite?
	getSwizzle() {
		// TODO
	}
	getInheritsParentSwizzle() {
		return this.data.inheritsParentSwizzle;
	}
	// Get the sprite frame and mask for the given time step.
	getFrame(step = -1) {

	}
	// const Mask &GetMask(int step = -1) const;

	// // Positional attributes.
	// const Point &Position() const;
	// const Point &Velocity() const;
	// const Point Center() const;
	// const Angle &Facing() const;
	// Point Unit() const;
	// double Zoom() const;
	// Point Scale() const;

	// // Check if this object is marked for removal from the game.
	// bool ShouldBeRemoved() const;

	// // Store the government here too, so that collision detection that is based
	// // on the Body class can figure out which objects will collide.
	// const Government *GetGovernment() const;

	// // Sprite serialization.
	// void LoadSprite(const DataNode &node);
	// void SaveSprite(DataWriter &out, const std::string &tag = "sprite") const;
	// // Set the sprite.
	// void SetSprite(const Sprite *sprite);
	// // Set the color swizzle.
	// void SetSwizzle(const Swizzle *swizzle);

	// // Functions determining the current alpha value of the body,
	// // dependent on the position of the body relative to the center of the screen.
	// double Alpha(const Point &drawCenter) const;
	// double DistanceAlpha(const Point &drawCenter) const;
	// bool IsVisible(const Point &drawCenter) const;

	// setStep(step) {
	// 	// If the animation is paused, reduce the step by however many frames it has
	// 	// been paused for.
	// 	step -= this.data.pause;

	// 	// If the step is negative or there is no sprite, do nothing. This updates
	// 	// and caches the mask and the frame so that if further queries are made at
	// 	// this same time step, we don't need to redo the calculations.
	// 	if (step == this.currentStep || step < 0 || !this.spriteName) { // || !sprite->Frames())
	// 		return;
	// 	}
	// 	this.currentStep = step;

	// 	// If the sprite only has one frame, no need to animate anything.
	// 	var frames = sprite->Frames();
	// 	if(frames <= 1.f)
	// 	{
	// 		frame = 0.f;
	// 		return;
	// 	}
	// 	float lastFrame = frames - 1.f;
	// 	// This is the number of frames per full cycle. If rewinding, a full cycle
	// 	// includes the first and last frames once and every other frame twice.
	// 	float cycle = (rewind ? 2.f * lastFrame : frames) + delay;

	// 	// If this is the very first step, fill in some values that we could not set
	// 	// until we knew the sprite's frame count and the starting step.
	// 	if(randomize)
	// 	{
	// 		randomize = false;
	// 		// The random offset can be a fractional frame.
	// 		frameOffset += static_cast<float>(Random::Real()) * cycle;
	// 	}
	// 	else if(startAtZero)
	// 	{
	// 		startAtZero = false;
	// 		// Adjust frameOffset so that this step's frame is exactly 0 (no fade).
	// 		frameOffset -= frameRate * step;
	// 	}

	// 	// Figure out what fraction of the way in between frames we are. Avoid any
	// 	// possible floating-point glitches that might result in a negative frame.
	// 	frame = max(0.f, frameRate * step + frameOffset);
	// 	// If repeating, wrap the frame index by the total cycle time.
	// 	if(repeat)
	// 		frame = fmod(frame, cycle);

	// 	if(!rewind)
	// 	{
	// 		// If not repeating, frame should never go higher than the index of the
	// 		// final frame.
	// 		if(!repeat)
	// 			frame = min(frame, lastFrame);
	// 		else if(frame >= frames)
	// 		{
	// 			// If we're in the delay portion of the loop, set the frame to 0.
	// 			frame = 0.f;
	// 		}
	// 	}
	// 	else if(frame >= lastFrame)
	// 	{
	// 		// In rewind mode, once you get to the last frame, count backwards.
	// 		// Regardless of whether we're repeating, if the frame count gets to
	// 		// be less than 0, clamp it to 0.
	// 		frame = max(0.f, lastFrame * 2.f - frame);
	// 	}
	// }
}

class ESStellarObject extends ESBody {
	constructor() {
		this.data.body = new ESBody();
		this.data.planetName = '';

		this.data.distance = 0;
		this.data.speed = 0;
		this.data.offset = 0;
		this.data.hazards = []; // std::vector<RandomEvent<Hazard>> 
		this.data.parentId = -1;

		this.data.message = '';
		this.data.isStar = false;
		this.data.isStation = false;
		this.data.isMoon = false;
	}

	// Functions provided by the Body base class:
	hasSprite() {
		return this.data.body.spriteName != '';
	}

	// // Get the radius of this planet, i.e. how close you must be to land.
	// double Radius() const;

	// // Determine if this object represents a planet with valid data.
	// bool HasValidPlanet() const;
	// // Get this object's planet, if any. It may or may not be fully defined.
	// const Planet *GetPlanet() const;

	// // Only planets that you can land on have names.
	// const std::string &DisplayName() const;
	// // If it is impossible to land on this planet, get the message
	// // explaining why (e.g. too hot, too cold, etc.).
	// const std::string &LandingMessage() const;

	// // Get the radar color to be used for displaying this object.
	// int RadarType(const Ship *ship) const;
	// // Check if this is a star.
	// bool IsStar() const;
	// // Check if this is a station.
	// bool IsStation() const;
	// // Check if this is a moon.
	// bool IsMoon() const;
	// // Get this object's parent index (in the System's vector of objects).
	// int Parent() const;
	// // This object's system hazards.
	// const std::vector<RandomEvent<Hazard>> &Hazards() const;
	// // Find out how far this object is from its parent.
	// double Distance() const;
}

class ESSolarGeneration {
	constructor() {
		this.fuel = 0;
		this.energy = 0;
		this.heat = 0;
	}
}

var gameData = new ESGameData();
gameData.register('galaxies', ESGalaxy);
gameData.register('systems', ESSystem);