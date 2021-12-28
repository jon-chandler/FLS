<?php
namespace Application\Helper;

use Concrete\Core\Entity\Express\Association;
use Concrete\Core\Entity\Express\Entity;
use Concrete\Core\Entity\Express\Entry;

class Dev {
	/**
	 * @param Entity|Entry $thing
	 */
	public static function assocs($thing) {
		$out = [];
		$assocs  = $thing->getAssociations();
		
		/** @var Association $assoc */
		foreach ($assocs as $assoc) {
			$out[] = get_class($assoc);
		}
		return $out;
	}
}
