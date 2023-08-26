<?php

namespace App\Entity\Sky;

class Point {
	
	public function __construct(public float $x = 0.0, public float $y = 0.0) {
	}
	
	public function X(): float {
		return $this->x;
	}
	
	public function Y(): float {
		return $this->y;
	}
	
	public function setX(float $newX): void {
		$this->x = $newX;
	}
	
	public function setY(float $newY): void {
		$this->y = $newY;
	}
	
	public function not(): bool {
		return !$this->x & !$this->y;
	}
	
	public function add(Point $point): Point {
		return new Point($this->x + $point->x, $this->y + $point->y);
	}
	
	public function asAdd(Point $point): Point {
		$this->x += $point->x;
		$this->y += $point->y;
		return $this;
	}
	
	public function sub(Point $point): Point {
		return new Point($this->x - $point->x, $this->y - $point->y);
	}
	
	public function asSub(Point $point): Point {
		$this->x -= $point->x;
		$this->y -= $point->y;
		return $this;
	}
	
	public function negate(): Point {
		return new Point(-$this->x, -$this->y);
	}
	
	public function mult(float $scalar): Point {
		return new Point($this->x * $scalar, $this->y * $scalar);
	}
	
	public function asMult(float $scalar): Point {
		$this->x *= $scalar;
		$this->y *= $scalar;
		return $this;
	}
	
	public function multPoint(Point $other): Point {
		return new Point($this->x * $other->x, $this->y * $other->y);
	}
	
	public function asMultPoint(Point $other): Point {
		$this->x *= $other->x;
		$this->y *= $other->y;
		return $this;
	}
	
	public function div(float $scalar): Point {
		return new Point($this->x / $scalar, $this->y / $scalar);
	}
	
	public function asDiv(float $scalar): Point {
		$this->x /= $scalar;
		$this->y /= $scalar;
		return $this;
	}
	
	public function set(float $x, float $y): void {
		$this->x = $x;
		$this->y = $y;
	}
	
	public function dot(Point $point): float {
		return $this->x * $point->x + $this->y * $point->y;
	}
	
	public function cross(Point $point): float {
		return $this->x * $point->y - $this->y * $point->x;
	}
	
	public function getLength(): float {
		return sqrt($this->x * $this->x + $this->y * $this->y);
	}
	
	public function getLengthSquared(): float {
		return $this->dot($this);
	}
	
	public function getUnit(): Point {
		$b = $this->x * $this->x + $this->y * $this->y;
		if (!$b) {
			return new Point(1.0, 0.0);
		}
		$b = 1.0 / sqrt($b);
		return new Point($this->x * $b, $this->y * $b);
	}
	
	public function getDistance(Point $point): float {
		return $this->sub($point)->getLength();
	}
	
	public function getDistanceSquared(Point $point): float {
		return $this->sub($point)->getLengthSquared();
	}
	
	// Absolute value of both coordinates.
	public static function abs(Point $p): Point {
		return new Point(abs($p->x), abs($p->y));
	}
	
	// Take the min of the x and y coordinates.
	public static function min(Point $p, Point $q): Point {
		return new Point(min($p->x, $q->x), min($p->y, $q->y));
	}
	
	// Take the max of the x and y coordinates.
	public static function max(Point $p, Point $q): Point {
		return new Point(max($p->x, $q->x), max($p->y, $q->y));
	}
	
	public function toJSON(bool $justArray = false): string|array {
		$jsonArray = ['x'=>$this->x, 'y'=>$this->y];
		if ($justArray) {
			return $jsonArray;
		}
		return json_encode($jsonArray);
	}
	
	public function __toString(): string {
		return '('.$this->x.', '.$this->y.')';
	}

}