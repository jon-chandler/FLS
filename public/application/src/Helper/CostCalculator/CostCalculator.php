<?php

declare(strict_types=1);

namespace Application\Helper\CostCalculator;

use Application\Helper\VehicleFormula;
use Application\Service\SessionAnswerService;
use Application\Model\VehicleCost;
use Concrete\Core\Database\Connection\Connection;

/**
 * CostCalculator calculates the costs for a vehicle, mobility type & mobility sub type combo.
 * Also contains various getter method for fetching cost.
 */
abstract class CostCalculator
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
    protected $con;

    /**
     * @var SessionAnswerService
     */
    protected $sessionAnswerService;

    /**
     * @var VehicleFormula
     */
    protected $vehicleFormula;

    /**
     * @var bool Are answers snapshotted (saved)
     */
    protected $isSnapshotAnswers = false;

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
        $this->con = $con;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->vehicleFormula = $vehicleFormula;
    }

    /**
     * Calculate all costs for and individual vehicle type & mobility type & mobility sub type combo
     * 
     * @param array $vehicle
     * @param array $answers
     * @param array $cacheValues
     * 
     * @return array
     */
    abstract protected function calculateCosts(array $vehicle, array $answers, array $cacheValues = []);

    /**
     * @param bool $isSnapshotAnswers
     */
    public function setIsSnapshotAnswers(bool $isSnapshotAnswers)
    {
        $this->isSnapshotAnswers = $isSnapshotAnswers;
    }

    /**
     * Get sum of all total upfront costs
     * 
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
     * Get total cost of individual vehicle type & mobility type & mobility sub type combo over ownership period
     * 
     * @param array $vehicleCosts
     * @param array $answers
     * 
     * @return float
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
     * Get total cost of a specific category over ownership period
     * 
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
     * Get all monthly costs
     * 
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
     * Get monthly cost by category
     * 
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
     * Get monthly income
     * 
     * @param array $vehicleCosts
     * 
     * @return float
     */
    public function getMonthlyIncome(array $vehicleCosts)
    {
        $total = 0;
        foreach ($vehicleCosts as $vehicleCost) {
            if (
                $vehicleCost->getIsIncome() === true
                && $vehicleCost->getIsIncludedInCatTotal()
                && $vehicleCost->getFrequency() === VehicleCost::FREQUENCY_MONTHLY
            ) {
                $total += $vehicleCost->getCost();
            }
        }

        return $total;
    }

    /**
     * Get total cost by frequency title
     * 
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
     * Group vehicle costs by category & frequency title
     * 
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
     * Get vehicle costs by name
     * 
     * @param string $name
     * @param array $vehicleCosts
     * 
     * @return VehicleCost|null
     */
    public function getVehicleCostByName(string $name, array $vehicleCosts)
    {
        foreach ($vehicleCosts as $vehicleCost) {
            if ($vehicleCost->getName() === $name) {
                return $vehicleCost;
            }
        }

        return null;
    }

    /**
     * Get vehicle costs by frequency title
     * 
     * @param string $frequencyTitle
     * @param array $vehicleCosts
     * 
     * @return array
     */
    public function getVehicleCostsByFrequencyTitle(string $frequencyTitle, array $vehicleCosts): array
    {
        return array_values(array_filter($vehicleCosts, function ($vehicleCost) use ($frequencyTitle) {
            return ($vehicleCost->getFrequencyTitle() === $frequencyTitle) ? true : false;
        }));
    }

    /**
     * Get estimated mileage for journey question answers
     * 
     * @param array $answers
     * 
     * @return int
     */
    public function getEstimatedMileage(array $answers): int
    {
        $mileage = 0;
        $howOftenValues = [];
        $shortJourneys = null;
        $mediumJourneys = null;
        $longJourneys = null;

        if ($answers) {
            if ($this->isSnapshotAnswers) {
                if (array_key_exists('howOften', $answers)) {
                    $howOften = $answers['howOften'];
                    foreach ($howOften as $howOftenV) {
                        $howOftenValues[] = $howOftenV;
                    }
                }

                if (array_key_exists('journeyKind', $answers)) {
                    $journeyKind = $answers['journeyKind'];
                    $shortJourneys = (isset($journeyKind[0][0])) ? (int) $journeyKind[0][0] : 0;
                    $mediumJourneys = (isset($journeyKind[1][0])) ? (int) $journeyKind[1][0] : 0;
                    $longJourneys = (isset($journeyKind[2][0])) ? (int) $journeyKind[2][0] : 0;
                }
            } else {
                $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                    [
                        'howOften',
                        'journeyKind'
                    ],
                    $answers
                );
    
                if (array_key_exists('howOften', $tempAnswers)) {
                    $howOften = $tempAnswers['howOften'];
                    foreach ($howOften as $howOftenV) {
                        $howOftenValues[] = $howOftenV->getOption()->getOptionTitle();
                    }
                }

                if (array_key_exists('journeyKind', $tempAnswers)) {
                    $journeyKind = $tempAnswers['journeyKind'];
                    $shortJourneys = (isset($journeyKind[0])) ? (int) $journeyKind[0]->getValue() : 0;
                    $mediumJourneys = (isset($journeyKind[1])) ? (int) $journeyKind[1]->getValue() : 0;
                    $longJourneys = (isset($journeyKind[2])) ? (int) $journeyKind[2]->getValue() : 0;
                }
            }

            if (
                count($howOftenValues) > 0
                && $shortJourneys
                && $mediumJourneys
                && $longJourneys
            )
            {
                $mileage = $this->vehicleFormula->calcEstimatedAnnualMileage($howOftenValues, $shortJourneys, $mediumJourneys, $longJourneys);
            }
        }
        return $mileage;
    }

    /**
     * Excel function
     * 
     * @param float $rate
     * @param int $nper
     * @param float $pv
     * @param int $start
     * @param int $end
     * @param int $type
     *
     * @return float|string
     */
    public function CUMIPMT(float $rate, int $nper, float $pv, int $start, int $end, int $type = 0)
    {
        // Calculate
        $interest = 0;
        for ($per = $start; $per <= $end; ++$per) {
            $interest += self::IPMT($rate, $per, $nper, $pv, 0, $type);
        }

        return $interest;
    }

    /**
     * Excel function
     * 
     * @param float $rate
     * @param int $nper
     * @param float $pv
     * @param float $fv
     * @param int $type
     *
     * @return float|string
     */
    public function PMT(float $rate = 0, int $nper = 0, float $pv = 0, float $fv = 0, int $type = 0)
    {
        // Calculate
        if ($rate !== null && $rate != 0) {
            return (-$fv - $pv * (1 + $rate) ** $nper) / (1 + $rate * $type) / (((1 + $rate) ** $nper - 1) / $rate);
        }

        return (-$pv - $fv) / $nper;
    }

    /**
     * Excel function
     * 
     * @param float $nominal_rate
     * @param int $npery
     *
     * @return float|string
     */
    public static function EFFECT(float $nominal_rate = 0, int $npery = 0)
    {
        return (1 + $nominal_rate / $npery) ** $npery - 1;
    }

    /**
     * Excel function
     * 
     * @param float $rate
     * @param int $per
     * @param int $nper
     * @param float $pv
     * @param float $fv
     * @param int $type
     *
     * @return float|string
     */
    public function IPMT(float $rate, int $per, int $nper, float $pv, float $fv = 0, int $type = 0)
    {
        // Calculate
        $interestAndPrincipal = $this->interestAndPrincipal($rate, $per, $nper, $pv, $fv, $type);

        return $interestAndPrincipal[0];
    }

    /**
     * Calculate the interest & principal
     * 
     * @param float $rate
     * @param int $per
     * @param int $nper
     * @param float $pv
     * @param float $fv
     * @param int $type
     * 
     * @return array
     */
    private function interestAndPrincipal(
        float $rate = 0,
        int $per = 0,
        int $nper = 0,
        float $pv = 0,
        float $fv = 0,
        int $type = 0
    ): array
    {
        $pmt = $this->PMT($rate, $nper, $pv, $fv, $type);
        $capital = $pv;
        for ($i = 1; $i <= $per; ++$i) {
            $interest = ($type && $i == 1) ? 0 : -$capital * $rate;
            $principal = $pmt - $interest;
            $capital += $principal;
        }

        return [$interest, $principal];
    }
}