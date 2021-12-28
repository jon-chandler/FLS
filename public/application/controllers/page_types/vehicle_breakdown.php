<?php

namespace Application\Controller\PageType;

use Application\Helper\CostCalculator\CostCalculator;
use Application\Helper\CostCalculator\CostCalculatorCacheValues;
use Application\Helper\CostCalculator\CostCalculatorFactory;
use Application\Helper\VehicleAttributeMap;
use Application\Helper\VehicleFormula;
use Application\Karfu\Journey\SmartRedirect;
use Application\Model\VehicleCost;
use Application\Service\KarfuAttributeMapService;
use Application\Service\ScrapedVehicleContentService;
use Application\Service\SessionAnswerService;
use Application\Service\ShortlistService;
use Application\Service\VehicleService;
use Application\Service\VehicleTempService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Page\Controller\PageTypeController;
use Concrete\Core\Support\Facade\Express;
Use DateTime;
use User;
use Exception;

class VehicleBreakdown extends PageTypeController
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
     * @var VehicleTempService
     */
    private $vehicleTempService;

    /**
     * @var ScrapedVehicleContentService
     */
    private $scrapedVehicleContentService;

    /**
     * @var CostCalculator
     */
    private $costCalculator;

    /**
     * @var CostCalculatorCacheValues
     */
    private $costCalculatorCacheValues;

    /**
     * @var CostCalculatorFactory
     */
    private $costCalculatorFactory;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var ShortlistService
     */
    private $shortlistService;

    /**
     * @var SmartRedirect
     */
    private $smartRedirect;

    /**
     * @var VehicleFormula
     */
    private $vehicleFormula;

    /**
     * @var VehicleAttributeMap
     */
    private $vehicleAttributeMap;

    /**
     * @param $obj
     * @param Connection $con
     * @param SessionAnswerService $sessionAnswerService
     * @param VehicleService $vehicleService
     * @param VehicleTempService $vehicleTempService
     * @param ScrapedVehicleContentService $scrapedVehicleContentService
     * @param CostCalculatorCacheValues $costCalculatorCacheValues
     * @param CostCalculatorFactory $costCalculatorFactory
     * @param KarfuAttributeMapService $karfuAttributeMapService
     * @param ShortlistService $shortlistService
     * @param SmartRedirect $smartRedirect
     * @param VehicleFormula $vehicleFormula
     * @param VehicleAttributeMap $vehicleAttributeMap
     */
    public function __construct(
        $obj = null,
        Connection $con,
        SessionAnswerService $sessionAnswerService,
        VehicleService $vehicleService,
        VehicleTempService $vehicleTempService,
        ScrapedVehicleContentService $scrapedVehicleContentService,
        CostCalculatorCacheValues $costCalculatorCacheValues,
        CostCalculatorFactory $costCalculatorFactory,
        KarfuAttributeMapService $karfuAttributeMapService,
        ShortlistService $shortlistService,
        SmartRedirect $smartRedirect,
        VehicleFormula $vehicleFormula,
        VehicleAttributeMap $vehicleAttributeMap
    )
    {
        parent::__construct($obj);
        $this->con = $con;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->vehicleService = $vehicleService;
        $this->vehicleTempService = $vehicleTempService;
        $this->scrapedVehicleContentService = $scrapedVehicleContentService;
        $this->costCalculatorCacheValues = $costCalculatorCacheValues;
        $this->costCalculatorFactory = $costCalculatorFactory;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $this->shortlistService = $shortlistService;
        $this->smartRedirect = $smartRedirect;
        $this->vehicleFormula = $vehicleFormula;
        $this->vehicleAttributeMap = $vehicleAttributeMap;
    }

    /**
     * Concrete5 view hook
     * 
     * @param string $slug
     * @param int $id
     * @param string $mobilityChoice
     * @param string $mobilityChoiceType
     * @param int $shortlistId
     */
    public function view($slug = null, $id = null, $mobilityChoice = null, $mobilityChoiceType = null, $shortlistId = null)
    {
        if ($slug && $id && is_numeric($id) && $mobilityChoice && $mobilityChoiceType) {
            $id = (int) $id;
            $mobilityChoice = strtoupper($mobilityChoice);
            $mobilityChoiceType = strtoupper(str_replace('-', ' ', $mobilityChoiceType));
            $mobilityChoiceTypeHr = $this->karfuAttributeMapService->mapToKarfuAttribute($mobilityChoiceType, 'mobilityTypeToHr');
            $mobilityChoiceTypeHr = ($mobilityChoiceTypeHr) ? $mobilityChoiceTypeHr : $mobilityChoiceType;
            $moneyOut = [];
            $moneyIn = [];
            $moneyOutTotal = 0;
            $moneyInTotal = 0;
            $amountOfCredit = 0;
            $isShortlisted = false;
            $isFromShortlist = false;
            $karfuScoreTabContent = null;
            $isSellingVehicle = false;

            // CHeck if user is logged in
            $user = new User();
            if ($user->isLoggedIn()) {
                $userId = (int) $user->getUserID();

                // Get shortlist record
                if (is_numeric($shortlistId)) {
                    $shortlistId = (int) $shortlistId;
                    $shortlist = $this->shortlistService->readById($shortlistId);
                } else {
                    $shortlist = $this->shortlistService->readByUserVehicleMobilityChoiceMobilityChoiceType($id, $userId, $mobilityChoice, $mobilityChoiceType);
                }

                // If shortlist record exists, set variables
                if ($shortlist) {
                    $isShortlisted = true;
                    $isFromShortlist = (is_numeric($shortlistId)) ? true : false;
                }
            }

            // If there is no shortlist record, we need to check user has a completed journey in the current session
            // If not, we need to redirect them
            if (!$isFromShortlist) {
                if (
                    isset($_SESSION['KARFU_user']['currentJourneyType'])
                    && isset($_SESSION['KARFU_user']['currentJourneyGroup'])
                ) {
                    $currentJourneyType = $_SESSION['KARFU_user']['currentJourneyType'];
                    $currentJourneyGroup = $_SESSION['KARFU_user']['currentJourneyGroup'];

                    if (isset($_SESSION['KARFU_user']['completed'])) {
                        $list = new EntryList(Express::getObjectByHandle('journey'));
                        $list->filterByAttribute('journey_type', $currentJourneyType, '=');
                        $list->filterByAttribute('journey_group', $currentJourneyGroup, '=');
                        $journeys = $list->getResults();
        
                        if ($currentJourneyType === 'Standard') {
                            foreach ($journeys as $journey) {
                                $journeyOrder = (int) $journey->getJourneyOrder();
                                if (!in_array($journeyOrder, $_SESSION['KARFU_user']['completed'])) {
                                    return $this->smartRedirect->redirectToLastAnsweredQuestion(
                                        $journey->getJourneyTitle(),
                                        $journey->getJourneyGroup()
                                    );
                                }
                            }
                        }
                    } else {
                        $this->redirect('/');    
                    }
                } else {
                    $this->redirect('/');
                }
            }

            // Get vehicle by the id in the url
            $vehicle = $this->vehicleService->readById($id);

            if ($vehicle) {
                $vehicleCondition = null;
                $vehicleMileage = 0;
                $vehicleDistance = 0;
                $vehicleYear = date('Y');
                $vehicleLocation = null;
                $vehicleProvider = $vehicle['ManName'];
                $co2Rating = (isset($vehicle['CO2GKM'])) ? (int) $vehicle['CO2GKM'] : 0;
                $vehicle['priceNew'] = $vehicle['Price'];
                $netEquity = 0;
                $vehicle['mobilityChoice'] = [
                    'name' => $mobilityChoice,
                    'type' => $mobilityChoiceType,
                    'typeHr' => $mobilityChoiceTypeHr
                ];
                $price = (float) $vehicle['Price'];
                $finalVehicleCosts = [];
                
                // Get vehicle temp
                $vehicleTemp = $this->vehicleTempService->readBySessionKeyAndKvIdAndMobilityChoiceAndMobilityType(session_id(), $id, $mobilityChoice, $mobilityChoiceType);

                if ($vehicleTemp) {
                    // Merge vehicle temp data with vehicle data
                    $vehicle = array_merge($vehicle, $vehicleTemp);

                    $vehicleCondition = $vehicleTemp['Condition'];
                    $vehicleMileage = $vehicleTemp['CurrentMileage'];
                    $vehicleDistance = $vehicleTemp['LocationDistance'];
                    $vehicleYear = DateTime::createFromFormat('Y-m-d', $vehicleTemp['RegistrationDate'])->format('Y');
                    $karfuScoreTabContent = (isset($vehicleTemp['SuitabilityScoreData'])) ? json_decode($vehicleTemp['SuitabilityScoreData'], true) : null;
                    $minMaxSuitabilityScores = $this->con->fetchAssoc(
                        'SELECT
                            MIN(SuitabilityScore) AS MinSuitabilityScore,
                            MAX(SuitabilityScore) AS MaxSuitabilityScore
                        FROM karfu_vehicle_temp WHERE SessionKey = ?',
                        [session_ID()]
                    );
                    $karfuScoreTabContent['lowestKarfuScore'] = (int) $minMaxSuitabilityScores['MinSuitabilityScore'];
                    $karfuScoreTabContent['highestKarfuScore'] = (int) $minMaxSuitabilityScores['MaxSuitabilityScore'];
                }
                
                $hrVehicleType = $this->karfuAttributeMapService->mapToKarfuAttribute($vehicle['VehicleType'], 'vehicleTableToHuman');

                // Get cost calculator class
                if ($hrVehicleType) {
                    try {
                        $this->costCalculator = $this->costCalculatorFactory->create($hrVehicleType, $mobilityChoice, $mobilityChoiceType);
                    } catch (Exception $e) {}
                } else {
                    $this->costCalculator = $this->costCalculatorFactory->create('', '', '');
                }

                if (
                    $vehicle['VehicleType'] === 'C'
                    && (
                        $mobilityChoiceType === CostCalculator::OWNERSHIP_OUTRIGHT
                        || $mobilityChoiceType === CostCalculator::OWNERSHIP_HP
                    )
                )
                {
                    // Get scraped vehicle
                    $carsForSale = $this->scrapedVehicleContentService->mapByFuelAndDerivative(
                        $vehicle['FuelType'],
                        $vehicle['Derivative']
                    );

                    // If there is a scraped vehicle record, set variables for mocking a used vehicle
                    if ($carsForSale) {
                        $vehicle['Price'] = $carsForSale[0]['price'];
                        $vehicleProvider = $carsForSale[0]['data_provider'];
                        $vehicleProviderAddress = explode(',', $carsForSale[0]['dealer_address']);
                        $vehicleLocation = $vehicleProviderAddress[count($vehicleProviderAddress) - 1];
                    }
                }

                // Get answers
                $answers = ($isFromShortlist) ? $shortlist->getAnswers() : $this->sessionAnswerService->getSessionAnswers(false, false);

                // Tell cost calculator is data is from snapshot or not
                $this->costCalculator->setIsSnapshotAnswers($isFromShortlist);

                if (count($answers) > 0) {
                    $ownershipPeriod = 0;
                    $ownershipPeriodMonths = 0;
                    $annualMileage = 0;

                    if ($isFromShortlist) {
                        // Get ownership period
                        if (array_key_exists('howLongTerm', $answers)) {
                            $ownershipPeriod = (int) $answers['howLongTerm'];
                            $ownershipPeriodMonths = $ownershipPeriod * 12;
                        }

                        // Get estimated mileage
                        if (array_key_exists('whatIsYourEstimatedMileage', $answers)) {
                            $annualMileage = (int) $answers['whatIsYourEstimatedMileage'];
                        }
                    } else {
                        $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                            [
                                'howLongTerm',
                                'whatIsYourEstimatedMileage',
                                'whatVehicleDoYouHave',
                                'whereAreYou'
                            ],
                            $answers
                        );

                        // Get ownership period
                        if (array_key_exists('howLongTerm', $tempAnswers)) {
                            $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
                            $ownershipPeriodMonths = $ownershipPeriod * 12;
                        }

                        // Get estimated mileage
                        if (array_key_exists('whatIsYourEstimatedMileage', $tempAnswers)) {
                            $annualMileage = (int) $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue();
                        }

                        if (array_key_exists('whatVehicleDoYouHave', $tempAnswers)) {
                            $isSellingVehicle = true;
                        }
                    }

                    // Calculate various data
                    $annualCarbon = $this->vehicleFormula->calcAnnualCarbonCost($annualMileage, $co2Rating);
                    $annualCarbonOffset = $this->vehicleFormula->calcAnnualCarbonOffsetCost($annualCarbon);
                    $annualNumTreesDestroyed = $this->vehicleFormula->calcAnnualNumberOfTreesDestroyed($annualMileage, $co2Rating);
                    $enviroImpact = round($annualNumTreesDestroyed * $ownershipPeriod);

                    // Calculate particle emissions
                    $particleEmissions = $this->vehicleFormula->calcParticleEmissions($co2Rating, $hrVehicleType, $vehicle['FuelType']);
                    $noxMileBased = $particleEmissions['mile']['NOx'];
                    $pm10MileBased = $particleEmissions['mile']['PM10'];
                    $pm25MileBased = $particleEmissions['mile']['PM2.5'];
                    $noxKilometreBased = $particleEmissions['kilometre']['NOx'];
                    $pm10KilometreBased = $particleEmissions['kilometre']['PM10'];
                    $pm25KilometreBased = $particleEmissions['kilometre']['PM2.5'];

                    // Build data for emissions tab content view
                    $emissionsTabContent = [
                        'carbon' => [
                            'title' => 'Carbon',
                            'value' => round($annualCarbon * $ownershipPeriod, 2)
                        ],
                        'carbonOffset' => [
                            'title' => 'Carbon Offset',
                            'value' => round($annualCarbonOffset * $ownershipPeriod, 2)
                        ],
                        'numTreesDestroyed' => [
                            'title' => 'Number of Trees Destroyed',
                            'value' => round($annualNumTreesDestroyed * $ownershipPeriod)
                        ],
                        'noxMileBased' => [
                            'title' => 'NOx',
                            'value' => round($noxMileBased)
                        ],
                        'pm10MileBased' => [
                            'title' => 'PM10',
                            'value' => round($pm10MileBased)
                        ],
                        'pm25MileBased' => [
                            'title' => 'PM2.5',
                            'value' => round($pm25MileBased)
                        ],
                        'noxKilometreBased' => [
                            'title' => 'NOx',
                            'value' => round($noxKilometreBased)
                        ],
                        'pm10KilometreBased' => [
                            'title' => 'PM10',
                            'value' => round($pm10KilometreBased)
                        ],
                        'pm25KilometreBased' => [
                            'title' => 'PM2.5',
                            'value' => round($pm25KilometreBased)
                        ],
                        'mileage' => [
                            'title' => 'Total Mileage',
                            'value' => $annualMileage * $ownershipPeriod
                        ],
                        'ownership' => [
                            'title' => 'Owenship Period',
                            'value' => $ownershipPeriod
                        ]
                    ];

                    // Get data for vehicle tab view
                    $vehicleTabContent = $this->vehicleAttributeMap->map($vehicle);

                    // Get cached values such as vehicle valuation
                    $cacheValues = ($isFromShortlist) ? [] : $this->costCalculatorCacheValues->get($answers);

                    // Calculate costs
                    $vehicleCosts = $this->costCalculator->calculateCosts($vehicle, $answers, $cacheValues);

                    // Get values from costs
                    $vehicleTotalCost = $this->costCalculator->getTotalCost($vehicleCosts, $answers);
                    $vehicleMonthlyCost = $this->costCalculator->getMonthlyCost($vehicleCosts, $answers);
                    $groupedVehicleCosts = $this->costCalculator->groupByCategoryAndFrequencyTitle($vehicleCosts);

                    // Get depreciation rate
                    $depreciationRate = $this->con->fetchAssoc('SELECT * FROM `depreciation_rate` WHERE `condition` = ? AND `karfu_group` IN (
                        SELECT karfu_group FROM insurance_price WHERE insurance_group = ?
                    )', ['Used', $vehicle['InsuranceGroup']]);
                    $initialRate = (float) $depreciationRate['initial'];
                    $tailRate = (float) $depreciationRate['tail'];
                    $totalLost = 0;

                    // Calc depreciation value
                    for ($i = 0; $i < $ownershipPeriod; $i++) {
                        // On first loop, use initial
                        if ($i === 0) {
                            $lost = ($initialRate / 100) * $price;
                            $retained = $price - $lost;
                            $totalLost += $lost;
                        } else {
                            $lost = ($tailRate / 100) * $retained;
                            $retained -= $lost;
                            $totalLost += $lost;
                        }
                    }
                    $gfmv = $retained;

                    $moneyIn = [
                        VehicleCost::CAT_NET_INCOME => 0
                    ];
                    $moneyOut = [
                        VehicleCost::CAT_NET_VEHICLE_PROVIDER => 0,
                        VehicleCost::CAT_NET_RUNNING => 0
                    ];

                    // Loop through vehicle costs and build array for the view
                    foreach ($groupedVehicleCosts as $gvcKey => $groupedVehicleCost) {
                        $catTotalCost = $this->costCalculator->getTotalCostByCategory($gvcKey, $vehicleCosts, $answers);
                        $catMonthlyCost = $this->costCalculator->getMonthlyCostByCategory($gvcKey, $vehicleCosts, $answers);
                        $displayCatTotalCost = ($catTotalCost < 0) ? $catTotalCost * -1 : $catTotalCost;
                        $displayCatMonthlyCost = ($catMonthlyCost < 0) ? $catMonthlyCost * -1 : $catMonthlyCost;

                        if ($gvcKey === VehicleCost::CAT_NET_POSITION) {
                            $netEquity = $catTotalCost;
                            $displayCatTotalCost = $catTotalCost;
                        } else {
                            if (array_key_exists($gvcKey, $moneyIn)) {
                                $moneyIn[$gvcKey] += $displayCatTotalCost;
                                $moneyInTotal += $displayCatTotalCost;
                            } elseif (array_key_exists($gvcKey, $moneyOut)) {
                                $moneyOut[$gvcKey] += $displayCatTotalCost;
                                $moneyOutTotal += $displayCatTotalCost;
                            }
                        }

                        $frequencies = [];
                        foreach ($groupedVehicleCost as $fKey => $frequency) {
                            $freqTotalCost = $this->costCalculator->getFrequencyTitleTotalCost($gvcKey, $fKey, $vehicleCosts);
                            $opts = [];
                            foreach ($frequency as $cost) {
                                if (!$cost->getIsHidden()) {
                                    if ($cost->getCost() !== null) {
                                        $displayCost = ($cost->getCost() < 0) ? $cost->getCost() * -1 : $cost->getCost();
                                        $opts[] = [
                                            $cost->getName(),
                                            '£' . number_format($displayCost, 2)
                                        ];
                                        if ($cost->getApr()) {
                                            $amountOfCredit += $cost->getCost();
                                            $opts[] = [
                                                'APR',
                                                $cost->getApr() . '%'
                                            ];
                                        }
                                        if ($cost->getFixedRate()) {
                                            $opts[] = [
                                                'Fixed rate of interest',
                                                $cost->getFixedRate() . '% fixed'
                                            ];
                                        }
                                    } else {
                                        $opts[] = [
                                            $cost->getName(),
                                            $cost->getStringValue()
                                        ];
                                    }
                                }
                            }

                            if (count($opts) > 0) {
                                $frequencies[] = [
                                    'title' => $fKey,
                                    'total' => '£' . number_format($freqTotalCost, 2),
                                    'opts' => $opts
                                ];
                            }
                        }

                        $finalVehicleCosts[] = [
                            'title' => $gvcKey,
                            'class' => $class,
                            'totalCost' => number_format($displayCatTotalCost, 2),
                            'monthlyCost' => number_format($displayCatMonthlyCost, 2),
                            'term' => $ownershipPeriodMonths,
                            'frequencies' => $frequencies
                        ];
                    }

                    $this->set('vehicle', $vehicle);
                    $this->set('vehicleYear', $vehicleYear);
                    $this->set('vehicleProvider', $vehicleProvider);
                    $this->set('vehicleLocation', $vehicleLocation);
                    $this->set('vehicleCondition', $vehicleCondition);
                    $this->set('vehicleMileage', $vehicleMileage);
                    $this->set('vehicleDistance', $vehicleDistance);
                    $this->set('isShortlisted', $isShortlisted);
                    $this->set('isFromShortlist', $isFromShortlist);
                    $this->set('vehicleCosts', $finalVehicleCosts);
                    $this->set('vehicleTotalCost', $vehicleTotalCost);
                    $this->set('vehicleMonthlyCost', $vehicleMonthlyCost);
                    $this->set('periodInMonths', $ownershipPeriodMonths);
                    $this->set('co2Rating', $co2Rating);
                    $this->set('enviroImpact', $enviroImpact);
                    $this->set('moneyOut', $moneyOut);
                    $this->set('moneyIn', $moneyIn);
                    $this->set('moneyOutTotal', $moneyOutTotal);
                    $this->set('moneyInTotal', $moneyInTotal);
                    $this->set('netEquity', $netEquity);
                    $this->set('emissionsTabContent', $emissionsTabContent);
                    $this->set('vehicleTabContent', $vehicleTabContent);
                    $this->set('karfuScoreTabContent', $karfuScoreTabContent);
                    $this->set('saveRedirectUrl', '/compare/your_results');
                    $this->set('isQuickSearch', $this->isQuickSearch());
                    $this->set('isSellingVehicle', $isSellingVehicle);
                    if($tempAnswers['whereAreYou'][0]) {
                        $this->set('uLocation', $tempAnswers['whereAreYou'][0]->getValue());
                    }
                } else {
                    $this->redirect('/');
                }
            } else {
                $this->redirect('/');
            }
        } else if ($slug && $id && is_numeric($id)) {
            // If there is no mobility type or sub type in the url, redirect to vehicle page
            $id = (int) $id;
            $vehicle = $this->vehicleService->readById($id);
            $hrVehicleType = $this->karfuAttributeMapService->mapToKarfuAttribute($vehicle['VehicleType'], 'vehicleTableToHuman');
            $vehicleTypeSlug = strtolower($hrVehicleType);

            $this->redirect('/vehicles/' . $vehicleTypeSlug . '/' . $slug . '?ID=' . $id);
        } else {
            $this->redirect('/vehicles');
        }
    }

    /**
     * Check if the current search type is 'Quick'
     * 
     * @return bool
     */
    private function isQuickSearch(): bool
    {
        return (isset($_SESSION['KARFU_user']['currentJourneyType']) && $_SESSION['KARFU_user']['currentJourneyType'] === 'Quick');
    }

}
