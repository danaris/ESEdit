<?php
namespace App\CustomType;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use geoPHP;

class Polygon extends Type {
	public function getName(): string {
		return 'polygon';
	}

	public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string {
		return 'POLYGON';
	}

	/**
	 * @return mixed
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform): mixed {
		if ($value) {
			$value = geoPHP::load($value, 'wkb');
		}
		return $value;
	}

	public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed {
		return $value->out('wkt');
	}

	public function canRequireSQLConversion(): bool {
		return true;
	}

	public function convertToPHPValueSQL($sqlExpr, $platform): string {
		return sprintf('AsBinary(%s)', $sqlExpr);
	}

	public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string {
		return sprintf('GeomFromText(%s)', $sqlExpr);
	}
}
