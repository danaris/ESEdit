<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use App\Entity\TaskDefinition;

class WaffleExtension extends AbstractExtension {
	
	protected $names = array('clean'=>'Cleaning', 'chore'=>'Other Chores', 'health'=>'Waffle Health', 'BR'=>'Upstairs Bathroom', 'DBR'=>'Downstairs Bathroom','Swiff'=>'Swiffing', 'Vacuum'=>'Vacuuming', 'OtherClean'=>'Other', 'Cats'=>'Cats', 'OtherChore'=>'Other', 'Food'=>'Food &amp; Weight', 'Mindful'=>'Mindfulness', 'OtherHealth'=>'Other');
	
	public function getFilters() {
		$filters = array();
		$filters []= new TwigFilter('catName', function ($string) {
			if (isset($this->names[$string])) {
				return $this->names[$string];
			} else {
				return $string;
			}
		});
		
		return $filters;
	}
}