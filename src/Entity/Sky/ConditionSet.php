<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;
use App\Entity\DataWriter;

#[ORM\Entity]
#[ORM\Table(name: 'ConditionSet')]
class ConditionSet {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	// Sets of condition tests can contain nested sets of tests. Each set is
	// either an "and" grouping (meaning every condition must be true to satisfy
	// it) or an "or" grouping where only one condition needs to be true.
	#[ORM\Column(type: 'boolean', name: 'isOr')]
	private bool $isOr = false;
	// If this set contains assignment expressions. If true, the Test()
	// method must first apply them before testing any conditions.
	#[ORM\Column(type: 'boolean', name: 'hasAssign')]
	private bool $hasAssign = false;
	// Conditions that this set tests or applies.
	#[ORM\OneToMany(mappedBy: 'conditionSet', targetEntity: Expression::class, orphanRemoval: true, cascade: ['persist'])]
	private Collection $expressions; // Expression array
	// Nested sets of conditions to be tested.
	#[ORM\OneToMany(mappedBy: 'parent', targetEntity: ConditionSet::class, orphanRemoval: true, cascade: ['persist'])]
	private Collection $children; // ConditionSet array
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\ConditionSet', inversedBy: 'children', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: true, name: 'parentId')]
	private $parent;
	
	static string $UNRECOGNIZED = "Warning: Unrecognized condition expression:";
	static string $UNREPRESENTABLE = "Error: Unrepresentable condition value encountered:";
	
	public static array $comparisons = ["==", "!=", "<", ">", "<=", ">="];
	public static array $assignments = ["=", "+=", "-=", "*=", "/=", "<?=", ">?="];
	public static array $simple = ["(", ")", "+", "-", "*", "/", "%"];
	public static array $precedence = ["(" => 0, ")" => 0, "+" => 1, "-" => 1, "*" => 2, "/" => 2, "%" => 2];
	public static array $invalids = ["{", "}", "[", "]", "|", "^", "&", "!", "~", "||", "&&", "&=", "|=", "<<", ">>"];

	public static function eq($a, $b): bool {
		return $a == $b;
	}
	public static function neq($a, $b): bool {
		return $a != $b;
	}
	public static function lt($a, $b): bool {
		return $a < $b;
	}
	public static function gt($a, $b): bool {
		return $a > $b;
	}
	public static function lte($a, $b): bool {
		return $a <= $b;
	}
	public static function gte($a, $b): bool {
		return $a >= $b;
	}
	public static function as($a, $b): bool {
		return $b;
	}
	public static function astim($a, $b): bool {
		return $a * $b;
	}
	public static function aspls($a, $b): bool {
		return $a + $b;
	}
	public static function asmin($a, $b): bool {
		return $a - $b;
	}
	public static function asdiv($a, $b): bool {
		return $b ? $a / $b : PHP_INT_MAX;
	}
	public static function aslt($a, $b): bool {
		return min($a, $b);
	}
	public static function asgt($a, $b): bool {
		return max($a, $b);
	}
	public static function mod($a, $b): bool {
		return $a % $b;
	}
	public static function tim($a, $b): bool {
		return $a * $b;
	}
	public static function pls($a, $b): bool {
		return $a + $b;
	}
	public static function min($a, $b): bool {
		return $a - $b;
	}
	public static function div($a, $b): bool {
		return $b ? $a / $b : PHP_INT_MAX;
	}
	
	public static array $opMap = [
		"==" => 'eq',
		"!=" => 'neq',
		"<" => 'lt',
		">" => 'gt',
		"<=" => 'lte',
		">=" => 'gte',
		"=" => 'as',
		"*=" => 'astim',
		"+=" => 'aspls',
		"-=" => 'asmin',
		"/=" => 'asdiv',
		"<?=" => 'aslt',
		">?=" => 'asgt',
		"%" => 'mod',
		"*" => 'tim',
		"+" => 'pls',
		"-" => 'min',
		"/" => 'div',
	];
	
	// Indicate if the operation is a comparison or modifies the condition.
	public static function IsComparison(string $op): bool {
		return in_array($op, ConditionSet::$comparisons);
	}
	
	public static function IsAssignment(string $op): bool {
		return in_array($op, ConditionSet::$assignments);
	}

	public static function IsSimple(string $op): bool {
		return in_array($op, ConditionSet::$simple);
	}

	public static function Precedence(string $op): int {
		return ConditionSet::$precedence[$op];
	}

	// Test to determine if unsupported operations are requested.
	public static function HasInvalidOperators(array $tokens): bool {
		foreach ($tokens as $str) {
			if (in_array($str, ConditionSet::$invalids)) {
				return true;
			}
		}
		return false;
	}

	// Ensure the ConditionSet line has balanced parentheses on both sides.
	public static function HasUnbalancedParentheses(array $tokens): bool {
		$parentheses = 0;
		foreach ($tokens as $str) {
			if ($parentheses < 0) {
				return true;
			} else if ($parentheses && (ConditionSet::IsAssignment($str) || ConditionSet::IsComparison($str))) {
				return true;
			} else if ($str == "(") {
				++$parentheses;
			} else if ($str == ")") {
				--$parentheses;
			}
		}
		return $parentheses;
	}

	// Perform a preliminary assessment of the input condition, to determine if it is remotely well-formed.
	// The final assessment of its validity will be whether it parses into an evaluable Expression.
	public function IsValidCondition(DataNode $node): bool {
		$tokens = $node->getTokens();
		$assigns = 0;
		$compares = 0;
		foreach ($tokens as $token) {
			if (ConditionSet::IsComparison($token)) {
				$compares++;
			}
			if (ConditionSet::IsAssignment($token)) {
				$assigns++;
			}
		}
		if ($assigns + $compares != 1) {
			$node->printTrace("Error: An expression must either perform a comparison or assign a value:");
		} else if (ConditionSet::HasInvalidOperators($tokens)) {
			$node->printTrace("Error: Brackets, braces, exponentiation, and boolean/bitwise math are not supported:");
		} else if (ConditionSet::HasUnbalancedParentheses($tokens)) {
			$node->printTrace("Error: Unbalanced parentheses in condition expression:");
		} else {
			foreach ($tokens as $token) {
				if (strlen($token) > 0 && $token[0] == '(') {
					$node->printTrace("Error: Parentheses must be separate from tokens:");
					return false;
				}
			}
			return true;
		}

		return false;
	}

	// Converts the given vector of condition tokens (like "reputation: Republic",
	// "random", or "4") into the integral values they have at runtime.
	public static function SubstituteValues(array $side, ConditionsStore $conditions, ConditionsStore $created): array {
		$result = [];
		foreach ($side as $str) {
			$value = 0;
			if ($str == "random") {
				$value = rand(0, 100);
			} else if (DataNode::TokenIsNumber($str)) {
				$value = intval(DataNode::TokenValue($str));
			} else {
				$temp = $created->hasGet($str);
				if ($temp[0]) {
					$value = $temp[1];
				} else {
					$perm = $conditions->hasGet(str);
					if ($perm[0]) {
						$value = $perm[1];
					}
				}
			}
			$result []= $value;
		}
		return $result;
	}

	public static function UsedAll(array $status): bool {
		foreach ($status as $v) {
			if (!$v) {
				return false;
			}
		}
		return true;
	}

	// Finding the left operand's index if getLeft = true. The operand's index is the first non-empty, non-used index.
	public static function FindOperandIndex(array $tokens, array $resultIndices, int $opIndex, bool $getLeft) {
		// Start at the operator index (left), or just past it (right).
		$index = $opIndex + !$getLeft;
		if ($getLeft) {
			while (strlen($tokens[$index]) == 0 && $index > 0) {
				--$index;
			}
		} else {
			while (strlen($tokens[$index]) == 0 && $index < count($tokens) - 2) {
				++$index;
			}
		}
		// Trace any used data to find the latest result.
		while ($resultIndices[$index] > 0) {
			$index = $resultIndices[$index];
		}

		return $index;
	}

	public static function PrintConditionError(array $side): void {
		$message = "Error decomposing complex condition expression:\nFound:	";
		foreach ($side as $str) {
			$message .= " \"" . $str . "\"";
		}
		error_log($message);
	}

	public static function IsUnrepresentable(string $token): bool {
		if (DataNode::TokenIsNumber($token)) {
			$value = DataNode::TokenValue($token);
			if ($value > floatval(PHP_INT_MAX) || $value < floatval(PHP_INT_MIN)) {
				return true;
			}
		}
		// It's possible that a condition uses purely representable values, but performs math
		// that result in unrepresentable values. However, that situation cannot be detected
		// during expression construction, only during execution.
		return false;
	}

	// Construct and Load() at the same time.
	public function __construct(?DataNode $node = null) {
		$this->expressions = new ArrayCollection();
		$this->children = new ArrayCollection();
		if ($node) {
			$this->load($node);
		}
	}
	
	public function getExpressions(): array {
		return $this->expressions->toArray();
	}
	
	public function getChildren(): array {
		return $this->children->toArray();
	}
	
	// Load a set of conditions from the children of this node.
	public function load(DataNode $node): void {
		$this->isOr = ($node->getToken(0) == "or");
		foreach ($node as $child) {
			$this->add(node: $child);
		}
	}
	
	// Save a set of conditions.
	public function save(DataWriter $out): void {
		foreach ($this->expressions as $expression) {
			$expression->save($out);
		}
	
		foreach ($this->children as $child) {
			$out->write($child->isOr ? "or" : "and");
			$out->beginChild();
			$child->save($out);
			$out->endChild();
		}
		$out->writeNewline();
	}
	
	// Check if there are any entries in this set.
	public function isEmpty(): bool {
		return count($this->expressions) == 0 && count($this->children) == 0;
	}
	
	public function add(?DataNode $node = null, ?string $firstToken = null, ?string $secondToken = null, ?string $name = null, ?string $op = null, ?string $value = null, ?array $lhs = null, ?array $rhs = null): bool {
		if ($node) {
			$this->addNode($node);
		} else if ($firstToken !== null && $secondToken !== null) {
			return $this->addTokens($firstToken, $secondToken);
		} else if ($name !== null && $op !== null && $value !== null) {
			return $this->addNameOpValue($name, $op, $value);
		} else if ($lhs !== null && $op !== null && $rhs !== null) {
			return $this->addLeftOpRight($lhs, $op, $rhs);
		} else {
			return false;
		}
		
		return true;
	}
	
	// Read a single condition from a data node.
	public function addNode(DataNode $node) {
		// Special keywords have a node size of 1 (never, and, or), or 2 (unary operators).
		// Simple conditions have a node size of 3, while complex conditions feature a single
		// non-simple operator (e.g. <=) and any number of simple operators.
		if ($node->size() == 2) {
			if (ConditionSet::IsUnrepresentable($node->getToken(1))) {
				$node->printTrace(ConditionSet::$UNREPRESENTABLE);
			} else if (!$this->add(firstToken: $node->getToken(0), secondToken: $node->getToken(1))) {
				$node->printTrace(ConditionSet::$UNRECOGNIZED);
			}
		} else if($node->size() == 1 && $node->getToken(0) == "never") {
			$expression = new Expression("'", "!=", "0");
			$expression->setConditionSet($this);
			$this->expressions []= $expression;
		} else if ($node->size() == 1 && ($node->getToken(0) == "and" || $node->getToken(0) == "or")) {
			// The "and" and "or" keywords introduce a nested condition set.
			$newSet = new ConditionSet($node);
			$this->children []= $newSet;
			// If a child node has assignment operators, warn on load since
			// these will be processed after all non-child expressions.
			if ($newSet->hasAssign) {
				$node->printTrace("Warning: Assignment expressions contained within and/or groups are applied last. This may be unexpected.");
			}
		} else if (ConditionSet::IsValidCondition($node)) {
			// This is a valid condition containing a single assignment or comparison operator.
			if ($node->size() == 3) {
				if (ConditionSet::IsUnrepresentable($node->getToken(0)) || ConditionSet::IsUnrepresentable($node->getToken(2))) {
					$node->printTrace(ConditionSet::$UNREPRESENTABLE);
				} else if(!$this->add(name: $node->getToken(0), op: $node->getToken(1), value: $node->getToken(2))) {
					$node->printTrace(ConditionSet::$UNRECOGNIZED);
				}
			} else {
				// Split the DataNode into left- and right-hand sides.
				$lhs = [];
				$rhs = [];
				$op = '';
				foreach ($node->getTokens() as $token) {
					if (ConditionSet::IsUnrepresentable($token)) {
						$node->printTrace(ConditionSet::$UNREPRESENTABLE);
						return;
					} else if($op != '') {
						$rhs []= $token;
					} else if (ConditionSet::IsComparison($token)) {
						$op = $token;
					} else if (ConditionSet::IsAssignment($token)) {
						if (count($lhs) == 1) {
							$op = $token;
						} else {
							$node->printTrace("Error: Assignment operators must be the second token:");
							return;
						}
					} else {
						$lhs []= $token;
					}
				}
				if(!$this->add(lhs: $lhs, op: $op, rhs: $rhs)) {
					$node->printTrace(ConditionSet::$UNRECOGNIZED);
				}
			}
		}
		if (count($this->expressions) > 0) {
			$lastExpression = $this->expressions[array_key_last($this->expressions->toArray())];
			if ($lastExpression->isEmpty()) {
				$node->printTrace("Warning: Condition parses to an empty set:");
				array_pop($this->expressions);
			}
		}
	}
	
	// Add a unary operator line to the list of expressions.
	public function addTokens(string $firstToken, string $secondToken): bool {
		// Each "unary" operator can be mapped to an equivalent binary expression.
		if ($firstToken == "not") {
			$expression = new Expression($secondToken, "==", "0");
			$expression->setConditionSet($this);
			$this->expressions []= $expression;
		} else if ($firstToken == "has") {
			$expression = new Expression($secondToken, "!=", "0");
			$expression->setConditionSet($this);
			$this->expressions []= $expression;
		} else if ($firstToken == "set") {
			$expression = new Expression($secondToken, "=", "1");
			$expression->setConditionSet($this);
			$this->expressions []= $expression;
		} else if ($firstToken == "clear") {
			$expression = new Expression($secondToken, "=", "0");
			$expression->setConditionSet($this);
			$this->expressions []= $expression;
		} else if ($secondToken == "++") {
			$expression = new Expression($firstToken, "+=", "1");
			$expression->setConditionSet($this);
			$this->expressions []= $expression;
		} else if ($secondToken == "--") {
			$expression = new Expression($firstToken, "-=", "1");
			$expression->setConditionSet($this);
			$this->expressions []= $expression;
		} else {
			return false;
		}
	
		$lastExpression = $this->expressions[array_key_last($this->expressions->toArray())];
		$this->hasAssign |= !$lastExpression->isTestable();
		return true;
	}
	
	// Add a simple condition expression to the list of expressions.
	public function addNameOpValue(string $name, string $op, string $value): bool {
		// If the operator is recognized, map it to a binary function.
		if (!isset(ConditionSet::$opMap[$op])) {
			return false;
		}
	
		$this->hasAssign |= !ConditionSet::IsComparison($op);
		$expression = new Expression($name, $op, $value);
		$expression->setConditionSet($this);
		$this->expressions []= $expression;
		return true;
	}
	
	// Add a complex condition expression to the list of expressions.
	public function addLeftOpRight(array $lhs, string $op, array $rhs): bool {
		if (!isset(ConditionSet::$opMap[$op])) {
			return false;
		}
	
		$this->hasAssign |= !ConditionSet::IsComparison($op);
		$expression = new Expression($lhs, $op, $rhs);
		$expression->setConditionSet($this);
		$this->expressions []= $expression;
		return true;
	}
	
	// Check if the given condition values satisfy this set of conditions. Performs any assignments
	// on a temporary condition map, if this set mixes comparisons and modifications.
	public function test(ConditionsStore $conditions): bool {
		// If this ConditionSet contains any expressions with operators that
		// modify the condition map, then they must be applied before testing,
		// to generate any temporary conditions needed.
		$created = new ConditionsStore();
		if ($this->hasAssign) {
			$this->testApply($conditions, $created);
		}
		return $this->testSet($conditions, $created);
	}
	
	// Modify the given set of conditions.
	public function apply(ConditionsStore $conditions): void {
		$unused = new ConditionsStore();
		foreach ($this->expressions as $expression) {
			if (!$this->expression->isTestable()) {
				$expression->apply($conditions, $unused);
			}
		}
	
		foreach ($this->children as $child) {
			$child->apply($conditions);
		}
	}
	
	// Get the names of the conditions that are relevant for this ConditionSet.
	public function relevantConditions(): array {
		$result = [];
		// Add the names from the expressions.
		// TODO: also sub-expressions?
		foreach ($this->expressions as $expr) {
			$result []= $expr.Name();
		}
		// Add the names from the children.
		foreach ($this->children as $child) {
			foreach ($child->relevantConditions() as $rc) {
				$result []= $rc;
			}
		}
		return $result;
	}
}

#[ORM\Entity]
#[ORM\Table(name: 'Expression')]
class Expression {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string', name: 'op')]
	// String representation of the Expression's binary function.
	private string $op = '';
	// Pointer to a binary function that defines the assignment or
	// comparison operation to be performed between SubExpressions.
	private $fun;

	// SubExpressions contain one or more tokens and any number of simple operators.
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\SubExpression', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'leftId')]
	private SubExpression $left;
	#[ORM\OneToOne(targetEntity: 'App\Entity\Sky\SubExpression', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'rightId')]
	private SubExpression $right;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Sky\ConditionSet', inversedBy: 'expressions', cascade: ['persist'])]
	#[ORM\JoinColumn(nullable: false, name: 'conditionSetId')]
	private $conditionSet;
	
// 	Expression(const std::vector<std::string> &left, const std::string &op, const std::vector<std::string> &right);
// 	Expression(const std::string &left, const std::string &op, const std::string &right);
// 
// 	void Save(DataWriter &out) const;
// 	// Convert this expression into a string, for traces.
// 	std::string ToString() const;
// 
// 	// Determine if this Expression instantiated properly.
// 	bool IsEmpty() const;
// 
// 	// Returns the left side of this Expression.
// 	std::string Name() const;
// 	// True if this Expression performs a comparison and false if it performs an assignment.
// 	bool IsTestable() const;
// 
// 	// Functions to use this expression:
// 	bool Test(const ConditionsStore &conditions, const ConditionsStore &created) const;
// 	void Apply(ConditionsStore &conditions, ConditionsStore &created) const;
// 	void TestApply(const ConditionsStore &conditions, ConditionsStore &created) const;
	// Constructor for complex expressions.
	public function __construct(array|string $left, string $op, array|string $right) {
		$this->op = $op;
		$this->fun = ConditionSet::$opMap[$op];
		if (is_array($left)) {
			$this->left = new SubExpression(sideTokens: $left);
		} else {
			$this->left = new SubExpression(sideToken: $left);
		}
		if (is_array($right)) {
			$this->right = new SubExpression(sideTokens: $right);
		} else {
			$this->right = new SubExpression(sideToken: $right);
		}
	}
	
	public function getLeft(): SubExpression {
		return $this->left;
	}
	
	public function getOp(): string {
		return $this->op;
	}
	
	public function getRight(): SubExpression {
		return $this->right;
	}
	
	public function setConditionSet(ConditionSet $conditionSet): void {
		$this->conditionSet = $conditionSet;
	}

	public function save(DataWriter $out): void {
		foreach ($this->left->toStrings() as $str) {
			$out->writeToken($str);
		}
		$out->writeToken($this->op);
		foreach ($this->right->toStrings() as $str) {
			$out->writeToken($str);
		}
		$out->writeNewline();
	}

	// Create a loggable string (for PrintTrace).
	public function __toString(): string {
		return $this->left . " \"" . $this->op . "\" " . $this->right;
	}

	// Checks if either side of the expression is tokenless.
	public function isEmpty(): bool {
		$leftEmpty = $this->left->isEmpty();
		$rightEmpty = $this->right->isEmpty();
		return $leftEmpty || $rightEmpty;
	}

	// Returns everything to the left of the main assignment or comparison operator.
	// In an assignment expression, this should be only a single token.
	public function getName(): string {
		return '' . $this->left;
	}

	// Returns true if the operator is a comparison and false otherwise.
	public function isTestable(): bool {
		return ConditionSet::IsComparison($this->op);
	}

	// Evaluate both the left- and right-hand sides of the expression, then compare the evaluated numeric values.
	public function test(ConditionsStore $conditions, ConditionsStore $created): bool {
		$lhs = $this->left->evaluate($conditions, $created);
		$rhs = $this->right->evaluate($conditions, $created);
		return $this->fun($lhs, $rhs);
	}

	// Assign the computed value to the desired condition.
	public function apply(ConditionsStore $conditions, ConditionsStore $created): void {
		$value = $this->right->evaluate($conditions, $created);
		$conditions[$this->getName()] = $this->fun($conditions[$this->getName()], $value);
	}

	// Assign the computed value to the desired temporary condition.
	public function testApply(ConditionsStore $conditions, ConditionsStore $created): void {
		$value = $this->right->evaluate($conditions, $created);
		$created[$this->getName()] = $this->fun($created[$this->getName()], $value);
	}

}

// A SubExpression results from applying operator-precedence parsing to one side of
// an Expression. The operators and tokens needed to recreate the given side are
// stored, and can be interleaved to restore the original string. Based on them, a
// sequence of "Operations" is created for runtime evaluation.
#[ORM\Entity]
#[ORM\Table(name: 'SubExpression')]
#[ORM\HasLifecycleCallbacks]
class SubExpression {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	// Iteration of the sequence vector yields the result.
	#[ORM\Column(type: 'string', name: 'sequenceStr')]
	private string $sequenceStr = '';
	private array $sequence = []; // Operation array
	
	// The tokens vector converts into a data vector of numeric values during evaluation.
	#[ORM\Column(type: 'string', name: 'tokenStr')]
	private string $tokenStr = '';
	private array $tokens = []; // string array
	
	#[ORM\Column(type: 'string', name: 'operatorStr')]
	private string $operatorStr = '';
	private array $operators = []; // string array
	// The number of true (non-parentheses) operators.
	private int $operatorCount = 0;
	
// Constructor for one side of a complex expression (supports multiple simple operators and parentheses).
	public function __construct(array $sideTokens = null, string $sideToken = null) {
		if ($sideTokens == null && $sideToken !== null) {
			$this->tokens []= $sideToken === '' ? "'" : $sideToken;
		} else if ($sideTokens != null) {
			$this->parseSide($sideTokens);
			$this->generateSequence();
		} else {
			return;
		}
	}
	
	public function getTokens(): array {
		return $this->tokens;
	}
	
	public function getOperators(): array {
		return $this->operators;
	}

	// Convert the tokens and operators back to a string, for use in logging.
	public function __toString(): string {
		$out = '';
		
		for ($i = 0; $i < count($this->operators); ++$i) {
			if (isset($this->tokens[$i])) {
				$out .= $this->tokens[$i] . ' ';
			}
			$out .= $this->operators[$i];
			if ($i != count($this->operators) - 1) {
				$out .= ' ';
			}
		}
		// The tokens vector contains more values than the operators vector.
		for ( ; $i < count($this->tokens); ++$i) {
			if ($i != 0) {
				$out .= ' ';
			}
			$out .= $this->tokens[$i];
		}
		return $out;
	}
	
	// Interleave the tokens and operators, for use in the save file.
	public function toStrings(): array { // returns string array
		$out = [];
		
		for ($i = 0; $i < count($this->operators); ++$i) {
			if (isset($this->tokens[$i])) {
				$out []= $this->tokens[$i];
			}
			$out []= $this->operators[$i];
		}
		for ( ; $i < count($this->tokens); ++$i) {
			if (isset($this->tokens[$i])) {
				$out []= $this->tokens[$i];
			}
		}
		return $out;
	}

	// Check if this SubExpression was able to build correctly.
	public function isEmpty(): bool {
		return count($this->tokens) == 0;
	}
	
	// Evaluate the SubExpression using the given condition maps.
	public function evaluate(ConditionsStore $conditions, ConditionsStore $created): int {
		// Sanity check.
		if(count($this->tokens) == 0) {
			return 0;
		}
	
		// For SubExpressions with no Operations (i.e. simple conditions), tokens will consist
		// of only the condition or numeric value to be returned as-is after substitution.
		$data = ConditionSet::SubstituteValues($this->tokens, $conditions, $created);
	
		if(count($this->sequence) > 0) {
			// Each Operation adds to the end of the data vector.
			foreach ($this->sequence as $op) {
				$val = $op->fun($data[$op->a], $data[$op->b]);
				$data []= $val;
			}
		}
	
		$lastData = $data[array_key_last($data)];
		return $lastData;
	}

	// Parse the input vector into the tokens and operators vectors. Parentheses are
	// considered simple operators, and also insert an empty string into tokens.
	public function parseSide(array $side): void {
		$EMPTY = '';
		$parentheses = 0;
		// Construct the tokens and operators vectors.
		for ($i = 0; $i < count($side); ++$i) {
			if ($side[$i] == "(" || $side[$i] == ")") {
				// Ensure reconstruction by adding a blank token.
				$this->tokens []= $EMPTY;
				$this->operators []= $side[$i];
				++$parentheses;
			} else if(ConditionSet::IsSimple($side[$i])) {
				// Normal operators do not need a token insertion.
				$this->operators []= $side[$i];
				++$this->operatorCount;
			} else {
				$this->tokens []= $side[$i];
			}
		}
	
		if (count($this->tokens) == 0 || $this->operatorCount == 0) {
			$this->operators = [];
		} else if ($parentheses % 2 != 0) {
			// This should have been caught earlier, but just in case.
			ConditionSet::PrintConditionError($side);
			$this->tokens = [];
			$this->operators = [];
		}
		// Remove empty strings that wrap simple conditions, so any token
		// wrapped by only parentheses simplifies to just the token.
		if (count($this->operators) == 0 && count($this->tokens) == 0) {
			foreach ($this->tokens as $tIndex => $token) {
				if ($token == '') {
					unset($this->tokens[$tIndex]);
				}
			}
		}
	}
	
	// Parse the token and operators vectors to make the sequence vector.
	public function generateSequence(): void {
		// Simple conditions have only a single token and no operators.
		if (count($this->tokens) == 0 || count($this->operators) == 0) {
			return;
		}
		// Use a boolean vector to indicate when an operator has been used.
		$usedOps = [];
		// Read the operators vector just once by using a stack.
		$opStack = []; // int array
		// Store the data index for each Operation, for use by later Operations.
		$destinationIndex = count($this->tokens);
		$dataDest = []; // int array
		$opIndex = 0;
		while (!ConditionSet::UsedAll($usedOps)) {
			while (true) {
				// Stack ops until one of lower or equal precedence is found, then evaluate the higher one first.
				if (count($this->opStack) || $this->operators[$opIndex] == "("
						|| (ConditionSet::Precedence($this->operators[$opIndex]) > ConditionSet::Precedence($this->operators[$opStack[array_key_last($opStack)]]))) {
					$opStack []= $opIndex;
					// Mark this operator as used and advance.
					$usedOps[$opIndex++] = true;
					break;
				}
	
				$workingIndex = $opStack[array_key_last($opStack)];
				array_pop($opStack);
	
				// A left parentheses results in a no-op step.
				if ($this->operators[$workingIndex] == "(") {
					if ($this->operators[$opIndex] != ")") {
						error_log("Did not find matched parentheses:");
						ConditionSet::PrintConditionError($this->toStrings());
						$this->tokens = [];
						$this->operators = [];
						$this->sequence = [];
						return;
					}
					// "Use" the parentheses and advance operators.
					$usedOps[$opIndex++] = true;
					break;
				} else if(!$this->addOperation($dataDest, $destinationIndex, $workingIndex)) {
					return;
				}
			}
		}
		// Handle remaining operators (which cannot be parentheses).
		while (count($opStack)) {
			$workingIndex = $opStack[array_key_last($opStack)];
			array_pop($opStack);
	
			if ($this->operators[$workingIndex] == "(" || $this->operators[$workingIndex] == ")") {
				error_log("Mismatched parentheses:" . $this);
				$this->tokens = [];
				$this->operators = [];
				$this->sequence = [];
				return;
			} else if (!$this->addOperation($dataDest, $destinationIndex, $workingIndex)) {
				return;
			}
		}
		// All operators and tokens should now have been used.
	}
	
	// Use a valid working index and data pointer vector to create an evaluable Operation.
	public function addOperation(array $data, int $index, int $opIndex) {
		// Obtain the operand indices. The operator is never a parentheses. The
		// operator index never exceeds the size of the tokens vector.
		$leftIndex = ConditionSet::FindOperandIndex($this->tokens, $data, $opIndex, true);
		$rightIndex = ConditionSet::FindOperandIndex($this->tokens, $data, $opIndex, false);
	
		// Bail out if the pointed token is in-bounds and empty.
		if (($leftIndex < count($this->tokens) && $this->tokens[$leftIndex] == '')
				|| ($rightIndex < count($this->tokens) && $this->tokens[$rightIndex] == '')) {
			error_log("Unable to obtain valid operand for function \"" . $this->operators[$opIndex] . "\" with tokens:");
			ConditionSet::PrintConditionError($this->tokens);
			$this->tokens = [];
			$this->operators = [];
			$this->sequence = [];
			return false;
		}
	
		// Record use of an operand by writing where its latest value is found.
		$this->data[$leftIndex] = $index;
		$this->data[$rightIndex] = $index;
		// Create the Operation.
		$op = new Operation($this->operators[$opIndex], $leftIndex, $rightIndex);
		$this->sequence []= $op;
		// Update the pointed index for the next operation.
		++$index;
	
		return true;
	}
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$sequenceArray = [];
		foreach ($this->sequence as $op) {
			$sequenceArray []= ['op'=>$op->opStr,'a'=>$op->a,'b'=>$op->b];
		}
		$this->sequenceStr = json_encode($sequenceArray);
		$this->tokenStr = json_encode($this->tokens);
		$this->operatorStr = json_encode($this->operators);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->operators = json_decode($this->operatorStr, true);
		$this->tokens = json_decode($this->tokenStr, true);
		foreach ($this->sequenceStr as $opArray) {
			$this->sequence []= new Operation($opArray['op'], $opArray['a'], $opArray['b']);
		}
	}

}

// An Operation has a pointer to its binary function, and the data indices for
// its operands. The result is always placed on the back of the data vector.
class Operation {
	public $opStr;
	public $fun;
	// Constructor for an Operation, indicating the binary function and the
	// indices of its operands within the evaluation-time data vector.
	public function __construct(string $op, public int $a, public int $b) {
		$this->opStr = $op;
		$fun = ConditionSet::$opMap[$op];
	}
}
