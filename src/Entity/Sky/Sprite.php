<?php

namespace App\Entity\Sky;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\DataNode;

#[ORM\Entity]
#[ORM\Table(name: 'Sprite')]
#[ORM\HasLifecycleCallbacks]
class Sprite {
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private int $id;
	
	#[ORM\Column(type: 'string', name: 'name')]
	private string $name = '';
	private array $texture = [0, 0];
	
	#[ORM\Column(type: 'float', name: 'width')]
	private float $width = 0.0;
	#[ORM\Column(type: 'float', name: 'height')]
	private float $height = 0.0;
	#[ORM\Column(type: 'integer', name: 'frames')]
	private int $frames = 0;

    #[ORM\OneToMany(mappedBy: 'sprite', targetEntity: SpritePath::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $framePaths;
	private array $paths = [];
	
	public function __construct(string $name) {
		$this->name = $name;
		$this->framePaths = new ArrayCollection();
	}
	
	public function getId(): int {
		return $this->id;
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function getPath(int $index = 0): string {
		if (isset($this->paths[$index])) {
			return $this->paths[$index];
		} else {
			return '';
		}
	}
	
	// Get the width, in pixels, of the 1x image.
	public function getWidth(): float {
		return $this->width;
	}
	
	// Set the width, in pixels, of the 1x image.
	public function setWidth(float $width): void {
		$this->width = $width;
	}
	
	// Get the height, in pixels, of the 1x image.
	public function getHeight(): float {
		return $this->height;
	}
	
	// Set the height, in pixels, of the 1x image.
	public function setHeight(float $height): void {
		$this->height = $height;
	}
	
	// Get the number of frames in the animation.
	public function getFrames(): int {
		return $this->frames;
	}
	
	public function setFrames(int $frames): void {
		$this->frames = $frames;
	}
	
	// Get the offset of the center from the top left corner; this is for easy
	// shifting of corner to center coordinates.
	public function getCenter(): Point {
		return new Point(.5 * $this->width, .5 * $this->height);
	}
	
	public function __toString() {
		return $this->name;
	}
	
	public function toJSON($justArray=false): array|string {
		$jsonArray = ['name'=>$this->name];
		$jsonArray['id'] = $this->id;
		$jsonArray['width'] = $this->width;
		$jsonArray['height'] = $this->height;
		$jsonArray['frames'] = $this->frames;
		
		if ($justArray) {
			return $jsonArray;
		}
		
		return json_encode($jsonArray);
	}

    /**
     * @return Collection<int, SpritePath>
     */
    public function getFramePaths(): Collection
    {
        return $this->framePaths;
    }

    public function addFramePath(SpritePath $framePath): static
    {
        if (!$this->framePaths->contains($framePath)) {
            $this->framePaths->add($framePath);
            $framePath->setSprite($this);
        }

        return $this;
    }

    public function removeFramePath(SpritePath $framePath): static
    {
        if ($this->framePaths->removeElement($framePath)) {
            // set the owning side to null (unless already changed)
            if ($framePath->getSprite() === $this) {
                $framePath->setSprite(null);
            }
        }

        return $this;
    }
	
	#[ORM\PreFlush]
	public function toDatabase(PreFlushEventArgs $eventArgs) {
		$handledPaths = [];
		foreach ($this->framePaths as $SpritePath) {
			if (!isset($this->paths[$SpritePath->getPathIndex()])) {
				$eventArgs->getEntityManager()->remove($SpritePath);
			} else if ($this->paths[$SpritePath->getPathIndex()] == $SpritePath->getPath()) {
				$handledPaths []= $SpritePath->getPathIndex();
			} else {
				$SpritePath->setPath($this->paths[$SpritePath->getPathIndex()]);
			}
		}
		foreach ($this->paths as $pathIndex => $pathString) {
			if (in_array($pathIndex, $handledPaths)) {
				continue;
			}
			$SpritePath = new SpritePath();
			$SpritePath->setSprite($this);
			$SpritePath->setPathIndex($pathIndex);
			$SpritePath->setPath($pathString);
			$SpritePath->setIs2x(false);
			$this->framePath []= $SpritePath;
		}
	}
	
	#[ORM\PostLoad]
	public function fromDatabase(PostLoadEventArgs $eventArgs) {
		foreach ($this->framePaths as $SpritePath) {
			$this->paths[$SpritePath->getPathIndex()] = $SpritePath->getPath();
		}
	}

}