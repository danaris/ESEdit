<?php

namespace App\Entity\Sky;

use App\Repository\Sky\PhraseChoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;

use App\Entity\DataNode;

#[ORM\Entity(repositoryClass: PhraseChoiceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PhraseChoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $phraseNamesStr = null;
	public array $phraseNames = [];

    #[ORM\ManyToMany(targetEntity: Phrase::class, cascade: ['persist'])]
    private Collection $phrases;

    #[ORM\ManyToOne(inversedBy: 'choices')]
    private ?PhrasePart $phrasePart = null;

	public function __construct(?DataNode $node = null, bool $isPhraseName = false) {
		$this->phrases = new ArrayCollection();
		if ($node) {
			$node->printTrace('Loading choice node:');
			// The given datanode should not have any children.
			if ($node->hasChildren()) {
				$node[0]->printTrace("Skipping unrecognized child node:");
			}
		
			if ($isPhraseName) {
				$this->phraseNames []= '';
				$this->phrases []= GameData::Phrases()[$node->getToken(0)];
				return;
			}
		
			// This node is a text string that may contain an interpolation request.
			$entry = $node->getToken(0);
			if($entry == '') {
				// A blank choice was desired.
				$this->phraseNames []= '';
				$this->phrases []= new Phrase();
				return;
			}
		
			$start = 0;
			$entryLength = strlen($entry);
			while ($start < $entryLength) {
				// Determine if there is an interpolation request in this string.
				$left = strpos($entry, '${', $start);
				if ($left === false || $left >= $entryLength) {
					break;
				}
				$right = strpos($entry, '}', $left);
				if ($right === false || $right >= $entryLength) {
					break;
				}
		
				// Add the text up to the ${, and then add the contained phrase name.
				$right++;
				$length = $right - $left;
				$text = substr($entry, $start, $left - $start);
				$phraseName = substr($entry, $left + 2, $length - 3);
				$this->phraseNames []= $text;
				$this->phrases []= new Phrase();
				$this->phraseNames []= '';
				$this->phrases []= GameData::Phrases()[$phraseName];
				$start = $right;
			}
			// Add the remaining text to the sequence.
			if(strlen($entry) - $start > 0) {
				$this->phraseNames []= substr($entry, $start);
				$this->phrases []= new Phrase();
			}
		}
	}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhraseNamesStr(): ?string
    {
        return $this->phraseNamesStr;
    }

    public function setPhraseNamesStr(string $phraseNamesStr): static
    {
        $this->phraseNamesStr = $phraseNamesStr;

        return $this;
    }

    /**
     * @return Collection<int, Phrase>
     */
    public function getPhrases(): Collection
    {
        return $this->phrases;
    }

    public function addPhrase(Phrase $phrase): static
    {
        if (!$this->phrases->contains($phrase)) {
            $this->phrases->add($phrase);
        }

        return $this;
    }

    public function removePhrase(Phrase $phrase): static
    {
        $this->phrases->removeElement($phrase);

        return $this;
    }

    public function getPhrasePart(): ?PhrasePart
    {
        return $this->phrasePart;
    }

    public function setPhrasePart(?PhrasePart $phrasePart): static
    {
        $this->phrasePart = $phrasePart;

        return $this;
    }
	
	public function get($index): array {
		return [$this->phraseNames[$index], $this->phrases[$index]];
	}
	
	public function count(): int {
		return count($this->phraseNames);
	}
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$this->phraseNamesStr = json_encode($this->phraseNames);
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		$this->phraseNames = json_decode($this->phraseNamesStr, true);
	}
	
	public function toJSON(bool $justArray=false): string|array {
		$jsonArray = [];
		
		$jsonArray['id'] = $this->id;
		
		$jsonArray['phraseNames'] = $this->phraseNames;
		$jsonArray['phrases'] = [];
		
		foreach ($this->phrases as $Phrase) {
			if ($Phrase->isEmpty()) {
				$jsonArray['phrases'] []= null;
			} else {
				$jsonArray['phrases'] []= $Phrase->toJSON(true);
			}
		}
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}
}
