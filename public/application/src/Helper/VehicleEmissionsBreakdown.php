<?php
	namespace Application\Helper;

	use Core;
	use Database;
	use Exception;
	
	class VehicleEmissionsBreakdown
	{


		// 
		public function getFuelSourceCO2($fuelType, $dist = NULL, $mpg = NULL) {

			$db = \Database::connection();
			$r = $db->executeQuery("SELECT * FROM fuel_energy_emissions where fuelType = ?", [$fuelType]);        
			$fuelInfo = $r->FetchRow();

			if($fuelInfo) {
				return $this->calcFuelEmissions($fuelInfo, $dist, $mpg);
			} else {
				throw new Exception('Unable to retrieve fuel breakdown');
			}

			return $fuelInfo;

		}

		private function calcFuelEmissions($fuelInfo, $dist = NULL, $mpg = NULL) {

			switch($fuelInfo['displayName']) {

				case 'Electric':

					$A = $this->wttAB($fuelInfo['gridLoss'], $dist, $fuelInfo['wattsPerMile']);
					$B = $this->wttAB($fuelInfo['genLoss'], $dist, $fuelInfo['wattsPerMile']);
					$total = $A + $B;

				break;

				case 'D/phev':
				case 'p/ELHEV':
				case 'p/HB':

					$A = $this->wttAB($fuelInfo['gridLoss'], $dist, $fuelInfo['wattsPerMile']);
					$B = $this->wttAB($fuelInfo['genLoss'], $dist, $fuelInfo['wattsPerMile']);
					$C = $this->wttCD($fuelInfo['genLoss'], $dist, $mpg);
					$total = $A + $B + $C;

				break;

				case 'Diesel':
				case 'Petrol':
				case 'p/LPG':

					$total = $this->wttCD($fuelInfo['genLoss'], $dist, $mpg);

				break;

				case 'Hydrogen':

					$total = $this->wttE($fuelInfo['genLoss'], $fuelInfo['wattsPerMile']);

				break;

				case 'Pedal':
				case 'Kick':
				default:

					$total = 0;

				break;

			}

			return ['total' => round($total), 'fuelType' => $fuelInfo['displayName']];

		}

		// THE NAMES OF THESE FUNCTIONS CORRESPOND TO THOSE IN THE SUPPLIED DOC
		// https://docs.google.com/spreadsheets/d/1-ssE-GuLRmFJX7ZowyVMwKw19MExw-1gQkXuj8ET5ic/ [DATA TO STORE TAB]

		// A, B
		public function wttAB($fuel, $dist, $wattsPM) {
			return round((($wattsPM * $dist) * $fuel) / 1000, 2);
		}

		// C, D
		public function wttCD($fuel, $dist, $mpg) {
			return round(((($fuel * 4.5) * ($dist * 10)) / $mpg), 2);
		}

		// E
		public function wttE($fuel, $wattsPM) {
			return round((($fuel * 1) + $wattsPM), 2);
		}

		////////////////////////////////////////////////////////////////


		public function getTotal($a = NULL, $b = NULL, $c = NULL, $d = NULL, $e = NULL, $f = NULL, $g = NULL) {
			$total = round(($a + $b + $c + $d + $e + $f + $g) /1000, 2);
			return ['total' => $total];
		}


		public function getLungsFilled($emissionsTotal, $lungCapacity) {
			// $lungCapacity in MCG
			$total = number_format((($emissionsTotal * 1000) * $lungCapacity));

			return ['total' => $total, 'lungCapacity' => $lungCapacity];
		}



		/////// NEW //////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////


		/**
		* 
		* @param int (optional) $batteryCapacity, int $carRatioKgBatteryToKWh
		* 
		* @return float
		*/
		private function calcBatteryWeight($batteryCapacity = NULL, $carRatioKgBatteryToKWh) {

			$batteryCapacity = ($batteryCapacity) ? $batteryCapacity : 1;
			
			return ($carRatioKgBatteryToKWh * $batteryCapacity);
		}


		/**
		* Get all static multipliers for use in the emissions calculations.
		* 
		* @return array
		* 
		* @throws Exception
		*/
		public static function getAllStaticValues() {
			$db = \Database::connection();
			$r = $db->executeQuery('SELECT * FROM  static_emission_values');
			$res = $r->fetchAll();

			if (!$res) {
				throw new Exception('No static values');
			}

			$vals = [];
			foreach ($res as $i=> $r) {
				$vals[$i]["name"] = $r["name"];
				$vals[$i]["group"] = $r["group"];
				$vals[$i]["value"] = $r["value"];
			}

			return $vals;
		}

		/**
		* Get a single static multipliers, by name, for use in the emissions calculations
		* 
		* @param string $key
		* 
		* @return float
		* 
		* @throws Exception
		*/
		public static function getStaticValueDB($key) {
			$db = \Database::connection();
			$r = $db->executeQuery("SELECT value FROM  static_emission_values where name =?", [$key]);
			$res = $r->FetchRow();

			if (!$res) {
				throw new Exception('No static value for: ' . $key);
			}

			return floatval($res['value']);
		}

		/**
		* Get a single multiplier, by key, from the existing *list* array, for use in the emissions calculations 'getAllStaticValues(list, key)'
		* 
		* @param array $list, string $key
		* 
		* @return float
		*/
		public static function getsingleStaticValueFromList($list, $key) {
			$idx = array_search($key, array_column($list, 'name'));

			return floatval($list[$idx]['value']);
		}


		/**
		* 
		* @param string $fuelType, int $vehicleWeight, int (optional) batteryKWH
		* 
		* @return array
		*/
		public function endOfLife($fuelType, $vehicleWeight, $batteryKWH = NULL) {

			$staticVals = $this->getAllStaticValues();

			$endOfLifeBatteryRecyclingFactor = $this->getsingleStaticValueFromList($staticVals, 'car_endoflife_batteryrecycling_emissions_scaling_factor');
 			$eolScalingFactor = $this->getsingleStaticValueFromList($staticVals, 'car_endoflife_carrecycling_emissions_scaling_factor');
 			$carRatioKgBatteryToKWh = $this->getsingleStaticValueFromList($staticVals, 'car_ratio_kg_battery_to_kWh');
			$batteryWeight = $this->calcBatteryWeight($batteryKWH, $carRatioKgBatteryToKWh);

			switch($fuelType) {
				case "Diesel":
				case "Petrol":
				case "Petrol/LPG":
				case "Petrol/Bio Ethanol (E85)":  
				case "Petrol/CNG":
				default:
					$total = ($eolScalingFactor * $vehicleWeight);
				break;
				case "Petrol/PlugIn Elec Hybrid":
				case "Petrol/Electric Hybrid":
				case "Diesel/PlugIn Elec Hybrid":
				case "Electric":
				case "Hydrogen Fuel Cell":
				case "Diesel/Electric Hybrid":       
				case "Electric Diesel REX":   
				case "Electric Petrol REX":
					$total = ($eolScalingFactor * ($vehicleWeight - $batteryWeight) - $endOfLifeBatteryRecyclingFactor * $batteryWeight);
				break;
			}

			return ['total' => round($total)];

		}

		/**
		* 
		* @param string $fuelType, int $vehicleWeight, int (optional) batteryKWH
		* 
		* @return array
		*/
		public function assemblyEmissions($fuelType, $vehicleWeight, $batteryKWH = NULL) {

			$staticVals = $this->getAllStaticValues();

			$emScalingFactor = $this->getsingleStaticValueFromList($staticVals, 'car_partmanufacturingandassembly_emissions_scaling_factor');
			$addOn = $this->getsingleStaticValueFromList($staticVals, 'car_partmanufacturingandassembly_emissions_add_on');
			$carRatioKgBatteryToKWh = $this->getsingleStaticValueFromList($staticVals, 'car_ratio_kg_battery_to_kWh');
			$batteryWeight = $this->calcBatteryWeight($batteryKWH, $carRatioKgBatteryToKWh);

			switch($fuelType) {
				case "Diesel":
				case "Petrol":
				case "Petrol/LPG":
				case "Petrol/Bio Ethanol (E85)":  
				case "Petrol/CNG":
				default:
					$emissions = (($emScalingFactor * $vehicleWeight) + $addOn);
				break;
				case "Petrol/PlugIn Elec Hybrid":
				case "Petrol/Electric Hybrid":
				case "Diesel/PlugIn Elec Hybrid":
				case "Electric":
				case "Hydrogen Fuel Cell":
				case "Diesel/Electric Hybrid":       
				case "Electric Diesel REX":   
				case "Electric Petrol REX":
					$emissions = (($emScalingFactor * ($vehicleWeight - $batteryWeight)) + $addOn);
				break;
			}

			return ['total' => round($emissions)];
		}

		/**
		* 
		* @param int $km, int $dist, int $co2
		* 
		* @return array
		*/
		public function deliveryEmissions($km, $dist, $co2) {
			$emissions = round(($km * $co2)/1000);
			return ['total' => $emissions, 'CO2' => $co2, 'distance' => $km];
		}


		public function batteryProduction($fuelType, $batteryKWH = NULL) {

			$fuelType = 'Electric';
			$batteryKWH = 64;

			$staticVals = $this->getAllStaticValues();
			$carRatioKgBatteryToKWh = $this->getsingleStaticValueFromList($staticVals, 'car_ratio_kg_battery_to_kWh');
			$batteryWeight = $this->calcBatteryWeight($batteryKWH, $carRatioKgBatteryToKWh);
			$hydroScaling = $this->getsingleStaticValueFromList($staticVals, 'car_batteryproduction_emissions_per_kg_fuelcell');
			$batScaling = $this->getsingleStaticValueFromList($staticVals, 'car_batteryproduction_emissions_per_kg_battery');
			
			if($fuelType === "Hydrogen Fuel Cell")	{
				$total = ($hydroScaling * $batteryWeight);
			} else {
				$total = ($batScaling * $batteryWeight);
			}

			return ['total' => round($total)];
		}

		/**
		* 
		* @param int $vehicleWeight
		* 
		* @return array
		*/
		public function materialEmissions($vehicleWeight) {

			$db = \Database::connection();
			$r = $db->executeQuery("SELECT * FROM materials");        
			$materials = $r->fetchAll();

			$materialEmissions = [];
			$total = 0;
			if($materials) {

				foreach($materials as $i => $material) {
					$CO2e = round($material['prop_by_weight'] * $material['CO2e'] * $vehicleWeight, 2);
					$materialEmissions[] = ['material' => $material['material'], 'CO2e' => $CO2e];
					$total += $CO2e;
				}

			}

			array_push($materialEmissions, ['total' => $total]);

			return $materialEmissions;

		}


	}
?>