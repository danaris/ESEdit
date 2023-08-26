<?php

namespace App\Entity;

class DataNode implements \Iterator, \ArrayAccess, \Countable {
	
	// These are "child" nodes found on subsequent lines with deeper indentation.
	protected array $children = []; // DataNode array
	// These are the tokens found in this particular line of the data file.
	protected array $tokens = []; // string array
	// The parent pointer is used only for printing stack traces.
	protected ?DataNode $parent = null;
	// The line number in the given file that produced this node.
	protected int $lineNumber = 0;
	
	private int $iterIndex = 0;
	
	public string $fromFile = '';
	
	// Construct a DataNode and remember what its parent is.
	public function __construct(DataNode $parent = null, DataNode $other = null, ?string $fromFile = null) {
		if ($parent) {
			$this->parent = $parent;
		}
		if ($other) {
			$this->children = $other->children;
			$this->tokens = $other->tokens;
			$this->lineNumber = $other->lineNumber;
			$this->reparent();
		}
		if ($fromFile) {
			$this->fromFile = $fromFile;
		}
	}
	
	// Get the number of tokens in this line of the data file.
	public function size(): int {
		return count($this->tokens);
	}
	
	// Get all tokens.
	public function getTokens(): array {
		return $this->tokens;
	}
	
	// Get the token with the given index. No bounds checking is done.
	// DataFile loading guarantees index 0 always exists.
	public function getToken(int $index): string {
		return $this->tokens[$index];
	}
	
	// Convert the token with the given index to a numerical value.
	public function getValue(int $index): float {
		// Check for empty strings and out-of-bounds indices.
		if (!isset($this->tokens[$index])) {
			$this->printTrace("Error: Requested token index (" . $index . ") is out of bounds:");
		} else if(!DataNode::TokenIsNumber($this->tokens[$index])) {
			$this->printTrace("Error: Cannot convert value \"" . $this->tokens[$index] . "\" to a number:");
		} else {
			return DataNode::TokenValue($this->tokens[$index]);
		}
	
		return 0.0;
	}
	
	// Static helper function for any class which needs to parse string -> number.
	public static function TokenValue(string $token): float {
		// Allowed format: "[+-]?[0-9]*[.]?[0-9]*([eE][+-]?[0-9]*)?".
		if (!DataNode::TokenIsNumber($token)) {
			error_log("Cannot convert value \"" . $token . "\" to a number.");
			return 0.0;
		}
		$tokenIndex = 0;
	
		// Check for leading sign.
		$sign = ($token[0] == '-') ? -1. : 1.;
		$tokenIndex += ($token[0] == '-' || $token[0] == '+');
	
		// Digits before the decimal point.
		$value = 0;
		while (isset($token[$tokenIndex]) && $token[$tokenIndex] >= '0' && $token[$tokenIndex] <= '9') {
			$value = ($value * 10) + intval($token[$tokenIndex]);
			$tokenIndex++;
		}
	
		// Digits after the decimal point (if any).
		$power = 0;
		if (isset($token[$tokenIndex]) && $token[$tokenIndex] == '.') {
			$tokenIndex++;
			while (isset($token[$tokenIndex]) && $token[$tokenIndex] >= '0' && $token[$tokenIndex] <= '9') {
				$value = ($value * 10) + intval($token[$tokenIndex++]);
				$power--;
			}
		}
	
		// Exponent.
		if (isset($token[$tokenIndex]) && ($token[$tokenIndex] == 'e' || $token[$tokenIndex] == 'E')) {
			$tokenIndex++;
			$expSign = ($token[$tokenIndex] == '-') ? -1 : 1;
			$tokenIndex += ($token[$tokenIndex] == '-' || $token[$tokenIndex] == '+');
	
			$exponent = 0;
			while (isset($token[$tokenIndex]) && $token[$tokenIndex] >= '0' && $token[$tokenIndex] <= '9')
				$exponent = ($exponent * 10) + intval($token[$tokenIndex]);
				$tokenIndex++;
	
			$power += $expSign * $exponent;
		}
	
		// Compose the return value.
		return $value * pow(10.0, $power) * $sign;
	}
	
	// Check if the token at the given index is a number in a format that this
	// class is able to parse.
	public function isNumber(int $index): bool {
		// Make sure this token exists and is not empty.
		if($index >= count($this->tokens) || !$this->tokens[$index])
			return false;
	
		return DataNode::TokenIsNumber($this->tokens[$index]);
	}
	
	public static function TokenIsNumber(string $token): bool {
		$hasDecimalPoint = false;
		$hasExponent = false;
		$isLeading = true;
		for ($tokenIndex = 0; $tokenIndex < strlen($token); $tokenIndex++) {
			// If this is the start of the number or the exponent, it is allowed to
			// be a '-' or '+' sign.
			if ($isLeading) {
				$isLeading = false;
				if ($token[$tokenIndex] == '-' || $token[$tokenIndex] == '+') {
					continue;
				}
			}
			// If this is a decimal, it may or may not be allowed.
			if ($token[$tokenIndex] == '.') {
				if ($hasDecimalPoint || $hasExponent) {
					return false;
				}
				$hasDecimalPoint = true;
			} else if ($token[$tokenIndex] == 'e' || $token[$tokenIndex] == 'E') {
				if ($hasExponent) {
					return false;
				}
				$hasExponent = true;
				// At the start of an exponent, a '-' or '+' is allowed.
				$isLeading = true;
			} else if ($token[$tokenIndex] < '0' || $token[$tokenIndex] > '9') {
				return false;
			}
		}
		return true;
	}
	
	// Convert the token at the given index to a boolean. This returns false
	// and prints an error if the index is out of range or the token cannot
	// be interpreted as a number.
	public function boolValue(int $index): bool {
		// Check for empty strings and out-of-bounds indices.
		if (!isset($this->tokens[$index])) {
			PrintTrace("Error: Requested token index (" . to_string(index) . ") is out of bounds:");
		} else if (!DataNode::TokenIsBool($this->tokens[$index])) {
			PrintTrace("Error: Cannot convert value \"" . tokens[index] . "\" to a boolean:");
		} else {
			$token = $this->tokens[$index];
			return $token == "true" || $token == "1";
		}
	
		return false;
	}
	
	// Check if the token at the given index is a boolean, i.e. "true"/"1" or "false"/"0"
	// as a string.
	public function isBool(int $index): bool {
		// Make sure this token exists and is not empty.
		if ($index >= count($this->tokens) || !$this->tokens[$index]) {
			return false;
		}
	
		return DataNode::TokenIsBool($this->tokens[$index]);
	}
	
	public static function TokenIsBool(string $token): bool {
		return $token == "true" || $token == "1" || $token == "false" || $token == "0";
	}
	
	// Check if this node has any children.
	public function hasChildren(): bool {
		return count($this->children) > 0;
	}
	
	public function getChildren(): array {
		return $this->children;
	}
	
	public function setLineNumber(int $lineNumber): void {
		$this->lineNumber = $lineNumber;
	}
	
	public function addToken(string $token): void {
		$this->tokens []= $token;
	}
	
	public function current(): DataNode {
		return $this->children[array_keys($this->children)[$this->iterIndex]];
	}
	
	public function key(): scalar {
		return array_keys($this->children)[$this->iterIndex];
	}
	
	public function next(): void {
		$this->iterIndex++;
	}
	
	public function rewind(): void {
		$this->iterIndex = 0;
	}
	
	public function offsetExists(mixed $offset): bool {
		return isset($this->children[$offset]);
	}
	public function offsetGet(mixed $offset): mixed {
		return $this->children[$offset];
	}
	public function offsetSet(mixed $offset, mixed $value): void {
		if ($offset === null) {
			$this->children []= $value;
		} else {
			$this->children[$offset] = $value;
		}
	}
	public function offsetUnset(mixed $offset): void {
		unset($this->children[$offset]);
	}
	
	public function count(): int {
		return count($this->children);
	}
	
	public function valid(): bool {
		if ($this->iterIndex < 0) {
			return false;
		}
		if (!isset(array_keys($this->children)[$this->iterIndex])) {
			return false;
		}
		$childKey = array_keys($this->children)[$this->iterIndex];
		if (isset($this->children[$childKey])) {
			return true;
		}
		
		return false;
	}
	
	// Print a message followed by a "trace" of this node and its parents.
	public function printTrace(string $message = ''): int {
		if($message) {
			error_log($message);
		}
	
		// Recursively print all the parents of this node, so that the user can
		// trace it back to the right point in the file.
		$indent = 0;
		if ($this->parent) {
			$indent = $this->parent->printTrace() + 2;
		}
		if (count($this->tokens) <= 0) {
			return $indent;
		}
	
		// Convert this node back to tokenized text, with quotes used as necessary.
		$line = !$this->parent ? "" : "L" . $this->lineNumber . ": ";
		$line = str_pad($line, strlen($line) + $indent, ' ');
		foreach ($this->tokens as $token) {
			if ($token != $this->tokens[0]) {
				$line .= ' ';
			}
			$hasSpace = str_contains($token, ' ');
			$hasQuote = str_contains($token, '"');
			if ($hasSpace) {
				$line .= $hasQuote ? '`' : '"';
			}
			$line .= $token;
			if ($hasSpace) {
				$line .= $hasQuote ? '`' : '"';
			}
		}
		error_log($line);
	
		// Put an empty line in the log between each error message.
		if($message)
			error_log('');
	
		// Tell the caller what indentation level we're at now.
		return $indent;
	}
	
	// Adjust the parent pointers when a copy is made of a DataNode.
	public function reparent(): void {
		foreach ($children as $child) {
			$child->parent = $this;
			$child->reparent();
		}
	}

}