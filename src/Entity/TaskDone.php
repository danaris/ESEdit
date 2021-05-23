<?php

namespace App\Entity;

use App\Repository\TaskDoneRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TaskDoneRepository::class)
 */
class TaskDone
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=TaskDefinition::class, inversedBy="instances")
     * @ORM\JoinColumn(nullable=false)
     */
    private $definition;

    /**
     * @ORM\Column(type="datetime")
     */
    private $doneOn;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $doneBy;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $extra;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDefinition(): ?TaskDefinition
    {
        return $this->definition;
    }

    public function setDefinition(?TaskDefinition $definition): self
    {
        $this->definition = $definition;

        return $this;
    }

    public function getDoneOn(): ?\DateTimeInterface
    {
        return $this->doneOn;
    }

    public function setDoneOn(\DateTimeInterface $doneOn): self
    {
        $this->doneOn = $doneOn;

        return $this;
    }

    public function getDoneBy(): ?string
    {
        return $this->doneBy;
    }

    public function setDoneBy(string $doneBy): self
    {
        $this->doneBy = $doneBy;

        return $this;
    }

    public function getExtra(): ?string
    {
        return $this->extra;
    }

    public function setExtra(?string $extra): self
    {
        $this->extra = $extra;

        return $this;
    }
    
    public function donePretty() {
        $now = new \DateTime("now");
        $dateDiff = $this->doneOn->diff($now);
        $agoText = '';
        $ago = false;
    
        if ($dateDiff->days < 1) {
            $agoText = 'Today';
        } else if ($dateDiff->days < 2) {
            $agoText = 'Yesterday';
        } else if ($dateDiff->days < 7) {
            $agoText = $this->doneOn->format('l');
        } else if ($dateDiff->days < 14) {
            $agoText = 'Last '.$this->doneOn->format('l');
        } else {
            if ($this->doneOn->format('Y') == $now->format('Y')) {
                $agoText = $this->doneOn->format('M j');
            } else {
                $agoText = $this->doneOn->format('M j Y');
            }
        }
        
        return $agoText;
    }
}
