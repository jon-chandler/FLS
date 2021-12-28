<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey;

use Application\Service\SessionAnswerService;
use Concrete\Core\Routing\Redirect;
use Concrete\Core\Routing\RedirectResponse;

/**
 * SmartRedirect contains list of methods for performing smart redirects
 */
class SmartRedirect
{
    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @param SessionAnswerService $sessionAnswerService
     */
    public function __construct(SessionAnswerService $sessionAnswerService)
    {
        $this->sessionAnswerService = $sessionAnswerService;
    }

    /**
     * Redirect to the last answered question
     * 
     * @param string|null $journeyTitle
     * @param string|null $journeyGroup
     * 
     * @return RedirectResponse
     */
    public function redirectToLastAnsweredQuestion($journeyTitle = null, $journeyGroup = null): RedirectResponse
    {
        $answers = $this->sessionAnswerService->getSessionAnswers(true);

        if (count($answers) > 0) {
            $url = '/compare/';
            $question = $answers[0]->getQuestion();

            if ($journeyTitle && $journeyGroup) {
                $url .= str_replace(' ', '-', strtolower(trim($journeyGroup))) . '/'
                    . str_replace(' ', '-', strtolower(trim($journeyTitle)));
            } else {
                $assoc = $question->getAssociation('journeys');
                $journey = $assoc->getSelectedEntries()->first();
                $url .= str_replace(' ', '-', strtolower(trim($journey->getJourneyGroup()))) . '/'
                    . str_replace(' ', '-', strtolower(trim($journey->getJourneyTitle())))
                    . '-journey/question/' . $question->getId();
            }
            return Redirect::to($url);
        } else {
            $url = '/compare/';
            $url .= ($journeyGroup) ? $journeyGroup : 'full';
            return Redirect::to($url);
        }
    }
}
