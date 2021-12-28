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
 * Sharing
 * Micromobility
 */
class CarSharingMicromobility extends CostCalculator
{
    /**
     * @param Connection $con
     * @param SessionAnswerService $sessionAnswerService
     */
    public function __construct(
        Connection $con,
        SessionAnswerService $sessionAnswerService,
        VehicleFormula $vehicleFormula
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

        $cost = 136.00;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Vehicle Fee')
            ->setCost(round($cost))
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
            ->setCategory('Vehicle & Provider');
        $vehicleCosts[] = $vehicleCost;

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
