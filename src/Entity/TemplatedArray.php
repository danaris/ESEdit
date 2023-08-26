<?php

namespace App\Entity;

use Doctrine\ORM\EntityManagerInterface;

class TemplatedArray implements \Iterator, \ArrayAccess, \Countable {
	private array $contents = [];
	private string $type = '';
	private string $nameColumn = '';
	
	private int $iterIndex = 0;
	
	private ?EntityManagerInterface $em = null;
	
	public function __construct($type, $nameColumn = 'name') {
		$this->type = $type;
		$this->nameColumn = $nameColumn;
	}
	
	public function getContents(): array {
		return $this->contents;
	}
	
	public function setEM(EntityManagerInterface $em) {
		$this->em = $em;
	}
	
	public function initContents(): void {
		if ($this->em) {
			$contentsQ = $this->em->createQuery('Select o from '.$this->type.' o index by o.'.$this->nameColumn);
			$this->contents = $contentsQ->getResult();
		}
	}
	
	public function has(mixed $offset): bool {
		return isset($this->contents[$offset]);
	}
	
	// ArrayAccess interface methods
	public function offsetExists(mixed $offset): bool {
		return true;
	}
	public function offsetGet(mixed $offset): mixed {
		if (!isset($this->contents[$offset])) {
			if ($this->em) {
				$Check = $this->em->getRepository($this->type)->findOneBy([$this->nameColumn => $offset]);
				if ($Check) {
					return $Check;
				}
			}
			$this->contents[$offset] = new $this->type();
			if ($this->em) {
				$this->em->persist($this->contents[$offset]);
			}
		}
		return $this->contents[$offset];
	}
	public function offsetSet(mixed $offset, mixed $value): void {
		if ($offset === null) {
			$this->contents []= $value;
		} else {
			$this->contents[$offset] = $value;
		}
	}
	public function offsetUnset(mixed $offset): void {
		unset($this->contents[$offset]);
	}
	
	// Iterator interface methods
	public function current(): mixed {
		return $this->contents[array_keys($this->contents)[$this->iterIndex]];
	}
	
	public function key(): string|int {
		return array_keys($this->contents)[$this->iterIndex];
	}
	
	public function next(): void {
		$this->iterIndex++;
	}
	
	public function rewind(): void {
		$this->iterIndex = 0;
	}
	
	public function valid(): bool {
		if ($this->iterIndex < 0) {
			return false;
		}
		if (!isset(array_keys($this->contents)[$this->iterIndex])) {
			return false;
		}
		$childKey = array_keys($this->contents)[$this->iterIndex];
		if (isset($this->contents[$childKey])) {
			return true;
		}
		
		return false;
	}	
	
	// Countable interface method
	public function count(): int {
		return count($this->contents);
	}
}