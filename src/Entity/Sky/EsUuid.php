<?php

namespace App\Entity\Sky;

class EsUuid {
	private $value;
	
	public function __toString() {
		return 'UUID' . $this->value;
	}
}