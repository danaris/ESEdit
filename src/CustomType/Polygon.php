<?php
namespace App\CustomType;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use geoPHP;

class Polygon extends Type {
	public function getName() {
		return 'polygon';
	}

	public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform) {
		return 'POLYGON';
	}

	public function convertToPHPValue($value, AbstractPlatform $platform) {
		if ($value) {
			$value = geoPHP::load($value, 'wkb');
		}
		return $value;
	}

	public function convertToDatabaseValue($value, AbstractPlatform $platform) {
		return $value->out('wkt');
	}

	public function canRequireSQLConversion() {
		return true;
	}

	public function convertToPHPValueSQL($sqlExpr, $platform) {
		return sprintf('AsBinary(%s)', $sqlExpr);
	}

	public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform) {
		return sprintf('GeomFromText(%s)', $sqlExpr);
	}
}
