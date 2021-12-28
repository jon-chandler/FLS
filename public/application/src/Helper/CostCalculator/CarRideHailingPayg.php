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
 * Ride Hailing
 * Pay as you Go
 */
class CarRideHailingPayg extends CostCalculator
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
        $amountOfCredit = 0;
        $ownershipPeriod = null;
        $ownershipPeriodInMonths = null;
        $mileage = null;
        $howMuchDoYouHave = null;
        $yourVehicleValuation = null;
        $journeyKinds = null;
        $howOften = null;
        $deposit = 0;
        $priceAfterDeposit = null;
        $cashLumpSum = null;
        $vehicleTradeInValue = null;
        $splitTripMiles = null;
        $frequency = null;
        $otrPrice = 0;

        if ($this->isSnapshotAnswers) {
            if (array_key_exists('howMuchDoYouHave', $answers)) {
                $howMuchDoYouHave = (int) $answers['howMuchDoYouHave'];
            }

            if (array_key_exists('howMuchDoYouHave', $answers)) {
                $yourVehicleValuation = (int) $answers['yourVehicleValuation'][1];
            }

            if (array_key_exists('howLongTerm', $answers)) {
                $ownershipPeriod = (int) $answers['howLongTerm'];
                $ownershipPeriodInMonths = $ownershipPeriod * 12;
            }

            if (array_key_exists('yourVehicleValuation', $answers)) {
                $yourVehicleValuation = $answers['yourVehicleValuation'][1];
            }

            if (array_key_exists('whatIsYourEstimatedMileage', $answers)) {
                $mileage = (int) $answers['whatIsYourEstimatedMileage'];
            }

            if (array_key_exists('journeyKind', $answers)) {
                $journeyKind = $answers['journeyKind'];
            }

            if (array_key_exists('howOften', $answers)) {
                $howOften = $answers['howOften'];
            }
        } else {
            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                [
                    'howLongTerm',
                    'howMuchDoYouHave',
                    'whatVehicleDoYouHave',
                    'yourVehicleValuation',
                    'journeyKind',
                    'howOften',
                    'whatIsYourEstimatedMileage'
                ],
                $answers
            );

            if (array_key_exists('howMuchDoYouHave', $tempAnswers)) {
                $howMuchDoYouHave = (int) $tempAnswers['howMuchDoYouHave'][0]->getValue();
            }

            if (array_key_exists('howLongTerm', $tempAnswers)) {
                $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
                $ownershipPeriodInMonths = $ownershipPeriod * 12;
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

            if (array_key_exists('journeyKind', $tempAnswers)) {
                $journeyKind = array_map(
                    function ($journeyKind) {
                        return [
                            $journeyKind->getValue(),
                            $journeyKind->getOption()->getOptionTitle()
                        ];
                    },
                    $tempAnswers['journeyKind']
                );
            }

            if (array_key_exists('howOften', $tempAnswers)) {
                $howOften = array_map(
                    function ($howOften) {
                        return $howOften->getOption()->getOptionTitle();
                    },
                    $tempAnswers['howOften']
                );
            }
        }

        $dailyMiles = $mileage / 365;
        $mileCost = 1.40;
        $minimumFare = 3.00;
        $cleanAirCost = 0.13;
        $minuteCost = 0.12;
        $singleTripMiles = $dailyMiles / 2;
        $sustainaCost = $cleanAirCost * $singleTripMiles;
        $mileageCost = $singleTripMiles * $mileCost;
        $timePerMile = 2.5;
        $timeCost = ($timePerMile * $singleTripMiles) * $minuteCost;
        $singleFairCost = $minimumFare + $sustainaCost + $mileageCost + $timeCost;
        $cost = ($singleFairCost * 2) * (365 / 12);
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Vehicle Fee')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
        $vehicleCosts[] = $vehicleCost;

        $totalCost = $this->getTotalCost($vehicleCosts, $answers);
        $cost = $totalCost;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Total Amount Payable')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PAID_OVER_TERM)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER)
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

        $cost = $remainingEquity - ($howMuchDoYouHave + $yourVehicleValuation);
        $vehicleCost = new VehicleCost();
        $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_NET_SPEND)
            ->setCost($cost)
            ->setCategory(VehicleCost::CAT_NET_POSITION)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;

        if ($ownershipPeriod) {
            if ($mileage) {
                $cost = (($totalCost * -1) + $gfmv) / ($mileage * $ownershipPeriod);
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName(VehicleCost::FREQUENCY_TITLE_PRICE_PER_MILE)
                    ->setCost(abs($cost))
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PRICE_PER_MILE)
                    ->setCategory(VehicleCost::CAT_NET_POSITION)
                    ->setIsHidden(true)
                    ->setIsIncludedInTotal(false)
                    ->setIsIncludedInCatTotal(false);
                $vehicleCosts[] = $vehicleCost;

                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Estimated Miles')
                    ->setStringValue(number_format($mileage * $ownershipPeriod))
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PRICE_PER_MILE)
                    ->setCategory(VehicleCost::CAT_NET_POSITION);
                $vehicleCosts[] = $vehicleCost;
            }

            $cost = (($totalCost * -1) + $gfmv) / ($ownershipPeriod * 12);
            $vehicleCost = new VehicleCost();
            $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PRICE_PER_MONTH)
                ->setCost(abs($cost))
                ->setCategory(VehicleCost::CAT_NET_POSITION)
                ->setIsIncludedInTotal(false)
                ->setIsIncludedInCatTotal(false);
            $vehicleCosts[] = $vehicleCost;

            $cost = (($totalCost * -1) + $gfmv) / ($ownershipPeriod * 52);
            $vehicleCost = new VehicleCost();
            $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PRICE_PER_WEEK)
                ->setCost(abs($cost))
                ->setCategory(VehicleCost::CAT_NET_POSITION)
                ->setIsIncludedInTotal(false)
                ->setIsIncludedInCatTotal(false);
            $vehicleCosts[] = $vehicleCost;

            $cost = (($totalCost * -1) + $gfmv) / ($ownershipPeriod * 365);
            $vehicleCost = new VehicleCost();
            $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PRICE_PER_DAY)
                ->setCost(abs($cost))
                ->setCategory(VehicleCost::CAT_NET_POSITION)
                ->setIsIncludedInTotal(false)
                ->setIsIncludedInCatTotal(false);
            $vehicleCosts[] = $vehicleCost;
        }

        return $vehicleCosts;
    }
}
