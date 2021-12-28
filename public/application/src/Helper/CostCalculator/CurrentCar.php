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
 * Selling Vehicle
 */
class CurrentCar extends CostCalculator
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
        $runningCosts = $this->con->fetchAssoc('SELECT * FROM insurance_price WHERE insurance_group = ?', [$vehicle['InsuranceGroup']]);

        $insuranceCost = null;
        $mot = null;
        $parkingHome = null;
        $breakdown = null;
        $warranty = null;
        $service = null;
        $parts = null;
        $tyres = null;
        $glass = null;
        $carWashing = null;
        $parkingTrip = null;
        $insuranceGap = null;
        $insuranceScratchDent = null;
        $insuranceOther = null;
        $incomeSharingFee = null;
        $ownershipPeriod = null;
        $ownershipPeriodMonths = null;
        $mileage = null;
        $howMuchDoYouHave = null;
        $showIncome = true;
        $gfmv = 0;

        if ($runningCosts) {
            $insuranceCost = (float) $runningCosts['insurance_price'];
            $mot = (float) $runningCosts['mot'];
            $parkingHome = (float) $runningCosts['parking_home'];
            $breakdown = (float) $runningCosts['breakdown'];
            $warranty = (float) $runningCosts['warranty'];
            $service = (float) $runningCosts['service'];
            $parts = (float) $runningCosts['parts'];
            $tyres = (float) $runningCosts['tyres'];
            $glass = (float) $runningCosts['glass'];
            $carWashing = (float) $runningCosts['car_washing'];
            $parkingTrip = (float) $runningCosts['parking_trip'];
            $insuranceGap = (float) $runningCosts['insurance_gap'];
            $insuranceScratchDent = (float) $runningCosts['insurance_scratch_dent'];
            $insuranceOther = (float) $runningCosts['insurance_other'];
            $incomeSharingFee = (float) $runningCosts['income_sharing_fee'];
        }

        if (array_key_exists('howMuchDoYouHave', $answers)) {
            $howMuchDoYouHave = (int) $answers['howMuchDoYouHave'];
        }

        if (array_key_exists('howLongTerm', $answers)) {
            $ownershipPeriod = (int) $answers['howLongTerm'];
            $ownershipPeriodInMonths = $ownershipPeriod * 12;
        }

        if (array_key_exists('whatIsYourEstimatedMileage', $answers)) {
            $mileage = (int) $answers['whatIsYourEstimatedMileage'];
        }

        if (array_key_exists('wouldYouConsiderSharingYourCarOrDriveway', $answers)) {
            $wouldYouConsiderSharing = $answers['wouldYouConsiderSharingYourCarOrDriveway'];
            $showIncome = (strtolower($wouldYouConsiderSharing) === 'yes') ? true : false;
        }

        if ($ownershipPeriod) {
            // Calculate GFMV
            if (array_key_exists('InsuranceGroup', $vehicle)) {
                $price = (float) $vehicle['Price'];
                $depreciationRate = $this->con->fetchAssoc('SELECT * FROM `depreciation_rate` WHERE `condition` = ? AND `karfu_group` IN (
                    SELECT karfu_group FROM insurance_price WHERE insurance_group = ?
                )', ['Used', $vehicle['InsuranceGroup']]);
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
        $otrPrice = 0;

        $cost = $this->vehicleFormula->calcCarTaxInitial($vehicle['FuelType'], (int) $vehicle['CO2GKM']);
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Road Tax')
            ->setCost($cost / 12)
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
            ->setIncurAfterMonths(12)
            ->setCategory(VehicleCost::CAT_NET_RUNNING);
        $vehicleCosts[] = $vehicleCost;

        if ($insuranceCost) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Insurance')
                ->setCost($insuranceCost / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
                ->setIncurAfterMonths(12)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($mot) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('MOT')
                ->setCost($mot / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
                ->setIncurAfterMonths(12)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($breakdown) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Breakdown')
                ->setCost($breakdown / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($warranty) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Warranty')
                ->setCost($warranty / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($service) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Service')
                ->setCost($service / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($ownershipPeriod) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Parts')
                ->setCost(($parts * $ownershipPeriod) / (12 * $ownershipPeriod))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($ownershipPeriod) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Tyres')
                ->setCost(($tyres * $ownershipPeriod) / (12 * $ownershipPeriod))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($glass) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Glass Repair')
                ->setCost($glass / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($carWashing) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Car Washing')
                ->setCost($carWashing / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($parkingTrip) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Parking (Out)')
                ->setCost($parkingTrip / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }
    
        if ($insuranceGap) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Insurance (GAP)')
                ->setCost($insuranceGap / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }
    
        if ($insuranceScratchDent) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Insurance (Scratch & Dent)')
                ->setCost($insuranceScratchDent / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($insuranceOther) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Insurance (Other)')
                ->setCost($insuranceOther / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
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
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($parkingHome) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Parking (Home)')
                ->setCost($parkingHome / 12)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;
        }

        if ($showIncome) {
            if ($incomeSharingFee) {
                $cost = $incomeSharingFee;
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Car Sharing')
                    ->setCost(-$cost)
                    ->setIsIncome(true)
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESTIMATED_MONTHLY)
                    ->setCategory(VehicleCost::CAT_NET_INCOME);
                $vehicleCosts[] = $vehicleCost;
            }

            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Space Rental (Parking)')
                ->setCost(-50)
                ->setIsIncome(true)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESTIMATED_MONTHLY)
                ->setCategory(VehicleCost::CAT_NET_INCOME);
            $vehicleCosts[] = $vehicleCost;
        }

        $totalCost = $this->getTotalCost($vehicleCosts, $answers);
        $remainingEquity = $howMuchDoYouHave + ($totalCost * -1);

        $cost = $gfmv + $remainingEquity;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_NET_POSITION_END_TERM)
            ->setCost($cost)
            ->setCategory(VehicleCost::CAT_NET_POSITION)
            ->setIsIncludedInTotal(false);
        $vehicleCosts[] = $vehicleCost;

        $cost = ($gfmv + $remainingEquity) - $howMuchDoYouHave;
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
