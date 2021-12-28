<?php

namespace Application\Controller\SinglePage\Compare;

use Application\Helper\QuestionJourney;
use Application\Karfu\Journey\Hook\Hook;
use Application\Karfu\Journey\Hook\HookExecuter;
use Application\Karfu\Journey\Hook\HookResultTemplateInject;
use Application\Service\JourneySummaryService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Support\Facade\Express;
use Core;
use PageController;

class FinalSummary extends PageController
{
    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var JourneySummaryService
     */
    private $journeySummaryService;

    /**
     * @var QuestionJourney
     */
    private $questionJourney;

    /**
     * @param $obj
     * @param SessionAnswerService $sessionAnswerService
     * @param JourneySummaryService $journeySummaryService
     * @param QuestionJourney $questionJourney
     */
    public function __construct(
        $obj = null,
        SessionAnswerService $sessionAnswerService,
        JourneySummaryService $journeySummaryService,
        QuestionJourney $questionJourney
    )
    {
        parent::__construct($obj);
        $this->sessionAnswerService = $sessionAnswerService;
        $this->journeySummaryService = $journeySummaryService;
        $this->questionJourney = $questionJourney;
    }

    /**
     * Concrete5 on_start hook
     */
    public function on_start()
    {
        $currentJourneyType = null;
        $currentJourneyGroup = null;

        // If current journey is not set, redirect
        if (isset($_SESSION['KARFU_user']['currentJourneyType'])) {
            $currentJourneyType = $_SESSION['KARFU_user']['currentJourneyType'];
            $currentJourneyGroup = $_SESSION['KARFU_user']['currentJourneyGroup'];
        } else {
            $this->redirect('/');
        }

        $this->set('currentJourneyType', $currentJourneyType);
        $this->set('currentJourneyGroup', $currentJourneyGroup);
    }

    /**
     * Concrete5 view hook
     */
    public function view()
    {
        // Can we skip processing results?
        $processResults = (empty($this->request->request('skipProcessResults'))) ? true : false;

        $currentJourneyType = $this->get('currentJourneyType');
        $currentJourneyGroup = $this->get('currentJourneyGroup');
        $journeySummaries = [];

        // Get answers
        $answers = $this->sessionAnswerService->getSessionAnswers(false, false);

        // Get journeys
        $journeys = $this->questionJourney->getJourneysByJourneyGroup($currentJourneyGroup);

        // Check if journey is complete
        $isJourneyComplete = $this->isJourneyComplete($journeys);

        // Loop through each journey & build summaries
        foreach ($journeys as $journey) {
            $journeyOrder = (int) $journey->getJourneyOrder();

            $summaries = $this->journeySummaryService->readByJourneyOrderAndPage(
                $journeyOrder,
                'final_summary',
                [
                    'order' => [
                        'column' =>'order',
                        'ascDesc' => 'ASC'
                    ]
                ]
            );

            $journeySummaries[] = [
                'journey' => $journey,
                'summaries' => $summaries
            ];
        }

        $journeySummaries = array_map(function ($journeySummary) use ($answers) {
            $journeySummary['summaries'] = array_map(function ($summary) use ($answers) {
                if (isset($summary['question_handle'])) {
                    // Summary has a question handle, get the answer & add to summary
                    $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandle($summary['question_handle'], $answers);
    
                    if (count($tempAnswers) > 0) {
                        $summary['answers'] = $tempAnswers;
                        return $summary;
                    } else {
                        return null;
                    }
                } else if (isset($summary['on_summary_question_load'])) {
                    // Summary has on load hook
                    $html = '';
                    $hook = new Hook(
                        Hook::SERVER_ON_SUMMARY_QUESTION_LOAD,
                        $summary['on_summary_question_load'],
                        ['answers' => $answers]
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
                            
                            $summary['answers'] = [$html];
                            return $summary;
                        }
                    }
                } else {
                    return null;
                }
            }, $journeySummary['summaries']);

            // Filter out null values
            $journeySummary['summaries'] = array_filter($journeySummary['summaries'], function ($summary) {
                return ($summary !== null);
            });

            return $journeySummary;
        }, $journeySummaries);

        $this->set('summaries', $journeySummaries);
        $this->set('isJourneyComplete', $isJourneyComplete);
        $this->set('processResults', $processResults);
    }

    /**
     * Check if current journey is complete
     * 
     * @param array $journeys
     * 
     * @return bool
     */
    public function isJourneyComplete(array $journeys): bool
    {
        if (isset($_SESSION['KARFU_user']['completed'])) {
            $completeCount = 0;
            foreach ($journeys as $journey) {
                $journeyOrder = (int) $journey->getJourneyOrder();
                if (in_array($journeyOrder, $_SESSION['KARFU_user']['completed'])) {
                    $completeCount++;
                }
            }

            if ($completeCount === count($journeys)) {
                return true;
            }
        }

        return false;
    }
}
