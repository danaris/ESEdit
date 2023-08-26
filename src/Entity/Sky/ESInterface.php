<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;
use App\Entity\TemplatedArray;

// Class representing a user interface, specified in a data file and filled with
// the contents of an Information object.
class ESInterface {
	private array $elements = []; // vector<Element *>
	private TemplatedArray $points; //map<string, Element>
	private array $values = []; // map<string, float>
	
	// Parse a set of tokens that specify horizontal and vertical alignment.
	public static function ParseAlignment(DataNode $node, int $i = 1): Point {
		$alignment = new Point();
		for ( ; $i < $node->size(); ++$i) {
			if ($node->getToken($i) == "left") {
				$alignment->setX(-1.);
			} else if ($node->getToken($i) == "top") {
				$alignment->setY(-1.);
			} else if ($node->getToken($i) == "right") {
				$alignment->setX(1.);
			} else if ($node->getToken($i) == "bottom") {
				$alignment->setY(1.);
			} else if ($node->getToken($i) != "center") {
				$node->printTrace("Skipping unrecognized alignment:");
			}
		}
		return $alignment;
	}
	
	public function __construct() {
		$this->points = new TemplatedArray(UIElement::class);
	}
	
	// Load an interface.
	public function load(DataNode $node): void {
		// Skip unnamed interfaces.
		if ($node->size() < 2) {
			return;
		}
		// Re-loading an interface always clears the previous interface, rather than
		// appending new elements to the end of it.
		$this->elements = [];
		$this->points = new TemplatedArray(UIElement::class);
		$this->values = [];
	
		// First, figure out the anchor point of this interface.
		$anchor = self::ParseAlignment($node, 2);
	
		// Now, parse the elements in it.
		$visibleIf = '';
		$activeIf = '';
		foreach ($node as $child) {
			if ($child->getToken(0) == "anchor") {
				$anchor = self::ParseAlignment($child);
			} else if ($child->getToken(0) == "value" && $child->size() >= 3) {
				$this->values[$child->getToken(1)] = $child->getValue(2);
			} else if (($child->getToken(0) == "point" || $child->getToken(0) == "box") && $child->size() >= 2) {
				// This node specifies a named point where custom drawing is done.
				$this->points[$child->getToken(1)]->load($child, $anchor);
			} else if ($child->getToken(0) == "visible" || $child->getToken(0) == "active") {
				// This node alters the visibility or activation of future nodes.
				$str = ($child->getToken(0) == "visible" ? 'visibleIf' : 'activeIf');
				if ($child->size() >= 3 && $child->getToken(1) == "if") {
					$$str = $child->getToken(2);
				} else {
					$$str = '';
				}
			} else {
				// Check if this node specifies a known element type.
				if ($child->getToken(0) == "sprite" || $child->getToken(0) == "image" || $child->getToken(0) == "outline") {
					$this->elements []= new ImageElement($child, $anchor);
				} else if ($child->getToken(0) == "label" || $child->getToken(0) == "string" || $child->getToken(0) == "button") {
					$this->elements []= new TextElement($child, $anchor);
				} else if ($child->getToken(0) == "bar" || $child->getToken(0) == "ring") {
					$this->elements []= new BarElement($child, $anchor);
				} else if ($child->getToken(0) == "pointer") {
					$this->elements []= new PointerElement($child, $anchor);
				} else if ($child->getToken(0) == "line") {
					$this->elements []= new LineElement($child, $anchor);
				} else {
					$child->printTrace("Skipping unrecognized element:");
					continue;
				}
	
				// If we get here, a new element was just added.
				$lastElement = $this->elements[array_key_last($this->elements)];
				$lastElement->setConditions($visibleIf, $activeIf);
			}
		}
	}
	
	// Check if a named point exists.
	public function hasPoint(string $name): bool {
		return in_array($name, $this->points);
	}
	
	// Get the center of the named point.
	public function getPoint(string $name): Point {
		if (isset($this->points[$name])) {
			return $this->points[$name]->getBounds()->getCenter();
		}
		
		return new Point();
	}
	// 
	// 
	// 
	// Rectangle Interface::GetBox(const string &name) const
	// {
	// 	auto it = points.find(name);
	// 	if (it == points.end())
	// 		return Rectangle();
	// 
	// 	return it->second.Bounds();
	// }
	// 
	// 
	// 
	// // Get a named value.
	// double Interface::GetValue(const string &name) const
	// {
	// 	auto it = values.find(name);
	// 	return (it == values.end() ? 0. : it->second);
	// }
	// 
	// 
	// 
	// // Members of the AnchoredPoint class:
	// 
	// // Get the point's location, given the current screen dimensions.
	// Point Interface::AnchoredPoint::Get() const
	// {
	// 	return position + .5 * Screen::Dimensions() * anchor;
	// }
	// 
	// 
	// 
	// void Interface::AnchoredPoint::Set(const Point &position, const Point &anchor)
	// {
	// 	this->position = position;
	// 	this->anchor = anchor;
	// }

}

class AnchoredPoint {
	private Point $position;
	private Point $anchor;
	
	public function __construct() {
		$this->position = new Point();
		$this->anchor = new Point();
	}
	
	// Get the point's location, given the current screen dimensions.
	public function get(): Point {
		// Putting in a placeholder screen size, because this won't be able to get the real one
		return $this->position->add((new Point(3840,2160))->mult($this->anchor)->mult(0.5));
	}
	
	public function set(Point $position, Point $anchor) {
		$this->position = $position;
		$this->anchor = $anchor;
	}
}

class UIElement {
	// State enumeration:
	const INACTIVE = 0;
	const ACTIVE = 1;
	const HOVER = 2;
	
	public function __construct() {
		$this->from = new AnchoredPoint();
		$this->to = new AnchoredPoint();
		$this->alignment = new Point();
		$this->padding = new Point();
	}
	
	// Create a new element. The alignment of the interface that contains
	// this element is used to calculate the element's position.
	public function load(DataNode $node, Point $globalAnchor) {
		// A location can be specified as:
		// center (+ dimensions):
		$hasCenter = false;
		$dimensions = new Point();
	
		// from (+ dimensions):
		$fromPoint = new Point();
		$fromAnchor = $globalAnchor;
	
		// from + to:
		$hasTo = false;
		$toPoint = new Point();
		$toAnchor = $globalAnchor;
	
		// Assume that the subclass constructor already parsed this line of data.
		foreach ($node as $child) {
			$key = $child->getToken(0);
			if ($key == "align" && $child->size() > 1) {
				$this->alignment = ESInterface::ParseAlignment($child);
			} else if ($key == "dimensions" && $child->size() >= 3) {
				$dimensions = new Point($child->getValue(1), $child->getValue(2));
			} else if ($key == "width" && $child->size() >= 2) {
				$dimensions->setX($child->getValue(1));
			} else if ($key == "height" && $child->size() >= 2) {
				$dimensions->setY($child->getValue(1));
			} else if ($key == "center" && $child->size() >= 3) {
				if ($child->size() > 3) {
					$fromAnchor = $toAnchor = ESInterface::ParseAlignment($child, 3);
				}
				// The "center" key implies "align center."
				$alignment = new Point();
				$fromPoint = $toPoint = new Point($child->getValue(1), $child->getValue(2));
				$hasCenter = true;
			} else if ($key == "from" && $child->size() >= 6 && $child->getToken(3) == "to") {
				// Anything after the coordinates is an anchor point override.
				if ($child->size() > 6) {
					$fromAnchor = $toAnchor = ESInterface::ParseAlignment($child, 6);
				}
	
				$fromPoint = new Point($child->getValue(1), $child->getValue(2));
				$toPoint = new Point($child->getValue(4), $child->getValue(5));
				$hasTo = true;
			} else if ($key == "from" && $child->size() >= 3) {
				// Anything after the coordinates is an anchor point override.
				if ($child->size() > 3) {
					$fromAnchor = ESInterface::ParseAlignment($child, 3);
				}
				$fromPoint = new Point($child->getValue(1), $child->getValue(2));
			} else if ($key == "to" && $child->size() >= 3) {
				// Anything after the coordinates is an anchor point override.
				if ($child->size() > 3) {
					$toAnchor = ESInterface::ParseAlignment($child, 3);
				}
	
				$toPoint = new Point($child->getValue(1), $child->getValue(2));
				$hasTo = true;
			} else if ($key == "pad" && $child->size() >= 3) {
				// Add this much padding when aligning the object within its bounding box.
				$this->padding = new Point($child->getValue(1), $child->getValue(2));
			} else if (!$this->ParseLine($child)) {
				$child->printTrace("Skipping unrecognized attribute:");
			}
		}
	
		// The "standard" way to specify a region is from + to. If it was specified
		// in a different way, convert it to that format:
		if ($hasCenter) {
			// Center alone or center + dimensions.
			$fromPoint->asSub($dimensions->mult(0.5));
			$toPoint->asAdd($dimensions->mult(0.5));
		} else if (!$hasTo) {
			// From alone or from + dimensions.
			$toPoint = $fromPoint->add($dimensions);
			$toAnchor = $fromAnchor;
		}
		$this->from->set($fromPoint, $fromAnchor);
		$this->to->set($toPoint, $toAnchor);
	}

// public:
// 	// Make sure the destructor is virtual, because classes derived from
// 	// this one will be used in a polymorphic list.
// 	Element() = default;
// 	virtual ~Element() = default;
// 
// 	// Create a new element. The alignment of the interface that contains
// 	// this element is used to calculate the element's position.
// 	void Load(const DataNode &node, const Point &globalAnchor);
// 
// 	// Draw this element, relative to the given anchor point. If this is a
// 	// button, it will add a clickable zone to the given panel.
// 	void Draw(const Information &info, Panel *panel) const;
// 
// Set the conditions that control when this element is visible and active.
// An empty string means it is always visible or active.
	public function setConditions(string $visible, string $active): void {
		$this->visibleIf = $visible;
		$this->activeIf = $active;
	}
// 	// Get the bounding rectangle, given the current screen dimensions.
// 	Rectangle Bounds() const;
// 
// protected:

// Parse the given data line: one that is not recognized by Element
// itself. This returns false if it does not recognize the line, either.
public function parseLine(DataNode $node): bool {
	return false;
}
// 	// Report the actual dimensions of the object that will be drawn.
// 	virtual Point NativeDimensions(const Information &info, int state) const;
// 	// Draw this element in the given rectangle.
// 	virtual void Draw(const Rectangle &rect, const Information &info, int state) const;
// 	// Add any click handlers needed for this element. This will only be
// 	// called if the element is visible and active.
// 	virtual void Place(const Rectangle &bounds, Panel *panel) const;

	protected AnchoredPoint $from;
	protected AnchoredPoint $to;
	protected Point $alignment;
	protected Point $padding;
	protected string $visibleIf;
	protected string $activeIf;
};

// This class handles "sprite", "image", and "outline" elements.
class ImageElement extends UIElement {
	// If a name is given, look up the sprite with that name and draw it.
	private string $name;
	// Otherwise, draw a sprite. Which sprite is drawn depends on the current
	// state of this element: inactive, active, or hover.
	private array $sprite = [null, null, null]; // [Sprite]
	// If this flag is set, draw the sprite as an outline:
	private bool $isOutline = false;
	// Store whether the outline should be colored.
	private bool $isColored = false;
	
// public:
// 	ImageElement(const DataNode &node, const Point &globalAnchor);
// 
// protected:
// Parse the given data line: one that is not recognized by Element
// itself. This returns false if it does not recognize the line, either.
public function parseLine(DataNode $node): bool {
	// The "inactive" and "hover" sprite only applies to non-dynamic images.
	// The "colored" tag only applies to outlines.
	if ($node->getToken(0) == "inactive" && $node->size() >= 2 && $this->name == '') {
		$this->sprite[Element::INACTIVE] = SpriteSet::Get($node->getToken(1));
	} else if($node->getToken(0) == "hover" && $node->size() >= 2 && $this->name == '') {
		$this->sprite[Element::HOVER] = SpriteSet::Get($node->getToken(1));
	} else if ($this->isOutline && $node->getToken(0) == "colored") {
		$this->isColored = true;
	} else {
		return false;
	}

	return true;
}

// 	// Report the actual dimensions of the object that will be drawn.
// 	virtual Point NativeDimensions(const Information &info, int state) const override;
// 	// Draw this element in the given rectangle.
// 	virtual void Draw(const Rectangle &rect, const Information &info, int state) const override;
// 
// private:
// 	const Sprite *GetSprite(const Information &info, int state) const;
};

// This class handles "label", "string", and "button" elements.
class TextElement extends UIElement {
// public:
// 	TextElement(const DataNode &node, const Point &globalAnchor);
// 
// protected:
// 	// Parse the given data line: one that is not recognized by Element
// 	// itself. This returns false if it does not recognize the line, either.
// 	virtual bool ParseLine(const DataNode &node) override;
// 	// Report the actual dimensions of the object that will be drawn.
// 	virtual Point NativeDimensions(const Information &info, int state) const override;
// 	// Draw this element in the given rectangle.
// 	virtual void Draw(const Rectangle &rect, const Information &info, int state) const override;
// 	// Add any click handlers needed for this element. This will only be
// 	// called if the element is visible and active.
// 	virtual void Place(const Rectangle &bounds, Panel *panel) const override;
// 
// private:
// 	string GetString(const Information &info) const;
// 
// private:
	// The string may either be a name of a dynamic string, or static text.
	private string $str;
	// Color for inactive, active, and hover states.
	private array $color = [null, null, null];
	private int $fontSize = 14;
	private string $buttonKey = '\0';
	private bool $isDynamic = false;
	private Truncate $truncate = Truncate::NONE;
};

// This class handles "bar" and "ring" elements.
class BarElement extends UIElement {
// public:
// 	BarElement(const DataNode &node, const Point &globalAnchor);
// 
// protected:
// 	// Parse the given data line: one that is not recognized by Element
// 	// itself. This returns false if it does not recognize the line, either.
// 	virtual bool ParseLine(const DataNode &node) override;
// 	// Draw this element in the given rectangle.
// 	virtual void Draw(const Rectangle &rect, const Information &info, int state) const override;
// 
// private:
	private string $name;
	private ?Color $color = null;
	private float $width = 2.0;
	private bool $isRing = false;
	private float $spanAngle = 360.;
	private float $startAngle = 0.;
};


// This class handles "pointer" elements.
class PointerElement extends UIElement {
// public:
// 	PointerElement(const DataNode &node, const Point &globalAnchor);
// 
// protected:
// 	// Parse the given data line: one that is not recognized by Element
// 	// itself. This returns false if it does not recognize the line, either.
// 	virtual bool ParseLine(const DataNode &node) override;
// 	// Draw this element in the given rectangle.
// 	virtual void Draw(const Rectangle &rect, const Information &info, int state) const override;
// 
// private:
	private ?Color $color = null;
	private Point $orientation;
};


// This class handles "line" elements.
class LineElement extends UIElement {
// public:
// 	LineElement(const DataNode &node, const Point &globalAnchor);
// 
// protected:
// 	// Parse the given data line: one that is not recognized by Element
// 	// itself. This returns false if it does not recognize the line, either.
// 	virtual bool ParseLine(const DataNode &node) override;
// 	// Draw this element in the given rectangle.
// 	virtual void Draw(const Rectangle &rect, const Information &info, int state) const override;
// 
// private:
	private ?Color $color = null;
};