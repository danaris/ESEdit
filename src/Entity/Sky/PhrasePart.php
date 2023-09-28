<?php

namespace App\Entity\Sky;

use App\Repository\Sky\PhrasePartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;
use OutOfBoundsException;

#[ORM\Entity(repositoryClass: PhrasePartRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PhrasePart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\OneToMany(mappedBy: 'phrasePart', targetEntity: PhraseChoice::class, cascade: ['persist'])]
    private Collection $choices;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $choiceWeightsStr = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $replacementsStr = null;

    #[ORM\ManyToOne(inversedBy: 'parts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PhraseSentence $sentence = null;

    public array $choiceWeights = [];
    public array $replacements = [];

    private int $total = -1;

    public function __construct()
    {
        $this->choices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, PhraseChoice>
     */
    public function getChoices(): Collection
    {
        return $this->choices;
    }

    public function addChoice(PhraseChoice $choice): static
    {
        if (!$this->choices->contains($choice)) {
            $this->choices->add($choice);
            $choice->setPhrasePart($this);
        }

        return $this;
    }

    public function removeChoice(PhraseChoice $choice): static
    {
        if ($this->choices->removeElement($choice)) {
            // set the owning side to null (unless already changed)
            if ($choice->getPhrasePart() === $this) {
                $choice->setPhrasePart(null);
            }
        }

        return $this;
    }

    public function getChoiceWeightsStr(): ?string
    {
        return $this->choiceWeightsStr;
    }

    public function setChoiceWeightsStr(string $choiceWeightsStr): static
    {
        $this->choiceWeightsStr = $choiceWeightsStr;

        return $this;
    }

    public function getReplacementsStr(): ?string
    {
        return $this->replacementsStr;
    }

    public function setReplacementsStr(string $replacementsStr): static
    {
        $this->replacementsStr = $replacementsStr;

        return $this;
    }

    public function getSentence(): ?PhraseSentence
    {
        return $this->sentence;
    }

    public function setSentence(?PhraseSentence $sentence): static
    {
        $this->sentence = $sentence;

        return $this;
    }

    public function getReplacements(): array {
        return $this->replacements;
    }

    private function getTotal(): int {
        if ($this->total == -1) {
            $this->total = 0;
            foreach ($this->choiceWeights as $weight) {
                $this->total += $weight;
            }
        }
        return $this->total;
    }

    public function getChoice(): PhraseChoice {
        if (count($this->choices) == 0) {
            throw new \OutOfBoundsException('Attempted to call get on a phrase with no choices');
        }

        $index = 0;
        for ($choice = Random::Int($this->getTotal()); $choice >= $this->choiceWeights[$index]; $index++) {
            $choice -= $this->choiceWeights[$index];
        }

        return $this->choices[$index];
    }
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->choiceWeightsStr = json_encode($this->choiceWeights);
		$this->replacementsStr = json_encode($this->replacements);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->choiceWeights = json_encode($this->choiceWeightsStr, true);
		$this->replacements = json_encode($this->replacementsStr, true);
	}
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		
		$jsonArray['choices'] = [];
		
		foreach ($this->choices as $Choice) {
			$jsonArray['choices'] []= $Choice->toJSON(true);
		}
		$jsonArray['choiceWeights'] = $this->choiceWeights;
		$jsonArray['replacements'] = $this->replacements;
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
}
