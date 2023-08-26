<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;
use App\Entity\DataWriter;

class ConditionsStore {
	private array $storage = []; // string => ConditionEntry array
	private array $providers = []; // string => DerivedProvider array

	public function __construct(DataNode $node = null, array /* [string, int] pairs */ $initialConditions = []) {
		if ($node) {
			$this->load($node);
		} else {
			foreach ($initialConditions as $pair) {
				$this->set($pair[0], $pair[1]);
			}
		}
	}
	
	public function load(DataNode $node): void {
		foreach ($node as $child) {
			$this->set($child->getToken(0), ($child->size() >= 2) ? $child->getValue(1) : 1);
		}
	}
	
	public function save(DataWriter $out) {
		$out->write("conditions");
		$out->beginChild();
		foreach ($this->storage as $storedName => $storedEntry) {
			// We don't need to save derived conditions that have a provider.
			if ($storedEntry->provider) {
				continue;
			}
			// If the condition's value is 0, don't write it at all.
			if (!$storedEntry->value) {
				continue;
			}
			// If the condition's value is 1, don't bother writing the 1.
			if ($storedEntry->value == 1) {
				$out->write($storedName);
			} else {
				$out->write([$storedName, $storedEntry->value]);
			}
		}
		$out->endChild();
	}
	
	// Get a condition from the Conditions-Store. Retrieves both conditions
	// that were directly set (primary conditions) as well as conditions
	// derived from other data-structures (derived conditions).
	public function get(string $name): int {
		$ce = $this->getEntry($name);
		if (!$ce) {
			return 0;
		}
	
		if (!$ce->provider) {
			return $ce->value;
		}
	
		return $ce->provider->getFunction($name);
	}
	
	public function has(string $name): bool {
		$ce = $this->getEntry($name);
		if (!$ce) {
			return false;
		}
	
		if (!$ce->provider) {
			return true;
		}
	
		return $ce->provider->hasFunction($name);
	}
	
	// Returns a pair where the boolean indicates if the game has this condition set,
	// and an int64_t which contains the value if the condition was set.
	public function hasGet(string $name): array {
		$ce = $this->getEntry($name);
		if (!$ce) {
			return [false, 0];
		}
	
		if (!$ce->provider) {
			return [true, $ce->value];
		}
	
		$has = $ce->provider->hasFunction($name);
		$val = 0;
		if ($has) {
			$val = $ce->provider->getFunction($name);
		}
	
		return [$has, $val];
	}
	
	// Add a value to a condition. Returns true on success, false on failure.
	public function add(string $name, int $value): bool {
		// This code performers 2 lookups of the condition, once for get and
		// once for set. This might be optimized to a single lookup in a
		// later version of the code.
		return $this->set($name, $this->get($name) + $value);
	}
	
	// Set a value for a condition, either for the local value, or by performing
	// a set on the provider.
	public function set(string $name, int $value): bool {
		$ce = $this->getEntry($name);
		if (!$ce) {
			$entry = new ConditionEntry();
			$entry->value = $value;
			$this->storage[$name] = $entry;
			return true;
		}
		if (!$ce->provider) {
			$ce->value = $value;
			return true;
		}
		return $ce->provider->setFunction($name, $value);
	}
	
	// Erase a condition completely, either the local value or by performing
	// an erase on the provider.
	public function erase(string $name): bool {
		$ce = $this->getEntry($name);
		if (!$ce) {
			return true;
		}
	
		if (!$ce->provider) {
			unset($this->storage[$name]);
			return true;
		}
		return $ce->provider->eraseFunction($name);
	}
	
	public function offsetExists(mixed $offset): bool {
		return isset($this->storage[$offset]) || $this->getEntry($offset) != null;
	}
	public function offsetGet(mixed $offset): mixed { // returns ConditionEntry
		// Search for an exact match and return it if it exists.
		if (isset($this->storage[$offset])) {
			return $this->storage[$offset];
		}
		
		// Check for a prefix provider.
		$ceprov = $this->getEntry($offset);
		// If no prefix provider is found, then just create a new value entry.
		if (!$ceprov) {
			$entry = new ConditionEntry();
			$this->storage[$offset] = $entry;
			return $entry;
		}
		
		// Found a matching prefixed entry provider, but no exact match for the entry itself,
		// let's create the exact match based on the prefix provider.
		$entry = new ConditionEntry();
		$this->storage[$offset] = $entry;
		$entry->provider = $ceprov->provider;
		$entry->fullKey = $offset;
		return $entry;
		
	}
	public function offsetSet(mixed $offset, mixed $value): void {
		// Search for an exact match and return it if it exists.
		if (isset($this->storage[$offset])) {
			$this->storage[$offset] = $value;
		}
		
		// Check for a prefix provider.
		$ceprov = $this->getEntry($offset);
		// If no prefix provider is found, then just create a new value entry.
		if (!$ceprov) {
			$this->storage[$offset] = $value;
		}
		
		// Found a matching prefixed entry provider, but no exact match for the entry itself,
		// let's create the exact match based on the prefix provider.
		$entry = new ConditionEntry();
		$this->storage[$offset] = $entry;
		$entry->provider = $ceprov->provider;
		$entry->fullKey = $offset;
		$entry->provider->setFunction($offset, $value);
	}
	public function offsetUnset(mixed $offset): void {
		// Search for an exact match and return it if it exists.
		if (isset($this->storage[$offset])) {
			unset($this->storage[$offset]);
		}
		
		// Check for a prefix provider.
		$ceprov = $this->getEntry($offset);
		// If no prefix provider is found, then just create a new value entry.
		if ($ceprov) {
			$ceprov->provider->eraseFunction($offset);
		}
	}
	
	// Build a provider for a given prefix.
	public function getProviderPrefixed(string $prefix): DerivedProvider {
		if (isset($this->providers[$prefix])) {
			$provider = $this->providers[$prefix];
		} else {
			$provider = new DerivedProvider($prefix, true);
		}
		if (!$provider->isPrefixProvider) {
			error_log("Error: Rewriting named provider \"" . $prefix . "\" to prefixed provider.");
			$provider->isPrefixProvider = true;
		}
		if ($this->verifyProviderLocation($prefix, $provider)) {
			$this->storage[$prefix]->provider = $provider;
			// Check if any matching later entries within the prefixed range use the same provider.
			$prefixLen = strlen($prefix);
			foreach ($this->storage as $storedName => $storedEntry) {
				if (substr($storedName, 0, $prefixLen) == $prefix) {
					if ($storedEntry->provider != $provider) {
						$storedEntry->provider = $provider;
						$storedEntry->fullKey = $storedName;
						// ?? what do we do with this? throw runtime_error("Replacing condition entries matching prefixed provider \"" + prefix + "\".");
						// guessing...
						throw new \Exception("Replacing condition entries matching prefixed provider \"" . prefix . "\".");
					}
				}
			}
		}
		return $provider;
	}
	
	// Build a provider for the condition identified by the given name.
	public function getProviderNamed(string $name): DerivedProvider {
		if (isset($this->providers[$prefix])) {
			$provider = $this->providers[$prefix];
		} else {
			$provider = new DerivedProvider($prefix, true);
		}
		if ($provider->isPrefixProvider) {
			error_log("Error: Retrieving prefixed provider \"" . name . "\" as named provider.");
			$provider->isPrefixProvider = true;
		} else if ($this->verifyProviderLocation($name, $provider)) {
			$this->storage[$name]->provider = $provider;
		}
		return $provider;
	}
	
	// Helper to completely remove all data and linked condition-providers from the store.
	public function clear(): void {
		$this->storage = [];
		$this->providers = [];
	}
	
	// Helper for testing; check how many primary conditions are registered.
	public function primariesSize(): int {
		$result = 0;
		foreach ($this->storage as $storedEntry) {
			// We only count primary conditions; conditions that don't have a provider.
			if ($storedEntry->provider) {
				continue;
			}
			++$result;
		}
		return $result;
	}
	
	public function getEntry(string $name): ?ConditionEntry {
		if (count($this->storage) == 0) {
			return null;
		}
		
		if (isset($this->storage[$name])) {
			return $this->storage[$name];
		}
		
		$prefixLen = strlen($name);
		foreach ($this->storage as $storedName => $storedEntry) {
			if ($storedEntry->provider && $storedEntry->provider->isPrefixProvider && substr($storedEntry->name, 0, $prefixLen) == $name) {
				return $storedEntry;
			}
		}
		
		return null;
	}
	
	// Helper function to check if we can safely add a provider with the given name.
	public function verifyProviderLocation(string $name, DerivedProvider $provider): bool {
		if (!isset($this->storage[$name])) {
			return true;
		}
		
		$prefixLen = strlen($name);
		foreach ($this->storage as $storedName => $storedEntry) {
			if (substr($storedEntry->name, 0, $prefixLen) == $name) {
				break;
			}
		}
	
		// If we find the provider we are trying to add, then it apparently
		// was safe to add the entry since it was already added before.
		if ($storedEntry->provider == $provider) {
			return true;
		}
	
		if (!$storedEntry->provider && $storedName == $name) {
			error_log("Error: overwriting primary condition \"" . $name . "\" with derived provider.");
			return true;
		}
	
		if ($storedEntry->provider && $storedEntry->provider->isPrefixProvider && substr($storedEntry->name, 0, $prefixLen) == $name) {
			throw new \Exception("Error: not adding provider for \"" . $name . "\", because it is within range of prefixed derived provider \"" . $storedEntry->provider->name . "\".");
		}
		return true;
	}
}

// Class for DerivedProviders, the (lambda) functions that provide access
// to the derived conditions are registered in this class.
class DerivedProvider {
	public $getFunction;
	public $setFunction;
	public $hasFunction;
	public $eraseFunction;
	
	// Default constructor
	public function __construct(protected string $name, 
								protected bool $isPrefixProvider) {
		$this->getFunction = function(string $name): int {
			return 0;
		};
		$this->setFunction = function(string $name, int $value): bool {
			return false;
		};
		$this->hasFunction = function(string $name): bool {
			return true;
		};
		$this->eraseFunction = function(string $name): bool {
			return false;
		};
	}
	
	public function setGetFunction($newGetFun): void {
		$this->getFunction = $newGetFun;
	}
	
	public function setHasFunction($newHasFun): void {
		$this->hasFunction = $newHasFun;
	}
	
	public function setSetFunction($newSetFun): void {
		$this->setFunction = $newSetFun;
	}
	
	public function setEraseFunction($newEraseFun): void {
		$this->eraseFunction = $newEraseFun;
	}
};


// Storage entry for a condition. Can act as a int64_t proxy when operator[] is used for access
// to conditions in the ConditionsStore.
class ConditionEntry {
	public int $value = 0;
	public ?DerivedProvider $provider = null;
	// The full keyname for condition we want to access. This full keyname is required
	// when accessing prefixed providers, because such providers will only know the prefix
	// part of the key.
	public string $fullKey = '';
	// int64_t proxy helper functions. Those functions allow access to the conditions
	// using `operator[]` on ConditionsStore.
	public function __invoke() {
		if (!$this->provider) {
			return $this->value;
		}
		
		$key = $this->fullKey == '' ? $this->provider->getName() : $this->fullKey;
		return $this->provider->getFunction($key);
	}
	public function set(int $val) {
		if (!$this->provider) {
			$this->value = $val;
		}
		
		$key = $this->fullKey == '' ? $this->provider->getName() : $this->fullKey;
		$this->provider->setFunction($key, $val);
	}
	public function increment() {
		if (!$this->provider) {
			$this->value++;
		}
		
		$key = $this->fullKey == '' ? $this->provider->getName() : $this->fullKey;
		$this->provider->setFunction($key, $this->provider->getFunction($key) + 1);
	}
	public function decrement() {
		if (!$this->provider) {
			$this->value--;
		}
		
		$key = $this->fullKey == '' ? $this->provider->getName() : $this->fullKey;
		$this->provider->setFunction($key, $this->provider->getFunction($key) - 1);
	}
	public function gain(int $val) {
		if (!$this->provider) {
			$this->value += $val;
		}
		
		$key = $this->fullKey == '' ? $this->provider->getName() : $this->fullKey;
		$this->provider->setFunction($key, $this->provider->getFunction($key) + $val);
	}
	public function reduce(int $val) {
		if (!$this->provider) {
			$this->value -= $val;
		}
		
		$key = $this->fullKey == '' ? $this->provider->getName() : $this->fullKey;
		$this->provider->setFunction($key, $this->provider->getFunction($key) - $val);
	}
};