<?php

declare(strict_types=1);

namespace Application\Helper;

use Concrete\Core\Database\Connection\Connection;
use DateTime;

/**
 * VehicleFormula contains a variety of methods for performing vehicle calculations
 */
class VehicleFormula
{
    const M_IN_KM = 0.62137119223733;
    const KM_IN_M = 1.60934;
    const G_IN_LB = 0.00220462;
    const G_IN_KG = 1000;
    const KG_IN_T = 0.001;
    const MG_IN_G = 1000;
    const LB_IN_TREES = 5.976;
    const CARBON_OFFSET_PRICE_PER_T = 7.5;

    /**
	 * @var Connection
	 */
    private $con;

    /**
	 * @param Connection $con
	 */
	public function __construct(Connection $con)
	{
		$this->con = $con;
    }

    /**
     * @var DateTime $todaysDate
     * @var DateTime $dateIntroduced
     * @return int
     */
    public function calcAge(DateTime $todaysDate, DateTime $dateIntroduced): int
    {
        return (int) $todaysDate->diff($dateIntroduced)->format('%y');
    }

    /**
     * @var DateTime $todaysDate
     * @var DateTime $dateIntroduced
     * @return int
     */
    public function calcMileage(DateTime $todaysDate, DateTime $dateIntroduced)
    {
        return (int) $todaysDate->diff($dateIntroduced)->format('%y') * 8000;
    }

    /**
     * @var array $prices
     * @return float
     */
    public function calcMinPrice(array $prices): float
    {
        $minPrice = null;

        if (count($prices) > 0) {
            foreach ($prices as $price) {
                if ($minPrice === null) {
                    $minPrice = $price;
                } else {
                    if ($price < $minPrice) {
                        $minPrice = $price;
                    }
                }
            }
        } else {
            $minPrice = 0;
        }

        return (float) $minPrice;
    }

    /**
     * @var array $prices
     * @return float
     */
    public function calcMaxPrice(array $prices): float
    {
        $maxPrice = 0;

        foreach ($prices as $price) {
            if ($price > $maxPrice) {
                $maxPrice = $price;
            }
        }

        return (float) $maxPrice;
    }

    /**
     * @var array $prices
     * @return float
     */
    public function calcAveragePrice(array $prices)
    {
        return (float) array_sum($prices) / count($prices);
    }

    /**
     * @var float $batteryWattHoursPerMile
     * @return float
     */
    public function calcConsumptionElectric(float $batteryWattHoursPerMile): float
    {
        return (float) ($batteryWattHoursPerMile * 0.001) * 100;
    }

    /**
     * @var float $badgeCc
     * @return float
     */
    public function calcEngineSize($badgeCc): float
    {
        return (float) $badgeCc / 1000;
    }

    /**
     * @var int $numOfSeats
     * @var int $numOfOptionalSeats
     * @return int
     */
    public function calcMaxSeats(int $numOfSeats, int $numOfOptionalSeats): int
    {
        return (int) $numOfSeats + $numOfOptionalSeats;
    }

    /**
     * @var $fuelType
     * @var $carbonEmissions
     * @return float
     */
    public function calcCarTaxInitial(string $fuelType, int $carbonEmissions): float
    {
        $result = $this->con->fetchAssoc('SELECT initial_cost
        FROM vehicle_tax_lookup
        WHERE fuel_type = ? AND ? BETWEEN co2_emissions_from AND co2_emissions_to
        LIMIT 1',
        [
            $fuelType,
            $carbonEmissions
        ]);

        if ($result) {
            return (float) $result['initial_cost'];
        }

        return 0;
    }

    /**
     * @var $fuelType
     * @var $carbonEmissions
     * @return float
     */
    public function calcCarTaxOngoing(string $fuelType, int $carbonEmissions): float
    {
        $result = $this->con->fetchAssoc('SELECT subsequent_cost
        FROM vehicle_tax_lookup
        WHERE fuel_type = ? AND ? BETWEEN co2_emissions_from AND co2_emissions_to
        LIMIT 1',
        [
            $fuelType,
            $carbonEmissions
        ]);

        if ($result) {
            return (float) $result['subsequent_cost'];
        }

        return 0;
    }

    /**
     * @var float $price
     * @var $fuelType
     * @return float
     */
    public function calcCarTaxLuxury(float $price, $fuelType): float
    {
        // TODO:
        return 0.00;
    }

    /**
     * @var int $manufacturerWarranty
     * @var int $dealerWarranty
     * @return int
     */
    public function calcTotalWarrantyLength(int $manufacturerWarranty, int $dealerWarranty): int
    {
        return (int) $manufacturerWarranty + $dealerWarranty;
    }

    /**
     * @var float $height
     * @var float $width
     * @var float $length
     * @return float
     */
    public function calcSizeVolume(float $height, float $width, float $length): float
    {
        return (float) $height * $width * $length;
    }

    /**
     * @var float $bhp
     * @var float $weight
     * @return float
     */
    public function calcPowerToWeight(float $bhp, float $weight): float
    {
        return (float) $bhp / $weight;
    }

    /**
     * @var float $sizeVolume
     * @return string
     */
    public function calcSizeKarfu(float $sizeVolume): string
    {
        if ($sizeVolume >= 16150000000) {
            return 'Very Big';
        } elseif ($sizeVolume >= 12960000000) {
            return 'Big';
        } elseif ($sizeVolume >= 10965000000) {
            return 'Middle';
        } elseif ($sizeVolume >= 9184000000) {
            return 'Small';
        } else {
            return 'Very Small';
        }
    }

    /**
     * @var $zeroTo62
     * @return int
     */
    public function calc0To62Score($zeroTo62): int
    {
        // TODO: get zero to 62 example
        if ($zeroTo62 < 3) {
            return 5;
        } elseif ($zeroTo62 < 6) {
            return 4;
        } elseif ($zeroTo62 < 9) {
            return 3;
        } elseif ($zeroTo62 < 12) {
            return 2;
        } else {
            return 1;
        }
    }

    /**
     * @var int $topSpeed
     * @return int
     */
    public function calcTopSpeedScore(int $topSpeed): int
    {
        if ($topSpeed > 200) {
            return 5;
        } elseif ($topSpeed > 175) {
            return 4;
        } elseif ($topSpeed > 150) {
            return 3;
        } elseif ($topSpeed > 125) {
            return 2;
        } else {
            return 1;
        }
    }

    /**
     * @var float $powerToWeight
     * @return int
     */
    public function calcPowerToWeightScore(float $powerToWeight): int
    {
        $ptwFinal = $powerToWeight * 1000;

        if ($ptwFinal > 500) {
            return 5;
        } elseif ($ptwFinal > 400) {
            return 4;
        } elseif ($ptwFinal > 300) {
            return 3;
        } elseif ($ptwFinal > 200) {
            return 2;
        } else {
            return 1;
        }
    }

    /**
     * @var int $powerToWeightScore
     * @var int $topSpeedScore
     * @var int $zeroTo62Score
     * @return int
     */
    public function calcTotalPerformanceScore(int $powerToWeightScore, int $topSpeedScore, int $zeroTo62Score): int
    {
        return (int) $powerToWeightScore + $topSpeedScore + $zeroTo62Score;
    }

    /**
     * @var int $performanceScore
     * @return string
     */
    public function calcPerformanceKarfu(int $performanceScore): string
    {
        if ($performanceScore > 11) {
            return 'Very High';
        } elseif ($performanceScore > 8) {
            return 'High';
        } elseif ($performanceScore > 5) {
            return 'Middle';
        } elseif ($performanceScore > 2) {
            return 'Low';
        } else {
            return 'Very Low';
        }
    }

    public function calcRange(): int
    {
        // TODO:
    }

    /**
     * @var int $range
     * @return string
     */
    public function calcRangeKarfu(int $range): string
    {
        if ($range > 600) {
            return 'Very High';
        } elseif ($range > 450) {
            return 'High';
        } elseif ($range > 350) {
            return 'Med';
        } elseif ($range > 250) {
            return 'Low';
        } else {
            return 'Very Low';
        }
    }

    /**
     * @var int $co2Gkm
     * @return string
     */
    public function calcLowCo2Karfu(int $co2Gkm): string
    {
        if ($co2Gkm < 50) {
            return 'Very Low';
        } elseif ($co2Gkm < 100) {
            return 'Low';
        } elseif ($co2Gkm < 150) {
            return 'Med';
        } elseif ($co2Gkm < 190) {
            return 'High';
        } else {
            return 'Very High';
        }
    }

    /**
     * @var int $co2Gkm
     * @return string
     */
    public function calcLowCarTaxKarfu(int $co2Gkm): string
    {
        if ($co2Gkm < 50) {
            return 'Very Low';
        } elseif ($co2Gkm < 100) {
            return 'Low';
        } elseif ($co2Gkm < 150) {
            return 'Med';
        } elseif ($co2Gkm < 190) {
            return 'High';
        } else {
            return 'Very High';
        }
    }

    /**
     * @var int $combinedMpg
     * @return string
     */
    public function calcHighMpgKarfu(int $combinedMpg): string
    {
        if ($combinedMpg > 70) {
            return 'Very High';
        } elseif ($combinedMpg > 55) {
            return 'High';
        } elseif ($combinedMpg > 45) {
            return 'Med';
        } elseif ($combinedMpg > 35) {
            return 'Low';
        } else {
            return 'Very Low';
        }
    }

    /**
     * @return string
     */
    public function calcLowDepreciationKarfu(): string
    {
        // TODO:
    }

    /**
     * @var int $insuranceGroup
     * @return string
     */
    public function calcLowInsuranceKarfu(int $insuranceGroup): string
    {
        if ($insuranceGroup > 40) {
            return 'HIGH';
        } elseif ($insuranceGroup >= 30) {
            return 'MED';
        } else {
            return 'LOW';
        }
    }

    /**
     * @var string $transmission
     * @return bool
     */
    public function calcManualGearbox(string $transmission): bool
    {
        return ($transmission === 'MANUAL') ? true : false;
    }

    /**
     * @var int $totalPerformanceScore
     * @return bool
     */
    public function calcHighPerformance(int $totalPerformanceScore): bool
    {
        return ($totalPerformanceScore > 9) ? true : false;
    }

    /**
     * @var string $depreciationRate
     * 
     * @return bool
     */
    public function calcLowDepreciation(string $depreciationRate): bool
    {
        return ($depreciationRate === 'LOW') ? true : false;
    }

    /**
     * @var int $co2Gkm
     * @return bool
     */
    public function calcLowCo2(int $co2Gkm): bool
    {
        return ($co2Gkm < 100) ? true : false;
    }

    /**
     * @var int $ncapOverall
     * @return bool
     */
    public function calcSafety(int $ncapOverall): bool
    {
        return ($ncapOverall === 5) ? true : false;
    }

    /**
     * @var int $combinedMpg
     * @return bool
     */
    public function calcHighMpg(int $combinedMpg): bool
    {
        return ($combinedMpg > 55) ? true : false;
    }

    /**
     * @var int $co2Gkm
     * @return bool
     */
    public function calcLowTax(int $co2Gkm): bool
    {
        return ($co2Gkm < 100) ? true : false;
    }

    /**
     * @var int $towingWeightBraked
     * @return bool
     */
    public function calcTowing(int $towingWeightBraked): bool
    {
        return ($towingWeightBraked > 3000) ? true : false;
    }

    /**
     * @var int $insuranceGroup
     * @return bool
     */
    public function calcLowInsurance(int $insuranceGroup): bool
    {
        return ($insuranceGroup < 20) ? true : false;
    }

    /**
     * @var string $manufacturerName
     * @return string
     */
    public function calcDepriciationRate(string $manufacturerName): string
    {
        // TODO:
    }

    /**
     * @return float
     */
    public function calcDepriciationInitial(string $calcDepriciationRate): float
    {
        // TODO:
    }

    /**
     * @return float
     */
    public function calcDepriciationSubsequent(): float
    {
        // TODO:
    }

    /**
     * @return float
     */
    public function calcValueRetained(float $price): float
    {
        // TODO:
    }

    /**
     * @var float $carTaxInitial
     * @var float $carTaxLuxury
     * @return float
     */
    public function calcTaxVedInitial(float $carTaxInitial, float $carTaxLuxury): float
    {
        return (float) $carTaxInitial + $carTaxLuxury;
    }

    /**
     * @return float
     */
    public function clacMonthlyInsuranceCost(): float
    {
        // TODO:
    }

    /**
     * @param string $fuelType
     * @param float $fuelCapacity
     * @param float $batteryKwh
     * 
     * @return float
     */
    public function calcFullFuelTank(string $fuelType, float $fuelCapacity, float $batteryKwh = null): float
    {
        // if ($fuelType === 'Electric') {
        //     return $this->calcCostToCharge();
        // } else {
        //     $result = $this->con->fetchAssoc('SELECT price from fuel_price WHERE fuel_type = ? LIMIT 1', [$fuelType]);

        //     if ($result) {
        //         $fuelPrice = (float) $result['price'];
        //         return $fuelPrice * $fuelCapacity;
        //     }
        // }

        return 0;
    }

    /**
     * @var float $carTaxOngoing
     * @var float $carTaxLuxury
     * @return float
     */
    public function calcTaxVedOngoing(float $carTaxOngoing, float $carTaxLuxury): float
    {
        return (float) $carTaxOngoing + $carTaxLuxury;
    }

    /**
     * @param string $insuranceGroup
     * 
     * @return float
     */
    public function clacAnnualInsuranceCost(string $insuranceGroup): float
    {
        $result = $this->con->fetchAssoc('SELECT price FROM insurance_price WHERE insurance_group = ?', [$insuranceGroup]);

        if ($result) {
            return (float) $result['price'];
        }
    
        return 0;
    }

    /**
     * @param string $fuelType
     * @param float $mpg
     * @param float $batteryKwh
     * @param $range
     * @param float $miKgH2
     * 
     * @return float
     */
    public function calcCostPerMile(string $fuelType, float $mpg, float $batteryKwh = null, $range = null, float $miKgH2 = null): float
    {
        if ($fuelType === 'Electric') {
            $costToCharge = $this->calcCostToCharge($batteryKwh);
            return $this->calcECostPerMile($costToCharge, $range);
        } else {
            $result = $this->con->fetchAssoc('SELECT price from fuel_price WHERE fuel_type = ? LIMIT 1', [$fuelType]);

            if ($result) {
                $fuelPrice = (float) $result['price'];

                if ($fuelType === 'Hydrogen Fuel Cell') {
                    return $fuelPrice * $miKgH2;
                } else {
                    if ($mpg > 0) {
                        return $fuelPrice / ($mpg * 0.219969);
                    }
                }
            }
        }
        return 0;
    }

    /**
     * @var float $pirce
     * @return float
     */
    public function calcCarSharingIncome(float $price): float
    {
        return (float) ($price * 0.03) / 10;
    }

    /**
     * @return float
     */
    public function calcCarSharingIncomeLoss(): float
    {
        // TODO:
    }

    /**
     * @return float
     */
    public function calcMinCarSharingCost(): float
    {
        // TODO:
    }

    /**
     * @return float
     */
    public function calcKwhPerMile(): float
    {
        // TODO:
    }

    /**
     * @param string $vehicleType
     * @param float $batteryKwh
     * @param $batteryCapacity
     * 
     * @return int
     */
    public function calcERange(string $vehicleType, float $batteryKwh, $batteryCapacity): int
    {
        return 200;
    }

    /**
     * @return float
     */
    public function calcEMpg(): float
    {
        // TODO:
    }

    /**
     * @return float
     */
    public function calAmpereHours(): float
    {
        // TODO:
    }

    /**
     * @var float $batteryKwh
     * @return float
     */
    public function calcWattHours(float $batteryKwh): float
    {
        return (float) $batteryKwh / 0.001;
    }

    /**
     * @var float $batteryKwh
     * @return float
     */
    public function calcChargeTime(float $batteryKwh): float
    {
        return (float) $batteryKwh / 7;
    }

    /**
     * @param float $batteryKwh
     * 
     * @return float
     */
    public function calcCostToCharge(float $batteryKwh): float
    {
        return $batteryKwh * 0.1475;
        // $result = $this->con->fetchAssoc('SELECT price from fuel_price WHERE fuel_type = ? LIMIT 1', ['Electric']);

        // if ($result) {
        //     $fuelPrice = (float) $result['fuel_price'];
        //     return $fuelPrice * $batteryKwh;
        // }

        // return 0;
    }

    /**
     * @param float $costToCharge
     * @param int range
     * 
     * @return float
     */
    public function calcECostPerMile(float $costToCharge, int $range): float
    {
        return $costToCharge / $range;
    }

    /**
     * @param float $mpg
     * @param int|null $months
     * 
     * @return float
     */
    public function calcEnvironmentalImpact(float $mpg, int $months = null): float
    {
        $months = $months ?: 48; 
        $monthlyMielage = CONFIG_AVG_DOM_ANNUAL_MILEAGE / 12;
        $mileageTotal = $monthlyMielage * $months;

        return round($mpg / 5.976) * $mileageTotal / 1000;
    }

    /**
     * @param string $vehicleType
     * @param string $fuelType
     * @param float $mpg
     * @param float $batteryKwh
     * @param $batteryCapacity
     * @param int $annualMileage
     * @param float $miKgH2
     * 
     * @return float
     */
    public function calcAnnualFuelCost(string $vehicleType, string $fuelType, float $mpg, float $batteryKwh, $batteryCapacity, int $annualMileage, float $miKgH2 = null): float
    {
        if ($vehicleType === 'B' || $vehicleType === 'K-ES') {
            if ($fuelType === 'Electric') {
                $result = $this->con->fetchAssoc('SELECT price from fuel_price WHERE fuel_type = ? LIMIT 1', [$fuelType]);

                if ($result) {
                    $fuelPrice = (float) $result['price'];
                    return 3 * $fuelPrice * 4 * 12;
                }
            }
            return 0;
        }

        $range = $this->calcERange($vehicleType, $batteryKwh, $batteryCapacity);
        $costPerMile = $this->calcCostPerMile($fuelType, $mpg, $batteryKwh, $range, $miKgH2);
        return $annualMileage * $costPerMile;
    }

    /**
     * @param string $registrationDate
     * 
     * @return float
     */
    // public function calcApproxAnnualMileage(string $registrationDate): float
    // {
    //     $today = new DateTime('NOW');
    //     $manufacturerDate = new DateTime($registrationDate);
    //     $ageInMonths = ($manufacturerDate->diff($today)->y * 12) + $manufacturerDate->diff($today)->m;
    //
    //     return ceil((CONFIG_AVG_DOM_ANNUAL_MILEAGE * $ageInMonths) / 12);
    // }

    /**
     * @param string $carType
     * @param string $bodyStyleRating
     * @param string $rating
     * 
     * @return float
     */
    public function calcAnnualBreakdownCost(string $bodyStyle, string $bodyStyleRating, string $rating): float
    {
        $result = $this->con->fetchAssoc('SELECT price
            FROM
                misc_running_cost
            WHERE
                name = ?
                AND body_style = ?
                AND body_style_rating = ?
                AND rating = ?',
            [
                'BREAKDOWN',
                $bodyStyle,
                $bodyStyleRating,
                $rating
            ]
        );

        if ($result) {
            $price = (float) $result['price'];
            return $price;
        }

        return 0;
    }

    /**
     * @param string $carType
     * @param string $bodyStyleRating
     * @param string $rating
     * 
     * @return float
     */
    public function calcAnnualOemServiceCost(string $bodyStyle, string $bodyStyleRating, string $rating): float
    {
        $result = $this->con->fetchAssoc('SELECT price
            FROM
                misc_running_cost
            WHERE
                name = ?
                AND body_style = ?
                AND body_style_rating = ?
                AND rating = ?',
            [
                'OEM SERVICE',
                $bodyStyle,
                $bodyStyleRating,
                $rating
            ]
        );

        if ($result) {
            $price = (float) $result['price'];
            return $price;
        }

        return 0;
    }

    /**
     * @param string $carType
     * @param string $bodyStyleRating
     * @param string $rating
     * 
     * @return float
     */
    public function calcAnnualPartsCost(string $bodyStyle, string $bodyStyleRating, string $rating): float
    {
        $result = $this->con->fetchAssoc('SELECT price
            FROM
                misc_running_cost
            WHERE
                name = ?
                AND body_style = ?
                AND body_style_rating = ?
                AND rating = ?',
            [
                'PARTS',
                $bodyStyle,
                $bodyStyleRating,
                $rating
            ]
        );

        if ($result) {
            $price = (float) $result['price'];
            return $price;
        }

        return 0;
    }

    /**
     * @param string $carType
     * @param string $bodyStyleRating
     * @param string $rating
     * 
     * @return float
     */
    public function calcAnnualWarrantyCost(string $bodyStyle, string $bodyStyleRating, string $rating): float
    {
        $result = $this->con->fetchAssoc('SELECT price
            FROM
                misc_running_cost
            WHERE
                name = ?
                AND body_style = ?
                AND body_style_rating = ?
                AND rating = ?',
            [
                'TYRE',
                $bodyStyle,
                $bodyStyleRating,
                $rating
            ]
        );

        if ($result) {
            $price = (float) $result['price'];
            return $price;
        }

        return 0;
    }

    /**
     * @param string $carType
     * @param string $bodyStyleRating
     * @param string $rating
     * 
     * @return float
     */
    public function calcAnnualTyreCost(string $bodyStyle, string $bodyStyleRating, string $rating): float
    {
        $result = $this->con->fetchAssoc('SELECT price
            FROM
                misc_running_cost
            WHERE
                name = ?
                AND body_style = ?
                AND body_style_rating = ?
                AND rating = ?',
            [
                'TYRE',
                $bodyStyle,
                $bodyStyleRating,
                $rating
            ]
        );

        if ($result) {
            $price = (float) $result['price'];
            return $price;
        }

        return 0;
    }

    /**
     * @param string $carType
     * @param string $bodyStyleRating
     * @param string $rating
     * 
     * @return float
     */
    public function calcAnnualGlassCost(string $bodyStyle, string $bodyStyleRating, string $rating): float
    {
        $result = $this->con->fetchAssoc('SELECT price
            FROM
                misc_running_cost
            WHERE
                name = ?
                AND body_style = ?
                AND body_style_rating = ?
                AND rating = ?',
            [
                'GLASS',
                $bodyStyle,
                $bodyStyleRating,
                $rating
            ]
        );

        if ($result) {
            $price = (float) $result['price'];
            return $price;
        }

        return 0;
    }


    /**
     * @param array $howOften
     * @param int $shortJourneys
     * @param int $mediumJourneys
     * @param int $longJourneys
     * 
     * @return int
     */
    public function calcEstimatedAnnualMileage(array $howOften, int $shortJourneys, int $mediumJourneys, int $longJourneys): int
    {
        
        // usage. Limit to 7 days a week
        $usage = 0;
        foreach($howOften as $often) {
            if($usage <= 7) {
                $usage += $this->mapHowOften($often);
            }
        }

        // DfT journey defintions
        $short = 2;
        $med = 8.4;
        $long = 35.28;

        // multipliers for journey types
        $multiplierShort = $multiplierLong = $multuplierMedium = 1;
        // 

        $shortJourneyTot = (($this->mapSliderToMiles($shortJourneys) * $short) * $multiplierShort);
        $medJourneyTot = (($this->mapSliderToMiles($mediumJourneys) * $med) * $multuplierMedium);
        $longJourneyTot = (($this->mapSliderToMiles($longJourneys) * $long) * $multiplierLong);
        $total = ($shortJourneyTot + $medJourneyTot + $longJourneyTot) * ($usage * 52);

        return intval(round($total));
    }

    /**
     * @param int $sliderVal
     * 
     * @return int
     */
    public function mapSliderToMiles($val) {
        switch($val) {
            case 1:
            default:
                $mVal = 0;
            break;
            case 2:
                $mVal = 0.55;
            break;
            case 3:
                $mVal = 1.1;
            break;           
            case 4:
                $mVal = 5.5;
            break;
            case 5:
                $mVal = 10;
            break;
        }

        return $mVal;
    }

    /**
     * @param string $howOften
     * 
     * @return float
     */
    public function mapHowOften($val) {
        switch($val) {
            case 'All Day Every Day':
            default:
                $oft = 7;
            break;
            case 'Weekends':
                $oft = 2;
            break;
            case 'Weekday mornings':
                $oft = 0.5;
            break;           
            case 'Weekday evenings':
                $oft = 0.5;
            break;
            case 'Every weekday':
                $oft = 5;
            break;
            case 'Once a week':
                $oft = 1;
            break;
            case 'Once a fortnight':
                $oft = 0.5;
            break;
            case 'Once a month, or less':
                $oft = 0.25;
            break;
        }

        return $oft;
    }

    /**
     * Calculate annual carbon cost in tonnes
     * 
     * @param int $annualMileage
     * @param int $co2Gkm
     * 
     * @return float
     */
    public function calcAnnualCarbonCost(int $annualMileage, int $co2Gkm): float
    {
        $co2Gmi = $co2Gkm * self::KM_IN_M;
        $annualCo2 = $co2Gmi * $annualMileage;
        return (($annualCo2 / self::G_IN_KG) * self::KG_IN_T);
    }

    /**
     * Calculate annual carbon offset cost in currency
     * 
     * @param float $annualCarbonCost
     * 
     * @return float
     */
    public function calcAnnualCarbonOffsetCost(float $annualCarbonCost): float
    {
        return $annualCarbonCost * self::CARBON_OFFSET_PRICE_PER_T;
    }

    /**
     * Calculate annual number of trees destroyed
     * 
     * @param int $annualMileage
     * @param int $co2Gkm
     * 
     * @return float
     */
    public function calcAnnualNumberOfTreesDestroyed(int $annualMileage, int $co2Gkm): float
    {
        if ($annualMileage <= 0) {
            $annualMileage = CONFIG_AVG_DOM_ANNUAL_MILEAGE;
        }

        $co2LbMi = ($co2Gkm * self::KM_IN_M) * self::G_IN_LB;

        if ($co2LbMi == 0) {
            return 0;
        } else {
            $carbonAbsoredByOneTree = self::LB_IN_TREES / $co2LbMi;
            return $annualMileage / $carbonAbsoredByOneTree;
        }
    }

    /**
     * Calculate particle emissions
     * 
     * @param int $co2Gkm
     * @param string $vehicleType
     * @param string $fuelType
     * 
     * @return array
     */
    public function calcParticleEmissions(int $co2Gkm, string $vehicleType, string $fuelType): array
    {
        $return = [
            'mile' => [
                'NOx' => 0,
                'PM10' => 0,
                'PM2.5' => 0
            ],
            'kilometre' => [
                'NOx' => 0,
                'PM10' => 0,
                'PM2.5' => 0
            ]
        ];
        $query = 'SELECT * FROM particle_emissions_generic WHERE fuel_type = ? AND vehicle_type = ?';
        $bindings = [$fuelType, $vehicleType];
        $result = $this->con->fetchAssoc($query, $bindings);

        if ($result) {
            $nox = (float) $result['nox'];
            $pm10 = (float) $result['pm10'];
            $pm2point5 = (float) $result['pm25'];
            $noxPerKm = (float) $result['nox_mg_per_km'];
            $noxPerM = (float) $result['nox_mg_per_m'];
            $pm10PerKm = (float) $result['pm10_mg_per_km'];
            $pm10PerM = (float) $result['pm10_mg_per_m'];
            $pm2point5PerKm = (float) $result['pm25_mg_per_km'];
            $pm2point5PerM = (float) $result['pm25_mg_per_m'];
            $co2G = (float) $result['co2'];
            $co2Gmi = $co2Gkm * self::KM_IN_M;
            
            if ($co2G === 0.0) {
                $return['mile']['NOx'] = $noxPerM;
                $return['mile']['PM10'] = $pm10PerM;
                $return['mile']['PM2.5'] = $pm2point5PerM;
                $return['kilometre']['NOx'] = $noxPerKm;
                $return['kilometre']['PM10'] = $pm10PerKm;
                $return['kilometre']['PM2.5'] = $pm2point5PerKm;
            } else {
                $return['mile']['NOx'] = (($co2Gmi / $co2G) * $nox) * self::MG_IN_G;
                $return['mile']['PM10'] = (($co2Gmi / $co2G) * $pm10) * self::MG_IN_G;
                $return['mile']['PM2.5'] = (($co2Gmi / $co2G) * $pm2point5) * self::MG_IN_G;
                $return['kilometre']['NOx'] = (($co2Gkm / $co2G) * $nox) * self::MG_IN_G;
                $return['kilometre']['PM10'] = (($co2Gkm / $co2G) * $pm10) * self::MG_IN_G;
                $return['kilometre']['PM2.5'] = (($co2Gkm / $co2G) * $pm2point5) * self::MG_IN_G;
            }
        }

        return $return;
    }
}
