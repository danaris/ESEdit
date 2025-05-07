class DisplayEditText extends HTMLElement {

	constructor() {
		super();
		const shadow = this.attachShadow({mode: 'open'});

		const fieldName = this.getAttribute('field-name');
		const fieldLabel = this.getAttribute('field-label');
		const fieldValue = this.getAttribute('field-value');
		const fieldPlaceholder = this.getAttribute('field-placeholder');

		const labelStyle = this.getAttribute('label-style');
		const overrideStyle = this.getAttribute('style');

		if (fieldPlaceholder) {
			this.placeholder = fieldPlaceholder;
		} else {
			this.placeholder = '';
		}

		this.wrapper = document.createElement('div');
		this.wrapper.setAttribute('class','editableContainer');
		if (overrideStyle) {
			this.wrapper.setAttribute('style', overrideStyle);
		}

		this.label = document.createElement('label');
		this.label.setAttribute('for', fieldName);
		this.label.textContent = fieldLabel;
		if (labelStyle) {
			this.label.setAttribute('style',labelStyle);
		}

		const icon = document.createElement('img');
		icon.src = '/images/pencil-gray-16.png';

		this.display = document.createElement('div');
		this.display.setAttribute('class','displayField');
		this.display.textContent = fieldValue;
		//this.display.setAttribute('onclick','toggleEditable(event);');
		const root = this;
		this.display.onclick = function() { root.toggleEditable(root) };

		this.edit = document.createElement('input');
		this.edit.setAttribute('class','editableField');
		this.edit.setAttribute('type','text');
		this.edit.setAttribute('name',fieldName);
		this.edit.setAttribute('value',fieldValue);
		//this.edit.setAttribute('onfocusout','toggleEditable(event);');
		this.edit.onfocusout = function() { root.toggleEditable(root) };
		this.edit.hidden = true;
		this.edit.onchange = function() { var oldValue = root.value; root.value = this.value; root.changed(oldValue, this.value); };

		const style = document.createElement('style');
		style.textContent = `
		.editableContainer {
			display: grid;
			grid-template-columns: 12em auto 16px;
		}
		.editableContainerLabelless {
			display: grid;
			grid-template-columns: 0px auto 16px;
		}
		.editableContainer input {
			background-color: var(--bright);
			color: var(--dim);
		}
		.editableField {
			background-color: var(--bright);
			color: var(--dim);
			width: 100%;
		}
		.displayField {
			background-color: var(--dim);
			color: var(--bright);
			padding: 0.1rem;
			min-width: 1.5rem;
		}
		.replaceableToken {
			display: inline-block;
			background-color: rgba(200,255,200,0.3);
			border-radius: 0.75rem;
		}`;

		shadow.appendChild(this.wrapper);
		this.wrapper.appendChild(style);
		this.wrapper.appendChild(this.label);
		this.wrapper.appendChild(this.display);
		this.wrapper.appendChild(this.edit);
		this.wrapper.appendChild(icon);

		this.editing = false;
		this.value = fieldValue;

		this.changeCallback = null;
		if (this.displayReplacements == undefined) {
			this.displayReplacements = [];
		} else {
			console.log('Had display replacements before construct?');
		}
	}

	connectedCallback() {
		const fieldName = this.getAttribute('field-name');
		const fieldLabel = this.getAttribute('field-label');
		var fieldValue = this.getAttribute('field-value');
		if (!this.hasAttribute('field-value')) {
			fieldValue = '';
		}
		const fieldPlaceholder = this.getAttribute('field-placeholder');

		const labelStyle = this.getAttribute('label-style');
		const overrideStyle = this.getAttribute('style');

		if (fieldPlaceholder) {
			this.placeholder = fieldPlaceholder;
		} else {
			this.placeholder = '';
		}

		if (overrideStyle) {
			this.wrapper.setAttribute('style', overrideStyle);
		}
		if (labelStyle) {
			this.label.setAttribute('style',labelStyle);
		}
		this.label.setAttribute('for', fieldName);
		this.label.textContent = fieldLabel;
		if (fieldValue == '' && this.placeholder != '') {
			this.display.textContent = this.placeholder;
		} else {
			this.display.textContent = fieldValue;
		}
		this.edit.setAttribute('name',fieldName);
		this.edit.setAttribute('value',fieldValue);
		this.value = fieldValue;

		if (fieldLabel == '') {
			this.wrapper.setAttribute('class','editableContainerLabelless');
		}

		this.displayReplacements = [];
		for (var c=0; c<this.children.length; c++) {
			var child = this.children[c];
			if (child.tagName == 'REPLACE') {
				var replacement = {};
				replacement.search = new RegExp(child.getAttribute('search'), 'g');
				replacement.replace = child.getAttribute('replace');
				this.displayReplacements.push(replacement);
			}
		}
		this.displayText(this.display.textContent);
	}

	displayText(text) {
		var replaced = false;
		for (var i=0; i<this.displayReplacements.length; i++) {
			var snr = this.displayReplacements[i];
			var newText = text.replaceAll(snr.search, snr.replace);
			if (newText != text) {
				replaced = true;
				text = newText;
			}
		}
		if (replaced) {
			this.display.innerHTML = text;
		} else {
			this.display.textContent = text;
		}
	}

	toggleEditable(root) {
		if (this.editing) {
			if (this.edit.value == '' && this.placeholder != '') {
				this.displayText(this.placeholder);
			} else {
				this.displayText(this.edit.value);
			}
			this.value = this.edit.value;
			this.edit.hidden = true;
			this.display.hidden = false;
		} else {
			this.display.hidden = true;
			this.edit.hidden = false;
			this.edit.focus();
		}
		this.editing = !this.editing;
	}

	setValue(newValue, fireCallback) {
		var oldValue = this.value;
		this.displayText(newValue);
		this.edit.setAttribute('value', newValue);
		this.edit.value = newValue;
		this.value = newValue;
		this.setAttribute('field-value', newValue);
		if (fireCallback) {
			this.changed(oldValue, newValue);
		}
	}

	changed(oldValue, newValue) {
		if (this.changeCallback) {
			this.changeCallback(oldValue, newValue);
		}
	}
}

class DisplayEditTextarea extends HTMLElement {

	constructor() {
		super();
		const shadow = this.attachShadow({mode: 'open'});

		const fieldName = this.getAttribute('field-name');
		const fieldLabel = this.getAttribute('field-label');
		const allowTab = this.hasAttribute('allow-tab');
		const startEditable = this.hasAttribute('start-editable');
		this.allowTab = allowTab;
		var rows = this.getAttribute('rows');
		var cols = this.getAttribute('cols');
		var autogrow = false;

		const labelStyle = this.getAttribute('label-style');
		const overrideStyle = this.getAttribute('style');

		this.wrapper = document.createElement('div');
		this.wrapper.setAttribute('class','editableContainer');
		if (overrideStyle) {
			this.wrapper.setAttribute('style',overrideStyle);
		}

		this.label = document.createElement('label');
		this.label.setAttribute('for', fieldName);
		this.label.textContent = fieldLabel;
		if (labelStyle) {
			this.label.setAttribute('style',labelStyle);
		}

		const icon = document.createElement('img');
		icon.src = '/images/pencil-gray-16.png';

		this.display = document.createElement('div');
		this.display.setAttribute('class','displayField');
		//this.display.setAttribute('onclick','toggleEditable(event);');
		const root = this;
		this.display.onclick = function() { root.toggleEditable(root) };

		this.edit = document.createElement('textarea');
		this.edit.setAttribute('class','editableField');
		this.edit.setAttribute('name',fieldName);
		if (rows == undefined) {
			rows = 4;
		} else if (rows == -1) {
			rows = 6;
			autogrow = true;
		}
		if (cols == undefined) {
			cols = 40;
		}
		this.edit.setAttribute('rows', rows);
		if (cols != undefined) {
			this.edit.setAttribute('cols', cols);
		}
		if (allowTab) {
			this.edit.onkeydown = function(e) {
				if(e.keyCode === 9) { // tab was pressed
					// get caret position/selection
					var start = this.selectionStart;
					var end = this.selectionEnd;

					var $this = $(this);
					var value = $this.val();

					// set textarea value to: text before caret + tab + text after caret
					$this.val(value.substring(0, start)
								+ "\t"
								+ value.substring(end));

					// put caret at right position again (add one for the tab)
					this.selectionStart = this.selectionEnd = start + 1;

					// prevent the focus lose
					e.preventDefault();
				}
			};
		}

		//this.edit.setAttribute('onfocusout','toggleEditable(event);');
		this.edit.onfocusout = function() { root.toggleEditable(root) };
		this.edit.hidden = true;
		this.edit.onchange = function() { var oldValue = root.value; root.value = this.value; root.display.textContent = this.value; root.changed(oldValue, this.value); };

		const style = document.createElement('style');
		style.textContent = `
		.editableContainer {
			display: grid;
			grid-template-columns: 12rem 41rem 16px;
		}
		.editableContainerLabelless {
			display: grid;
			grid-template-columns: 0px 41rem 16px;
		}
		.editableContainer input {
			background-color: var(--bright);
			color: var(--dim);
		}
		.editableField {
			background-color: var(--bright);
			color: var(--dim);
			font-family: Ubuntu;
			font-size: 16px;
		}
		.displayField {
			background-color: var(--dim);
			color: var(--bright);
			padding: 0.25rem;
			padding-bottom: 0.5rem;
			overflow: scroll;
			white-space: pre-wrap;
			max-height: 6rem;
		}
		.replaceableToken {
			display: inline-block;
			background-color: rgba(200,255,200,0.3);
			border-radius: 0.75rem;
		}`;

		shadow.appendChild(this.wrapper);
		this.wrapper.appendChild(style);
		this.wrapper.appendChild(this.label);
		this.wrapper.appendChild(this.display);
		this.wrapper.appendChild(this.edit);
		this.wrapper.appendChild(icon);

		this.editing = false;
		this.value = '';
		if (this.displayReplacements == undefined) {
			this.displayReplacements = [];
		} else {
			console.log('Had display replacements before textarea construct?');
		}
	}

	connectedCallback() {
		const fieldName = this.getAttribute('field-name');
		const fieldLabel = this.getAttribute('field-label');
		const allowTab = this.hasAttribute('allow-tab');
		const startEditable = this.hasAttribute('start-editable');
		this.allowTab = allowTab;
		const containerStyle = this.getAttribute('style');
		var rows = this.getAttribute('rows');
		var cols = this.getAttribute('cols');
		var autogrow = false;
		if (containerStyle) {
			this.wrapper.setAttribute('style', containerStyle);
		}
		if (fieldLabel == '') {
			this.wrapper.setAttribute('class','editableContainerLabelless');
		}
		this.label.setAttribute('for', fieldName);
		this.edit.setAttribute('name',fieldName);
		this.label.textContent = fieldLabel;
		if (rows == undefined) {
			rows = 4;
		} else if (rows == -1) {
			rows = 6;
			autogrow = true;
		}
		if (cols == undefined) {
			cols = 40;
		}
		this.edit.setAttribute('rows', rows);
		if (!autogrow) {
			this.edit.setAttribute('cols', cols);
			this.display.style.width = cols + 'rem';
			this.edit.style.width = cols + 'rem';
			this.display.style.height = rows + 'rem';
		}
		if (allowTab) {
			this.edit.onkeydown = function(e) {
				if(e.keyCode === 9) { // tab was pressed
					// get caret position/selection
					var start = this.selectionStart;
					var end = this.selectionEnd;

					var $this = $(this);
					var value = $this.val();

					// set textarea value to: text before caret + tab + text after caret
					$this.val(value.substring(0, start)
								+ "\t"
								+ value.substring(end));

					// put caret at right position again (add one for the tab)
					this.selectionStart = this.selectionEnd = start + 1;

					// prevent the focus lose
					e.preventDefault();
				}
			};
		}

		this.displayReplacements = [];
		for (var c=0; c<this.children.length; c++) {
			var child = this.children[c];
			if (child.tagName == 'REPLACE') {
				var replacement = {};
				replacement.search = new RegExp(child.getAttribute('search'), 'g');
				replacement.replace = child.getAttribute('replace');
				this.displayReplacements.push(replacement);
			} else if (child.tagName == 'TEXT') {
				this.display.textContent = child.textContent;
				this.display.textContent = child.textContent;
				this.value = child.textContent;
				this.edit.textContent = child.textContent;
			}
		}
		if (this.display.textContent == '' && this.textContent != '') {
			this.display.textContent = this.textContent;
			this.value = this.textContent;
			this.edit.textContent = this.textContent;
		}
		this.edit.value = this.value;
		this.displayText(this.value);
		if (startEditable) {
			this.toggleEditable();
		}
	}

	displayText(text) {
		var replaced = false;
		for (var i=0; i<this.displayReplacements.length; i++) {
			var snr = this.displayReplacements[i];
			var newText = text.replaceAll(snr.search, snr.replace);
			if (newText != text) {
				replaced = true;
				text = newText;
			}
		}
		if (replaced) {
			this.display.innerHTML = text;
		} else {
			this.display.textContent = text;
		}
	}

	setLabel(newLabel) {
		this.label.textContent = newLabel;
	}

	toggleEditable(root) {
		if (this.editing) {
			this.displayText(this.edit.value);
			this.value = this.edit.value;
			this.edit.hidden = true;
			this.display.hidden = false;
		} else {
			this.display.hidden = true;
			this.edit.hidden = false;
			this.edit.focus();
		}
		this.editing = !this.editing;
	}

	setValue(newValue, fireCallback, replacements) {
		var oldValue = this.value;
		this.displayText(newValue);
		this.edit.textContent = newValue;
		this.edit.value = newValue;
		this.value = newValue;
		for (var c=0; c<this.children.length; c++) {
			var child = this.children[c];
			if (child.tagName == 'TEXT') {
				child.textContent = newValue;
				break;
			}
		}
		if (fireCallback) {
			this.changed(oldValue, newValue);
		}
	}

	changed(oldValue, newValue) {
		if (this.changeCallback) {
			this.changeCallback(oldValue, newValue);
		}
	}
}

class DisplayEditSelect extends HTMLElement {

	constructor() {
		super();
		const shadow = this.attachShadow({mode: 'open'});

		const fieldName = this.getAttribute('field-name');
		const fieldLabel = this.getAttribute('field-label');
		const fieldValue = this.getAttribute('field-value');

		const labelStyle = this.getAttribute('label-style');
		const overrideStyle = this.getAttribute('style');

		this.name = fieldName;

		this.wrapper = document.createElement('div');
		this.wrapper.setAttribute('class','editableContainer');
		if (overrideStyle) {
			this.wrapper.setAttribute('style',overrideStyle);
		}

		this.label = document.createElement('label');
		this.label.setAttribute('for', fieldName);
		this.label.textContent = fieldLabel;
		if (labelStyle) {
			this.label.setAttribute('style', labelStyle);
		}

		const icon = document.createElement('img');
		icon.src = '/images/pencil-gray-16.png';

		this.display = document.createElement('div');
		this.display.setAttribute('class','displayField');
		this.display.textContent = fieldValue;
		const root = this;
		this.display.onclick = function() { root.toggleEditable(root) };

		this.edit = document.createElement('select');
		this.edit.setAttribute('class','editableField');
		this.edit.setAttribute('name',fieldName);

		this.edit.onfocusout = function() { root.toggleEditable(root) };
		this.edit.hidden = true;
		this.edit.onchange = function() { var oldValue = root.value; root.selectedOption = this.selectedOptions[0]; root.value = this.value; root.changed(oldValue, this.value); };

		const style = document.createElement('style');
		style.textContent = `
		.editableContainer {
			display: grid;
			grid-template-columns: 12em auto 16px;
		}
		.editableContainer input {
			background-color: var(--bright);
			color: var(--dim);
		}
		.editableField {
			background-color: var(--bright);
			color: var(--dim);
		}
		.displayField {
			background-color: var(--dim);
			color: var(--bright);
			padding: 0.1rem;
			min-width: 1.5rem;
		}`;

		shadow.appendChild(this.wrapper);
		this.wrapper.appendChild(style);
		this.wrapper.appendChild(this.label);
		this.wrapper.appendChild(this.display);
		this.wrapper.appendChild(this.edit);
		this.wrapper.appendChild(icon);

		this.editing = false;
		this.selectedOption = null;

		this.changeCallback = null;
	}

	connectedCallback() {
		const fieldName = this.getAttribute('field-name');
		const fieldLabel = this.getAttribute('field-label');
		const fieldValue = this.getAttribute('field-value');
		const labelStyle = this.getAttribute('label-style');
		const overrideStyle = this.getAttribute('style');
		if (overrideStyle && overrideStyle != 'null') {
			this.wrapper.setAttribute('style', overrideStyle);
		}
		if (labelStyle) {
			this.label.setAttribute('style',labelStyle);
		}
		this.label.setAttribute('for', fieldName);
		this.label.textContent = fieldLabel;
		this.display.textContent = fieldValue;
		this.edit.setAttribute('name',fieldName);
		this.name = fieldName;
		this.value = fieldValue;

		var options = this.children;

		while (this.edit.children.length > 0) {
			this.edit.removeChild(this.edit.children[0]);
		}

		while (options.length > 0) {
			var option = options[0];
			if (option.tagName == 'OPTION') {
				if (option.value == fieldValue) {
					option.selected = true;
					this.selectedOption = option;
				}
				this.edit.appendChild(option);
			} else {
				options.removeChild(option);
			}
		}
		if (this.selectedOption) {
			this.display.textContent = this.selectedOption.textContent;
		}
	}

	toggleEditable(root) {
		if (this.editing) {
			if (this.selectedOption) {
				this.display.textContent = this.selectedOption.textContent;
			} else {
				this.display.textContent = this.edit.value;
			}
			this.value = this.edit.value;
			this.edit.hidden = true;
			this.display.hidden = false;
		} else {
			this.display.hidden = true;
			this.edit.hidden = false;
			this.edit.focus();
		}
		this.editing = !this.editing;
	}

	setValue(newValue, fireCallback) {
		var oldValue = this.value;
		var found = false;
		for (var i=0; i<this.edit.children.length; i++) {
			var option = this.edit.children[i];
			if (option.value == newValue) {
				option.selected = true;
				this.selectedOption = option;
				found = true;
			} else {
				option.selected = false;
			}
		}
		if (found) {
			//console.log('DES setValue on '+this.name+' ('+newValue+') '+this.selectedOption.textContent);
			this.display.textContent = this.selectedOption.textContent;
		} else {
			//console.log('DES setValue on '+this.name+' val '+newValue);
			this.display.textContent = newValue;
		}
		this.edit.value = newValue;
		this.value = newValue;
		this.setAttribute('field-value',newValue);
		if (fireCallback) {
			this.changed(oldValue, newValue);
		}
	}

	setOptions(optionMap) {
		while (this.edit.children.length > 0) {
			this.edit.removeChild(this.edit.children[0]);
		}

		var hasValue = false;
		for (var val in optionMap) {
			var label = optionMap[val];
			var option = document.createElement('option');
			option.value = val;
			option.textContent = label;
			if (val == this.value) {
				//console.log('DES setOptions found '+this.value+' on '+this.name+' with '+option.textContent);
				option.selected = true;
				hasValue = true;
				this.selectedOption = option;
			}
			this.edit.appendChild(option);
		}
		if (!hasValue) {
			//console.log('DES setOptions on '+this.name+' cannot find '+this.value);
			this.value = null;
			this.display.textContent = '';
		} else {
			//console.log('DES setOptions on '+this.name+' '+this.selectedOption.textContent);
			this.display.textContent = this.selectedOption.textContent;
		}
	}

	changed(oldValue, newValue) {
		if (this.changeCallback) {
			this.changeCallback(oldValue, newValue);
		}
	}
}

class DisplayEditList extends HTMLElement {

	constructor() {
		super();
		const shadow = this.attachShadow({mode: 'open'});

		this.listName = this.getAttribute('list-name');
		this.listLabel = this.getAttribute('list-label');

		this.nameField = this.getAttribute('list-name-field') ?? 'name';
		this.valueField = this.getAttribute('list-value-field') ?? 'value';

		this.wrapper = document.createElement('div');
		this.wrapper.textContent = this.listLabel;

		this.listEl = document.createElement('ul');
		this.listEl.setAttribute('class','editableList');

		this.sourceList = null;

		const style = document.createElement('style');
		style.textContent = `
		.editableList {

		}
		.editableList input {
			background-color: var(--bright);
			color: var(--dim);
		}
		.editableField {
			background-color: var(--bright);
			color: var(--dim);
		}
		.displayField {
			background-color: var(--dim);
			color: var(--bright);
			padding: 0.1rem;
		}`;

		shadow.appendChild(this.wrapper);
		this.wrapper.appendChild(this.listEl);
		shadow.appendChild(style);

		this.editing = [];
		this.edit = [];
		this.display = [];
		this.values = [];
	}

	connectedCallback() {
		this.sourceList = window[this.listName];

		for (var i=0; i<this.listEl.children.length; i++) {
			this.listEl.removeChild(this.listEl.children[i]);
		}

		this.editing = [];
		this.edit = [];
		this.display = [];
		this.values = [];

		for (var i=0; i<this.sourceList.length; i++) {
			var item = this.sourceList[i];
			var listItem = document.createElement('li');
			var itemText = item[this.nameField];
			var listEditItem = document.createElement('input');
			listEditItem.setAttribute('type','text');
			listEditItem.setAttribute('class','editableField');
			listEditItem.setAttribute('name',this.listName + i);
			listEditItem.setAttribute('value',item[this.valueField]);
			listEditItem.onfocusout = function() { root.toggleEditable(root, i); };
			listEditItem.hidden = true;
			var listDisplayItem = document.createElement('div');
			listDisplayItem.setAttribute('class','displayField');
			listDisplayItem.textContent = itemText;
			listDisplayItem.onclick = function() { root.toggleEditable(root, i); };

			const icon = document.createElement('img');
			icon.src = '/images/pencil-gray-16.png';

			listItem.appendChild(listDisplayItem);
			listItem.appendChild(listEditItem);
			listItem.appendChild(icon);
			this.listEl.appendChild(listItem);

			this.edit[i] = listEditItem;
			this.display[i] = listDisplayItem;
			this.editing[i] = false;
			this.values[i] = item[this.valueField];
		}
	}

	setSource(sourceList, nameField = 'name', valField = 'value') {
		this.sourceList = sourceList;
		this.nameField = nameField;
		this.valField = valField;

		this.connectedCallback();
	}

	toggleEditable(root, i) {
		if (this.editing[i]) {
			this.display[i].textContent = this.edit[i].value;
			this.edit[i].hidden = true;
			this.display[i].hidden = false;
		} else {
			this.display[i].hidden = true;
			this.edit[i].hidden = false;
			this.edit[i].focus();
		}
		this.editing[i] = !this.editing[i];
	}
}

class DisplayEditConditionSet extends HTMLElement {

	constructor() {
		super();
		const shadow = this.attachShadow({mode: 'open'});

		this.outerWrapper = document.createElement('div');

		this.header = document.createElement('div');
		this.outerWrapper.appendChild(this.header);

		this.headerText = document.createElement('span');
		this.header.appendChild(this.headerText);

		this.wrapper = document.createElement('div');
		this.wrapper.textContent = this.listLabel;
		this.wrapper.setAttribute('class','conditionSetBody');
		this.outerWrapper.appendChild(this.wrapper);

		const style = document.createElement('style');
		style.textContent = `
		div {
			display: inline-block;
		}
		.conditionSetBody {
			margin-left: 1rem;
		}
		.conditionSetExpression {
			display: grid;
			grid-template-columns: 2rem auto;
		}
		.editableList input {
			background-color: var(--bright);
			color: var(--dim);
		}
		.editableField {
			background-color: var(--bright);
			color: var(--dim);
			margin-left: 0.2rem;
		}
		.displayField {
			background-color: var(--dim);
			color: var(--bright);
			padding: 0.1rem;
			margin-left: 0.2rem;
		}
		.conditionChild {
		}
		.conditionChild.paren::before {
			content: "(";
		}
		.conditionChild.paren::after {
			content: ")";
		}`;

		this.hasOps = ['has','and','or'];

		this.operations = ['has', 'not', 'and', 'or', '==','!=','<','>','<=','>=','=','%','*','+','-','/', 'var', 'lit', 'invalid']; //,'*=','+=','-=','/=','<?=','>?='
		this.operationChoices = {
			'has': 'has',
			'not': 'not',
			'and': 'and',
			'or': 'or',
			'==': '==',
			'!=': '!=',
			'<': '<',
			'>': '>',
			'<=': '<=',
			'>=': '>=',
			'=': '=',
			'%': '%',
			'*': '*',
			'+': '+',
			'-': '-',
			'/': '/',
			'var': 'var',
			'lit': 'literal',
			'invalid': 'never'
		};

		shadow.appendChild(this.outerWrapper);
		shadow.appendChild(style);

		this.initialized = false;
	}
	/*
		enum class ExpressionOp {
		INVALID, ///< Expression is invalid.

		// Direct access operators
		VAR, ///< Direct access to condition variable, no other operations.
		LIT, ///< Direct access to literal, no other operations).

		// Arithmetic operators
		ADD, ///< Adds ( + ) the values from all sub-expressions.
		SUB, ///< Subtracts ( - ) all later sub-expressions from the first one.
		MUL, ///< Multiplies ( * ) all sub-expressions with each-other.
		DIV, ///< (Integer) Divides ( / ) the first sub-expression by all later ones.
		MOD, ///< Modulo ( % ) by the second and later sub-expressions on the first one.

		// Boolean equality operators, return 0 or 1
		EQ, ///< Tests for equality ( == ).
		NE, ///< Tests for not equal to ( != ).
		LE, ///< Tests for less than or equal to ( <= ).
		GE, ///< Tests for greater than or equal to ( >= ).
		LT, ///< Tests for less than ( < ).
		GT, ///< Tests for greater than ( > ).

		// Boolean combination operators, return 0 or 1
		AND, ///< Boolean 'and' operator; returns 0 on first 0 subcondition, value of first sub-condition otherwise.
		OR, ///< Boolean 'or' operator; returns value of first non-zero sub-condition, or zero if all are zero.

		// Single boolean operators
		NOT, ///< Single boolean 'not' operator.
		HAS ///< Single boolean 'has' operator.
	};
	*/

	/* 
	 * Proposed visual structure:
	 * - For prefix operators, present on a single line, with the operator first and child(ren) after
	 * - For infix math & test operators, present on a single line, with the operator in between the children
	 * - For infix boolean operators, present the operator on its own line, with children as separate lines below
	 * 
	 * This should reasonably well mimic the layout in the data files, and feel familiar.
	 */

	connectedCallback() {
		if (this.initialized) {
			return;
		}
		for (var i=0; i<this.wrapper.children.length; i++) {
			this.wrapper.removeChild(this.wrapper.children[i]);
		}

		this.setId = this.getAttribute('setId');
		this.label = this.getAttribute('label');

		this.childSets = [];
		this.needsParen = false;
		for (var i=0; i<this.children.length; i++) {
			this.readChild(this.children[i], this);
		}
		this.wrapper.appendChild(this.element);

		if (this.childSets.length == 0) {
			this.headerText.textContent = 'always';
		} else if (this.op == 'or') {
			this.headerText.textContent = 'If any of the following are true: ';
		} else {
			this.headerText.textContent = 'If all of the following are true: ';
		}

		// var addChildButton = document.createElement('button');
		// addChildButton.setAttribute('type','button');
		// addChildButton.onclick = function() { root.addChild(); };
		// addChildButton.textContent = 'add child';
		// this.header.appendChild(addChildButton);

		// var andButton = document.createElement('button');
		// andButton.setAttribute('type','button');
		// andButton.onclick = function() { root.addAndChild(); }
		// andButton.textContent = '+ and';
		// this.header.appendChild(andButton);

		// const root = this;

		// // Ugh this is a mess; I probably need to rebuild it from the ground up based on the new structure -_-
		// if (this.left != null && this.op != null && this.right != null) {
		// 	var editDelete = document.createElement('button');
		// 	editDelete.setAttribute('type','button');
		// 	editDelete.onclick = function() { root.deleteExpression(i); };
		// 	editDelete.textContent = '❌';

		// 	expression.wrapper = document.createElement('div');
		// 	expression.wrapper.setAttribute('class','conditionSetExpression');
		// 	expression.wrapper.appendChild(editDelete);

		// 	expression.display = document.createElement('div');
		// 	expression.display.setAttribute('class','displayField');
		// 	if ((op == 'and' || op == 'or') && right == '0' && left == '') {
		// 		expression.display.textContent = op;
		// 	} else if (op == 'has' && right == '0') {
		// 		expression.display.textContent = op + ' ' + left;
		// 	} else if (op == 'set') {
		// 		expression.display.textContent = op + ' ' + left + ' = ' + right;
		// 	} else if (op == 'not' && right == '0') {
		// 		expression.display.textContent = op + ' ' + left;
		// 	} else {
		// 		expression.display.textContent = left + ' ' + op + ' ' + right;
		// 	}
		// 	expression.display.onclick = function() { root.toggleEditable(root, 'expression', i); };
		// 	expression.wrapper.appendChild(expression.display);
		// 	expression.edit = document.createElement('div');
		// 	expression.edit.setAttribute('class','editableField');
		// 	expression.wrapper.appendChild(expression.edit);
		// 	var editLeft = document.createElement('input');
		// 	editLeft.setAttribute('type','text');
		// 	editLeft.setAttribute('name','set'+this.setId+'_exp'+i+'_left');
		// 	editLeft.setAttribute('value',left);
		// 	editLeft.onchange = function() { expression.left = editLeft.value; }
		// 	expression.edit.appendChild(editLeft);
		// 	var editOp = document.createElement('select');
		// 	editOp.setAttribute('name','set'+this.setId+'_exp'+i+'_op');
		// 	for (var j=0; j<this.operations.length; j++) {
		// 		var option = document.createElement('option');
		// 		var operation = this.operations[j];
		// 		option.value = operation;
		// 		option.textContent = operation;
		// 		if (operation == op) {
		// 			option.selected = true;
		// 		}
		// 		editOp.appendChild(option);
		// 	}
		// 	editOp.onchange = function() { expression.op = editOp.value; }
		// 	expression.edit.appendChild(editOp);
		// 	var editRight = document.createElement('input');
		// 	editRight.setAttribute('type','text');
		// 	editRight.setAttribute('name','set'+this.setId+'_exp'+i+'_right');
		// 	editRight.setAttribute('value',right);
		// 	editRight.onchange = function() { expression.right = editRight.value; }
		// 	expression.edit.appendChild(editRight);
		// 	var editConfirm = document.createElement('button');
		// 	editConfirm.setAttribute('type','button');
		// 	editConfirm.onclick = function() { root.toggleEditable(root, 'expression', i); };
		// 	editConfirm.textContent = '✅';
		// 	expression.edit.appendChild(editConfirm);

		// 	expression.edit.hidden = true;
		// 	expression.editing = false;

		// 	this.wrapper.appendChild(expression.wrapper);

		// 	this.expressions.push(expression);
		// }

		// for (var i=0; i<childSetElements.length; i++) {
		// 	var element = childSetElements[i];
		// 	element.hidden = false;
		// 	element.editing = false;

		// 	this.wrapper.append(element);

		// 	this.childSets.push(element);
		// }

		this.initialized = true;
	}

	readChild(childNode, set, parent) {
		set.type = '';
		if (childNode.tagName == 'OP') {
			set.op = childNode.getAttribute('value');
			set.type = 'op';
		} else if (childNode.tagName == 'VAR-NAME') {
			set.varName = childNode.getAttribute('value');
			set.op = 'var';
			if (this.hasOps.includes(parent.op)) {
				set.op = 'has';
			}
			set.type = 'varName';
		} else if (childNode.tagName == 'LITERAL') {
			set.literal = childNode.getAttribute('value');
			set.op = 'lit';
			set.type = 'literal';
		}

		set.element = document.createElement('div');
		set.element.classList.add('conditionChild');
		set.element.classList.add(set.type + 'ConditionChild');
		if (set.needsParen) {
			set.element.classList.add('paren');
		}
		if (set.type == 'op') {
			set.opField = document.createElement('select');
			set.opChoices = {};
			for (var o of Object.keys(this.operationChoices)) {
				var opChoice = document.createElement('option');
				opChoice.setAttribute('value', o);
				opChoice.textContent = this.operationChoices[o];
				if (set.op == o) {
					opChoice.setAttribute('selected', true);
				}
				set.opChoices[o] = opChoice;
				set.opField.appendChild(opChoice);
			}
		} else {
			set.field = new DisplayEditText();
			set.field.setAttribute('field-name', set.id + '_' + set.type);
			set.field.setAttribute('field-label', '');
			set.field.setAttribute('field-value', set[set.type]);
			set.element.appendChild(set.field);
		}
		set.childSets = [];
		for (var i in childNode.children) {
			if (Number.isNaN(Number.parseInt(i))) {
				continue;
			}
			var childSet = new ChildSet();
			childSet.id = set.id + '_' + i;
			childSet.needsParen = (childNode.children[i].children.length > 0);
			this.readChild(childNode.children[i], childSet, set);
			set.childSets.push(childSet);
		}

		if (set.childSets.length > 0) {
			switch (set.op) {
				case 'lit':
					case 'var':
					case 'has':
					case 'not':
					set.element.appendChild(set.opField);
					set.element.appendChild(set.childSets[0].element);
					break;
				case '+':
				case '-':
				case '*':
				case '/':
				case '%':
					set.element.appendChild(set.childSets[0].element);
					set.element.appendChild(set.opField);
					set.element.appendChild(set.childSets[1].element);
					for (var i=2; i<set.childSets.length; i++) {
						var opCopy = set.getOpCopy();
						set.element.appendChild(opCopy);
						set.element.appendChild(set.childSets[i].element);
					}
					break;
				case '==':
				case '!=':
				case '>':
				case '<':
				case '>=':
				case '<=':
					set.element.appendChild(set.childSets[0].element);
					set.element.appendChild(set.opField);
					set.element.appendChild(set.childSets[1].element);
					break;
				default:
				case 'invalid':
				case 'and':
				case 'or':
					if (set.childSets.length > 1) {
						set.element.appendChild(set.childSets[0].element);
						set.element.appendChild(this.opField);
						set.element.appendChild(set.childSets[1].element);
					} else {
						set.element.appendChild(this.opField);
						if (set.childSets.length > 0) {
							set.element.appendChild(set.childSets[0].element);
						}
					}
					break;
			}
		}
	}

	toggleEditable(root, type, i) {
		if (type == 'expression') {
			if (this.expressions[i].editing) {
				this.expressions[i].display.textContent = this.expressions[i].edit.children[0].value + ' ' + this.expressions[i].edit.children[1].value + ' ' + this.expressions[i].edit.children[2].value;
				this.expressions[i].edit.hidden = true;
				this.expressions[i].display.hidden = false;
			} else {
				this.expressions[i].edit.hidden = false;
				this.expressions[i].display.hidden = true;
				this.expressions[i].edit.focus();
			}
			this.expressions[i].editing = !this.expressions[i].editing;
		}
	}

	addExpression() {
		var expressionCount = this.expressions.length;
		var editDelete = document.createElement('button');
		editDelete.setAttribute('type','button');
		editDelete.onclick = function() { root.deleteExpression(expressionCount); };
		editDelete.textContent = '❌';

		var expression = {left: '', op: '==', right: ''};

		const root = this;

		expression.wrapper = document.createElement('div');
		expression.wrapper.setAttribute('class','conditionSetExpression');
		expression.wrapper.appendChild(editDelete);

		expression.display = document.createElement('div');
		expression.display.setAttribute('class','displayField');
		expression.display.onclick = function() { root.toggleEditable(root, 'expression', expressionCount); };
		expression.display.hidden = true;
		expression.wrapper.appendChild(expression.display);
		expression.edit = document.createElement('div');
		expression.edit.setAttribute('class','editableField');
		expression.wrapper.appendChild(expression.edit);
		var editLeft = document.createElement('input');
		editLeft.setAttribute('type','text');
		editLeft.setAttribute('name','set'+this.setId+'_exp'+expressionCount+'_left');
		editLeft.setAttribute('value','');
		editLeft.onchange = function() { expression.left = editLeft.value; }
		expression.edit.appendChild(editLeft);
		var editOp = document.createElement('select');
		editOp.setAttribute('name','set'+this.setId+'_exp'+expressionCount+'_op');
		for (var i=0; i<this.operations.length; i++) {
			var option = document.createElement('option');
			var operation = this.operations[i];
			option.value = operation;
			option.textContent = operation;
			editOp.appendChild(option);
		}
		editOp.onchange = function() { expression.op = editOp.value; }
		expression.edit.appendChild(editOp);
		var editRight = document.createElement('input');
		editRight.setAttribute('type','text');
		editRight.setAttribute('name','set'+this.setId+'_exp'+expressionCount+'_right');
		editRight.setAttribute('value','');
		editRight.onchange = function() { expression.right = editRight.value; }
		expression.edit.appendChild(editRight);
		var editConfirm = document.createElement('button');
		editConfirm.setAttribute('type','button');
		editConfirm.onclick = function() { root.toggleEditable(root, 'expression', expressionCount); };
		editConfirm.textContent = '✅';
		expression.edit.appendChild(editConfirm);

		expression.edit.hidden = false;
		expression.editing = true;

		this.wrapper.appendChild(expression.wrapper);
		this.expressions.push(expression);
		expression.edit.children[0].focus();
		this.updateHeader();
	}

	deleteExpression(expIndex) {
		var expression = this.expressions[expIndex];
		this.wrapper.removeChild(expression.wrapper);
		this.expressions.splice(expIndex, 1);
		this.renumberExpressions();
		this.updateHeader();
	}

	renumberExpressions() {
		const root = this;

		for (var i=0; i<this.expressions.length; i++) {
			var expression = this.expressions[i];
			expression.wrapper.children[0].onclick = function() { root.deleteExpression(i); };
			expression.wrapper.children[1].onclick = function() { root.toggleEditable(root, 'expression', i); };
			expression.wrapper.children[2].children[0].setAttribute('name','set'+this.setId+'_exp'+i+'_left');
			expression.wrapper.children[2].children[1].setAttribute('name','set'+this.setId+'_exp'+i+'_op');
			expression.wrapper.children[2].children[2].setAttribute('name','set'+this.setId+'_exp'+i+'_right');
			expression.wrapper.children[2].children[3].onclick = function() { root.toggleEditable(root, 'expression', i); };
		}
	}

	addChild() {
		var childIndex = this.children.length;
		var child = document.createElement('display-edit-condition-set');
		child.setAttribute('type','child');
		child.setAttribute('setId',this.setId + '_child' + childIndex);
		child.setAttribute('label',this.setId + '_child' + childIndex);
		this.wrapper.appendChild(child);
	}

	deleteChild(childIndex) {
		var child = this.children[childIndex];
		this.wrapper.removeChild(child);
		this.childern.splice(childIndex, 1);
		this.renumberChildren();
		this.updateHeader();
	}

	renumberChildren() {
		const root = this;

		for (var i=0; i<this.children.length; i++) {
			var child = this.children[i];
			child.setAttribute('setId',this.setId + '_child' + i);
			child.setAttribute('label',this.setId + '_child' + i);
			child.renumberExpressions();
			child.renumberChildren();
		}
		this.updateHeader();
	}

	toggleAndOr() {
		this.isOr = !this.isOr;
		this.updateHeader();
	}

	updateHeader() {
		if (this.expressions.length == 0 && this.children.length == 0) {
			this.headerText.textContent = 'always ';
		} else if (this.isOr) {
			this.headerText.textContent = 'If any of the following are true: ';
		} else {
			this.headerText.textContent = 'If all of the following are true: ';
		}
	}

	getData() {
		var data = {};

		data['isOr'] = this.isOr;

		data['labels'] = this.labels;

		data['expressions'] = [];
		for (var e in this.expressions) {
			var expression = this.expressions[e];
			var expressionData = {};
			expressionData['left'] = expression.left;
			expressionData['op'] = expression.op;
			expressionData['right'] = expression.right;

			data['expressions'].push(expressionData);
		}

		data['children'] = [];
		for (var c=0; c<this.childSets.length; c++) {
			data['children'].push(this.childSets[c].getData());
		}

		return data;
	}
}
class ChildSet {
	constructor() {
		this.varName = null;
		this.op = null;
		this.literal = null;

		this.element = null;
		this.opCopies = [];
	}

	getOpCopy() {
		var opCopy = document.createElement('span');
		opCopy.textContent = this.op;
		this.opCopies.push(opCopy);

		return opCopy;
	}

	setOp(newOp) {
		this.op = newOp;
		for (var opCopy of this.opCopies) {
			opCopy.textContent = newOp;
		}
	}
}
class DisplayEditConversation extends HTMLElement {

	constructor() {
		super();
		const shadow = this.attachShadow({mode: 'open'});

		const name = this.getAttribute('name');
		this.name = name;

		this.wrapper = document.createElement('div');
		this.wrapper.setAttribute('class','conversationContainer');

		if (this.hasAttribute('conversation-callback')) {
			this.callback = this.getAttribute('conversation-callback');
		} else {
			this.callback = null;
		}

		const style = document.createElement('style');
		style.textContent = `
		.conversationContainer {

		}
		.conversationHeader {

		}
		.conversationText {
			font-family: monospace;
		}
		.conversationNode {
			display: grid;
			grid-template-columns: 16rem auto;
			border: 3px var(--medium) ridge;
		}
		.conversationNodeLabel {
			grid-row-start: span 2;
			position: relative;
		}
		.conversationNodeContents {
			position: relative;
		}
		.conversationNodeConditions {
			display: grid;
			grid-template-columns: 12em auto;
		}
		.conversationElementText {
			background-color: var(--dimmer);
		}
		.conversationNodeElement {
			border: 3px var(--medium) groove;
			position: relative;
		}
		.conversationElementConditions {
			border: 2px var(--dark) groove;
		}
		.conversationElementNext {
			border: 2px var(--dark) groove;
		}
		.displayField {
			background-color: var(--dim);
			color: var(--bright);
			padding: 0.1rem;
		}
		.addNodeButton, .addElementButton {
			background-color: green;
			border-radius: 1rem;
			z-index: 3;
		}
		.conversationNodeLabel .addNodeButton, .conversationNodeElements .addElementButton {
			position: absolute;
			left: -0.75rem;
			bottom: -0.75rem;
		}
		.delNodeButton, .delElementButton {
			background-color: red;
			position: absolute;
			left: -0.75rem;
			top: calc(50% - 0.5rem);
			border-radius: 1rem;
			z-index: 3;
		}
		.replaceableToken {
			display: inline-block;
			background-color: rgba(200,255,200,0.3);
			border-radius: 0.75rem;
		}
		`;

		this.tokenReplacement = [{token: 'commodity', replacement: '<span class="replaceableToken">&lt;commodity&gt;</span>'},
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

		shadow.appendChild(this.wrapper);
		shadow.appendChild(style);

		this.editing = false;
		this.nodes = [];
		this.labels = {};

		this.initialized = false;
	}

	connectedCallback() {
		if (this.initialized) {
			return;
		}
		this.nodeCount = 0;
		for (var i=0; i<this.children.length; i++) {
			var child = this.children[i];
			if (child.tagName == 'NODE') {
				this.nodeCount++;
			} else if (child.tagName == 'LABEL') {
				var labelName = child.getAttribute('name');
				var labelValue = child.getAttribute('value');
				this.labels[labelValue] = labelName;
			}
		}

		this.nextOptions = {};
		for (var i=0; i<this.nodeCount; i++) {
			if (this.labels[i]) {
				this.nextOptions[i] = this.labels[i];
			} else {
				this.nextOptions[i] = 'node #'+i;
			}
		}
		this.nextOptions[i] = 'node #'+i;
		this.nextOptions[-1] = '(end)';

		var nodeIndex = 0;

		this.firstAddNodeButton = this.createAddNodeButton(null);
		this.wrapper.appendChild(this.firstAddNodeButton);

		for (var i=0; i<this.children.length; i++) {
			var child = this.children[i];
			if (child.tagName == 'NODE') {
				var nodeLabel = child.getAttribute('label');
				var nodeType = child.getAttribute('type');
				var nodeScene = child.getAttribute('scene');

				var node = this.createNode();
				if (nodeLabel) {
					node.labelName.setValue(nodeLabel);
				}
				node.setIndex(nodeIndex++);
				node.setType(nodeType);
				if (nodeScene) {
					node.setScene(nodeScene);
				}
				this.nodes.push(node);

				for (var j=0; j<child.children.length; j++) {
					var grand = child.children[j];
					if (grand.tagName == 'DISPLAY-EDIT-CONDITION-SET') {
						node.setDisplayConditions(grand);
						j--;
					} else if (grand.tagName == 'ELEMENT') {
						var element = this.createElement(node);

						var elementNextLabel = grand.getAttribute('next-label');
						if (elementNextLabel) {
							element.setNext(elementNextLabel);
						} else {
							var elementNextIndex = grand.getAttribute('next-index');
							if (!grand.hasAttribute('next-index')) {
								elementNextIndex = nodeIndex+1;
							}
							element.setNext(elementNextIndex);
						}

						for (var k=0; k<grand.children.length; k++) {
							var ggrand = grand.children[k];
							if (ggrand.tagName == 'DISPLAY-EDIT-CONDITION-SET') {
								element.setDisplayConditions(ggrand);
								k--;
							} else {
								var text = ggrand.textContent;
								element.setText(text);
							}
						}

						node.elements.push(element);
						node.elementsContainer.appendChild(element.container);
					}
				}

				this.wrapper.appendChild(node.container);
			}
		}

		this.initialized = true;
		if (this.callback) {
			window[this.callback](this);
		}
	}

	createNode(index) {
		const root = this;

		var nodeIndex = index;
		if (index == undefined) {
			nodeIndex = this.nodes.length;
		}
		var node = {};

		node.conversation = this;
		node.name = this.name + '_' + nodeIndex;
		node.index = nodeIndex;
		node.label = null;
		node.type = 'text';
		if (this.labels[nodeIndex]) {
			node.label = this.labels[nodeIndex];
		}
		node.isChoice = false;
		// TODO: actions

		node.container = document.createElement('div');
		node.container.setAttribute('class','conversationNode');

		node.labelContainer = document.createElement('div');
		node.labelContainer.setAttribute('class','conversationNodeLabel');
		node.container.appendChild(node.labelContainer);

		node.labelNumber = document.createElement('div');
		node.labelContainer.appendChild(node.labelNumber);

		node.labelName = document.createElement('display-edit-text');
		node.labelName.setAttribute('field-name', node.name + '_label');
		node.labelName.setAttribute('field-placeholder', '(none)');
		node.labelName.setAttribute('field-label', 'Label: ');
		node.labelName.setAttribute('style', 'grid-template-columns: 5em auto 16px;');
		node.labelName.setAttribute('field-value', '');
		node.labelContainer.appendChild(node.labelName);
		node.labelContainer.appendChild(this.createAddNodeButton(node));

		node.labelType = document.createElement('div');
		node.labelContainer.appendChild(node.labelType);

		node.typeSelect = document.createElement('display-edit-select');
		node.typeSelect.setAttribute('field-name', node.name + '_type');
		node.typeSelect.setAttribute('field-label', 'Type: ');
		node.typeSelect.setAttribute('field-value', 'text');
		var validTypes = ['text','choice','branch'];
		for (var t=0; t<validTypes.length; t++) {
			var option = document.createElement('option');
			option.value = validTypes[t];
			option.innerText = validTypes[t];
			node.typeSelect.appendChild(option);
		}
		node.labelType.appendChild(node.typeSelect);
		node.typeSelect.edit.onchange = function (event) { node.setType(event.target.value, false); }

		node.contentsContainer = document.createElement('div');
		node.contentsContainer.setAttribute('class','conversationNodeContents');
		node.container.appendChild(node.contentsContainer);

		node.displayConditionContainer = document.createElement('div');
		node.displayConditionContainer.setAttribute('class','conversationNodeConditions');
		node.contentsContainer.appendChild(node.displayConditionContainer);

		node.displayConditionHeader = document.createElement('div');
		node.displayConditionHeader.style.display = 'inline-block';
		node.displayConditionHeader.textContent = 'Display this node:';
		node.displayConditionContainer.appendChild(node.displayConditionHeader);

		node.displayConditions = document.createElement('display-edit-condition-set');
		node.displayConditionContainer.appendChild(node.displayConditions);

		// TODO: scene should actually be a new type, a sprite picker with preview
		node.scene = document.createElement('display-edit-text');
		node.scene.setAttribute('field-name', node.name + '_scene');
		node.scene.setAttribute('field-placeholder', '(none)');
		node.scene.setAttribute('field-label', 'Scene Image: ');
		node.scene.setAttribute('field-value', '');
		node.contentsContainer.appendChild(node.scene);

		// TODO: the associated action needs to be handled, but that's pretty in-depth; placeholder it for now
		node.action = document.createElement('div');
		node.action.setAttribute('style','color: var(--meddim)');
		node.action.textContent = '(Action still TODO)';
		node.contentsContainer.appendChild(node.action);

		node.elementsContainer = document.createElement('div');
		node.elementsContainer.setAttribute('class','conversationNodeElements');

		node.elements = [];

		// When the label
		node.labelName.changeCallback = function(oldLabel, newLabel) {
			root.labels[node.index] = newLabel;
			node.label = newLabel;
			root.updateLabels();
		};

		node.setIndex = function(newIndex) {
			this.index = newIndex;
			this.name = root.name + '_' + newIndex;
			// TODO: method for displayEditText for setting name
			this.labelName.setAttribute('field-name',this.name + '_label');
			this.scene.setAttribute('field-name',this.name + '_scene');
			this.labelNumber.textContent = 'Node #' + newIndex;
		};
		node.setType = function(type, updateSelect=true) {
			if (type == 'choice') {
				this.isChoice = true;
			} else {
				this.isChoice = false;
			}
			if (updateSelect) {
				this.typeSelect.setValue(type);
			}
			var oldType = this.type;
			this.type = type;

			for (var e=0; e<this.elements.length; e++) {
				var element = this.elements[e];

				var textValue = element.text.value;
				if (oldType == 'branch') {
					textValue = element.text.innerText;
				}
				element.text.remove();
				if (type == 'choice') {
					element.text = document.createElement('display-edit-text');
					element.text.setAttribute('field-name', element.name + '_text');
					element.text.setAttribute('field-label', 'Choice: ');
					element.text.setAttribute('field-value', textValue);
					root.addReplacements(element.text);
					element.contents.appendChild(element.text);
				} else if (type == 'text') {
					element.text = document.createElement('display-edit-textarea');
					element.text.setAttribute('field-name', element.name + '_text');
					element.text.setAttribute('allow-tab','');
					element.text.setAttribute('rows',-1);
					element.text.setAttribute('field-label', '');
					var textText = document.createElement('text');
					textText.textContent = textValue;
					element.text.appendChild(textText);
					root.addReplacements(element.text);
					element.contents.appendChild(element.text);
				} else if (type == 'branch') {
					element.text = document.createElement('div');
					element.text.setAttribute('style','display: none');
					element.text.innerText = textValue;
				}

			}
		};
		node.setDisplayConditions = function(newConditions) {
			this.displayConditionContainer.removeChild(this.displayConditions);
			this.displayConditions = newConditions;
			this.displayConditionContainer.appendChild(newConditions);
		};
		node.setScene = function(scene) {
			this.scene.setValue(scene);
		};
		node.createAddElementButton = function(afterElement) {
			var button = document.createElement('button');
			button.setAttribute('type','button');
			button.setAttribute('class','addElementButton');
			button.onclick = function() { node.addElement(afterElement); };
			button.textContent = '+';

			return button;
		};
		node.addElement = function(afterElement) {
			var element = root.createElement(node, afterElement);
			if (afterElement) {
				var newIndex = afterElement.index + 1;
				element.setIndex(newIndex);
				afterElement.container.after(element.container);
				this.elements.splice(newIndex, 0, element);
			} else {
				element.setIndex(0);
				this.elementsContainer.appendChild(element.container);
				this.elements.splice(0, 0, element);
			}
			this.renumberElements();
		};
		node.delElement = function(element) {
			element.container.remove();
			this.elements.splice(element.index, 1);
			this.renumberElements();
		};
		node.renumberElements = function() {
			for (var i=0; i<this.elements.length; i++) {
				var element = this.elements[i];
				element.setIndex(i);
			}
		};

		node.firstAddElementButton = node.createAddElementButton(null);
		node.contentsContainer.appendChild(node.firstAddElementButton);

		node.contentsContainer.appendChild(node.elementsContainer);

		node.deleteButton = document.createElement('button');
		node.deleteButton.setAttribute('type','button');
		node.deleteButton.setAttribute('class','delNodeButton');
		node.deleteButton.onclick = function() { root.delNode(node); };
		node.deleteButton.textContent = '-';
		node.labelContainer.appendChild(node.deleteButton);

		node.getData = function() {
			var data = {};

			data['elements'] = [];
			for (var e in this.elements) {
				data['elements'].push(this.elements[e].getData());
			}
			data['isChoice'] = this.isChoice;
			data['branch'] = this.type == 'branch';
			data['conditionSet'] = this.displayConditions.getData();
			data['scene'] = this.scene.value;
			data['actions'] = null;

			return data;
		}

		return node;
	}

	createElement(node, index) {
		var elementIndex = index;
		if (elementIndex == undefined) {
			elementIndex = node.elements.length;
		}
		const root = this;
		var element = {};

		element.parent = node;
		element.name = node.name + '_' + elementIndex;
		element.index = elementIndex;
		element.nextIndex = node.index + 1;
		element.nextLabel = null;

		element.container = document.createElement('div');
		element.container.setAttribute('class','conversationNodeElement');
		element.container.appendChild(node.createAddElementButton(element));

		element.displayConditionContainer = document.createElement('div');
		element.displayConditionContainer.setAttribute('class','conversationElementConditions');
		element.container.appendChild(element.displayConditionContainer);

		element.displayConditionHeader = document.createElement('div');
		element.displayConditionHeader.setAttribute('class','conversationElementHeader');
		element.displayConditionHeader.textContent = 'Display this element:';
		element.displayConditionContainer.appendChild(element.displayConditionHeader);

		element.displayConditions = document.createElement('display-edit-condition-set');
		element.displayConditionContainer.appendChild(element.displayConditions);

		element.contents = document.createElement('div');
		element.contents.setAttribute('class','conversationElementText');

		if (node.type == 'choice') {
			element.text = document.createElement('display-edit-text');
			element.text.setAttribute('field-name', element.name + '_text');
			element.text.setAttribute('field-label', 'Choice: ');
			element.text.setAttribute('field-value', '');
			this.addReplacements(element.text);
			element.contents.appendChild(element.text);
		} else if (node.type == 'text') {
			element.text = document.createElement('display-edit-textarea');
			element.text.setAttribute('field-name', element.name + '_text');
			element.text.setAttribute('allow-tab','');
			element.text.setAttribute('rows',-1);
			element.text.setAttribute('field-label', '');
			var textText = document.createElement('text');
			element.text.appendChild(textText);
			this.addReplacements(element.text);
			element.contents.appendChild(element.text);
		} else if (node.type == 'branch') {
			element.text = document.createElement('div');
			element.text.setAttribute('style','display: none');
		}
		element.container.appendChild(element.contents);

		element.nextContainer = document.createElement('div');
		element.container.appendChild(element.nextContainer);

		element.next = document.createElement('display-edit-select');
		element.next.setAttribute('class','conversationElementNext');
		element.next.setAttribute('field-label', 'Next: ');
		element.next.name = element.name + '_next';
		element.next.setAttribute('field-name', element.next.name);
		element.next.setAttribute('style','display: flex;');
		element.next.setAttribute('label-style','margin-right: 2em;');
		element.next.value = element.nextIndex;
		element.next.setAttribute('field-value', element.next.value);
		for (var i in this.nextOptions) {
			var optionText = this.nextOptions[i];
			var option = document.createElement('option');
			option.value = i;
			option.textContent = optionText;
			element.next.appendChild(option);
		}
		element.nextContainer.appendChild(element.next);

		element.deleteButton = document.createElement('button');
		element.deleteButton.setAttribute('type','button');
		element.deleteButton.setAttribute('class','delElementButton');
		element.deleteButton.onclick = function() { node.delElement(element); };
		element.deleteButton.textContent = '-';
		element.container.appendChild(element.deleteButton);

		element.setNext = function(next) { this.next.setValue(next); };
		element.setText = function(text) {
			if (this.parent.type != 'branch') {
				this.text.setValue(text);
			} else {
				this.text.textContent = text;
			}
		};
		element.setDisplayConditions = function(newConditions) {
			this.displayConditionContainer.removeChild(this.displayConditions);
			this.displayConditions = newConditions;
			this.displayConditionContainer.appendChild(newConditions);
		};
		element.setIndex = function(newIndex) {
			this.index = newIndex;
			this.name = node.name + '_' + newIndex;
			// TODO: method for displayEditText for setting name
			this.text.setAttribute('field-name',this.name + '_text');
		};

		element.getData = function() {
			var data = {};
			if (this.text.value) {
				data['text'] = this.text.value;
			} else {
				data['text'] = '';
			}
			data['next'] = this.next.value;
			data['conditions'] = this.displayConditions.getData();
			return data;
		};

		return element;
	}

	addReplacements(textElement) {
		for (var i=0; i<this.tokenReplacement.length; i++) {
			var replacement = this.tokenReplacement[i];
			var replacer = document.createElement('replace');
			replacer.setAttribute('search','<'+replacement['token']+'>');
			replacer.setAttribute('replace',replacement['replacement']);
			textElement.appendChild(replacer);
		}
	}

	createAddNodeButton(afterNode) {
		const root = this;
		var button = document.createElement('button');
		button.setAttribute('type','button');
		button.setAttribute('class','addNodeButton');
		button.onclick = function() { root.addNode(afterNode); };
		button.textContent = '+';

		return button;
	}

	addNode(afterNode) {
		var node = this.createNode(afterNode);
		if (afterNode) {
			var newIndex = afterNode.index + 1;
			node.setIndex(newIndex);
			afterNode.container.after(node.container);
			this.nodes.splice(newIndex, 0, node);
		} else {
			node.setIndex(0);
			this.wrapper.appendChild(node.container);
			this.nodes.splice(0, 0, node);
		}
		this.renumberNodes();
	}

	delNode(node) {
		node.container.remove();
		this.nodes.splice(node.index, 1);
		this.renumberNodes();
	}

	getIndexForLabel(labelName) {
		for (var labelIndex in this.labels) {
			if (this.labels[labelIndex] == labelName) {
				return labelIndex;
			}
		}
		return null;
	}

	updateLabels() {
		this.nextOptions = {};
		this.nextOptions[-1] = '(end)';
		for (var i=0; i<this.nodes.length; i++) {
			var node = this.nodes[i];
			if (node.label) {
				this.nextOptions[i] = node.label;
			} else {
				this.nextOptions[i] = 'node #' + i;
			}
		}
		this.nextOptions[i] = 'node #' + i;
		for (var i=0; i<this.nodes.length; i++) {
			var node = this.nodes[i];
			for (var j=0; j<node.elements.length; j++) {
				var element = node.elements[j];

				element.next.setOptions(this.nextOptions);
			}
		}
	}

	renumberNodes() {
		var indexMap = [];
		for (var i=0; i<this.nodes.length; i++) {
			var node = this.nodes[i];
			var oldIndex = node.index;
			node.setIndex(i);
			indexMap[oldIndex] = i;
			if (this.labels[oldIndex]) {
				delete this.labels[oldIndex];
			}
			if (node.label) {
				this.labels[i] = node.label;
			}
		}
		this.updateLabels();
		for (var i=0; i<this.nodes.length; i++) {
			var node = this.nodes[i];
			for (var j=0; j<node.elements.length; j++) {
				var element = node.elements[j];
				if (element.nextLabel) {
					element.nextIndex = this.getIndexForLabel(element.nextLabel);
				} else {
					element.nextIndex = indexMap[element.nextIndex];
				}
				element.next.setValue(element.nextIndex);
			}
		}
	}

	getData() {
		var data = {};

		data['name'] = this.name;

		data['labels'] = {};
		for (var l in this.labels) {
			data['labels'][this.labels[l]] = l;
		}

		data['nodes'] = [];
		for (var n in this.nodes) {
			var node = this.nodes[n];
			var nodeData = node.getData();
			data['nodes'].push(nodeData);
		}

		return data;
	}
}

class DisplayEditLocationFilter extends HTMLElement {

	constructor() {
		super();
		const shadow = this.attachShadow({mode: 'open'});

		// private Collection $planets;
		//
		// private array $attributes = []; //list<set<string>>
		//
		// private Collection $systems;
		//
		// private Collection $governments;
		//
		// private ?System $center = null;
		// private int $centerMinDistance = 0;
		// private int $centerMaxDistance = 1;
		// private DistanceCalculationSettings $centerDistanceOptions;
		//
		// private int $originMinDistance = 0;
		// private int $originMaxDistance = -1;
		// private DistanceCalculationSettings $originDistanceOptions;
		//
		// private array $outfits = []; //list<set<const Outfit *>>
		//
		// private array $shipCategory = []; //set<string>
		//
		// private Collection $notFilters;
		//
		// private Collection $neighborFilters;
	}

}

customElements.define('display-edit-text',DisplayEditText);
customElements.define('display-edit-textarea',DisplayEditTextarea);
customElements.define('display-edit-select',DisplayEditSelect);
customElements.define('display-edit-list',DisplayEditList);
customElements.define('display-edit-condition-set',DisplayEditConditionSet);
customElements.define('display-edit-conversation',DisplayEditConversation);