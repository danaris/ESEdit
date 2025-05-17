function nameToCSS(name) {
	var cssName = name.replaceAll(' ', '_').replaceAll("'", "-");

	return cssName;
}

function angleBetween(fromPoint, toPoint) {
	var rise = toPoint.y - fromPoint.y;
	var run = toPoint.x - fromPoint.x;
	if (run == 0) {
		if (rise > 0) {
			return 90;
		} else {
			return 270;
		}
	}

	var slope = rise / run;

	var angle = Math.atan(slope);

	var angleDeg = angle * (180/Math.PI);
	if (rise < 0 && run > 0) {
		angleDeg += 360;
	} else if (rise < 0 || run < 0) {
		angleDeg += 180;
	}

	return angleDeg;
}

function pointFromES(esPoint) {
	var newX = esPoint.x + 2048 + 112;
	var newY = esPoint.y + 2048 + 22;
	return new MapPoint(newX, newY);
}

class ESMap extends FlexMap {

	constructor() {
		super();

		this.wormholePoints = [[25,-8],[41,0],[25,8]];

		this.systems = {};
		this.planets = {};

		this.planetLoadUrl;

		this.styleElement.textContent += `
		text { 
			font-family: Ubuntu;
		}
		line {
			pointer-events: none;
		}
		.system {
			cursor: pointer;
		}
		.selected {
			opacity: 1;
		}
		line.selected {
			stroke-width: 2 !important;
			stroke: var(--bright) !important;
		}
		circle.selected {
			stroke-width: 4 !important;
		}
		text.selected {
			font-weight: bold;
		}
		.deselected {
			opacity: 0.9;
		}
		`;
	}

	connectedCallback() {
		super.connectedCallback();

		this.arrowDef = document.createElementNS('http://www.w3.org/2000/svg', 'g');
		this.arrowDef.id = 'arrow';
		
		this.arrowPathDef = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		this.arrowPathDef.setAttribute('d', 'M 25,-8 L 41,0 L 25,8');
		this.arrowDef.appendChild(this.arrowPathDef);

		this.mapDefs.appendChild(this.arrowDef);

		if (this.map.getAttribute('width') > window.innerWidth) {
			this.map.setAttribute('width', window.innerWidth);
		}
		if (this.map.getAttribute('height') > window.innerHeight) {
			this.map.setAttribute('height', window.innerHeight);
		}
		this.map.setAttribute('viewBox', '0 0 ' + this.map.getAttribute('width') + ' ' + this.map.getAttribute('height'));
		
		this.systemPlanetView = document.createElement('div');
		this.systemPlanetView.style.position = 'fixed';
		this.systemPlanetView.style.left = '0.5rem';
		this.systemPlanetView.style.top = '2.5rem';
		this.systemPlanetView.style.maxHeight = '50%';
		this.systemPlanetView.style.backgroundColor = 'var(--dimmer)';
		this.systemPlanetView.style.overflow = 'scroll';
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
			case 'system':
				this.drawSystem(item.name, item.x, item.y, item.color);
				this.systems[item.name] = item.systemData;
				break;
			case 'systemName':
				var x = item.x;
				var y = item.y + 20;
				itemSvg = this.drawText(item.id, 'systemNames', item.value, x, y, item.fontSize + 'px', 'var(--bright)');
				this.layers['systemNames'].mapElements[item.value] = itemSvg;
				if (item.style) {
					itemSvg.setAttribute('style', item.style);
				}
				if (item.svgClass != undefined) {
					itemSvg.classList.add(item.svgClass);
				}
				itemSvg.classList.add('system');
				itemSvg.classList.add('system-' + item.id);
				itemSvg.classList.add('systemName');
				itemSvg.classList.add(layerClass + '-text');
				var root = this;
				itemSvg.onmouseover = function(event) {
					root.highlightSystem(item.value);
				};
				itemSvg.onmouseout = function(event) {
					root.unhighlightSystem(item.value);
				};
				itemSvg.onclick = function(event) {
					root.displaySystemPlanets(item.value);
				}
				break;
			case 'systemLink':
				var linkLayer = this.layers['systemLinks'];
				itemSvg = this.drawLine(linkLayer, item.id, item.x1, item.y1, item.x2, item.y2, 'rgba(192, 192, 192, 0.8)');
				itemSvg.classList.add('systemLink');
				itemSvg.classList.add('system-' + nameToCSS(item.fromSystem));
				itemSvg.classList.add('system-' + nameToCSS(item.toSystem));
				break;
			case 'wormhole':
				itemSvg = this.drawWormholeLink(item.id, item.x1, item.y1, item.x2, item.y2, item.color);
				break;
			default:
				super.addLayerItem(layer, item, dataLayerName);
				break;
		}
	}

	drawLine(layer, id, x1, y1, x2, y2, color) {
		if (layer[id] != undefined) {
			console.log('Tried to draw a line in layer ' + layer.name + ' with a duplicate ID "' + id + '"');
			return;
		}
		var lineElement = document.createElementNS('http://www.w3.org/2000/svg', 'line');
		lineElement.id = id;
		lineElement.setAttribute('x1', x1);
		lineElement.setAttribute('y1', y1);
		lineElement.setAttribute('x2', x2);
		lineElement.setAttribute('y2', y2);
		lineElement.style.strokeWidth = 2;
		lineElement.style.stroke = color;

		layer.mapElements[id] = lineElement;
		layer.svg.appendChild(lineElement);

		return lineElement;
	}

	drawSystem(name, x, y, color) {
		var systemsLayer = this.layers['systems'];
		if (systemsLayer.mapElements[name] != undefined) {
			console.log('Tried to draw a system with a duplicate name "' + name + '"');
			return;
		}
		var circleElement = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
		var safeName = nameToCSS(name);
		circleElement.id = 'system-' + safeName;
		circleElement.classList.add('system-' + safeName);
		circleElement.classList.add('system');
		circleElement.classList.add('systemCircle');
		circleElement.setAttribute('cx', x);
		circleElement.setAttribute('cy', y);
		circleElement.setAttribute('r', 5);
		circleElement.style.strokeWidth = 3;
		circleElement.style.stroke = color;

		var root = this;
		circleElement.onmouseover = function(event) {
			root.highlightSystem(name);
		};
		circleElement.onmouseout = function(event) {
			root.unhighlightSystem(name);
		};
		circleElement.onclick = function(event) {
			root.displaySystemPlanets(name);
		}

		systemsLayer.mapElements[name] = circleElement;
		systemsLayer.svg.appendChild(circleElement);

		return circleElement;
	}

	drawWormholeLink(id, x1, y1, x2, y2, color) {
		var wormholeLayer = this.layers['wormholes'];
		if (wormholeLayer[id] != undefined) {
			console.log('Tried to draw a wormhole with a duplicate ID "' + id + '"');
			return;
		}
		var wormholeElement = document.createElementNS('http://www.w3.org/2000/svg', 'g');
		wormholeElement.id = id;

		var lineElement = document.createElementNS('http://www.w3.org/2000/svg', 'line');
		lineElement.id = id + '_line';
		lineElement.setAttribute('x1', x1);
		lineElement.setAttribute('y1', y1);
		lineElement.setAttribute('x2', x2);
		lineElement.setAttribute('y2', y2);
		lineElement.style.strokeWidth = 2;
		lineElement.style.stroke = color;
		wormholeElement.appendChild(lineElement);

		var startPoint = new MapPoint(x1, y1);
		var endPoint = new MapPoint(x2, y2);
		var angle = angleBetween(startPoint, endPoint);

		// var arrowElement = document.createElementNS('http://www.w3.org/2000/svg', 'g');
		// arrowElement.id = id + '_arrow';
		// arrowElement.setAttribute('transform', 'rotate('+angle+' '+ x2 + ' ' + y2 + ')');

		// var arrowPoints = [];
		// arrowPoints[0] = [];
		// for (var i in this.wormholePoints) {
		// 	var point = new MapPoint();
		// 	point.x = this.wormholePoints[i][0] + x2;
		// 	point.y = this.wormholePoints[i][1] + y2;
			
		// 	arrowPoints[0].push(point);
		// }

		// var arrowPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		// arrowPath.id = id + '_arrowPath';
		// arrowPath.setAttribute('d', pointsToPath(arrowPoints, false));
		// arrowPath.style.fill = 'none';
		// arrowPath.style.stroke = color;
		// arrowPath.style.strokeWidth = 2;
		// arrowElement.appendChild(arrowPath);

		var arrowElement = document.createElementNS('http://www.w3.org/2000/svg','use');
		arrowElement.id = id + '_arrowPath';
		// arrowElement.classList.add('wormhole');
		// arrowElement.classList.add('system-'+nameEsc(fromSystem.name));
		arrowElement.setAttribute('href', '#arrow');
		arrowElement.setAttribute('x', x1);
		arrowElement.setAttribute('y', y1);
		arrowElement.style.stroke = color;
		arrowElement.style.strokeWidth = '2px';
		arrowElement.style.fill = 'none';
		arrowElement.setAttribute('transform', 'rotate('+angle+' '+x1+' '+y1+')');

		wormholeElement.appendChild(arrowElement);

		wormholeLayer.mapElements[id] = wormholeElement;
		wormholeLayer.svg.appendChild(wormholeElement);

		return wormholeElement;
	}

	setSystemColor(name, color) {
		this.layers['systems'].mapElements[name].style.stroke = color;
	}

	highlightSystem(name) {
		var systemElements = this.map.querySelectorAll('.system');
		for (var element of systemElements) {
			element.classList.add('deselected');
		}
		var allLinkElements = this.map.querySelectorAll('.systemLink');
		for (var element of allLinkElements) {
			element.classList.add('deselected');
		}
		this.layers['systems'].mapElements[name].classList.remove('deselected');
		this.layers['systems'].mapElements[name].classList.add('selected');
		this.layers['systemNames'].mapElements[name].classList.remove('deselected');
		this.layers['systemNames'].mapElements[name].classList.add('selected');
		var systemLinks = this.layers['systemLinks'].svg.querySelectorAll('.system-' + nameToCSS(name));
		for (var linkElement of systemLinks) {
			linkElement.classList.add('selected');
			linkElement.classList.remove('deselected');
		}
	}

	unhighlightSystem(name) {
		this.layers['systems'].mapElements[name].classList.remove('selected');
		this.layers['systemNames'].mapElements[name].classList.remove('selected');
		var systemLinks = this.layers['systemLinks'].svg.querySelectorAll('.system-' + nameToCSS(name));
		for (var linkElement of systemLinks) {
			linkElement.classList.remove('selected');
		}
		var systemElements = this.map.querySelectorAll('.system');
		for (var element of systemElements) {
			element.classList.remove('deselected');
		}
		var allLinkElements = this.map.querySelectorAll('.systemLink');
		for (var element of allLinkElements) {
			element.classList.remove('deselected');
		}
	}

	centerSystem(name) {
		var system = this.layers['systems'].mapElements[name];
		if (system == undefined) {
			console.log("Attempted to center nonexistent system '" + name + "'");
		}
		var systemX = system.getAttribute('cx');
		var systemY = system.getAttribute('cy');
		this.centerPoint(systemX, systemY);
	}

	displaySystemPlanets(name) {
		while (this.systemPlanetView.children.length > 0) {
			this.systemPlanetView.children[0].remove();
		}
		var system = this.systems[name];
		for (var o of system.objects) {
			if (o.planet) {
				console.log('Planning to create a display for planet ' + o.planet + ' in the ' + name + ' system');
				if (this.planets[o.planet] != undefined) {
					this.createPlanetDisplay(o.planet, o, system);
					root.systemPlanetView.appendChild(root.planets[o.planet].element);
				} else {
					this.loadPlanetAndDisplay(o.planet, o, system);
				}
			}
		}
		this.shadow.appendChild(this.systemPlanetView);
	}

	loadPlanetAndDisplay(planetName, stellarObject, system) {
		var root = this;
		var planetFetch = fetch(this.planetLoadUrl.replace('~replace~', planetName));
		planetFetch.then((response) => {
			response.json().then((data) => {
				root.planets[planetName] = data;
				root.createPlanetDisplay(planetName, stellarObject, system);
				root.systemPlanetView.appendChild(root.planets[planetName].element);
			});
		});
	}

	createPlanetDisplay(planetName, stellarObject, system) {
		var planet = this.planets[planetName];
		if (planet.element != undefined) {
			planet.element.remove();
			planet.element = null;
		}
		planet.element = document.createElement('div');
		planet.element.style.display = 'grid';
		planet.element.style.gridTemplateColumns = 'auto auto';
		planet.planetViewEl = document.createElement('img');
		console.log('Creating display for planet ' + planetName + ' with sprite ' + stellarObject.sprite);
		planet.planetViewEl.src = "/sprites/" + stellarObject.sprite + ".png";
		planet.planetViewEl.style.maxHeight = '6rem';
		planet.planetViewEl.style.textAlign = 'center';
		planet.element.appendChild(planet.planetViewEl);
		planet.infoColumnEl = document.createElement('div');
		planet.element.appendChild(planet.infoColumnEl);
		planet.planetNameEl = document.createElement('div');
		planet.planetNameEl.textContent = planetName;
		planet.planetNameEl.style.color = 'var(--medium)';
		planet.infoColumnEl.appendChild(planet.planetNameEl);
		planet.govNameEl = document.createElement('div');
		if (!planet.government) {
			if (system.government) {
				planet.govNameEl.textContent = system.government;
			} else {
				planet.govNameEl.textContent = 'Uninhabited';
			}
		} else {
			planet.govNameEl.textContent = planet.government;
		}
		planet.govNameEl.style.marginLeft = '1em';
		planet.infoColumnEl.appendChild(planet.govNameEl);
		planet.spaceportEl = document.createElement('div');
		planet.spaceportEl.style.marginLeft = '1em';
		planet.spaceportEl.textContent = 'spaceport';
		if (planet.attributes.includes('spaceport')) {
			planet.spaceportEl.style.color = 'var(--medium)';
		} else {
			planet.spaceportEl.style.color = 'var(--dim)';
		}
		planet.infoColumnEl.appendChild(planet.spaceportEl);
		planet.shipyardEl = document.createElement('div');
		planet.shipyardEl.style.marginLeft = '1em';
		planet.shipyardEl.textContent = 'shipyard';
		if (planet.attributes.includes('shipyard')) {
			planet.shipyardEl.style.color = 'var(--medium)';
		} else {
			planet.shipyardEl.style.color = 'var(--dim)';
		}
		planet.infoColumnEl.appendChild(planet.shipyardEl);
		planet.outfitterEl = document.createElement('div');
		planet.outfitterEl.style.marginLeft = '1em';
		planet.outfitterEl.textContent = 'outfitter';
		if (planet.attributes.includes('outfitter')) {
			planet.outfitterEl.style.color = 'var(--medium)';
		} else {
			planet.outfitterEl.style.color = 'var(--dim)';
		}
		planet.infoColumnEl.appendChild(planet.outfitterEl);
	}
}

customElements.define('es-map',ESMap);