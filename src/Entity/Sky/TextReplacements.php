<?php

namespace App\Entity\Sky;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'TextReplacements')]
class TextReplacements {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	private array $substitutions = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subKey = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ConditionSet $conditionSet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $substitution = null; // vector<pair<string, pair<ConditionSet, string>>>
	
	#[ORM\Column(type: 'string')]
	private string $sourceName = '';
	#[ORM\Column(type: 'string')]
	private string $sourceFile = '';
	#[ORM\Column(type: 'string')]
	private string $sourceVersion = '';
	
	public function load(DataNode $node): void {
    	// Check for reserved keys. Only some hardcoded replacement keys are
    	// reserved, as these ones are done on the fly after all other replacements
    	// have been done.
    	$reserved = ["<first>", "<last>", "<ship>"];
		if ($node->getSourceName()) {
			$this->sourceName = $node->getSourceName();
			$this->sourceFile = $node->getSourceFile();
			$this->sourceVersion = $node->getSourceVersion();
		}
	
    	foreach ($node as $child) {
    		if ($child->size() < 2) {
    			$child->printTrace("Skipping substitution key with no replacement:");
    			continue;
    		}
    		$key = $child->getToken(0);
    		if ($key == '') {
    			$child->printTrace("Error: Cannot replace the empty string:");
    			continue;
    		}
    		if ($key[0] != '<') {
    			$key = "<" . $key;
    			$child->printTrace("Warning: text replacements must be prefixed by \"<\":");
    		}
    		if ($key[strlen($key)-1] != '>') {
    			$key .= ">";
    			$child->printTrace("Warning: text replacements must be suffixed by \">\":");
    		}
    		if (in_array($key, $reserved)) {
    			$child->printTrace("Skipping reserved substitution key:");
    			continue;
    		}
                           		
    		$toSubstitute = new ConditionSet($child);
    		$substitutions[$key] = [$toSubstitute, $child->getToken(1)];
    	}
	}
	// 
	// // Clear this TextReplacement's substitutions and insert the substitutions of other.
	// void TextReplacements::Revert(TextReplacements &other)
	// {
	// 	substitutions.clear();
	// 	substitutions.insert(substitutions.begin(), other.substitutions.begin(), other.substitutions.end());
	// }
	// 
	// 
	// 
	// // Add new text replacements to the given map after evaltuating all possible replacements.
	// // This text replacement will overwrite the value of any existing keys in the given map
	// // if the map and this TextReplacements share a key.
	// void TextReplacements::Substitutions(map<string, string> &subs, const ConditionsStore &conditions) const
	// {
	// 	for(const auto &sub : substitutions)
	// 	{
	// 		const string &key = sub.first;
	// 		const ConditionSet &toSub = sub.second.first;
	// 		const string &replacement = sub.second.second;
	// 		if(toSub.Test(conditions))
	// 			subs[key] = replacement;
	// 	}
	// }
	
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

    public function getSubKey(): ?string
    {
        return $this->subKey;
    }

    public function setSubKey(string $subKey): self
    {
        $this->subKey = $subKey;

        return $this;
    }

    public function getConditionSet(): ?ConditionSet
    {
        return $this->conditionSet;
    }

    public function setConditionSet(?ConditionSet $conditionSet): self
    {
        $this->conditionSet = $conditionSet;

        return $this;
    }

    public function getSubstitution(): ?string
    {
        return $this->substitution;
    }

    public function setSubstitution(string $substitution): self
    {
        $this->substitution = $substitution;

        return $this;
    }
	
	public function toJSON($justArray=false): array|string {
		$jsonArray = [];
		
		$jsonArray['subKey'] = $this->subKey;
		$jsonArray['substitution'] = $this->substitution;
		
		$jsonArray['source'] = ['name'=>$this->sourceName,'file'=>$this->sourceFile,'version'=>$this->sourceVersion];
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
}