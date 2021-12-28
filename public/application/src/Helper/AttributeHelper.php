<?php 

namespace Application\Helper;

use Database;

class AttributeHelper {


	public function getOptionValues(string $handle) 
	{

		$db = Database::connection();
		$id = $db->GetAll("SELECT akID FROM AttributeKeys WHERE akHandle = ?", array($handle));
		$values = $db->GetAll("SELECT d.value FROM atSelectOptions d JOIN atSelectSettings v ON d.avSelectOptionListID = v.avSelectOptionListID WHERE akID = ?", array($id[0]['akID']));

		return $values;
	}


}