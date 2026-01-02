class ESShipDisplay extends HTMLElement {
	constructor() {
		super();
		this.ship = null;
		this.outfits = {};
		this.outfitCategories = [];

		this.miscAttributes = ['cloak','gaslining', 'ion resistance', 'outfit scan efficiency', 'outfit scan power', 'cargo scan efficiency', 'cargo scan power', 'tactical scan power', 'ramscoop'];

		this.shadow = this.attachShadow({mode: 'open'});
	}

	connectedCallback() {
		if (this.hasAttribute('ship-load-url')) {
			this.shipLoadUrl = this.getAttribute('ship-load-url');

			var shipFetch = fetch(this.shipLoadUrl);
			shipFetch.then((response) => {
				var shipJSON = response.json();

				shipJSON.then((shipData) => {
					this.ship = shipData;

					this.loadOutfits();
				});
			});
		} else {
			if (this.ship) {
				this.loadOutfits();
			}
		}
	}

	loadOutfits() {
		var outfitFetches = [];

		for (var o of Object.keys(this.ship.outfits)) {
			var outfitFetch = fetch('/outfitRaw/' + o + '.json');
			outfitFetches.push(outfitFetch);
		}

		Promise.all(outfitFetches).then((responses) => {
			var outfitJSONs = [];
			for (var i in responses) {
				var jsonP = responses[i].json();
				outfitJSONs.push(jsonP);
			}

			Promise.all(outfitJSONs).then((outfitData) => {
				for (var i in outfitData) {
					var outfit = outfitData[i];
					this.outfits[outfit.trueName] = outfit;
					if (!this.outfitCategories.includes(outfit.category)) {
						this.outfitCategories.push(outfit.category);
					}
				}

				this.setupShip();
			});
		});
	}

	addLabeledField(fieldName, label, value, parent) {
		this[fieldName + 'Label'] = document.createElement('div');
		this[fieldName + 'Label'].classList.add('esLabel');
		this[fieldName + 'Label'].innerHTML = label;
		parent.appendChild(this[fieldName + 'Label']);

		this[fieldName + 'Field'] = document.createElement('div');
		this[fieldName + 'Field'].classList.add('esField');
		this[fieldName + 'Field'].textContent = value;
		parent.appendChild(this[fieldName + 'Field']);
	}

	addLabeledPowerField(fieldName, label, energyVal, heatVal, parent) {
		this[fieldName + 'Label'] = document.createElement('div');
		this[fieldName + 'Label'].classList.add('esLabel');
		this[fieldName + 'Label'].innerHTML = label;
		parent.appendChild(this[fieldName + 'Label']);

		this[fieldName + 'EnergyField'] = document.createElement('div');
		this[fieldName + 'EnergyField'].classList.add('esField');
		this[fieldName + 'EnergyField'].textContent = energyVal;
		parent.appendChild(this[fieldName + 'EnergyField']);

		this[fieldName + 'HeatField'] = document.createElement('div');
		this[fieldName + 'HeatField'].classList.add('esField');
		this[fieldName + 'HeatField'].textContent = heatVal;
		parent.appendChild(this[fieldName + 'HeatField']);
	}

	setupShip() {
		this.styleElement = document.createElement('style');
		this.styleElement.textContent = `
.esLabel {
	color: var(--medium);
	max-width: 15rem;
}
.esField {
	color: var(--bright);
	text-align: right;
	max-width: 10rem;
}
.esDataPanel {
	margin: 0.5rem;
	max-width: 20rem;
	align-content: start;
}
.esPanelHeader {
	color: var(--bright);
}
`;
		this.wrapper = document.createElement('div');
		this.shadow.appendChild(this.wrapper);

		this.wrapper.appendChild(this.styleElement);
		
		this.dataPanelContainer = document.createElement('div');
		this.dataPanelContainer.style.maxHeight = '90lvh';
		this.dataPanelContainer.style.display = 'flex';
		this.dataPanelContainer.style.alignContent = 'flex-start';
		this.dataPanelContainer.style.flexDirection = 'column';
		this.dataPanelContainer.style.flexWrap = 'wrap';
		this.wrapper.appendChild(this.dataPanelContainer);

		this.baseDataPanel = document.createElement('div');
		this.baseDataPanel.classList.add('esDataPanel');
		this.baseDataPanel.style.display = 'grid';
		this.baseDataPanel.style.gridTemplateColumns = 'auto auto';
		this.dataPanelContainer.appendChild(this.baseDataPanel);

		// TODO: This may want to become a DisplayEditText at some point, along with many of the others here
		this.addLabeledField('shipModel', 'model:', this.ship.trueModelName, this.baseDataPanel);
		this.addLabeledField('shipCategory', 'category:', this.ship.baseAttributes.category, this.baseDataPanel);
		this.addLabeledField('shipHullCost', 'hull cost:', formatNumber(this.ship.baseAttributes.cost), this.baseDataPanel);
		this.addLabeledField('shipTotalCost', 'total cost:', formatNumber(this.getCombinedAttribute('cost')), this.baseDataPanel);

		this.primaryDataPanel = document.createElement('div');
		this.primaryDataPanel.classList.add('esDataPanel');
		this.primaryDataPanel.style.display = 'grid';
		this.primaryDataPanel.style.gridTemplateColumns = 'auto auto';
		this.dataPanelContainer.appendChild(this.primaryDataPanel);

		var shieldGen = this.getCombinedAttribute('shield generation');
		var shieldDelayed = this.getCombinedAttribute('delayed shield generation');
		var shieldMult = this.getCombinedAttribute('shield generation multiplier');
		var shieldCharge = 60 * (shieldGen + shieldDelayed) * (1 + shieldMult);
		if (countDecimals(shieldCharge) > 4) {
			shieldCharge = shieldCharge.toFixed(2);
		}
		var shieldLabel = 'shields';
		var shieldVal = this.ship.shields;
		if (shieldCharge > 0) {
			shieldLabel += ' (charge)';
			shieldVal += ' (' + shieldCharge + '/s)'
		}
		shieldLabel += ':';
		this.addLabeledField('shipShields', shieldLabel, shieldVal, this.primaryDataPanel);

		var hullRate = this.getCombinedAttribute('hull repair rate');
		var hullDelayed = this.getCombinedAttribute('delayed hull repair rate');
		var hullMult = this.getCombinedAttribute('hull repair multiplier');
		var hullRep = 60 * (hullRate + hullDelayed) * (1 + hullMult);
		if (countDecimals(hullRep) > 4) {
			hullRep = hullRep.toFixed(2);
		}
		var hullLabel = 'hull';
		var hullVal = this.ship.hull;
		if (hullRep > 0) {
			hullLabel += ' (repair)';
			hullVal += ' (' + hullRep + '/s)';
		}
		hullLabel += ':';

		var emptyMass = this.getCombinedAttribute('mass');
		var cargoSpace = this.getCombinedAttribute('cargo space');

		this.addLabeledField('shipHull', hullLabel, hullVal, this.primaryDataPanel);
		this.addLabeledField('shipMass', 'unloaded mass:', emptyMass + ' tons', this.primaryDataPanel);
		this.addLabeledField('shipCargo', 'unloaded cargo space:', cargoSpace + ' tons', this.primaryDataPanel);

		var reqCrew = this.getCombinedAttribute('required crew');
		var bunks = this.getCombinedAttribute('bunks');
		this.addLabeledField('shipBunks', 'required crew / bunks:', reqCrew + ' / ' + bunks, this.primaryDataPanel);
		this.addLabeledField('shipFuel', 'fuel capacity:', this.getCombinedAttribute('fuel capacity'), this.primaryDataPanel);

		this.movementDataPanel = document.createElement('div');
		this.movementDataPanel.classList.add('esDataPanel');
		this.movementDataPanel.style.display = 'grid';
		this.movementDataPanel.style.gridTemplateColumns = 'auto auto';
		this.dataPanelContainer.appendChild(this.movementDataPanel);

		this.movementHeader = document.createElement('div');
		this.movementHeader.classList.add('esPanelHeader');
		this.movementHeader.style.gridColumnStart = 'span 2';
		this.movementHeader.textContent = 'movement (full - no cargo)';
		this.movementDataPanel.appendChild(this.movementHeader);

		var drag = this.getCombinedAttribute('drag');
		var thrust = this.getCombinedAttribute('thrust');
		// TODO: use afterburner if no thrust
		var maxSpeed = 60 * thrust / drag;
		this.addLabeledField('shipMaxSpeed', 'max speed:', formatNumber(maxSpeed), this.movementDataPanel);

		var fullMass = emptyMass + cargoSpace;
		var reduction = 1 + this.getCombinedAttribute('inertia reduction');
		emptyMass /= reduction;
		fullMass /= reduction;

		var baseAccel = 3600 * thrust * (1 + this.getCombinedAttribute('acceleration multiplier'));
		var emptyAccel = baseAccel / emptyMass;
		var fullAccel = baseAccel / fullMass;

		this.addLabeledField('shipAccel', 'acceleration:', formatNumber(fullAccel) + ' - ' + formatNumber(emptyAccel), this.movementDataPanel);

		var baseTurn = 60 * this.getCombinedAttribute('turn') * (1 + this.getCombinedAttribute('turn multiplier'));
		var emptyTurn = baseTurn / emptyMass;
		var fullTurn = baseTurn / fullMass;
		this.addLabeledField('shipTurn', 'turning:', formatNumber(fullTurn) + ' - ' + formatNumber(emptyTurn), this.movementDataPanel);

		this.expandDataPanel = document.createElement('div');
		this.expandDataPanel.classList.add('esDataPanel');
		this.expandDataPanel.style.display = 'grid';
		this.expandDataPanel.style.gridTemplateColumns = 'auto auto';
		this.dataPanelContainer.appendChild(this.expandDataPanel);

		this.addLabeledField('shipOutfitSpace', 'outfit space free:', this.getCombinedAttribute('outfit space') + ' / ' + this.ship.baseAttributes.attributes._store['outfit space'], this.expandDataPanel);
		this.addLabeledField('shipWeaponSpace', ' &nbsp;weapon capacity:', this.getCombinedAttribute('weapon capacity') + ' / ' + this.ship.baseAttributes.attributes._store['weapon capacity'], this.expandDataPanel);
		this.addLabeledField('shipEngineSpace', ' &nbsp;engine capacity:', this.getCombinedAttribute('engine capacity') + ' / ' + this.ship.baseAttributes.attributes._store['engine capacity'], this.expandDataPanel);

		var gunPorts = this.ship.baseAttributes.attributes._store['gun ports'];
		if (gunPorts > 0) {
			this.addLabeledField('shipGunPorts', 'gun ports free:', this.getCombinedAttribute('gun ports') + ' / ' + this.ship.baseAttributes.attributes._store['gun ports'], this.expandDataPanel);
		}
		var turretMounts = this.ship.baseAttributes.attributes._store['turret mounts']
		if (turretMounts > 0) {
			this.addLabeledField('shipTurretMounts', 'turret mounts free:', this.getCombinedAttribute('turret mounts') + ' / ' + this.ship.baseAttributes.attributes._store['turret mounts'], this.expandDataPanel);
		}
		var bays = {};
		for (var b in this.ship.bays) {
			var bay = this.ship.bays[b];
			var cat = bay.category.toLowerCase();
			if (bays[cat] == undefined) {
				bays[cat] = 0;
			}
			bays[cat]++;
		}
		for (var type of Object.keys(bays)) {
			var count = bays[type];
			var typeInName = type.substring(0, 1).toUpperCase() + type.substring(1);
			this.addLabeledField('ship' + typeInName + 'Bays', type + ' bays:', count, this.expandDataPanel);
		}

		this.powerDataPanel = document.createElement('div');
		this.powerDataPanel.classList.add('esDataPanel');
		this.powerDataPanel.style.display = 'grid';
		this.powerDataPanel.style.gridTemplateColumns = 'auto 5rem 5rem';
		this.dataPanelContainer.appendChild(this.powerDataPanel);
		
		this.powerEnergyHead = document.createElement('div');
		this.powerEnergyHead.style.gridColumnStart = '2';
		this.powerEnergyHead.style.textAlign = 'right';
		this.powerEnergyHead.textContent = 'energy';
		this.powerDataPanel.appendChild(this.powerEnergyHead);

		this.powerHeatHead = document.createElement('div');
		this.powerHeatHead.style.gridColumnStart = '3';
		this.powerHeatHead.style.textAlign = 'right';
		this.powerHeatHead.textContent = 'heat';
		this.powerDataPanel.appendChild(this.powerHeatHead);

		var coolingInefficiency = this.getCombinedAttribute('cooling inefficiency');
		var coolingEfficiency = 2 + 2 / (1 + Math.pow(Math.E, coolingInefficiency / -2)) - 4 / (1 + Math.pow(Math.E, coolingInefficiency / -4));

		var idleEnergyPerFrame = this.getCombinedAttribute('energy generation') + this.getCombinedAttribute('solar collection') - this.getCombinedAttribute('energy consumption') - this.getCombinedAttribute('cooling energy');
		var idleHeatPerFrame = this.getCombinedAttribute('heat generation') + this.getCombinedAttribute('solar heat') + this.getCombinedAttribute('fuel heat') - coolingEfficiency * (this.getCombinedAttribute('cooling') + this.getCombinedAttribute('active cooling'));

		this.addLabeledPowerField('shipIdle', 'idle:', formatNumber(60 * idleEnergyPerFrame), formatNumber(60 * idleHeatPerFrame), this.powerDataPanel);

		var movingEnergyPerFrame = Math.max(this.getCombinedAttribute('thrusting energy'), this.getCombinedAttribute('reverse thrusting energy')) + this.getCombinedAttribute('turning energy') + this.getCombinedAttribute('afterburner energy');
		var movingHeatPerFrame = Math.max(this.getCombinedAttribute('thrusting heat'), this.getCombinedAttribute('reverse thrusting heat')) + this.getCombinedAttribute('turning heat') + this.getCombinedAttribute('afterburner heat');

		this.addLabeledPowerField('shipMoving', 'moving:', formatNumber(-60 * movingEnergyPerFrame), formatNumber(60 * movingHeatPerFrame), this.powerDataPanel);

		var firingEnergy = 0;
		var firingHeat = 0;
		for (var o of Object.keys(this.ship.outfits)) {
			var outfit = this.outfits[o];
			var count = this.ship.outfits[o];
			if (outfit.isWeapon && outfit.reload > 0) {
				firingEnergy += count * outfit.firingEnergy / outfit.reload;
				firingHeat += count * outfit.firingHeat / outfit.reload;
			}
		}
		this.addLabeledPowerField('shipFiring', 'firing:', formatNumber(-60 * firingEnergy), formatNumber(60 * firingHeat), this.powerDataPanel);

		var shieldEnergy = 0;
		var shieldHeat = 0;
		if (shieldCharge > 0) {
			shieldEnergy = (this.getCombinedAttribute('shield energy') + this.getCombinedAttribute('delayed shield energy')) * (1 + this.getCombinedAttribute('shield energy multiplier'));
			shieldHeat = (this.getCombinedAttribute('shield heat') + this.getCombinedAttribute('delayed shield heat')) * (1 + this.getCombinedAttribute('shield heat multiplier'));
		}
		var hullEnergy = 0;
		var hullHeat = 0;
		if (hullRep > 0) {
			hullEnergy = (this.getCombinedAttribute('hull energy') + this.getCombinedAttribute('delayed hull energy')) * (1 + this.getCombinedAttribute('hull energy multiplier'));
			hullHeat = (this.getCombinedAttribute('hull heat') + this.getCombinedAttribute('delayed hull heat')) * (1 + this.getCombinedAttribute('hull heat multiplier'));
		}
		var shHLabel = '';
		if (shieldEnergy > 0 && hullEnergy > 0) {
			shHLabel = 'shields / hull:';
		} else if (hullEnergy > 0) {
			shHLabel = 'repairing hull:';
		} else {
			shHLabel = 'charging shields:';
		}
		this.addLabeledPowerField('shipRegen', shHLabel, formatNumber(-60 * (shieldEnergy + hullEnergy)), formatNumber(60 * (shieldHeat + hullHeat)), this.powerDataPanel);

		var overallEnergy = idleEnergyPerFrame - movingEnergyPerFrame - firingEnergy - shieldEnergy - hullEnergy;
		var overallHeat = idleHeatPerFrame + movingHeatPerFrame + firingHeat + shieldHeat + hullHeat;
		this.addLabeledPowerField('shipNet', 'net change:', formatNumber(60 * overallEnergy), formatNumber(60 * overallHeat), this.powerDataPanel);

		var maxEnergy = this.getCombinedAttribute('energy capacity');
		var heatDissipation = .001 * this.getCombinedAttribute('heat dissipation');
		const MAXIMUM_TEMPERATURE = 100;
		var heatCapacity = MAXIMUM_TEMPERATURE * (emptyMass + this.getCombinedAttribute('heat capacity'));
		var maxHeat = 60 * heatDissipation * heatCapacity;
		this.addLabeledPowerField('shipMax', 'max:', formatNumber(maxEnergy), formatNumber(maxHeat), this.powerDataPanel);

		this.outfitPanel = document.createElement('div');
		this.outfitPanel.classList.add('esDataPanel');
		this.outfitPanel.style.flexDirection = 'column';
		this.dataPanelContainer.appendChild(this.outfitPanel);

		for (var cat of this.outfitCategories) {
			var catPanel = document.createElement('div');
			catPanel.classList.add('esPanel');
			catPanel.style.display = 'grid';
			catPanel.style.gridTemplateColumns = 'auto auto';
			catPanel.style.maxWidth = '20rem';
			this.outfitPanel.appendChild(catPanel);

			var catHeader = document.createElement('div');
			catHeader.classList.add('esPanelHeader');
			catHeader.textContent = cat;
			catHeader.style.gridColumnStart = 'span 2';
			catPanel.appendChild(catHeader);
			for (var o of Object.keys(this.ship.outfits)) {
				var outfit = this.outfits[o];
				if (outfit.category != cat) {
					continue;
				}
				var count = this.ship.outfits[o];
				this.addLabeledField('ship' + outfit, outfit.displayName, count, catPanel);
			}
		}

		this.miscAttributePanel = document.createElement('div');
		this.miscAttributePanel.classList.add('esDataPanel');
		this.miscAttributePanel.style.display = 'grid';
		this.miscAttributePanel.style.gridTemplateColumns = 'auto auto';

		var hasMiscAttr = false;
		for (var a of this.miscAttributes) {
			var aVal = this.getCombinedAttribute(a);
			if (aVal != 0) {
				this.addLabeledField('ship_' + a, a, aVal, this.miscAttributePanel);
				hasMiscAttr = true;
			}
		}
		if (hasMiscAttr) {
			this.dataPanelContainer.appendChild(this.miscAttributePanel);
		}
		
		// this.shipSpriteContainer = document.createElement('div');
		// this.dataPanelContainer.appendChild(this.shipSpriteContainer);

		this.shipSprite = new ESBody();
		this.shipSprite.body = this.ship;
		this.dataPanelContainer.appendChild(this.shipSprite);

		// this.shipSpriteContainer.appendChild(this.shipSprite);
		this.shipSprite.animate();

		// this.shipSprite = document.createElement('img');
		// this.shipSprite.src = '/sprite/' + this.ship.sprite;
		// this.shipSpriteContainer.appendChild(this.shipSprite);
	}

	getCombinedAttribute(attrName) {
		var base = this.ship.baseAttributes.attributes._store[attrName] ?? this.ship.baseAttributes[attrName];
		if (base == undefined) {
			base = 0;
		}
		for (var o of Object.keys(this.ship.outfits)) {
			var outfit = this.outfits[o];
			var outfitCount = this.ship.outfits[o];
			var outfitValue = outfit.attributes._store[attrName] ?? outfit[attrName];
			if (outfitValue != undefined) {
				base += (outfitValue * outfitCount);
			}
		}

		if (countDecimals(base) > 4) {
			base = parseFloat(base.toFixed(2));
		}

		return base;
	}
}

function formatNumber(num) {
	var thresholds = {'1000000000000': 'T', '1000000000': 'B', '1000000': 'M'};
	for (var t of Object.keys(thresholds)) {
		var tInt = parseInt(t);
		if (num > tInt) {
			num = num / tInt;
			num = num.toFixed(3);
			return num + thresholds[t];
		}
	}

	if (countDecimals(num) > 3) {
		num = num.toFixed(2);
	}
	return num;
}

function countDecimals(value) {
	let text = value.toString()
	// verify if number 0.000005 is represented as "5e-6"
	if (text.indexOf('e-') > -1) {
	  let [base, trail] = text.split('e-');
	  let deg = parseInt(trail, 10);
	  return deg;
	}
	// count decimals for number in representation like "0.123456"
	if (Math.floor(value) !== value) {
	  return value.toString().split(".")[1].length || 0;
	}
	return 0;
}

customElements.define('es-ship-display',ESShipDisplay);
