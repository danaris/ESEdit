<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;

use App\Entity\TemplatedArray;

class TemplatedArrayService {
	private array $templatedArrays = [];
	private EntityManagerInterface $em;
	
	private static ?TemplatedArrayService $instance = null;
	
	public static function Instance(): TemplatedArrayService {
		if (!self::$instance) {
			self::$instance = new TemplatedArrayService();
		}
		return self::$instance;
	}
	
	#[Required]
	public function withEntityManager(EntityManagerInterface $em): static {
		$new = clone $this;
		$new->em = $em;
		
		return $new;
	}
	public function setEntityManager(EntityManagerInterface $em) {
		$this->em = $em;
	}
	
	public function createTemplatedArray($type, string $nameColumn = 'name'): TemplatedArray {
		$array = new TemplatedArray($type, $nameColumn);
		$array->setEM($this->em);
		$this->templatedArrays []= $array;
		
		return $array;
	}
}