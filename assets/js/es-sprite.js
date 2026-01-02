class ESSprite extends HTMLElement {
	constructor() {
		super();
		this.sprite = null;
		this.hiDPI = false;

		this.settingUp = false;
		this.isSetup = false;

		this.frameIndex = -1;


		this.swizzles = [];
		this.swizzles[0] = [ [1, 0, 0, 0, 0], [0, 1, 0, 0, 0], [0, 0, 1, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[1] = [ [1, 0, 0, 0, 0], [0, 0, 1, 0, 0], [0, 1, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[2] = [ [0, 1, 0, 0, 0], [1, 0, 0, 0, 0], [0, 0, 1, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[3] = [ [0, 0, 1, 0, 0], [1, 0, 0, 0, 0], [0, 1, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[4] = [ [0, 1, 0, 0, 0], [0, 0, 1, 0, 0], [1, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[5] = [ [0, 0, 1, 0, 0], [0, 1, 0, 0, 0], [1, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[6] = [ [0, 1, 0, 0, 0], [0, 0, 1, 0, 0], [0, 0, 1, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[7] = [ [1, 0, 0, 0, 0], [0, 0, 1, 0, 0], [0, 0, 1, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[8] = [ [1, 0, 0, 0, 0], [0, 1, 0, 0, 0], [0, 1, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[9] = [ [0, 0, 1, 0, 0], [0, 0, 1, 0, 0], [0, 0, 1, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[10] = [ [0, 1, 0, 0, 0], [0, 1, 0, 0, 0], [0, 1, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[11] = [ [1, 0, 0, 0, 0], [1, 0, 0, 0, 0], [1, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[12] = [ [0, 0, 1, 0, 0], [0, 0, 1, 0, 0], [0, 1, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[13] = [ [0, 0, 1, 0, 0], [0, 0, 1, 0, 0], [1, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[14] = [ [0, 1, 0, 0, 0], [0, 1, 0, 0, 0], [1, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[15] = [ [0, 0, 1, 0, 0], [0, 1, 0, 0, 0], [0, 1, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[16] = [ [0, 0, 1, 0, 0], [1, 0, 0, 0, 0], [1, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[17] = [ [0, 1, 0, 0, 0], [1, 0, 0, 0, 0], [1, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[18] = [ [0, 0, 1, 0, 0], [0, 1, 0, 0, 0], [0, 0, 1, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[19] = [ [0, 0, 1, 0, 0], [1, 0, 0, 0, 0], [0, 0, 1, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[20] = [ [0, 1, 0, 0, 0], [1, 0, 0, 0, 0], [0, 1, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[21] = [ [0, 1, 0, 0, 0], [0, 1, 0, 0, 0], [0, 0, 1, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[22] = [ [1, 0, 0, 0, 0], [1, 0, 0, 0, 0], [0, 0, 1, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[23] = [ [1, 0, 0, 0, 0], [1, 0, 0, 0, 0], [0, 1, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[24] = [ [0, 1, 0, 0, 0], [0, 0, 1, 0, 0], [0, 1, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[25] = [ [1, 0, 0, 0, 0], [0, 0, 1, 0, 0], [1, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[26] = [ [1, 0, 0, 0, 0], [0, 1, 0, 0, 0], [1, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[27] = [ [0, 0, 1, 0, 0], [0, 0, 0, 0, 0], [0, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];
		this.swizzles[28] = [ [0, 0, 0, 0, 0], [0, 0, 0, 0, 0], [0, 0, 0, 0, 0], [0, 0, 0, 1, 0] ];

		this.shadow = this.attachShadow({mode: 'open'});
		this.wrapper = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		this.wrapper.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
		this.shadow.appendChild(this.wrapper);

		this.defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
		this.wrapper.appendChild(this.defs);
		
		this.swizzleFilter = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
		this.swizzleFilter.id = 'swizzle';
		this.defs.appendChild(this.swizzleFilter);

		this.swizzleFilterMatrix = document.createElementNS('http://www.w3.org/2000/svg', 'feColorMatrix');
		this.swizzleFilterMatrix.setAttribute('in', 'SourceGraphic');
		this.swizzleFilterMatrix.setAttribute('type', 'matrix');
		this.swizzleFilterMatrix.setAttribute('values', 
`1 0 0 0 0
 0 1 0 0 0
 0 0 1 0 0
 0 0 0 1 0`);
		this.swizzleFilter.appendChild(this.swizzleFilterMatrix);
		
		this.maskSwizzleFilter = document.createElementNS('http://www.w3.org/2000/svg', 'filter');
		this.maskSwizzleFilter.id = 'swizzleMasked';
		this.defs.appendChild(this.maskSwizzleFilter);

		this.maskSwizzleFilterMatrix = document.createElementNS('http://www.w3.org/2000/svg', 'feColorMatrix');
		this.maskSwizzleFilterMatrix.setAttribute('in', 'SourceGraphic');
		this.maskSwizzleFilterMatrix.setAttribute('result', 'swizzled');
		this.maskSwizzleFilterMatrix.setAttribute('type', 'matrix');
		this.maskSwizzleFilterMatrix.setAttribute('values', 
`1 0 0 0 0
 0 1 0 0 0
 0 0 1 0 0
 0 0 0 1 0`);
		this.maskSwizzleFilter.appendChild(this.maskSwizzleFilterMatrix);

		this.maskSwizzleMask = document.createElementNS('http://www.w3.org/2000/svg', 'feImage');
		this.maskSwizzleMask.setAttribute('result', 'maskImage');
		this.maskSwizzleMask.setAttributeNS('http://www.w3.org/1999/xlink', 'href', '/sprite/ship/avgi%20undsyni?swizzleMask=true');
		this.maskSwizzleFilter.appendChild(this.maskSwizzleMask);

		this.maskSwizzleComp1 = document.createElementNS('http://www.w3.org/2000/svg', 'feComposite');
		this.maskSwizzleComp1.setAttribute('operator', 'in');
		this.maskSwizzleComp1.setAttribute('in', 'maskImage');
		this.maskSwizzleComp1.setAttribute('in2', 'SourceGraphic');
		this.maskSwizzleComp1.setAttribute('result', 'sourceMasked');
		this.maskSwizzleFilter.appendChild(this.maskSwizzleComp1);

		this.maskSwizzleComp2 = document.createElementNS('http://www.w3.org/2000/svg', 'feComposite');
		this.maskSwizzleComp2.setAttribute('operator', 'over');
		this.maskSwizzleComp2.setAttribute('in', 'swizzled');
		this.maskSwizzleComp2.setAttribute('in2', 'sourceMasked');
		this.maskSwizzleFilter.appendChild(this.maskSwizzleComp2);

		this.imWrapper = document.createElementNS('http://www.w3.org/2000/svg', 'g');
		this.wrapper.appendChild(this.imWrapper);
	}

	connectedCallback() {
		if (this.hasAttribute('hi-dpi') && this.getAttribute('hi-dpi') != 'false') {
			this.hiDPI = true;
		}
		if (this.hasAttribute('sprite-load-url')) {
			this.spriteLoadUrl = this.getAttribute('sprite-load-url');

			var spriteFetch = fetch(this.spriteLoadUrl);
			spriteFetch.then((response) => {
				var spriteJSON = response.json();

				spriteJSON.then((spriteData) => {
					this.sprite = spriteData;

					this.setupSprite();
				});
			});
		} else {
			if (this.sprite) {
				this.setupSprite();
			}
		}
	}

	setSwizzle(swizzleIndex, masked = false) {
		var swizzleMatrix = this.swizzles[swizzleIndex];
		var fromArray = ['r','g','b','a','c'];
		var toArray = ['r','g','b','a'];
		var matrixValues = '';
		for (var i=0; i<toArray.length; i++) {
			for (var j=0; j<fromArray.length; j++) {
				if (j != 0) {
					matrixValues += ' ';
				}
				var val = swizzleMatrix[i][j];
				matrixValues += val;
			}
			if (i != toArray.length - 1) {
				matrixValues += '\n';
			}
		}
		if (!masked) {
			this.swizzleFilterMatrix.setAttribute('values', matrixValues);
		} else {
			this.maskSwizzleFilterMatrix.setAttribute('values', matrixValues);
		}
	}

	toggleSwizzle(masked = false) {
		if (this.imageFrame.hasAttribute('filter')) {
			this.imageFrame.removeAttribute('filter');
		} else {
			if (!masked) {
				this.imageFrame.setAttribute('filter', 'url(#swizzle)');
			} else {
				this.imageFrame.setAttribute('filter', 'url(#swizzleMasked)');
			}
		}
	}

	setupSprite() {
		console.log('Setting up sprite ' + this.sprite.name + '...');
		this.spriteNameUrl = this.sprite.name.replaceAll(' ','%20');
		this.settingUp = true;
		this.frames = [];
		this.imageFrame = document.createElementNS('http://www.w3.org/2000/svg', 'image');
		this.imWrapper.appendChild(this.imageFrame);
		var imagePath = '/sprite/' + this.spriteNameUrl;
		var suffix = '?';
		if (this.hiDPI) {
			imagePath += '?hiDPI=true';
			suffix = '&';
		}
		var frame0 = document.createElement('img');
		var root = this;
		frame0.onload = function(event) {
			root.width = this.width;
			root.height = this.height;
			root.wrapper.setAttribute('width', root.width);
			root.wrapper.setAttribute('height', root.height);
			root.wrapper.setAttribute('viewBox', '0 0 ' + root.width + ' ' + root.height);
			root.imageFrame.setAttribute('width', root.width);
			root.imageFrame.setAttribute('height', root.height);
			for (var i=0; i<root.sprite.frames; i++) {
				var frameImage = document.createElement('img');
				frameImage.src = imagePath + suffix + 'frame=' + i;
				// frameImage.setAttribute('width', root.width);
				// frameImage.setAttribute('height', root.height);
				// frameImage.setAttributeNS('http://www.w3.org/1999/xlink',"href", '/sprite/' + root.sprite.name + '?' +hiDPIStr + 'frame=' + i);

				root.frames[i] = frameImage;
				console.log('Created & added frame ' + i);
			}

			console.log('Setup done.');
			root.isSetup = true;
		}
		frame0.src = imagePath;
	}

	displayFrame(frameIndex) {
		console.log('Attempting to display frame ' + frameIndex);
		if (!this.isSetup) {
			if (!this.settingUp) {
				console.log('Not yet set up!');
				this.setupSprite();
			}
			return;
		}
		if (this.frameIndex != -1) {
			this.frames[this.frameIndex].remove();
		}
		//this.imWrapper.appendChild(this.frames[frameIndex]);
		var hiDPIStr = '';
		if (this.hiDPI) {
			hiDPIStr = 'hiDPI=true&';
		}
		this.imageFrame.setAttributeNS('http://www.w3.org/1999/xlink',"href", '/sprite/' + this.spriteNameUrl + '?' +hiDPIStr + 'frame=' + frameIndex);

		this.frameIndex = frameIndex;
	}

	getFrameCount() {
		return this.frames.length;
	}
}

class ESBody extends HTMLElement {
	constructor() {
		super();
		this.body = null;
		this.frameIndex = 0;
		this.increment = 1;

		this.shadow = this.attachShadow({mode: 'open'});
	}

	connectedCallback() {
		this.sprite = new ESSprite();
		if (sprites == null) {
			var root = this;
			dataLoadCallbacks.push(function() { root.setupBody(); });
		} else {
			this.setupBody();
		}
	}

	setupBody() {
		this.sprite.sprite = sprites[this.body.sprite];
		this.shadow.appendChild(this.sprite);

		this.frameIndex = this.body.frameOffset;

		this.sprite.displayFrame(this.frameIndex);

		var fps = this.body.frameRate * 60;
		this.delayPerFrame = 1000 / fps;
		this.animateTimer = null;

	// float frameRate = 2.f / 60.f;
	// int delay = 0;
	// // The chosen frame will be (step * frameRate) + frameOffset.
	// mutable float frameOffset = 0.f;
	// mutable bool startAtZero = false;
	// mutable bool randomize = false;
	// bool repeat = true;
	// bool rewind = false;
	// int pause = 0;
	}

	fixSize() {
		this.setAttribute('width', this.sprite.width);
		this.setAttribute('height', this.sprite.height);
		this.style.width = this.sprite.width;
		this.style.height = this.sprite.height;
	}

	animate() {
		var root = this;
		this.animateTimer = setTimeout(function() { root.nextFrame(); }, this.delayPerFrame);
		this.sprite.onclick = function(event) { root.stopAnimation(); };
	}

	nextFrame() {
		if (this.sprite.isSetup) {
			this.fixSize();
			if (this.sprite.getFrameCount() == 1) {
				console.log('Attempted to animate a 1-frame sprite');
				this.sprite.displayFrame(0);
				return;
			}
			var nextFrameIndex = this.frameIndex + this.increment;
			if (nextFrameIndex >= this.sprite.getFrameCount()) {
				if (this.body.rewind) {
					this.increment = -1;
					nextFrameIndex = this.frameIndex - 1;
				} else {
					nextFrameIndex = 0;
				}
			}
			if (this.increment == -1 && nextFrameIndex < 0) {
				this.increment = 1;
				nextFrameIndex = 1;
			}
			this.sprite.displayFrame(nextFrameIndex);
			this.frameIndex = nextFrameIndex;
		}
		var root = this;
		this.animateTimer = setTimeout(function() { root.nextFrame(); }, this.delayPerFrame);
	}

	stopAnimation() {
		clearTimeout(this.animateTimer);
		this.animateTimer = null;
		var root = this;
		this.sprite.onclick = function(event) { root.animate(); };
	}

}

customElements.define('es-sprite',ESSprite);
customElements.define('es-body',ESBody);