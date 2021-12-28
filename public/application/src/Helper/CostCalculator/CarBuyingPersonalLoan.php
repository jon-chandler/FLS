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
 * Buying
 * Personal Loan
 */
class CarBuyingPersonalLoan extends CostCalculator
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
        $runningCosts = $this->con->fetchAssoc('SELECT * FROM insurance_price WHERE insurance_group = ?', [$vehicle['InsuranceGroup']]);

        $insuranceCost = null;
        $delivery = null;
        $registration = null;
        $mot = null;
        $parkingHome = null;
        $breakdown = null;
        $warranty = null;
        $service = null;
        $parts = null;
        $labour = null;
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

        if ($runningCosts) {
            $insuranceCost = (float) $runningCosts['insurance_price'];
            $delivery = (float) $runningCosts['delivery'];
            $registration = (float) $runningCosts['registration'];
            $mot = (float) $runningCosts['mot'];
            $parkingHome = (float) $runningCosts['parking_home'];
            $breakdown = (float) $runningCosts['breakdown'];
            $warranty = (float) $runningCosts['warranty'];
            $service = (float) $runningCosts['service'];
            $parts = (float) $runningCosts['parts'];
            $labour = (float) $runningCosts['labour'];
            $tyres = (float) $runningCosts['tyres'];
            $glass = (float) $runningCosts['glass'];
            $carWashing = (float) $runningCosts['car_washing'];
            $parkingTrip = (float) $runningCosts['parking_trip'];
            $insuranceGap = (float) $runningCosts['insurance_gap'];
            $insuranceScratchDent = (float) $runningCosts['insurance_scratch_dent'];
            $insuranceOther = (float) $runningCosts['insurance_other'];
            $incomeSharingFee = (float) $runningCosts['income_sharing_fee'];
        }

        $cost = $this->vehicleFormula->calcCarTaxInitial($vehicle['FuelType'], (int) $vehicle['CO2GKM']);
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Road Tax')
            ->setCost(round($cost))
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
            ->setCategory('Running');
        $vehicleCosts[] = $vehicleCost;

        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Road Tax')
            ->setCost(round($cost / 12))
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
            ->setIncurAfterMonths(12)
            ->setCategory('Running');
        $vehicleCosts[] = $vehicleCost;

        // $cost = $this->vehicleFormula->clacAnnualInsuranceCost($vehicle['InsuranceGroup']);
        if ($insuranceCost) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Insurance')
                ->setCost(round($insuranceCost))
                ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;

            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Insurance')
                ->setCost(round($insuranceCost / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
                ->setIncurAfterMonths(12)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        if ($mot) {
            // TODO: Only add if car is 3 years or older tahn registration date
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('MOT')
                ->setCost(round($mot))
                ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;

            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('MOT')
                ->setCost(round($mot / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
                ->setIncurAfterMonths(12)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        if ($delivery) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Delivery')
                ->setCost(round($delivery))
                ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
                ->setCategory('Vehicle & Provider');
            $vehicleCosts[] = $vehicleCost;
        }

        if ($registration) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Registration')
                ->setCost(round($registration))
                ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
                ->setCategory('Vehicle & Provider');
            $vehicleCosts[] = $vehicleCost;
        }

        // $cost = $this->vehicleFormula->calcFullFuelTank($vehicle['FuelType'], (float) $vehicle['FuelCapacityL'], (float) $vehicle['BatKWH']);
        // $vehicleCost = new VehicleCost();
        // $vehicleCost->setName('Full Fuel Tank')
        // 	->setCost($cost)
        // 	->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
        //  ->setCategory('Running');
        // $vehicleCosts[] = $vehicleCost;

        // TODO: Get values from DB
        // $bodyStyleRating = 'MED';
        // $rating = 'MED';
        if ($this->isSnapshotAnswers) {
            if (isset($answers['howLongTerm'])) {
                $ownershipPeriod = (int) $answers['howLongTerm'];
                $ownershipPeriodMonths = $ownershipPeriod * 12;
            }
        } else {
            $howLongTerm = $this->sessionAnswerService->getAnswerByQuestionHandle('howLongTerm', $answers);
            if ($howLongTerm) {
                $ownershipPeriod = (int) $howLongTerm->getValue();
                $ownershipPeriodMonths = $ownershipPeriod * 12;
            }
        }

        // $cost = $this->vehicleFormula->calcAnnualBreakdownCost(
        // 	$vehicle['BodyStyle'],
        // 	$bodyStyleRating,
        // 	$rating
        // );
        if ($breakdown) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Breakdown')
                ->setCost(round($breakdown / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }
    
        // $cost = $this->vehicleFormula->calcAnnualWarrantyCost(
        // 	$vehicle['BodyStyle'],
        // 	$bodyStyleRating,
        // 	$rating
        // );
        if ($warranty) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Warranty')
                ->setCost(round($warranty / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        // $cost = $this->vehicleFormula->calcAnnualOemServiceCost(
        // 	$vehicle['BodyStyle'],
        // 	$bodyStyleRating,
        // 	$rating
        // );
        if ($service) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Service')
                ->setCost(round($service / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        if ($ownershipPeriod) {
            // $cost = $this->vehicleFormula->calcAnnualPartsCost(
            // 	$vehicle['BodyStyle'],
            // 	$bodyStyleRating,
            // 	$rating
            // );
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Parts')
                ->setCost(round(($parts * $ownershipPeriod) / (12 * $ownershipPeriod)))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        // TODO: Get labour (when needed)

        if ($ownershipPeriod) {
            // $cost = $this->vehicleFormula->calcAnnualTyreCost(
            // 	$vehicle['BodyStyle'],
            // 	$bodyStyleRating,
            // 	$rating
            // );
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Tyres')
                ->setCost(round(($tyres * $ownershipPeriod) / (12 * $ownershipPeriod)))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        // $cost = $this->vehicleFormula->calcAnnualGlassCost(
        // 	$vehicle['BodyStyle'],
        // 	$bodyStyleRating,
        // 	$rating
        // );
        if ($glass) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Glass Repair')
                ->setCost(round($glass / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        if ($carWashing) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Car Washing')
                ->setCost(round($carWashing / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        if ($parkingTrip) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Parking (Out)')
                ->setCost(round($parkingTrip / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }
    
        if ($insuranceGap) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Insurance (GAP)')
                ->setCost(round($insuranceGap / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }
    
        if ($insuranceScratchDent) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Insurance (Scratch & Dent)')
                ->setCost(round($insuranceScratchDent / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        if ($insuranceOther) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Insurance (Other)')
                ->setCost(round($insuranceOther / 12))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_RECOMMENDED_MONTHLY)
                ->setCategory('Running');
            $vehicleCosts[] = $vehicleCost;
        }

        $cost = (float) $vehicle['Price'];
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('On The Road Price')
            ->setCost(round($cost))
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PAID_OVER_TERM)
            ->setCategory('Vehicle & Provider')
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false)
            ->setIsIncludedInFreqTotal(false);
        $vehicleCosts[] = $vehicleCost;

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

        if ($incomeSharingFee) {
            $cost = $incomeSharingFee;
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Car Sharing')
                ->setCost(round(-$cost))
                ->setIsIncome(true)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESTIMATED_MONTHLY)
                ->setCategory('Income');
            $vehicleCosts[] = $vehicleCost;
        }

        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Space Rental (Parking)')
            ->setCost(-50)
            ->setIsIncome(true)
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESTIMATED_MONTHLY)
            ->setCategory('Income');
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
