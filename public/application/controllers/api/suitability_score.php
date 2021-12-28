<?php

namespace Application\Controller\Api;

use Application\Factory\VehicleFactory;
use Application\Helper\CostCalculator\CostCalculator;
use Application\Helper\CostCalculator\CostCalculatorFactory;
use Application\Service\ApiCacheService;
use Application\Service\KarfuAttributeMapService;
use Application\Service\ScoreItemService;
use Application\Service\SessionAnswerService;
use Application\Service\VehicleService;
use Application\Service\VehicleTempService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Session\SessionValidator;
use Core;
use Symfony\Component\HttpFoundation\JsonResponse;
use User;

/**
 * API calls for suitability score
 */
class SuitabilityScore
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
     * @var ScoreItemService
     */
    private $scoreItemService;

    /**
     * @var VehicleTempService
     */
    private $vehicleTempService;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @var VehicleService
     */
    private $vehicleService;

    /**
     * @var VehicleFactory
     */
    private $vehicleFactory;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var CostCalculatorFactory
     */
    private $costCalculatorFactory;

    /**
     * @param Connection $con
     * @param SessionValidator $sessionValidator
     * @param SessionAnswerService $sessionAnswerService
     * @param ScoreItemService $scoreItemService
     * @param VehicleTempService $vehicleTempService
     * @param ApiCacheService $apiCacheService
     * @param VehicleService $vehicleService
     * @param VehicleFactory $vehicleFactory
     * @param KarfuAttributeMapService $karfuAttributeMapService
     * @param CostCalculatorFactory $costCalculatorFactory
     */
    public function __construct(
        Connection $con,
        SessionValidator $sessionValidator,
        SessionAnswerService $sessionAnswerService,
        ScoreItemService $scoreItemService,
        VehicleTempService $vehicleTempService,
        ApiCacheService $apiCacheService,
        VehicleService $vehicleService,
        VehicleFactory $vehicleFactory,
        KarfuAttributeMapService $karfuAttributeMapService,
        CostCalculatorFactory $costCalculatorFactory
    )
    {
        $this->con = $con;
        $this->sessionValidator = $sessionValidator;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->scoreItemService = $scoreItemService;
        $this->vehicleTempService = $vehicleTempService;
        $this->apiCacheService = $apiCacheService;
        $this->vehicleService = $vehicleService;
        $this->vehicleFactory = $vehicleFactory;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $this->costCalculatorFactory = $costCalculatorFactory;
    }

    /**
     * Main function for building the suitability score
     * 
     * @return JsonResponse
     */
    public function build()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $user = new User();
        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;

        // Check session is valid & user logged in
        if ($session && $user->isLoggedIn()) {
            $sessionKey = $session->getId();

            // Get count of vehicles already scored
            $scoredCountRes = $this->con->fetchAssoc('SELECT COUNT(KV_ID) AS ScoredCount FROM karfu_vehicle_temp WHERE SessionKey = ? AND SuitabilityScore IS NOT NULL', [$sessionKey]);

            if (is_array($scoredCountRes) && isset($scoredCountRes['ScoredCount'])) {
                $scoredCount = (int) $scoredCountRes['ScoredCount'];

                if ($scoredCount === 0) {
                    // Get vehicles
                    $vehicles = $this->vehicleTempService->readBySessionKey($sessionKey);
    
                    if ($vehicles) {
                        $returnScores = [];
                        $ownedVehicle = null;
                        $ovCostCalculator = null;
                        $ovCosts = null;
                        $answers = $this->sessionAnswerService->getSessionAnswers();

                        // Get min, max and average values
                        $minMaxAvgs = $this->con->fetchAssoc(
                            'SELECT
                                MIN(TotalCost) AS MinTotalCost,
                                MIN(EnviroImpact) AS MinEnviroImpact,
                                MIN(TotalCO2) AS MinTotalCO2,
                                MIN(LocationDistance) AS MinLocationDistance,
                                AVG(TotalCost) AS AvgTotalCost,
                                AVG(EnviroImpact) AS AvgEnviroImpact,
                                AVG(TotalCO2) AS AvgTotalCO2,
                                AVG(LocationDistance) AS AvgLocationDistance,
                                MAX(TotalCost) AS MaxTotalCost,
                                MAX(EnviroImpact) AS MaxEnviroImpact,
                                MAX(TotalCO2) AS MaxTotalCO2,
                                MAX(LocationDistance) AS MaxLocationDistance
                            FROM karfu_vehicle_temp WHERE SessionKey = ?',
                            [$sessionKey]
                        );

                        $sum = ($minMaxAvgs['AvgTotalCost'] - $minMaxAvgs['MinTotalCost']) / 4;
                        $totalCostQuartiles = [
                            'TotalCostQuartile1' => $minMaxAvgs['MinTotalCost'] + ($sum * 4),
                            'TotalCostQuartile2' => $minMaxAvgs['MinTotalCost'] + ($sum * 3),
                            'TotalCostQuartile3' => $minMaxAvgs['MinTotalCost'] + ($sum * 2),
                            'TotalCostQuartile4' => $minMaxAvgs['MinTotalCost'] + $sum
                        ];

                        $sum = ($minMaxAvgs['AvgTotalCO2'] - $minMaxAvgs['MinTotalCO2']) / 4;
                        $totalCo2Quartiles = [
                            'TotalCO2Quartile1' => $minMaxAvgs['MinTotalCO2'] + ($sum * 4),
                            'TotalCO2Quartile2' => $minMaxAvgs['MinTotalCO2'] + ($sum * 3),
                            'TotalCO2Quartile3' => $minMaxAvgs['MinTotalCO2'] + ($sum * 2),
                            'TotalCO2Quartile4' => $minMaxAvgs['MinTotalCO2'] + $sum
                        ];

                        $sum = ($minMaxAvgs['AvgLocationDistance'] - $minMaxAvgs['MinLocationDistance']) / 4;
                        $locationQuartiles = [
                            'LocationDistanceQuartile1' => $minMaxAvgs['MinLocationDistance'] + ($sum * 4),
                            'LocationDistanceQuartile2' => $minMaxAvgs['MinLocationDistance'] + ($sum * 3),
                            'LocationDistanceQuartile3' => $minMaxAvgs['MinLocationDistance'] + ($sum * 2),
                            'LocationDistanceQuartile4' => $minMaxAvgs['MinLocationDistance'] + $sum
                        ];

                        $quartiles = array_merge($totalCostQuartiles, $totalCo2Quartiles, $locationQuartiles);

                        // Get vehicle being sold from api cache
                        $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');

                        // If there is a vehicle being sold, get vehicle data
                        if ($apiCache) {
                            // Get the cache data
                            $carData = $apiCache->getData();

                            // Get further vehicle data using the cap id
                            $karfuVehicle = $this->vehicleService->readByCapId($carData['derivativeDetails']['capId']);

                            if ($karfuVehicle) {
                                // Merge data together
                                $ownedVehicle = $this->vehicleFactory->createFromKarfuVehicleAndCapData($karfuVehicle, $carData);

                                if ($ownedVehicle) {
                                    // Get cost calculator & costs
                                    $map = $this->karfuAttributeMapService->mapToKarfuAttribute($ownedVehicle['VehicleType'], 'vehicleTableToHuman');
                                    $ovCostCalculator = $this->costCalculatorFactory->create($map, CostCalculator::OWNERSHIP, CostCalculator::OWNERSHIP_OUTRIGHT);
                                    $ovCosts = $ovCostCalculator->calculateCosts($ownedVehicle, $answers);
                                }
                            }
                        }
    
                        // Loop through each vehicle & build a score
                        foreach ($vehicles as $vehicle) {
                            // Build suitability score
                            $karfuScoreTabContent = $this->scoreItemService->build(
                                $sessionKey,
                                $answers,
                                $vehicle,
                                $vehicle['MobilityChoice'],
                                $vehicle['MobilityType'],
                                $ownedVehicle,
                                $ovCostCalculator,
                                $ovCosts,
                                $minMaxAvgs,
                                $quartiles
                            );
                            $suitabilityScore = $karfuScoreTabContent['karfuScore'];

                            // json encode the data
                            $suitabilityScoreData = json_encode($karfuScoreTabContent);

                            // Update temp record with the new score & data
                            $this->con->executeQuery(
                                'UPDATE karfu_vehicle_temp
                                SET
                                    SuitabilityScore = ?,
                                    SuitabilityScoreData = ?
                                WHERE 
                                    SessionKey = ?
                                    AND KV_ID = ?
                                    AND MobilityChoice = ?
                                    AND MobilityType = ?',
                                [
                                    $suitabilityScore,
                                    $suitabilityScoreData,
                                    $sessionKey,
                                    (int) $vehicle['ID'],
                                    $vehicle['MobilityChoice'],
                                    $vehicle['MobilityType']
                                ]
                            );

                            // Build a list of score to return to update the view
                            if ($this->findMatch((int) $vehicle['ID'], $vehicle['MobilityChoice'], $vehicle['MobilityType'], $data) === true) {
                                $returnScores[] = [
                                    (int) $vehicle['ID'],
                                    $vehicle['MobilityChoice'],
                                    $vehicle['MobilityType'],
                                    $karfuScoreTabContent['karfuScore']
                                ];
                            }
                        }
    
                        $response = ['success' => true, 'status' => 'COMPLETE', 'scores' => $returnScores];
                    } else {
                        $response = ['success' => false];
                    }
                } else {
                    // Get unscored count
                    $unscoredCount = $this->con->fetchAssoc('SELECT COUNT(KV_ID) AS UnscoredCount FROM karfu_vehicle_temp WHERE SessionKey = ? AND SuitabilityScore IS NULL', [$sessionKey]);

                    if (is_array($unscoredCount) && isset($unscoredCount['UnscoredCount'])) {
                        if ($unscoredCount['UnscoredCount'] == 0) {
                            $response = ['success' => true, 'status' => 'COMPLETE'];
                        } else {
                            $response = ['success' => true, 'status' => 'INCOMPLETE'];
                        }
                    } else {
                        $response = ['success' => false];
                    }
                }
            } else {
                $response = ['success' => false];
            }
        } else {
            $response = ['success' => false];
        }
        return new JsonResponse($response);
    }

    /**
     * Find a vehicle match in the data
     * 
     * @var int $vehicleId
     * @var string $mobilityChoice
     * @var string $mobilityChoiceType
     * @var array $data
     * 
     * @return bool
     */
    private function findMatch(int $vehicleId, string $mobilityChoice, string $mobilityChoiceType, array $data): bool
    {
        foreach ($data as $d) {
            if (
                $d[0] === $vehicleId
                && $d[1] === $mobilityChoice
                && $d[2] === $mobilityChoiceType
            ) {
                return true;
            }
        }
        return false;
    }
}
