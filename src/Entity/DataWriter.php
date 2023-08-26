<?php

namespace App\Entity;

class DataWriter {
	// Save path (in UTF-8). Empty string for in-memory DataWriter.
	protected string $path = '';
	// Current indentation level.
	protected string $indent = '';
	// Before writing each token, we will write either the indentation string
	// above, or this string.
	protected static string $space = ' ';
	// Remember which string should be written before the next token. This is
	// "indent" for the first token in a line and "space" for subsequent tokens.
	protected string $before = '';
	// Compose the output in memory before writing it to file.
	protected string $out = '';
	
	// Constructor, specifying the file to save.
	public function __construct(string $path = null) {
		if ($path) {
			$this->path = $path;
		}
	}
	
	// Save the contents to a file.
	public function saveToPath(string $filepath): void {
		file_put_contents($filepath, $this->out);
	}
	
	public function getString(): string {
		error_log($this->out);
		return $this->out;
	}
	
	// Write a DataNode with all its children.
	public function writeNode(DataNode $node) {
		// Write all this node's tokens.
		for ($i = 0; $i < count($node->getTokens()); ++$i) {
			$this->writeToken($node->getToken($i));
		}
		$this->writeNewline();
	
		// If this node has any children, call this function recursively on them.
		if ($node->hasChildren()) {
			$this->beginChild();
			foreach ($node as $child) {
				$this->writeNode($child);
			}
			$this->endChild();
		}
	}
	
	// Begin a new line of the file.
	public function writeNewline() {
		$this->out .= "\n";
		$this->before = $this->indent;
	}
	
	public function write(array|string $tokens, bool $forceBacktick = false) {
		if (!is_array($tokens)) {
			$tokens = [$tokens];
		}
		foreach ($tokens as $token) {
			$this->writeToken($token, $forceBacktick);
		}
		$this->writeNewline();
	}
	
	// Increase the indentation level.
	public function beginChild() {
		$this->before = $this->indent .= '	';
	}
	
	// Decrease the indentation level.
	public function endChild() {
		$this->before = $this->indent = mb_substr($this->indent, 0, -1);
	}
	
	// Write a comment line, at the current indentation level.
	public function writeComment(string $str) {
		$this->out .= $this->indent . "# " . $str . "\n";
	}
	
	// Write a token, given as a string object.
	public function writeToken(string $a, bool $forceBacktick = false) {
		// Figure out what kind of quotation marks need to be used for this string.
		$hasSpace = str_contains($a, ' ');
		$hasQuote = str_contains($a, '"');
		// If the token is an empty string, it needs to be wrapped in quotes as if it had a space.
		$hasSpace |= $a === '';
		// Write the token, enclosed in quotes if necessary.
		$this->out .= $this->before;
		if ($hasQuote || $forceBacktick) {
			$this->out .= '`' . $a . '`';
		} else if ($hasSpace) {
			$this->out .= '"' . $a . '"';
		} else {
			$this->out .= $a;
		}
	
		// The next token written will not be the first one on this line, so it only
		// needs to have a single space before it.
		$this->before = self::$space;
	}

}