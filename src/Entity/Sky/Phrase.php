<?php

namespace App\Entity\Sky;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'Phrase')]
class Phrase {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string')]
	private string $name = '';
	// Each time this phrase is defined, a new sentence is created.
    #[ORM\OneToMany(mappedBy: 'phrase', targetEntity: PhraseSentence::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $sentences;
	//private array $sentences = []; // vector<Sentence>
		
	// public:
	// 	// Replace all occurrences ${phrase name} with the expanded phrase from GameData::Phrases()
	// 	static string ExpandPhrases(const string &source);

	// Replace all occurrences ${phrase name} with the expanded phrase from GameData::Phrases()
	// string Phrase::ExpandPhrases(const string &source)
	// {
	// 	string result;
	// 	size_t next = 0;
	// 	while(next < source.length())
	// 	{
	// 		size_t var = source.find("${", next);
	// 		if(var == string::npos)
	// 			break;
	// 		else if(var > next)
	// 			result.append(source, next, var - next);
	// 		next = source.find('}', var);
	// 		if(next == string::npos)
	// 			break;
	// 		++next;
	// 		string phraseName = string{source, var + 2, next - var - 3};
	// 		const Phrase *phrase = GameData::Phrases().Find(phraseName);
	// 		result.append(phrase ? phrase->Get() : phraseName);
	// 	}
	// 	// Optimization for most common case: no phrase in string:
	// 	if(!next)
	// 		return source;
	// 	else if(next < source.length())
	// 		result.append(source, next, string::npos);
	// 	return result;
	// }
	// 
	
	
	public function __construct(?DataNode $node = null) {
		if ($node !== null) {
			$this->load($node);
		}
		$this->sentences = new ArrayCollection();
	}
	
	public function load(DataNode $node): void {
		// Set the name of this phrase, so we know it has been loaded.
		$this->name = $node->size() >= 2 ? $node->getToken(1) : "Unnamed Phrase";
		// To avoid a possible parsing ambiguity, the interpolation delimiters
		// may not be used in a Phrase's name.
		if (strstr($this->name, '${') !== false || strstr($this->name, '}') !== false) {
			$node->printTrace('Error: Phrase names may not contain "${" or "}":');
			return;
		}
		
		$sentence = new PhraseSentence($node, $this);
		
		if (count($sentence->getParts()) == 0) {
			$node->printTrace("Error: Unable to parse node:");
		} else {
			$this->sentences []= $sentence;
		}
	}
	
	public function isEmpty(): bool {
		return count($this->sentences) == 0;
	}
	
	// Get the name associated with the node this phrase was instantiated
	// from, or "Unnamed Phrase" if it was anonymously defined.
	public function getName(): string {
		return $this->name;
	}

	// Get a random sentence's text.
	public function get(): string {
		$result = '';
		if (count($this->sentences) == 0) {
			return $result;
		}
	
		$randIndex = Random::Int(count($this->sentences));
		foreach ($this->sentences[$randIndex] as $part) {
			if (count($part->getChoices()) > 0) {
				$choice = $part->getChoice();
				for ($i=0; $i<count($choice->getPhrases); $i++) {
					$element = $choice->get($i);
					$result += $element[1] ? $element[1]->get() : $element[0];
				}
			} else if (count($part->getReplacements()) > 0) {
				foreach ($part->getReplacements() as $pair) {
					$result = Format::ReplaceAll($result, $pair[0], $pair[1]);
				}
			}
		}
	
		return $result;
	}
	
	// Inspect this phrase and all its subphrases to determine if a cyclic
	// reference exists between this phrase and the other.
	public function referencesPhrase(Phrase $other): bool {
		if ($other == $this) {
			return true;
		}
		foreach ($this->sentences as $Sentence) {
			foreach ($Sentence->getParts() as $Part) {
				foreach ($Part->getChoices() as $Choice) {
					for ($i=0; $i<$Choice->count(); $i++) {
						$element = $Choice->get($i);
						if ($element[1] && $element[1]->referencesPhrase($other)) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}
	
    /**
     * @return Collection<int, PhraseSentence>
     */
    public function getSentences(): Collection
    {
        return $this->sentences;
    }

    public function addSentence(PhraseSentence $phraseSentence): static
    {
        if (!$this->sentences->contains($phraseSentence)) {
            $this->sentences->add($phraseSentence);
            $phraseSentence->setPhrase($this);
        }

        return $this;
    }

    public function removeSentence(PhraseSentence $phraseSentence): static
    {
        if ($this->sentences->removeElement($phraseSentence)) {
            // set the owning side to null (unless already changed)
            if ($phraseSentence->getPhrase() === $this) {
                $phraseSentence->setPhrase(null);
            }
        }

        return $this;
    }
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		$jsonArray['name'] = $this->name;
		
		$jsonArray['sentences'] = [];
		foreach ($this->sentences as $Sentence) {
			$jsonArray['sentences'] []= $Sentence->toJSON(true);
		}
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
}
// 
// 
// // An individual definition associated with a Phrase name.
// class Sentence {
// 	
// 	//public array $parts;
// 	
// 	// Forwarding constructor, for use with emplace/emplace_back.
// 	public function __construct(DataNode $node, ?Phrase $parent = null) {
// 		$this->load($node, $parent);
// 	}
// 	
// 	// Parse the children of the given node to populate the sentence's structure.
// 	public function load(DataNode $node, ?Phrase $parent) {
// 		foreach ($node as $child) {
// 			if (!$child->hasChildren()) {
// 				$child->printTrace("Skipping node with no children:");
// 				continue;
// 			}
// 			$Part = new Part();
// 	
// 			if ($child->getToken(0) == "word") {
// 				foreach ($child as $grand) {
// 					$Choice = new Choice($grand, false);
// 					$Choice->setPart($Part);
// 					$Part->choices []= $Choice;
// 					$Part->choiceWeights []= ($grand->size() >= 2 ? max(1, $grand->getValue(1)) : 1);
// 				}
// 			} else if ($child->getToken(0) == "phrase") {
// 				foreach ($child as $grand) {
// 					$Choice = new Choice($grand, true);
// 					$Choice->setPart($Part);
// 					$Part->choices []= $Choice;
// 					$Part->choiceWeights []= ($grand->size() >= 2 ? max(1, $grand->getValue(1)) : 1);
// 				}
// 			} else if ($child->getToken(0) == "replace") {
// 				foreach ($child as $grand) {
// 					$Part->replacements []= [$grand->getToken(0), ($grand->size() >= 2) ? $grand->getToken(1) : ''];
// 				}
// 			} else {
// 				$child->printTrace("Skipping unrecognized attribute:");
// 			}
// 			// Require any newly added phrases have no recursive references. Any recursions
// 			// will instead yield an empty string, rather than possibly infinite text.
// 			foreach ($Part->choices as $Choice) {
// 				foreach ($Choice->phraseNames as $nameIndex => $phraseName) {
// 					$phrase = $Choice->phrases[$nameIndex];
// 					if ($phrase && $phrase->referencesPhrase($parent)) {
// 						$child->printTrace("Warning: Replaced recursive '" . $phrase->getName() . "' phrase reference with \"\":");
// 						array_splice($Choice->phraseNames, $nameIndex, 1);
// 						array_splice($Choice->phrases, $nameIndex, 1);
// 					}
// 				}
// 			}
// 			// If no words, phrases, or replaces were given, discard this part of the phrase.
// 			if (count($Part->choices) > 0 || count($Part->replacements) > 0) {
// 				$Part->setSentence($this);
// 				$this->parts []= $Part;
// 			}
// 		}
// 	}
// 	
// }

// A Choice represents one entry in a Phrase definition's "word" or "phrase" child
// node. If from a "word" node, a Choice may be pure text or contain embedded phrase
// references, e.g. `"I'm ${pirate} and I like '${band}' concerts."`.

// class Choice { //: private vector<pair<string, const Phrase *>> {
// 	
// 	//public array $phraseNames = [];
// 	//public array $phrases = [];
// // public:
// // 	// Create a choice from a grandchild DataNode.
// // 	Choice(const DataNode &node, bool isPhraseName = false);
// // 
// // 	// Enable empty checks and iteration:
// // 	using vector<pair<string, const Phrase *>>::empty;
// // 	using vector<pair<string, const Phrase *>>::begin;
// // 	using vector<pair<string, const Phrase *>>::end;
// 	public function __construct(DataNode $node, bool $isPhraseName) {
// 		// The given datanode should not have any children.
// 		if ($node->hasChildren()) {
// 			$node[0]->printTrace("Skipping unrecognized child node:");
// 		}
// 	
// 		if ($isPhraseName) {
// 			$this->phraseNames []= '';
// 			$this->phrases []= GameData::Phrases()[$node->getToken(0)];
// 			return;
// 		}
// 	
// 		// This node is a text string that may contain an interpolation request.
// 		$entry = $node->getToken(0);
// 		if($entry == '') {
// 			// A blank choice was desired.
// 			$this->phraseNames []= '';
// 			$this->phrases []= null;
// 			return;
// 		}
// 	
// 		$start = 0;
// 		$entryLength = strlen($entry);
// 		while ($start < $entryLength) {
// 			// Determine if there is an interpolation request in this string.
// 			$left = strpos($entry, '${', $start);
// 			if ($left >= $entryLength) {
// 				break;
// 			}
// 			$right = strpos($entry, '}', $left);
// 			if ($right >= $entryLength) {
// 				break;
// 			}
// 	
// 			// Add the text up to the ${, and then add the contained phrase name.
// 			++$right;
// 			$length = $right - $left;
// 			$text = substr($entry, $start, $left - $start);
// 			$phraseName = substr($entry, $left + 2, $length - 3);
// 			$this->phraseNames []= $text;
// 			$this->phrases []= null;
// 			$this->phraseNames []= '';
// 			$this->phrases []= GameData::Phrases()[$phraseName];
// 			$start = $right;
// 		}
// 		// Add the remaining text to the sequence.
// 		if(strlen($entry) - $start > 0) {
// 			$this->phraseNames []= substr($entry, $start);
// 			$this->phrases []= $null;
// 		}
// 	}
// }

// A Part represents a the content contained by a "word", "phrase", or "replace" child node.
// class Part {
// 	// Sources of text, either literal or via phrase invocation.
// 	//public array $choices = []; //WeightedList<Choices>
// 	//public array $choiceWeights = [];
// 	// Character sequences that should be replaced, e.g. "llo"->"y"
// 	// would transform "Hello hello" into "Hey hey"
// 	//public array $replacements = []; //vector<pair<string, string>>
// }