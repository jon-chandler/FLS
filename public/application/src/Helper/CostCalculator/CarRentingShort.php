<?php

declare(strict_types=1);

namespace Application\Helper\CostCalculator;

use Application\Helper\CostCalculator\CostCalculator;
use Application\Helper\VehicleFormula;
use Application\Service\SessionAnswerService;
use Application\Model\VehicleCost;
use Concrete\Core\Database\Connection\Connection;

/**
 * Cost calculator class for:
 * Car
 * Renting
 * Short
 */
class CarRentingShort extends CostCalculator
{
    /**
     * @param Connection $con
     * @param VehicleFormula $vehicleFormula
     * @param SessionAnswerService $sessionAnswerService
     */
    public function __construct(
        Connection $con,
        VehicleFormula $vehicleFormula,
        SessionAnswerService $sessionAnswerService
    )
    {
        parent::__construct($con, $sessionAnswerService, $vehicleFormula);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateCosts(array $vehicle, array $answers, array $cacheValues = []): array
    {
        $vehicleCosts = [];
        $runningCosts = $this->con->fetchAssoc('SELECT car_rental_fee, parking_home FROM insurance_price WHERE insurance_group = ?', [$vehicle['InsuranceGroup']]);

        $parkingHome = null;
        $carRrentalFee = null;

        if ($runningCosts) {
            $parkingHome = (float) $runningCosts['parking_home'];
            $carRrentalFee = (float) $runningCosts['car_rental_fee'];
        }

        if ($carRrentalFee) {
            $cost = $runningCosts['car_rental_fee'];
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Vehicle Fee')
                ->setCost(round($cost * 20))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                ->setCategory('Vehicle & Provider');
            $vehicleCosts[] = $vehicleCost;
        }

        $mileage = $this->getEstimatedMileage($answers);
        $cost = $this->vehicleFormula->calcAnnualFuelCost(
            $vehicle['VehicleType'],
            $vehicle['FuelType'],
            (float) $vehicle['CombinedMPG'],
            (float) $vehicle['BatKWH'],
            $vehicle['BatMileage'],
            $mileage,
            (float) $vehicle['MIKGH2']
        );
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Fuel')
            ->setCost(round($cost / 12))
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
            ->setCategory('Running');
        $vehicleCosts[] = $vehicleCost;

        if ($parkingHome) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Parking (Home)')
                ->setCost(round($parkingHome / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        // START OF MUST BE LAST
        $cost = $this->getTotalCost($vehicleCosts, $answers);
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Total Amount Payable')
            ->setCost(round($cost))
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PAID_OVER_TERM)
            ->setCategory('Vehicle & Provider')
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;
        // END OF MUST BE LAST

        return $vehicleCosts;
    }
}
