<?php

namespace Application\Controller\Api;

use Application\Helper\SnapshotAnswer;
use Application\Service\ApiCacheService;
use Application\Service\SessionAnswerService;
use Application\Service\ShortlistService;
use Application\Service\VehicleTempService;
use Application\Model\Shortlist as ShortlistModel;
use Concrete\Core\Session\SessionValidator;
use Core;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use User;

/**
 * API calls for shortlisting a vehicle, mobility type & sub type combo
 */
class Shortlist
{
    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var ShortlistService
     */
    private $shortlistService;

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
     * @var VehicleTempService
     */
    private $vehicleTempService;

    /**
     * @param SessionValidator $sessionValidator
     * @param ShortlistService $shortlistService
     * @param SessionAnswerService $sessionAnswerService
     * @param ApiCacheService $apiCacheService
     * @param SnapshotAnswer $snapshotAnswer
     * @param VehicleTempService $vehicleTempService
     */
    public function __construct(
        SessionValidator $sessionValidator,
        ShortlistService $shortlistService,
        SessionAnswerService $sessionAnswerService,
        ApiCacheService $apiCacheService,
        SnapshotAnswer $snapshotAnswer,
        VehicleTempService $vehicleTempService
    )
    {
        $this->sessionValidator = $sessionValidator;
        $this->shortlistService = $shortlistService;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->apiCacheService = $apiCacheService;
        $this->snapshotAnswer = $snapshotAnswer;
        $this->vehicleTempService = $vehicleTempService;
    }

    /**
     * Add to shortlist
     * 
     * @return JsonResponse
     */
    public function add()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = null;
        $mobilityChoice = null;
        $mobilityChoiceType = null;
        $snapshotAnswers = [];
        $user = new User();
        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;

        // Check session is valid & user logged in
        if ($session && $user->isLoggedIn()) {
            $sessionKey = $session->getId();
            $uId = (int) $user->getUserID();

            // Get post data
            if (isset($data['vehicleId']) && is_numeric($data['vehicleId'])) {
                $id = (int) $data['vehicleId'];
            }
            if (isset($data['mobilityChoice'])) {
                $mobilityChoice = $data['mobilityChoice'];
            }
            if (isset($data['mobilityChoiceType'])) {
                $mobilityChoiceType = $data['mobilityChoiceType'];
            }

            if ($id && $mobilityChoice && $mobilityChoiceType) {
                $answers = $this->sessionAnswerService->getSessionAnswers();

                if (count($answers) > 0) {
                    // Get snapshot of answers
                    $snapshotAnswers = $this->snapshotAnswer->takeSnapshot($answers);

                    // Get vehicle
                    $karfuVehicleTemp = $this->vehicleTempService->readBySessionKeyAndKvIdAndMobilityChoiceAndMobilityType(
                        $sessionKey,
                        $id,
                        $mobilityChoice,
                        $mobilityChoiceType
                    );

                    // Format data keys to camel case
                    $vehicleTempData = ($karfuVehicleTemp) ? $this->formatKarfuVehicleTempData($karfuVehicleTemp) : null;

                    // Create shortlist model
                    $shortlist = new ShortlistModel();
                    $shortlist->setUserId($uId)
                        ->setVehicleId($id)
                        ->setMobilityChoice($mobilityChoice)
                        ->setMobilityChoiceType($mobilityChoiceType)
                        ->setSavedDate(new DateTime('now'))
                        ->setAnswers($snapshotAnswers)
                        ->setVehicleTempData($vehicleTempData);

                    // Create shortlist record
                    $shortlist = $this->shortlistService->create($shortlist);

                    if ($shortlist) {
                        $response = ['success' => true];
                    } else {
                        $response = ['success' => false];
                    }
                } else {
                    $response = ['success' => false];
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
     * Format keys to camel case
     * 
     * @var array $vehicleTempData
     * 
     * @return array
     */
    private function formatKarfuVehicleTempData(array $vehicleTempData): array
    {
        $return = [];
        $use = [
            'MobilityChoice',
            'MobilityType',
            'TotalCost',
            'TotalMonthlyCost',
            'EnviroImpact',
            'NetPosition',
            'CostPerMile',
            'Condition',
            'CurrentMileage',
            'LocationDistance',
            'RegistrationDate'
        ];

        foreach ($vehicleTempData as $k => $v) {
            if ($v !== null && in_array($k, $use)) {
                $return[lcfirst($k)] = $v;
            }
        }

        return $return;
    }
}
