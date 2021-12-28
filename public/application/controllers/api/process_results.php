<?php

namespace Application\Controller\Api;

use Application\Helper\Budget;
use Application\Helper\CostCalculator\CostCalculator;
use Application\Helper\CostCalculator\CostCalculatorFactory;
use Application\Helper\Map;
use Application\Helper\MobilityTypeFilter;
use Application\Helper\QuestionFilter;
use Application\Helper\SnapshotAnswer;
use Application\Helper\VehicleFormula;
use Application\Model\JourneyUserSession;
use Application\Model\Shortlist;
use Application\Model\VehicleCost;
use Application\Service\ApiCacheService;
use Application\Service\JourneyUserSessionService;
use Application\Service\KarfuAttributeMapService;
use Application\Service\ScrapedVehicleContentService;
use Application\Service\SessionAnswerService;
use Application\Service\ShortlistService;
use Application\Service\VehicleService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Session\SessionValidator;
use Core;
use DateTime;
use Exception;
use Page;
use Symfony\Component\HttpFoundation\JsonResponse;
use User;

/**
 * API calls for processing the results
 */
class ProcessResults
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
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @var SnapshotAnswer
     */
    private $snapshotAnswer;

    /**
     * @var ShortlistService
     */
    private $shortlistService;

    /**
     * @var QuestionFilter
     */
    private $questionFilter;

    /**
     * @var VehicleService
     */
    private $vehicleService;

    /**
     * @var MobilityTypeFilter
     */
    private $mobilityTypeFilter;

    /**
     * @var Budget
     */
    private $budget;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var ScrapedVehicleContentService
     */
    private $scrapedVehicleContentService;

    /**
     * @var Map
     */
    private $distanceMapper;

    /**
     * @var CostCalculatorFactory
     */
    private $costCalculatorFactory;

    /**
     * @var VehicleFormula
     */
    private $vehicleFormula;

    /**
     * @var JourneyUserSessionService
     */
    private $journeyUserSessionService;

    /**
     * @param Connection $con
     * @param SessionValidator $sessionValidator
     * @param SessionAnswerService $sessionAnswerService
     * @param ApiCacheService $apiCacheService
     * @param SnapshotAnswer $snapshotAnswer
     * @param ShortlistService $shortlistService
     * @param QuestionFilter $questionFilter
     * @param VehicleService $vehicleService
     * @param MobilityTypeFilter $mobilityTypeFilter
     * @param Budget $budget
     * @param KarfuAttributeMapService $karfuAttributeMapService
     * @param ScrapedVehicleContentService $scrapedVehicleContentService
     * @param Map $distanceMapper
     * @param CostCalculatorFactory $costCalculatorFactory
     * @param VehicleFormula $vehicleFormula
     * @param JourneyUserSessionService $journeyUserSessionService
     */
    public function __construct(
        Connection $con,
        SessionValidator $sessionValidator,
        SessionAnswerService $sessionAnswerService,
        ApiCacheService $apiCacheService,
        SnapshotAnswer $snapshotAnswer,
        ShortlistService $shortlistService,
        QuestionFilter $questionFilter,
        VehicleService $vehicleService,
        MobilityTypeFilter $mobilityTypeFilter,
        Budget $budget,
        KarfuAttributeMapService $karfuAttributeMapService,
        ScrapedVehicleContentService $scrapedVehicleContentService,
        Map $distanceMapper,
        CostCalculatorFactory $costCalculatorFactory,
        VehicleFormula $vehicleFormula,
        JourneyUserSessionService $journeyUserSessionService
    )
    {
        $this->con = $con;
        $this->sessionValidator = $sessionValidator;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->apiCacheService = $apiCacheService;
        $this->snapshotAnswer = $snapshotAnswer;
        $this->shortlistService = $shortlistService;
        $this->questionFilter = $questionFilter;
        $this->vehicleService = $vehicleService;
        $this->mobilityTypeFilter = $mobilityTypeFilter;
        $this->budget = $budget;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $this->scrapedVehicleContentService = $scrapedVehicleContentService;
        $this->distanceMapper = $distanceMapper;
        $this->costCalculatorFactory = $costCalculatorFactory;
        $this->vehicleFormula = $vehicleFormula;
        $this->journeyUserSessionService = $journeyUserSessionService;
    }

    /**
     * Main function for process the results from a journey
     * 
     * @return JsonResponse
     */
    public function process()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $user = new User();
        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;

        // Check session is valid & user logged in
        if ($session && $user->isLoggedIn()) {
            $sessionKey = $session->getId();

            // Get all the question answers
            $answers = $this->sessionAnswerService->getSessionAnswers(false, false);

            if (count($answers) > 0) {
                $user = new User();
                $vehicleTypes = [];
                $mobilityTypes = [];
                $mobilityFilters = [];
                $vehiclesToInject = [];
                $distanceMemCache = [];

                // Shortlist current vehicle
                $this->shortlistCurrentVehicle($sessionKey, $answers);

                // Generate & get filters from the answers
                $this->questionFilter->generateFilters($answers);
                $filters = $this->questionFilter->getFilters();

                // Get vehicles by filters
                $vehicles = $this->vehicleService->readByQuestionFilter(
                    $filters,
                    [
                        'limit' => [
                            'offset' => 0,
                            'count' => 250
                        ]
                    ]
                );

                // Get mobility types
                $mobilityTypes = $this->mobilityTypeFilter->getMobilityTypes($answers);

                // Get vehicle types from cache
                $vehicleTypesCacheResult = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'karfu', 'vehicle-type');
                $vehicleTypesCache = ($vehicleTypesCacheResult) ? $vehicleTypesCacheResult->getData() : [];



                /** CHECK IF IS A MAIN JOURNEY SEARCH AND INJECT THE SHARING AND HAILING OPTIONS */
                if($_SESSION['KARFU_user']['currentJourneyGroup'] === 'full') {

                    /** START OF RIDE HAILING INJECT */

                    if (
                        in_array('CAR', $vehicleTypesCache)
                        && in_array(CostCalculator::RIDE_HAILING, $mobilityTypes)
                    )
                    {
                        $tempVehicles = $this->vehicleService->readByMobilityChoiceUses(CostCalculator::RIDE_HAILING);
                        if ($tempVehicles) {
                            $tempVehicles = array_map(function ($vehicle) {
                                $vehicle['mobilityChoice'] = [
                                    'name' => CostCalculator::RIDE_HAILING,
                                    'type' => CostCalculator::RIDE_HAILING_PAYG
                                ];
                                return $vehicle;
                            }, $tempVehicles);

                            $vehiclesToInject = array_merge($vehiclesToInject, $tempVehicles);
                            $mobilityFilters[] = CostCalculator::RIDE_HAILING;
                        }
                    }
                    /** END OF RIDE HAILING INJECT */

                    /** START OF RIDE SHARING INJECT */
                    if (
                        in_array('CAR', $vehicleTypesCache)
                        && in_array(CostCalculator::SHARING, $mobilityTypes)
                    )
                    {
                        $tempVehicles = $this->vehicleService->readByMobilityChoiceUses(CostCalculator::SHARING);
                        if ($tempVehicles) {
                            $tempVehicles = array_map(function ($vehicle) {
                                $vehicle['mobilityChoice'] = [
                                    'name' => CostCalculator::SHARING,
                                    'type' => CostCalculator::SHARING_PERSONAL_CAR
                                ];
                                return $vehicle;
                            }, $tempVehicles);

                            $vehiclesToInject = array_merge($vehiclesToInject, $tempVehicles);
                            $mobilityFilters[] = CostCalculator::SHARING;
                        }
                    }

                    /** END OF RIDE SHARING INJECT */

                }
                


                // Get the users budget & deposit
                $budget = $this->budget->getTotal($answers);
                $totalDeposit = $this->budget->getTotalDeposit($answers);
        
                if (count($vehicles) > 0) {
                    // Delete existing vehicle session results
                    $deleteTempVehicles = $this->con->executeQuery('DELETE FROM karfu_vehicle_temp WHERE SessionKey = ?', [$sessionKey]);

                    // Get specific answers required for processing results
                    $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(['howLongTerm', 'whatIsYourEstimatedMileage', 'whereAreYou'], $answers);
        
                    foreach ($vehicles as $vehicle) {
                        $vehicleTypeMap = $this->karfuAttributeMapService->mapToKarfuAttribute($vehicle['VehicleType']);
                        $wheres = [];
                        $bindings = [];
                        $wheres[] = 'active = ?';
                        $wheres[] = 'partner = ?';
                        $wheres[] = 'partner_type = ?';
                        $bindings[] = 1;
                        $bindings[] = $vehicle['ManName'];
                        $bindings[] = $vehicleTypeMap;
                        $where = implode(' AND ', $wheres);

                        // Get list of partner offerings by vehicle manufacturer
                        $partner = $this->con->fetchAssoc('SELECT * FROM btPartnerManager WHERE ' . $where, $bindings);
                        $offeringsDo = explode(',', $partner['partner_offerings']);
                        $offerings = $this->con->fetchAll(
                            'SELECT d.value, d.displayOrder
                            FROM atSelectOptions d
                            JOIN atSelectSettings v ON d.avSelectOptionListID = v.avSelectOptionListID
                            WHERE akID = (SELECT akID FROM AttributeKeys WHERE akHandle = ? LIMIT 1)',
                            ['partner_offerings']
                        );
        
                        // Loop through each offering
                        foreach ($offerings as $offering) {
                            $tmpVehicle = $vehicle;

                            // If partner has offering
                            if (in_array($offering['displayOrder'], $offeringsDo)) {

                                // Get mobility type & sub type in correct format
                                $map = $this->karfuAttributeMapService->mapToKarfuAttribute($offering['value'], 'offeringsToMobilityChoice');
                                if ($map) {
                                    $mapExplode = explode(',', $map);
        
                                    // If mobility type is in list of suitable mobility types
                                    if (in_array($mapExplode[0], $mobilityTypes)) {

                                        // Set some default values
                                        $mobilityChoice = ['name' => $mapExplode[0], 'type' => $mapExplode[1]];
                                        $ownershipPeriod = 0;
                                        $ownershipPeriodInMonths = 0;
                                        $estMileage = 0;
                                        $tmpVehicle['mobilityChoice'] = $mobilityChoice;
                                        $tmpVehicle['priceNew'] = $tmpVehicle['Price'];
                                        $tmpVehicle['partner'] = $partner;
                                        $condition = 'New';
                                        $currentMileage = 0;
                                        $locationDistance = rand(1, 10);
                                        $registrationDate = new DateTime();

                                        if (array_key_exists('howLongTerm', $tempAnswers)) {
                                            $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
                                            $ownershipPeriodInMonths = $ownershipPeriod * 12;
                                        }
                                        
                                        if (array_key_exists('whatIsYourEstimatedMileage', $tempAnswers)) {
                                            $estMileage = (int) $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue();
                                        }
        
                                        // If the current loop vehicle is of type 'Car'
                                        // & mobility type is buying, check if there are any
                                        // matches in the scraped vehicle table
                                        if (
                                            $tmpVehicle['VehicleType'] === 'C'
                                            && (
                                                $mobilityChoice['type'] === CostCalculator::OWNERSHIP_OUTRIGHT
                                                || $mobilityChoice['type'] === CostCalculator::OWNERSHIP_HP
                                            )
                                        )
                                        {
                                            // Get scraped vehicle data
                                            $carsForSale = $this->scrapedVehicleContentService->mapByFuelAndDerivative(
                                                $tmpVehicle['FuelType'],
                                                $tmpVehicle['Derivative']
                                            );

                                            // If record exists, set vehicle condition, price etc
                                            if ($carsForSale) {
                                                $tmpVehicle['Price'] = $carsForSale[0]['price'];
                                                $condition = 'Used';
                                                $currentMileage = $carsForSale[0]['mileage'];

                                                //
                                                $colour = $carsForSale[0]['colour'];


                                                $registrationDate = DateTime::createFromFormat('Y-m-d', $carsForSale[0]['registration_date']);
                                                $postcode = trim($this->getPostcodeFromAddress($carsForSale[0]['dealer_address']));
                                                if (array_key_exists($postcode, $distanceMemCache)) {
                                                    $locationDistance = $distanceMemCache[$postcode];
                                                } else {
                                                    if (array_key_exists('whereAreYou', $tempAnswers)) {
                                                        $distance = $this->distanceMapper->getDistance($tempAnswers['whereAreYou'][0]->getValue(), $postcode);
                                                        if (array_key_exists('miles', $distance) && is_numeric($distance['miles'])) {
                                                            $locationDistance = (float) $distance['miles'];
                                                            $distanceMemCache[$postcode] = $locationDistance;
                                                        } else {
                                                            $locationDistance = null;    
                                                        }
                                                    } else {
                                                        $locationDistance = null;
                                                    }
                                                }
                                            }
                                        }
        
                                        // Get human readable version of vehicle type
                                        $vehicleTypeHumanMap = $this->karfuAttributeMapService->mapToKarfuAttribute($vehicle['VehicleType'], 'vehicleTableToHuman');
                                        if ($vehicleTypeHumanMap) {
                                            $vehicleTypes[] = $vehicleTypeHumanMap;

                                            // Get the correct cost calculator class to use
                                            try {
                                                $costCalculator = $this->costCalculatorFactory->create($vehicleTypeHumanMap, $mobilityChoice['name'], $mobilityChoice['type']);
                                            } catch (Exception $e) {}

                                            // If the mobility type is Ownership Outright, we need to set the upfront cost
                                            if ($mobilityChoice['type'] === CostCalculator::OWNERSHIP_OUTRIGHT) {
                                                $upfrontCost = (int) $tmpVehicle['Price'];
                                            }

                                            // Calculate the costs
                                            $vehicleCosts = $costCalculator->calculateCosts($tmpVehicle, $answers);

                                            // Get various costs
                                            $upfrontCost = $costCalculator->getTotalUpfrontCost($vehicleCosts, $answers);
                                            $tmpVehicle['totalCost'] = $costCalculator->getTotalCost($vehicleCosts, $answers);
                                            $tmpVehicle['monthlyCost'] = $costCalculator->getMonthlyCost($vehicleCosts);
                                            $netPosition = $costCalculator->getTotalCostByCategory(VehicleCost::CAT_NET_POSITION, $vehicleCosts, $answers);
                                            $netSpending = $costCalculator->getFrequencyTitleTotalCost(VehicleCost::CAT_NET_POSITION, VehicleCost::FREQUENCY_TITLE_NET_SPEND, $vehicleCosts);
                                            $costPerMile = $costCalculator->getFrequencyTitleTotalCost(VehicleCost::CAT_NET_POSITION, VehicleCost::FREQUENCY_TITLE_PRICE_PER_MILE, $vehicleCosts);
                                            $enviroImpact = $this->vehicleFormula->calcAnnualNumberOfTreesDestroyed($estMileage, (int) $tmpVehicle['CO2GKM']) * $ownershipPeriod;
                                            $totalCo2 = $this->vehicleFormula->calcAnnualCarbonCost($estMileage, (int) $tmpVehicle['CO2GKM']) * $ownershipPeriod;
            
                                            // If user can afford the vehicle upfornt & ongoing costs, save the entry
                                            if ($totalDeposit >= $upfrontCost && $budget >= $tmpVehicle['totalCost']) {
                                                try {
                                                    // Save entry in temp table
                                                    $this->con->executeQuery('INSERT INTO karfu_vehicle_temp (
                                                            KV_ID,
                                                            SessionKey,
                                                            MobilityChoice,
                                                            MobilityType,
                                                            TotalCost,
                                                            TotalMonthlyCost,
                                                            EnviroImpact,
                                                            TotalCO2,
                                                            NetPosition,
                                                            CostPerMile,
                                                            `Condition`,
                                                            CurrentMileage,
                                                            LocationDistance,
                                                            RegistrationDate,
                                                            NetSpending,
                                                            Colour
                                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                                                        [
                                                            $tmpVehicle['ID'],
                                                            $sessionKey,
                                                            $tmpVehicle['mobilityChoice']['name'],
                                                            $tmpVehicle['mobilityChoice']['type'],
                                                            $tmpVehicle['totalCost'],
                                                            $tmpVehicle['monthlyCost'],
                                                            $enviroImpact,
                                                            $totalCo2,
                                                            $netPosition,
                                                            $costPerMile,
                                                            $condition,
                                                            $currentMileage,
                                                            $locationDistance,
                                                            $registrationDate->format('Y-m-d'),
                                                            $netSpending,
                                                            $colour
                                                        ]
                                                    );
                                                } catch(Exception $e) {}
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Loop through vehicles to inject
                    foreach ($vehiclesToInject as $vehicleToInject) {
                        $tmpVehicle = $vehicleToInject;
                        $tmpVehicle['priceNew'] = $tmpVehicle['Price'];
                        $condition = 'Used';
                        $currentMileage = null;
                        $locationDistance = null;
                        $registrationDate = new DateTime('NOW');

                        // Get human readable version of vehicle type
                        $vehicleTypeHumanMap = $this->karfuAttributeMapService->mapToKarfuAttribute($tmpVehicle['VehicleType'], 'vehicleTableToHuman');
                        if ($vehicleTypeHumanMap) {
                            $vehicleTypes[] = $vehicleTypeHumanMap;

                            // Get the correct cost calculator class to use
                            try {
                                $costCalculator = $this->costCalculatorFactory->create($vehicleTypeHumanMap, $tmpVehicle['mobilityChoice']['name'], $tmpVehicle['mobilityChoice']['type']);
                            } catch (Exception $e) {}
                            $ownershipPeriod = 0;
                            $ownershipPeriodInMonths = 0;
                            $estMileage = 0;

                            if (array_key_exists('howLongTerm', $tempAnswers)) {
                                $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
                                $ownershipPeriodInMonths = $ownershipPeriod * 12;
                            }
                            
                            if (array_key_exists('whatIsYourEstimatedMileage', $tempAnswers)) {
                                $estMileage = (int) $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue();
                            }

                            // Calculate the costs
                            $vehicleCosts = $costCalculator->calculateCosts($tmpVehicle, $answers);

                            // Get various costs
                            $upfrontCost = $costCalculator->getTotalUpfrontCost($vehicleCosts, $answers);
                            $tmpVehicle['totalCost'] = $costCalculator->getTotalCost($vehicleCosts, $answers);
                            $tmpVehicle['monthlyCost'] = $costCalculator->getMonthlyCost($vehicleCosts);
                            $netPosition = $costCalculator->getTotalCostByCategory(VehicleCost::CAT_NET_POSITION, $vehicleCosts, $answers);
                            $netSpending = $costCalculator->getFrequencyTitleTotalCost(VehicleCost::CAT_NET_POSITION, VehicleCost::FREQUENCY_TITLE_NET_SPEND, $vehicleCosts);
                            $costPerMile = $costCalculator->getFrequencyTitleTotalCost(VehicleCost::CAT_NET_POSITION, VehicleCost::FREQUENCY_TITLE_PRICE_PER_MILE, $vehicleCosts);
                            $enviroImpact = $this->vehicleFormula->calcAnnualNumberOfTreesDestroyed($estMileage, $tmpVehicle['CO2GKM']) * $ownershipPeriod;
                            $totalCo2 = $this->vehicleFormula->calcAnnualCarbonCost($estMileage, (int) $tmpVehicle['CO2GKM']) * $ownershipPeriod;

                            // If user can afford the vehicle upfornt & ongoing costs, save the entry
                            if ($totalDeposit >= $upfrontCost && $budget >= $tmpVehicle['totalCost']) {
                                try {
                                    // Save entry in temp table
                                    $this->con->executeQuery('INSERT INTO karfu_vehicle_temp (
                                            KV_ID,
                                            SessionKey,
                                            MobilityChoice,
                                            MobilityType,
                                            TotalCost,
                                            TotalMonthlyCost,
                                            EnviroImpact,
                                            TotalCO2,
                                            NetPosition,
                                            CostPerMile,
                                            `Condition`,
                                            CurrentMileage,
                                            LocationDistance,
                                            RegistrationDate,
                                            NetSpending
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                                        [
                                            $tmpVehicle['ID'],
                                            $sessionKey,
                                            $tmpVehicle['mobilityChoice']['name'],
                                            $tmpVehicle['mobilityChoice']['type'],
                                            $tmpVehicle['totalCost'],
                                            $tmpVehicle['monthlyCost'],
                                            $enviroImpact,
                                            $totalCo2,
                                            $netPosition,
                                            $costPerMile,
                                            $condition,
                                            $currentMileage,
                                            $locationDistance,
                                            $registrationDate->format('Y-m-d'),
                                            $netSpending
                                        ]
                                    );
                                } catch(Exception $e) {}
                            }
                        }
                    }
            }

                if (isset($_SESSION['KARFU_user']['currentJourneyType'])) {
                    // Get correct url redirect for save search
                    switch ($_SESSION['KARFU_user']['currentJourneyType']) {
                        case 'Quick':
                            $sessionStartUrl = '/compare/quick-search';
                            break;
                        case 'Standard':
                        default:
                            $sessionStartUrl = '/compare/final_summary';
                    }

                    // Save the journey as a recent search
                    $this->saveAsRecentJourney($user, $sessionKey, array_unique($vehicleTypes), $mobilityTypes, $sessionStartUrl);
                }

                $response = ['success' => true];
            } else {
                $response = ['success' => false];
            }
        } else {
            $response = ['success' => false];
        }
        return new JsonResponse($response);
    }

    /**
     * Shortlist current vehicle (vehicle being sold)
     * 
     * @param string $sessionKey
     * @param array $answers
     * 
     * @return void
     */
    private function shortlistCurrentVehicle(string $sessionKey, array $answers)
    {
        $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles([
            'whatVehicleDoYouHave',
            'yourVehicleValuation',
        ], $answers);

        if (array_key_exists('whatVehicleDoYouHave', $tempAnswers) && array_key_exists('yourVehicleValuation', $tempAnswers)) {

            // Check cache exists
            $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');

            if ($apiCache) {
                $carData = $apiCache->getData();
                $user = new User();
                $uId = (int) $user->getUserID();
                $snapshotAnswers = $this->snapshotAnswer->takeSnapshot($answers);
                if (isset($carData['vehicleDetails']['vrm'])) {
                    $vrm = $carData['vehicleDetails']['vrm'];
                    $shortlist = $this->shortlistService->readByUserIdAndVrm($uId, $vrm);

                    if (!$shortlist) {
                        $shortlist = new Shortlist();
                        $shortlist->setUserId($uId)
                            ->setMobilityChoice(CostCalculator::OWNERSHIP)
                            ->setMobilityChoiceType(CostCalculator::OWNERSHIP_OUTRIGHT)
                            ->setSavedDate(new DateTime('now'))
                            ->setAnswers($snapshotAnswers)
                            ->setVrm($vrm)
                            ->setApiCache($apiCache);
                        $shortlist = $this->shortlistService->create($shortlist);
                    }
                }
            }
        }
    }

    /**
     * Get the postcode from the address string
     * 
     * @param string $address
     * 
     * @return string
     */
    private function getPostcodeFromAddress(string $address): string
    {
        $vehicleProviderAddress = explode(',', $address);
        $postcode = $vehicleProviderAddress[count($vehicleProviderAddress) - 1];
        return $postcode;
    }

    /**
     * Save journey as recent journey
     * 
     * @param User $user
     * @param string $sessionKey
     * @param array $vehicleTypes
     * @param array $mobilityTypes
     * @param string|null $sessionStartUrl
     * 
     * @return void
     */
    private function saveAsRecentJourney(User $user, string $sessionKey, array $vehicleTypes, array $mobilityTypes, string $sessionStartUrl)
    {
        $uId = (int) $user->getUserID();
        $dateTime = new DateTime('NOW');
        $progress = (isset($_SESSION['KARFU_user']['completed'])) ? $_SESSION['KARFU_user']['completed'] : [0];
        $journeyType = (isset($_SESSION['KARFU_user']['currentJourneyType'])) ? $_SESSION['KARFU_user']['currentJourneyType'] : null;
        $journeyGroup = (isset($_SESSION['KARFU_user']['currentJourneyGroup'])) ? $_SESSION['KARFU_user']['currentJourneyGroup'] : null;
        $vehicleTypes = array_unique($vehicleTypes, SORT_STRING);
        $description = [
            [
                'Vehicle Types',
                ucwords(strtolower(implode(', ', $vehicleTypes)))
            ],
            [
                'Mobility Choices',
                ucwords(strtolower(implode(', ', $mobilityTypes)))
            ]
        ];

        // Check if record already exists
        $journey = $this->journeyUserSessionService->readByUserIdAndSessionKey($uId, $sessionKey);
        if ($journey) {
            $journey->setLastUpdated($dateTime)
                ->setSessionStartUrl($sessionStartUrl)
                ->setProgress($progress)
                ->setDescription($description);

            // Update the record
            $journey = $this->journeyUserSessionService->update($journey);
        } else {
            $journey = new JourneyUserSession();
            $journey->setCreated($dateTime)
                ->setLastUpdated($dateTime)
                ->setSessionKey($sessionKey)
                ->setSessionStartUrl($sessionStartUrl)
                ->setUserId($uId)
                ->setSaved(false)
                ->setProgress($progress)
                ->setDescription($description)
                ->setJourneyType($journeyType)
                ->setJourneyGroup($journeyGroup);

            // Create new record
            $journey = $this->journeyUserSessionService->create($journey);
        }
    }
}
