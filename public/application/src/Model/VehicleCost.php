<?php

declare(strict_types = 1);

namespace Application\Model;

/**
 * Model for an individual vehicle cost
 * Includes all the consts for category, frequency & frequency titles
 */
class VehicleCost
{
    CONST CAT_NET_VEHICLE_PROVIDER = 'Vehicle & Provider';
    CONST CAT_NET_RUNNING = 'Running';
    CONST CAT_NET_INCOME = 'Income';
    CONST CAT_NET_POSITION = 'NET Position';
    const FREQUENCY_MONTHLY = 'Monthly';
    const FREQUENCY_ANNUALLY = 'Annually';
    const FREQUENCY_ONE_OFF = 'One Off';
    const FREQUENCY_TITLE_VEHICLE_PRICE = 'Vehicle Price';
    const FREQUENCY_TITLE_UPFRONT = 'Upfront Cost To You';
    const FREQUENCY_TITLE_UPFRONT_COST = 'Upfront Cost';
    const FREQUENCY_TITLE_PAID_OVER_TERM = 'Paid Over Term';
    const FREQUENCY_TITLE_MONTHLY = 'Monthly';
    const FREQUENCY_TITLE_MONTHLY_COST = 'Monthly Cost';
    const FREQUENCY_TITLE_DEPOSIT = 'Your Deposit';
    const FREQUENCY_TITLE_OTHER = 'Other Fees';
    const FREQUENCY_TITLE_ESSENTIAL_MONTHLY = 'Essential Monthly';
    const FREQUENCY_TITLE_ESTIMATED_MONTHLY = 'Estimated Monthly';
    const FREQUENCY_TITLE_RECOMMENDED_MONTHLY = 'Recommended Monthly';
    const FREQUENCY_TITLE_EXCESS_MILEAGE = 'Excess Mileage Charge';
    const FREQUENCY_TITLE_INITIAL_RENTAL = 'Your Initial Rental';
    const FREQUENCY_TITLE_STARTING_POSITION = 'Your Starting Position';
    const FREQUENCY_TITLE_TOTAL_COST_OF_USE = 'Total Cost of Use';
    const FREQUENCY_TITLE_VEHICLE_EQUITY_END_TERM = 'Vehicle Equity at End of Term';
    const FREQUENCY_TITLE_REMAINING_STARTING_POSITION = 'Remaining From Starting Position';
    const FREQUENCY_TITLE_NET_POSITION_END_TERM = 'Net Position at End of Term';
    const FREQUENCY_TITLE_PRICE_PER_MILE = 'Karfu Cost Per Mile';
    const FREQUENCY_TITLE_PRICE_PER_MONTH = 'Karfu Cost Per Month';
    const FREQUENCY_TITLE_PRICE_PER_WEEK = 'Karfu Cost Per Week';
    const FREQUENCY_TITLE_PRICE_PER_DAY = 'Karfu Cost Per Day';
    const FREQUENCY_TITLE_NET_SPEND = 'Net Spend';

    /**
     * @var string
     */
    private $name = '';

    /** 
     * @var float|null
     */
    private $cost;

    /**
     * @var string
     */
    private $stringValue = '';

    /**
     * @var bool
     */
    private $isIncome = false;

    /**
     * @var float
     */
    private $apr = 0;

    /**
     * @var float
     */
    private $fixedRate = 0;

    /**
     * @var string
     */
    private $frequency = '';

    /**
     * @var string
     */
    private $frequencyTitle = '';

    /**
     * @var int
     */
    private $incurAfterMonths = 0;

    /**
     * @var string
     */
    private $category = '';

    /**
     * @var bool
     */
    private $isIncludedInTotal = true;

    /**
     * @var bool
     */
    private $isIncludedInCatTotal = true;

    /**
     * @var bool
     */
    private $isIncludedInFreqTotal = true;

    /**
     * @var bool
     */
    private $isHidden = false;

    /**
     * @var bool
     */
    private $isUpfrontCost = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * 
     * @return VehicleCost
     */
    public function setName(string $name): VehicleCost
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * @param float $cost
     * 
     * @return VehicleCost
     */
    public function setCost(float $cost): VehicleCost
    {
        $this->cost = $cost;
        return $this;
    }

    /**
     * @return string
     */
    public function getStringValue(): string
    {
        return $this->stringValue;
    }

    /**
     * @param string $stringValue
     * 
     * @return VehicleCost
     */
    public function setStringValue(string $stringValue): VehicleCost
    {
        $this->stringValue = $stringValue;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsIncome(): bool
    {
        return $this->isIncome;
    }

    /**
     * @param float $isIncome
     * 
     * @return VehicleCost
     */
    public function setIsIncome(bool $isIncome): VehicleCost
    {
        $this->isIncome = $isIncome;
        return $this;
    }

    /**
     * @return float
     */
    public function getApr(): float
    {
        return $this->apr;
    }

    /**
     * @param float $apr
     * 
     * @return VehicleCost
     */
    public function setApr(float $apr): VehicleCost
    {
        $this->apr = $apr;
        return $this;
    }

    /**
     * @return float
     */
    public function getFixedRate(): float
    {
        return $this->fixedRate;
    }

    /**
     * @var float $fixedRate
     * 
     * @return VehicleCost
     */
    public function setFixedRate(float $fixedRate): VehicleCost
    {
        $this->fixedRate = $fixedRate;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrequency(): string
    {
        return $this->frequency;
    }

    /**
     * @param string $frequency
     * 
     * @return VehicleCost
     */
    public function setFrequency(string $frequency): VehicleCost
    {
        $this->frequency = $frequency;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrequencyTitle(): string
    {
        return $this->frequencyTitle;
    }

    /**
     * @param string $frequency
     * 
     * @return VehicleCost
     */
    public function setFrequencyTitle(string $frequencyTitle): VehicleCost
    {
        $this->frequencyTitle = $frequencyTitle;
        return $this;
    }

    /**
     * @return int
     */
    public function getIncurAfterMonths(): int
    {
        return $this->incurAfterMonths;
    }

    /**
     * @param int $incurAfterMonths
     * 
     * @return VehicleCost
     */
    public function setIncurAfterMonths(int $incurAfterMonths): VehicleCost
    {
        $this->incurAfterMonths = $incurAfterMonths;
        return $this;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     * 
     * @return VehicleCost
     */
    public function setCategory(string $category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsIncludedInTotal(): bool
    {
        return $this->isIncludedInTotal;
    }

    /**
     * @param string $isIncludedInTotal
     * 
     * @return VehicleCost
     */
    public function setIsIncludedInTotal(bool $isIncludedInTotal)
    {
        $this->isIncludedInTotal = $isIncludedInTotal;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsIncludedInCatTotal(): bool
    {
        return $this->isIncludedInCatTotal;
    }

    /**
     * @param string $isIncludedInCatTotal
     * 
     * @return VehicleCost
     */
    public function setIsIncludedInCatTotal(bool $isIncludedInCatTotal)
    {
        $this->isIncludedInCatTotal = $isIncludedInCatTotal;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsIncludedInFreqTotal(): bool
    {
        return $this->isIncludedInFreqTotal;
    }

    /**
     * @param string $isIncludedInFreqTotal
     * 
     * @return VehicleCost
     */
    public function setIsIncludedInFreqTotal(bool $isIncludedInFreqTotal)
    {
        $this->isIncludedInFreqTotal = $isIncludedInFreqTotal;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsHidden(): bool
    {
        return $this->isHidden;
    }

    /**
     * @param bool $isHidden
     * 
     * @return VehicleCost
     */
    public function setIsHidden(bool $isHidden): VehicleCost
    {
        $this->isHidden = $isHidden;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsUpfrontCost(): bool
    {
        return $this->isUpfrontCost;
    }

    /**
     * @param bool $isUpfrontCost
     * 
     * @return VehicleCost
     */
    public function setIsUpfrontCost(bool $isUpfrontCost): VehicleCost
    {
        $this->isUpfrontCost = $isUpfrontCost;
        return $this;
    }
}
