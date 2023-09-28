<?php

namespace App\Entity\Sky;

use App\Repository\Sky\PhraseSentenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;

#[ORM\Entity(repositoryClass: PhraseSentenceRepository::class)]
class PhraseSentence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sentences')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Phrase $phrase = null;

    #[ORM\OneToMany(mappedBy: 'sentence', targetEntity: PhrasePart::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $parts;

	public function __construct(?DataNode $node = null, ?Phrase $parent = null) {
		$this->parts = new ArrayCollection();
		if ($node) {
			$this->load($node, $parent);
		}
	}
	
	// Parse the children of the given node to populate the sentence's structure.
	public function load(DataNode $node, ?Phrase $parent) {
		$node->printTrace('Loading sentence node:');
		foreach ($node as $child) {
			if (!$child->hasChildren()) {
				$child->printTrace("Skipping node with no children:");
				continue;
			}
			$Part = new PhrasePart();
	
			if ($child->getToken(0) == "word") {
				foreach ($child as $grand) {
					$Choice = new PhraseChoice($grand, false);
					$Choice->setPhrasePart($Part);
					$Part->addChoice($Choice);
					$Part->choiceWeights []= ($grand->size() >= 2 ? max(1, $grand->getValue(1)) : 1);
				}
			} else if ($child->getToken(0) == "phrase") {
				foreach ($child as $grand) {
					$Choice = new PhraseChoice($grand, true);
					$Choice->setPhrasePart($Part);
					$Part->addChoice($Choice);
					$Part->choiceWeights []= ($grand->size() >= 2 ? max(1, $grand->getValue(1)) : 1);
				}
			} else if ($child->getToken(0) == "replace") {
				foreach ($child as $grand) {
					$Part->replacements []= [$grand->getToken(0), ($grand->size() >= 2) ? $grand->getToken(1) : ''];
				}
			} else {
				$child->printTrace("Skipping unrecognized attribute:");
			}
			// Require any newly added phrases have no recursive references. Any recursions
			// will instead yield an empty string, rather than possibly infinite text.
			foreach ($Part->getChoices() as $Choice) {
				foreach ($Choice->phraseNames as $nameIndex => $phraseName) {
					$phrase = $Choice->getPhrases()[$nameIndex];
					if ($phrase && $phrase->referencesPhrase($parent)) {
						$child->printTrace("Warning: Replaced recursive '" . $phrase->getName() . "' phrase reference with \"\":");
						array_splice($Choice->phraseNames, $nameIndex, 1);
						$Choice->getPhrases()->remove($nameIndex);
					}
				}
			}
			// If no words, phrases, or replaces were given, discard this part of the phrase.
			if (count($Part->getChoices()) > 0 || count($Part->replacements) > 0) {
				$Part->setSentence($this);
				$this->parts []= $Part;
			}
		}
	}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhrase(): ?Phrase
    {
        return $this->phrase;
    }

    public function setPhrase(?Phrase $phrase): static
    {
        $this->phrase = $phrase;

        return $this;
    }

    /**
     * @return Collection<int, PhrasePart>
     */
    public function getParts(): Collection
    {
        return $this->parts;
    }

    public function addPart(PhrasePart $part): static
    {
        if (!$this->parts->contains($part)) {
            $this->parts->add($part);
            $part->setSentence($this);
        }

        return $this;
    }

    public function removePart(PhrasePart $part): static
    {
        if ($this->parts->removeElement($part)) {
            // set the owning side to null (unless already changed)
            if ($part->getSentence() === $this) {
                $part->setSentence(null);
            }
        }

        return $this;
    }
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		
		$jsonArray['parts'] = [];
		
		foreach ($this->parts as $Part) {
			$jsonArray['parts'] []= $Part->toJSON(true);
		}
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
}
