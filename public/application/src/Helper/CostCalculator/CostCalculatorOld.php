<?php

declare(strict_types=1);

namespace Application\Helper\CostCalculator;

use Application\Helper\VehicleFormula;
use Application\Service\ApiCacheService;
use Application\Service\SessionAnswerService;
use Application\Model\VehicleCost;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Session\SessionValidator;
use Core;

/**
 * OLD VERSION OF THE CostCalculator, being phased out
 */
class CostCalculatorOld
{
    const OWNERSHIP = 'BUYING';
    const LEASING = 'LEASING';
    const SUBSCRIPTION = 'SUBSCRIPTION';
    const RENTING = 'RENTING';
    const SHARING = 'SHARING';
    const RIDE_POOLING = 'RIDE-POOLING';
    const RIDE_HAILING = 'RIDE-HAILING';
    const MAAS = 'MAAS';
    const OWNERSHIP_OUTRIGHT = 'OUTRIGHT PURCHASE';
    const OWNERSHIP_HP = 'HP';
    const OWNERSHIP_PERSONAL_LOAD = 'PERSONAL LOAN';
    const OWNERSHIP_PCP = 'PCP';
    const LEASING_PCH = 'PCH';
    const SUBSCRIPTION_SHORT = 'SHORT TERM';
    const SUBSCRIPTION_LONG = 'LONG TERM';
    const RENTING_SHORT = 'SHORT TERM';
    const RENTING_LONG = 'LONG TERM';
    const SHARING_PERSONAL_CAR = 'PERSONAL CAR';
    const SHARING_MEMBERSHIP_CAR = 'MEMBERSHIP CAR';
    const SHARING_MICROMOBILITY = 'MICROMOBILITY';
    const RIDE_POOLING_PAYG = 'PAYG';
    const RIDE_HAILING_PAYG = 'PAYG';
    const RIDE_HAILING_SUBSCRIPTION = 'SUBSCRIPTION';
    const MAAS_SUBSCRIPTION = 'SUBSCRIPTION';
    const INTEREST_RATE = 7;

    /**
     * @var Connection
     */
    private $con;

    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var VehicleFormula
     */
    private $vehicleFormula;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @var string
     */
    private $sessionKey = '';

    /**
     * @var bool
     */
    private $isSnapshotAnswers = false;

    /**
     * @param Connection $con
     * @param SessionValidator $sessionValidator
     * @param VehicleFormula $vehicleFormula
     * @param SessionAnswerService $sessionAnswerService
     * @param ApiCacheService $apiCacheService
     */
    public function __construct(
        Connection $con,
        SessionValidator $sessionValidator,
        VehicleFormula $vehicleFormula,
        SessionAnswerService $sessionAnswerService,
        ApiCacheService $apiCacheService
    )
    {
        $this->con = $con;
        $this->sessionValidator = $sessionValidator;
        $this->vehicleFormula = $vehicleFormula;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->apiCacheService = $apiCacheService;

        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
        
        if ($session) {
            $this->sessionKey = $session->getId();
        }
    }

    /**
     * @param bool $isSnapshotAnswers
     */
    public function setIsSnapshotAnswers(bool $isSnapshotAnswers)
    {
        $this->isSnapshotAnswers = $isSnapshotAnswers;
    }

    /**
     * @param array $vehicle
     * @param array $answers
     * 
     * @return array
     */
    public function calculateCosts(array $vehicle, array $answers): array
    {
        /**
         * We need to check for the following
         * 
         * Vehicle Type: Car, Bike etc.
         * Category: Buying, Leasing etc.
         * Sub-Category: Outright Purchase, Personal Load, PCP etc.
         */
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
        $amountOfCredit = 0;
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

        if ($vehicle['mobilityChoice']['name'] === self::OWNERSHIP) {
            if ($vehicle['mobilityChoice']['type'] === self::OWNERSHIP_OUTRIGHT) {
                $cost = (float) $vehicle['Price'];
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Buying')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;
            }

            if ($vehicle['mobilityChoice']['type'] === self::OWNERSHIP_HP) {
                $price = (float) $vehicle['Price'];
                if ($this->isSnapshotAnswers) {
                    $tempAnswers = $answers;
                } else {
                    $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                        [
                            'howLongTerm',
                            'howMuchDoYouHave',
                            'whatVehicleDoYouHave',
                            'yourVehicleValuation'
                        ],
                        $answers
                    );
                }

                if (isset($tempAnswers['howLongTerm'])) {
                    $maxDeposit = (25 / 100) * $price;
                    $deposit = 0;

                    if (isset($tempAnswers['howMuchDoYouHave'])) {
                        if ($this->isSnapshotAnswers) {
                            $deposit += (float) $tempAnswers['howMuchDoYouHave'];
                        } else {
                            $deposit += (float) $tempAnswers['howMuchDoYouHave'][0]->getValue();
                        }
                    }

                    if (isset($tempAnswers['whatVehicleDoYouHave']) && isset($tempAnswers['yourVehicleValuation'])) {
                        if ($this->isSnapshotAnswers) {
                            $vehicleValuation = $tempAnswers['yourVehicleValuation'][0];
                            $deposit += (float) $tempAnswers['yourVehicleValuation'][1];
                        } else {
                            $vehicleValuation = $tempAnswers['yourVehicleValuation'][0]->getOption()->getOptionTitle();

                            // Get car data from the cache
                            $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($this->sessionKey, 'cap-hpi', 'vrms');
                            $carData = $apiCache->getData();
            
                            $privateValuation = (isset($carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'])) ?
                                (float) $carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'] : 0;

                            switch ($vehicleValuation) {
                                case 'Private Sale':
                                    $deposit += $privateValuation;
                                    break;
                                case 'Part Exchange';
                                    $partExValuation = (float) $privateValuation - $privateValuation / 20;
                                    $deposit += $partExValuation;
                                    break;
                                case 'Car Buying Service':
                                    $cbsValuation = (float) $privateValuation - $privateValuation / 10;
                                    $deposit += $cbsValuation;
                                    break;
                            }
                        }
                    }

                    if ($deposit > $maxDeposit) {
                        $deposit = $maxDeposit;
                    }
                    
                    $price -= $deposit;

                    if (isset($tempAnswers['howLongTerm'])) {
                        if ($this->isSnapshotAnswers) {
                            $ownershipPeriod = (int) $tempAnswers['howLongTerm'];
                        } else {
                            $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
                        }
                        $vehicleFee = $this->calcPmt((self::INTEREST_RATE / 100) / 12, $ownershipPeriod * 12, $price);
                        $vehicleCost = new VehicleCost();
                        $vehicleCost->setName('Vehicle Fee')
                            ->setCost(round($vehicleFee))
                            ->setApr(self::INTEREST_RATE)
                            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                            ->setCategory('Vehicle & Provider');
                        $vehicleCosts[] = $vehicleCost;
                        $amountOfCredit += $vehicleFee / 12;

                        $repayment = $price / ($ownershipPeriod * 12);
                        $vehicleCost = new VehicleCost();
                        $vehicleCost->setName('Repayment')
                            ->setCost(round($repayment))
                            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                            ->setCategory('Vehicle & Provider')
                            ->setIsIncludedInTotal(false)
                            ->setIsIncludedInCatTotal(false)
                            ->setIsIncludedInFreqTotal(false);
                        $vehicleCosts[] = $vehicleCost;

                        $cost = $vehicleFee - $repayment;
                        $vehicleCost = new VehicleCost();
                        $vehicleCost->setName('Interest')
                            ->setCost(round($cost))
                            ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                            ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                            ->setCategory('Vehicle & Provider')
                            ->setIsIncludedInTotal(false)
                            ->setIsIncludedInCatTotal(false)
                            ->setIsIncludedInFreqTotal(false);
                        $vehicleCosts[] = $vehicleCost;
                    }

                    unset(
                        $price,
                        $tempAnswers,
                        $maxDeposit,
                        $deposit,
                        $vehicleValuation,
                        $apiCache,
                        $carData,
                        $privateValuation,
                        $partExValuation,
                        $cbsValuation,
                        $ownershipPeriod,
                        $vehicleFee,
                        $repayment
                    );
                }
            }

            if ($vehicle['mobilityChoice']['type'] === self::OWNERSHIP_PCP) {
                if ($this->isSnapshotAnswers) {
                    $tempAnswers = $answers;
                } else {
                    $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                        [
                            'howLongTerm',
                            'howMuchDoYouHave',
                            'whatVehicleDoYouHave',
                            'yourVehicleValuation'
                        ],
                        $answers
                    );
                }

                if (isset($tempAnswers['howLongTerm'])) {
                    if ($this->isSnapshotAnswers) {
                        $ownershipPeriod = (int) $tempAnswers['howLongTerm'];
                    } else {
                        $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
                    }
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

                    $cost = $gfmv;
                    $vehicleCost = new VehicleCost();
                    $vehicleCost->setName('Optional Final Payment')
                        ->setCost(round($cost))
                        ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                        ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_ESSENTIAL_MONTHLY)
                        ->setCategory('Vehicle & Provider')
                        ->setIsIncludedInTotal(false)
                        ->setIsIncludedInCatTotal(false)
                        ->setIsIncludedInFreqTotal(false);
                    $vehicleCosts[] = $vehicleCost;
                }
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
        }

        if ($vehicle['mobilityChoice']['name'] === self::LEASING) {
            if ($vehicle['mobilityChoice']['type'] === self::LEASING_PCH) {
                if ($this->isSnapshotAnswers) {
                    $tempAnswers = $answers;
                } else {
                    $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                        [
                            'howLongTerm',
                            'howMuchDoYouHave',
                            'whatVehicleDoYouHave',
                            'yourVehicleValuation'
                        ],
                        $answers
                    );
                }

                if (isset($tempAnswers['howLongTerm'])) {
                    if ($this->isSnapshotAnswers) {
                        $ownershipPeriod = (int) $tempAnswers['howLongTerm'];
                    } else {
                        $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
                    }
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
                    $maxDeposit = (25 / 100) * $price;
                    $deposit = 0;

                    if (isset($tempAnswers['howMuchDoYouHave'])) {
                        if ($this->isSnapshotAnswers) {
                            $deposit += (float) $tempAnswers['howMuchDoYouHave'];
                        } else {
                            $deposit += (float) $tempAnswers['howMuchDoYouHave'][0]->getValue();
                        }
                    }

                    if (isset($tempAnswers['whatVehicleDoYouHave']) && isset($tempAnswers['yourVehicleValuation'])) {
                        if ($this->isSnapshotAnswers) {
                            $vehicleValuation = $tempAnswers['yourVehicleValuation'][0];
                            $deposit += (float) $tempAnswers['yourVehicleValuation'][1];
                        } else {
                            $vehicleValuation = $tempAnswers['yourVehicleValuation'][0]->getOption()->getOptionTitle();

                            // Get car data from the cache
                            $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($this->sessionKey, 'cap-hpi', 'vrms');
                            $carData = $apiCache->getData();
            
                            $privateValuation = (isset($carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'])) ?
                                (float) $carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'] : 0;

                            switch ($vehicleValuation) {
                                case 'Private Sale':
                                    $deposit += $privateValuation;
                                    break;
                                case 'Part Exchange';
                                    $partExValuation = (float) $privateValuation - $privateValuation / 20;
                                    $deposit += $partExValuation;
                                    break;
                                case 'Car Buying Service':
                                    $cbsValuation = (float) $privateValuation - $privateValuation / 10;
                                    $deposit += $cbsValuation;
                                    break;
                            }
                        }
                    }

                    if ($deposit > $maxDeposit) {
                        $deposit = $maxDeposit;
                    }
                    
                    $price -= $deposit;
                    $loanAmount = $price - $gfmv;
                    if ($this->isSnapshotAnswers) {
                        $ownershipPeriod = (int) $tempAnswers['howLongTerm'];
                    } else {
                        $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
                    }
                    $cost = $this->calcPmt((self::INTEREST_RATE / 100) / 12, $ownershipPeriod * 12, $loanAmount);
                    $vehicleCost = new VehicleCost();
                    $vehicleCost->setName('Vehicle Fee')
                        ->setCost(round($cost))
                        ->setApr(self::INTEREST_RATE)
                        ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                        ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                        ->setCategory('Vehicle & Provider');
                    $vehicleCosts[] = $vehicleCost;
                    $amountOfCredit += $cost / 12;

                    $repayment = $price / ($ownershipPeriod * 12);
                    $vehicleCost = new VehicleCost();
                    $vehicleCost->setName('Repayment')
                        ->setCost(round($repayment))
                        ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                        ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                        ->setCategory('Vehicle & Provider')
                        ->setIsIncludedInTotal(false)
                        ->setIsIncludedInCatTotal(false)
                        ->setIsIncludedInFreqTotal(false);
                    $vehicleCosts[] = $vehicleCost;

                    $cost = $vehicleFee - $repayment;
                    $vehicleCost = new VehicleCost();
                    $vehicleCost->setName('Interest')
                        ->setCost(round($cost))
                        ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                        ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                        ->setCategory('Vehicle & Provider')
                        ->setIsIncludedInTotal(false)
                        ->setIsIncludedInCatTotal(false)
                        ->setIsIncludedInFreqTotal(false);
                    $vehicleCosts[] = $vehicleCost;

                    unset(
                        $price,
                        $tempAnswers,
                        $maxDeposit,
                        $deposit,
                        $vehicleValuation,
                        $apiCache,
                        $carData,
                        $privateValuation,
                        $partExValuation,
                        $cbsValuation,
                        $ownershipPeriod,
                        $depreciationRate,
                        $initialRate,
                        $tailRate,
                        $today,
                        $registrationDate,
                        $age,
                        $gfmv,
                        $loanAmount,
                        $vehicleFee,
                        $repayment
                    );
                }
            }
        }

        if ($vehicle['mobilityChoice']['name'] === self::SUBSCRIPTION) {
            if ($vehicle['mobilityChoice']['type'] === self::SUBSCRIPTION_LONG) {
                $cost = (float) $runningCosts['subscription_fee'];
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Vehicle Fee')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;

                $cost = 150.00;
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Membership Fee')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;
            }
        }

        if ($vehicle['mobilityChoice']['name'] === self::RENTING) {
            if ($vehicle['mobilityChoice']['type'] === self::RENTING_SHORT) {
                $cost = $runningCosts['car_rental_fee'];
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Vehicle Fee')
                    ->setCost(round($cost * 20))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;
            }
        }

        if ($vehicle['mobilityChoice']['name'] === self::SHARING) {
            if ($vehicle['mobilityChoice']['type'] === self::SHARING_PERSONAL_CAR) {
                $cost = (float) $runningCosts['personal_sharing_fee'];
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Vehicle Fee')
                    ->setCost(round($cost * 20))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;

                $cost = 220.00;
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Insurance Fee')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;
            }

            if ($vehicle['mobilityChoice']['type'] === self::SHARING_MEMBERSHIP_CAR) {
                $cost = 30.00;
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Membership Fee')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_UPFRONT)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;

                $cost = (float) $runningCosts['membership_sharing_fee'];
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Vehicle Fee')
                    ->setCost(round($cost * 20))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;

                $cost = 220.00;
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Insurance Fee')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;
            }

            if ($vehicle['mobilityChoice']['type'] === self::SHARING_MICROMOBILITY) {
                $cost = 136.00;
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Vehicle Fee')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;
            }
        }

        if ($vehicle['mobilityChoice']['name'] === self::RIDE_HAILING) {
            if ($vehicle['mobilityChoice']['type'] === self::RIDE_HAILING_PAYG) {
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
                $singleFairCost = $minimumFare + $sustainaCost + $mileageCost + $timeCost;

                $cost = ($singleFairCost * 3) * (365 / 12);
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Vehicle Fee')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;
            }

            if ($vehicle['mobilityChoice']['type'] === self::RIDE_HAILING_SUBSCRIPTION) {
                $cost = (float) 401;
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Vehicle Fee')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;
            }
        }

        if ($vehicle['mobilityChoice']['name'] === self::RIDE_POOLING) {
            if ($vehicle['mobilityChoice']['type'] === self::RIDE_POOLING_PAYG) {
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
            }
        }

        if ($vehicle['mobilityChoice']['name'] === self::MAAS) {
            if ($vehicle['mobilityChoice']['type'] === self::MAAS_SUBSCRIPTION) {
                $cost = 426.00;
                $vehicleCost = new VehicleCost();
                $vehicleCost->setName('Vehicle Fee')
                    ->setCost(round($cost))
                    ->setFrequency(VehicleCost::FREQUENCY_MONTHLY)
                    ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_MONTHLY)
                    ->setCategory('Vehicle & Provider');
                $vehicleCosts[] = $vehicleCost;
            }
        }

        if (
            $vehicle['mobilityChoice']['name'] === self::OWNERSHIP
            || $vehicle['mobilityChoice']['name'] === self::LEASING
        ) {
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
        }

        if (
            $vehicle['mobilityChoice']['name'] === self::OWNERSHIP
            || $vehicle['mobilityChoice']['name'] === self::LEASING
            || $vehicle['mobilityChoice']['name'] === self::SUBSCRIPTION
            || $vehicle['mobilityChoice']['name'] === self::RENTING
            || $vehicle['mobilityChoice']['name'] === self::SHARING
        ) {
            $cost = $this->vehicleFormula->calcAnnualFuelCost(
                $vehicle['VehicleType'],
                $vehicle['FuelType'],
                (float) $vehicle['CombinedMPG'],
                (float) $vehicle['BatKWH'],
                $vehicle['BatMileage'],
                0,
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

        if (
            (
                $amountOfCredit > 0
                && $ownershipPeriodMonths > 0
                && $vehicle['mobilityChoice']['name'] === CostCalculator::OWNERSHIP
                && $vehicle['mobilityChoice']['type'] === CostCalculator::OWNERSHIP_HP
            )
            || (
                $amountOfCredit > 0
                && $ownershipPeriodMonths > 0
                && $vehicle['mobilityChoice']['name'] === CostCalculator::LEASING
                && $vehicle['mobilityChoice']['type'] === CostCalculator::LEASING_PCH
            )
        ) {
            // TODO: This is soon to be removed
            // $cost = $amountOfCredit * $ownershipPeriodMonths;
            // $vehicleCost = new VehicleCost();
            // $vehicleCost->setName('Amount Of Credit')
            //     ->setCost(round($cost))
            //     ->setFrequency(VehicleCost::FREQUENCY_ONE_OFF)
            //     ->setFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PAID_OVER_TERM)
            //     ->setCategory('Vehicle & Provider')
            //     ->setIsIncludedInTotal(false)
            //     ->setIsIncludedInCatTotal(false)
            //     ->setIsIncludedInFreqTotal(false);
            // $vehicleCosts[] = $vehicleCost;
        }
        // END OF MUST BE LAST

        return $vehicleCosts;
    }

    /**
     * @param array $vehicleCosts
     * @param array $answers
     */
    public function getTotalCost(array $vehicleCosts, array $answers): float
    {
        $ownershipPeriod = null;
        if ($this->isSnapshotAnswers) {
            if (isset($answers['howLongTerm'])) {
                $ownershipPeriod = (int) $answers['howLongTerm'];
            }
        } else {
            $howLongTerm = $this->sessionAnswerService->getAnswerByQuestionHandle('howLongTerm', $answers);
            if ($howLongTerm) {
                $ownershipPeriod = (int) $howLongTerm->getValue();
            }
        }

        $total = 0;
        foreach ($vehicleCosts as $vehicleCost) {
            if ($vehicleCost->getIsIncludedInTotal()) {
                switch ($vehicleCost->getFrequency()) {
                    case VehicleCost::FREQUENCY_ANNUALLY:
                        if ($ownershipPeriod) {
                            $total += $vehicleCost->getCost() * $ownershipPeriod;
                        }
                        break;
                    case VehicleCost::FREQUENCY_MONTHLY:
                        if ($ownershipPeriod) {
                            $total += $vehicleCost->getCost() * (($ownershipPeriod * 12) - (int) $vehicleCost->getIncurAfterMonths());
                        }
                        break;
                    case VehicleCost::FREQUENCY_ONE_OFF:
                    default:
                        $total += $vehicleCost->getCost();
                        break;
                }
            }
        }
        return $total;
    }

    /**
     * @param array $vehicleCosts
     * @param array $answers
     * 
     * @return float
     */
    public function getTotalUpfrontCost(array $vehicleCosts, array $answers): float
    {
        $total = 0;
        foreach ($vehicleCosts as $vehicleCost) {
            if (
                $vehicleCost->getIsUpfrontCost() === true
                && $vehicleCost->getFrequency() === VehicleCost::FREQUENCY_ONE_OFF
            )
            {
                $total += $vehicleCost->getCost();
            }
        }
        return $total;
    }

    /**
     * @param string $category
     * @param array $vehicleCosts
     * @param array $answers
     * 
     * @return float
     */
    public function getTotalCostByCategory(string $category, array $vehicleCosts, array $answers): float
    {
        $ownershipPeriod = null;
        if ($this->isSnapshotAnswers) {
            if (isset($answers['howLongTerm'])) {
                $ownershipPeriod = (int) $answers['howLongTerm'];
            }
        } else {
            $howLongTerm = $this->sessionAnswerService->getAnswerByQuestionHandle('howLongTerm', $answers);
            if ($howLongTerm) {
                $ownershipPeriod = (int) $howLongTerm->getValue();
            }
        }

        $total = 0;
        foreach ($vehicleCosts as $vehicleCost) {
            if ($vehicleCost->getIsIncludedInCatTotal() && $vehicleCost->getCategory() === $category) {
                switch ($vehicleCost->getFrequency()) {
                    case VehicleCost::FREQUENCY_ANNUALLY:
                        if ($ownershipPeriod) {
                            $total += $vehicleCost->getCost() * $ownershipPeriod;
                        }
                        break;
                    case VehicleCost::FREQUENCY_MONTHLY:
                        if ($ownershipPeriod) {
                            $total += $vehicleCost->getCost() * (($ownershipPeriod * 12) - (int) $vehicleCost->getIncurAfterMonths());
                        }
                        break;
                    case VehicleCost::FREQUENCY_ONE_OFF:
                    default:
                        $total += $vehicleCost->getCost();
                        break;
                }
            }
        }
        return $total;
    }

    /**
     * @param array $vehicleCosts
     */
    public function getMonthlyCost(array $vehicleCosts): float
    {
        $total = 0;
        foreach ($vehicleCosts as $vehicleCost) {
            if (
                $vehicleCost->getIsIncludedInTotal()
                && $vehicleCost->getFrequency() === VehicleCost::FREQUENCY_MONTHLY
            )
            {
                $total += $vehicleCost->getCost();
            }
        }

        return $total;
    }

    /**
     * @param string $category
     * @param array $vehicleCosts
     * 
     * @return float
     */
    public function getMonthlyCostByCategory(string $category, array $vehicleCosts): float
    {
        $total = 0;
        foreach ($vehicleCosts as $vehicleCost) {
            if (
                $vehicleCost->getIsIncludedInCatTotal()
                && $vehicleCost->getCategory() === $category 
                && $vehicleCost->getFrequency() === VehicleCost::FREQUENCY_MONTHLY
            ) {
                $total += $vehicleCost->getCost();
            }
        }

        return $total;
    }

    /**
     * @param string $category
     * @param string $frequency
     * @param array $vehicleCosts
     * 
     * @return float
     */
    public function getFrequencyTitleTotalCost(string $category, string $frequencyTitle, array $vehicleCosts): float
    {
        $total = 0;
        foreach ($vehicleCosts as $vehicleCost) {
            if (
                $vehicleCost->getIsIncludedInFreqTotal()
                && $vehicleCost->getCategory() === $category 
                && $vehicleCost->getFrequencyTitle() === $frequencyTitle
            ) {
                $total += $vehicleCost->getCost();
            }
        }

        return $total;
    }

    /**
     * @param array $vehicleCosts
     * 
     * @return array
     */
    public function groupByCategoryAndFrequencyTitle(array $vehicleCosts): array
    {
        $groupedVehicleCosts = [];

        foreach ($vehicleCosts as $vehicleCost) {
            $cat = $vehicleCost->getCategory();
            $frequencyTitle = $vehicleCost->getFrequencyTitle();
            $groupedVehicleCosts[$cat][$frequencyTitle][] = $vehicleCost;
        }

        return $groupedVehicleCosts;
    }

    /**
     * @param float $interest
     * @param int $numOfPayments
     * @param float $pv
     * @param float $fv
     * @param int $type
     * 
     * @return float
     */
    public function calcPmt(float $interest, int $numOfPayments, float $pv, float $fv = 0.00, int $type = 0): float
    {
        $xp = pow((1 + $interest), $numOfPayments);
        return ($pv * $interest * $xp / ($xp-1) + $interest / ($xp - 1) *$fv) * ($type === 0 ? 1 : 1 / ($interest + 1));
    }
}
