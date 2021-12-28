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
 * Leasing
 * Personal Contract Hire
 */
class CarLeasingPch extends CostCalculator
{
    const MIN_LOAN_AMOUNT = 3000;

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
        $amountOfCredit = 0;
        $ownershipPeriod = null;
        $ownershipPeriodInMonths = null;
        $mileage = null;
        $howMuchDoYouHave = null;
        $yourVehicleValuation = null;
        $deposit = 0;
        $priceAfterDeposit = null;
        $cashLumpSum = null;
        $vehicleTradeInValue = null;
        $gfmv = 0;
        $annualMileageLimit = 10000;
        $excessPerMileCharge = 0.05;
        $estExcessMileage = 0;

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
        }

        if ($this->isSnapshotAnswers) {
            if (array_key_exists('howMuchDoYouHave', $answers)) {
                $howMuchDoYouHave = (float) $answers['howMuchDoYouHave'];
            }

            if (array_key_exists('yourVehicleValuation', $answers)) {
                $yourVehicleValuation = (float) $answers['yourVehicleValuation'][1];
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

            $estExcessMileage = ($mileage * $ownershipPeriod) - ($annualMileageLimit * $ownershipPeriod);
            $estExcessMileage = ($estExcessMileage > 0) ? $estExcessMileage : 0;
        }

        // Calculate on the road price
        $otrPrice = (float) $vehicle['Price'];

        // Calculate max deposit amount
        $deposit += $howMuchDoYouHave + $yourVehicleValuation;
        $maxDeposit = $this->calcDeposit($otrPrice, $deposit, $gfmv);
        $deposit = $maxDeposit;

        $priceAfterDeposit = $otrPrice - $bar;
        $loanAmount = $priceAfterDeposit - $gfmv;

        // Calculate cash lump sum & vehicle trade in amount to contribute towards deposit
        if ($howMuchDoYouHave) {
            if ($deposit <= $howMuchDoYouHave) {
                $cashLumpSum = $deposit;
                $leftover = $howMuchDoYouHave - $deposit;

                if ($yourVehicleValuation) {
                    $vehicleTradeInValue = 0;
                    $leftover += $yourVehicleValuation;
                }
            } else {
                $cashLumpSum = $howMuchDoYouHave;
                $leftover = 0;

                if ($yourVehicleValuation) {
                    $vehicleTradeInValue = $deposit - $howMuchDoYouHave;
                    $leftover += $yourVehicleValuation - $vehicleTradeInValue;
                }
            }
        } else if ($yourVehicleValuation) {
            if ($deposit < $yourVehicleValuation) {
                $vehicleTradeInValue = $deposit;
            } else {
                $vehicleTradeInValue = $yourVehicleValuation;
            }
            $leftover = $yourVehicleValuation - $vehicleTradeInValue;
        }

        $cost = $otrPrice;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('On The Road Price (Including VAT)')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_VEHICLE_PRICE)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;

        $interest = abs($this->CUMIPMT((self::INTEREST_RATE / 100) / 12, $ownershipPeriod * 12, $priceAfterDeposit, 1, $ownershipPeriod * 12));
        $cost = ($loanAmount + $interest) / $ownershipPeriodInMonths;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Initial Rental')
            ->setCost($cost)
            ->setIsUpfrontCost(true)
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT_COST)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;

        if ($ownershipPeriod) {
            $fees = 0;
            $ownershipPeriodInDays = $ownershipPeriod * 365;
            $interest = abs($this->CUMIPMT((self::INTEREST_RATE / 100) / 12, $ownershipPeriod * 12, $priceAfterDeposit, 1, $ownershipPeriod * 12));

            $cost = (($loanAmount + $interest) / ($ownershipPeriodInMonths)) * ($ownershipPeriodInMonths - 1);
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Total of Monthly Payments')
                ->setCost($cost)
                ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PAID_OVER_TERM)
                ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER)
                ->setIsIncludedInTotal(false)
                ->setIsIncludedInCatTotal(false);
            $vehicleCosts[] = $vehicleCost;

            $cost = $excessPerMileCharge * $estExcessMileage;
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Excess Mileage Charge')
                ->setCost($cost)
                ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PAID_OVER_TERM)
                ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER)
                ->setIsIncludedInTotal(false)
                ->setIsIncludedInCatTotal(false);
            $vehicleCosts[] = $vehicleCost;

            $cost = ($loanAmount + $interest) / $ownershipPeriodInMonths;
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Monthly Cost')
                ->setCost($cost)
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY_COST)
                ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
            $vehicleCosts[] = $vehicleCost;

            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('No. of Payments')
                ->setStringValue((string) ($ownershipPeriodInMonths - 1))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY_COST)
                ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
            $vehicleCosts[] = $vehicleCost;
        }

        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Annual Mileage')
            ->setStringValue(number_format($annualMileageLimit))
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_EXCESS_MILEAGE)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
        $vehicleCosts[] = $vehicleCost;

        if ($ownershipPeriod) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Total Mileage (Max Over Term)')
                ->setStringValue(number_format($annualMileageLimit * $ownershipPeriod))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_EXCESS_MILEAGE)
                ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
            $vehicleCosts[] = $vehicleCost;
        }

        $cost = $excessPerMileCharge;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Excess Mileage (Per Mile)')
            ->setCost($excessPerMileCharge)
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_EXCESS_MILEAGE)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;

        if ($mileage && $ownershipPeriod) {
            $vehicleCost = new VehicleCost();
            $vehicleCost->setName('Estimate Mileage')
                ->setStringValue(number_format($mileage * $ownershipPeriod))
                ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_EXCESS_MILEAGE)
                ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
            $vehicleCosts[] = $vehicleCost;
        }

        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Estimated Excess Mileage')
            ->setStringValue(number_format($estExcessMileage))
            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_EXCESS_MILEAGE)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
        $vehicleCosts[] = $vehicleCost;

        $cost = $excessPerMileCharge * $estExcessMileage;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Excess Mileage Charge')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_EXCESS_MILEAGE)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER);
        $vehicleCosts[] = $vehicleCost;

        $cost = 0;
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Arrangement Fees')
            ->setCost($cost)
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_OTHER)
            ->setCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER)
            ->setIsIncludedInTotal(false)
            ->setIsIncludedInCatTotal(false);
        $vehicleCosts[] = $vehicleCost;

        $cost = $this->vehicleFormula->calcCarTaxInitial($vehicle['FuelType'], (int) $vehicle['CO2GKM']);
        $vehicleCost = new VehicleCost();
        $vehicleCost->setName('Road Tax')
            ->setCost($cost)
            ->setIsUpfrontCost(true)
            ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
            ->setCategory(VehicleCost::CAT_NET_RUNNING);
        $vehicleCosts[] = $vehicleCost;

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
                ->setCost($insuranceCost)
                ->setIsUpfrontCost(true)
                ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;

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
                ->setCost($mot)
                ->setIsUpfrontCost(true)
                ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
                ->setCategory(VehicleCost::CAT_NET_RUNNING);
            $vehicleCosts[] = $vehicleCost;

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

    /**
     * @param float $price
     * @param float $deposit
     * @param float $gfmv
     * @param int $i
     * 
     * @return float
     */
    private function calcDeposit(float $price, float $deposit, float $gfmv, int $i = 0): float
    {
        // Cap the nesting level
        if ($i === 10) {
            return 0;
        }

        if ($i > 0) {
            // Half the deposit
            $deposit = (50 / 100) * $deposit;
        }

        $loanAmount = ($price - $deposit) - $gfmv;

        if ($loanAmount >= self::MIN_LOAN_AMOUNT) {
            return $deposit;
        } else {
            $i++;
            return $this->calcDeposit($price, $deposit, $gfmv, $i);
        }
    }
}
