<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'Phrase')]
#[ORM\HasLifecycleCallbacks]
class Phrase {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string')]
	private string $name = '';
	// Each time this phrase is defined, a new sentence is created.
	#[ORM\Column(type: 'text')]
	private string $sentencesStr = '';
	private array $sentences = []; // vector<Sentence>
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->sentencesStr = json_encode($this->sentences);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->sentences = json_decode($this->sentencesStr, true);
	}
		
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
	}
	
	public function load(DataNode $node): void {
		// Set the name of this phrase, so we know it has been loaded.
		$name = $node->size() >= 2 ? $node->getToken(1) : "Unnamed Phrase";
		// To avoid a possible parsing ambiguity, the interpolation delimiters
		// may not be used in a Phrase's name.
		if (strstr($name, '${') !== false || strstr($name, '}') !== false) {
			$node->printTrace('Error: Phrase names may not contain "${" or "}":');
			return;
		}
		
		// TODO: We'll get to this
	// 	$sentence = new Sentence($node, $this);
	// 
	// 	sentences.emplace_back(node, this);
	// 	if(sentences.back().empty())
	// 	{
	// 		sentences.pop_back();
	// 		node.PrintTrace("Error: Unable to parse node:");
	// 	}
	}
	
	// 
	// 
	// bool Phrase::IsEmpty() const
	// {
	// 	return sentences.empty();
	// }
	// 
	// 
	// 
	// // Get the name associated with the node this phrase was instantiated
	// // from, or "Unnamed Phrase" if it was anonymously defined.
	// const string &Phrase::Name() const
	// {
	// 	return name;
	// }
	// 
	// 
	// 
	// // Get a random sentence's text.
	// string Phrase::Get() const
	// {
	// 	string result;
	// 	if(sentences.empty())
	// 		return result;
	// 
	// 	for(const auto &part : sentences[Random::Int(sentences.size())])
	// 	{
	// 		if(!part.choices.empty())
	// 		{
	// 			const auto &choice = part.choices.Get();
	// 			for(const auto &element : choice)
	// 				result += element.second ? element.second->Get() : element.first;
	// 		}
	// 		else if(!part.replacements.empty())
	// 			for(const auto &pair : part.replacements)
	// 				Format::ReplaceAll(result, pair.first, pair.second);
	// 	}
	// 
	// 	return result;
	// }
	// 
	// 
	// 
	// // Inspect this phrase and all its subphrases to determine if a cyclic
	// // reference exists between this phrase and the other.
	// bool Phrase::ReferencesPhrase(const Phrase *other) const
	// {
	// 	if(other == this)
	// 		return true;
	// 
	// 	for(const auto &sentence : sentences)
	// 		for(const auto &part : sentence)
	// 			for(const auto &choice : part.choices)
	// 				for(const auto &element : choice)
	// 					if(element.second && element.second->ReferencesPhrase(other))
	// 						return true;
	// 
	// 	return false;
	// }
	// 	
}

// A Choice represents one entry in a Phrase definition's "word" or "phrase" child
// node. If from a "word" node, a Choice may be pure text or contain embedded phrase
// references, e.g. `"I'm ${pirate} and I like '${band}' concerts."`.

class Choice { //: private vector<pair<string, const Phrase *>> {
// public:
// 	// Create a choice from a grandchild DataNode.
// 	Choice(const DataNode &node, bool isPhraseName = false);
// 
// 	// Enable empty checks and iteration:
// 	using vector<pair<string, const Phrase *>>::empty;
// 	using vector<pair<string, const Phrase *>>::begin;
// 	using vector<pair<string, const Phrase *>>::end;
	// Phrase::Choice::Choice(const DataNode &node, bool isPhraseName)
	// {
	// 	// The given datanode should not have any children.
	// 	if(node.HasChildren())
	// 		node.begin()->PrintTrace("Skipping unrecognized child node:");
	// 
	// 	if(isPhraseName)
	// 	{
	// 		emplace_back(string{}, GameData::Phrases().Get(node.Token(0)));
	// 		return;
	// 	}
	// 
	// 	// This node is a text string that may contain an interpolation request.
	// 	const string &entry = node.Token(0);
	// 	if(entry.empty())
	// 	{
	// 		// A blank choice was desired.
	// 		emplace_back();
	// 		return;
	// 	}
	// 
	// 	size_t start = 0;
	// 	while(start < entry.length())
	// 	{
	// 		// Determine if there is an interpolation request in this string.
	// 		size_t left = entry.find("${", start);
	// 		if(left == string::npos)
	// 			break;
	// 		size_t right = entry.find('}', left);
	// 		if(right == string::npos)
	// 			break;
	// 
	// 		// Add the text up to the ${, and then add the contained phrase name.
	// 		++right;
	// 		size_t length = right - left;
	// 		auto text = string{entry, start, left - start};
	// 		auto phraseName = string{entry, left + 2, length - 3};
	// 		emplace_back(text, nullptr);
	// 		emplace_back(string{}, GameData::Phrases().Get(phraseName));
	// 		start = right;
	// 	}
	// 	// Add the remaining text to the sequence.
	// 	if(entry.length() - start > 0)
	// 		emplace_back(string{entry, start, entry.length() - start}, nullptr);
	// }
};

// A Part represents a the content contained by a "word", "phrase", or "replace" child node.
class Part {
	// Sources of text, either literal or via phrase invocation.
	public array $choices; //WeightedList<Choices>
	// Character sequences that should be replaced, e.g. "llo"->"y"
	// would transform "Hello hello" into "Hey hey"
	public array $replacements; //vector<pair<string, string>>
}


// An individual definition associated with a Phrase name.
class Sentence {
	// // Forwarding constructor, for use with emplace/emplace_back.
	// Phrase::Sentence::Sentence(const DataNode &node, const Phrase *parent)
	// {
	// 	Load(node, parent);
	// }
	// 
	// 
	// 
	// // Parse the children of the given node to populate the sentence's structure.
	// void Phrase::Sentence::Load(const DataNode &node, const Phrase *parent)
	// {
	// 	for(const DataNode &child : node)
	// 	{
	// 		if(!child.HasChildren())
	// 		{
	// 			child.PrintTrace("Skipping node with no children:");
	// 			continue;
	// 		}
	// 
	// 		emplace_back();
	// 		auto &part = back();
	// 
	// 		if(child.Token(0) == "word")
	// 			for(const DataNode &grand : child)
	// 				part.choices.emplace_back((grand.Size() >= 2) ? max<int>(1, grand.Value(1)) : 1, grand);
	// 		else if(child.Token(0) == "phrase")
	// 			for(const DataNode &grand : child)
	// 				part.choices.emplace_back((grand.Size() >= 2) ? max<int>(1, grand.Value(1)) : 1, grand, true);
	// 		else if(child.Token(0) == "replace")
	// 			for(const DataNode &grand : child)
	// 				part.replacements.emplace_back(grand.Token(0), (grand.Size() >= 2) ? grand.Token(1) : string{});
	// 		else
	// 			child.PrintTrace("Skipping unrecognized attribute:");
	// 
	// 		// Require any newly added phrases have no recursive references. Any recursions
	// 		// will instead yield an empty string, rather than possibly infinite text.
	// 		for(auto &choice : part.choices)
	// 			for(auto &element : choice)
	// 				if(element.second && element.second->ReferencesPhrase(parent))
	// 				{
	// 					child.PrintTrace("Warning: Replaced recursive '" + element.second->Name() + "' phrase reference with \"\":");
	// 					element.second = nullptr;
	// 				}
	// 
	// 		// If no words, phrases, or replaces were given, discard this part of the phrase.
	// 		if(part.choices.empty() && part.replacements.empty())
	// 			pop_back();
	// 	}
	// }
// public:
// 	Sentence(const DataNode &node, const Phrase *parent);
// 	void Load(const DataNode &node, const Phrase *parent);
	public array $parts;
};