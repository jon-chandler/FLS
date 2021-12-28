<?php

namespace Application\Controller\Api;

use Application\Model\JourneyUserSession;
use Application\Service\JourneyUserSessionService;
use Application\Service\KarfuApiService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Session\SessionValidator;
use Core;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use User;

/**
 * API calls for saving searches
 */
class SaveSearch
{
    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var JourneyUserSessionService
     */
    private $journeyUserSessionService;

    /**
     * @var KarfuApiService
     */
    private $karfuApiService;

    /**
     * @param SessionValidator $sessionValidator
     * @param SessionAnswerService $sessionAnswerService
     * @param JourneyUserSessionService $journeyUserSessionService
     * @param KarfuApiService $karfuApiService
     */
    public function __construct(
        SessionValidator $sessionValidator,
        SessionAnswerService $sessionAnswerService,
        JourneyUserSessionService $journeyUserSessionService,
        KarfuApiService $karfuApiService
    )
    {
        $this->sessionValidator = $sessionValidator;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->journeyUserSessionService = $journeyUserSessionService;
        $this->karfuApiService = $karfuApiService;
    }

    /**
     * Save a search
     * 
     * @return JsonResponse
     */
    public function save()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $user = new User();
        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;

        // Check session is valid & user logged in
        if ($session && $user->isLoggedIn()) {
            $sessionKey = $session->getId();
            $uId = (int) $user->getUserID();
            $dateTime = new DateTime('NOW');
            $label = null;
            $sessionStartUrl = null;
            $description = [];

            // Get label from POST data
            if (isset($data['label'])) {
                $label = $data['label'];
            }

            // Get correct url redirect for save search
            if (isset($_SESSION['KARFU_user']['currentJourneyType'])) {
                switch ($_SESSION['KARFU_user']['currentJourneyType']) {
                    case 'Quick':
                        $sessionStartUrl = '/compare/quick-search';
                        break;
                    case 'Standard':
                    default:
                        $sessionStartUrl = '/compare/final_summary';
                }
            }

            // Get vehicle types & mobility types
            $vehicleTypes = $this->karfuApiService->getVehicleTypes(false);
            $mobilityTypes = $this->karfuApiService->getMobilityTypes();

            // Build description
            if ($vehicleTypes) {
                $description[] = [
                    'Vehicle Types',
                    ucwords(strtolower(implode(', ', $vehicleTypes)))
                ];
            }
            if ($mobilityTypes) {
                $description[] = [
                    'Mobility Choices',
                    ucwords(strtolower(implode(', ', $mobilityTypes)))
                ];
            }

            // Check if record already exists
            $journey = $this->journeyUserSessionService->readByUserIdAndSessionKey($uId, $sessionKey);
            if ($journey && $sessionStartUrl) {

                // If current existing label is null, set label
                if ($journey->getLabel() === null) {
                    $journey->setLabel($label);
                }

                // If current existing description is empty, set description
                if (count($description) > 0) {
                    $journey->setDescription($description);
                }
    
                $journey->setLastUpdated($dateTime)
                    ->setSessionStartUrl($sessionStartUrl)
                    ->setSaved(true)
                    ->setProgress($_SESSION['KARFU_user']['completed']);
    
                // Update the record
                $journey = $this->journeyUserSessionService->update($journey);

                $response = ($journey) ? ['success' => true] : ['success' => false];
            } else {
                $journey = new JourneyUserSession();
                $journey->setCreated($dateTime)
                    ->setLastUpdated($dateTime)
                    ->setSessionKey($sessionKey)
                    ->setSessionStartUrl($sessionStartUrl)
                    ->setUserId($uId)
                    ->setLabel($label)
                    ->setSaved(true)
                    ->setProgress($_SESSION['KARFU_user']['completed'])
                    ->setDescription($description);

                    if (isset($_SESSION['KARFU_user']['currentJourneyType'])) {
                        $journey->setJourneyType($_SESSION['KARFU_user']['currentJourneyType']);
                    }

                    if (isset($_SESSION['KARFU_user']['currentJourneyGroup'])) {
                        $journey->setJourneyGroup($_SESSION['KARFU_user']['currentJourneyGroup']);
                    }
    
                // Create new record
                $journey = $this->journeyUserSessionService->create($journey);

                $response = ($journey) ? ['success' => true] : ['success' => false];
            }
        } else {
            $response = ['success' => false];
        }
        return new JsonResponse($response);
    }
}
