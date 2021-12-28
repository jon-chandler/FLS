<?php

namespace Application\Controller\SinglePage\Compare;

use Application\Helper\Budget;
use Application\Service\SessionAnswerService;
use Application\Service\KarfuApiService;
use Application\Service\KarfuAttributeMapService;
use Application\Helper\CostCalculator\CostCalculator;
use Application\Karfu\Journey\Hook\HookSummaryFunction;
use CollectionAttributeKey;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Session\SessionValidator;
use Core;
use DateTime;
use Page;
use PageController;

class Filter extends PageController
{
    /**
     * @var Connection
     */
    private $con;

    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var KarfuApiService
     */
    private $karfuApiService;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var Budget
     */
    private $budget;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var array
     */
    private $answers;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @param $obj
     * @param Connection $con
     * @param SessionValidator $sessionValidator
     * @param KarfuApiService $karfuApiService
     * @param SessionAnswerService $sessionAnswerService
     * @param Budget $budget
     * @param KarfuAttributeMapService $karfuAttributeMapService
     */
    public function __construct(
        $obj = null,
        Connection $con,
        SessionValidator $sessionValidator,
        KarfuApiService $karfuApiService,
        SessionAnswerService $sessionAnswerService,
        Budget $budget,
        KarfuAttributeMapService $karfuAttributeMapService
    )
    {
        parent::__construct($obj);
        $this->con = $con;
        $this->sessionValidator = $sessionValidator;
        $this->karfuApiService = $karfuApiService;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->budget = $budget;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
        $this->sessionKey = ($session) ? $session->getId() : '';
        $this->answers = $this->sessionAnswerService->getSessionAnswers();
    }

    /**
     * Get vehicle types
     * 
     * @return array
     */
    public function getVehicleTypes()
    {
        $vehicleTypes = [];

        // Get vehicle types from API
        $selectedVehicleTypes = $this->karfuApiService->getVehicleTypes(false);

        // Get vehicle types from attributes
        $ak = CollectionAttributeKey::getByHandle('vehicle_types');
        $akc = $ak->getController();
        $opts = array_map('strtoupper', $akc->getOptions());

        // Filter vehicle types by API vehicle types
        if (!empty($selectedVehicleTypes)) {
            foreach ($opts as $opt) {
                if (in_array($opt, $selectedVehicleTypes)) {
                    $vehicleTypes[] = ['option' => $opt, 'selected' => 'checked'];
                }
            }
        }

        return $vehicleTypes;
    }

    /**
     * Get min & max budget
     * 
     * @return array
     */
    public function getBudgetBounds()
    {
        // Get total cost of use
        $tcu = $this->budget->getTotal($this->answers);

        if (!empty($tcu)) {
            $budget = ceil($tcu);
        } else {
            $budget = 100000;
        }
        
        $budgetOpts = ['minVal' => 0, 'maxVal' => $budget, 'step' => 50];
        return $budgetOpts;
    }


    /**
     * Get min & max monthly budget
     * 
    * @return array
    */
    public function getMonhtlyBudgetBounds()
    {
        $step = 2;

        // Get ownership period & ongoing spend from summary hooks
        // TODO: We should get this data from a reusable class, maybe the Budget class?
        $summary = Core::make(HookSummaryFunction::class);
        $tot = $summary->getOngoingSpend(['answers' => $this->answers])->getData();
        $term = $summary->getVehicleKeepLength(['answers' => $this->answers])->getData();
        
        if (!empty($term)) {
            $months = str_replace([' years'], '', $term['html']) * 12;
        } else {
            $months = 12;
        }
        
        if (!empty($tot)) {
            $budget = str_replace([',', '£'], '', $tot['html']);
        } else {
            $budget = 10000;
        }

        // Calculate monthly spend
        $monthlySpend = number_format($budget / $months);

        // Get max monthly spend from results
        $query = 'SELECT MAX(TotalMonthlyCost) AS MaxTotalMonthlyCost FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID
        WHERE Active = ? AND SessionKey = ?';
        $bindings = [1, $this->sessionKey];
        $result = $this->con->fetchAssoc($query, $bindings);

        if ($result) {
            $maxTotalMonthlyCost = (float) $result['MaxTotalMonthlyCost'];
            
            // Override monthly spend with DB results
            if ($maxTotalMonthlyCost > $monthlySpend) {
                $monthlySpend = $step * ceil($maxTotalMonthlyCost / $step);
            }
        }

        $monthlyBudgetOpts = ['minVal' => 0, 'maxVal' => $monthlySpend, 'step' => $step];
        return $monthlyBudgetOpts;
    }

    /**
     * Get manufacturer list
     * 
     * @param array $vehicleTypes
     *
     * @return array
     */
    public function getBrandList(array $vehicleTypes)
    {
        $partners = [];

        // Get manufacturers from result set
        $query = 'SELECT DISTINCT ManName FROM karfu_vehicle
            INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID
            WHERE Active = ? AND SessionKey = ?';
        $bindings = [1, $this->sessionKey];
        $brands = $this->con->fetchAll($query, $bindings);

        if ($brands) {
            // Build partner query from list of manufacturers
            $query = 'SELECT * FROM btPartnerManager WHERE `partner` IN (';
            $bindings = [];
            foreach ($brands as $brand) {
                $bindings[] = $brand['ManName'];
            }

            $bindStr = implode(',',
                array_map(function ($brand) {
                    return '?';
                }, $brands)
            );
            $query .= $bindStr . ')';

            if (count($vehicleTypes) > 0) {
                $query .= ' AND partner_type IN (';
                foreach ($vehicleTypes as $vehicleType) {
                    $bindings[] = ucfirst(strtolower($vehicleType['option'])) . ' manufacturer';
                }

                $bindStr = implode(',',
                    array_map(function ($vehicleType) {
                        return '?';
                    }, $vehicleTypes)
                );
                $query .= $bindStr . ')';
            }

            // Get partners
            $partners = $this->con->fetchAll($query, $bindings);
        }

        return $partners;
    }

    /**
     * Get mobility options
     * 
     * @return array
     */
    public function getMobilityOptions()
    {
        // Get mobility choices from API
        $mobilityChoices = $this->karfuApiService->getMobilityChoices($this->answers);

        // Get mapping entries
        $mobilityTypeMaps = $this->karfuAttributeMapService->readByMappingType('mobilityTypeToHr');

        // Return formatted results
        return array_map(function ($mobilityChoice) use ($mobilityTypeMaps) {
            foreach ($mobilityChoice as $k => $mobilityType) {
                $mobilityChoice[$k] = $this->karfuAttributeMapService->mapNameFromList($mobilityType, $mobilityTypeMaps);
            }
            return $mobilityChoice;
        }, $mobilityChoices);
    }

    /**
     * Get vehicle types
     * 
     * @return array
     */
    public function getVehicleOptions()
    {
        $results = [];
        $results = $this->karfuApiService->getVehicleTypes();

        if (!empty($results)) {
            foreach ($results as $i => $result) {
                $results[$i] = ['option' => $result, 'selected' => 'checked'];
            }
        }

        return $results;
    }

    /**
     * Get min & max environment impacts
     * 
     * @return array
     */
    public function getEnviroImpactBounds()
    {
        $query = 'SELECT MIN(EnviroImpact) AS MinEnviroImpact, MAX(EnviroImpact) AS MaxEnviroImpact FROM karfu_vehicle_temp WHERE SessionKey = ?';
        $bindings = [$this->sessionKey];
        $result = $this->con->fetchAssoc($query, $bindings);

        if ($result) {
            return $result;
        } else {
            return ['MinEnviroImpact' => 0, 'MaxEnviroImpact' => 300];
        }
    }

    /**
     * Get min & max emissions
     * 
     * @return array
     */
    public function getEmissionsBounds()
    {
        $query = 'SELECT MIN(CO2GKM) AS MinCO2GKM, MAX(CO2GKM) AS MaxCO2GKM FROM karfu_vehicle
            INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID
            WHERE Active = ? AND SessionKey = ?';
        $bindings = [1, $this->sessionKey];
        $result = $this->con->fetchAssoc($query, $bindings);

        if ($result) {
            return $result;
        } else {
            return ['MinCO2GKM' => 0, 'MaxCO2GKM' => 300];
        }
    }

    /**
     * Get min & max distance
     * 
     * @return array
     */
    public function getDistanceBounds()
    {
        $step = 10;
        $query = 'SELECT MAX(LocationDistance) AS MaxLocationDistance FROM karfu_vehicle
            INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID
            WHERE Active = ? AND SessionKey = ?';
        $bindings = [1, $this->sessionKey];
        $result = $this->con->fetchAssoc($query, $bindings);
        $maxDistance = ($result) ? $step * ceil(((int) $result['MaxLocationDistance']) / $step) : 500;

        return ['minDistance' => 0, 'maxDistance' => $maxDistance, 'step' => $step];
    }

    /**
     * Get min & max mileage
     * 
     * @return array
     */
    public function getMileageBounds()
    {
        $step = 100;
        $query = 'SELECT MAX(CurrentMileage) AS MaxCurrentMileage FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID
        WHERE Active = ? AND SessionKey = ?';
        $bindings = [1, $this->sessionKey];
        $result = $this->con->fetchAssoc($query, $bindings);
        $maxMileage = ($result) ? $step * ceil(((int) $result['MaxCurrentMileage']) / $step) : 200000;

        return ['minMiles' => 0, 'maxMiles' => $maxMileage, 'step' => $step];
    }

    /**
     * Get vehicle conditions
     * 
     * @return array
     */
    public function getVehicleAgeCats()
    {
        return ['new', 'used'];
    }

    /**
     * Get min & max vehicle age in years
     * 
     * @return array
     */
    public function getVehicleYearsRange()
    {
        $query = 'SELECT MIN(RegistrationDate) AS MinRegistrationDate FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID
        WHERE Active = ? AND SessionKey = ?';
        $bindings = [1, $this->sessionKey];
        $result = $this->con->fetchAssoc($query, $bindings);
        $maxYears = 20;

        if ($result) {
            $todaysDate = new DateTime();
            $todaysDate->setTime(0, 0, 0);
            $maxRegistrationDate = DateTime::createFromFormat('Y-m-d', $result['MinRegistrationDate'])->setTime(0, 0, 0);
            $maxYears = (int) $maxRegistrationDate->diff($todaysDate)->format('%Y');
        }

        return ['min' => 0, 'max' => $maxYears];
    }

    /**
     * Get min & max vehicle seat count
     * 
     * @return int
     */
    public function getVehicleSeatCount()
    {
        $query = 'SELECT MIN(NumSeats) AS MinNumSeats, MAX(NumSeats) AS MaxNumSeats FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID
        WHERE Active = ? AND SessionKey = ?';
        $bindings = [1, $this->sessionKey];
        $result = $this->con->fetchAssoc($query, $bindings);
        $maxNumSeats = 8;

        if ($result) {
            $minNumSeats = (int) $result['MinNumSeats'];
            $maxNumSeats = (int) $result['MaxNumSeats'];
        }

        return ['min' => $minNumSeats, 'max' => $maxNumSeats];
    }

    /**
     * Get list of fuel types
     * 
     * @return array
     */
    public function getFuelTypes()
    {
        $fuelTypeMaps = $this->karfuAttributeMapService->readByMappingType('karfuToCapFuelType');
        $query = 'SELECT DISTINCT FuelType FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID
        WHERE Active = ? AND SessionKey = ?';
        $bindings = [1, $this->sessionKey];
        $results = $this->con->fetchAll($query, $bindings);
        $mappedFuelTypes = [];

        foreach ($results as $result) {
            $fuelType = $result['FuelType'];

            foreach ($fuelTypeMaps as $fuelTypeMap) {
                if ($fuelType === $fuelTypeMap['attribute_name']) {
                    $mappedFuelTypes[] = $fuelTypeMap['attribute_list'];
                    break;
                }
            }
        }

        return $mappedFuelTypes;
    }

    /**
     * Get list of nice to haves
     * 
     * @return array
     */
    public function getEssentials(): array
    {
        return [
            'Low Co2',
            '5 Star Safety',
            'Low Insurance',
            'Manual Gearbox',
            'Towing Capacity'
        ];
    }
}
