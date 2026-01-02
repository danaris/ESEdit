class AutoSuggestTextbox extends HTMLElement {

	constructor() {
		super();
		const shadow = this.attachShadow({mode: 'open'});
		
		this.name = this.getAttribute('name');
		this.id = this.getAttribute('id');
		this.value = this.getAttribute('value');
		this.sourceFunc = this.getAttribute('source');
		
		this.wrapper = document.createElement('div');
		this.wrapper.setAttribute('class','astContainer');
		
		this.text = document.createElement('input');
		this.text.setAttribute('class','astText');
		this.text.setAttribute('type','text');
		this.text.setAttribute('value',this.value);
		const root = this;
		this.text.onkeyup = function(event) { root.lookup(); };
		this.wrapper.appendChild(this.text);
		
		this.
		
		this.style = document.createElement('style');
		style.textContent = `
		.astContainer {
			
		}
		`;
		
		shadow.appendChild(wrapper);
		shadow.appendChild(style);
	}
	
	connectedCallback() {
		
	}
	
	lookup() {
		var textVal = this.text.value);
		var list = window[this.source](textVal);
		
	}
	
}