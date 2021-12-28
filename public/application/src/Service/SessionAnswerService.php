<?php

declare(strict_types = 1);

namespace Application\Service;

use Application\Service\DynamicOptionMappingService;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Entity\Express\Entry;
use Concrete\Core\Session\SessionValidator;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Express;
use Core;

/**
 * Service call for session answers
 */
class SessionAnswerService
{
    /**
     * @var DynamicOptionMappingService
     */
    private $dynamicOptionMappingService;

    /**
     * @param DynamicOptionMappingService $dynamicOptionMappingService
     */
    public function __construct(DynamicOptionMappingService $dynamicOptionMappingService)
    {
        $this->dynamicOptionMappingService = $dynamicOptionMappingService;
    }

    /**
     * Return answers filtered to the current session token
     *
     * @param bool $sortDesc sort by data added descending if true
     * @param bool $currentJourneyOnly
     * @param bool $orderByQuestionOrder
     * @param Entry $journey
     * 
     * @return array
     * 
     * @throws Exception
     */
    public function getSessionAnswers($sortDesc = false, $currentJourneyOnly = true, $journey = null, $orderByQuestionOrder = false)
    {
        $sessionKey = $this->sessionKey();
        $answers = [];
        $entity = Express::getObjectByHandle('answer');
        $answerList = new EntryList($entity);
        $answerList->filterByAttribute('answer_session_key', $sessionKey);
        if ($sortDesc) {
            $answerList->sortByDateAddedDescending();
        }
        $answers = $answerList->getResults();

        // Remove answers from other journeys
        if ($currentJourneyOnly) {
            if ($journey) {
                $journeyId = $journey->getId();

                $answers = array_filter($answers, function($answer) use($journeyId) {
                    $question = $answer->getQuestion();

                    if ($question) {
                        $journeys = $question->getJourneys();
                        if ($journeys) {
                            foreach ($journeys as $journey) {
                                if ($journey->getId() === $journeyId) {
                                    return true;
                                }
                            }
                        }
                    }

                    return false;
                });
            }
        }

        // Order by question order
        if ($orderByQuestionOrder) {
            usort($answers, function ($answerA, $answerB) {
                // Get questions
                $questionA = $answerA->getQuestion();
                $questionB = $answerB->getQuestion();

                // Get options
                $optionA = $answerA->getOption();
                $optionB = $answerB->getOption();

                // Get options order
                $optionOrderA = ($optionA) ? $optionA->getEntryDisplayOrder() : 0;
                $optionOrderB = ($optionB) ? $optionB->getEntryDisplayOrder() : 0;

                // If there are no options, sort by answer value
                if (!$optionA && !$optionB) {
                    return strcmp($answerA->getValue(), $answerB->getValue());
                }

                // Get order
                $orderA = $questionA->getOrder();
                $orderB = $questionB->getOrder();

                if ($orderA === $orderB) {
                    if ($optionOrderA === $optionOrderB) {
                        return 0;
                    }

                    return ($optionOrderA < $optionOrderB) ? -1 : 1;
                }

                return ($orderA < $orderB) ? -1 : 1;
            });
        }

        return $answers;
    }

    /**
     * Return the current or new session key if no session exists
     *
     * @return string|null
     */
    public function sessionKey()
    {
        $sessionValidator = Core::make(SessionValidator::class);
        $session = $sessionValidator->hasActiveSession() ? Core::make('session') : null;
        
        if (!$session) {
            return null;
        }

        return $session->getId();
    }

    /**
     * Get the answers from the question if exists
     * 
     * @param Entry $question
     * @param array $answers
     * 
     * @return array
     */
    private function getAnswersByQuestion(Entry $question, array $answers): array
    {
        $foundAnswers = [];
        $options = $question->getOptions();
        $quesitonTitle = $question->getQuestionTitle();
        $questionId = $question->getId();

        if (!$options) {
            // Try and get options dynamically by session id
            $sessionKey = $this->sessionKey();
            $dynamicOptionMaps = $this->dynamicOptionMappingService->readBySessionKey($sessionKey);

            $optionEntity = Express::getObjectByHandle('option');
            $optionList = new EntryList($optionEntity);
            $query = $optionList->getQueryObject();

            foreach ($dynamicOptionMaps as $dynamicOptionMap) {
                $dataHandle = $dynamicOptionMap['option_data_handle'];
                $query->orWhere('ak_option_data_handle = "' . $dataHandle . '"');
            }

            $options = $optionList->getResults();
        }

        if ($options) {

            foreach ($options as $option) {
                $optionId = $option->getId();

                foreach ($answers as $answer) {
                    $aOption = $answer->getOption();

                    if ($aOption) {
                        $aOptionId = $aOption->getId();

                        if ($aOption->getId() === $optionId) {
                            $foundAnswers[] = $answer;
                        }
                    }
                }
            }
        }

        // Filter out already found answers
        $answers = array_filter($answers, function($answer) use($foundAnswers) {
            foreach ($foundAnswers as $foundAnswer) {
                if ($answer->getId() === $foundAnswer->getId()) {
                    return false;
                }
            }
            return true;
        });

        // Loop though answers to check for dynamic answers
        foreach ($answers as $answer) {
            $aQuestion = $answer->getQuestion();

            if ($aQuestion) {
                $aQuestionId = $aQuestion->getId();

                if ($aQuestionId === $questionId) {
                    $foundAnswers[] = $answer;
                }
            }
        }

        return $foundAnswers;
    }

    /**
     * Get answers by question handles
     * 
     * @param array $handles
     * @param array $answers
     * 
     * @return array
     */
    public function getAnswersByQuestionHandles(array $handles, array $answers): array
    {
        $foundAnswers = [];

        foreach ($answers as $answer) {
            $question = $answer->getQuestion();
            $questionHandle = $question->getQuestionDataHandle();

            foreach ($handles as $handle) {
                if ($questionHandle === $handle) {
                    $foundAnswers[$handle][] = $answer;
                }
            }
        }

        return $foundAnswers;
    }

    /**
     * Get answer by question handle
     * 
     * @param string $handle
     * @param array $answers
     * 
     * @return Entry|null
     */
    public function getAnswerByQuestionHandle(string $handle, array $answers)
    {
        foreach ($answers as $answer) {
            $question = $answer->getQuestion();
            $questionHandle = $question->getQuestionDataHandle();

            if ($questionHandle === $handle) {
                return $answer;
            }
        }
        return null;
    }

    /**
     * Get answers by question handle
     * 
     * @param string $handle
     * @param array $answers
     * 
     * @return array
     */
    public function getAnswersByQuestionHandle(string $handle, array $answers): array
    {
        $foundAnswers = [];

        foreach ($answers as $answer) {
            $question = $answer->getQuestion();
            $questionHandle = $question->getQuestionDataHandle();

            if ($questionHandle === $handle) {
                $foundAnswers[] = $answer;
            }
        }

        return $foundAnswers;
    }
}
