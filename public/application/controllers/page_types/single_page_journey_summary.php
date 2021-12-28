<?php

namespace Application\Controller\PageType;

use Application\Karfu\Journey\Hook\Hook;
use Application\Karfu\Journey\Hook\HookExecuter;
use Application\Karfu\Journey\Hook\HookResultTemplateInject;
use Application\Service\JourneySummaryService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Page\Controller\PageTypeController;
use Core;

class SinglePageJourneySummary extends PageTypeController
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
     * @param $obj
     * @param SessionAnswerService $sessionAnswerService
     * @param JourneySummaryService $journeySummaryService
     */
    public function __construct(
        $obj = null,
        SessionAnswerService $sessionAnswerService,
        JourneySummaryService $journeySummaryService
    )
    {
        parent::__construct($obj);
        $this->sessionAnswerService = $sessionAnswerService;
        $this->journeySummaryService = $journeySummaryService;
    }

    /**
     * Concrete5 view hook
     */
    public function view()
    {   
        $finalSummaries = [];

        // Get journey
        $journey = $this->getJourney();

        if ($journey) {
            $journeyHandle = $journey->getJourneyDataHandle();

            if ($journeyHandle) {
                // Get summaries by journey data handle
                $summaries = $this->journeySummaryService->readByJourneyDataHandle(
                    $journey->getJourneyDataHandle(),
                    [
                        'order' => [
                            'column' =>'order',
                            'ascDesc' => 'ASC'
                        ]
                    ]
                );

                $answers = $this->sessionAnswerService->getSessionAnswers(false, false);

                if ($summaries && $answers) {
                    // Loop through each summary & build a final summary list
                    foreach ($summaries as $summary) {
                        if (isset($summary['question_handle'])) {
                            // Summary has a question handle, get the answer & add to final summary
                            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandle($summary['question_handle'], $answers);
            
                            if (count($tempAnswers) > 0) {
                                $tempSummary = $summary;
                                $tempSummary['answers'] = $tempAnswers;
                                $finalSummaries[] = $tempSummary;
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
        
                            // If hook response HookResultTemplateInject, add to html
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
                }
            }
        }        

        $this->set('summaries', $finalSummaries);
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
     * Get question by question data handle
     * 
     * @var mixed
     * @var $questions
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
}
