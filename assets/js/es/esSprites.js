class ESSprite extends ESObject {
	static blendingModes = {
		'-': "alpha",
		'=': "premultiplied alpha",
		'^': "half additive",
		'+': "additive",
		'~': "compat half additive"
	};
	constructor(jsonData = null) {
		super(jsonData);
		this.data.name = '';

		this.data.width = 0.;
		this.data.height = 0.;
		this.data.framePath = '';

		this.data.is2x = false;
		this.data.noReduction = true;
		this.data.frameNumber = 1;
		this.data.blendingMode = "alpha";
		this.data.isSwizzleMask = false;
	}

		getName() {
		return this.data.name;
	}
	setName(name) {
		this.data.name = name;
	}

	getWidth() {
		return this.data.width;
	}
	setWidth(width) {
		this.data.width = width;
	}
	getHeight() {
		return this.data.height;
	}
	setHeight(height) {
		this.data.height = height;
	}
	getFramePath() {
		return this.data.framePath;
	}
	setFramePath(framePath) {
		this.data.framePath = framePath;
	}

	getIs2x() {
		return this.data.is2x;
	}
	setIs2x(is2x) {
		this.data.is2x = is2x;
	}
	getNoReduction() {
		return this.data.noReduction;
	}
	setNoReduction(noReduction) {
		this.data.noReduction = noReduction;
	}
	getFrameNumber() {
		return this.data.frameNumber;
	}
	setFrameNumber(frameNumber) {
		this.data.frameNumber = frameNumber;
	}
	getBlendingMode() {
		return this.data.blendingMode;
	}
	setBlendingMode(blendingMode) {
		this.data.blendingMode = blendingMode;
	}
	getIsSwizzleMask() {
		return this.data.isSwizzleMask;
	}
	setIsSwizzleMask(isSwizzleMask) {
		this.data.isSwizzleMask = isSwizzleMask;
	}

}

class ESSpriteSet {
	static spriteSet = new ESSet(ESSpriteSet);
	static get(name) {
		return ESSpriteSet.spriteSet.get(name);
	}
	static add(sprite) {
		ESSpriteSet.spriteSet.get(sprite.getName()).add(sprite);
	}
	static getSprite(name, frameNumber = 0) {
		return ESSpriteSet.spriteSet.get(name).getSprite(frameNumber);
	}
	static load(path) {
		var pathParts = path.split('.');
		var extension = pathParts.slice(-1)[0];
		var name = path.substring(0, path.length - (extension.length + 1));
		var sprite = new ESSprite();
		if(name.endsWith("@2x")) {
			sprite.setIs2x(true);
			sprite.setNoReduction(true);
			name = name.substring(0, name.length - 3);
		}
		if(name.endsWith("@1x")) {
			sprite.setNoReduction(true);
			name = name.substring(0, name.length - 3);
		}
		if(name.endsWith("@sw")) {
			sprite.setIsSwizzleMask(true);
			name = name.substring(0, name.length - 3);
		}

		let zeroCode = '0'.charCodeAt(0);
		let nineCode = '9'.charCodeAt(0);
		var frameNumberStart = name.length;
		while (frameNumberStart > 0 && name[--frameNumberStart].charCodeAt(0) >= zeroCode && name[frameNumberStart].charCodeAt(0) <= nineCode) {
			continue;
		}

		if (frameNumberStart > 0 && ESSprite.blendingModes[name[frameNumberStart]] != undefined) {
			sprite.setFrameNumber(parseInt(name.substring(frameNumberStart + 1)));
			sprite.setBlendingMode(ESSprite.blendingModes[name[frameNumberStart]]);
			name = name.substring(0, frameNumberStart);

			if (sprite.getBlendingMode() == "compat half additive") {
				sprite.setBlendingMode("half additive");
				console.log("File '" + path + "'uses legacy marker for half-additive blending mode; please use '^' instead of '~'.",);
			}
		}

		sprite.setName(name);
		sprite.setFramePath(path);
		
		var spriteImage = new Image();
		spriteImage.onload = function(event) { console.log('Got width & height for ' + path); sprite.setWidth(this.width); sprite.setHeight(this.height); };
		spriteImage.src = '/es/sprite/' + path;

		ESSpriteSet.add(sprite);
	}
	constructor() {
		this.name = '';
		this.frames = [];
		this.currentFrame = 0;
	}
	add(sprite) {
		if (this.name == '') {
			this.name = sprite.getName();
		} else if (this.name != sprite.getName()) {
			console.log("tried to add a sprite with name '" + sprite.getName() + "' to sprite set named '" + this.name + "'");
		}
		this.frames[sprite.getFrameNumber()] = sprite;
	}
	getSprite(frameNumber = 0) {
		if (frameNumber >= this.frames.length - 1) {
			console.log("Tried to get frame number " + frameNumber + " from sprite set '" + this.name + "' with only " + this.frames.length + " frame(s)");
			return null;
		}
		return this.frames[frameNumber];
	}
}