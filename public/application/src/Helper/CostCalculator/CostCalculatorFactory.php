<?php

declare(strict_types=1);

namespace Application\Helper\CostCalculator;

use Application\Helper\CostCalculator\CostCalculator;
use Application\Helper\CostCalculator\CostCalculatorOld;
use Application\Helper\CostCalculator\CarBuyingOutrightPurchase;
use Core;
use Exception;

/**
 * CostCalculatorFactory creates & returns CostCalculator classes
 */
class CostCalculatorFactory
{
    /**
     * Create CostCalculator class from vehicle, mobility type & mobility sub type
     * e.g. Vehicle: Car, Mobility Type: Buying, Mobility Subtype: HP => CarBuyingHp
     * 
     * @param string $vehicle
     * @param string $mobilityChoice
     * @param string $mobilityChoiceType
     * @param bool $isCurrentCar
     * 
     * @return CostCalculator|CostCalculatorOld
     * @throws Exception
     */
    public function create(string $vehicle, string $mobilityChoice, string $mobilityChoiceType, bool $isCurrentCar = false)
    {
        $names = ($isCurrentCar) ? ['Current Car'] : [$vehicle, $mobilityChoice, $mobilityChoiceType];
        $className = $this->createClassName($names);
        if (class_exists($className)) {
            return Core::make($className);
        } else {
            // Fallback to old version
            return Core::make(CostCalculatorOld::class);
            // throw new Exception('Error creating cost calculator class ' + $className);
        }
    }

    /**
     * Convert string to class name format
     * 
     * @param string $value
     * 
     * @return string
     */
    private function stringToClassNameFriendly(string $value): string
    {
        $search = [' ', '-', '/'];
        $ucwDelimiters = " -\t\r\n\f\v";
        return str_replace($search, '', ucwords(strtolower($value), $ucwDelimiters));
    }

    /**
     * Create the full class name prefixed with the namespace
     * 
     * @param array $strings
     * 
     * @return string
     */
    private function createClassName(array $values): string
    {
        $className = __NAMESPACE__ . '\\';

        foreach ($values as $value) {
            $className .= $this->stringToClassNameFriendly($value);
        }

        return $className;
    }
}
