<?php
namespace App\CustomType;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class Set extends Type {
	protected $values = array();

	public function getName() {
		return 'set';
	}

	public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
		$values = array_map(function($val) { return "'".$val."'"; }, $this->values);

		return "SET(".implode(", ", $values).")";
	}

	public function convertToPHPValue($value, AbstractPlatform $platform) {
		if ($value===null) return null;
		return explode(',', $value);
	}

	public function convertToDatabaseValue($value, AbstractPlatform $platform) {
/*	TODO: this doesn't work because we can't get the values from anywhere
		if (!in_array($value, $this->values)) {
			throw new \InvalidArgumentException("Invalid '".$this->name."' value.");
		}
*/
		if ($value===null) return null;
		$realValues = array();
		foreach ($value as $val) {
			if ($val == '') {
				continue;
			}
			$realValues []= $val;
		}
		return implode(',', $realValues);
	}

}