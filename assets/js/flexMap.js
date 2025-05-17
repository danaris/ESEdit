class FlexMap extends HTMLElement {
	constructor() {
		super();
		this.activeBackground = "";
		this.mapId = null;
		this.mapScale = 1;
		this.bigMap = false;
		this.validScales = [ 0.5, 0.75, 1.0 ];
		this.bigScales = [ 1.5, 2 ];
		this.displayZoom = { 0.5: "50%", 0.75: "75%", 1.0: "100%", 1.5: "150%", 2: "200%" };

		this.backgrounds = {};
		this.layers = {};
		this.layerGroups = {};
		this.customInitializer = null;

		this.mapControlsHeight = 128;
		this.controlPanelHeight = 220;
		this.mapControlsExpanded = true;

		this.globalMinX = 0;
		this.globalMinY = 0;
		this.mapFrameWidth = window.innerWidth;
		this.mapFrameHeight = window.innerHeight - 72;
		this.mapViewboxX = 0;
		this.mapViewboxY = 0;

		this.mapXOffset = 0;
		this.mapYOffset = 0;

		this.dragX = 0;
		this.dragY = 0;

		this.dragStartOffsetX = 0;
		this.dragStartOffsetY = 0;

		this.mapControlImageLayers = [];
		this.mapControlPolygonLayers = [];
		this.mapControlTextLayers = [];

		this.dataLayerDefs = {};
		this.substitutions = {};
		
		this.controls = null;

		this.styleElement = document.createElement('style');
		this.styleElement.textContent = `#regionName {
	font-size: 120%;
	color: #FFE0E0;
	font-weight: bold;
}
#realmName {
	font-weight: bold;
	color: white;
}
#regionDataDisplay {
	min-width: 32em;
	margin-top: 1em;
	display: none;
}
.regionDetailsInner {
	display: grid;
	grid-template-columns: auto auto;
	padding-left: 2em;
	padding-top: 1em;
	width: 20em;
}
.right {
	color: white;
}
.regionDetailsInner div {
	padding: 0.1em 0;
}
.double {
	transform: scale(2.0);
}
#mapControlsFront text {
	font-family: Caligula, Georgia, serif;
	cursor: pointer;
}
.regionData, .realmData, .duchyData {
	font-family: Caligula, Georgia, serif;
}
g[hoverlayer=true] path:hover {
	fill-opacity: 0.6 !important;
}
.mapButton {
	border: 3px gray outset;
	background-color: rgba(0, 0, 0, 0.3);
	cursor: pointer;
}
.mapButton:hover {
	background-color: rgba(64, 64, 64, 0.3);
	cursor: pointer;
}
.mapButton:active {
	border: 3px gray inset;
	background-color: rgba(192, 192, 192, 0.3);
	cursor: pointer;
}
.mapButton.pressed {
	border: 3px gray inset;
	background-color: rgba(128, 128, 128, 0.3);
}`;

		var mapDefsTemplate = document.createElement('template');
		mapDefsTemplate.innerHTML = `<defs>
	  <g id="arrowMarker">
		  <g>
			  <path d="M 8,-4 L 0,0 L 8,4 L 8,-4" />
		  </g>
	  </g>
		<marker id="startMarker" markerWidth="48" markerHeight="24" viewBox="-4 -4 25 5" orient="auto" refX="0" refY="0" markerUnits="strokeWidth">
			<g>
				<use xlink:href="#arrowMarker" transform="rotate(180)" stroke-width="1"/>
			</g>
		</marker>
		<g id="yArrowMarker">
			<g stroke="yellow" stroke-width="1">
				<path d="M 8,-4 L 0,0 L 8,4 L 8,-4" style="fill: yellow; stroke: yellow; stroke-width: 1px" />
			</g>
		</g>
		<marker id="yStartMarker" markerWidth="48" markerHeight="24" viewBox="-4 -4 25 5" orient="auto" refX="0" refY="0" markerUnits="strokeWidth">
			<g>
				<use xlink:href="#yArrowMarker" transform="rotate(180)" stroke-width="1" stroke="yellow"/>
			</g>
		</marker>
		<filter id="inner-glow">
			<feFlood flood-color="white"/>
			<feComposite in2="SourceAlpha" operator="out"/>
			<feGaussianBlur stdDeviation="0.9" result="blur"/>
			<feComposite operator="atop" in2="SourceGraphic"/>
		</filter>

		<filter id="textGlow" height="300%" width="300%" x="-75%" y="-75%">
			<!-- Thicken out the original shape -->
			<feMorphology operator="dilate" radius="1.2" in="SourceAlpha" result="thicken" />

			<!-- Use a gaussian blur to create the soft blurriness of the glow -->
			<feGaussianBlur in="thicken" stdDeviation="1.5" result="blurred" />

			<!-- Change the colour -->
			<feFlood flood-color="rgba(255,255,255, 0.7)" result="glowColor" />

			<!-- Color in the glows -->
			<feComposite in="glowColor" in2="blurred" operator="in" result="softGlow_colored" />

			<!-- Layer the effects together -->
			<feMerge>
				<feMergeNode in="softGlow_colored"/>
				<feMergeNode in="SourceGraphic"/>
			</feMerge>

		</filter>

		<filter id="textGlowRev" height="300%" width="300%" x="-75%" y="-75%">
			<!-- Thicken out the original shape -->
			<feMorphology operator="dilate" radius="1.2" in="SourceAlpha" result="thicken" />

			<!-- Use a gaussian blur to create the soft blurriness of the glow -->
			<feGaussianBlur in="thicken" stdDeviation="1.5" result="blurred" />

			<!-- Change the colour -->
			<feFlood flood-color="rgba(0,0,0, 0.7)" result="glowColor" />

			<!-- Color in the glows -->
			<feComposite in="glowColor" in2="blurred" operator="in" result="softGlow_colored" />

			<!-- Layer the effects together -->
			<feMerge>
				<feMergeNode in="softGlow_colored"/>
				<feMergeNode in="SourceGraphic"/>
			</feMerge>

		</filter>
	 </defs>`;

		//this.mapDefs = mapDefsTemplate.content.childNodes[0];
		this.mapDefs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');

		this.map = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		this.map.id = 'mapId';
		this.map.setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
		this.map.setAttribute('width', 300);
		this.map.setAttribute('height', 300);
		this.map.setAttribute('viewBox', '0 0 300 300');
		this.map.appendChild(this.mapDefs);
		this.backgroundContainer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
		this.backgroundContainer.id = 'backgroundLayers';
		this.map.appendChild(this.backgroundContainer);
		this.foregroundContainer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
		this.foregroundContainer.id = 'foregroundLayers';
		this.map.appendChild(this.foregroundContainer);
		this.mapOuter = document.createElement('div');
		this.mapOuter.style.position = 'relative';
		this.mapOuter.style.overflow = 'auto';
		this.mapOuter.style.marginRight = '1em';
		this.mapOuter.style.zIndex = 0;

		this.mapInner = document.createElement('div');
		this.mapInner.style.width = '100%';
		this.mapInner.style.height = 'calc(100lvh - 60px - 1rem)';
		this.mapInner.style.overflow = 'auto';
		this.mapInner.style.zIndex = '-1';
		this.mapOuter.appendChild(this.mapInner);

		this.mapInner.appendChild(this.map);

		var root = this;
		this.onmousedown = function(event) { root.grabMap(event); };
		this.ontouchstart = function(event) { root.grabMapTouch(event); };

		this.childrenFound = false;
		this.finalizeTimer = null;
		this.postLoadCallbacks = [];

		this.onMutation = this.onMutation.bind(this);

		this.buildNewMapControls();

	}
	connectedCallback() {
		if (this.mapId != null) {
			return;
		}
		this.id = this.getAttribute('id');
		this.mapContentWidth = parseInt(this.getAttribute('width'));
		this.mapContentHeight = parseInt(this.getAttribute('height'));

		this.map.setAttribute('width', this.mapContentWidth);
		this.map.setAttribute('height', this.mapContentHeight);
		this.map.setAttribute('viewBox', this.mapViewboxX + ' ' + this.mapViewboxY + ' ' + this.mapContentWidth + ' ' + this.mapContentHeight);

		// Set up observer
		this.observer = new MutationObserver(this.onMutation);

		// Watch the Light DOM for child node changes
		this.observer.observe(this, {
			childList: true
		});

		this.shadow = this.attachShadow({mode: 'open'});

		this.shadow.appendChild(this.styleElement);
		this.shadow.appendChild(this.mapOuter);
		
		this.mapFrameWidth = Math.min(this.mapFrameWidth, this.map.clientWidth);
		this.mapFrameHeight = Math.min(this.mapFrameHeight, this.map.clientHeight);
	}

	disconnectedCallback() {
		// remove observer if element is no longer connected to DOM
		this.observer.disconnect();
	}

	onMutation(mutations) {
		const added = [];

		// A `mutation` is passed for each new node
		for (const mutation of mutations) {
			// Could test for `mutation.type` here, but since we only have
			// set up one observer type it will always be `childList`
			added.push(...mutation.addedNodes);
		}

		//console.log('Processing ' + added.length + ' added subelements');

		for (var i=0; i<added.length; i++) {
			//console.log('- Added ' + added[i].constructor.name + ' node');
			var childNode = added[i].cloneNode(true);
			if (childNode instanceof MapBackgroundElement) {
				var backgroundName = childNode.getAttribute('name');
				var background = new ImageLayer(backgroundName);
				background.default = (Object.keys(this.backgrounds).length == 0);
				var backgroundBaseScale = parseInt(childNode.getAttribute('base-scale'));
				if (backgroundBaseScale) {
					background.baseScale = backgroundBaseScale;
				}
				added[i].addImageListener(this);
				this.addBackgroundLayer(background, background.default);
				this.addBackgroundLayerControl(background);
			} else if (childNode instanceof MapLayerElement) {
				var layerName = childNode.getAttribute('name');
				var layerId = childNode.getAttribute('id');
				var layerVisible = childNode.getAttribute('visible') == 'true';
				var layer = new MapLayer(layerName, '', '', '', layerVisible);
				layer.svg.id = layerId;
				this.addForegroundLayer(layer);
			} else if (childNode instanceof MapLayerGroupElement) {
				var layerDesc = childNode.getAttribute('name');
				var layerShort = childNode.getAttribute('short-name');
				var loadUrl = childNode.getAttribute('load-url');
				var dataUrl = childNode.getAttribute('data-url');
				var hasBase = childNode.getAttribute('map') == 'true';
				var hasNames = childNode.getAttribute('names') == 'true';
				var hasData = childNode.getAttribute('data') == 'true';
				var startVisible = childNode.getAttribute('visible') == 'true';
				var controllable = !(childNode.getAttribute('controllable') == 'false');
				if (hasBase) {
					var baseLayer = new MapLayer(layerShort + 'Base', layerDesc, layerShort, loadUrl, startVisible, controllable);
					this.addForegroundLayer(baseLayer);
				}
				if (hasNames) {
					var namesLayer = new MapLayer(layerShort + 'Names', layerDesc, layerShort, loadUrl, startVisible, controllable);
					this.addForegroundLayer(namesLayer);
				}
				this.addBaseLayerControl(baseLayer);
				if (hasData) {
					var dataLayerName = layerShort + 'Data';
					var dataLayer; 
					if (this.layers[dataLayerName] != undefined) {
						dataLayer = this.layers[dataLayerName];
						dataLayer.desc = layerDesc;
						dataLayer.group = layerShort;
						dataLayer.svg.style.visibility = startVisible ? 'visible' : 'hidden';
						dataLayer.controllable = controllable;
						dataLayer.dataUrl = dataUrl;
					} else {
						dataLayer = new MapLayer(dataLayerName, layerDesc, layerShort, dataUrl, startVisible, controllable);
						this.addForegroundLayer(dataLayer);
						this.addDataLayerControl(dataLayer);
					}
				}
				this.layerGroups[layerShort] = {'name': layerShort, 'loadUrl': loadUrl, 'dataUrl': dataUrl, 'hasBase': hasBase, 'hasNames': hasNames, 'hasData': hasData, 'loaded': false};
			} else if (childNode instanceof MapDataLayerElement) {
				var parentLayer;

				var child_name = childNode.getAttribute('name');
				var child_displayName = childNode.getAttribute('display-name');
				var child_parent = childNode.getAttribute('parent');
				var child_startVisible = childNode.getAttribute('start-visible') == 'true';
				var child_controllable = childNode.getAttribute('controllable') == 'true';
				var child_iconUrl = childNode.getAttribute('icon-url');
				var child_extraInfo = childNode.getAttribute('extra-info');

				if (this.layers[child_parent + 'Data'] != undefined) {
					parentLayer = this.layers[child_parent + 'Data'];
				} else {
					parentLayer = new MapLayer(child_parent + 'Data');
					this.addForegroundLayer(parentLayer);
				}

				var dataLayer = new MapLayer(child_name + 'Data', child_displayName, child_parent, '', child_startVisible, child_controllable);
				dataLayer.iconUrl = child_iconUrl;
				dataLayer.extraInfo = child_extraInfo;
				this.layers[child_name + 'Data'] = dataLayer;
				parentLayer.svg.appendChild(dataLayer.svg);
				dataLayer.parent = parentLayer;
				this.addDataLayerControl(dataLayer);
			}
		}

		if (this.finalizeTimer != null) {
			console.log('Resetting finalization timer');
			clearTimeout(this.finalizeTimer);
		}
		var root = this;
		this.finalizeTimer = setTimeout(function() {
			root.finalizeInit();
		}, 450);
	}

	finalizeInit() {
		console.log('Finalizing initialization');

		this.loadAllLayerData();
		this.childrenFound = true;
		this.finalizeTimer = null;
	}

	postLoad() {
		this.postLoadCallbacks.sort((a, b) => { return a.priority - b.priority; });
		while (this.postLoadCallbacks.length > 0) {
			var callbackPriority = this.postLoadCallbacks.shift();
			var callback = callbackPriority.callback;
			callback(this);
		}
	}

	addPostLoadCallback(callback, priority = 1) {
		var callbackPriority = {'callback': callback, 'priority': priority};
		this.postLoadCallbacks.unshift(callbackPriority);
		this.checkAllLoaded();
	}

	addBackgroundLayer(imageLayer) {
		if (this.backgrounds[imageLayer.name] == undefined) {
			this.backgrounds[imageLayer.name] = imageLayer;
			this.backgroundContainer.appendChild(imageLayer.svg);
		}
	}

	layerAddedImage(layer, image) {
		this.backgrounds[layer.name].addImage(image.url, image.width, image.height);
		if (!this.backgrounds[layer.name].default) {
			this.backgrounds[layer.name].svg.style.visibility = 'hidden';
		} else {
			this.backgrounds[layer.name].svg.style.visibility = 'visible';
			this.activeBackground = layer.name;
		}
		this.backgrounds[layer.name].calculateScales();
		this.backgrounds[layer.name].setScale(this.backgrounds[layer.name].baseScale);
		this.updateZoomLevels();
	}

	addForegroundLayer(mapLayer) {
		if (this.layers[mapLayer.name] == undefined) {
			this.layers[mapLayer.name] = mapLayer;

			this.foregroundContainer.appendChild(mapLayer.svg);
		}
	}

	/**
	 * Creates a new <image> element at the specified location, and adds it to the supplied layer. 
	 * All drawn elements must have an ID so they can be referenced sanely later.
	 * 
	 * @param {string} id 
	 * @param {string} layerName 
	 * @param {string} imageUrl 
	 * @param {int} x 
	 * @param {int} y 
	 * @returns {HTMLElement}
	 */
	drawImage(id, layerName, imageUrl, x, y, width = null, height = null) {
		if (this.layers[layerName] == undefined && this.dataLayers[layerName] == undefined) {
			console.log('Tried to draw an image ('+imageUrl+') to a nonexistent layer "' + layerName +'"');
			return;
		}
		var dataLayer = false;
		if (this.layers[layerName] == undefined) {
			dataLayer = true;
		}
		if (!dataLayer && this.layers[layerName].mapElements[id] != undefined ||
			dataLayer && this.dataLayers[layerName].mapElements[id] != undefined) {
			console.log('Tried to draw an image ('+imageUrl+') with a duplicate ID "' + id + '"');
			return;
		}
		var imageElement = document.createElementNS('http://www.w3.org/2000/svg', 'image');
		imageElement.id = id;
		imageElement.setAttribute('x', x);
		imageElement.setAttribute('y', y);
		if (width) {
			imageElement.setAttribute('width', width);
		}
		if (height) {
			imageElement.setAttribute('height', height);
		}
		imageElement.setAttributeNS('http://www.w3.org/1999/xlink',"href",imageUrl);

		if (dataLayer) {
			this.dataLayers[layerName].mapElements[id] = imageElement;
			this.dataLayers[layerName].svg.appendChild(imageElement);
		} else {
			this.layers[layerName].mapElements[id] = imageElement;
			this.layers[layerName].svg.appendChild(imageElement);
		}

		return imageElement;
	}

	/**
	 * Creates a new <text> element at the specified location, and adds it to the supplied layer.
	 * All drawn elements must have an ID so they can be referenced sanely later.
	 * 
	 * @param {string} id 
	 * @param {string} layerName 
	 * @param {string} text 
	 * @param {int} x 
	 * @param {int} y 
	 * @returns {HTMLElement}
	 */
	drawText(id, layerName, text, x, y, size = '16px', color = 'white', filter = null) {
		if (this.layers[layerName] == undefined && this.dataLayers[layerName] == undefined) {
			console.log('Tried to draw text ('+text+') to a nonexistent layer "' + layerName +'"');
			return;
		}
		var dataLayer = false;
		if (this.layers[layerName] == undefined) {
			dataLayer = true;
		}
		if (!dataLayer && this.layers[layerName].mapElements[id] != undefined ||
			dataLayer && this.dataLayers[layerName].mapElements[id] != undefined) {
			console.log('Tried to draw text ('+text+') with a duplicate ID "' + id + '"');
			return;
		}
		var textElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
		textElement.id = id;
		textElement.setAttribute('x', x);
		textElement.setAttribute('y', y);
		textElement.style.textAnchor = 'middle';
		textElement.style.fontSize = size;
		textElement.style.fill = color;
		if (filter) {
			textElement.setAttribute('filter', filter);
		}
		textElement.textContent = text;

		if (dataLayer) {
			this.dataLayers[layerName].mapElements[id] = textElement;
			this.dataLayers[layerName].svg.appendChild(textElement);
		} else {
			this.layers[layerName].mapElements[id] = textElement;
			this.layers[layerName].svg.appendChild(textElement);
		}

		return textElement;
	}

	/**
	 * Creates a new <path> element at the specified location, with the specified points, and adds it to the supplied layer.
	 * All drawn elements must have an ID so they can be referenced sanely later.
	 * 
	 * @param {string} id the DOM ID to give the new Path object
	 * @param {string} layerName the name of the layer to add the object to
	 * @param {array} points the array of points to specify the path
	 * @returns {HTMLElement} the new path element
	 */
	drawPath(id, layerName, points, closed = true, strokeWidth = 2, strokeColor = 'rgba(255,0,0,0.5)', fillColor = 'rgba(0,0,255,0.05)') {
		if (this.layers[layerName] == undefined && this.dataLayers[layerName] == undefined) {
			console.log('Tried to draw text ('+text+') to a nonexistent layer "' + layerName +'"');
			return;
		}
		var dataLayer = false;
		if (this.layers[layerName] == undefined) {
			dataLayer = true;
		}
		if (!dataLayer && this.layers[layerName].mapElements[id] != undefined ||
			dataLayer && this.dataLayers[layerName].mapElements[id] != undefined) {
			console.log('Tried to draw path ('+path+') with a duplicate ID "' + id + '" in layer ' + layerName);
			return;
		}
		var pathElement = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		pathElement.id = id;
		pathElement.setAttribute('stroke-width', strokeWidth);
		pathElement.setAttribute('stroke', strokeColor);
		pathElement.setAttribute('fill', fillColor);
		pathElement.points = points;
		var pathString = pointsToPath(points, closed);
		pathElement.setAttribute('d', pathString);

		if (dataLayer) {
			this.dataLayers[layerName].mapElements[id] = pathElement;
			this.dataLayers[layerName].svg.appendChild(pathElement);
		} else {
			this.layers[layerName].mapElements[id] = pathElement;
			this.layers[layerName].svg.appendChild(pathElement);
		}

		return pathElement;
	}

	/**
	 * Creates a new <use> element at the specified location, and adds it to the supplied layer. 
	 * All drawn elements must have an ID so they can be referenced sanely later.
	 * 
	 * @param {string} id 
	 * @param {string} layerName 
	 * @param {string} defId 
	 * @param {int} x 
	 * @param {int} y 
	 * @returns {HTMLElement}
	 */
	drawDefined(id, layerName, defId, x, y, width = null, height = null) {
		if (this.layers[layerName] == undefined && this.dataLayers[layerName] == undefined) {
			console.log('Tried to add a predefined SVG element ('+defId+') to a nonexistent layer "' + layerName +'"');
			return;
		}
		var dataLayer = false;
		if (this.layers[layerName] == undefined) {
			dataLayer = true;
		}
		if (!dataLayer && this.layers[layerName].mapElements[id] != undefined ||
			dataLayer && this.dataLayers[layerName].mapElements[id] != undefined) {
			console.log('Tried to add a predefined SVG element ('+defId+') with a duplicate ID "' + id + '"');
			return;
		}
		var useElement = document.createElementNS('http://www.w3.org/2000/svg', 'use');
		useElement.id = id;
		useElement.setAttribute('x', x);
		useElement.setAttribute('y', y);
		if (width) {
			useElement.setAttribute('width', width);
		}
		if (height) {
			useElement.setAttribute('height', height);
		}
		useElement.setAttributeNS('http://www.w3.org/1999/xlink',"href",'#' + defId);

		if (dataLayer) {
			this.dataLayers[layerName].mapElements[id] = useElement;
			this.dataLayers[layerName].svg.appendChild(useElement);
		} else {
			this.layers[layerName].mapElements[id] = useElement;
			this.layers[layerName].svg.appendChild(useElement);
		}

		return useElement;
	}

	/**
	 * Moves the specified image element by the specified offset.
	 * 
	 * @param {string} id the ID of the image element to move
	 * @param {int} offsetX how many pixels to move the element to the right
	 * @param {int} offsetY how many pixels to move the element down
	 */
	moveImage(id, offsetX, offsetY) {
		var imageElement = this.querySelector('#' + id);
		if (!imageElement) {
			console.log('Tried to move a nonexistent image element ' + id);
			return;
		}
		if (!(imageElement instanceof SVGImageElement)) {
			console.log('Tried to move an element ' + id +' that wasn\'t an image element.');
			return;
		}

		var x = parseInt(imageElement.getAttribute('x'));
		var y = parseInt(imageElement.getAttribute('y'));
		x += offsetX;
		y += offsetY;
		imageElement.setAttribute('x', x);
		imageElement.setAttribute('y', y);
	}

	/**
	 * Moves the specified text element by the specified offset.
	 * 
	 * @param {string} id the ID of the text element to move
	 * @param {int} offsetX how many pixels to move the element to the right
	 * @param {int} offsetY how many pixels to move the element down
	 */
	moveText(id, offsetX, offsetY) {
		var textElement = this.querySelector('#' + id);
		if (!textElement) {
			console.log('Tried to move a nonexistent text element ' + id);
			return;
		}
		if (!(textElement instanceof SVGImageElement)) {
			console.log('Tried to move an element ' + id +' that wasn\'t a text element.');
			return;
		}

		var x = parseInt(textElement.getAttribute('x'));
		var y = parseInt(textElement.getAttribute('y'));
		x += offsetX;
		y += offsetY;
		textElement.setAttribute('x', x);
		textElement.setAttribute('y', y);
	}

	/**
	 * Moves the specified path element by the specified offset, adjusting each of its points individually and recalculating the path string.
	 * 
	 * @param {string} id the ID of the path element to move
	 * @param {int} offsetX how many pixels to move the element to the right
	 * @param {int} offsetY how many pixels to move the element down
	 */
	movePath(id, offsetX, offsetY) {
		var pathElement = this.querySelector('#' + id);
		if (!pathElement) {
			console.log('Tried to move a nonexistent path element ' + id);
			return;
		}
		if (!(pathElement instanceof SVGPathElement)) {
			console.log('Tried to move an element ' + id +' that wasn\'t a path element.');
			return;
		}
		for (var i = 0; i < pathElement.points.length; i++) {
			pathElement.points[i].x += offsetX;
			pathElement.points[i].y += offsetY;
		}
		var pathString = pointsToPath(pathElement.points);
		pathElement.setAttribute('d', pathString);
	}

	// Need to genericize data loading better
	// maybe a member function to process each data item, that takes the item and the layer
	// also a member function to process the whole thing (which then calls the per-item method)
	// then these can be overridden in the subclass(es) to do their thing *plus* add some of the domain-specific data/metadata/etc?

	loadAllLayerData() {
		console.log('Loading layer data...');
		for (var g in this.layerGroups) {
			var group = this.layerGroups[g];
			if (group.loaded) {
				continue;
			}
			console.log('Loading data for layer group ' + group.name);
			this.loadLayerData(group);
			// if (group.loadUrl && (group.hasBase || group.hasNames)) {
			// 	console.log('It has a load URL of ' + group.loadUrl);
			// 	this.loadBaseLayerData(group);
			// }
			// if (group.dataUrl && group.hasData) {
			// 	console.log('It has data URL ' + group.dataUrl);
			// 	this.loadDataLayerData(group);
			// }
			group.loaded = true;
		}
		for (var l in this.layers) {
			var layer = this.layers[l];
			if (!layer.loaded && layer.loadUrl) {

			}
		}
	}

	loadLayerData(group) {
		var baseFetch;
		var dataFetch;
		var baseJSON;
		var dataJSON;
		const root = this;
		if (group.loadUrl && (group.hasBase || group.hasNames)) {
			console.log('It has a load URL of ' + group.loadUrl);
			baseFetch = fetch(group.loadUrl);
			baseFetch.then((response) => {
				baseJSON = response.json();
				baseJSON.then((data) => {
					root.loadBaseLayerData(group, data);
					if (group.dataUrl && group.hasData) {
						dataFetch = fetch(group.dataUrl);
						dataFetch.then((response) => {
							dataJSON = response.json();
							dataJSON.then((data) => {
								root.loadDataLayerData(group, data);
								this.checkAllLoaded();
							});
						});
					} else {
						this.checkAllLoaded();
					}
				});
			});
		} else if (group.dataUrl && group.hasData) {
			console.log('It has data URL ' + group.dataUrl);
			dataFetch = fetch(group.dataUrl);
			dataFetch.then((response) => {
				dataJSON = response.json();
				dataJSON.then((data) => {
					root.loadDataLayerData(group, data);
					this.checkAllLoaded();
				});
			});
		}
	}

	// loadBaseLayerData(group) {
	// 	var root = this;
	// 	$.ajax({
	// 		url: group.loadUrl,
	// 		method: 'GET',
	// 		data: { },
	// 		dataType: 'json',
	// 		error: function(xhr, status, error) {
	// 			root.logOutput("Error attempting to fetch layer group " + group.name + ".");
	// 			console.log('Error while fetching layer group ' + group.name + ': '+error);
	// 		},
	// 		success: function(data, status, xhr) {
	// 			if (data.error) {
	// 				root.logOutput('<p class="error">Error attempting to fetch layer group ' + group.name + ': '+data.error+'</p>');
	// 				console.log('Error while processing layer group ' + group.name + ': '+data.error);
	// 			} else {
	// 				console.log('Received valid load data for group ' + group.name);
	// 				var unified = false;
	// 				if (group.hasBase) {
	// 					var baseData;
	// 					if (data.base != undefined) {
	// 						baseData = data.base;
	// 					} else {
	// 						baseData = data;
	// 						unified = true;
	// 					}
	// 					var baseLayer = root.layers[group.name + 'Base'];
	// 					root.addLayerItems(baseLayer, baseData);
	// 					baseLayer.loaded = true;
	// 				}
	// 				if (group.hasNames && !unified) {
	// 					var nameData;
	// 					if (data.names != undefined) {
	// 						nameData = data.names;
	// 					} else {
	// 						nameData = data;
	// 					}
	// 					var namesLayer = root.layers[group.name + 'Names'];
	// 					root.addLayerItems(namesLayer, nameData);
	// 					namesLayer.loaded = true;
	// 				}
	// 			}
	// 		}
	// 	});
	// }

	loadBaseLayerData(group, data) {
		if (data.error) {
			this.logOutput('<p class="error">Error attempting to fetch layer group ' + group.name + ': '+data.error+'</p>');
			console.log('Error while processing layer group ' + group.name + ': '+data.error);
		} else {
			console.log('Received valid load data for group ' + group.name);
			var unified = false;
			if (group.hasBase) {
				var baseData;
				if (data.base != undefined) {
					baseData = data.base;
				} else {
					baseData = data;
					unified = true;
				}
				var baseLayer = this.layers[group.name + 'Base'];
				this.addLayerItems(baseLayer, baseData);
				baseLayer.loaded = true;
				console.log('✅ Base layer ' + baseLayer.name + ' loaded');
			}
			if (group.hasNames && !unified) {
				var nameData;
				if (data.names != undefined) {
					nameData = data.names;
				} else {
					nameData = data;
				}
				var namesLayer = this.layers[group.name + 'Names'];
				this.addLayerItems(namesLayer, nameData);
				namesLayer.loaded = true;
				console.log('✅ Names layer ' + namesLayer.name + ' loaded');
			}
		}
	}

	// loadDataLayerData(group) {
	// 	var root = this;
	// 	$.ajax({
	// 		url: group.dataUrl,
	// 		method: 'GET',
	// 		data: { },
	// 		dataType: 'json',
	// 		error: function(xhr, status, error) {
	// 			root.logOutput("Error attempting to fetch layer group data " + group.name + ".");
	// 			console.log('Error while fetching layer group data ' + group.name + ': '+error);
	// 		},
	// 		success: function(data, status, xhr) {
	// 			if (data.error) {
	// 				root.logOutput('<p class="error">Error attempting to fetch layer group data ' + group.name + ': '+data.error+'</p>');
	// 				console.log('Error while processing layer group data ' + group.name + ': '+data.error);
	// 			} else {
	// 				console.log('Received valid data for group ' + group.name);
	// 				var dataLayer = root.layers[group.name + 'Data'];
	// 				if (!dataLayer.loaded) {
	// 					root.addDataLayerItems(dataLayer, data);
	// 					dataLayer.loaded = true;
	// 				}
	// 			}
	// 		}
	// 	});
	// }

	loadDataLayerData(group, data) {
		if (data.error) {
			this.logOutput('<p class="error">Error attempting to fetch layer group data ' + group.name + ': '+data.error+'</p>');
			console.log('Error while processing layer group data ' + group.name + ': '+data.error);
		} else {
			console.log('Received valid data for group ' + group.name);
			var dataLayer = this.layers[group.name + 'Data'];
			if (!dataLayer.loaded) {
				this.addDataLayerItems(dataLayer, data);
				dataLayer.loaded = true;
				console.log('✅ Data layer ' + dataLayer.name + ' loaded');
			}
		}
	}

	checkAllLoaded() {
		if (!this.childrenFound) {
			return;
		}
		var allLoaded = true;
		for (var i in this.layers) {
			var layer = this.layers[i];
			if (layer.dataUrl != "" && !layer.loaded) {
				allLoaded = false;
				break;
			}
		}

		if (allLoaded) {
			this.postLoad();
		}
	}

	addLayerItems(layer, itemList) {
		for (var i in itemList) {
			var item = itemList[i];
			this.addLayerItem(layer, item);
		}
	}

	addDataLayerItems(layer, itemList) {
		for (var i in itemList) {
			var item = itemList[i];
			if (item.svgClass == undefined) {
				item.svgClass = '';
			} else if (item.svgClass != '') {
				item.svgClass += ' ';
			}
			item.svgClass += item.layer + 'Data';
			var dataLayer = this.layers[item.layer + 'Data'];
			this.addLayerItem(dataLayer, item, item.layer);
		}
	}

	addLayerItem(layer, item, dataLayerName = null) {
		var layerGroupPrefix = layer.group ? layer.group + '-' : '';
		if (dataLayerName) {
			layerGroupPrefix += dataLayerName + '-';
			layer = this.layers[dataLayerName + 'Data'];
		}
		var svgLayerName = layer.name;
		var layerClass = layerGroupPrefix + svgLayerName;
		var itemSvg = null;
		switch (item.type) {
			case 'image':
				var x = item.x;
				var y = item.y;
				itemSvg = this.drawImage(layerGroupPrefix + item.id + '-' + svgLayerName + '-image', svgLayerName, item.value, x, y, item.width, item.height);
				itemSvg.classList.add(layerClass + '-image');
				itemSvg.style.pointerEvents = 'none';
				if (item.svgClass != undefined) {
					itemSvg.classList.add(item.svgClass);
				}
				break;
			case 'path':
				var closed = true;
				if (item.isClosed != undefined && (item.isClosed == 'false' || item.closed == false)) {
					closed = false;
				}
				itemSvg = this.drawPath(layerGroupPrefix + item.id + '-' + svgLayerName + '-path', svgLayerName, item.points, closed);
				if (item.strokeColor != undefined) {
					itemSvg.setAttribute('stroke', item.strokeColor);
				}
				if (item.strokeWidth != undefined) {
					itemSvg.setAttribute('stroke-width', item.strokeWidth);
				}
				if (item.fillColor != undefined) {
					itemSvg.setAttribute('fill', item.fillColor);
				}
				if (item.opacity != undefined) {
					itemSvg.setAttribute('opacity', item.opacity);
				}
				if (item.svgClass != undefined) {
					itemSvg.classList.add(item.svgClass);
				}
				itemSvg.classList.add(layerClass + '-path');
				//itemSvg.style.pointerEvents = 'none';
				break;
			case 'text':
			default:
				var x = item.x;
				var y = item.y;
				itemSvg = this.drawText(layerGroupPrefix + item.id + '-' + svgLayerName + '-text', svgLayerName, item.value, x, y, item.height + 'px', 'black');
				if (item.style) {
					itemSvg.setAttribute('style', item.style);
				}
				if (item.svgClass != undefined) {
					itemSvg.classList.add(item.svgClass);
				}
				itemSvg.classList.add(layerClass + '-text');
				itemSvg.style.pointerEvents = 'none';
				break;
		}

		if (layer.mapElements[item.id] == undefined) {
			layer.mapElements[item.id] = [];
		}
		layer.mapElements[item.id].push(item);

		return itemSvg;
	}

	buildNewMapControls() {
		let root = this;
		this.controlsBack = document.createElement('div');
		this.controlsBack.style.position = 'absolute';
		this.controlsBack.style.fontFamily = 'Caligula, serif';
		this.controlsBack.style.left = '0px';
		this.controlsBack.style.top = '0px';
		//this.controlsBack.style.opacity = '0.5';
		this.controlsBack.style.transition = '.6s ease width, .6s ease height';
		this.controlsBack.style.width = '32px';
		this.controlsBack.style.height = '32px';
		this.controlsBack.style.backgroundColor = 'rgba(128, 128, 128, 0.5)';
		this.controlsBack.style.display = 'grid';
		this.controlsBack.style.gridTemplateRows = '32px auto auto';
		this.mapOuter.appendChild(this.controlsBack);

		this.controlsHamburgerImage = 'url(\'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg"><g><line x1="4" y1="8" x2="28" y2="8" stroke-width="3" stroke="white" /><line x1="4" y1="16" x2="28" y2="16" stroke-width="3" stroke="white" /><line x1="4" y1="24" x2="28" y2="24" stroke-width="3" stroke="white" /></g></svg>\')';
		this.controlsXImage = 'url(\'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg"><g><line x1="4" y1="4" x2="28" y2="28" stroke-width="4" stroke="white" /><line x1="4" y1="28" x2="28" y2="4" stroke-width="4" stroke="white" /></g></svg>\')';

		this.controlsTopRow = document.createElement('div');
		this.controlsTopRow.style.display = 'flex';
		this.controlsTopRow.style.flexWrap = 'wrap';
		this.controlsBack.appendChild(this.controlsTopRow);

		this.controlsButton = document.createElement('div');
		this.controlsButton.style.backgroundImage = this.controlsHamburgerImage;
		this.controlsButton.style.width = '32px';
		this.controlsButton.style.height = '32px';
		this.controlsButton.style.cursor = 'pointer';
		this.controlsButton.style.transition = '.6s ease background-image';
		this.controlsButton.onclick = function(event) { root.showControls(event) };
		this.controlsTopRow.appendChild(this.controlsButton);

		this.controlPanes = [];

		this.controlsZoom = document.createElement('div');
		this.controlsZoom.style.display = 'grid';
		this.controlsZoom.style.width = 'calc(100% - 48px)'
		this.controlsZoom.style.gridTemplateColumns = '32px auto 32px';
		this.controlsZoom.style.marginLeft = '0.5rem';
		this.controlsZoom.style.transform = 'scale(0)';
		// this.controlsZoom.style.transition = '.6s ease transform';
		this.controlsTopRow.appendChild(this.controlsZoom);

		this.controlPanes.push(this.controlsZoom);

		this.controlsZoomOut = document.createElement('button');
		this.controlsZoomOut.style.opacity = '0.5';
		this.controlsZoomOut.style.backgroundImage = 'url(' + symfonyPaths['images/Zoom-Out_32.png'] + ')';
		this.controlsZoomOut.style.width = '32px';
		this.controlsZoomOut.style.height = '32px';
		this.controlsZoomOut.style.cursor = 'pointer';
		this.controlsZoomOut.onclick = function(event) { root.zoomOut(event); };
		this.controlsZoom.appendChild(this.controlsZoomOut);

		this.controlsZoomPips = document.createElement('div');
		this.controlsZoomPips.style.display = 'flex';
		this.controlsZoomPips.style.justifyContent = 'space-around';
		this.controlsZoomPips.style.alignItems = 'center';
		this.controlsZoom.appendChild(this.controlsZoomPips);

		this.controlsZoomPipElements = [];

		this.controlsZoomIn = document.createElement('button');
		this.controlsZoomIn.style.opacity = '0.5';
		this.controlsZoomIn.style.backgroundImage = 'url(' + symfonyPaths['images/Zoom-In_32.png'] + ')';
		this.controlsZoomIn.style.width = '32px';
		this.controlsZoomIn.style.height = '32px';
		this.controlsZoomIn.style.cursor = 'pointer';
		this.controlsZoomIn.onclick = function(event) { root.zoomIn(event); };
		this.controlsZoom.appendChild(this.controlsZoomIn);

		this.controlsBackground = document.createElement('div');
		this.controlsBackground.style.display = 'none';
		this.controlsBackground.style.transform = 'scale(0)';
		this.controlsBackground.style.transition = '.6s ease transform';
		this.controlsBack.appendChild(this.controlsBackground);

		this.controlsBackgroundInner = document.createElement('div');
		this.controlsBackgroundInner.style.display = 'flex';
		this.controlsBackgroundInner.style.flexWrap = 'wrap';
		this.controlsBackgroundInner.style.textAlign = 'center';
		this.controlsBackground.appendChild(this.controlsBackgroundInner);

		this.controlPanes.push(this.controlsBackground);

		this.controlsBasicLayers = document.createElement('div');
		this.controlsBasicLayers.style.display = 'none';
		this.controlsBasicLayers.style.transform = 'scale(0)';
		this.controlsBasicLayers.style.transition = '.6s ease transform';
		this.controlsBack.appendChild(this.controlsBasicLayers);

		this.controlsBasicLayersInner = document.createElement('div');
		this.controlsBasicLayersInner.style.display = 'grid';
		this.controlsBasicLayersInner.style.gridTemplateColumns = 'auto';
		this.controlsBackground.appendChild(this.controlsBasicLayersInner);

		this.controlPanes.push(this.controlsBasicLayers);

		this.controlsDataLayers = document.createElement('div');
		this.controlsDataLayers.style.display = 'none';
		this.controlsDataLayers.style.transform = 'scale(0)';
		this.controlsDataLayers.style.transition = '.6s ease transform';
		this.controlsBack.appendChild(this.controlsDataLayers);

		this.controlPanes.push(this.controlsDataLayers);

		this.controlsDataLayersInner = document.createElement('div');
		this.controlsDataLayersInner.style.display = 'grid';
		this.controlsDataLayersInner.style.gridTemplateColumns = 'auto auto';
		this.controlsDataLayers.appendChild(this.controlsDataLayersInner);

		this.controlsLayerDivs = {};

	}

	addBackgroundLayerControl(layerInfo) {
		//console.log('Adding control for background layer ' + layerInfo.name);
		var root = this;
		var layer = this.backgrounds[layerInfo.name];
		layer.controlElement = document.createElement('div');
		layer.controlElement.classList.add('mapButton');
		layer.controlElement.style.fontFamily = 'Caligula, serif';
		layer.controlElement.style.cursor = 'pointer';
		layer.controlElement.style.flexGrow = 1;
		layer.controlElement.innerText = layerInfo.name;
		layer.controlElement.setAttribute('layerName', layerInfo.name);
		layer.controlElement.onclick = function(event) { root.switchToBackground(this.getAttribute('layerName')); };
		if (layerInfo.default) {
			layer.controlElement.classList.add('pressed');
		}
		this.controlsBackgroundInner.appendChild(layer.controlElement);
	}

	addBaseLayerControl(layer) {
		//console.log('Adding control for base layer ' + layer.name);
		var root = this;
		layer.controlBackground = document.createElement('div');
		layer.controlBackground.style.display = 'grid';
		layer.controlBackground.style.gridTemplateColumns = 'auto 5rem 5rem';
		this.controlsBasicLayersInner.appendChild(layer.controlBackground);

		layer.controlLabel = document.createElement('div');
		layer.controlLabel.fontFamily = 'Caligula, serif';
		layer.controlLabel.color = 'white';
		layer.controlLabel.textContent = layer.desc;
		layer.controlBackground.appendChild(layer.controlLabel);

		layer.controlButton = document.createElement('div');
		layer.controlButton.classList.add('mapButton');
		layer.controlButton.fontFamily = 'Caligula, serif';
		layer.controlButton.cursor = 'pointer';
		layer.controlButton.innerText = 'Poly';
		layer.controlButton.setAttribute('layerName', layer.group);
		layer.controlButton.onclick = function(event) { root.togglePolyLayer(this.getAttribute('layerName')); event.preventDefault(); };
		layer.controlBackground.appendChild(layer.controlButton);
		if (this.layers[layer.name].visible) {
			layer.controlButton.classList.add('pressed');
		}

		if (layer.group && this.layers[layer.group + 'Names'] != undefined) {
			var textLayer = this.layers[layer.group + 'Names'];
			//console.log('Adding control for names layer ' + textLayer.name);

			textLayer.controlButton = document.createElement('div');
			textLayer.controlButton.classList.add('mapButton');
			textLayer.controlButton.fontFamily = 'Caligula, serif';
			textLayer.controlButton.cursor = 'pointer';
			textLayer.controlButton.innerText = 'Text';
			textLayer.controlButton.setAttribute('layerName', layer.group);
			textLayer.controlButton.onclick = function(event) { root.toggleTextLayer(this.getAttribute('layerName')); event.preventDefault(); };
			layer.controlBackground.appendChild(textLayer.controlButton);
			if (this.layers[layer.name].visible) {
				textLayer.controlButton.classList.add('pressed');
			}
		}
	}

	addDataLayerControl(layer) {
		//console.log('Adding control for data layer ' + layer.name);
		var root = this;
		if (!layer.controllable) {
			return;
		}
		var layerDiv = document.createElement('div');
		layerDiv.setAttribute('class', 'mapButton');
		layerDiv.style.display = 'grid';
		layerDiv.style.gridTemplateColumns = '24px auto';
		layerDiv.style.cursor = 'pointer';
		layerDiv.style.margin = '0.25rem 0';
		layerDiv.setAttribute('layerName', layer.name);
		
		if (layer.iconUrl != undefined) {
			var layerIcon = document.createElement('img');
			layerIcon.src = layer.iconUrl;
			layerDiv.appendChild(layerIcon);
		} else {
			var layerIconPlaceholder = document.createElement('div');
			layerDiv.appendChild(layerIconPlaceholder);
		}

		var layerText = document.createElement('div');
		layerText.style.marginLeft = '0.25rem';
		layerText.innerHTML = layer.desc;
		layerDiv.appendChild(layerText);

		if (layer.extraInfo) {
			var layerExtraInfo = document.createElement('div');
			layerExtraInfo.style.gridColumnStart = 'span 2';
			layerExtraInfo.innerHTML = layer.extraInfo;
			layerDiv.appendChild(layerExtraInfo);
			layer.extraSvg = layerExtraInfo;
		} else {
			layer.extraSvg = null;
		}

		layerDiv.onclick = function (event) { root.toggleDataLayer(this.getAttribute('layerName')); };

		this.controlsLayerDivs[layer.name] = layerDiv;

		layer.controlButton = layerDiv;
		if (layer.visible) {
			layer.controlButton.classList.add('pressed');
		}

		this.controlsDataLayersInner.appendChild(layerDiv);

		layer.iconSvg = layerIcon;
		layer.textSvg = layerText;
	}

	showControls(event) {
		for (var p of this.controlPanes) {
			p.style.transform = 'scale(1)';
			if (p.style.display == 'none') {
				p.style.display = 'block';
			}
		}
		this.controlsBack.style.width = 'auto';
		this.controlsBack.style.height = 'auto';
		this.controlsButton.style.display = 'inline-block';

		this.controlsButton.style.backgroundImage = this.controlsXImage;
		let root = this;
		this.controlsButton.onclick = function(event) { root.hideControls(event); };

		event.preventDefault();
	}

	hideControls(event) {
		for (var p of this.controlPanes) {
			p.style.transform = 'scale(0)';
			if (p.style.display == 'block') {
				p.style.display = 'none';
			}
		}
		this.controlsBack.style.width = '32px';
		this.controlsBack.style.height = '32px';
		this.controlsButton.style.display = 'block';

		this.controlsButton.style.backgroundImage = this.controlsHamburgerImage;
		let root = this;
		this.controlsButton.onclick = function(event) { root.showControls(event); };

		event.preventDefault();
	}

	switchToBackground(layerName) {
		var newLayerName = layerName;
		var oldLayerName = this.activeBackground;
		var newLayer = this.backgrounds[newLayerName];
		var oldLayer = this.backgrounds[oldLayerName];

		oldLayer?.controlElement.classList.remove('pressed');
		newLayer.controlElement.classList.add('pressed');

		newLayer.svg.style.visibility = 'visible';
		if (newLayerName != oldLayerName) {
			if (oldLayer) {
				oldLayer.svg.style.visibility = 'hidden';
				newLayer.setScale(oldLayer.curScale);
			}
			this.activeBackground = layerName;
		}

		this.updateZoomLevels();
	}
	
	togglePolyLayer(layerGroupName) {
		var layerName = layerGroupName + 'Base';
		if (this.layers[layerName] == undefined) {
			return;
		}
		var alreadyLoaded = this.checkLayerLoaded(layerName);
		if (!alreadyLoaded) {
			this.logOutput('Loading data for ' + layerName + ' layer...');
		}
		var layer = this.layers[layerName];
		if (layer.svg.style.visibility == 'hidden') {
			layer.svg.style.visibility = 'visible';
			layer.controlButton.classList.add('pressed');
		} else {
			layer.svg.style.visibility = 'hidden';
			layer.controlButton.classList.remove('pressed');
		}
		this.updateHoverLayer();
	}

	toggleTextLayer(layerGroupName) {
		var layerName = layerGroupName + 'Names';
		if (this.layers[layerName] == undefined) {
			return;
		}
		var alreadyLoaded = this.checkLayerLoaded(layerName);
		if (!alreadyLoaded) {
			this.logOutput('Loading data for ' + layerName + ' layer...');
		}
		var layer = this.layers[layerName];
		if (layer.svg.style.visibility == 'hidden') {
			layer.svg.style.visibility = 'visible';
			layer.controlButton.classList.add('pressed');
		} else {
			layer.svg.style.visibility = 'hidden';
			layer.controlButton.classList.remove('pressed');
		}
	}
	
	toggleDataLayer(layerName) {
		if (!this.layers[layerName]) {
			return;
		}
		if (this.layers[layerName].svg.style.visibility != 'hidden') {
			this.layers[layerName].svg.style.visibility = 'hidden';
			this.layers[layerName].controlButton.classList.remove('pressed');
		} else {
			this.layers[layerName].svg.style.visibility = 'visible';
			this.layers[layerName].controlButton.classList.add('pressed');
		}
	}

	updateHoverLayer() {
		var topIndex = -1;
		var topLayer = null;
		var oldLayer = null;
		for (var lKey of Object.keys(this.layers)) {
			var l = this.layers[lKey];
			// The sea layer is always hoverable
			if (l.name == 'sea') {
				continue;
			}
			if (l.svg.style.visibility == 'visible') {
				if (l.zIndex > topIndex) {
					topIndex = l.zIndex;
					topLayer = l;
				}
			}
			if (l.svg.hasAttribute('hoverlayer')) {
				oldLayer = l;
			}
		}
		if (oldLayer != topLayer) {
			if (oldLayer) {
				oldLayer.svg.removeAttribute('hoverlayer');
			}
			topLayer.svg.setAttribute('hoverlayer', 'true');
			//console.log('setting ' + topLayer.name + ' layer as hover layer');
		}
	}

	updateZoomLevels() {
		var zoomLevels = this.backgrounds[this.activeBackground].countScales();
		while (this.controlsZoomPips.children.length > 0) {
			this.controlsZoomPips.children[0].remove();
		}
		this.controlsZoomPipElements = [];
		for (var i = 0; i < zoomLevels; i++) {
			var pip = document.createElement('div');
			// pip.style.flexGrow = 1;
			if (i == this.backgrounds[this.activeBackground].curScaleIndex()) {
				pip.textContent = '•';
			} else {
				pip.textContent = '-';
			}
			this.controlsZoomPips.appendChild(pip);
			this.controlsZoomPipElements.push(pip);
		}
	}

	setZoomPip(index) {
		for (var i = 0; i < this.controlsZoomPipElements.length; i++) {
			if (i == index) {
				this.controlsZoomPipElements[i].textContent = '•';
			} else {
				this.controlsZoomPipElements[i].textContent = '-';
			}
		}
	}

	zoomIn(event) {
		this.zoomBy(1, event);
	}
	zoomOut(event) {
		this.zoomBy(-1, event);
	}

	zoomBy(increment, event) {
		var curBackground = this.backgrounds[this.activeBackground];
		var curScale = curBackground.curScale;
		var curScaleIndex = curBackground.curScaleIndex();
		var nextScaleIndex = curScaleIndex + increment;
		if (curBackground.validScales[nextScaleIndex] == undefined) {
			console.log('Tried to zoom by ' + increment + ' from ' + curScale);
			return false;
		}

		var nextScale = curBackground.validScales[nextScaleIndex];
		curBackground.setScale(nextScale);
		
		var scaleImage = curBackground.images[curBackground.scales[nextScale]];
		var scalePct = nextScale / scaleImage.width;
		var scaleHeight = Math.round(scaleImage.height * scalePct);
		this.map.setAttribute('width', nextScale);
		this.map.setAttribute('height', scaleHeight);
		this.map.setAttribute('viewBox', this.mapViewboxX + ' ' + this.mapViewboxY + ' ' + scaleImage.width + ' ' + scaleImage.height);
		this.mapContentWidth = scaleImage.width;
		this.mapContentHeight = scaleImage.height;
		//console.log('Zoomed from ' + curScale + ' to ' + nextScale + ' on ' + curBackground.name);

		this.setZoomPip(nextScaleIndex);
		for (var i in this.layers) {
			if (this.layers[i].parent != undefined) {
				continue;
			}
			this.layers[i].svg.setAttribute('transform', 'scale(' + scaleImage.scaleFactor + ')');
		}
		if (nextScaleIndex + 1 >= curBackground.validScales.length) {
			this.controlsZoomIn.style.opacity = '0.25';
			this.controlsZoomIn.setAttribute('disabled', 'disabled');
		} else {
			this.controlsZoomIn.style.opacity = '0.5';
			this.controlsZoomIn.removeAttribute('disabled');
		}
		if (nextScaleIndex - 1 < 0) {
			this.controlsZoomOut.style.opacity = '0.25';
			this.controlsZoomOut.setAttribute('disabled', 'disabled');
		} else {
			this.controlsZoomOut.style.opacity = '0.5';
			this.controlsZoomOut.removeAttribute('disabled');
		}

		// var scaleIndex;
		// var indexMax;
		// if (this.bigMap) {
		// 	scaleIndex = this.bigScales.indexOf(this.mapScale);
		// 	indexMax = (this.bigScales.length - 1);
		// } else {
		// 	scaleIndex = this.validScales.indexOf(this.mapScale);
		// 	indexMax = (this.validScales.length - 1);
		// }
		// if (scaleIndex < indexMax) {
		// 	scaleIndex++;
		// } else if (!this.bigMap) {
		// 	this.bigMap = true;
		// 	scaleIndex = 0;
		// 	this.layers[this.activeBackground + 'Map'].setScale(2);
		// }
		// if (this.bigMap) {
		// 	this.mapScale = this.bigScales[scaleIndex];
		// } else {
		// 	this.mapScale = this.validScales[scaleIndex];
		// }
		// if (this.mapScale == this.bigScales[this.bigScales.length - 1]) {
		// 	this.zoomInIcon.style.fill = 'gray';
		// }
		// this.zoomOutIcon.style.fill = 'black';
		// this.map.setAttribute("width", this.mapWidth * this.mapScale);
		// this.map.setAttribute("height", this.mapHeight * this.mapScale);

		if (event) {
			event.stopPropagation();
		}
	}

	grabMap(event) {
		var grabPoint = {'x': event.offsetX, 'y': event.offsetY};
		this.grabMapAt(grabPoint);
	}
	grabMapTouch(event) {
		event.preventDefault();
		var grabPoint = {'x': event.touches[0].clientX, 'y': event.touches[0].clientY};
		this.grabMapAt(grabPoint);
	}
	grabMapAt(point) {
		var root = this;
		this.dragX = point.x;
		this.dragY = point.y;
		this.dragStartOffsetX = this.mapViewboxX;
		this.dragStartOffsetY = this.mapViewboxY;
		//console.log('~ Saving grab start at (' + this.dragX + ', ' + this.dragY + ') and start offset at (' + this.dragStartOffsetX + ', ' + this.dragStartOffsetY + ')');
		this.map.onmousemove = function(event) { root.dragMap(event) };
		this.map.ontouchmove = function(event) { root.dragMapTouch(event); };
		document.onmouseup = function(event) { root.ungrabMap(event); };
		document.ontouchend = function(event) { root.ungrabMapTouch(event); };
		this.map.style.userSelect = 'none';
		this.map.style.cursor = 'grabbing';
	}
	ungrabMap(event) {
		this.map.ontouchmove = null;
		this.map.onmousemove = null;
		this.map.style.cursor = 'grab';
	}
	ungrabMapTouch(event) {
		event.preventDefault();
		this.map.ontouchmove = null;
		this.map.ontouchmove = null;
		this.map.style.cursor = 'grab';
	}
	dragMap(event) {
		var dragPoint = {'x': event.offsetX, 'y': event.offsetY};
		this.dragMapTo(dragPoint);
	}
	dragMapTouch(event) {
		event.preventDefault();
		var dragPoint = {'x': event.touches[0].clientX, 'y': event.touches[0].clientY};
		this.dragMapTo(dragPoint);
	}
	dragMapTo(point) {
		var mapX = point.x;
		var mapY = point.y;

		var moveX = this.dragX - mapX;
		var moveY = this.dragY - mapY;

		//console.log('Grabbed map at (' + mapX + ', ' + mapY + '); moving by (' + moveX + ', ' + moveY + ')');

		this.mapViewboxX = this.dragStartOffsetX + moveX;
		if (this.mapViewboxX < 0) {
			this.mapViewboxX = 0;
		}
		if (this.mapViewboxX > this.mapContentWidth - this.mapFrameWidth) {
			this.mapViewboxX = this.mapContentWidth - this.mapFrameWidth;
		}
		this.mapViewboxY = this.dragStartOffsetY + moveY;
		if (this.mapViewboxY < 0) {
			this.mapViewboxY = 0;
		}
		if (this.mapViewboxY > this.mapContentHeight - this.mapFrameHeight) {
			this.mapViewboxY = this.mapContentHeight - this.mapFrameHeight;
		}
		var viewboxWidth = this.mapContentWidth < this.mapFrameWidth ? this.mapContentWidth : this.mapFrameWidth;
		var viewboxHeight = this.mapContentHeight < this.mapFrameHeight ? this.mapContentHeight : this.mapFrameHeight;
		var newViewBox = this.mapViewboxX + ' ' + this.mapViewboxY + ' ' + viewboxWidth + ' ' + viewboxHeight;
		console.log('- New view box: ' + newViewBox);
		this.map.setAttribute('viewBox', newViewBox);
	}

	centerPoint(toX, toY) {
		console.log("Centering map on (" + toX + ", " + toY + ")");
		this.dragStartOffsetX = this.mapViewboxX;
		this.dragStartOffsetY = this.mapViewboxY;
		var curCenter = new MapPoint();
		curCenter.x = this.mapViewboxX + this.mapFrameWidth / 2;
		curCenter.y = this.mapViewboxY + this.mapFrameHeight / 2;
		var theCenter = new MapPoint();
		theCenter.x = toX; // - (this.mapFrameWidth / 2);
		theCenter.y = toY; // - (this.mapFrameHeight / 2);
		this.dragX = theCenter.x;
		this.dragY = theCenter.y;
		this.dragMapTo(curCenter);
		// this.mapInner.scrollTo(toX - this.mapInner.clientWidth / 2, toY - this.mapInner.clientHeight / 2);
		// if (this.mapInner.scrollTop == 0) {
		// 	window.scrollTo(toX, toY);
		// }
	}
	centerRegion(regionId) {
		var scaleFactor = this.backgrounds[this.activeBackground].curScale / this.backgrounds[this.activeBackground].baseScale;
		var toX = scaleFactor * this.regionData[regionId].x;
		var toY = scaleFactor * this.regionData[regionId].y;
		this.centerPoint(toX, toY);
	}

	// zoomOut(event) {
	// 	var scaleIndex;
	// 	var indexMax;
	// 	if (this.bigMap) {
	// 		scaleIndex = this.bigScales.indexOf(this.mapScale);
	// 		indexMax = (this.bigScales.length - 1);
	// 	} else {
	// 		scaleIndex = this.validScales.indexOf(this.mapScale);
	// 		indexMax = (this.validScales.length - 1);
	// 	}
	// 	if (scaleIndex > 0) {
	// 		scaleIndex--;
	// 	} else if (this.bigMap) {
	// 		this.bigMap = false;
	// 		scaleIndex = this.validScales.length - 1;
	// 		this.layers[this.activeBackground + 'Map'].setScale(1);
	// 	}
	// 	if (this.bigMap) {
	// 		this.mapScale = this.bigScales[scaleIndex];
	// 	} else {
	// 		this.mapScale = this.validScales[scaleIndex];
	// 	}
	// 	if (this.mapScale == this.validScales[0]) {
	// 		this.zoomOutIcon.style.fill = 'gray';
	// 	}
	// 	this.zoomInIcon.style.fill = 'black';
	// 	this.map.setAttribute("width", this.mapWidth * this.mapScale);
	// 	this.map.setAttribute("height", this.mapHeight * this.mapScale);

	// 	if (event) {
	// 		event.stopPropagation();
	// 	}
	// }

	// oldInit(dataURLs, worldId) {

	// 	this.selectedOpacity = 0.8;
	// 	this.hoverOpacity = 0.6;
	// 	this.deselectedOpacity = 0.25;
	// 	this.baseOpacity = 0;
	// 	this.regionBorderOpacity = 0.3;

	// 	this.selectedRegion = false;
	// 	this.selectedSeaZone = false;

	// 	this.regionClickEnabled = true;
	// 	this.seaClickEnabled = true;
	// 	this.seaClickCallback = null;
	// 	this.regionClickCallback = null;
	// 	this.regionClickCallbacks = [];

	// 	this.regionHoverEnabled = true;
	// 	this.seaHoverEnabled = true;
	// 	this.regionHoverCallback = null;
	// 	this.seaHoverCallback = null;
	// 	this.curHoverRegPoly = false;
	// 	this.curHoverSeaPoly = false;
	// 	this.curHoverRealmPoly = false;
	// 	this.curHoverDuchyPoly = false;

	// 	this.realmData = [];
	// 	this.duchyData = [];
	// 	this.regionData = [];
	// 	this.seaData = [];
	// 	this.realmDataLoaded = false;
	// 	this.duchyDataLoaded = false;
	// 	this.regionDataLoaded = false;

	// 	this.regionColors = [];

	// 	this.myLocationId = false;
	// 	this.mySeaLocationId = false;
	// 	this.centerRegionId = false;
	// 	this.worldId = worldId;

	// 	this.routes = [];
	// 	this.ways = [];

	// 	this.regionsWithText = [];
	// 	this.regionTexts = {};

	// 	this.layerInfoUrl = dataURLs['layers'];
	// 	this.realmInfoUrl = dataURLs['realm'];
	// 	this.duchyInfoUrl = dataURLs['duchy'];
	// 	this.wayInfoUrl = dataURLs['way'];

	// 	this.polygonLayerNames = ['region','realm','duchy'];
	// 	this.polygonLayers = {};
	// 	this.textLayers = {'region': false, 'realm': false, 'duchy': false};

	// 	if (this.worldId == 4) {
	// 		this.substitutions['~~rogueBreak~~'] = '12';
	// 	} else {
	// 		this.substitutions['~~rogueBreak~~'] = '5';
	// 	}

	// 	this.dataLayers = {};

	// 	this.mapInner = this.map.parentElement;
	// 	this.mapOuter = this.mapInner.parentElement;
	// 	this.outputContainer = this.mapOuter.querySelector('#output');
	// 	this.mainMapDefs = this.map.querySelector('defs');
	// 	this.backgroundContainer = this.map.querySelector('#backgroundLayers');

	// 	this.mapContentWidth = this.map.width;
	// 	this.mapContentHeight = this.map.height;
	// }

	// getDataLayerDefs() {
	// 	if (!this.layerInfoUrl) {
	// 		console.log('Attempted to get layer info without layer info URL set!');
	// 		return;
	// 	}
	// 	const root = this;
	// 	console.log('Loading layer defs from url ' + this.layerInfoUrl);
	// 	$.ajax({
	// 		url: this.layerInfoUrl,
	// 		method: 'POST',
	// 		data: { },
	// 		dataType: 'json',
	// 		error: function(xhr, status, error) {
	// 			root.logOutput("Error attempting to fetch layer defs.");
	// 			console.log('Error while fetching layer defs: '+error);
	// 		},
	// 		success: function(data, status, xhr) {
	// 			if (data.error) {
	// 				root.logOutput('<p class="error">Error attempting to fetch layer defs: '+data.error+'</p>');
	// 				console.log('Error while processing layer defs: '+data.error);
	// 			} else {
	// 				root.dataLayerDefs = data;

	// 				for (var layerName in data) {
	// 					root.dataLayers[layerName] = {};
	// 					root.dataLayers[layerName].svg = document.createElementNS('http://www.w3.org/2000/svg','g');
	// 					root.dataLayers[layerName].svg.id = 'dataLayer_' + layerName;
	// 					root.map.appendChild(root.dataLayers[layerName].svg);

	// 					if (root.dataLayerDefs[layerName].extraInfo) {
	// 						for (var subst of Object.keys(root.substitutions)) {
	// 							root.dataLayerDefs[layerName].extraInfo = root.dataLayerDefs[layerName].extraInfo.replaceAll(subst, root.substitutions[subst]);
	// 						}
	// 					}
	// 				}

	// 				// Once we have the data layer defs, we can do more initialization
	// 				root.getRegionInfo();
	// 				root.controls = root.buildNewMapControls();
	// 			}
	// 		}
	// 	});
	// }

	// buildNewMapControls() {
	// 	let root = this;
	// 	this.controlsBack = document.createElement('div');
	// 	this.controlsBack.style.position = 'absolute';
	// 	this.controlsBack.style.fontFamily = 'Caligula, serif';
	// 	this.controlsBack.style.left = '0px';
	// 	this.controlsBack.style.top = '0px';
	// 	//this.controlsBack.style.opacity = '0.5';
	// 	this.controlsBack.style.transition = '.6s ease width, .6s ease height';
	// 	this.controlsBack.style.width = '32px';
	// 	this.controlsBack.style.height = '32px';
	// 	this.controlsBack.style.backgroundColor = 'rgba(128, 128, 128, 0.5)';
	// 	this.controlsBack.style.display = 'grid';
	// 	this.controlsBack.style.gridTemplateRows = '32px auto auto';
	// 	this.mapOuter.appendChild(this.controlsBack);

	// 	this.controlsHamburgerImage = 'url(\'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg"><g><line x1="4" y1="8" x2="28" y2="8" stroke-width="3" stroke="white" /><line x1="4" y1="16" x2="28" y2="16" stroke-width="3" stroke="white" /><line x1="4" y1="24" x2="28" y2="24" stroke-width="3" stroke="white" /></g></svg>\')';
	// 	this.controlsXImage = 'url(\'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg"><g><line x1="4" y1="4" x2="28" y2="28" stroke-width="4" stroke="white" /><line x1="4" y1="28" x2="28" y2="4" stroke-width="4" stroke="white" /></g></svg>\')';

	// 	this.controlsTopRow = document.createElement('div');
	// 	this.controlsTopRow.style.display = 'flex';
	// 	this.controlsTopRow.style.flexWrap = 'wrap';
	// 	this.controlsBack.appendChild(this.controlsTopRow);

	// 	this.controlsButton = document.createElement('div');
	// 	this.controlsButton.style.backgroundImage = this.controlsHamburgerImage;
	// 	this.controlsButton.style.width = '32px';
	// 	this.controlsButton.style.height = '32px';
	// 	this.controlsButton.style.cursor = 'pointer';
	// 	this.controlsButton.style.transition = '.6s ease background-image';
	// 	this.controlsButton.onclick = function(event) { root.showControls(event) };
	// 	this.controlsTopRow.appendChild(this.controlsButton);

	// 	this.controlPanes = [];

	// 	this.controlsZoom = document.createElement('div');
	// 	this.controlsZoom.style.display = 'none';
	// 	this.controlsZoom.style.marginLeft = '0.5rem';
	// 	this.controlsZoom.style.transform = 'scale(0)';
	// 	this.controlsZoom.style.transition = '.6s ease transform';
	// 	this.controlsTopRow.appendChild(this.controlsZoom);

	// 	this.controlPanes.push(this.controlsZoom);

	// 	this.controlsZoomOut = document.createElement('button');
	// 	this.controlsZoomOut.style.opacity = '0.5';
	// 	this.controlsZoomOut.style.backgroundImage = 'url(' + symfonyPaths['images/Zoom-Out_32.png'] + ')';
	// 	this.controlsZoomOut.style.width = '32px';
	// 	this.controlsZoomOut.style.height = '32px';
	// 	this.controlsZoom.appendChild(this.controlsZoomOut);

	// 	this.controlsZoomIn = document.createElement('button');
	// 	this.controlsZoomIn.style.opacity = '0.5';
	// 	this.controlsZoomIn.style.backgroundImage = 'url(' + symfonyPaths['images/Zoom-In_32.png'] + ')';
	// 	this.controlsZoomIn.style.width = '32px';
	// 	this.controlsZoomIn.style.height = '32px';
	// 	this.controlsZoom.appendChild(this.controlsZoomIn);

	// 	this.controlsBackground = document.createElement('div');
	// 	this.controlsBackground.style.display = 'none';
	// 	this.controlsBackground.style.transform = 'scale(0)';
	// 	this.controlsBackground.style.transition = '.6s ease transform';
	// 	this.controlsBack.appendChild(this.controlsBackground);

	// 	this.controlsBackgroundInner = document.createElement('div');
	// 	this.controlsBackgroundInner.style.display = 'flex';
	// 	this.controlsBackgroundInner.style.flexWrap = 'wrap';
	// 	this.controlsBackgroundInner.style.justifyContent = 'space-between';
	// 	this.controlsBackground.appendChild(this.controlsBackgroundInner);

	// 	this.controlPanes.push(this.controlsBackground);

	// 	this.controlsBasicLayers = document.createElement('div');
	// 	this.controlsBasicLayers.style.display = 'none';
	// 	this.controlsBasicLayers.style.transform = 'scale(0)';
	// 	this.controlsBasicLayers.style.transition = '.6s ease transform';
	// 	this.controlsBack.appendChild(this.controlsBasicLayers);

	// 	this.controlsBasicLayersInner = document.createElement('div');
	// 	this.controlsBasicLayersInner.style.display = 'grid';
	// 	this.controlsBasicLayersInner.style.gridTemplateColumns = 'auto auto';
	// 	this.controlsBackground.appendChild(this.controlsBasicLayersInner);

	// 	this.controlPanes.push(this.controlsBasicLayers);

	// 	this.controlsDataLayers = document.createElement('div');
	// 	this.controlsDataLayers.style.display = 'none';
	// 	this.controlsDataLayers.style.transform = 'scale(0)';
	// 	this.controlsDataLayers.style.transition = '.6s ease transform';
	// 	this.controlsBack.appendChild(this.controlsDataLayers);

	// 	this.controlPanes.push(this.controlsDataLayers);

	// 	// this.controlsGeoBack = document.createElement('div');
	// 	// this.controlsGeoBack.style.fontFamily = 'Caligula, serif';
	// 	// this.controlsGeoBack.style.color = 'white';
	// 	// this.controlsGeoBack.style.cursor = 'pointer';
	// 	// this.controlsGeoBack.innerText = 'Geo';
	// 	// this.controlsGeoBack.onclick = function(event) { root.switchToBackground('geographic'); };
	// 	// this.controlsBackgroundInner.appendChild(this.controlsGeoBack);

	// 	// this.controlsDetailBack = document.createElement('div');
	// 	// this.controlsDetailBack.style.fontFamily = 'Caligula, serif';
	// 	// this.controlsDetailBack.style.color = 'black';
	// 	// this.controlsDetailBack.style.cursor = 'pointer';
	// 	// this.controlsDetailBack.innerText = 'Details';
	// 	// this.controlsDetailBack.onclick = function(event) { root.switchToBackground('detail'); };
	// 	// this.controlsBackgroundInner.appendChild(this.controlsDetailBack);

	// 	var defaultBackgroundName = null;
	// 	for (var layerInfo of this.mapControlImageLayers) {
	// 		var layer = this.layers[layerInfo.id + 'Map'];
	// 		layer.controlElement = document.createElement('div');
	// 		layer.controlElement.classList.add('mapButton');
	// 		layer.controlElement.fontFamily = 'Caligula, serif';
	// 		layer.controlElement.cursor = 'pointer';
	// 		layer.controlElement.innerText = layerInfo.name;
	// 		layer.controlElement.setAttribute('layerName', layerInfo.id);
	// 		layer.controlElement.onclick = function(event) { root.switchToBackground(this.getAttribute('layerName')); };
	// 		if (layerInfo.default) {
	// 			defaultBackgroundName = layerInfo.id;
	// 		}
	// 		this.controlsBackgroundInner.appendChild(layer.controlElement);
	// 	}
	// 	if (defaultBackgroundName) {
	// 		this.switchToBackground(defaultBackgroundName);
	// 	}

	// 	for (var polyLayerInfo of this.mapControlPolygonLayers) {
	// 		var polyLayer = this.layers[polyLayerInfo.id];
	// 		if (this.layers[polyLayerInfo.id + 'Data'] == undefined) {
	// 			continue;
	// 		}
	// 		var textLayer = this.layers[polyLayerInfo.id + 'Data'];

	// 		polyLayer.controlBackground = document.createElement('div');
	// 		polyLayer.controlBackground.style.display = 'grid';
	// 		polyLayer.controlBackground.style.gridTemplateColumns = 'auto 5rem 5rem';
	// 		this.controlsBasicLayersInner.appendChild(polyLayer.controlBackground);

	// 		polyLayer.controlLabel = document.createElement('div');
	// 		polyLayer.controlLabel.fontFamily = 'Caligula, serif';
	// 		polyLayer.controlLabel.color = 'white';
	// 		polyLayer.controlLabel.textContent = polyLayerInfo.name;
	// 		polyLayer.controlBackground.appendChild(polyLayer.controlLabel);

	// 		polyLayer.controlButton = document.createElement('div');
	// 		polyLayer.controlButton.classList.add('mapButton');
	// 		polyLayer.controlButton.fontFamily = 'Caligula, serif';
	// 		polyLayer.controlButton.cursor = 'pointer';
	// 		polyLayer.controlButton.innerText = 'Poly';
	// 		polyLayer.controlButton.setAttribute('layerName', polyLayerInfo.id);
	// 		polyLayer.controlButton.onclick = function(event) { root.togglePolyLayer(this.getAttribute('layerName')); event.preventDefault(); };
	// 		polyLayer.controlBackground.appendChild(polyLayer.controlButton);
	// 		if (this.polygonLayers[polyLayerInfo.id]) {
	// 			polyLayer.controlButton.classList.add('pressed');
	// 		}

	// 		textLayer.controlButton = document.createElement('div');
	// 		textLayer.controlButton.classList.add('mapButton');
	// 		textLayer.controlButton.fontFamily = 'Caligula, serif';
	// 		textLayer.controlButton.cursor = 'pointer';
	// 		textLayer.controlButton.innerText = 'Text';
	// 		textLayer.controlButton.setAttribute('layerName', polyLayerInfo.id);
	// 		textLayer.controlButton.onclick = function(event) { root.toggleTextLayer(this.getAttribute('layerName')); event.preventDefault(); };
	// 		polyLayer.controlBackground.appendChild(textLayer.controlButton);
	// 	}

	// 	this.controlsDataLayersInner = document.createElement('div');
	// 	this.controlsDataLayersInner.style.display = 'grid';
	// 	this.controlsDataLayersInner.style.gridTemplateColumns = 'auto auto';
	// 	this.controlsDataLayers.appendChild(this.controlsDataLayersInner);

	// 	this.controlsLayerDivs = {};

	// 	for (var layerName of Object.keys(this.dataLayerDefs)) {
	// 		var layerDef = this.dataLayerDefs[layerName];
	// 		if (layerDef.category != 'base' || !layerDef.visible) {
	// 			continue;
	// 		}
	// 		var layerDiv = document.createElement('div');
	// 		layerDiv.style.display = 'grid';
	// 		layerDiv.style.gridTemplateColumns = '24px auto';
	// 		layerDiv.style.cursor = 'pointer';
	// 		layerDiv.style.margin = '0.25rem 0';
	// 		layerDiv.setAttribute('layerName', layerDef.name);
			
	// 		var layerIcon = document.createElement('img');
	// 		layerIcon.src = layerDef.iconUrl;
	// 		layerDiv.appendChild(layerIcon);

	// 		var layerText = document.createElement('div');
	// 		layerText.style.marginLeft = '0.25rem';
	// 		layerText.innerHTML = layerDef.displayName;
	// 		layerDiv.appendChild(layerText);

	// 		if (layerDef.extraInfo) {
	// 			var layerExtraInfo = document.createElement('div');
	// 			layerExtraInfo.style.gridColumnStart = 'span 2';
	// 			layerExtraInfo.innerHTML = layerDef.extraInfo;
	// 			layerDiv.appendChild(layerExtraInfo);
	// 		}

	// 		layerDiv.onclick = function (event) { root.toggleDataLayer(this.getAttribute('layerName')); };

	// 		this.controlsLayerDivs[layerDef.name] = layerDiv;

	// 		this.controlsDataLayersInner.appendChild(layerDiv);
	// 	}

	// }

	// getRegionInfo() {
	// 	const root = this;
	// 	var regionInfoUrl = '/map/regionInfo-' + this.worldId + '.json';
	// 	console.log('Loading region info from ' + regionInfoUrl);
	// 	$.ajax({
	// 		url: regionInfoUrl,
	// 		method: 'POST',
	// 		data: { },
	// 		dataType: 'json',
	// 		error: function(xhr, status, error) {
	// 			root.logOutput("Error attempting to fetch region info.");
	// 			console.log('Error while fetching region info: '+error);
	// 		},
	// 		success: function(data, status, xhr) {
	// 			if (data.error) {
	// 				root.logOutput('<p class="error">Error attempting to fetch region info: '+data.error+'</p>');
	// 				console.log('Error while processing region info: '+data.error);
	// 			} else {
	// 				for (var rId in data) {
	// 					var region = root.regionData[rId];

	// 					for (var layerName in data[rId]) {
	// 						var layerData = data[rId][layerName];

	// 						for (var layerItem of layerData) {
	// 							var itemSvg;
	// 							switch (layerItem.type) {
	// 								case 'icon':
	// 									itemSvg = document.createElementNS('http://www.w3.org/2000/svg','image');
	// 									if (layerItem.style) {
	// 										itemSvg.setAttribute('style', layerItem.style);
	// 									}
	// 									itemSvg.style.width = layerItem.width;
	// 									itemSvg.style.height = layerItem.height;
	// 									itemSvg.style.pointerEvents = 'none';
	// 									var x = region.x + layerItem.xOffset;
	// 									itemSvg.setAttribute('x', x);
	// 									var y = region.y + layerItem.yOffset;
	// 									itemSvg.setAttribute('y', y);
	// 									itemSvg.setAttributeNS('http://www.w3.org/1999/xlink',"href",layerItem.value);
	// 									break;
	// 								case 'text':
	// 								default:
	// 									itemSvg = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 									if (layerItem.style) {
	// 										itemSvg.setAttribute('style', layerItem.style);
	// 									}
	// 									itemSvg.style.width = layerItem.width;
	// 									itemSvg.style.height = layerItem.height;
	// 									itemSvg.style.pointerEvents = 'none';
	// 									var x = region.x + layerItem.xOffset;
	// 									itemSvg.setAttribute('x', x);
	// 									var y = region.y + layerItem.yOffset;
	// 									itemSvg.setAttribute('y', y);
	// 									itemSvg.textContent = layerItem.value;
	// 									break;
	// 							}

	// 							//console.log('Adding ' + layerName + ' item to region ' + rId);
	// 							root.dataLayers[layerName].svg.appendChild(itemSvg);
	// 							root.regionData[rId].appendData(layerName, layerItem);
	// 						}
	// 					}
	// 				}
	// 				root.regionDataLoaded = true;
	// 			}
	// 		}
	// 	});
	// }

	// checkLayerLoaded(layerName) {
	// 	const root = this;
	// 	switch (layerName) {
	// 		case 'realm':
	// 			if (!this.realmDataLoaded) {
	// 				if (!this.realmInfoUrl) {
	// 					console.log("Attempted to set realm info without realm info URL set");
	// 					return;
	// 				}
	// 				console.log('Loading unloaded realm data from url ' + this.realmInfoUrl);
	// 				$.ajax({
	// 					url: this.realmInfoUrl,
	// 					method: 'POST',
	// 					data: { },
	// 					dataType: 'json',
	// 					error: function(xhr, status, error) {
	// 						root.logOutput("Error attempting to fetch Realm data.");
	// 						console.log('Error while fetching Realm data: '+error);
	// 					},
	// 					success: function(data, status, xhr) {
	// 						if (data.error) {
	// 							root.logOutput('<p class="error">Error attempting to fetch realm data: '+data.error+'</p>');
	// 							console.log('Error while processing Realm data: '+data.error);
	// 						} else {
	// 							root.realmData = data.realmInfo;
	// 							for (var realmId in root.realmData) {
	// 								root.addRealmPoly(root.realmData[realmId]);
	// 								root.addRealmName(realmId, root.realmData[realmId].name);
	// 							}
	// 							root.realmDataLoaded = true;
	// 						}
	// 					}
	// 				});
	// 				return false;
	// 			} else {
	// 				return true;
	// 			}
	// 			break;
	// 		case 'duchy':
	// 			if (!this.duchyDataLoaded) {
	// 				if (!this.duchyInfoUrl) {
	// 					console.log("Attempted to set duchy info without duchy info URL set");
	// 					return;
	// 				}
	// 				console.log('Loading unloaded duchy data from url ' + this.duchyInfoUrl);
	// 				$.ajax({
	// 					url: this.duchyInfoUrl,
	// 					method: 'POST',
	// 					data: { },
	// 					dataType: 'json',
	// 					error: function(xhr, status, error) {
	// 						root.logOutput("Error attempting to fetch duchy names.");
	// 						console.log("Error fetching duchy names: "+error);
	// 					},
	// 					success: function(data, status, xhr) {
	// 						if (data.error) {
	// 							root.logOutput('<p class="error">Error attempting to fetch duchy names: '+data.error+'</p>');
	// 							console.log("Error processing duchy names: "+data.error);
	// 						} else {
	// 							root.duchyData = data.duchyInfo;
	// 							for (var duchyId in root.duchyData) {
	// 								if (root.duchyData[duchyId].poly) {
	// 									root.addDuchyPoly(root.duchyData[duchyId]);
	// 									root.addDuchyName(duchyId, root.duchyData[duchyId].name);
	// 								}
	// 							}
	// 							root.duchyDataLoaded = true;
	// 						}
	// 					}
	// 				});
	// 				return false;
	// 			} else {
	// 				return true;
	// 			}
	// 			break;
	// 		case 'region':
	// 			return this.regionData != [];
	// 			break;
	// 	}
	// }

	// selectRegion(regionId, multi=false, color=false) {
	// 	if (!multi) {
	// 		if (this.selectedRegion) {
	// 			this.deselectRegion(this.selectedRegion);
	// 		}
	// 		this.selectedRegion = regionId;
	// 	}
	// 	this.regionData[regionId].svg.style.fillOpacity = this.selectedOpacity;
	// 	if (color) {
	// 		this.regionData[regionId].svg.style.fill = color;
	// 	}
	// }
	// deselectRegion(regionId=false) {
	// 	if (regionId === false) {
	// 		regionId = this.selectedRegion;
	// 	}
	// 	if (regionId === false) {
	// 		return;
	// 	}
	// 	this.regionData[regionId].svg.style.fillOpacity = this.baseOpacity;
	// 	this.regionData[regionId].svg.style.fill = 'rgba(0,0,255,0.5)';
	// 	if (regionId == this.selectedRegion) {
	// 		this.selectedRegion = false;
	// 	}
	// }
	// clickedRegPoly(event) {
	// 	if (!this.regionClickEnabled) {
	// 		return;
	// 	}
	// 	var regionId = parseInt(event.target.attributes.regionid.value);
	// 	var keepGoing = true;
	// 	if (this.regionClickCallbacks[regionId] != undefined) {
	// 		keepGoing = this.regionClickCallbacks[regionId](regionId, event);
	// 	}
	// 	if (keepGoing && this.regionClickCallback != null) {
	// 		this.regionClickCallback(regionId, event);
	// 	}
	// }
	// clickedSeaPoly(event) {
	// 	if (!this.seaClickEnabled) {
	// 		return;
	// 	}
	// 	var seaId = parseInt(event.target.attributes.seaid.value);
	// 	if (this.seaClickCallback != null) {
	// 		this.seaClickCallback(seaId, event);
	// 	}
	// }
	// clickedRealmPoly(event) {
	// 	var id = parseInt(event.target.attributes.realmid.value);
	// 	this.logOutput(this.realmData[realmId].name);
	// 	//realmClick = true;
	// }
	// clickedDuchyPoly(event) {
	// 	var id = parseInt(event.target.attributes.duchyid.value);
	// 	this.logOutput('Duchy: '+this.duchyData[duchyId].name);
	// 	//duchyClick = true;
	// }
	// hoverSeaPoly(event) {
	// 	if (!this.seaHoverEnabled) {
	// 		return;
	// 	}
	// 	var id = parseInt(event.target.attributes.seaid.value);
	// 	var sea = this.seaData[id];
	// 	if (!sea.hoverActive) {
	// 		return;
	// 	}
	// 	if (this.seaHoverCallback) {
	// 		this.seaHoverCallback(id);
	// 	}
	// 	this.curHoverSeaPoly = id;
	// 	this.curHoverRegPoly = null;
	// }
	// hoverRegPoly(event) {
	// 	if (!this.regionHoverEnabled) {
	// 		return;
	// 	}
	// 	var id = parseInt(event.target.attributes.regionid.value);
	// 	var reg = this.regionData[id];
	// 	if (!reg.hoverActive) {
	// 		return;
	// 	}
	// 	if (this.regionHoverCallback) {
	// 		this.regionHoverCallback(id);
	// 	}
	// 	this.curHoverRegPoly = id;
	// 	this.curHoverSeaPoly = null;
	// }
	// hoverRegData(event) {
	// 	var regionId = parseInt(event.target.attributes.regionid.value);
	// 	this.regionData[regionId].svg.style.fillOpacity = this.hoverOpacity;
	// 	this.curHoverRegPoly = regionId;
	// }
	// unhoverRegData(event) {
	// 	var regionId = parseInt(event.target.attributes.regionid.value);
	// 	this.regionData[regionId].svg.style.fillOpacity = this.baseOpacity;
	// }
	// hoverRealmPoly(event) {
	// 	var id = parseInt(event.target.attributes.realmid.value);
	// 	this.curHoverRealmPoly = id;
	// }
	// hoverDuchyPoly(event) {
	// 	var id = parseInt(event.target.attributes.duchyid.value);
	// 	this.curHoverDuchyPoly = id;
	// }

	// setRegionText(regionId, text, color='white', style='') {
	// 	this.removeRegionText(regionId);
	// 	var yOffset = 0;
	// 	var innerText = '';
	// 	var textElement = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 	textElement.id = 'region'+regionId+'Text';
	// 	textElement.setAttributeNS(null, 'class', 'regionData _text');
	// 	textElement.setAttributeNS(null, 'fill', color);
	// 	textElement.setAttributeNS(null, 'style', style);
	// 	textElement.setAttributeNS(null, 'text-anchor', 'middle');
	// 	textElement.setAttributeNS(null, 'font-family', 'serif');
	// 	if (Array.isArray(text)) {
	// 		var lenMax = 0;
	// 		for (var i=0; i<text.length; i++) {
	// 			var textLine = document.createElementNS('http://www.w3.org/2000/svg','tspan');
	// 			textLine.setAttributeNS(null, 'class', 'region'+regionId+'TextLine');
	// 			textLine.setAttributeNS(null, 'x', this.regionData[regionId].x);
	// 			textLine.setAttributeNS(null, 'dy', '1.2rem');
	// 			textLine.textContent = text[i];
	// 			textElement.appendChild(textLine);
	// 		}
	// 		yOffset += 18;
	// 	} else {
	// 		textElement.textContent = text;
	// 		yOffset = 9;
	// 	}
	// 	textElement.setAttributeNS(null, 'x', this.regionData[regionId].x);
	// 	textElement.setAttributeNS(null, 'y', this.regionData[regionId].y - yOffset);

	// 	this.layers['regionData'].svg.appendChild(textElement);
	// 	this.regionTexts[regionId] = textElement;
	// 	this.regionsWithText.push(regionId);
	// }
	// removeRegionText(regionId) {
	// 	if (this.regionTexts[regionId] != undefined) {
	// 		this.regionTexts[regionId].remove();
	// 		delete this.regionTexts[regionId];
	// 	}
	// }
	// clearRegionText() {
	// 	for (var t of regionTexts) {
	// 		t.remove();
	// 	}
	// 	regionTexts = {};
	// }

	// setAllRegionNames() {
	// 	for (var id in this.regionData) {
	// 		if (!parseInt(id)) {
	// 			continue;
	// 		}
	// 		this.addRegionName(id, this.regionData[id].name);
	// 	}
	// }

	// setAllSeaZoneNames() {
	// 	for (var id in this.seaData) {
	// 		if (!parseInt(id)) {
	// 			continue;
	// 		}
	// 		this.addSeaZoneName(id, this.seaData[id].name);
	// 	}
	// }

	// addRealmName(realmId, text) {
	// 	if (this.realmData[realmId].nameSvg) {
	// 		this.realmData[realmId].nameSvg.remove();
	// 	}
	// 	var yOffset = 0;
	// 	var textElement = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 	textElement.id = 'realm'+realmId+'Name';
	// 	textElement.setAttributeNS(null, 'class', 'realmData realm'+realmId+'Data name');
	// 	textElement.setAttributeNS(null, 'fill', 'black');
	// 	textElement.setAttributeNS(null, 'style', 'font-family: Caligula, serif; font-size: 20px; fill: black;');
	// 	textElement.setAttributeNS(null, 'filter', 'url(#textGlow)');
	// 	textElement.setAttributeNS(null, 'text-anchor', 'middle');
	// 	if (Array.isArray(text)) {
	// 		for (var i=0; i<text.length; i++) {
	// 			var textLine = document.createElementNS('http://www.w3.org/2000/svg','tspan');
	// 			textLine.setAttributeNS(null, 'class', 'realm'+realmId+'TextLine');
	// 			textLine.setAttributeNS(null, 'x', this.realmData[realmId].x);
	// 			textLine.setAttributeNS(null, 'dy', '1.2rem');
	// 			textLine.textContent = text[i];
	// 			textElement.appendChild(textLine);
	// 		}
	// 		yOffset += 18;
	// 	} else {
	// 		textElement.textContent = text;
	// 		yOffset = 0;
	// 	}
	// 	textElement.setAttributeNS(null, 'x', this.realmData[realmId].x);
	// 	textElement.setAttributeNS(null, 'y', this.realmData[realmId].y - yOffset);
	// 	this.layers['realmData'].svg.appendChild(textElement);
	// 	this.realmData[realmId].nameSvg = textElement;
	// }
	// addRealmPoly(realm) {
	// 	var root = this;
	// 	realm.svg = document.createElementNS('http://www.w3.org/2000/svg','path');
	// 	realm.svg.id = 'realm-poly-'+realm.id;
	// 	realm.svg.setAttribute('d', realm.poly);
	// 	realm.svg.setAttribute('class', 'realm-poly-path');
	// 	realm.svg.setAttribute('realmid', realm.id);
	// 	realm.svg.style.fill = realm.colour;
	// 	realm.svg.style.fillOpacity = 0.5;
	// 	realm.svg.style.stroke = 'rgba(255,0,0,0.5)';
	// 	realm.svg.style.strokeWidth = 2;
	// 	realm.svg.style.strokeOpacity = this.regionBorderOpacity;
	// 	realm.svg.style.fillRule = 'evenodd';
	// 	realm.svg.onmouseover = function(event) { root.hoverRealmPoly(event); }; // TODO
	// 	realm.svg.onclick = function(event) { root.clickedRealmPoly(event); }; // TODO
	// 	this.layers['realm'].svg.appendChild(realm.svg);
	// }
	// addDuchyName(duchyId, text) {
	// 	if (this.duchyData[duchyId].nameSvg) {
	// 		this.duchyData[duchyId].nameSvg.remove();
	// 	}
	// 	var yOffset = 0;
	// 	var textElement = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 	textElement.id = 'duchy'+duchyId+'Name';
	// 	textElement.setAttributeNS(null, 'class', 'duchyData duchy'+duchyId+'Data name');
	// 	textElement.setAttributeNS(null, 'fill', 'black');
	// 	textElement.setAttributeNS(null, 'style', 'font-family: Caligula, serif; font-size: 18px; fill: black;');
	// 	textElement.setAttributeNS(null, 'filter', 'url(#textGlow)');
	// 	textElement.setAttributeNS(null, 'text-anchor', 'middle');
	// 	if (Array.isArray(text)) {
	// 		for (var i=0; i<text.length; i++) {
	// 			var textLine = document.createElementNS('http://www.w3.org/2000/svg','tspan');
	// 			textLine.setAttributeNS(null, 'class', 'duchy'+duchyId+'TextLine');
	// 			textLine.setAttributeNS(null, 'x', this.duchyData[duchyId].x);
	// 			textLine.setAttributeNS(null, 'dy', '1.2rem');
	// 			textLine.textContent = text[i];
	// 			textElement.appendChild(textLine);
	// 		}
	// 		yOffset += 18;
	// 	} else {
	// 		textElement.textContent = text;
	// 		yOffset = 0;
	// 	}
	// 	textElement.setAttributeNS(null, 'x', this.duchyData[duchyId].x);
	// 	textElement.setAttributeNS(null, 'y', this.duchyData[duchyId].y - yOffset);
	// 	this.layers['duchyData'].svg.appendChild(textElement);
	// 	this.duchyData[duchyId].nameSvg = textElement;
	// }
	// addDuchyPoly(duchy) {
	// 	var root = this;
	// 	duchy.svg = document.createElementNS('http://www.w3.org/2000/svg','path');
	// 	duchy.svg.id = 'duchy-poly-'+duchy.id;
	// 	duchy.svg.setAttribute('d', duchy.poly);
	// 	duchy.svg.setAttribute('class', 'duchy-poly-path');
	// 	duchy.svg.setAttribute('duchyid', duchy.id);
	// 	duchy.svg.style.fill = duchy.colour;
	// 	duchy.svg.style.fillOpacity = 0.5;
	// 	duchy.svg.style.stroke = 'rgba(255,0,0,0.5)';
	// 	duchy.svg.style.strokeWidth = 2;
	// 	duchy.svg.style.strokeOpacity = this.regionBorderOpacity;
	// 	duchy.svg.style.fillRule = 'evenodd';
	// 	duchy.svg.onmouseover = function(event) { root.hoverDuchyPoly(event); }; // TODO
	// 	duchy.svg.onclick = function(event) { root.clickedDuchyPoly(event); }; // TODO
	// 	this.layers['duchy'].svg.appendChild(duchy.svg);
	// }
	// addRegionName(regionId, text) {
	// 	this.regionData[regionId].nameSvg = textElement;
	// 	var yOffset = 0;
	// 	var textElement = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 	textElement.id = 'region'+regionId+'Name';
	// 	textElement.setAttributeNS(null, 'class', 'regionData region'+regionId+'Data name');
	// 	textElement.setAttributeNS(null, 'fill', 'black');
	// 	textElement.setAttributeNS(null, 'style', 'font-family: Caligula, serif; font-size: 16px; fill: black;');
	// 	textElement.setAttributeNS(null, 'filter', 'url(#textGlow)');
	// 	textElement.setAttributeNS(null, 'text-anchor', 'middle');
	// 	if (Array.isArray(text)) {
	// 		for (var i=0; i<text.length; i++) {
	// 			var textLine = document.createElementNS('http://www.w3.org/2000/svg','tspan');
	// 			textLine.setAttributeNS(null, 'class', 'region'+regionId+'TextLine');
	// 			textLine.setAttributeNS(null, 'x', this.regionData[regionId].x);
	// 			textLine.setAttributeNS(null, 'dy', '1.2rem');
	// 			textLine.textContent = text[i];
	// 			textElement.appendChild(textLine);
	// 		}
	// 		yOffset += 18;
	// 	} else {
	// 		textElement.textContent = text;
	// 		yOffset = 0;
	// 	}
	// 	textElement.setAttributeNS(null, 'x', this.regionData[regionId].x);
	// 	textElement.setAttributeNS(null, 'y', this.regionData[regionId].y - yOffset);
	// 	this.layers['regionData'].svg.appendChild(textElement);
	// 	this.regionData[regionId].nameSvg = textElement;
	// }
	// addSeaZoneName(seaId, text) {
	// 	this.seaData[seaId].nameSvg = textElement;
	// 	var yOffset = 0;
	// 	var textElement = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 	textElement.id = 'sea'+seaId+'Name';
	// 	textElement.setAttributeNS(null, 'class', 'seaData sea'+seaId+'Data name');
	// 	textElement.setAttributeNS(null, 'fill', 'black');
	// 	textElement.setAttributeNS(null, 'style', 'font-family: Caligula, serif; font-size: 16px; fill: black;');
	// 	textElement.setAttributeNS(null, 'filter', 'url(#textGlow)');
	// 	textElement.setAttributeNS(null, 'text-anchor', 'middle');
	// 	if (Array.isArray(text)) {
	// 		for (var i=0; i<text.length; i++) {
	// 			var textLine = document.createElementNS('http://www.w3.org/2000/svg','tspan');
	// 			textLine.setAttributeNS(null, 'class', 'sea'+seaId+'TextLine');
	// 			textLine.setAttributeNS(null, 'x', seaData[seaId].x);
	// 			textLine.setAttributeNS(null, 'dy', '1.2rem');
	// 			textLine.textContent = text[i];
	// 			textElement.appendChild(textLine);
	// 		}
	// 		yOffset += 18;
	// 	} else {
	// 		textElement.textContent = text;
	// 		yOffset = 0;
	// 	}
	// 	textElement.setAttributeNS(null, 'x', seaData[seaId].x);
	// 	textElement.setAttributeNS(null, 'y', seaData[seaId].y - yOffset);
	// 	this.layers['seaData'].svg.appendChild(textElement);
	// 	this.seaData[seaId].nameSvg = textElement;
	// }
	// hideSeaZoneNames() {
	// 	for (var r of this.seaData) {
	// 		if (r.nameSvg) {
	// 			r.nameSvg.style.visibility = 'hidden';
	// 		}
	// 	}
	// }
	// showSeaZoneNames() {
	// 	for (var r of this.seaData) {
	// 		if (r.nameSvg) {
	// 			r.nameSvg.style.visibility = 'visible';
	// 		}
	// 	}
	// }

	// hideRegionName(regionId) {
	// 	this.regionData[regionId].nameSvg.style.visibility = 'hidden';
	// }
	// showRegionName(regionId) {
	// 	this.regionData[regionId].nameSvg.style.visibility = 'visible';
	// }
	// hideRegionNames() {
	// 	for (var r of this.regionData) {
	// 		if (r.nameSvg) {
	// 			r.nameSvg.style.visibility = 'hidden';
	// 		}
	// 	}
	// }
	// showRegionNames() {
	// 	for (var r of this.regionData) {
	// 		if (r.nameSvg) {
	// 			r.nameSvg.style.visibility = 'visible';
	// 		}
	// 	}
	// }
	// clearAllRegionColors() {
	// 	for (var regionId in this.regionColors) {
	// 		if (regionId != 'removeElement') {
	// 			this.regionData[regionId].svg.style.fill = 'rgba(0,0,255,0.5)';
	// 		}
	// 	}
	// 	this.regionColors = [];
	// }
	// setAllRegionColors(color) {
	// 	for (var i in this.regionData) {
	// 		if ($.isNumeric(i)) {
	// 			this.regionColors[i] = color;
	// 			this.regionData[i].svg.style.fill = color;
	// 		}
	// 	}
	// }
	// unsetRegionColor(regionId) {
	// 	this.regionData[regionId].svg.style.fill = 'rgba(0,0,255,0.5)';
	// 	this.regionColors.splice(regionId, 1);
	// }
	// setRegionColor(regionId, color, opacity = false) {
	// 	this.regionData[regionId].svg.style.fill = color;
	// 	this.regionColors[regionId] = color;
	// 	if (opacity || this.regionData[regionId].svg.style.fillOpacity === "0") {
	// 		if (!opacity) {
	// 			opacity = 0.5;
	// }
	// 		this.regionData[regionId].svg.style.fillOpacity = opacity;
	// 	}
	// }
	// centerRegion(regionId) {
	// 	var toX = this.regionData[regionId].x;
	// 	var toY = this.regionData[regionId].y;
	// 	this.mapInner.scrollTo(toX - this.mapInner.clientWidth / 2, toY - this.mapInner.clientHeight / 2);
	// 	if (this.mapInner.scrollTop == 0) {
	// 		window.scrollTo(toX, toY);
	// 	}
	// }
	// findRegion(name) {
	// 	var region = null;
	// 	for (var r of this.regionData) {
	// 		if (r != undefined && r.name == name) {
	// 			region = r;
	// 			break;
	// 		}
	// 	}
	// 	if (region == null) {
	// 		return false;
	// 	}
	// 	this.centerRegion(region.id);
	// 	this.selectRegion(region.id);
	// }

	// showWay(fromReg, toReg, withArrow=false, strokeColor=false) {
	// 	var wayElement = this.ways[fromReg][toReg].svg;
	// 	var wayExtraElement = this.ways[fromReg][toReg].extraSvg;
	// 	if (wayElement) {
	// 		wayElement.visibility = 'visible';
	// 		if (wayExtraElement) {
	// 			wayExtraElement.visibility = 'visible';
	// 		}
	// 	} else {
	// 		wayElement = document.createElementNS('http://www.w3.org/2000/svg','line');
	// 		wayElement.id = 'way-'+fromReg+'-'+toReg;
	// 		wayElement.setAttribute('class', 'way way-line-'+fromReg+' way-line-'+toReg);
	// 		wayElement.setAttribute('x1', this.regionData[fromReg].x);
	// 		wayElement.setAttribute('y1', this.regionData[fromReg].y);
	// 		wayElement.setAttribute('x2', this.regionData[toReg].x);
	// 		wayElement.setAttribute('y2', this.regionData[toReg].y);
	// 		wayElement.style.stroke = 'rgb(255,255,0)';
	// 		wayElement.style.strokeWidth = 2;
	// 		this.ways[fromReg][toReg].svg = wayElement;
	// 		this.layers['ways'].svg.appendChild(wayElement);
	// 	}
	// 	if (withArrow && !wayExtraElement) {
	// 		let lastSlope = (this.regionData[toReg].y - this.regionData[fromReg].y) / (this.regionData[toReg].x - this.regionData[fromReg].x);
	// 		let lastAngleRad = Math.atan(lastSlope);
	// 		var flip = 180;
	// 		if (this.regionData[toReg].x < this.regionData[fromReg].x) {
	// 			flip = 0;
	// 		}
	// 		let lastAngle = flip + lastAngleRad * (180/Math.PI);
	// 		var wayExtraElement = document.createElementNS('http://www.w3.org/2000/svg','use');
	// 		wayExtraElement.id = 'way-'+fromReg+'-'+toReg+'-extra';
	// 		wayExtraElement.setAttribute('class', 'way way-extra way-line-'+fromReg+' way-line-'+toReg);
	// 		wayExtraElement.setAttribute('x', regionData[toReg].x);
	// 		wayExtraElement.setAttribute('y', regionData[toReg].y);
	// 		wayExtraElement.setAttribute('href', '#yArrowMarker');
	// 		wayExtraElement.setAttribute('transform', 'rotate('+lastAngle+' '+this.regionData[toReg].x+' '+this.regionData[toReg].y+')');
	// 		wayExtraElement.style.stroke = 'rgb(255, 255, 0)';
	// 		wayExtraElement.style.strokeWidth = 2;
	// 		this.ways[fromReg][toReg].extraSvg = wayExtraElement;
	// 		this.layers['ways'].svg.appendChild(wayExtraElement);
	// 	}
	// 	if (strokeColor) {
	// 		wayElement.style.stroke = strokeColor;
	// 		wayExtraElement.style.stroke = strokeColor;
	// 	}
	// }
	// hideWay(fromReg, toReg) {
	// 	if (this.ways[fromReg][toReg].svg) {
	// 		this.ways[fromReg][toReg].svg.style.visibility = 'hidden';
	// 	}
	// 	if (this.ways[fromReg][toReg].extraSvg) {
	// 		this.ways[fromReg][toReg].extraSvg.style.visibility = 'hidden';
	// 	}
	// }

	// clearWays() {
	// 	for (var w of this.ways) {
	// 		for (var y of w) {
	// 			if (y.svg) {
	// 				y.svg.remove();
	// 			}
	// 			if (y.extraSvg) {
	// 				y.extraSvg.remove();
	// 			}
	// 		}
	// 	}
	// }

	// drawRegionArrow(fromRegion, toRegion, color = 'rgb(255, 255, 0)') {
	// 	var arrowElement = document.createElementNS('http://www.w3.org/2000/svg','g');
	// 	arrowElement.id = 'arrow-'+fromRegion.id+'-'+toRegion.id;

	// 	var arrowLineElement = document.createElementNS('http://www.w3.org/2000/svg','line');
	// 	arrowLineElement.id = 'arrow-'+fromRegion.id+'-'+toRegion.id+'-line';
	// 	arrowLineElement.setAttribute('class', 'arrow arrow-'+fromRegion.id+' arrow-'+toRegion.id);
	// 	arrowLineElement.setAttribute('x1', fromRegion.x);
	// 	arrowLineElement.setAttribute('y1', fromRegion.y);
	// 	arrowLineElement.setAttribute('x2', toRegion.x);
	// 	arrowLineElement.setAttribute('y2', toRegion.y);
	// 	arrowLineElement.style.stroke = color;
	// 	arrowLineElement.style.strokeWidth = 2;
	// 	arrowElement.appendChild(arrowLineElement);

	// 	let lastSlope = (toRegion.y - fromRegion.y) / (toRegion.x - fromRegion.x);
	// 	let lastAngleRad = Math.atan(lastSlope);
	// 	var flip = 180;
	// 	if (toRegion.x < fromRegion.x) {
	// 		flip = 0;
	// 	}
	// 	let lastAngle = flip + lastAngleRad * (180/Math.PI);

	// 	var arrowheadElement = document.createElementNS('http://www.w3.org/2000/svg','use');
	// 	arrowheadElement.id = 'arrow-'+fromRegion.id+'-'+toRegion.id+'-head';
	// 	arrowheadElement.setAttribute('class', 'arrow arrow-head arrow-'+fromRegion.id+' arrow-'+toRegion.id);
	// 	arrowheadElement.setAttribute('x', toRegion.x);
	// 	arrowheadElement.setAttribute('y', toRegion.y);
	// 	arrowheadElement.setAttribute('href', '#arrowMarker');
	// 	arrowheadElement.setAttribute('transform', 'rotate('+lastAngle+' '+toRegion.x+' '+toRegion.y+')');
	// 	arrowheadElement.style.stroke = color;
	// 	arrowheadElement.style.fill = color;
	// 	arrowheadElement.style.strokeWidth = 2;
	// 	arrowElement.appendChild(arrowheadElement);

	// 	// For now, I'm putting this in the ways layer
	// 	this.layers['ways'].svg.appendChild(arrowElement);
	// }

	// showRoute(routeId) {
	// 	var route = this.routes[routeId];
	// 	for (var i=1; i<route.length; i++) {
	// 		var from = route[i-1];
	// 		var to = route[i];
	// 		var wayId = from + '_' + to;
	// 		if (from >= to) {
	// 			wayId = to + '_' + from;
	// 		}
	// 		this.ways[wayId].svg.style.visibility = true;
	// 	}
	// }

	// toggleMapControls(event) {
	// 	if (this.mapControlsExpanded) {
	// 		this.mapControls.setAttribute('width','32')
	// 		this.mapControls.setAttribute('height','32');
	// 		this.mapControls.setAttribute('viewBox','0 0 32 32');
	// 		this.mapControlsBack.setAttribute('width','32')
	// 		this.mapControlsBack.setAttribute('height','32');
	// 		var toggleable = this.mapControls.querySelectorAll('.mapControlsToggleable');
	// 		for (var t of toggleable) {
	// 			t.style.visibility = 'hidden';
	// 		}
	// 	} else {
	// 		this.mapControls.setAttribute('width','128');
	// 		this.mapControls.setAttribute('height',this.controlPanelHeight);
	// 		this.mapControls.setAttribute('viewBox','0 0 128 ' + this.controlPanelHeight);
	// 		this.mapControlsBack.setAttribute('width','128')
	// 		this.mapControlsBack.setAttribute('height', this.controlPanelHeight);
	// 		var toggleable = this.mapControls.querySelectorAll('.mapControlsToggleable');
	// 		for (var t of toggleable) {
	// 			t.style.visibility = 'visible';
	// 		}
	// 	}
	// 	this.mapControlsExpanded = !this.mapControlsExpanded;

	// 	if (event) {
	// 		event.stopPropagation();
	// 	}
	// }

	// setBackgroundLayer(event, layerName) {
	// 	var newLayerName = layerName + 'Map';
	// 	var oldLayerName = this.activeBackground + 'Map';
	// 	var newLayer = this.layers[newLayerName];
	// 	var oldLayer = this.layers[oldLayerName];

	// 	oldLayer.controlText.style.fill = 'black';
	// 	newLayer.controlText.style.fill = 'white';

	// 	newLayer.svg.style.visibility = 'visible';
	// 	oldLayer.svg.style.visibility = 'hidden';
	// 	this.activeBackground = layerName;

	// 	if (event) {
	// 		event.stopPropagation();
	// 	}
	// }
	// setPolygonLayer(event, layer) {
	// 	if (this.polygonLayer != layer) {
	// 		if (this.layers[this.polygonLayer]) {
	// 			var oldPolygonLayer = this.layers[this.polygonLayer].svg;
	// 			oldPolygonLayer.style.visibility = 'hidden';
	// 			this.layers[this.polygonLayer].controlText.style.fill = 'black';
	// 		}
	// 		var newPolygonLayer = this.layers[layer].svg;
	// 		newPolygonLayer.style.visibility = 'visible';
	// 		var newPolygonLayerText = this.layers[layer].controlText;
	// 		newPolygonLayerText.style.fill = 'white';
	// 		this.polygonLayer = layer;
	// 		// for (var i in this.polygonLayerNames) {
	// 		// 	if (!$.isNumeric(i)) {
	// 		// 		continue;
	// 		// 	}
	// 		// 	let checkLayerName = this.polygonLayerNames[i];
	// 		// 	var checkLayer = this.map.querySelectorAll('.'+checkLayerName+"Data.name");
	// 		// 	for (var l of checkLayer) {
	// 		// 		if (this.polygonLayer != checkLayerName) {
	// 		// 			l.style.visibility = 'hidden';
	// 		// 		} else {
	// 		// 			l.style.visibility = 'visible';
	// 		// 		}
	// 		// 	}
	// 		// }
	// 	}

	// 	if (event) {
	// 		event.stopPropagation();
	// 	}
	// }
	// // toggleTextLayer(event, layerName) {
	// // 	if (this.textLayers[layerName] == undefined) {
	// // 		return;
	// // 	}
	// // 	var layerElements = this.map.querySelectorAll('.'+layerName+"Data.name");
	// // 	var dataLayerName = layerName + 'Data';
	// // 	if (this.textLayers[layerName]) {
	// // 		for (var e of layerElements) {
	// // 			e.style.visibility = 'hidden';
	// // 		}
	// // 		this.textLayers[layerName] = false;
	// // 		var textElement = this.layers[dataLayerName].controlText;
	// // 		textElement.style.fill = 'black';
	// // 	} else if (layerElements.length > 0) {
	// // 		for (var e of layerElements) {
	// // 			e.style.visibility = 'visible';
	// // 		}
	// // 		this.textLayers[layerName] = true;
	// // 		var textElement = this.layers[dataLayerName].controlText;
	// // 		textElement.style.fill = 'white';
	// // 	} else {
	// // 		switch (layerName) {
	// // 			case 'region':
	// // 				this.setAllRegionNames();
	// // 				break;
	// // 			case 'realm':
	// // 				this.setAllRealmInfo();
	// // 				break;
	// // 			case 'duchy':
	// // 				this.setAllDuchyInfo();
	// // 				break;
	// // 			case 'sea':
	// // 				this.setAllSeaZoneNames();
	// // 				break;
	// // 		}
	// // 		this.textLayers[layerName] = true;
	// // 		var textElement = this.layers[dataLayerName].controlText;
	// // 		textElement.style.fill = 'white';
	// // 	}
	// // 	if (event) {
	// // 		event.stopPropagation();
	// // 	}
	// // }

	// disableSeaZones() {
	// 	for (var s of this.seaData) {
	// 		if (s == undefined) {
	// 			continue;
	// 		}
	// 		s.hoverActive = false;
	// 		s.svg.style.fillOpacity = this.hoverOpacity;
	// 		s.svg.style.fill = 'rgba(48, 48, 48, 1)';
	// 		s.svg.style.cursor = 'inherit';
	// 	}
	// }

	// disableRegions() {
	// 	for (var r of this.regionData) {
	// 		if (r == undefined) {
	// 			continue;
	// 		}
	// 		r.hoverActive = false;
	// 		r.svg.style.fillOpacity = this.hoverOpacity;
	// 		r.svg.style.fill = 'rgba(48, 48, 48, 1)';
	// 		r.svg.style.cursor = 'inherit';
	// 	}
	// }

	// enableSeaZone(seaId) {
	// 	var s = this.seaData[seaId];
	// 	s.svg.style.fillOpacity = this.baseOpacity;
	// 	s.svg.style.fill = 'rgba(0,0,255,0.5)';
	// 	s.svg.style.cursor = 'pointer';
	// 	s.hoverActive = true;
	// }

	// enableRegion(regionId) {
	// 	var r = this.regionData[regionId];
	// 	r.svg.style.fillOpacity = this.baseOpacity;
	// 	r.svg.style.fill = 'rgba(0,0,255,0.5)';
	// 	r.svg.style.cursor = 'pointer';
	// 	r.hoverActive = true;
	// }

	// displayAllRegions() {
	// 	var root = this;
	// 	for (var id in this.regionData) {
	// 		if (id == 'removeElement') {
	// 			continue;
	// 		}
	// 		var r = this.regionData[id];
	// 		if (r.poly == undefined) {
	// 			console.log("Poly is undefined for region "+id);
	// 			continue;
	// 		}
	// 		r.svg = document.createElementNS('http://www.w3.org/2000/svg','path');
	// 		r.svg.setAttributeNS(null, 'd', r.poly);
	// 		r.svg.setAttributeNS(null, 'class', 'reg-poly-path');
	// 		r.svg.setAttributeNS(null, 'id', 'reg-poly-' + r.id);
	// 		r.svg.setAttributeNS(null, 'regionid', r.id);
	// 		r.svg.style.fillOpacity = 0;
	// 		r.svg.style.fill = 'rgba(0,0,255,0.5)';
	// 		r.svg.style.stroke = 'rgba(255,0,0,0.5)';
	// 		r.svg.style.strokeWidth = 2;
	// 		r.svg.style.fillRule = 'evenodd';
	// 		r.svg.onmouseover = function(event) { root.hoverRegPoly(event) };
	// 		r.svg.onclick = function(event) { root.clickedRegPoly(event); };
	// 		this.layers['region'].svg.appendChild(r.svg);
	// 		var regionDataElements = this.map.querySelectorAll('.region'+r.id+'Data');
	// 		for (var e=0; e < regionDataElements.length; e++) {
	// 			var xOffset = parseInt(regionDataElements[e].getAttributeNS(null, "x"));
	// 			var yOffset = parseInt(regionDataElements[e].getAttributeNS(null, "y"));
	// 			regionDataElements[e].setAttributeNS(null, "x", xOffset + r.x);
	// 			regionDataElements[e].setAttributeNS(null, "y", yOffset + r.y);
	// 		}

	// 		r.nameSvg = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 		r.nameSvg.textContent = r.name;
	// 		r.nameSvg.setAttributeNS(null, 'class', 'regionData region' + id + 'Data name');
	// 		r.nameSvg.setAttributeNS(null, 'id', 'reg-name-' + r.id);
	// 		r.nameSvg.setAttributeNS(null, 'regionid', r.id);
	// 		r.nameSvg.style.fill = 'black';
	// 		r.nameSvg.style.filter = 'url(#textGlow)';
	// 		r.nameSvg.style.fontFamily = 'Caligula, serif';
	// 		r.nameSvg.style.fontSize = '16px';
	// 		r.nameSvg.style.textAnchor = 'middle';
	// 		r.nameSvg.style.pointerEvents = 'none';
	// 		r.nameSvg.setAttribute('x', r.x);
	// 		r.nameSvg.setAttribute('y', r.y);
	// 		// r.nameSvg.onmouseover = function(event) { root.hoverRegPoly(event) };
	// 		// r.nameSvg.onclick = function(event) { root.clickedRegPoly(event); };
	// 		this.layers['regionData'].svg.appendChild(r.nameSvg);

	// 		// Set up for later use
	// 		r.data = {};
	// 		r.appendData = function (layerName, dataItem) {
	// 			if (this.data[layerName] == undefined) {
	// 				this.data[layerName] = [];
	// 			}
	// 			this.data[layerName].push(dataItem);
	// 		};
	// 	}
	// }
	// displayAllSeaZones() {
	// 	var root = this;
	// 	for (var id in this.seaData) {
	// 		if (id == 'removeElement') {
	// 			continue;
	// 		}
	// 		var s = this.seaData[id];
	// 		if (s.poly == undefined) {
	// 			console.log("Poly is undefined for sea zone "+id);
	// 			continue;
	// 		}
	// 		s.svg = document.createElementNS('http://www.w3.org/2000/svg','path');
	// 		s.svg.setAttributeNS(null, 'd', s.poly);
	// 		s.svg.setAttributeNS(null, 'class', 'sea-poly-path');
	// 		s.svg.setAttributeNS(null, 'id', 'sea-poly-' + s.id);
	// 		s.svg.setAttributeNS(null, 'seaid', s.id);
	// 		s.svg.style.fillOpacity = 0;
	// 		s.svg.style.fill = 'rgba(0,0,255,0.5)';
	// 		s.svg.style.stroke = 'rgba(255,0,0,0.5)';
	// 		s.svg.style.strokeWidth = 2;
	// 		s.svg.style.fillRule = 'evenodd';
	// 		s.svg.onmouseover = function(event) { root.hoverSeaPoly(event); };
	// 		s.svg.onclick = function(event) { root.clickedSeaPoly(event); };
	// 		this.layers['sea'].svg.appendChild(s.svg);
	// 		var seaDataElements = this.map.querySelectorAll('.sea'+s.id+'Data');
	// 		for (var e=0; e < seaDataElements.length; e++) {
	// 			var xOffset = parseInt(seaDataElements[e].getAttributeNS(null, "x"));
	// 			var yOffset = parseInt(seaDataElements[e].getAttributeNS(null, "y"));
	// 			seaDataElements[e].setAttributeNS(null, "x",xOffset + s.x);
	// 			seaDataElements[e].setAttributeNS(null, "y",yOffset + s.y);
	// 		}
	// 	}
	// }

	// createBaseLayer(layerName, beforeLayerName, visible = false) {
	// 	var layer = new MapLayer(layerName, layerName, '', '', visible);
	// 	layer.svg.id = layerName + 'Base';
	// 	if (beforeLayerName) {
	// 		var nextLayer = this.map.querySelector('#'+beforeLayerName);
	// 		this.map.insertBefore(layer.svg, nextLayer);
	// 	} else {
	// 		this.map.appendChild(layer.svg);
	// 	}
	// 	if (visible) {
	// 		layer.svg.setAttribute('hoverlayer', 'true');
	// 	}
	// 	this.layers[layerName] = layer;
	// 	this.layers[layerName].zIndex = Object.keys(this.layers).length;
	// }
	// buildMapControls(imageLayers, polyLayers, textLayers) {
	// 	var imageLayerTop = 28;
	// 	var controlTextLeft = 40;
	// 	var imageLayerCount = 0;
	// 	let root = this;
	// 	for (var l of imageLayers) {
	// 		var imageLayerText = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 		imageLayerText.id = l.id + 'LayerText';
	// 		imageLayerText.setAttribute('x', controlTextLeft);
	// 		imageLayerText.setAttribute('y', imageLayerTop + 20 * ++imageLayerCount);
	// 		if (l.default) {
	// 			imageLayerText.style.fill = 'white';
	// 		} else {
	// 			imageLayerText.style.fill = 'black';
	// 		}
	// 		const layerId = l.id;
	// 		imageLayerText.onclick = function(event) { root.setBackgroundLayer(event, layerId) };
	// 		imageLayerText.textContent = l.name;
	// 		this.mapControlsFront.appendChild(imageLayerText);
	// 		this.layers[l.id + 'Map'].controlText = imageLayerText;
	// 	}
	// 	var polyLayerTop = imageLayerTop + Math.max(32, 20 * imageLayerCount) + 4;
	// 	var polyLayerCount = 0;
	// 	for (var l of polyLayers) {
	// 		var polyLayerText = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 		polyLayerText.id = l.id + 'LayerText';
	// 		polyLayerText.setAttribute('x', controlTextLeft);
	// 		polyLayerText.setAttribute('y', polyLayerTop + 20 * ++polyLayerCount);
	// 		if (l.default) {
	// 			polyLayerText.style.fill = 'white';
	// 		} else {
	// 			polyLayerText.style.fill = 'black';
	// 		}
	// 		const layerId = l.id;
	// 		polyLayerText.onclick = function(event) { root.setPolygonLayer(event, layerId) };
	// 		polyLayerText.textContent = l.name;
	// 		this.mapControlsFront.appendChild(polyLayerText);
	// 		this.layers[l.id].controlText = polyLayerText;
	// 	}
	// 	var textLayerTop = polyLayerTop + Math.max(32, 20 * polyLayerCount) + 4;
	// 	if (polyLayerCount == 0) {
	// 		textLayerTop = polyLayerTop;
	// 	}
	// 	var textLayerCount = 0;
	// 	for (var l of textLayers) {
	// 		var textLayerText = document.createElementNS('http://www.w3.org/2000/svg','text');
	// 		const layerId = l.id.substring(0, l.id.length - 4);
	// 		textLayerText.id = layerId + 'TextText';
	// 		textLayerText.setAttribute('x', controlTextLeft);
	// 		textLayerText.setAttribute('y', textLayerTop + 20 * ++textLayerCount);
	// 		textLayerText.style.fill = 'black';
	// 		textLayerText.onclick = function(event) { root.toggleTextLayer(event, layerId) };
	// 		textLayerText.textContent = l.name;
	// 		this.mapControlsFront.appendChild(textLayerText);
	// 		this.layers[l.id].controlText = textLayerText;
	// 		if (l.default) {
	// 			textLayerText.onclick();
	// 		}
	// 	}

	// 	if (polyLayerCount > 0) {
	// 		this.polyLayersIcon.setAttribute('transform', 'translate(0 ' + (polyLayerTop+8) + ')');
	// 	} else {
	// 		this.polyLayersIcon.remove();
	// 	}
	// 	if (textLayerCount > 0) {
	// 		this.textLayersIcon.setAttribute('transform', 'translate(0 ' + (textLayerTop+8) + ')');
	// 		this.mapControlsHeight = textLayerTop + Math.max(32, 20 * textLayerCount) + 4;
	// 	} else {
	// 		this.textLayersIcon.remove();
	// 		this.mapControlsHeight = polyLayerTop + Math.max(32, 20 * polyLayerCount) + 4;
	// 	}
	// 	this.controlPanelHeight = this.mapControlsHeight + 12;
	// }

	// // The map images get passed in from outside, so that they can be initialized with Twig
	// initializeMainMap(mapImages) {
	// 	var tempScaleFilenames = {};
	// 	this.mapControlImageLayers = [];
	// 	for (var image of mapImages) {
	// 		var shortName = image.name.toLowerCase();
	// 		if (tempScaleFilenames[shortName] == undefined) {
	// 			tempScaleFilenames[shortName] = [];
	// 		}
	// 		tempScaleFilenames[shortName][image.scale] = image.filename;
	// 		if (image.scale == 1) {
	// 			var imageMapLayer = new ImageLayer(shortName, image.filename, image.width, image.height);
	// 			imageMapLayer.scale = image.scale;
	// 			imageMapLayer.filename = image.filename;
	// 			this.backgroundContainer.appendChild(imageMapLayer.svg);
	// 			if (image.default) {
	// 				imageMapLayer.svg.style.visibility = 'visible';
	// 				this.activeBackground = shortName;
	// 			} else {
	// 				imageMapLayer.svg.style.visibility = 'hidden';
	// 			}
	// 			this.layers[shortName + 'Map'] = imageMapLayer;
	// 			this.layers[shortName + 'Map'].zIndex = Object.keys(this.layers).length;
	// 			this.mapControlImageLayers.push({id: shortName, name: image.name, default: image.default});
	// 		}
	// 	}

	// 	for (var n in tempScaleFilenames) {
	// 		var scaleFilenames = tempScaleFilenames[n];
	// 		for (var s in scaleFilenames) {
	// 			this.layers[n + 'Map'].scaleUrls[s] = scaleFilenames[s];
	// 		}
	// 	}

	// 	this.mapControlPolygonLayers = [];
	// 	this.mapControlTextLayers = [];

	// 	var regionDataLayer = new MapLayer('regionData', 'allMapRegionData', this.map.querySelector('#allMapRegionData'));
	// 	this.layers['regionData'] = regionDataLayer;
	// 	this.layers['regionData'].zIndex = Object.keys(this.layers).length;
	// 	this.mapControlPolygonLayers.push({id: 'region', name: 'Regions', default: true});
	// 	this.polygonLayers['region'] = true;
	// 	this.mapControlTextLayers.push({id: 'regionData', name: 'Regions', default: false});
	// 	this.textLayers['region'] = false;

	// 	var realmDataLayer = new MapLayer('realmData', 'allMapRealmData', this.map.querySelector('#allMapRealmData'));
	// 	this.layers['realmData'] = realmDataLayer;
	// 	this.layers['realmData'].zIndex = Object.keys(this.layers).length;
	// 	this.mapControlPolygonLayers.push({id: 'realm', name: 'Realms', default: false});
	// 	this.polygonLayers['realm'] = false;
	// 	this.mapControlTextLayers.push({id: 'realmData', name: 'Realms', default: false});
	// 	this.textLayers['realm'] = false;

	// 	var duchyDataLayer = new MapLayer('duchyData', 'allMapDuchyData', this.map.querySelector('#allMapDuchyData'));
	// 	this.layers['duchyData'] = duchyDataLayer;
	// 	this.layers['duchyData'].zIndex = Object.keys(this.layers).length;
	// 	this.mapControlPolygonLayers.push({id: 'duchy', name: 'Duchies', default: false});
	// 	this.polygonLayers['duchy'] = false;
	// 	this.mapControlTextLayers.push({id: 'duchyData', name: 'Duchies', default: false});
	// 	this.textLayers['duchy'] = false;

	// 	var seaDataLayer = new MapLayer('seaData', 'allMapSeaData', this.map.querySelector('#allMapSeaData'), true);
	// 	this.layers['seaData'] = seaDataLayer;
	// 	this.layers['seaData'].zIndex = Object.keys(this.layers).length;
	// 	this.mapControlTextLayers.push({id: 'seaData', name: 'Sea Zones', default: true});
	// 	this.textLayers['sea'] = false;

	// 	var wayDataLayer = new MapLayer('ways', 'allMapWayData', this.map.querySelector('#allMapWayData'));
	// 	this.layers['ways'] = wayDataLayer;
	// 	this.layers['ways'].zIndex = Object.keys(this.layers).length;
	// 	var nextLayerName = 'allMapRegionData';
	// 	var svgLayers = this.map.querySelectorAll('.svgLayers');
	// 	if (svgLayers.length > 0) {
	// 		nextLayerName = svgLayers[0].id;
	// 	}
	// 	this.createBaseLayer('region', nextLayerName, true);
	// 	this.createBaseLayer('duchy', nextLayerName);
	// 	this.createBaseLayer('realm', nextLayerName);
	// 	this.createBaseLayer('sea', nextLayerName, true);
	// 	this.displayAllRegions();
	// 	this.displayAllSeaZones();
	// 	if (this.centerRegionId) {
	// 		this.centerRegion(this.centerRegionId);
	// 	} else if (this.myLocationId) {
	// 		//setRegionColor(myLocationId, 'green');
	// 		this.centerRegion(this.myLocationId);
	// 	}

	// 	this.getDataLayerDefs();

	// 	this.layers['region'].svg.style.visibility = 'visible';

	// 	//this.buildMapControls(this.mapControlImageLayers, this.mapControlPolygonLayers, this.mapControlTextLayers);
	// 	//this.setPolygonLayer(null, 'region');
	// }

	// setAllRealmInfo(callback) {
	// 	if (!this.realmInfoUrl) {
	// 		console.log("Attempted to set realm info without realm info URL set");
	// 		return;
	// 	}
	// 	const root = this;
	// 	console.log('Setting realm data from url ' + this.realmInfoUrl);
	// 	$.ajax({
	// 		url: this.realmInfoUrl,
	// 		method: 'POST',
	// 		data: { },
	// 		dataType: 'json',
	// 		error: function(xhr, status, error) {
	// 			root.logOutput("Error attempting to fetch Realm data.");
	// 			console.log('Error while fetching Realm data: '+error);
	// 		},
	// 		success: function(data, status, xhr) {
	// 			if (data.error) {
	// 				root.logOutput('<p class="error">Error attempting to fetch realm data: '+data.error+'</p>');
	// 				console.log('Error while processing Realm data: '+data.error);
	// 			} else {
	// 				root.realmData = data.realmInfo;
	// 				for (var realmId in root.realmData) {
	// 					root.addRealmPoly(root.realmData[realmId]);
	// 					root.addRealmName(realmId, root.realmData[realmId].name);
	// 				}

	// 				if (callback) {
	// 					callback();
	// 				}
	// 			}
	// 		}
	// 	});
	// }
	// setAllDuchyInfo(callback) {
	// 	if (!this.duchyInfoUrl) {
	// 		console.log("Attempted to set duchy info without duchy info URL set");
	// 		return;
	// 	}
	// 	console.log('Setting duchy data from url ' + this.duchyInfoUrl);
	// 	const root = this;
	// 	$.ajax({
	// 		url: this.duchyInfoUrl,
	// 		method: 'POST',
	// 		data: { },
	// 		dataType: 'json',
	// 		error: function(xhr, status, error) {
	// 			root.logOutput("Error attempting to fetch duchy names.");
	// 			console.log("Error fetching duchy names: "+error);
	// 		},
	// 		success: function(data, status, xhr) {
	// 			if (data.error) {
	// 				root.logOutput('<p class="error">Error attempting to fetch duchy names: '+data.error+'</p>');
	// 				console.log("Error processing duchy names: "+data.error);
	// 			} else {
	// 				root.duchyData = data.duchyInfo;
	// 				for (var duchyId in root.duchyData) {
	// 					if (root.duchyData[duchyId].poly) {
	// 						root.addDuchyPoly(root.duchyData[duchyId]);
	// 						root.addDuchyName(duchyId, root.duchyData[duchyId].name);
	// 					}
	// 				}

	// 				if (callback) {
	// 					callback();
	// 				}
	// 			}
	// 		}
	// 	});
	// }

	// loadWayData(callback) {
	// 	if (!this.wayInfoUrl) {
	// 		console.log("Attempted to set way info without way info URL set");
	// 		return;
	// 	}
	// 	const root = this;
	// 	console.log('Loading way data from url ' + this.wayInfoUrl);
	// 	$.ajax({
	// 		url: this.wayInfoUrl,
	// 		method: 'POST',
	// 		data: { },
	// 		dataType: 'json',
	// 		error: function(xhr, status, error) {
	// 			this.outputLog("Error attempting to fetch Way data.");
	// 		},
	// 		success: function(data, status, xhr) {
	// 			if (data.error) {
	// 				this.outputLog('<p class="error">Error attempting to fetch way data: '+data.error+'</p>');
	// 			} else {
	// 				for (var i=0; i<data.ways.length; i++) {
	// 					let fromReg = data.ways[i].fromReg;
	// 					let toReg = data.ways[i].toReg;
	// 					if (root.ways[fromReg] == undefined) {
	// 						root.ways[fromReg] = {};
	// 					}
	// 					root.ways[fromReg][toReg] = {};
	// 					root.ways[fromReg][toReg].distance = data.ways[i].distance;
	// 					root.ways[fromReg][toReg].svg = false;
	// 					root.ways[fromReg][toReg].extraSvg = false;

	// 					if (root.ways[toReg] == undefined) {
	// 						root.ways[toReg] = {};
	// 					}
	// 					root.ways[toReg][fromReg] = {};
	// 					root.ways[toReg][fromReg].distance = data.ways[i].distance;
	// 					root.ways[toReg][fromReg].svg = false;
	// 					root.ways[toReg][fromReg].extraSvg = false;
	// 				}
	// 			}
	// 			if (callback) {
	// 				callback();
	// 			}
	// 		}
	// 	});
	// }
	logOutput(text) {
		if (this.outputContainer) {
			if (this.outputContainer.textContent) {
				this.outputContainer.textContent += ', ' + text;
			} else {
				this.outputContainer.textContent = text;
			}
		} else {
			console.log(text);
		}
	}
}

class MapPoint {
	constructor(x, y) {
		this.x = x;
		this.y = y;
	}
}

/**
 * Takes a single point object and converts it into an SVG point string.
 * 
 * @param {MapPoint} pointObj 
 * @returns {string} 
 */
function pointToPath(pointObj) {
	return pointObj.x+' '+pointObj.y;
}

/**
 * Takes an array of points and converts it into SVG path syntax. If the `close` parameter is true,
 * also ensures that the path is closed.
 * 
 * @param {MapPoint[]} pointArray 
 * @param {bool} close 
 * @returns {string} the path string itself
 */
function pointsToPath(pointArray, close=true) {
	var path = '';
	
	// if (!Array.isArray(pointArray[0][0])) {
	// 	pointArray = [pointArray];
	// }
	for (var i in pointArray) {
		var points = pointArray[i];
		path += 'M ';
		path += pointToPath(points[0]);

		for (var j = 1; j < points.length; j++) {
			path += ' L '+pointToPath(points[j]);
		}

		if (close) {
			path += ' z ';
		} else {
			console.log('Building path without closing!');
		}
	}

	return path;
}

let standardScales = [12.5, 25, 37.5, 50, 62.5, 75, 87.5, 100];

class ImageLayer {
	constructor(name) {
		this.name = name;
		this.id = name + 'Map';
		this.images = [];
		this.validScales = [];
		this.scales = {};
		this.default = false;
		this.baseScale = -1;
		this.curScale = -1;
		this.svg = document.createElementNS('http://www.w3.org/2000/svg','g');
		this.svg.id = this.id;
	}

	addImage(url, width, height) {
		var image = {};
		image.imageUrl = url;
		image.width = width;
		if (this.baseScale == -1) {
			this.baseScale = width;
		}
		image.height = height;
		image.svg = document.createElementNS('http://www.w3.org/2000/svg','g');
		image.svg.id = this.id + '-' + width;
		image.imageSvg = document.createElementNS('http://www.w3.org/2000/svg','image');
		image.imageSvg.id = this.id + '-image';
		image.imageSvg.setAttributeNS('http://www.w3.org/1999/xlink',"href",url);
		image.imageSvg.setAttribute('height', height);
		image.imageSvg.setAttribute('width', width);
		image.svg.appendChild(image.imageSvg);
		image.veilSvg = document.createElementNS('http://www.w3.org/2000/svg','rect');
		image.veilSvg.id = this.id + '-veil';
		image.veilSvg.setAttribute('height', height);
		image.veilSvg.setAttribute('width', width);
		image.veilSvg.style.fill = 'rgba(0, 0, 0, 0.5)';
		image.veilSvg.style.strokeWidth = 0;
		image.veilSvg.style.visibility = 'hidden';
		image.svg.appendChild(image.veilSvg);
		this.images.push(image);
	}

	calculateScales() {
		var newValidScales = [];
		var newScales = {};
		var maxWidth = 0;
		var baseWidth = this.baseScale;
		this.images.sort((a, b) => (a.width - b.width));
		for (var i in this.images) {
			var image = this.images[i];
			var imageWidth = parseInt(image.width);
			newValidScales.push(imageWidth);
			newScales[imageWidth] = i;
			if (imageWidth > maxWidth) {
				maxWidth = imageWidth;
			}
			image.scaleFactor = (imageWidth / baseWidth);
		}
		var w = 0;
		for (var s in standardScales) {
			var scalePct = standardScales[s];
			var scaleWidth = Math.round(maxWidth * scalePct / 100);
			if (scaleWidth > newValidScales[w]) {
				w++;
			}
			newScales[scaleWidth] = w;
			if (!newValidScales.includes(scaleWidth)) {
				newValidScales.push(scaleWidth);
			}
		}
		newValidScales.sort((a, b) => (a - b));

		this.validScales = newValidScales;
		this.scales = newScales;
	}

	applyVeil() {
		this.curImage().veilSvg.style.visibility = 'visible';
	}

	removeVeil() {
		this.curImage().veilSvg.style.visibility = 'hidden';
	}

	curImage() {
		return this.images[this.scales[this.curScale]];
	}

	imageUrl() {
		return this.images[this.scales[this.curScale]].imageUrl;
	}

	countScales() {
		return this.validScales.length;
	}

	curScaleIndex() {
		return this.validScales.indexOf(this.curScale);
	}

	setScale(newScale) {
		if (this.scales[newScale] == undefined) {
			var nextUp = Object.keys(this.scales)[0];
			// If the new scale value is not one of the breakpoints we already have, we round it up to the next one
			// We do this by iterating through the breakpoints as long as the new scale value is greater than them
			for (var s in this.scales) {
				if (newScale > s) {
					nextUp = s;
				} else {
					break;
				}
			}
			if (newScale > nextUp) {
				return false;
			}
			newScale = nextUp;
		}
		// if (newScale == this.curScale) {
		// 	return true;
		// }
		var newImage = this.images[this.scales[newScale]];
		while (this.svg.children.length > 0) {
			this.svg.children[0].remove();
		}
		this.svg.appendChild(newImage.svg);
		this.curScale = newScale;
	}
}

class MapLayer {
	constructor(name, desc = '', group = '', layerDataUrl = '', startVisible = false, controllable = true) {
		this.name = name;
		this.desc = desc;
		this.group = group;
		this.svg = document.createElementNS('http://www.w3.org/2000/svg','g');
		this.svg.id = name + 'Layer';
		this.visible = startVisible;
		if (startVisible) {
			this.svg.style.visibility = 'visible';
		} else {
			this.svg.style.visibility = 'hidden';
		}
		this.controllable = controllable;

		this.dataUrl = layerDataUrl;
		this.loaded = false;

		this.mapElements = {};
		this.clickCallback = null;
		this.hoverCallback = null;
	}
}

class MapBackgroundElement extends HTMLElement {
	constructor() {
		super();

		this.images = [];
		this.imageListeners = [];

		this.onMutation = this.onMutation.bind(this);
	}

	connectedCallback() {
		this.name = this.getAttribute('name');

		// Set up observer
		this.observer = new MutationObserver(this.onMutation);

		// Watch the Light DOM for child node changes
		this.observer.observe(this, {
			childList: true
		});
	}

	onMutation(mutations) {
		const added = [];

		// A `mutation` is passed for each new node
		for (const mutation of mutations) {
			// Could test for `mutation.type` here, but since we only have
			// set up one observer type it will always be `childList`
			added.push(...mutation.addedNodes);
		}

		for (var i=0; i<added.length; i++) {
			var childNode = added[i].cloneNode(true);
			if (childNode instanceof HTMLImageElement) {
				var image = {};
				image.url = childNode.getAttribute('src');
				image.width = parseInt(childNode.getAttribute('width'));
				image.height = parseInt(childNode.getAttribute('height'));
				this.images.push(image);
				this.notifyListeners(image);
			}
		}
	}

	addImageListener(listener) {
		if (!this.imageListeners.includes(listener)) {
			this.imageListeners.push(listener);
		}
	}

	notifyListeners(image) {
		for (var i in this.imageListeners) {
			this.imageListeners[i].layerAddedImage(this, image);
		}
	}
}

class MapLayerElement extends HTMLElement {
	constructor() {
		super();
	}

	connectedCallback() {
		this.name = this.getAttribute('name');
		this.id = this.getAttribute('id');
		this.startVisible = this.getAttribute('visible') == 'true';
	}
}

class MapDataLayerElement extends HTMLElement {
	constructor() {
		super();
	}

	connectedCallback() {
		this.name = this.getAttribute('name');
		this.displayName = this.getAttribute('display-name');
		this.parent = this.getAttribute('parent');
		this.startVisible = this.getAttribute('start-visible') == 'true';
		this.controllable = this.getAttribute('controllable') == 'true';
		this.iconUrl = this.getAttribute('icon-url');
		this.extraInfo = this.getAttribute('extra-info');
	}
}

class MapLayerGroupElement extends HTMLElement {
	constructor() {
		super();
	}

	connectedCallback() {
		this.name = this.getAttribute('name');
		this.shortName = this.getAttribute('short-name');
		this.loadUrl = this.getAttribute('load-url');
		this.dataUrl = this.getAttribute('data-url');
		this.includesMap = this.getAttribute('map') == 'true';
		this.includesNames = this.getAttribute('names') == 'true';
		this.includesData = this.getAttribute('data') == 'true';
		this.id = this.getAttribute('id');
		this.startVisible = this.getAttribute('visible') == 'true';
	}
}

customElements.define('flex-map',FlexMap);
customElements.define('map-background',MapBackgroundElement);
customElements.define('map-layer',MapLayerElement);
customElements.define('map-data-layer',MapDataLayerElement);
customElements.define('map-layer-group',MapLayerGroupElement);