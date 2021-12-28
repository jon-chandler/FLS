<?php

namespace Application\Controller\PageType;

use Application\Model\ApiCache;
use Application\Helper\QuestionJourney;
use Application\Service\ApiCacheService;
use Application\Service\KarfuApiService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Page\Controller\PageTypeController;
use Concrete\Core\Page\Page;
use Core;

class SinglePageJourney extends PageTypeController
{
    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var QuestionJourney
     */
    private $questionJourney;

    /**
     * @var KarfuApiService
     */
    private $karfuApiService;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @param $obj
     * @param SessionAnswerService $sessionAnswerService
     * @param QuestionJourney $questionJourney
     * @param KarfuApiService $karfuApiService
     * @param ApiCacheService $apiCacheService
     */
    public function __construct(
        $obj = null,
        SessionAnswerService $sessionAnswerService,
        QuestionJourney $questionJourney,
        KarfuApiService $karfuApiService,
        ApiCacheService $apiCacheService
    )
    {
        parent::__construct($obj);
        $this->sessionAnswerService = $sessionAnswerService;
        $this->questionJourney = $questionJourney;
        $this->karfuApiService = $karfuApiService;
        $this->apiCacheService = $apiCacheService;
    }

    /**
     * Concrete5 on_start hook
     */
    public function on_start()
    {
        $request = $this->getRequest();
        $journey = $this->getJourney();
        $questions = $this->getQuestions();
        $answers = $this->sessionAnswerService->getSessionAnswers(false, true, $journey);
        $formData = $this->getFormValuesFromAnswers($answers);

        if ($request->isMethod('POST') && count($questions) > 0) {
            // Get value from post data
            $formData = $this->getFormValuesFromPostData($questions);
            $content = $request->getContent();
            $data = [];
            parse_str($content, $data);

            // Match question order to data
            $tempQuestions = [];
            foreach ($data['Options'] as $k => $v) {
                $tempQuestions[] = $this->getQuestionByHandle($k, $questions);
            }
            $questions = $tempQuestions;

            // Validate answers
            if ($this->validate($questions, $data)) {
                // Map data to correct array format
                $data = array_map(function ($data) {
                    $newData = [];
                    foreach ($data as $d) {
                        $newData[] = $d;
                    }
    
                    return array_map(function ($data) {
                        return ['Options' => $data];
                    }, $newData);
                }, $data);
    
                $data = $data['Options'];

                // If there is an active session, destroy & create a new one
                if (session_status() === PHP_SESSION_ACTIVE) {
                    $configStore = Core::make('config');
                    $sessionCookieKey = $configStore->get('concrete.session.name');
                    $oldSession = $_SESSION;
        
                    // Close current session
                    session_destroy();
                    unset($_COOKIE[$sessionCookieKey]);
        
                    // Start session and copy old global session values
                    session_start();
                    $_SESSION = $oldSession;
                    $_SESSION['KARFU_user']['currentJourneyType'] = (string) $journey->getJourneyType();
                    $_SESSION['KARFU_user']['currentJourneyGroup'] = $journey->getJourneyGroup();
                    $_SESSION['KARFU_user']['completed'] = [0];
                }
    
                $this->questionJourney->writeMultipleQuestionAnswers($questions, $data);
                $this->cacheVehicleTypes();
                $this->cacheMobilityTypes();
                $this->redirect(Page::getCurrentPage()->getCollectionPath() . '/summary');
            } else {
                $this->set('error', 'e6');
            }
        }

        $this->set('formData', $formData);
    }

    /**
     * Concrete5 view hook
     */
    public function view()
    {
        $questions = $this->getQuestions();

        if (count($questions) > 0) {
            $array = [];
            foreach ($questions as $question) {
                $array[$question->getQuestionDataHandle()] = $question;
            }

            $this->set('questions', $array);
        }
    }

    /**
     * Get journey
     * 
     * @return Entity|null
     */
    private function getJourney()
    {
        $journeyPageAttr = $this->c->getAttribute('journey_page_journey');

        if ($journeyPageAttr) {
            return $journeyPageAttr->getSelectedEntries()->first();
        }

        return null;
    }

    /**
     * Get questions
     * 
     * @return array
     */
    private function getQuestions()
    {
        $journey = $this->getJourney();

        if ($journey) {
            return $this->questionJourney->getAllQuestionsByJourney($journey);
        }

        return [];
    }

    /**
     * Get question by quesiton data handle
     * 
     * @var mixed $handle
     * @var mixed $questions
     * 
     * @return Entry|null
     */
    private function getQuestionByHandle($handle, $questions)
    {
        foreach ($questions as $question) {
            if ($question->getQuestionDataHandle() === $handle) {
                return $question;
            }
        }
        return null;
    }

    /**
     * Validate answers
     * 
     * @param mixed $questions
     * @param mixed $data
     * 
     * @return bool
     */
    private function validate($questions, $data): bool
    {
        foreach ($questions as $question) {
            $questiongHandle = $question->getQuestionDataHandle();

            if (isset($data['Options'][$questiongHandle])) {
                foreach ($data['Options'][$questiongHandle] as $questionData) {
                    if (!isset($questionData) || empty($questionData)) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Cache the vehicle types
     * 
     * @return void
     */
    private function cacheVehicleTypes()
    {
        $vehicleTypes = $this->karfuApiService->getVehicleTypes(false);

        $service = 'karfu';
        $call = 'vehicle-type';
        $sessionKey = session_id();

        $apiCache = new ApiCache();
        $apiCache->setSessionKey($sessionKey)
            ->setService($service)
            ->setCall($call)
            ->setData($vehicleTypes);

        $vehicleTypesCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, $service, $call);

        if ($vehicleTypesCache === false) {
            $this->apiCacheService->create($apiCache);
        } else {
            $apiCache->setId($vehicleTypesCache->getId());
            $this->apiCacheService->update($apiCache);
        }
    }

    /**
     * Cache the mobility types
     * 
     * @return void
     */
    private function cacheMobilityTypes()
    {
        $mobilityTypes = $this->karfuApiService->getMobilityTypes();
        
        $service = 'karfu';
        $call = 'mobility-choices';
        $sessionKey = session_id();

        $apiCache = new ApiCache();
        $apiCache->setSessionKey($sessionKey)
            ->setService($service)
            ->setCall($call)
            ->setData($mobilityTypes);

        $mobilityTypesCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, $service, $call);

        if ($mobilityTypesCache === false) {
            $this->apiCacheService->create($apiCache);
        } else {
            $apiCache->setId($mobilityTypesCache->getId());
            $this->apiCacheService->update($apiCache);
        }
    }

    /**
     * Get form values from session answers
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function getFormValuesFromAnswers(array $answers): array
    {
        $values = [];

        if (count($answers) > 0) {
            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                [
                    'consideredVehicles',
                    'importantMatters',
                    'howMuchDoYouHave',
                    'whatIsYourMonthlyBudgetForThisSolution',
                    'howLongTerm',
                    'whatIsYourEstimatedMileage',
                    'whereAreYou'
                ],
                $answers
            );

            if (isset($tempAnswers['consideredVehicles'])) {
                $option = $tempAnswers['consideredVehicles'][0]->getOption();
                if ($option) {
                    $value = $option->getOptionTitle();
                    $values['consideredVehicles'] = $value;
                }
            }

            if (isset($tempAnswers['importantMatters'])) {
                $option = $tempAnswers['importantMatters'][0]->getOption();
                if ($option) {
                    $value = $option->getOptionTitle();
                    $values['importantMatters'] = $value;
                }
            }

            if (isset($tempAnswers['howMuchDoYouHave'])) {
                $value = $tempAnswers['howMuchDoYouHave'][0]->getValue();
                $values['howMuchDoYouHave'] = $tempAnswers['howMuchDoYouHave'][0]->getValue();
            }

            if (isset($tempAnswers['whatIsYourMonthlyBudgetForThisSolution'])) {
                $value = $tempAnswers['whatIsYourMonthlyBudgetForThisSolution'][0]->getValue();
                $values['whatIsYourMonthlyBudgetForThisSolution'] = $tempAnswers['whatIsYourMonthlyBudgetForThisSolution'][0]->getValue();
            }

            if (isset($tempAnswers['howLongTerm'])) {
                $value = $tempAnswers['howLongTerm'][0]->getValue();
                $values['howLongTerm'] = $tempAnswers['howLongTerm'][0]->getValue();
            }

            if (isset($tempAnswers['whatIsYourEstimatedMileage'])) {
                $value = $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue();
                $values['whatIsYourEstimatedMileage'] = $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue();
            }

            if (isset($tempAnswers['whereAreYou'])) {
                $value = $tempAnswers['whereAreYou'][0]->getValue();
                $values['whereAreYou'] = $tempAnswers['whereAreYou'][0]->getValue();
            }
        }

        return $values;
    }

    /**
     * Get form values from post data
     * 
     * @param $questions
     * 
     * @return array
     */
    private function getFormValuesFromPostData($questions): array
    {
        $values = [];

        if (isset($_POST['Options']) && count($_POST['Options']) > 0) {
            foreach ($_POST['Options'] as $k => $option) {
                foreach ($option as $k2 => $value) {
                    switch ($k) {
                        case 'consideredVehicles':
                            $question = $this->getQuestionByHandle($k, $questions);
                            $qOptions = $question->getOptions();
                            if ($qOptions) {
                                foreach ($qOptions as $qOption) {
                                    $optionId = $qOption->getId();
                                    if ($optionId == $value) {
                                        $optionTitle = $qOption->getOptionTitle();
                                        $values[$k] = $optionTitle;
                                        break;
                                    }
                                }
                            }
                            break;
                        case 'importantMatters':
                            $question = $this->getQuestionByHandle($k, $questions);
                            $qOptions = $question->getOptions();
                            if ($qOptions) {
                                foreach ($qOptions as $qOption) {
                                    $optionId = $qOption->getId();
                                    if ($optionId == $k2) {
                                        $optionTitle = $qOption->getOptionTitle();
                                        $values[$k] = $optionTitle;
                                        break;
                                    }
                                }
                            }
                            break;
                        default:
                            $values[$k] = $value;
                    }
                    break;
                }
            }
        }

        return $values;
    }
}
