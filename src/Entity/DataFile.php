<?php

namespace App\Entity;

class DataFile implements \Iterator, \ArrayAccess {
	protected DataNode $root;
	
	#[ORM\Column(type: 'string')]
	private string $sourceName = '';
	#[ORM\Column(type: 'string')]
	private string $sourceFile = '';
	#[ORM\Column(type: 'string')]
	private string $sourceVersion = '';
	
	// Constructor, taking a file path (in UTF-8).
	public function __construct(array $sourceInfo) {
		$this->root = new DataNode(source: $sourceInfo);
		$this->sourceName = $sourceInfo['name'];
		$this->sourceFile = $sourceInfo['file'];
		$this->sourceVersion = $sourceInfo['version'];
		$this->load($sourceInfo['file']);
	}
	
	public function getSource(): array {
		return ['name'=>$this->sourceName, 'file'=>$this->sourceFile, 'version'=>$this->sourceVersion];
	}
	
	// Load from a file path (in UTF-8).
	public function load(string $path): void {
		$data = file_get_contents($path);
		if(!$data) {
			return;
		}
	
		// As a sentinel, make sure the file always ends in a newline.
		if (substr($data, -1) != '\n') {
			$data .= "\n";
		}
	
		// Note what file this node is in, so it will show up in error traces.
		$this->root->addToken("file");
		$this->root->addToken($path);
	
		$this->loadData($data);
	}
	
	public function getRoot(): DataNode {
		return $this->root;
	}
	
	public function current(): DataNode {
		return $this->root->current();
	}
	
	public function key(): scalar {
		return $this->root->key();
	}
	
	public function next(): void {
		$this->root->next();
	}
	
	public function rewind(): void {
		$this->root->rewind();
	}
	
	public function valid(): bool {
		return $this->root->valid();
	}
	
	public function offsetExists(mixed $offset): bool {
		return $this->root->offsetExists($offset);
	}
	public function offsetGet(mixed $offset): mixed {
		return $this->root->offsetGet($offset);
	}
	public function offsetSet(mixed $offset, mixed $value): void {
		$this->root->offsetSet($offset, $value);
	}
	public function offsetUnset(mixed $offset): void {
		$this->root->offsetUnset($offset);
	}
	
	// Parse the given text.
	public function loadData(string $data): void {
		// Keep track of the current stack of indentation levels and the most recent
		// node at each level - that is, the node that will be the "parent" of any
		// new node added at the next deeper indentation level.
		$stack = [1=>$this->root];
		$separatorStack = [1=>-1];
		$fileIsTabs = false;
		$fileIsSpaces = false;
		$lineNumber = 0;
		
		$spaceCode = mb_ord(' ');
		
		$end = mb_strlen($data);
		$lines = explode("\n", $data);
		//for ($pos = 0; $pos < $end; ) {
		for ($lineNumber = 0; $lineNumber < count($lines); $lineNumber++) {
			//++$lineNumber;
			//$tokenPos = $pos;
			//$c = mb_substr($data, $pos++, 1);
			$line = $lines[$lineNumber] . "\n";
			$firstChar = mb_substr($line, 0, 1);
	
			$mixedIndentation = false;
			$separators = 0;
			
			$pos = 0;
			$tokenPos = $pos;
			$c = mb_substr($line, $pos++, 1);
			// Find the first tokenizable character in this line (i.e. neither space nor tab).
			while (mb_ord($c) <= $spaceCode && $c != "\n") {
				// Determine what type of indentation this file is using.
				if(!$fileIsTabs && !$fileIsSpaces) {
					if($c == '	') {
						$fileIsTabs = true;
					} else if ($c == ' ') {
						$fileIsSpaces = true;
					}
				} else if(($fileIsTabs && $c != '	') || ($fileIsSpaces && c != ' ')) {
					// Issue a warning if the wrong indentation is used.
					$mixedIndentation = true;
				}
	
				++$separators;
				$tokenPos = $pos;
				//$c = mb_substr($data, $pos++, 1);
				$c = mb_substr($line, $pos++, 1);
			}
	
			// If the line is a comment, skip to the end of the line.
			if ($c == '#') {
				if ($mixedIndentation) {
					$this->root->printTrace("Warning: Mixed whitespace usage for comment at line " . $lineNumber);
				}
				while ($c != "\n") {
					//$c = mb_substr($data, $pos++, 1);
					$c = mb_substr($line, $pos++, 1);
				}
			}
			// Skip empty lines (including comment lines).
			if ($c == "\n") {
				continue;
			}
	
			// Determine where in the node tree we are inserting this node, based on
			// whether it has more indentation that the previous node, less, or the same.
			while ($separatorStack[array_key_last($separatorStack)] >= $separators) {
				array_pop($separatorStack);
				array_pop($stack);
			}
	
			// Add this node as a child of the proper node.
			$stackBack = $stack[array_key_last($stack)];
			$node = new DataNode(parent: $stackBack, source: $this->getSource());
			$stackBack []= $node;
			$node->setLineNumber($lineNumber);
	
			// Remember where in the tree we are.
			$stack []= $node;
			$separatorStack []= $separators;
	
			// Tokenize the line. Skip comments and empty lines.
			while ($c != "\n") {
				// Check if this token begins with a quotation mark. If so, it will
				// include everything up to the next instance of that mark.
				$endQuote = $c;
				$isQuoted = ($endQuote == '"' || $endQuote == '`');
				if ($isQuoted) {
					$tokenPos = $pos;
					//$c = mb_substr($data, $pos++, 1);
					$c = mb_substr($line, $pos++, 1);
				}
	
				$endPos = $tokenPos;
	
				// Find the end of this token.
				while($c != "\n" && ($isQuoted ? ($c != $endQuote) : (mb_ord($c) > $spaceCode))) {
					$endPos = $pos;
					//$c = mb_substr($data, $pos++, 1);
					$c = mb_substr($line, $pos++, 1);
				}
	
				// It ought to be legal to construct a string from an empty iterator
				// range, but it appears that some libraries do not handle that case
				// correctly. So:
				//$newToken = mb_substr($data, $tokenPos, $endPos - $tokenPos);
				$newToken = mb_substr($line, $tokenPos, $endPos - $tokenPos);
				$node->addToken($newToken);
				
				// This is not a fatal error, but it may indicate a format mistake:
				if ($isQuoted && $c == "\n") {
					$node->printTrace("Warning: Closing quotation mark is missing:");
				}
	
				if ($c != "\n") {
					// If we've not yet reached the end of the line of text, search
					// forward for the next non-whitespace character.
					if ($isQuoted) {
						$tokenPos = $pos;
						//$c = mb_substr($data, $pos++, 1);
						$c = mb_substr($line, $pos++, 1);
					}
					while ($c != "\n" && mb_ord($c) <= $spaceCode && $c != '#') {
						$tokenPos = $pos;
						//$c = mb_substr($data, $pos++, 1);
						$c = mb_substr($line, $pos++, 1);
					}
	
					// If a comment is encountered outside of a token, skip the rest
					// of this line of the file.
					if ($c == '#') {
						while ($c != "\n") {
							//$c = mb_substr($data, $pos++, 1);
							$c = mb_substr($line, $pos++, 1);
						}
					}
				}
			}
	
			// Now that we've tokenized this node, print any mixed whitespace warnings.
			if ($mixedIndentation) {
				$node->printTrace("Warning: Mixed whitespace usage at line");
			}
		}
	}

}