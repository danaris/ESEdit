<?php
namespace App\CustomType;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Enum extends Type {
	protected $values = array();
	
	public function getName(): string {
		return 'enum';
	}
	
	public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string {
		$values = array_map(function($val) { return "'".$val."'"; }, $this->values);

		return "ENUM(".implode(", ", $values).")";
	}

	public function convertToPHPValue($value, AbstractPlatform $platform): mixed {
		if ($value===null) return null;
		return $value;
	}

	public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed {
/*	TODO: this doesn't work because we can't get the values from anywhere
		if (!in_array($value, $this->values)) {
			throw new \InvalidArgumentException("Invalid '".$this->name."' value '".$value."'.");
		}
*/
		if ($value===null) return null;
		return $value;
	}

}