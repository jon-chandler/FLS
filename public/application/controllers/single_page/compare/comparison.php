<?php

namespace Application\Controller\SinglePage\Compare;

use Application\Factory\VehicleFactory;
use Application\Helper\CostCalculator\CostCalculator;
use Application\Helper\CostCalculator\CostCalculatorFactory;
use Application\Helper\Image;
use Application\Helper\VehicleFormula;
use Application\Model\VehicleCost;
use Application\Service\KarfuAttributeMapService;
use Application\Service\ScrapedVehicleContentService;
use Application\Service\SessionAnswerService;
use Application\Service\ShortlistService;
use Application\Service\VehicleService;
use Concrete\Core\Database\Connection\Connection;
use DateTime;
use PageController;
use URLify;

class Comparison extends PageController
{
    /**
     * @var Connection
     */
    private $con;

   /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var VehicleService
     */
    private $vehicleService;

    /**
     * @var CostCalculatorFactory
     */
    private $costCalculatorFactory;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var VehicleFormula
     */
    private $vehicleFormula;

    /**
     * @var ScrapedVehicleContentService
     */
    private $scrapedVehicleContentService;

    /**
     * @var ShortlistService
     */
    private $shortlistService;

    /**
     * @var VehicleFactory
     */
    private $vehicleFactory;

    /**
     * @param $obj
     * @param Connection $con
     * @param SessionAnswerService $sessionAnswerService
     * @param VehicleService $vehicleService
     * @param CostCalculatorFactory $costCalculatorFactory
     * @param KarfuAttributeMapService $karfuAttributeMapService
     * @param VehicleFormula $vehicleFormula
     * @param ScrapedVehicleContentService $scrapedVehicleContentService
     * @param ShortlistService $shortlistService
     * @param VehicleFactory $vehicleFactory
     */
    public function __construct(
        $obj = null,
        Connection $con,
        SessionAnswerService $sessionAnswerService,
        VehicleService $vehicleService,
        CostCalculatorFactory $costCalculatorFactory,
        KarfuAttributeMapService $karfuAttributeMapService,
        VehicleFormula $vehicleFormula,
        ScrapedVehicleContentService $scrapedVehicleContentService,
        ShortlistService $shortlistService,
        VehicleFactory $vehicleFactory
    )
    {
        parent::__construct($obj);
        $this->con = $con;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->vehicleService = $vehicleService;
        $this->costCalculatorFactory = $costCalculatorFactory;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $this->vehicleFormula = $vehicleFormula;
        $this->scrapedVehicleContentService = $scrapedVehicleContentService;
        $this->shortlistService = $shortlistService;
        $this->vehicleFactory = $vehicleFactory;
    }

    /**
     * Concrete5 on_start hook
     */
    public function on_start()
    {
        // Get left & right array keys for currently viewing vehicles
        $lIdx = $this->request->request('lIdx');
        $rIdx = $this->request->request('rIdx');

        $deleteIdx = $this->request->request('delete');
        $postVehicles = $this->request->request('vehicle');
        $lIdx = ($lIdx) ? $lIdx : 1;
        $rIdx = ($rIdx) ? $rIdx : 2;
        $vehicles = [];
        $left = null;
        $right = null;
        $newLeft = null;
        $newRight = null;

        // Save vehicles from POST to SESSION
        if ($postVehicles && is_array($postVehicles)) {
            $i = 1;
            foreach ($postVehicles as $postVehicle) {
                if (is_numeric($postVehicle)) {
                    $vehicles[$i] = (int) $postVehicle;
                    $i++;
                }
            }

            if (count($vehicles) > 0) {
                $_SESSION['KARFU_user']['compare'] = $vehicles;
            }
        }

        // Check if SESSION exists
        if (isset($_SESSION['KARFU_user']['compare'])) {
            $vehicles = $_SESSION['KARFU_user']['compare'];
        }

        // CHeck if delete is set
        if ($deleteIdx && is_numeric($deleteIdx) && $deleteIdx > 0) {
            $deleteIdx = (int) $deleteIdx;

            if (array_key_exists($deleteIdx, $vehicles)) {
                unset($vehicles[$deleteIdx]);

                // Reindex the vehicles array
                $i = 1;
                $reidxVehicles = [];
                foreach ($vehicles as $vehicle) {
                    $reidxVehicles[$i] = $vehicle;
                    $i++;
                }
                $vehicles = $reidxVehicles;
                unset($i, $reidxVehicles);
                $_SESSION['KARFU_user']['compare'] = $vehicles;
            }
        }

        // Get vehicle count
        $vehicleCount = count($vehicles);

        if ($vehicleCount > 0 && is_numeric($lIdx) && is_numeric($rIdx)) {
            $lIdx = (int) $lIdx;
            $rIdx = (int) $rIdx;

            if ($lIdx === $rIdx) {
                $lIdx = 1;
                $rIdx = 2;
            }

            if (array_key_exists($lIdx, $vehicles) && array_key_exists($rIdx, $vehicles)) {
                $left = $this->getVehicleComparisonData($vehicles, $lIdx, $rIdx);
                $right = $this->getVehicleComparisonData($vehicles, $rIdx, $lIdx);
            }
        }
        
        $this->set('left', $left);
        $this->set('right', $right);
        $this->set('vehicleCount', $vehicleCount);
    }

    /**
     * Get the comparison data for a vehicle
     * 
     * @param array $vehicles
     * @param int $idx
     * @param int $oIdx Opposite idx. e.g. If idx is left, oIdx is right
     * 
     * @return array
     */
    private function getVehicleComparisonData(array $vehicles, int $idx, int $oIdx): array
    {
        $vehicleAttributes = [];
        $id = $vehicles[$idx];
        $imageHelper = new Image();
        $url = null;
        $vrm = '';

        $vehicleList = array_filter($vehicles, function ($v, $k) use ($oIdx) {
            return ($k === $oIdx) ? false : true;
        }, ARRAY_FILTER_USE_BOTH);

        // Get shortlist
        $shortlist = $this->shortlistService->readByIdJoinApiCache($id);
        $vehicleTempData = $shortlist->getVehicleTempData();

        // Is current vehicle i.e. vehicle being traded in
        $isCurrentVehicle = ($shortlist->getApiCache()) ? true : false;
        if ($isCurrentVehicle) {
            $cacheData = $shortlist->getApiCache()->getData();
            $capId = $cacheData['derivativeDetails']['capId'];

            // Get karfu vehicle
            $karfuVehicle = $this->vehicleService->readByCapId($capId);

            // Merge karfu vehicle with cache data
            $vehicle = $this->vehicleFactory->createFromKarfuVehicleAndCapData($karfuVehicle, $cacheData);

            $vrm = $cacheData['derivativeDetails']['vrm'];
            $price = 0;
            $provider = 'Current Vehicle';
            $distance = 0;
            $currentMileage = 0;
            $year = DateTime::createFromFormat('Y-m-d', $cacheData['vehicleDetails']['firstRegistration']['firstRegistrationDate'])->format('Y');
            $condition = 'Used';
        } else {
            // Get vehicle
            $vehicle = $this->vehicleService->readById($shortlist->getVehicleId());

            // Get scraped vehicle
            $carsForSale = $this->scrapedVehicleContentService->mapByFuelAndDerivative(
                $vehicle['FuelType'],
                $vehicle['Derivative']
            );

            // Get scraped vehicle to get a vrm
            $scrapedCars = $this->scrapedVehicleContentService->mapScrapedData(
                $vehicle['ManName'],
                $vehicle['RangeName'],
                $vehicle['FuelType'],
                $vehicle['Derivative']
            );
            if ($scrapedCars) {
                $vrm = $scrapedCars[0]['vrm'];
            }

            if ($carsForSale) {
                if (
                    $shortlist->getMobilityChoice() === CostCalculator::OWNERSHIP_OUTRIGHT
                    || $shortlist->getMobilityChoiceType() === CostCalculator::OWNERSHIP_HP
                )
                {
                    // Mock vehicle used price
                    $vehicle['Price'] = $carsForSale[0]['price'];
                }

                // Hard code used details
                $provider = 'Arnold Clark';
                $currentMileage = (int) $carsForSale[0]['mileage'];
                $condition = 'Used';
            } else {
                // Default value to new vehicle from manufacturer
                $provider = $vehicle['ManName'];
                $currentMileage = 0;
                $condition = 'New';
            }

            $distance = 0;
            $year = date('Y');
            if ($vehicleTempData) {
                $distance = (float) $vehicleTempData['locationDistance'];
                $condition = $vehicleTempData['condition'];
                if (isset($vehicleTempData['RegistrationDate'])) {
                    $year = DateTime::createFromFormat('Y-m-d', $vehicleTempData['RegistrationDate'])->format('Y');
                }
            }

            $price = (float) $vehicle['Price'];
        }

        if ($vehicle) {
            $vehicle['mobilityChoice']['name'] = $shortlist->getMobilityChoice();
            $vehicle['mobilityChoice']['type'] = $shortlist->getMobilityChoiceType();

            // Get answers
            $answers = $shortlist->getAnswers();
            $annualMileage = (isset($answers['whatIsYourEstimatedMileage'])) ? (int) $answers['whatIsYourEstimatedMileage'] : 0;
            $ownershipPeriod = (isset($answers['howLongTerm'])) ? (int) $answers['howLongTerm'] : 0;
            $ownershipPeriodInMonths = $ownershipPeriod * 12;
            
            // Get image directories
            $licencePlate = strtoupper($vrm);
            $image = "{$_SERVER['DOCUMENT_ROOT']}/application/files/vehicles/{$licencePlate}.jpg";
            $imagePath = $imageHelper->getMissingImage($vehicle['VehicleType']);
            $imageClass = 'compressed'; 
        
            if (file_exists($image) && filesize($image) > 250) {
                $imagePath = "/application/files/vehicles/{$licencePlate}.jpg";
                $imageClass = 'full'; 
            }

            if ($vehicle['ImageLink']) {
                $imagePath = $vehicle['ImageLink'];
                $imageClass = 'compressed';
            }

            // Get mappings
            $mobilityTypeMaps = $this->karfuAttributeMapService->readByMappingType('mobilityTypeToHr');
            $vehicleType = $this->karfuAttributeMapService->mapToKarfuAttribute($vehicle['VehicleType'], 'vehicleTableToHuman');

            // Create cost calculator class
            $costCalculator = $this->costCalculatorFactory->create(
                $vehicleType,
                $shortlist->getMobilityChoice(),
                $shortlist->getMobilityChoiceType(),
                $isCurrentVehicle
            );

            // Tell the cost calculator it's working off snapshot answers
            $costCalculator->setIsSnapshotAnswers(true);

            // Get costs
            $costs = $costCalculator->calculateCosts($vehicle, $shortlist->getAnswers());

            $totalCost = $costCalculator->getTotalCost($costs, $shortlist->getAnswers());
            $monthlyCost = $costCalculator->getMonthlyCost($costs);
            $monthlyIncome = abs($costCalculator->getMonthlyIncome($costs));
            $monthlyRunningCost = $costCalculator->getMonthlyCostByCategory(VehicleCost::CAT_NET_RUNNING, $costs);
            $vAndPMonthly = $costCalculator->getMonthlyCostByCategory(VehicleCost::CAT_NET_VEHICLE_PROVIDER, $costs);
            $pricePerMile = $costCalculator->getVehicleCostsByFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PRICE_PER_MILE, $costs);
            $pricePerMile = (count($pricePerMile) > 0) ? $pricePerMile[0]->getCost() : null;
            $pricePerWeek = $costCalculator->getVehicleCostsByFrequencyTitle(VehicleCost::FREQUENCY_TITLE_PRICE_PER_WEEK, $costs);
            $pricePerWeek = (count($pricePerWeek) > 0) ? $pricePerWeek[0]->getCost() : null;
            $startingPosition = $costCalculator->getFrequencyTitleTotalCost(VehicleCost::CAT_NET_POSITION, VehicleCost::FREQUENCY_TITLE_STARTING_POSITION, $costs);
            $netSpending = $costCalculator->getVehicleCostsByFrequencyTitle(VehicleCost::FREQUENCY_TITLE_NET_SPEND, $costs);
            $netSpending = (count($netSpending) > 0) ? $netSpending[0]->getCost() : null;
            $netPosition = $costCalculator->getVehicleCostsByFrequencyTitle(VehicleCost::FREQUENCY_TITLE_NET_POSITION_END_TERM, $costs);
            $netPosition = (count($netPosition) > 0) ? $netPosition[0]->getCost() : null;
            $netEquityEndOfTerm = $costCalculator->getFrequencyTitleTotalCost(VehicleCost::CAT_NET_POSITION, VehicleCost::FREQUENCY_TITLE_VEHICLE_EQUITY_END_TERM, $costs);
            $depreciation = $costCalculator->getVehicleCostByName('Depreciation', $costs);
            $depreciation = ($depreciation) ? $depreciation->getCost() : 0;
            $estValue = $costCalculator->getVehicleCostByName('Estimated Value', $costs);
            $estValue = ($estValue) ? $estValue->getCost() : 0;
            $annualCarbon = $this->vehicleFormula->calcAnnualCarbonCost($annualMileage, (int) $vehicle['CO2GKM']);
            $annualCarbonOffset = $this->vehicleFormula->calcAnnualCarbonOffsetCost($annualCarbon);
            $annualNumTreesDestroyed = $this->vehicleFormula->calcAnnualNumberOfTreesDestroyed($annualMileage, (int) $vehicle['CO2GKM']);
            $particleEmissions = $this->vehicleFormula->calcParticleEmissions((int) $vehicle['CO2GKM'], $vehicleType, $vehicle['FuelType']);
            $carbon = round($annualCarbon * $ownershipPeriod, 2);
            $carbonOffset = round($annualCarbonOffset * $ownershipPeriod, 2);
            $numTreesDestroyed = round($annualNumTreesDestroyed * $ownershipPeriod);
            $noxM = round($particleEmissions['mile']['NOx']);
            $pm10M = round($particleEmissions['mile']['PM10']);
            $pm25M = round($particleEmissions['mile']['PM2.5']);
            $noxKm = round($particleEmissions['kilometre']['NOx']);
            $pm10Km = round($particleEmissions['kilometre']['PM10']);
            $pm25Km = round($particleEmissions['kilometre']['PM2.5']);
            $particleEmissionsSumM = $noxM + $pm10M + $pm25M;
            $particleEmissionsSumKm = $noxKm + $pm10Km + $pm25Km;
            $totalParticleEmissions = round($particleEmissionsSumM * ($annualMileage * $ownershipPeriod));
            $tradeOffs = $this->con->fetchAssoc(
                'SELECT * FROM vehicle_mobility_trade_off WHERE vehicle_type = ? AND mobility_choice = ? AND mobility_type = ?',
                [
                    $vehicleType,
                    $shortlist->getMobilityChoice(),
                    $shortlist->getMobilityChoiceType()
                ]
            );

            // Build vehicle breakdown url
            $slug = URLify::filter($vehicle['ManName'] . ' ' . $vehicle['ModelName'] . ' ' . $vehicle['Derivative']);
            $url = ($isCurrentVehicle) ? null :
                '/compare/vehicle-breakdown/'
                . strtolower($slug) . '/' .
                $shortlist->getVehicleId() . '/'
                . strtolower($shortlist->getMobilityChoice()) . '/'
                . str_replace(' ', '-', strtolower($shortlist->getMobilityChoiceType())) . '/'
                . $shortlist->getId();

            // Start building vehicle attributes array
            $vehicleAttributes['keyData'] = [
                'category' => 'Key Data',
                'attributes' => []
            ];
            $attributes['title'] = 'Term';
            $attributes['value'] = $ownershipPeriod;
            $attributes['value'] .= ($ownershipPeriod === 1) ? ' Year' : ' Years';
            $attributes['isSubHeader'] = false;
            $vehicleAttributes['keyData']['attributes'][] = $attributes;
            $attributes['title'] = 'Term Mileage';
            $attributes['value'] = number_format($annualMileage * $ownershipPeriod) . ' Miles';
            $vehicleAttributes['keyData']['attributes'][] = $attributes;
            $attributes['title'] = 'How';
            $attributes['value'] = $shortlist->getMobilityChoice();
            $vehicleAttributes['keyData']['attributes'][] = $attributes;
            $attributes['title'] = 'Type';
            $attributes['value'] = $this->karfuAttributeMapService->mapNameFromList($shortlist->getMobilityChoiceType(), $mobilityTypeMaps);
            $vehicleAttributes['keyData']['attributes'][] = $attributes;
            $attributes['title'] = 'Provider';
            $attributes['value'] = $provider;
            $vehicleAttributes['keyData']['attributes'][] = $attributes;
            $attributes['title'] = 'Distance from you';
            $attributes['value'] = $distance;
            $attributes['value'] .= ($distance === 1) ? ' Mile' : ' Miles';
            $vehicleAttributes['keyData']['attributes'][] = $attributes;

            $vehicleAttributes['vehicleData'] = [
                'category' => 'Vehicle Data',
                'attributes' => []
            ];
            $attributes['title'] = 'What';
            $attributes['value'] = $vehicleType;
            $vehicleAttributes['vehicleData']['attributes'][] = $attributes;
            $attributes['title'] = 'Style';
            $attributes['value'] = !empty($vehicle['KarfuBodyStyle']) ? $vehicle['KarfuBodyStyle'] : $vehicle['BodyStyle'];
            $vehicleAttributes['vehicleData']['attributes'][] = $attributes;
            $attributes['title'] = 'Status';
            $attributes['value'] = $condition;
            $vehicleAttributes['vehicleData']['attributes'][] = $attributes;
            $attributes['title'] = 'Year';
            $attributes['value'] = $year;
            $vehicleAttributes['vehicleData']['attributes'][] = $attributes;
            $attributes['title'] = 'Price';
            $attributes['value'] = '£' . number_format($price);
            $vehicleAttributes['vehicleData']['attributes'][] = $attributes;
            $attributes['title'] = 'Fuel';
            $attributes['value'] = $vehicle['FuelType'];
            $vehicleAttributes['vehicleData']['attributes'][] = $attributes;
            $attributes['title'] = 'Current Mileage';
            $attributes['value'] = $currentMileage;
            $attributes['value'] .= ($currentMileage === 1) ? ' Mile' : ' Miles';
            $vehicleAttributes['vehicleData']['attributes'][] = $attributes;
            $attributes['title'] = 'Insurance Group';
            $attributes['value'] = $vehicle['InsuranceGroup'];
            $vehicleAttributes['vehicleData']['attributes'][] = $attributes;

            $vehicleAttributes['money'] = [
                'category' => 'Money',
                'attributes' => []
            ];
            $attributes['title'] = 'TCU';
            $attributes['value'] = '£' . number_format($totalCost);
            $vehicleAttributes['money']['attributes'][] = $attributes;
            $attributes['title'] = 'Total Monthly';
            $attributes['value'] = '£' . number_format($monthlyCost);
            $vehicleAttributes['money']['attributes'][] = $attributes;
            $attributes['title'] = 'V&P Monthly';
            $attributes['value'] = '£' . number_format($vAndPMonthly);
            $vehicleAttributes['money']['attributes'][] = $attributes;
            $attributes['title'] = 'Running Monthly';
            $attributes['value'] = '£' . number_format($monthlyRunningCost);
            $vehicleAttributes['money']['attributes'][] = $attributes;
            $attributes['title'] = 'Income Monthly';
            $attributes['value'] = '£' . number_format($monthlyIncome);
            $vehicleAttributes['money']['attributes'][] = $attributes;

            $vehicleAttributes['environment'] = [
                'category' => 'Environment',
                'attributes' => []
            ];
            $attributes['title'] = 'Over Term';
            $attributes['value'] = null;
            $attributes['isSubHeader'] = true;
            $vehicleAttributes['environment']['attributes'][] = $attributes;
            $attributes['isSubHeader'] = false;
            $attributes['title'] = "Impact";
            $attributes['value'] = number_format($numTreesDestroyed);
            $attributes['value'] .= ($numTreesDestroyed === 1) ? ' Tree Destroyed' : ' Trees Destroyed';
            $vehicleAttributes['environment']['attributes'][] = $attributes;
            $attributes['title'] = 'CO2';
            $attributes['value'] = $carbon;
            $attributes['value'] .= ($carbon === 1) ? ' (Tonne)' : ' (Tonnes)';
            $vehicleAttributes['environment']['attributes'][] = $attributes;
            $attributes['title'] = 'Particle Emissions';
            $attributes['value'] = number_format($totalParticleEmissions) . ' (MG)';
            $vehicleAttributes['environment']['attributes'][] = $attributes;
            $attributes['title'] = 'In Term';
            $attributes['value'] = null;
            $attributes['isSubHeader'] = true;
            $vehicleAttributes['environment']['attributes'][] = $attributes;
            $attributes['isSubHeader'] = false;
            $attributes['title'] = 'CO2';
            $attributes['value'] = number_format((int) $vehicle['CO2GKM']) . ' (G/KM)';
            $vehicleAttributes['environment']['attributes'][] = $attributes;
            $attributes['title'] = 'CO2';
            $attributes['value'] = number_format(((int) $vehicle['CO2GKM'] * 1.60934)) . ' (G/Mile)';
            $vehicleAttributes['environment']['attributes'][] = $attributes;
            $attributes['title'] = 'Particle Emissions';
            $attributes['value'] = number_format($particleEmissionsSumKm) . ' (MG/KM)';
            $vehicleAttributes['environment']['attributes'][] = $attributes;
            $attributes['title'] = 'Particle Emissions';
            $attributes['value'] = number_format($particleEmissionsSumM) . ' (MG/Mile)';
            $vehicleAttributes['environment']['attributes'][] = $attributes;

            $vehicleAttributes['efficiency'] = [
                'category' => 'Efficiency',
                'attributes' => []
            ];
            $attributes['title'] = 'MPG';
            $attributes['value'] = number_format($vehicle['CombinedMPG']);
            $vehicleAttributes['efficiency']['attributes'][] = $attributes;

            $vehicleAttributes['netPosition'] = [
                'category' => 'Net Position',
                'attributes' => []
            ];
            $attributes['title'] = 'Starting Position';
            $attributes['value'] = '£' . number_format($startingPosition);
            $vehicleAttributes['netPosition']['attributes'][] = $attributes;
            $attributes['title'] = 'Net Position';
            $attributes['value'] = '£' . number_format($netPosition);
            $vehicleAttributes['netPosition']['attributes'][] = $attributes;
            $attributes['title'] = 'Net Spend';
            $attributes['value'] = '£' . number_format($netSpending);
            $vehicleAttributes['netPosition']['attributes'][] = $attributes;
            $attributes['title'] = 'Cost Per Mile';
            $attributes['value'] = '£' . number_format($pricePerMile, 2);
            $vehicleAttributes['netPosition']['attributes'][] = $attributes;
            $attributes['title'] = 'Cost Per Week';
            $attributes['value'] = '£' . number_format($pricePerWeek);
            $vehicleAttributes['netPosition']['attributes'][] = $attributes;

            $vehicleAttributes['vehicleEquity'] = [
                'category' => 'Vehicle Equity',
                'attributes' => []
            ];
            $attributes['title'] = 'Vehicle Equity at End of Term';
            $attributes['value'] = '£' . number_format($netEquityEndOfTerm);
            $vehicleAttributes['vehicleEquity']['attributes'][] = $attributes;
            $attributes['title'] = 'Purchase Price / Value';
            $attributes['value'] = '£' . number_format($price);
            $vehicleAttributes['vehicleEquity']['attributes'][] = $attributes;
            $attributes['title'] = 'Depreciation';
            $attributes['value'] = '£' . number_format($depreciation);
            $vehicleAttributes['vehicleEquity']['attributes'][] = $attributes;
            $attributes['title'] = 'Estimated Value';
            $attributes['value'] = '£' . number_format($estValue);
            $vehicleAttributes['vehicleEquity']['attributes'][] = $attributes;

            $vehicleAttributes['highLevelOverview'] = [
                'category' => 'High Level Overview',
                'attributes' => []
            ];
            $attributes['title'] = $shortlist->getMobilityChoiceType();
            $attributes['value'] = null;
            $attributes['isSubHeader'] = true;
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['isSubHeader'] = false;
            $attributes['title'] = 'Upfront Cost';
            $attributes['value'] = $tradeOffs['upfront_cost'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['title'] = 'Maintenance Cost';
            $attributes['value'] = $tradeOffs['maintenance_cost'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['title'] = 'Security Cost';
            $attributes['value'] = $tradeOffs['security_cost'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['title'] = 'Parking';
            $attributes['value'] = $tradeOffs['parking'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['title'] = 'Storage';
            $attributes['value'] = $tradeOffs['storage'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['title'] = 'Safety Cost';
            $attributes['value'] = $tradeOffs['safety_cost'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['title'] = 'Waiting Time';
            $attributes['value'] = $tradeOffs['waiting_time'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['title'] = 'Daily Cost';
            $attributes['value'] = $tradeOffs['daily_cost'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['title'] = 'Fuel / Energy Cost';
            $attributes['value'] = $tradeOffs['fuel_energy_cost'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            $attributes['title'] = 'Income Potential';
            $attributes['value'] = $tradeOffs['income_potential'];
            $vehicleAttributes['highLevelOverview']['attributes'][] = $attributes;
            // End building vehicle attributes array
        }

        return [
            'idx' => $idx,
            'isCurrentVehicle' => $isCurrentVehicle,
            'vehicleList' => $vehicleList,
            'vehicle' => $vehicle,
            'imgPath' => $imagePath,
            'imgClass' => $imageClass,
            'url' => $url,
            'vehicleAttributes' => $vehicleAttributes
        ];
    }

    /**
     * @param mixed $val
     * 
     * @return string
     */
    public function responseMarkup($val): string
    {
        $responseMarkup = '<span class="yesNo tbc">' . $val . '</span>';

        if($val === 'Y') {
            $responseMarkup = '<span class="yesNo yes"><img src="../application/themes/KARFU/images/icons/tick_no_border.svg" alt="Yes" /></span>';
        } else if ($val === 'N') {
            $responseMarkup = '<span class="yesNo no"><img src="../application/themes/KARFU/images/icons/cross_no_border.svg" alt="No" /></span>';
        }

        return $responseMarkup;
    }
}
