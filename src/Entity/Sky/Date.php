<?php

namespace App\Entity\Sky;

use App\Entity\DataNode;

class Date {
	
	private int $date = 0;
	
	private int $daysSinceEpoch = 0;
	
	private string $str = '';
	
	public function __construct(int $day, int $month, int $year) {
		$this->date = $day + ($month << 5) + ($year << 9);
	}
	
	public function getDate(): int {
		return $this->date;
	}
	
	// Get the current day of the month.
	public function getDay(): int {
		return ($this->date & 31);
	}
	// Get the current month (January = 1, rather than being zero-indexed).
	public function getMonth(): int {
		return (($this->date >> 5) & 15);
	}
	// Get the current year.
	public function getYear(): int {
		return ($this->date >> 9);
	}
}