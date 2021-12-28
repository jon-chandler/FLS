<?php

namespace Application\Controller\PageType;

use Application\Helper\QuestionJourney;
use Application\Karfu\Journey\Hook\Hook;
use Application\Karfu\Journey\Hook\HookExecuter;
use Application\Karfu\Journey\Hook\HookResultTemplateInject;
use Application\Karfu\Journey\Progress;
use Application\Karfu\Journey\SmartRedirect;
use Application\Model\ApiCache;
use Application\Service\ApiCacheService;
use Application\Service\JourneySummaryService;
use Application\Service\KarfuApiService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Page\Controller\PageTypeController;
use Concrete\Core\Page\Page;
use Concrete\Core\Session\SessionValidator;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Express;
use Core;

class JourneySummary extends PageTypeController
{
    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var SmartRedirect
     */
    private $smartRedirect;

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
     * @var JourneySummaryService
     */
    private $journeySummaryService;

    /**
     * @param $obj
     * @param SessionAnswerService $sessionAnswerService
     * @param SmartRedirect $smartRedirect
     * @param QuestionJourney $questionJourney
     * @param KarfuApiService $karfuApiService
     * @param ApiCacheService $apiCacheService
     * @param JourneySummaryService $journeySummaryService
     */
    public function __construct(
        $obj = null,
        SessionAnswerService $sessionAnswerService,
        SmartRedirect $smartRedirect,
        QuestionJourney $questionJourney,
        KarfuApiService $karfuApiService,
        ApiCacheService $apiCacheService,
        JourneySummaryService $journeySummaryService
    )
    {
        parent::__construct($obj);
        $this->sessionAnswerService = $sessionAnswerService;
        $this->smartRedirect = $smartRedirect;
        $this->questionJourney = $questionJourney;
        $this->karfuApiService = $karfuApiService;
        $this->apiCacheService = $apiCacheService;
        $this->journeySummaryService = $journeySummaryService;
    }

    /**
     * Concrete5 on_start hook
     * 
     * @return void
     */
    public function on_start()
    {
        // Get progress from the session
        $sessionProgress = Progress::getProgress();

        // Get page progress
        $pageProgress = (int) $this->c->getAttribute('tool_progress')
            ->getSelectedOptions()
            ->current()
            ->getSelectAttributeOptionValue();

        if ($sessionProgress === 0) {
            $sessionProgress = 1;
            Progress::setProgress($progress);
        }
        
        // If user has skipped ahead
        if ($pageProgress > $sessionProgress) {
            // TODO: Redirect or show error
        }

        // Get the journey
        $journey = $this->c->getAttribute('journey_page_journey')
            ->getSelectedEntries()
            ->first();

        if (isset($_SESSION['KARFU_user']['currentJourneyType'])) {
            $currentJourneyType = $_SESSION['KARFU_user']['currentJourneyType'];

            if ($currentJourneyType === 'Standard') {
                if (isset($_SESSION['KARFU_user']['completed'])) {
                    // Set the progress session for this journey as complete
                    if (array_search($pageProgress, $_SESSION['KARFU_user']['completed']) === false) {
                        $_SESSION['KARFU_user']['completed'][] = $pageProgress;
                    }
                } else {
                    $_SESSION['KARFU_user']['completed'] = [$pageProgress];
                }
            } else {
                $this->redirect('/');
            }
        } else {
            $this->redirect('/');
        }

        $this->set('progress', $pageProgress);
        $this->set('journey', $journey);
    }

    /**
     * Concrete5 view hook
     */
    public function view()
    {
        // Get vehicle & mobility types
        $vehicleTypes = $this->karfuApiService->getVehicleTypes(false);
        $mobilityTypes = $this->karfuApiService->getMobilityTypes();

        $service = 'karfu';
        $call = 'vehicle-type';
        $sessionKey = $this->session_key();
        $apiCache = new ApiCache();
        $apiCache->setSessionKey($sessionKey)
            ->setService($service)
            ->setCall($call)
            ->setData($vehicleTypes);

        // Get vehicle types from cache
        $vehicleTypesCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, $service, $call);

        // Create or update cache
        if ($vehicleTypesCache === false) {
            $this->apiCacheService->create($apiCache);
        } else {
            $apiCache->setId($vehicleTypesCache->getId());
            $this->apiCacheService->update($apiCache);
        }

        $call = 'mobility-choices';
        $apiCache = new ApiCache();
        $apiCache->setSessionKey($sessionKey)
            ->setService('karfu')
            ->setCall($call)
            ->setData($mobilityTypes);

        // Get mobility types from cache
        $mobilityTypesCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, $service, $call);

        // Create or update cache
        if ($mobilityTypesCache === false) {
            $this->apiCacheService->create($apiCache);
        } else {
            $apiCache->setId($mobilityTypesCache->getId());
            $this->apiCacheService->update($apiCache);
        }

        // Get answers by journey
        $answers = $this->sessionAnswerService->getSessionAnswers(false, true, $this->get('journey'), true);

        if (count($answers) > 0) {
            // Get all answers for later use
            $allAnswers = $this->sessionAnswerService->getSessionAnswers();

            // Get questions
            $questionEntity = Express::getObjectByHandle('question');
            $questionList = new EntryList($questionEntity);
            $questionList->filterByAttribute('hidden_question', 0, '=');
            $questionList->filterByAttribute('question_type', 'Dynamic', '=');
            $questionList->sortByOrder('asc');
            $questions = $questionList->getResults();

            // Get current journey
            $journey = $this->get('journey');
            $currentJourneyId = $journey->getId();
            $currentJourneyGroup = $journey->getJourneyGroup();
            $currentJourneyOrderId = (int) $journey->getJourneyOrder();

            // Get next journey
            $nextJourney = $this->questionJourney->getNextJourney(
                $journey->getJourneyGroup(),
                $journey->getJourneyOrder()
            );

            // Filter out quesitons not in current journey
            $questions = array_filter($questions, function ($question) use ($currentJourneyId) {
                $assoc = $question->getAssociation('journeys');
                $entries = $assoc->getSelectedEntries();

                foreach ($entries as $entry) {
                    $id = (int) $entry->getId();
                    if ($id === $currentJourneyId) {
                        return true;
                    }
                }

                return false;
            });

            // Get summaries
            $summaries = $this->journeySummaryService->readByJourneyGroupAndJourneyOrderAndPage(
                $currentJourneyGroup,
                $currentJourneyOrderId,
                'summary',
                [
                    'order' => [
                        'column' =>'order',
                        'ascDesc' => 'ASC'
                    ]
                ]
            );

            $finalSummaries = [];
            foreach ($summaries as $summary) {
                if (isset($summary['question_handle'])) {
                    $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandle($summary['question_handle'], $answers);

                    if (count($tempAnswers) > 0) {
                        $tempSummary = $summary;
                        $tempSummary['answers'] = $tempAnswers;
                        $finalSummaries[] = $tempSummary;
                    }
                } else if (isset($summary['on_summary_question_load'])) {
                    $html = '';
                    $hook = new Hook(
                        Hook::SERVER_ON_SUMMARY_QUESTION_LOAD,
                        $summary['on_summary_question_load'],
                        ['answers' => $allAnswers]
                    );

                    // Execute hook
                    $hookFuncExecuter = Core::make(HookExecuter::class);
                    $hookResult = $hookFuncExecuter->execute($hook);

                    // If hook response HookResultTemplateInject, get content to inject
                    if ($hookResult instanceof HookResultTemplateInject) {
                        $hookData = $hookResult->getData();

                        if (count($hookData) > 0) {
                            foreach ($hookData as $data) {
                                $html = $html . $data;
                            }
                            
                            $tempSummary = $summary;
                            $tempSummary['answers'] = [$html];
                            $finalSummaries[] = $tempSummary;
                        }
                    }
                }
            }

            $this->set('summaries', $finalSummaries);
            $this->set('nextJourney', $nextJourney);
        } else {
            return $this->smartRedirect->redirectToLastAnsweredQuestion();
        }
    }
    
    /**
     * Return the current or new session key if no session exists
     *
     * @return string
     * @throws
     */
    public function session_key()
    {
        $app = Application::getFacadeApplication();
        $sessionValidator = $app->make(SessionValidator::class);
        $session = $sessionValidator->hasActiveSession() ? $app->make('session') : null;

        /** try again. **/
        if (!$session) {
            $currentPage = Page::getCurrentPage()->getCollectionPath();
            $this->redirect($currentPage);
        }

        return $session->getId();
    }
    
    /**
     * Return the detail/header for the current journey
     *
     * @return string
     */
    public function get_journey_details()
    {
        if ($journey = $this->get('journey')) {
            return $journey->getJourneyDetails();
        }
    }
}
