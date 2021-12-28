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
 * Bicycle
 * Subscription
 * Long Term
 */
class BicycleSubscriptionLongTerm extends CostCalculator
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
        $ownershipPeriod = null;
        $ownershipPeriodMonths = null;
        $mileage = null;
        $howMuchDoYouHave = null;
        $yourVehicleValuation = null;
        $gfmv = 0;

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
        } else {
            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                [
                    'howLongTerm',
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
        }

        if ($ownershipPeriod) {
            // Calculate GFMV
            if (array_key_exists('InsuranceGroup', $vehicle)) {
                $price = (float) $vehicle['Price'];
                $depreciationRate = $this->con->fetchAssoc('SELECT * FROM `depreciation_rate` WHERE `condition` = ? AND `karfu_group` IN (
                    SELECT karfu_group FROM insurance_price WHERE insurance_group = ?
                )', ['New', 26]);
                $initialRate = (float) $depreciationRate['initial'];
                $tailRate = (float) $depreciationRate['tail'];

                // Calc depreciation value
                for ($i = 0; $i < $ownershipPeriod; $i++) {
                    // On first loop, use initial
                    if ($i === 0) {
                        $lost = ($initialRate / 100) * $price;
                        $retained = $price - $lost;
                    } else {
                        $lost = ($tailRate / 100) * $retained;
                        $retained = $retained - $lost;
                    }
                }

                $gfmv = $retained;
            }
        }

        // Calculate on the road price
        $otrPrice = (float) $vehicle['Price'];

        $cost = 0.04 * $otrPrice;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Bicycle Fee')
            ->setCost(round($cost))
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
        $vehicleCosts[] = $vehicleCost;

        $cost = 10.00;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Membership Fee')
            ->setCost(round($cost))
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
        $vehicleCosts[] = $vehicleCost;

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
                ->setCost(round($cost / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        $vehicleCost = new VehicleCost();
        $vehicleCost->setCategory(VehicleCost::CAT_NET_INCOME)
            ->setIsHidden(true);
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

        $totalCost = $this->getTotalCost($vehicleCosts, $answers);
        $cost = $totalCost * -1;
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

        $cost = $otrPrice;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Purchase Price')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_VEHICLE_EQUITY_END_TERM)
            ->setCategory(VehicleCost::CAT_NET_POSITION)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false)
            ->setIsIncludedInFreqTotal(false);
        $vehicleCosts[] = $vehicleCost;

        $cost = $otrPrice - $gfmv;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Depreciation')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_VEHICLE_EQUITY_END_TERM)
            ->setCategory(VehicleCost::CAT_NET_POSITION)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false)
            ->setIsIncludedInFreqTotal(false);
        $vehicleCosts[] = $vehicleCost;

        $cost = $gfmv;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Estimated Value')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_VEHICLE_EQUITY_END_TERM)
            ->setCategory(VehicleCost::CAT_NET_POSITION)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false)
            ->setIsIncludedInFreqTotal(false);
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
