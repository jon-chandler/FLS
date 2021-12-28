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
 * Personal Car
 */
class CarSharingPersonalCar extends CostCalculator
{
    /**
     * @param Connection $con
     * @param SessionAnswerService $sessionAnswerService
     * @param VehicleFormula $vehicleFormula
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
        $runningCosts = $this->con->fetchAssoc('SELECT personal_sharing_fee, parking_trip FROM insurance_price WHERE insurance_group = ?', [$vehicle['InsuranceGroup']]);

        $personalSharingFee = null;
        $parkingTrip = null;

        if ($runningCosts) {
            $personalSharingFee = (float) $runningCosts['personal_sharing_fee'];
            $parkingTrip = (float) $runningCosts['parking_trip'];
        }

        if ($this->isSnapshotAnswers) {
            if (array_key_exists('howMuchDoYouHave', $answers)) {
                $howMuchDoYouHave = (int) $answers['howMuchDoYouHave'];
            }

            if (array_key_exists('yourVehicleValuation', $answers)) {
                $yourVehicleValuation = $answers['yourVehicleValuation'][1];
            }

            if (array_key_exists('whatIsYourEstimatedMileage', $answers)) {
                $mileage = (int) $answers['whatIsYourEstimatedMileage'];
            }
        } else {
            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                [
                    'howMuchDoYouHave',
                    'whatVehicleDoYouHave',
                    'yourVehicleValuation',
                    'whatIsYourEstimatedMileage'
                ],
                $answers
            );

            if (array_key_exists('howMuchDoYouHave', $tempAnswers)) {
                $howMuchDoYouHave = (int) $tempAnswers['howMuchDoYouHave'][0]->getValue();
            }

            if (
                array_key_exists('vehicleValuation', $cacheValues)
                && array_key_exists('yourVehicleValuation', $tempAnswers)
            )
            {
                $vehicleValuationType = $tempAnswers['yourVehicleValuation'][0]->getOption()->getOptionTitle();
                $privateValuation = $cacheValues['vehicleValuation'];

                switch ($vehicleValuationType) {
                    case 'Private Sale':
                        $yourVehicleValuation = $privateValuation;
                        break;
                    case 'Part Exchange';
                        $yourVehicleValuation = (float) $privateValuation - $privateValuation / 20;
                        break;
                    case 'Car Buying Service':
                        $yourVehicleValuation = (float) $privateValuation - $privateValuation / 10;
                        break;
                    default:
                        $yourVehicleValuation = $privateValuation;
                }
            }

            if (array_key_exists('whatIsYourEstimatedMileage', $tempAnswers)) {
                $mileage = (int) $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue();
            }
        }

        if ($personalSharingFee) {
            $personalSharingFee = ((float) $personalSharingFee) / 8;
            $cost = (float) $personalSharingFee;
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Vehicle Fee')
                ->setCost($cost * 20)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                ->setCategory('Vehicle & Provider');
            $vehicleCosts[] = $vehicleCost;
        }

        $cost = 220.00;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Insurance Fee')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
            ->setCategory('Vehicle & Provider');
        $vehicleCosts[] = $vehicleCost;

        if ($parkingTrip) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Parking (Out)')
                ->setCost($parkingTrip / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        if ($mileage) {
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
                ->setCost($cost / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        $totalCost = $this->getTotalCost($vehicleCosts, $answers);
        $cost = $totalCost;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Total Amount Payable')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PAID_OVER_TERM)
            ->setCategory('Vehicle & Provider')
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;

        if ($howMuchDoYouHave) {
            $cost = $howMuchDoYouHave;
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Your Lump Sum')
                ->setCost($cost)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_STARTING_POSITION)
                ->setCategory(VehicleCost::CAT_NET_POSITION)
                ->setIsIncludedInTotal(false)
                ->setIsIncludedInCatTotal(false);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($yourVehicleValuation) {
            $cost = $yourVehicleValuation;
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Your Vehicle Trade-In Value')
                ->setCost($cost)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_STARTING_POSITION)
                ->setCategory(VehicleCost::CAT_NET_POSITION)
                ->setIsIncludedInTotal(false)
                ->setIsIncludedInCatTotal(false);
            $vehicleCosts[] = $vehicleCost;
        }

        $cost = $totalCost;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_TOTAL_COST_OF_USE)
            ->setCost($cost)
            ->setCategory(VehicleCost::CAT_NET_POSITION)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;

        $remainingEquity = ($howMuchDoYouHave + $yourVehicleValuation) + ($totalCost * -1);
        $cost = $remainingEquity;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_REMAINING_STARTING_POSITION)
            ->setCost($cost)
            ->setCategory(VehicleCost::CAT_NET_POSITION)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;

        $cost = $remainingEquity;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_NET_POSITION_END_TERM)
            ->setCost($cost)
            ->setCategory(VehicleCost::CAT_NET_POSITION)
            ->setIsIncludedInTotal(false);
        $vehicleCosts[] = $vehicleCost;

        $cost = ($remainingEquity - $gfmv) - ($howMuchDoYouHave + $yourVehicleValuation);
        $vehicleCost = new VehicleCost();
        $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_NET_SPEND)
            ->setCost($cost)
            ->setCategory(VehicleCost::CAT_NET_POSITION)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;

        if ($mileage) {
            $cost = $totalCost / $mileage;
            $vehicleCost = new VehicleCost();
            $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PRICE_PER_MILE)
                ->setCost($cost)
                ->setCategory(VehicleCost::CAT_NET_POSITION)
                ->setIsIncludedInTotal(false)
                ->setIsIncludedInCatTotal(false);
            $vehicleCosts[] = $vehicleCost;
        }

        return $vehicleCosts;
    }
}
