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
 * Ride Pooling
 * Pay as you Go
 */
class CarRidePoolingPayg extends CostCalculator
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

        if ($this->isSnapshotAnswers) {
            $tempAnswers = $answers;
        } else {
            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                [
                    'journeyKind',
                    'howOften'
                ],
                $answers
            );
        }

        $journeyKindMiles = [];
        $journeyKindAnswers = $tempAnswers['journeyKind'];
        foreach ($journeyKindAnswers as $journeyKindAnswer) {
            if ($this->isSnapshotAnswers) {
                $journeyKindAnswerValue = $journeyKindAnswer[0];
                $journeyKindAnswerTitle = $journeyKindAnswer[1];
            } else {
                $journeyKindAnswerValue = $journeyKindAnswer->getValue();
                $journeyKindAnswerTitle = $journeyKindAnswer->getOption()->getOptionTitle();
            }

            switch ($journeyKindAnswerValue) {
                case 1:
                    $metric = 0;
                    break;
                case 2:
                    $metric = 0.55;
                    break;
                case 3:
                    $metric = 1.1;
                    break;
                case 4:
                    $metric = 5.55;
                    break;
                case 5:
                    $metric = 10;
                    break;
            }

            switch ($journeyKindAnswerTitle) {
                case 'SHORT':
                    $multiplier = 2;
                    break;
                case 'MEDIUM':
                    $multiplier = 8.4;
                    break;
                case 'LONG':
                    $multiplier = 35.28;
                    break;
            }
            $journeyKindMiles[] = $metric * $multiplier;
        }

        $splitTripMiles = array_sum($journeyKindMiles);

        $frequencys = [];
        $howOftenAnswers = $tempAnswers['howOften'];
        foreach ($howOftenAnswers as $howOftenAnswer) {
            if ($this->isSnapshotAnswers) {
                $howOftenAnswerTitle = $howOftenAnswer;
            } else {
                $howOftenAnswerTitle = $howOftenAnswer->getOption()->getOptionTitle();
            }
            switch ($howOftenAnswerTitle) {
                case 'All day every day':
                    $metric = 7;
                    break;
                case 'All':
                    $metric = 2;
                    break;
                case 'All':
                    $metric = 0.5;
                    break;
                case 'All':
                    $metric = 0.5;
                    break;
                case 'All':
                    $metric = 5;
                    break;
                case 'All':
                    $metric = 1;
                    break;
                case 'All':
                    $metric = 0.5;
                    break;
                case 'All':
                    $metric = 0.25;
                    break;
            }
            $frequencys[] = $metric;
        }

        if (in_array(7, $frequencys)) {
            $frequency = 7;
        } else {
            $frequency = array_sum($frequencys);
            $frequency = ($frequency > 7) ? 7 : $frequency;
        }

        $dailyMiles = ($splitTripMiles * $frequency) / 7;
        $mileCost = 1.40;
        $minimumFare = 3.00;
        $cleanAirCost = 0.13;
        $minuteCost = 0.12;
        $singleTripMiles = $dailyMiles / 3;
        $sustainaCost = $cleanAirCost * $singleTripMiles;
        $mileageCost = $singleTripMiles * $mileCost;
        $timePerMile = 2.5;
        $timeCost = ($timePerMile * $singleTripMiles) * $minuteCost;
        $singleFairCost = ($minimumFare + $sustainaCost + $mileageCost + $timeCost) / 2;
        $cost = ($singleFairCost * 3) * (365 / 12);

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
