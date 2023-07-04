<?php

namespace App\Entity\Sky;

class Description {
	public array $text = array();
	
	public function addText($text) {
		$this->text []= $text;
	}
}