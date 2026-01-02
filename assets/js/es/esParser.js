class ESDataNode {
	constructor(parentNode = null) {
		this.children = []; // list of DataNodes
		this.tokens = []; // vector of strings
		this.parent = parentNode; // DataNode
		this.lineNumber = 0;

		this.sourceFile = null; // DataFile

		this.zeroCode = '0'.charCodeAt(0);
		this.nineCode = '9'.charCodeAt(0);
	}

	printTrace(message) {
		// Print a message followed by a "trace" of this node and its parents.

		if (message != '') {
			console.log('Error: ' + message);
		}

		// Recursively print all the parents of this node, so that the user can
		// trace it back to the right point in the file.
		var indent = 0;
		if (this.parent) {
			indent = parent.printTrace() + 2;
		}
		if (this.tokens.length == 0) {
			return indent;
		}

		// Convert this node back to tokenized text, with quotes used as necessary.
		var line = !this.parent ? "" : "L" + this.lineNumber + ": ";
		line.padEnd(indent, ' ');
		let firstToken = this.tokens[0];
		for (var token of this.tokens) {
			if(token != firstToken)
				line += ' ';
			line += ESDataWriter.Quote(token);
		}
		console.log(line);

		// Put an empty line in the log between each error message.
		if(message != '')
			console.log('');

		// Tell the caller what indentation level we're at now.
		return indent;
	}

	getSize() {
		return this.tokens.length;
	}

	getTokens() {
		return this.tokens;
	}

	getChildren() {
		return this.children;
	}

	getTokenAt(index) {
		if (this.tokens[index] !== undefined) {
			return this.tokens[index];
		}
	}

	addToken(token) {
		if (typeof token !== 'string') {
			return;
		}
		this.tokens.push(token);
	}

	getValueAt(index) {
		// Check for empty strings and out-of-bounds indices.
		if (index >= this.tokens.length || this.tokens[index] == '') {
			this.printTrace("Requested token index (" + index + ") is out of bounds:");
		} else if (isNaN(this.tokens[index])) {
			this.printTrace("Cannot convert value \"" + this.tokens[index] + "\" to a number:");
		} else {
			return this.getValue(this.tokens[index]);
		}

		return 0;
	}

	getValue(string) {
		// Allowed format: "[+-]?[0-9]*[.]?[0-9]*([eE][+-]?[0-9]*)?".
		if(isNaN(string)) {
			console.log("Cannot convert value \"" + string + "\" to a number.");
			return 0;
		}
		var it = Array.from(string);
		var at = 0;

		// Check for leading sign.
		var sign = (it[at] == '-') ? -1 : 1;
		if (it[at] == '-' || it[at] == '+') {
			at++;
		}

		// Digits before the decimal point.
		var value = 0;
		while (at <= it.length - 1 && it[at].charCodeAt(0) >= this.zeroCode && it[at].charCodeAt(0) <= this.nineCode) {
			value = (value * 10) + (it[at++].charCodeAt(0) - this.zeroCode);
		}

		// Digits after the decimal point (if any).
		var power = 0;
		if(it[at] == '.') {
			++at;
			while (at <= it.length - 1 && it[at].charCodeAt(0) >= this.zeroCode && it[at].charCodeAt(0) <= this.nineCode) {
				value = (value * 10) + (it[at++].charCodeAt(0) - this.zeroCode);
				--power;
			}
		}

		// Exponent.
		if (it[at] == 'e' || it[at] == 'E') {
			++at;
			var eSign = (it[at] == '-') ? -1 : 1;
			at += (it[at] == '-' || it[at] == '+') ? 1 : 0;

			var exponent = 0;
			while (at <= it.length - 1 && it[at].charCodeAt(0) >= this.zeroCode && it[at].charCodeAt(0) <= this.nineCode) {
				exponent = (exponent * 10) + (it[at++].charCodeAt(0) - this.zeroCode);
			}

			power += eSign * exponent;
		}

		// Compose the return value.
		return value * Math.pow(10., power) * sign;
	}

	getBoolValueAt(index) {
		// Check for empty strings and out-of-bounds indices.
		if (index >= this.tokens.length || this.tokens[index] == '') {
			this.printTrace("Requested token index (" + index + ") is out of bounds:");
		} else if (this.isBool(this.tokens[index])) {
			this.printTrace("Cannot convert value \"" + this.tokens[index] + "\" to a number:");
		} else {
			var token = this.tokens[index];
			return token == "true" || token == "1";
		}

		return false;
	}

	// Check if the token at the given index is a boolean, i.e. "true"/"1" or "false"/"0"
	// as a string.
	isBool(index) {
		// Make sure this token exists and is not empty.
		if (index >= this.tokens.length || this.tokens[index] == '') {
			return false;
		}

		return this.isBool(this.tokens[index]);
	}

	isBool(token) {
		return token == "true" || token == "1" || token == "false" || token == "0";
	}

	static isConditionName(token) {
		// For now check if condition names start with an alphabetic character, and that is all we check for now.
		// Token "'" is required for backwards compatibility (used for illegal tokens).
		// Boolean keywords are not valid conditionNames, so we also check for that.
		return token != '' &&
			!this.isBool(token) &&
			(
				(token == "'") ||
				(token.charCodeAt(0) >= 'a'.charCodeAt(0) && token.charCodeAt(0) <= 'z'.charCodeAt(0)) ||
				(token.charCodeAt(0) >= 'A'.charCodeAt(0) && token.charCodeAt(0) <= 'Z'.charCodeAt(0))
			);
	}

}

class ESDataWriter {
	constructor() {
		// Relative path (in UTF-8). Empty string for in-memory DataWriter.
		this.path = '';
		// Current indentation level (string).
		this.indent = '';
		// Remember which string should be written before the next token. This is
		// "indent" for the first token in a line and "space" for subsequent tokens.
		this.before = '';
		// Compose the output in memory before writing it to local storage.
		this.out = '';
	}

	write(node) {
		if (!node) {
			this.out += "\n";
			this.before = this.indent;
			return;
		}
		// Write all this node's tokens.
		for (var i = 0; i < node.getSize(); ++i) {
			this.writeToken(node.getTokenAt(i));
		}
		this.write();

		// If this node has any children, call this function recursively on them.
		if(node.hasChildren())
		{
			this.beginChild();
			for (var child of node.getChildren()) {
				this.write(child);
			}
			this.endChild();
		}
	}

	// Increase the indentation level.
	beginChild() {
		this.indent += '\t';
	}

	// Decrease the indentation level.
	endChild() {
		this.indent = this.indent.slice(0, -1);
	}

	// Write a comment line, at the current indentation level.
	writeComment(comment)
	{
		this.out += this.before + "# " + comment;
		this.write();
	}

	// Write a token, given as a string object.
	writeToken(token) {
		this.out += before;
		this.out += ESDataWriter.Quote(token);

		// The next token written will not be the first one on this line, so it only
		// needs to have a single space before it.
		this.before = ' ';
	}

	saveToStorage() {
		localStorage.setItem('esData/' + this.path, this.out);
	}

	static Quote(string) {
		// Figure out what kind of quotation marks need to be used for this string.
		var hasSpace = string.contains(' ');
		var hasQuote = string.contains('"');
		var hasBacktick = string.contains('`');
		// If the token is an empty string, it needs to be wrapped in quotes as if it had a space.
		hasSpace |= string === '';

		if (hasQuote) {
			return '`' + string + '`';
		} else if (hasSpace || hasBacktick) {
			return '"' + string + '"';
		} else {
			return string;
		}
	}
}

class ESDataFile {
	constructor(path, loadCallback) {
		this.root = new ESDataNode();
		this.root.sourceFile = this;
		this.loadCallback = loadCallback;

		this.source = '';
		this.filePath = '';

		this.load(path);
	}

	load(path, source='esdata') {
		var fileFetch = fetch('/es/data/' + path);
		fileFetch.then((data) => {
			data.text().then((text) => {
				if (text == '') {
					return;
				} else {
					if (text.slice(-1) != "\n") {
						text += "\n";
					}

					console.log('starting to parse file ' + path);
					this.root.tokens.push('file');
					this.root.tokens.push(path);

					this.source = source;
					this.filePath = path;

					this.loadData(text);
				}
			});
		});
	}

	loadData(data) {
		// Keep track of the current stack of indentation levels and the most recent
		// node at each level - that is, the node that will be the "parent" of any
		// new node added at the next deeper indentation level.
		var stack = [this.root];
		var separatorStack = [-1];
		var fileIsTabs = false;
		var fileIsSpaces = false;
		var lineNumber = 0;

		var end = data.length;

		var pos = 0;
		let spaceCode = ' '.charCodeAt(0);
		let crCode = '\n'.charCodeAt(0); 
		let tabCode = '	'.charCodeAt(0);
		let hashCode = '#'.charCodeAt(0);
		let quoteCode = '"'.charCodeAt(0);
		let btCode = '`'.charCodeAt(0);

		while(pos < end) {
			++lineNumber;
			var tokenPos = pos;
			var c = data.charCodeAt(pos++);

			var mixedIndentation = false;
			var separators = 0;
			// Find the first tokenizable character in this line (i.e. neither space nor tab).
			while (c <= spaceCode && c != crCode) {
				// Determine what type of indentation this file is using.
				if(!fileIsTabs && !fileIsSpaces) {
					if (c == tabCode) {
						fileIsTabs = true;
					} else if (c == spaceCode) {
						fileIsSpaces = true;
					}
				// Issue a warning if the wrong indentation is used.
				} else if ((fileIsTabs && c != tabCode) || (fileIsSpaces && c != spaceCode)) {
					mixedIndentation = true;
				}

				++separators;
				tokenPos = pos;
				c = data.charCodeAt(pos++);
			}

			// If the line is a comment, skip to the end of the line.
			if (c == hashCode) {
				if (mixedIndentation) {
					this.root.printTrace("Warning: Mixed whitespace usage for comment at line " + lineNumber);
				}
				while(c != crCode) {
					c = data.charCodeAt(pos++);
				}
			}
			// Skip empty lines (including comment lines).
			if (c == crCode) {
				continue;
			}

			// Determine where in the node tree we are inserting this node, based on
			// whether it has more indentation that the previous node, less, or the same.
			while (separatorStack.slice(-1)[0] >= separators) {
				separatorStack.pop();
				stack.pop();
			}

			// Add this node as a child of the proper node.
			var children = stack.slice(-1)[0].getChildren();
			var node = new ESDataNode(stack.slice(-1)[0]);
			node.sourceFile = this;
			children.push(node);
			node.lineNumber = lineNumber;

			// Remember where in the tree we are.
			stack.push(node);
			separatorStack.push(separators);

			// Tokenize the line. Skip comments and empty lines.
			while (c != crCode) {
				// Check if this token begins with a quotation mark. If so, it will
				// include everything up to the next instance of that mark.
				var endQuote = c;
				var isQuoted = (endQuote == quoteCode || endQuote == btCode);
				if(isQuoted) {
					tokenPos = pos;
					c = data.charCodeAt(pos++);
				}

				var endPos = tokenPos;

				// Find the end of this token.
				while(c != crCode && (isQuoted ? (c != endQuote) : (c > spaceCode))) {
					endPos = pos;
					c = data.charCodeAt(pos++);
				}

				// It ought to be legal to construct a string from an empty iterator
				// range, but it appears that some libraries do not handle that case
				// correctly. So:
				if (tokenPos == endPos) {
					node.tokens.push('');
				} else {
					var tokenString = data.substring(tokenPos, endPos);
					node.tokens.push(tokenString);
				}
				// This is not a fatal error, but it may indicate a format mistake:
				if(isQuoted && c == '\n') {
					node.printTrace("Warning: Closing quotation mark is missing:");
				}

				if(c != crCode) {
					// If we've not yet reached the end of the line of text, search
					// forward for the next non-whitespace character.
					if (isQuoted) {
						tokenPos = pos;
						c = data.charCodeAt(pos++);
					}
					while(c != crCode && c <= spaceCode && c != hashCode)
					{
						tokenPos = pos;
						c = data.charCodeAt(pos++);
					}

					// If a comment is encountered outside of a token, skip the rest
					// of this line of the file.
					if (c == hashCode) {
						while (c != crCode) {
							c = data.charCodeAt(pos++);
						}
					}
				}
			}

			// Now that we've tokenized this node, print any mixed whitespace warnings.
			if (mixedIndentation) {
				node.printTrace("Warning: Mixed whitespace usage at line");
			}
		}

		this.loadCallback();
	}
}