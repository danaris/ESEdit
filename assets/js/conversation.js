const endpointNames = ['accept', 'decline', 'defer', 'launch', 'flee', 'depart', 'die', 'explode'];
var tokenReplacement = [{token: 'commodity', replacement: '<span class="replaceableToken">&lt;commodity&gt;</span>'},
	{token: 'tons', replacement: '<span class="replaceableToken">&lt;tons&gt;</span>'},
	{token: 'cargo', replacement: '<span class="replaceableToken">&lt;cargo&gt;</span>'},
	{token: 'bunks', replacement: '<span class="replaceableToken">&lt;bunks&gt;</span>'},
	{token: 'passengers', replacement: '<span class="replaceableToken">&lt;passengers&gt;</span>'},
	{token: 'fare', replacement: '<span class="replaceableToken">&lt;fare&gt;</span>'},
	{token: 'origin', replacement: '<span class="replaceableToken">&lt;origin&gt;</span>'},
	{token: 'planet', replacement: '<span class="replaceableToken">&lt;planet&gt;</span>'},
	{token: 'system', replacement: '<span class="replaceableToken">&lt;system&gt;</span>'},
	{token: 'destination', replacement: '<span class="replaceableToken">&lt;destination&gt;</span>'},
	{token: 'stopovers', replacement: '<span class="replaceableToken">&lt;stopovers&gt;</span>'},
	{token: 'planet stopovers', replacement: '<span class="replaceableToken">&lt;planet stopovers&gt;</span>'},
	{token: 'waypoints', replacement: '<span class="replaceableToken">&lt;waypoints&gt;</span>'},
	{token: 'payment', replacement: '<span class="replaceableToken">&lt;payment&gt;</span>'},
	{token: 'fine', replacement: '<span class="replaceableToken">&lt;fine&gt;</span>'},
	{token: 'date', replacement: '<span class="replaceableToken">&lt;date&gt;</span>'},
	{token: 'day', replacement: '<span class="replaceableToken">&lt;day&gt;</span>'},
	{token: 'npc', replacement: '<span class="replaceableToken">&lt;npc&gt;</span>'},
	{token: 'npc model', replacement: '<span class="replaceableToken">&lt;npc model&gt;</span>'},
	{token: 'first', replacement: '<span class="replaceableToken">&lt;first&gt;</span>'},
	{token: 'last', replacement: '<span class="replaceableToken">&lt;last&gt;</span>'},
	{token: 'ship', replacement: '<span class="replaceableToken">&lt;ship&gt;</span>'},
];

class ConversationElement extends HTMLElement {
	constructor() {
		super();

		this.nodeTypes = ['text', 'scene', 'label', 'choice', 'name', 'branch', 'action'];
		this.nodes = [];
		this.elements = [];
		this.activeText = null;
		this.curIndex = 0;
		this.baseIndent = 0;

		this.changeCallback = null;

		this.shadow = this.attachShadow({mode: 'open'});
		this.container = document.createElement('div');

		this.labelsList = document.createElement('datalist');
		this.labelsList.id = 'labels';
		this.container.appendChild(this.labelsList);
		
		this.styleElement = document.createElement('style');
		this.styleElement.textContent = `
.conversationText {
	margin-top: 0.25rem;
	margin-bottom: 0.25rem;
	text-indent: 4rem;
}
.textNodeOptions {
	margin-bottom: 0.5rem;
}`;

		this.shadow.appendChild(this.styleElement);
		this.shadow.appendChild(this.container);

	}
	connectedCallback() {

		this.createNode(0);
	}

	textKeyHandler(event, index) {
		if (event.which == 13) { // Return key
			console.log('Handling return key for node ' + index);
			event.preventDefault();
			this.nodes[index].textElement.textElement.blur();

			var lastIndex = this.nodes.length - 1;
			var lastNode = this.nodes[lastIndex];
			
			if (lastNode.getValue() != '' && lastNode.getValue() != '	') {
				var newIndex = this.nodes.length;
				this.createNode(newIndex);
				this.nodes[newIndex].textElement.textElement.toggleEditable();
				this.nodes[newIndex].textElement.textElement.focus();
			}
			if (this.changeCallback) {
				this.changeCallback(event);
			}
		}
	}

	addTextKeyHandler(textElement, index) {
		console.log('Adding text handler for node ' + index);
		var root = this;
		textElement.onkeydown = function(event) { root.textKeyHandler(event, index); };
	}

	createNode(index) {
		console.log('Creating node ' + index);
		var root = this;
		var node = {};
		node.id = index;
		node.type = 'text';
		node.value = '';
		node.children = '';
		node.reindex = function(newIndex) {
			this.id = newIndex;
			this.titleText.textContent = 'Node ' + newIndex;
			this.container.id = 'node-' + newIndex;
			this.textElement.changeCallback = function(oldValue, newValue) { root.nodeChangeText(oldValue, newValue, newIndex); };
			this.labelElement.changeCallback = function(oldValue, newValue) { root.nodeChangeOther('label', oldValue, newValue, newIndex); };
			this.sceneElement.changeCallback = function(oldValue, newValue) { root.nodeChangeOther('scene', oldValue, newValue, newIndex); };
			this.branch.labelField.changeCallback = function(oldValue, newValue) { root.nodeChangeOther('branchLabel', oldValue, newValue, newIndex); };
			this.branch.conditionField.changeCallback = function(oldValue, newValue) { root.nodeChangeOther('branchCondition', oldValue, newValue, newIndex); };
			this.actionElement.changeCallback = function(oldValue, newValue) { root.nodeChangeOther('action', oldValue, newValue, newIndex); };
			this.choiceElement.changeCallback = function(oldValue, newValue) { root.nodeChangeOther('choice', oldValue, newValue, newIndex); };
		}
		node.getValue = function() {
			switch (node.type) {
				case 'text':
					return node.textElement.getValue();
				case 'label':
					return node.labelElement.value;
				case 'scene':
					return node.sceneElement.value;
				case 'branch':
					var branchText = 'branch ' + node.branch.labelField.value + '\n';
					var condText = node.branch.conditionField.value;
					for (var line of condText.split('\n')) {
						branchText += '	' + line + '\n';
					}
					return branchText;
				case 'action':
					return node.actionElement.value;
				case 'choice':
					return node.choiceElement.toData();
			}
		}

		node.container = document.createElement('div');
		node.container.style.display = 'grid';
		node.container.style.gridTemplateColumns = '12rem auto';
		node.container.id = 'node-' + index;
		node.container.classList.add('node');

		node.metaContainer = document.createElement('div');
		node.container.appendChild(node.metaContainer);

		node.titleText = document.createElement('div');
		node.titleText.textContent = 'Node ' + index;
		node.metaContainer.appendChild(node.titleText);

		node.typeSelector = document.createElement('select');
		node.typeSelector.classList.add('nodeType');
		node.typeChoices = [];
		for (var type of this.nodeTypes) {
			var choice = document.createElement('option');
			choice.textContent = type;
			if (type == 'text') {
				choice.setAttribute('selected','selected');
			}
			node.typeChoices.push(choice);
			node.typeSelector.appendChild(choice);
		}
		node.typeSelector.onchange = function(event) { root.nodeChangeType(event, node.id); };
		node.metaContainer.appendChild(node.typeSelector);

		node.textElement = new ConversationTextNode();
		node.textElement.index = index;
		node.textElement.baseIndent = this.baseIndent + 1;
		if (index > 0) {
			node.textElement.textElement.value = '	';
		}
		this.addTextKeyHandler(node.textElement, index);
		node.container.appendChild(node.textElement);

		node.textElement.addNodeButton.onclick = function(event) { root.createNode(node.id + 1); };

		node.sceneElement = new DisplayEditText();
		node.sceneElement.setAttribute('field-label','');

		node.labelElement = new DisplayEditText();
		node.labelElement.setAttribute('field-label','');

		node.nameElement = document.createElement('span');

		node.branchElement = document.createElement('div');
		node.branchElement.style.display = 'grid';
		node.branchElement.style.gridTemplateColumns = '10rem auto';
		node.branch = {};
		node.branch.labelLabel = document.createElement('span');
		node.branch.labelLabel.textContent = 'branch to label:';
		node.branchElement.appendChild(node.branch.labelLabel);
		node.branch.labelField = document.createElement('input');
		node.branch.labelField.setAttribute('type','text');
		node.branch.labelField.setAttribute('list','labels');
		node.branch.labelField.style.width = '10rem';
		node.branchElement.appendChild(node.branch.labelField);
		node.branch.condLabel = document.createElement('span');
		node.branch.condLabel.textContent = 'branch conditions:';
		node.branchElement.appendChild(node.branch.condLabel);
		node.branch.conditionField = new DisplayEditTextarea();
		node.branch.conditionField.setAttribute('field-label','');
		node.branch.conditionField.setAttribute('allow-tabs','allow-tabs');
		node.branchElement.appendChild(node.branch.conditionField);

		node.actionElement = new DisplayEditTextarea();
		node.actionElement.setAttribute('field-label', '');

		node.choiceElement = new ConversationChoiceNode();
		node.choiceElement.baseIndent = this.baseIndent + 1;

		node.reindex(index);

		if (index < this.nodes.length - 1) {
			// We need to make space for it if it's in the middle
			this.nodes.splice(index, 0, node);
			for (var i = index + 1; i < this.nodes.length; i++) {
				this.nodes[i].reindex(i);
			}
			this.nodes[index - 1].container.after(node.container);
		} else {
			this.nodes[index] = node;
			this.container.appendChild(node.container);
		}

		var labelOption = document.createElement('option');
		labelOption.textContent = index;
		this.labelsList.appendChild(labelOption);
	}
	nodeChangeType(event, index) {
		var newType = this.nodes[index].typeSelector.value;
		var oldType = this.nodes[index].type;
		this.nodes[index].type = newType;
		// TODO: blank other types
		if (oldType == 'text') {
			this.nodes[index].textElement.setValue('', false);
		}
		this.nodes[index][oldType + 'Element'].remove();
		this.nodes[index].container.appendChild(this.nodes[index][newType + 'Element']);
	}
	nodeChangeText(oldValue, newValue, index) {
		this.nodes[index].text = newValue;
		if (newValue == '') {
			if (this.nodes.length > 1) {
				this.deleteNode(index);
			}
		} else if (index == this.nodes.length - 1) {
			this.createNode(this.nodes.length);
		}
	}
	deleteNode(index) {
		this.nodes[index].container.remove();
		this.nodes.splice(index, 1);
		for (var i in this.nodes) {
			if (this.nodes[i].id != i) {
				this.nodes[i].reindex(i);
			}
		}
		this.labelsList.children[index].remove();
	}
	nodeChangeOther(changedWhat, oldValue, newValue, index) {
		switch (changedWhat) {
			case 'label':
				this.labelsList.children[index].textContent = newValue;
				break;
			
		}
		if (index == this.nodes.length - 1 && newValue != oldValue && newValue != '') {
			this.createNode(this.nodes.length);
		}
		if (this.changeCallback) {
			this.changeCallback(oldValue, newValue);
		}
	}
	toData() {
		var dataText = 'conversation\n';
		var baseIndentTabs = '';
		baseIndentTabs.padStart(this.baseIndent, '	');
		for (var i in this.nodes) {
			var node = this.nodes[i];
			var nodeText = '';
			switch (node.type) {
				case 'text':
					nodeText = node.textElement.toData();
					if (nodeText == '`	`' && i == this.nodes.length - 1) {
						continue;
					}
					break;
				case 'label':
					var labelValue = node.labelElement.value;
					if (labelValue.includes(' ')) {
						labelValue = '"' + labelValue + '"';
					}
					nodeText = 'label ' + labelValue;
					break;
				case 'scene':
					var sceneValue = node.sceneElement.value;
					if (sceneValue.includes(' ')) {
						sceneValue = '"' + sceneValue + '"';
					}
					nodeText = 'scene ' + sceneValue;
					break;
				case 'name':
					nodeText = 'name';
					break;
				case 'branch':
					nodeText = 'branch ' + node.branch.labelField.value + '\n';
					var branchLines = node.branch.conditionField.value.split('\n');
					for (var line of branchLines) {
						nodeText += '	' + line + '\n';
					}
					break;
				case 'action':
					nodeText = 'action\n';
					var actionLines = node.actionElement.value.split('\n');
					for (var line of actionLines) {
						nodeText += '	' + line + '\n';
					}
					break;
				case 'choice':
					nodeText = node.choiceElement.toData();
					break;
			}
			var nodeLines = nodeText.split('\n');
			for (var line of nodeLines) {
				dataText += baseIndentTabs + '	' + line + '\n';
			}
		}

		return dataText;
	}
}

class ConversationChoiceNode extends HTMLElement {
	constructor() {
		super();

		this.elements = [];
		this.headers = [];

		this.container = document.createElement('div');

		this.changeCallback = null;
	}

	connectedCallback() {
		this.appendChild(this.container);
		this.addElement(0);
	}

	addElement(index) {

		this.headers[index] = document.createElement('div');
		this.headers[index].textContent = 'Choice ' + index + ':';
		this.container.appendChild(this.headers[index]);

		this.elements[index] = new ConversationTextNode();
		this.elements[index].textElement.value = '	';
		this.elements[index].index = index;
		this.elements[index].baseIndent = 1;
		this.addHandlers(this.elements[index], index);
		this.elements[index].addNodeButton.remove();

		this.container.appendChild(this.elements[index]);
	}

	addHandlers(textElement, index) {
		var root = this;
		textElement.onkeydown = function(event) { root.textKeyHandler(event, index); };
		textElement.changeCallback = function(newValue, oldValue) { root.choiceChanged(newValue, oldValue, index); };
	}

	textKeyHandler(event, index) {
		if (event.which == 13) { // Return key
			event.preventDefault();
			this.elements[index].textElement.blur();

			var lastIndex = this.elements.length - 1;
			var lastElement = this.elements[lastIndex];
			
			if (lastElement.getValue() != '') {
				var newIndex = this.elements.length;
				this.addElement(newIndex);
				this.elements[newIndex].textElement.toggleEditable();
				this.elements[newIndex].textElement.focus();
			}
			if (this.changeCallback) {
				this.changeCallback(event);
			}
		}
	}

	choiceChanged(newValue, oldValue, index) {
		if (newValue == '') {
			this.deleteElement(index);
		}
		if (this.changeCallback) {
			this.changeCallback(newValue, oldValue);
		}
	}

	deleteElement(index) {
		this.elements[index].container.remove();
		this.headers[index].remove();
		this.elements.splice(index, 1);
		for (var i in this.elements) {
			if (this.elements[i].id != i) {
				this.elements[i].index = i;
				this.headers[i].textContent = 'Choice ' + i;
			}
		}
	}

	toData() {
		var dataText = 'choice\n';
		for (var element of this.elements) {
			var elementText = element.toData();
			// dataText += elementText + '\n';
			for (var line of elementText.split('\n')) {
				dataText += '	' + line + '\n';
			}
		}

		return dataText;
	}
}

class ConversationTextNode extends HTMLElement {
	constructor() {
		super();

		this.container = document.createElement('div');

		this.index = 0;
		this.baseIndent = 0;
		this.text = '';
		this.goto = '';
		this.toDisplay = '';
		this.endpoint = 'none';

		this.textElement = new DisplayEditTextarea();
		this.textElement.setAttribute('start-editable','start-editable');
		var root = this;
		this.textElement.changeCallback = function(oldValue, newValue) { root.textChanged(oldValue, newValue); };
		for (var i=0; i<tokenReplacement.length; i++) {
			var replacement = tokenReplacement[i];
			var replacer = document.createElement('replace');
			replacer.setAttribute('search','<'+replacement['token']+'>');
			replacer.setAttribute('replace',replacement['replacement']);
			this.textElement.appendChild(replacer);
		}

		
		this.childElements = document.createElement('details');
		this.childElements.classList.add('textNodeOptions');
		this.childElementsTitle = document.createElement('summary');
		this.childElementsTitle.textContent = 'Options';
		this.childElements.appendChild(this.childElementsTitle);

		this.gotoElement = document.createElement('div');
		this.gotoElement.classList.add('textChild');
		this.gotoElementLabel = document.createElement('span');
		this.gotoElementLabel.textContent = 'Goto: ';
		this.gotoElement.appendChild(this.gotoElementLabel);
		this.gotoElementField = document.createElement('input');
		this.gotoElementField.setAttribute('type','text');
		this.gotoElementField.setAttribute('list','labels');
		this.gotoElementField.onchange = function(event) { root.goto = this.value; };
		this.gotoElement.appendChild(this.gotoElementField);
		this.childElements.appendChild(this.gotoElement);

		this.toDisplayElement = document.createElement('div');
		this.toDisplayElement.classList.add('textChild');
		this.toDisplayElementLabel = document.createElement('span');
		this.toDisplayElementLabel.textContent = 'To Display: ';
		this.toDisplayElement.appendChild(this.toDisplayElementLabel);
		this.toDisplayElementField = document.createElement('input');
		this.toDisplayElementField.setAttribute('type','text');
		this.toDisplayElementField.onchange = function(event) { root.toDisplay = this.value; };
		this.toDisplayElement.appendChild(this.toDisplayElementField);
		this.childElements.appendChild(this.toDisplayElement);

		this.endpointElement = document.createElement('div');
		this.endpointElement.classList.add('textChild');
		this.endpointElementLabel = document.createElement('span');
		this.endpointElementLabel.textContent = 'Endpoint: ';
		this.endpointElement.appendChild(this.endpointElementLabel);
		this.endpointElementField = document.createElement('select');
		this.endpointChoices = {};
		this.endpointChoices['none'] = document.createElement('option');
		this.endpointChoices['none'].textContent = 'none';
		this.endpointElementField.appendChild(this.endpointChoices['none']);
		for (var endpointName of endpointNames) {
			this.endpointChoices[endpointName] = document.createElement('option');
			this.endpointChoices[endpointName].textContent = endpointName;
			this.endpointElementField.appendChild(this.endpointChoices[endpointName]);
		}
		this.endpointElementField.onchange = function(event) { root.endpoint = this.value; };
		this.endpointElement.appendChild(this.endpointElementField);
		this.childElements.appendChild(this.endpointElement);

		this.addNodeButton = document.createElement('button');
		this.addNodeButton.setAttribute('type','button');
		this.addNodeButton.textContent = 'Add Node After';
		this.childElements.appendChild(this.addNodeButton);

		this.container.appendChild(this.textElement);
		this.container.appendChild(this.childElements);

		this.changeCallback = null;
		
	}

	connectedCallback() {
		this.textElement.setAttribute('field-name', 'node-' + this.index);
		this.textElement.setAttribute('field-label', '');
		this.appendChild(this.container);
	}

	textChanged(oldValue, newValue) {
		this.text = newValue;
		if (this.changeCallback) {
			this.changeCallback(oldValue, newValue);
		}
	}

	setValue(newValue, fireCallback) {
		this.text = newValue;
		this.textElement.setValue(newValue, fireCallback);
	}

	getValue() {
		return this.textElement.value;
	}

	toData() {
		var baseIndentTabs = '';
		baseIndentTabs.padStart(this.baseIndent, '	');
		var dataText = baseIndentTabs + '`' + this.getValue() + '`';
		if (this.goto) {
			dataText += "\n";
			var gotoValue = this.goto;
			if (gotoValue.includes(' ')) {
				gotoValue = '"' + gotoValue + '"';
			}
			dataText += baseIndentTabs + '	goto ' + gotoValue;
		}
		if (this.toDisplay) {
			dataText += "\n";
			dataText += baseIndentTabs + '	to display';
			dataText += "\n";
			var toDisplayLines = this.toDisplay.split("\n");
			for (var line of toDisplayLines) {
				dataText += baseIndentTabs + '		' + line + '\n';
			}
		}
		if (this.endpoint != 'none') {
			dataText += "\n";
			dataText += baseIndentTabs + '	' + this.endpoint;
		}

		return dataText;
	}

}

customElements.define('conversation-edit',ConversationElement);
customElements.define('conversation-choice',ConversationChoiceNode);
customElements.define('conversation-text',ConversationTextNode);