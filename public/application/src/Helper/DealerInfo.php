<?php

namespace Application\Helper;

use Core;
use Database;

class DealerInfo {

	// Find a retailer for a specific vehicle
	public function getRetailerDetails($manufacturer_name, $manufacturer_model, $fuel_type, $trim) {
		$db = \Database::connection();
        $r = $db->executeQuery("SELECT * FROM scraped_vehicle_content WHERE manufacturer_name='". $manufacturer_name."' AND manufacturer_model='". $manufacturer_model ."' AND fuel_type='". $fuel_type ."' OR manufacturer_model_variant LIKE '%". $trim."%' AND manufacturer_model='". $manufacturer_model ."' ");        
        $res = $r->fetchAll();
        
        return $res;
	}

}