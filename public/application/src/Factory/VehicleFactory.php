<?php

declare(strict_types=1);

namespace Application\Factory;

/**
 * VehicleFactory creates an array in the format of a karfu_vehicle table record
 */
class VehicleFactory
{
    /**
     * Create array from a mix of karfu_vehicle table data & cap data
     * 
     * @param string $karfuVehicle
     * @param array $capData
     * 
     * @return array
     */
    public function createFromKarfuVehicleAndCapData(array $karfuVehicle, array $capData): array
    {       
        $vehicle = [];
        $vehicle['Price'] = $capData['currentValuations']['valuations'][0]['valuationPoints'][0]['value'];
        $vehicle['CO2GKM'] = $capData['vehicleDetails']['co2Rating'];
        $vehicle['FuelType'] = $capData['derivativeDetails']['fuelType']['name'];
        $vehicle['Introduced'] = $capData['derivativeDetails']['dates']['introduced'];
        $vehicle['BodyStyle'] = $capData['derivativeDetails']['bodyStyle']['name'];
        $vehicle['ManName'] = $capData['derivativeDetails']['brand']['name'];
        $vehicle['ModelName'] = $capData['derivativeDetails']['model']['name'];
        $vehicle['RangeName'] = $capData['derivativeDetails']['range']['name'];
        $vehicle['Trim'] = $capData['derivativeDetails']['trim']['name'];
        $vehicle['CombinedMPG'] = $karfuVehicle['CombinedMPG'];
        $vehicle['VehicleType'] = 'C';
        $vehicle['BatMileage'] = 0;
        $vehicle['BatKWH'] = 0;
        $vehicle['MIKGH2'] = 0.012874720;
        $vehicle['InsuranceGroup'] = '';
        $vehicle['ModelYear'] = '';
        $vehicle['KarfuBodyStyle'] = '';

        if ($karfuVehicle) {
            $vehicle['CombinedMPG'] = $karfuVehicle['CombinedMPG'];
            $vehicle['VehicleType'] = $karfuVehicle['VehicleType'];
            $vehicle['BatMileage'] = $karfuVehicle['BatMileage'];
            $vehicle['BatKWH'] = $karfuVehicle['BatKWH'];
            $vehicle['MIKGH2'] = $karfuVehicle['MIKGH2'];
            $vehicle['InsuranceGroup'] = $karfuVehicle['InsuranceGroup'];
            $vehicle['ModelYear'] = $karfuVehicle['ModelYear'];
            $vehicle['KarfuBodyStyle'] = $karfuVehicle['KarfuBodyStyle'];
        }

        return $vehicle;
    }
}
