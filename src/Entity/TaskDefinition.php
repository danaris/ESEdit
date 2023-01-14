<?php

namespace App\Entity;

use App\Repository\TaskDefinitionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TaskDefinitionRepository::class)
 */
class TaskDefinition
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $viewOrder;

    /**
     * @ORM\ManyToOne(targetEntity=TaskCategory::class, inversedBy="tasks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $extra;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $altButton;

    /**
     * @ORM\Column(type="integer")
     */
    private $redDays;

    /**
     * @ORM\Column(type="boolean")
     */
    private $inNeed;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $belongsTo;

    /**
     * @ORM\OneToMany(targetEntity=TaskDone::class, mappedBy="definition")
     * @ORM\OrderBy({"doneOn" = "DESC"})
     */
    private $instances;
    
    /* Unmapped bool */
    private $red;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    public function __construct()
    {
        $this->instances = new ArrayCollection();
        $this->red = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?TaskCategory
    {
        return $this->category;
    }

    public function setCategory(?TaskCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getExtra(): ?string
    {
        return $this->extra;
    }

    public function setExtra(string $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    public function getAltButton(): ?string
    {
        return $this->altButton;
    }

    public function setAltButton(string $altButton): self
    {
        $this->altButton = $altButton;

        return $this;
    }

    public function getRedDays(): ?int
    {
        return $this->redDays;
    }

    public function setRedDays(int $redDays): self
    {
        $this->redDays = $redDays;

        return $this;
    }

    public function getInNeed(): ?bool
    {
        return $this->inNeed;
    }

    public function setInNeed(bool $inNeed): self
    {
        $this->inNeed = $inNeed;

        return $this;
    }

    public function getBelongsTo(): ?string
    {
        return $this->belongsTo;
    }

    public function setBelongsTo(string $belongsTo): self
    {
        $this->belongsTo = $belongsTo;

        return $this;
    }

    public function getRed(): ?bool
    {
        return $this->red;
    }

    public function setRed(bool $red): self
    {
        $this->red = $red;

        return $this;
    }

    /**
     * @return Collection|TaskDone[]
     */
    public function getInstances(): Collection
    {
        return $this->instances;
    }

    public function addInstance(TaskDone $instance): self
    {
        if (!$this->instances->contains($instance)) {
            $this->instances[] = $instance;
            $instance->setDefinition($this);
        }

        return $this;
    }

    public function removeInstance(TaskDone $instance): self
    {
        if ($this->instances->removeElement($instance)) {
            // set the owning side to null (unless already changed)
            if ($instance->getDefinition() === $this) {
                $instance->setDefinition(null);
            }
        }

        return $this;
    }
    
    public function belongsToWaffle($waffle) {
        if (!$waffle) {
            $waffle = 'none';
        }
        if ($this->belongsTo) {
            $belong = $this->belongsTo == "All Wafs" || str_contains($this->belongsTo, $waffle);
            // $is = 'is';
            // if (!$belong) {
            //     $is = 'is not';
            // }
            //error_log("Task ".$this->name." belongs to ".$this->belongsTo.", which $is the same as ".$waffle);
        } else {
            $belong = true;
        }
        return $belong;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
