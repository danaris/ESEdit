<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;
use App\Entity\DataWriter;

#[ORM\Entity]
#[ORM\Table(name: 'Conversation')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Conversation {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string', nullable: true)]
	public ?string $name = null;
	
	// While parsing the conversation, keep track of what labels link to what
	// nodes. If a name appears in a goto before that label appears, remember
	// what node and what element it appeared at in order to link it up later.
	protected array $labels = [];
	protected array $unresolved = [];
	// The actual conversation data:
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\Node', mappedBy: 'conversation', cascade: ['persist'], fetch: 'EAGER')]
	protected Collection $nodes;
	
	#[ORM\Column(type: 'string')]
	private string $sourceName = '';
	#[ORM\Column(type: 'string')]
	private string $sourceFile = '';
	#[ORM\Column(type: 'string')]
	private string $sourceVersion = '';
	
	#[ORM\OneToOne(targetEntity: MissionAction::class, mappedBy: 'conversation')]
	#[ORM\JoinColumn(nullable: true, name: 'missionActionId')]
	protected ?MissionAction $missionAction = null;
	
	#[ORM\OneToOne(targetEntity: NPC::class, mappedBy: 'conversation')]
	#[ORM\JoinColumn(nullable: true, name: 'npcId')]
	protected ?NPC $npc = null;
	
	#[ORM\Column(type: 'text')]
	protected string $labelsStr = '';
	protected array $labelNames = [];
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->labelsStr = json_encode($this->labels);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->labels = json_decode($this->labelsStr, true);
		foreach ($this->labels as $label => $labelIndex) {
			$this->labelNames[$labelIndex] = $label;
		}
	}
	
	const ACCEPT = -1;
	const DECLINE = -2;
	const DEFER = -3;
	// These 3 options force the player to TakeOff (if landed), or cause
	// the boarded NPCs to explode, in addition to respectively duplicating
	// the above mission outcomes.
	const LAUNCH = -4;
	const FLEE = -5;
	const DEPART = -6;
	// The player may simply die (if landed on a planet or captured while
	// in space), or the flagship might also explode.
	const DIE = -7;
	const EXPLODE = -8;
	
	public static array $tokenIndex = [
		"accept" => Conversation::ACCEPT,
		"decline" => Conversation::DECLINE,
		"defer" => Conversation::DEFER,
		"launch" => Conversation::LAUNCH,
		"flee" => Conversation::FLEE,
		"depart" => Conversation::DEPART,
		"die" => Conversation::DIE,
		"explode" => Conversation::EXPLODE,
	];
	
	// Get the index of the given special string. 0 means it is "goto", a number
	// less than 0 means it is an outcome, and 1 means no match.
	public static function TokenIndex(string $token): int {
		if (isset(self::$tokenIndex[$token])) {
			return self::$tokenIndex[$token];
		}
		return 0;
	}
	
	// Map an index back to a string, for saving the conversation to a file.
	public static function TokenName(int $index): string {
		foreach (self::$tokenIndex as $tokenName => $tokenType) {
			if ($tokenType == $index) {
				return $tokenName;
			}
		}
	
		return '' . $index;
	}
	
	// Write a "goto" or endpoint.
	public static function WriteToken(int $index, DataWriter $out): void {
		$out->beginChild();
		//{
			if ($index >= 0) {
				$out->write(["goto", $index]);
			} else {
				$out->write(self::TokenName($index));
			}
		//}
		$out->endChild();
	}
	
	public function __construct(?DataNode $node = null, ?string $missionName = null) {
		$this->nodes = new ArrayCollection();
		if ($node && $missionName !== null) {
			$this->load($node, $missionName);
		}
	}
	
	public function getId(): int {
		return $this->id;
	}
	public function setId(int $id): void {
		$this->id = $id;
	}
	
	public function getName(): ?string {
		return $this->name;
	}
	
	public function getSourceName(): string {
		return $this->sourceName;
	}
	public function setSourceName(string $sourceName): self {
		$this->sourceName = $sourceName;
		return $this;
	}
	
	public function getSourceFile(): string {
		return $this->sourceFile;
	}
	public function setSourceFile(string $sourceFile): self {
		$this->sourceFile = $sourceFile;
		return $this;
	}
	
	public function getSourceVersion(): string {
		return $this->sourceVersion;
	}
	public function setSourceVersion(string $sourceVersion): self {
		$this->sourceVersion = $sourceVersion;
		return $this;
	}
	
	public function getLabelForIndex(int $labelIndex): ?string {
		if (isset($this->labelNames[$labelIndex])) {
			return $this->labelNames[$labelIndex];
		}
		return null;
	}
	
	public function getLabels(): array {
		return $this->labels;
	}
	
	public function getLabelNames(): array {
		return $this->labelNames;
	}
	
	// Load a conversation from file.
	public function load(DataNode $node, string $missionName = '') {
		// Make sure this really is a conversation specification.
		if ($node->getToken(0) != "conversation") {
			error_log('#CL ['.$node->getToken(0).'] not a conversation node');
			return;
		}
		
		if ($node->size() >= 2) {
			$this->name = $node->getToken(1);
		} /*else {
			error_log('Nameless conversation');
		}*/
		error_log('#CL Processing conversation '.$this->name);
		if ($node->getSourceName()) {
			$this->sourceName = $node->getSourceName();
			$this->sourceFile = $node->getSourceFile();
			$this->sourceVersion = $node->getSourceVersion();
		}
	
		// Free any previously loaded data.
		$this->nodes->clear();
	
		foreach ($node as $child) {
			$child->printTrace('#CL processing node: ');
			if ($child->getToken(0) == "scene" && $child->size() >= 2) {
				// A scene always starts a new text node.
				$this->addNode();
				$lastNode = $this->nodes[array_key_last($this->nodes->toArray())];
				$lastNode->scene = SpriteSet::Get($child->getToken(1));
				error_log('#CL Added scene '.$lastNode->scene);
			} else if ($child->getToken(0) == "label" && $child->size() >= 2) {
				// You cannot merge text above a label with text below it.
				if (count($this->nodes) > 0) {
					$lastNode = $this->nodes[array_key_last($this->nodes->toArray())];
					$lastNode->canMergeOnto = false;
				}
				$this->addLabel($child->getToken(1), $child);
				error_log('#CL Added label '.$child->getToken(1));
			} else if ($child->getToken(0) == "choice") {
				// Create a new node with one or more choices in it.
				$convNode = new Node(true);
				$convNode->setConversation($this);
				$this->nodes []= $convNode;
				$foundErrors = false;
				foreach ($child as $grand) {
					// Check for common errors such as indenting a goto incorrectly:
					if ($grand->size() > 1) {
						$grand->printTrace("Error: Conversation choices should be a single token:");
						$foundErrors = true;
						continue;
					}
	
					// Store the text of this choice. By default, the choice will
					// just bring you to the next node in the script.
					$element = new Element();
					$element->setNode($convNode);
					$element->text = $grand->getToken(0) . "\n";
					$element->next = count($this->nodes);
					$convNode->elements []= $element;
					
					$this->loadDestinations($grand);
				}
				if (count($convNode->elements) == 0) {
					if (!$foundErrors) {
						$child->printTrace("Warning: Conversation contains an empty \"choice\" node:");
					}
					$this->nodes->removeElement($convNode);
				}
				error_log('#CL Added choice node');
			} else if ($child->getToken(0) == "name") {
				// A name entry field is just represented as an empty choice node.
				$convNode = new Node(true);
				$convNode->setConversation($this);
				$this->nodes []= $convNode;
			} else if ($child->getToken(0) == "branch") {
				// Don't merge "branch" nodes with any other nodes.
				$convNode = new Node();
				$convNode->setConversation($this);
				$convNode->branchName = $child->getToken(1);
				$this->nodes []= $convNode;
				$convNode->canMergeOnto = false;
				$convNode->conditions->load($child);
				// A branch should always specify what node to go to if the test is
				// true, and may also specify where to go if it is false.
				for ($i = 1; $i <= 2; ++$i) {
					// If no link is provided, just go to the next node.
					$element = new Element();
					$element->setNode($convNode);
					$element->text = "";
					$element->next = count($this->nodes);
					$convNode->elements []= $element;
					if ($child->size() > $i) {
						$index = self::TokenIndex($child->getToken($i));
						if (!$index) {
							$this->goto($child->getToken($i), count($this->nodes) - 1, $i - 1);
						} else if ($index < 0) {
							$element->next = $index;
						}
					}
				}
				error_log('#CL Added branch node to '.$convNode->branchName);
			} else if ($child->getToken(0) == "action" || $child->getToken(0) == "apply") {
				if ($child->getToken(0) == "apply") {
					$child->printTrace("Warning: `apply` is deprecated syntax. Use `action` instead to ensure future compatibility.");
				}
				// Don't merge "action" nodes with any other nodes. Allow the legacy keyword "apply," too.
				$this->addNode();
				$lastNode = count($this->nodes) > 0 ? $this->nodes[array_key_last($this->nodes->toArray())] : null;
				$lastNode->canMergeOnto = false;
				$lastNode->actions->load($child, $missionName);
				error_log('#CL Added action/apply node');
			} else if ($child->size() > 1) {
				// Check for common errors such as indenting a goto incorrectly:
				$child->printTrace("Error: Conversation text should be a single token:");
			} else {
				// This is just an ordinary text node.
				// If the previous node is a choice, or if the previous node ended
				// in a goto, or if the new node has a condition, then create a new
				// node. Otherwise, just merge this new paragraph into the previous
				// node.
				$lastNode = count($this->nodes) > 0 ? $this->nodes[array_key_last($this->nodes->toArray())] : null;
				if (count($this->nodes) == 0 || !$lastNode->canMergeOnto || $this->hasDisplayRestriction($child)) {
					$this->addNode();
				}
				
				$lastNode = count($this->nodes) > 0 ? $this->nodes[array_key_last($this->nodes->toArray())] : null;
				$lastElement = count($lastNode->elements) > 0 ? $lastNode->elements[array_key_last($lastNode->elements->toArray())] : null;
				// Always append a newline to the end of the text.
				$lastElement->text .= $child->getToken(0) . "\n";
	
				// Check whether there is a goto attached to this block of text. If
				// so, future nodes can't merge onto this one.
				if ($this->loadDestinations($child)) {
					$lastNode->canMergeOnto = false;
				}
				error_log('#CL Added text node');
			}
		}
	
		// Display a warning if a label was not resolved.
		if (count($this->unresolved) > 0) {
			foreach ($this->unresolved as $unLabel => $unPair) {
				$node->printTrace("Warning: Conversation contains unrecognized label \"" . $unLabel . "\":");
			}
		}
	
		// Check for any loops in the conversation.
		foreach ($this->labels as $labelName => $labelId) {
			$nodeIndex = $labelId;
			while ($nodeIndex >= 0 && $this->choices($nodeIndex) <= 1) {
				$nodeIndex = $this->nextNodeForChoice($nodeIndex);
				if ($nodeIndex == $labelId) {
					$node->printTrace("Error: Conversation contains infinite loop beginning with label \"" . $labelName . "\":");
					$this->nodes->clear();
					return;
				}
			}
		}
	
		// Free the working buffers that we no longer need.
		$this->unresolved = [];
	}
	
	// Write a conversation to file.
	public function save(DataWriter $out): void {
		$out->write(["conversation"]);
		$out->beginChild();
		
		for ($i = 0; $i < count($this->nodes); ++$i) {
			// The original label names are not preserved anywhere. Instead,
			// the label for every node is just its node index.
			$out->write(["label", $i]);
			$node = $this->nodes[$i];

			if ($node->scene) {
				$out->write(["scene", $node->scene->getName()]);
			}
			if ($this->isBranch($i)) {
				$out->write(["branch", $this->tokenName($node->elements[0]->next), $this->tokenName($node->elements[1]->next)]);
				// Write the condition set as a child of this node.
				$out->beginChild(); {
					$node->conditions->save($out);
				}
				$out->endChild();
				continue;
			}
			if (!$node->actions->isEmpty()) {
				$out->write("action");
				// Write the GameAction as a child of this node.
				$out->beginChild();
				
				$node->actions->save($out);
				
				$out->endChild();
				continue;
			}
			if ($node->isChoice) {
				$out->write([count($node->elements) == 0 ? "name" : "choice"]);
				$out->beginChild();
			}
			foreach ($node->elements as $el) {
				// Break the text up into paragraphs.
				$elLines = explode("\n", $el->text);
				foreach ($elLines as $line) {
					if ($line === '') {
						continue;
					}
					$out->write($line, true);
					// If the conditions are the same, output them for each
					// paragraph. (We currently don't merge paragraphs with
					// identical ConditionSets, but some day we might.
					if (!$el->conditions->isEmpty()) {
						$out->beginChild();
						
							$out->write(["to", "display"], true);
							$out->beginChild();
							
								$el->conditions->save($out);
							
							$out->endChild();
						
						$out->endChild();
					}
				}
				// Check what node the conversation goes to after this.
				$index = $el->next;
				if ($index > 0 && !$this->nodeIsValid($index)) {
					$index = Conversation::DECLINE;
				}

				// Write the node that we go to next after this.
				$this->writeToken($index, $out);
			}
			if ($node->isChoice) {
				$out->endChild();
			}
		}
		
		$out->endChild();
	}
	
	// Parse the children of the given node to see if then contain any "gotos," or
	// "to shows." If so, link them up properly. Return true if gotos or
	// conditions were found.
	public function loadDestinations(DataNode $node): bool {
		$hasGoto = false;
		$hasCondition = false;
		foreach ($node as $child) {
			if ($child->size() == 2 && $child->getToken(0) == "goto" && $hasGoto) {
				$child->printTrace("Warning: Ignoring extra endpoint in conversation choice:");
			} else if($child->size() == 2 && $child->getToken(0) == "goto") {
				$this->goto($child->getToken(1), count($this->nodes) - 1, count($this->nodes[array_key_last($this->nodes->toArray())]->elements) - 1);
				$hasGoto = true;
			} else if ($child->size() == 2 && $child->getToken(0) == "to" && $child->getToken(1) == "display" && $hasCondition) {
				// Each choice can only have one condition
				$child->printTrace("Warning: Ignoring extra condition in conversation choice:");
			} else if ($child->size() == 2 && $child->getToken(0) == "to" && $child->getToken(1) == "display") {
				$lastNode = $this->nodes[array_key_last($this->nodes->toArray())];
				$lastElement = $lastNode->elements[array_key_last($lastNode->elements->toArray())];
				$lastElement->conditions->load($child);
				$lastElement->conditions->setConversationNodeElement($lastElement);
				$hasCondition = true;
			} else {
				// Check if this is a recognized endpoint name.
				$index = self::TokenIndex($child->getToken(0));
				if ($child->size() == 1 && $index < 0) {
					if ($hasGoto) {
						$child->printTrace("Warning: Ignoring extra endpoint in conversation choice:");
					} else {
						$lastNode = $this->nodes[array_key_last($this->nodes->toArray())];
						$lastElement = $lastNode->elements[array_key_last($lastNode->elements->toArray())];
						$lastElement->next = $index;
						$hasGoto = true;
					}
				} else {
					$child->printTrace("Warning: Expected goto, to show, or endpoint in conversation, found this:");
				}
			}
		}
		return $hasGoto || $hasCondition;
	}
	
	// Check if this conversation contains any data.
	public function isEmpty(): bool {
		return count($this->nodes) == 0;
	}
	
	// Check if this conversation contains a name prompt, and thus can be used as an "intro" conversation.
	public function isValidIntro(): bool {
		foreach ($this->nodes as $node) {
			if ($node->isChoice && count($node->elements) == 0) {
				return true;
			}
		}
		return false;
	}
	
	// Check if the actions in this conversation are valid.
	public function validate(): string {
		foreach ($this->nodes as $node) {
			if ($node->actions->isEmpty()) {
				$reason = $node->actions->validate();
				if ($reason) {
					return "conversation action " . $reason;
				}
			}
		}
		return "";
	}
	
	// Check if the given conversation node is a choice node.
	public function isChoice($node): bool {
		if (!$this->nodeIsValid($node)) {
			return false;
		}
	
		return $this->nodes[$node]->isChoice;
	}
	
	// Check if the given conversation node is a choice node.
	public function hasAnyChoices(ConditionsStore $vars, int $node): bool {
		if (!$this->nodeIsValid($node)) {
			return false;
		}
	
		if (!$this->nodes[$node]->isChoice) {
			return false;
		}
	
		if (count($this->nodes[$node]->elements)) {
			// A zero-length choice is a special case: it sets the player's name.
			return true;
		}
	
		foreach ($this->nodes[$node]->elements as $data) {
			if ($data->conditions->isEmpty()) {
				return true;
			} 
			if ($data->conditions->test($vars)) {
				return true;
			}
		}
	
		return false;
	}
	
	// If the given node is a choice node, check how many choices it offers.
	public function choices(int $node): int {
		if (!$this->nodeIsValid($node)) {
			return 0;
		}
	
		return $this->nodes[$node]->isChoice ? count($this->nodes[$node]->elements) : 0;
	}
	
	// Check if the given conversation node is a conditional branch.
	public function isBranch(int $node): bool {
		if (!$this->nodeIsValid($node)) {
			return false;
		}
	
		return !$this->nodes[$node]->conditions->isEmpty() && count($this->nodes[$node]->elements) > 1;
	}
	
	// Check if the given conversation node performs an action.
	public function isAction(int $node): bool {
		if(!$this->nodeIsValid($node)) {
			return false;
		}
	
		return !$this->nodes[$node]->actions->isEmpty();
	}
	
	// Get the list of conditions that the given node tests.
	public function conditions(int $node): ConditionSet {
		$empty = new ConditionSet();
		if (!$this->nodeIsValid($node)) {
			return $empty;
		}
	
		return $this->nodes[$node]->conditions;
	}
	
	// Get the action that the given node applies.
	public function getAction(int $node): GameAction {
		$empty = new GameAction();
		if(!$this->nodeIsValid($node)) {
			return $empty;
		}
	
		return $this->nodes[$node]->actions;
	}
	
	// Get the text of the given element of the given node.
	public function text(int $node, int $element): string {
		$empty = '';
	
		if (!$this->nodeIsValid($node) || !$this->elementIsValid($node, $element)) {
			return $empty;
		}
	
		return $this->nodes[$node]->elements[$element]->text;
	}
	
	// Get the scene image, if any, associated with the given node.
	public function scene(int $node): Sprite {
		if (!$this->nodeIsValid($node)) {
			return null;
		}
	
		return $this->nodes[$node]->scene;
	}
	
	public function getNodes(): Collection {
		return $this->nodes;
	}	
	
	// Find out where the conversation goes if the given option is chosen.
	public function nextNodeForChoice(int $node, int $element = -1): int {
		if(!$this->nodeIsValid($node) || !$this->elementIsValid($node, $element)) {
			return Conversation::DECLINE;
		}
	
		return $this->nodes[$node]->elements[$element]->next;
	}
	
	// Go to the next node of the conversation, ignoring any choices.
	public function stepToNextNode(int $node): int {
		$next_node = $node+1;
	
		if (!$this->nodeIsValid($next_node)) {
			return Conversation::DECLINE;
		}
	
		return $next_node;
	}
	
	// Returns whether the given node should be displayed.
	public function shouldDisplayNode(ConditionsStore $vars, int $node, int $element): bool {
		if (!$this->nodeIsValid($node)) {
			return false;
		} else if ($this->isChoice($node) ? !$this->elementIsValid($node, $element) : $element != 0) {
			return false;
		}
		$data = $this->nodes[$node]->elements[$element];
		if ($data->conditions->isEmpty()) {
			return true;
		}
		return $data->conditions->test($vars);
	}
	
	// Returns true if the given node index is in the range of valid nodes for this
	// Conversation.
	public function nodeIsValid(int $node): bool {
		if ($node < 0) {
			return false;
		}
		return isset($this->nodes[$node]);
	}
	
	// Returns true if the given node index is in the range of valid nodes for this
	// Conversation *and* the given element index is in the range of valid elements
	// for the given node.
	public function elementIsValid(int $node, int $element): bool {
		if (!$this->nodeIsValid($node)) {
			return false;
		} else if ($element < 0) {
			return false;
		}
		return isset($this->nodes[$node]->elements[$element]);
	}
	
	public function hasDisplayRestriction(DataNode $node): bool {
		foreach ($node as $child) {
			if ($child->size() == 2 && $child->getToken(0) == "to" && $child->getToken(1) == "display") {
				return true;
			}
		}
	
		return false;
	}
	
	// Add a label, pointing to whatever node is created next.
	public function addLabel(string $label, DataNode $node) {
		if (in_array($label, $this->labels)) {
			$node->printTrace("Error: Conversation: label \"" . $label . "\" is used more than once:");
			return;
		}
	
		// If there are any unresolved references to this label, we can now set
		// their indices correctly.
		foreach ($this->unresolved as $unresolvedLabel => $unresolvedPair) {
			if ($unresolvedLabel == $label) {
				$this->nodes[$unresolvedPair[0]]->elements[$unresolvedPair[1]]->next = count($this->nodes);
				unset($this->unresolved[$label]);
			}
		}
	
		// Remember what index this label points to.
		$this->labels[$label] = count($this->nodes);
		$this->labelNames[count($this->nodes)] = $label;
	}
	
	// Set up a "goto". Depending on whether the named label has been seen yet
	// or not, it is either resolved immediately or added to the unresolved set.
	public function goto(string $label, int $node, int $element): void {
		$index = array_search($label, $this->labels);
	
		if ($index == count($this->labels)) {
			$this->unresolved[$label] = [$node, $element];
		} else {
			$this->nodes[$node]->elements[$element]->next = $index;
		}
	}
	
	// Add an "empty" node. It will contain one empty line of text, with its
	// goto link set to fall through to the next node.
	public function addNode(): void {
		$node = new Node();
		$node->setConversation($this);
		$this->nodes []= $node;
		$element = new Element();
		$element->setNode($node);
		$element->text = "";
		$element->next = count($this->nodes);
		$node->elements []= $element;
	}
	
	public function toJSON(bool $justArray): string|array {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		
		if ($this->name) {
			$jsonArray['name'] = $this->name;
		} else {
			$jsonArray['name'] = '';
		}
		
		$jsonArray['nodes'] = [];
		foreach ($this->nodes as $Node) {
			$jsonArray['nodes'] []= $Node->toJSON(true);
		}
		
		$jsonArray['source'] = ['name'=>$this->sourceName,'file'=>$this->sourceFile,'version'=>$this->sourceVersion];
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}
	
	public function setFromJSON(string|array $jsonArray): void {
		if (!is_array($jsonArray)) {
			$jsonArray = json_decode($jsonArray, true);
		}
		
		$this->name = $jsonArray['name'];
		
		if (isset($jsonArray['labels'])) {
			$this->labels = $jsonArray['labels'];
		} else {
			$this->labels = [];
		}
		
		if (isset($jsonArray['nodes'])) {
			foreach ($jsonArray['nodes'] as $nodeArray) {
				$Node = new Node();
				$Node->setConversation($this);
				$this->nodes []= $Node;
				$Node->setFromJSON($nodeArray);
			}
		}
	}
}

// This serves multiple purposes:
// - In a regular text node, there's exactly one of these. It contains the
//   text data, the index of the next node to unconditionally visit, and,
//   optionally, a condition set which, if not met, prevents the text from
//   being displayed (without affecting which node is processed next).
// - In a choice node, there's one of these for each possible choice,
//   containing the text to display, the node the choice leads to, and,
//   optionally, the conditions under which to offer the choice.
// - In a branch node, there's two of these. The first one contains the
//   condition for the branch. If the condition is met, the "next" member
//   of the first element is followed. If it's not met, it's the second
//   element whose "next" member is followed.
#[ORM\Entity]
#[ORM\Table(name: 'ConversationElement')]
#[ApiResource]
class Element {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'text')]
	public string $text;
	// The next node to visit:
	#[ORM\Column(type: 'integer')]
	public int $next;
	// Conditions for displaying the text:
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', mappedBy: 'conversationNodeElement', cascade: ['persist'])]
	public ?ConditionSet $conditions = null;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Node', inversedBy: 'elements')]
	#[ORM\JoinColumn(nullable: false, name: 'nodeId')]
	private Node $node;
	
	public function __construct() {
		$this->conditions = new ConditionSet();
	}
	
	public function setNode($node): void {
		$this->node = $node;
	}
	
	public function getId(): int {
		return $this->id;
	}
	
	public function getText(): string {
		return $this->text;
	}
	
	public function getNext(): int {
		return $this->next;
	}
	
	public function getConditions(): ConditionSet {
		return $this->conditions;
	}
	
	public function getNode(): Node {
		return $this->node;
	}
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		$jsonArray['text'] = $this->text;
		$jsonArray['next'] = $this->next;
		
		$jsonArray['conditions'] = $this->conditions?->toJSON(true);
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}
	
	public function setFromJSON(string|array $jsonArray): void {
		if (!is_array($jsonArray)) {
			$jsonArray = json_decode($jsonArray, true);
		}
		
		$this->text = $jsonArray['text'];
		$next = $jsonArray['next'];
		if (!is_numeric($jsonArray['next'])) {
			$next = $this->node->getConversation()->getLabels()[$next] ?? -1;
		} 
		$this->next = $next;
		
		$this->conditions->setFromJSON($jsonArray['conditions']);
	}
};

// The conversation is a network of "nodes" that you travel between by
// making choices (or by automatic branches that depend on the condition
// variable values for the current player).
#[ORM\Entity]
#[ORM\Table(name: 'ConversationNode')]
#[ApiResource]
class Node {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Conversation', inversedBy: 'nodes')]
	#[ORM\JoinColumn(nullable: false, name: 'conversationId')]
	private Conversation $conversation;
	
	// Construct a new node. Each paragraph of conversation that involves no
	// choice can be merged into what came before it, to simplify things.
	public function __construct(bool $isChoice = false) {
		$this->conditions = new ConditionSet();
		$this->conditions->setConversationNode($this);
		$this->actions = new GameAction();
		$this->actions->setConversationNode($this);
		$this->canMergeOnto = !$isChoice;
		$this->elements = new ArrayCollection();
		$this->isChoice = $isChoice;
	}
	
	public function setConversation(Conversation $conversation): void {
		$this->conversation = $conversation;
	}

	// The condition expressions that determine the next node to load, or
	// whether to display.
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\ConditionSet', mappedBy: 'conversationNode', cascade: ['persist'])]
	public ConditionSet $conditions;
	// Tasks performed when this node is reached.
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\GameAction', mappedBy: 'conversationNode', cascade: ['persist'])]
	public GameAction $actions;
	// See Element's comment above for what this actually entails.
	#[ORM\OneToMany(targetEntity: 'App\Entity\Sky\Element', mappedBy: 'node', cascade: ['persist'])]
	public Collection $elements;
	// This distinguishes "choice" nodes from "branch" or text nodes. If
	// this value is false, a one-element node is considered text, and a
	// node with more than one element is considered is considered a
	// "branch".
	#[ORM\Column(type: 'boolean')]
	public bool $isChoice = false;
	// Keep track of whether it's possible to merge future nodes onto this.
	#[ORM\Column(type: 'boolean')]
	public bool $canMergeOnto;
	
	#[ORM\Column(type: 'string')]
	public string $branchName = '';

	// Image that should be shown along with this text.
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\Sprite', cascade: ['persist'])]
	public ?Sprite $scene = null;
	
	public function getId(): int {
		return $this->id;
	}
	public function setId(int $id): self {
		$this->id = $id;
		return $this;
	}
	
	public function getConversation(): Conversation {
		return $this->conversation;
	}
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		$jsonarray['canMergeOnto'] = $this->canMergeOnto;
		$jsonArray['isChoice'] = $this->isChoice;
		
		$jsonArray['scene'] = $this->scene?->getName();
		
		$jsonArray['conditionSet'] = $this->conditions->toJSON(true);
		$jsonArray['actions'] = $this->actions->toJSON(true);
		
		$jsonArray['elements'] = [];
		foreach ($this->elements as $Element) {
			$jsonArray['elements'] []= $Element->toJSON(true);
		}
		
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}
	
	public function setFromJSON(string|array $jsonArray): void {
		if (!is_array($jsonArray)) {
			$jsonArray = json_decode($jsonArray, true);
		}
		
		$this->isChoice = $jsonArray['isChoice'];
		if ($jsonArray['scene']) {
			$this->scene = SpriteSet::Get($jsonArray['scene']); // TODO: handle "ad hoc" scenes, added just now
		}
		$this->conditions->setFromJSON($jsonArray['conditionSet']);
		//$this->actions->setFromJSON($jsonArray['actions']);
		
		if (isset($jsonArray['elements'])) {
			foreach ($jsonArray['elements'] as $elementArray) {
				$Element = new Element();
				$Element->setNode($this);
				$this->elements []= $Element;
				$Element->setFromJSON($elementArray);
			}
		}
	}
	
	public function isBranch(): bool {
		return !$this->conditions->isEmpty() && count($this->elements) > 1;
	}

};