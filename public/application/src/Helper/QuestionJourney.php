<?php

declare(strict_types = 1);

namespace Application\Helper;

use Application\Service\DynamicOptionMappingService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Entity\Express\Entry;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Support\Facade\Express;

/**
 * QuestionJourney helper class
 */
class QuestionJourney
{
    /**
     * @var DynamicOptionMappingService
     */
    private $dynamicOptionMappingService;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @param DynamicOptionMappingService
     * @param SessionAnswerService
     */
    public function __construct(
        DynamicOptionMappingService $dynamicOptionMappingService,
        SessionAnswerService $sessionAnswerService
    )
    {
        $this->dynamicOptionMappingService = $dynamicOptionMappingService;
        $this->sessionAnswerService = $sessionAnswerService;
    }

    /**
     * Get the next question
     * 
     * @param array $answers
     * @param string $sessionKey
     * @param Entry|null $question
     * @param int|null $currentJourneyId
     * 
     * @return Entry|null
     */
    public function getNextQuestion(array $answers, string $sessionKey, Entry $question = null, int $currentJourneyId = null)
    {
        $prevOrder = (int) $question->getOrder();
        $answerSelection = (string) $question->getAnswerSelection();
        $selectedOptionIds = [];

        if (strtolower($answerSelection) === 'progress') {
            $options = $question->getOptions();

            if (!$options) {
                // Try and get options dynamically by question id and session id
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

            // Check if all progress questions are complete
            if ($this->isQuestionProgressComplete($question, $answers, $options)) {

                // Get all answers that their question has dependency on one of the questions options
                $filteredAnswers = $this->getDependingAnswersFromOptions($answers, $options);

                // Get highest order number from these filtered answers
                $lastAnswerQuestion = $this->getLastQuestionFromAnswers($filteredAnswers);

                // Set $prevOrder value to of the last answer quesiton order
                $prevOrder = (int) $lastAnswerQuestion->getOrder();
            }
        }

        foreach ($answers as $answer) {
            $option = $answer->getOption();
            if ($option) {
                $selectedOptionIds[] = $answer->getOption()->getId();
            }
        }
        
        $entity = Express::getObjectByHandle('question');
        $questionList = new EntryList($entity);
        $questionList->filterByAttribute('hidden_question', 0, '=');
        $questionList->filterByAttribute('question_type', 'Static', '=');
        if ($prevOrder !== null) {
            $questionList->filterByAttribute('order', $prevOrder, '>');
        }
        $questionList->sortByOrder('asc');
        $questions = $questionList->getResults();

        // Filter out unrelated journey questions
        if ($currentJourneyId !== null) {
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
        }

        unset($_SESSION['KARFU_user']['remainingQuestions']);
        
        // testing progress stuff
        $_SESSION['KARFU_user']['remainingQuestions'] = count($questions);

        foreach ($questions as $question) {
            $assocOptions = $question->getAssociation('dependency_options');
            if ($assocOptions) {
                $optionEntries = $assocOptions->getSelectedEntries();
                foreach ($optionEntries as $option) {
                    //  TODO: do all answers not just chosen options
                    $optionId = $option->getId();
                    if (in_array($option->getId(), $selectedOptionIds)) { 
                        return $question;
                    }
                }
            } else {
                return $question;
            }
        }

        return null;
    }

    /**
     * Get all questions by journey
     * 
     * @param Entry $journey
     * 
     * @return array
     */
    public function getAllQuestionsByJourney(Entry $journey)
    {
        $questions = [];
        $journeyAssoc = $journey->getAssociation('questions');

        if ($journeyAssoc) {
            $questions = $journeyAssoc->getSelectedEntries();
        }

        // testing progress stuff
        $_SESSION['KARFU_user']['totalQuestions'] = count($questions);
        return $questions;
    }

    /**
     * Get the previous question
     *
     * @param string $sessionKey
     * @param Entry $currentQuestion
     * @param array $answers
     * @param bool $incHiddenWithAnswers
     * 
     * @return Entry|null
     */
    public function getPreviousQuestion(string $sessionKey, Entry $currentQuestion, array $answers, bool $incHiddenWithAnswers = false)
    {
        $prevQuestion = null;
        $progressQuestion = null;
        $matchOptionId = null;
        $firstProgressQuestion = null;
        
        $questionEntity = Express::getObjectByHandle('question');
        $questionList = new EntryList($questionEntity);
        $questionList->filterByAttribute('hidden_question', 0, '=');
        $questionList->filterByAttribute('question_type', 'Static', '=');
        $questionList->filterByAttribute('order', $currentQuestion->getOrder(), '<=');
        if ($incHiddenWithAnswers === false) {
            $questionList->filterByAttribute('hidden_question_with_answers', false, '=');
        }
        $questionList->sortByOrder('desc');
        $questions = $questionList->getResults();

        // Check if there are any previous quesitons
        if (count($questions) > 0) {

            // Check if there is a question with answer selection of 'progress' type
            // and get the first result
            foreach ($questions as $question) {
                $answerSelection = (string) $question->getAnswerSelection();
                if (strtolower($answerSelection) === 'progress') {
                    $progressQuestion = $question;
                    break;
                }
            }
            
            foreach ($questions as $question) {
                $answerSelection = (string) $question->getAnswerSelection();
                if (
                    $prevQuestion === null
                    && $question->getId() !== $currentQuestion->getId()
                    && strtolower($answerSelection) !== 'progress'
                ) {
                    // Loop through answers to get previous answered question
                    foreach ($answers as $answer) {
                        $answerQuestion = $answer->getQuestion();

                        if ($question->getId() === $answerQuestion->getId()) {
                            $prevQuestion = $question;
                            break;
                        }
                    }
                }

                if ($progressQuestion !== null) {
                    $assocOptions = $question->getAssociation('dependency_options');

                    if ($assocOptions) {
                        $optionEntries = $assocOptions->getSelectedEntries();
                        
                        foreach ($optionEntries as $option) {

                            if (
                                $matchOptionId !== null
                                && $option->getId() !== $matchOptionId
                            ) {
                                // Break as we have now found the first question of progress question
                                break;
                            }

                            // Check if question has dependency on a question with answer selection 'progress'
                            $pqOptions = $progressQuestion->getOptions();

                            if (!$pqOptions) {
                                // Try and get options dynamically by session id
                                $dynamicOptionMaps = $this->dynamicOptionMappingService->readBySessionKey($sessionKey);

                                $optionEntity = Express::getObjectByHandle('option');
                                $optionList = new EntryList($optionEntity);
                                $query = $optionList->getQueryObject();

                                foreach ($dynamicOptionMaps as $dynamicOptionMap) {
                                    $dataHandle = $dynamicOptionMap['option_data_handle'];
                                    $query->orWhere('ak_option_data_handle = "' . $dataHandle . '"');
                                }

                                $pqOptions = $optionList->getResults();
                            }

                            if ($pqOptions) {
                                if (count($pqOptions) === 1) {
                                    break;
                                }

                                foreach ($pqOptions as $progressOptions) {
                                    if ($option->getId() === $progressOptions->getId()) {
                                        $matchOptionId = $option->getId();
                                        $firstProgressQuestion = $question;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    if ($firstProgressQuestion && $prevQuestion) {
                        // If the first progress question order is greater than last answered question order
                        // the previous question should be the 'progress' question
                        if ($firstProgressQuestion->getOrder() > $prevQuestion->getOrder()) {
                            $prevQuestion = $progressQuestion;
                            break;
                        }
                    }
                }
            }
        }

        return $prevQuestion;
    }

    /**
     * Get the next journey by journey order
     * 
     * @param string $group
     * @param int $order
     * 
     * @return Entry|null
     */
    public function getNextJourney(string $group, int $order)
    {
        $list = new EntryList(Express::getObjectByHandle('journey'));
        $list->filterByAttribute('journey_group', $group, '=');
        $list->filterByAttribute('journey_order', $order, '>');
        $list->sortByJourneyOrder('asc');
        $journey = $list->getResults();
        return (count($journey) > 0) ? $journey[0] : null;
    }

    /**
     * Get the previous journey by journey order
     * 
     * @param string $group
     * @param int $order
     * 
     * @return Entry|null
     */
    public function getPrevJourney(string $group, int $order)
    {
        $list = new EntryList(Express::getObjectByHandle('journey'));
        $list->filterByAttribute('journey_group', $group, '=');
        $list->filterByAttribute('journey_order', $order, '<');
        $list->sortByJourneyOrder('desc');
        $journey = $list->getResults();
        return (count($journey) > 0) ? $journey[0] : null;
    }

    /**
     * Get the current journey by journey order
     * 
     * @param int $order
     * 
     * @return Entry|null
     */
    public function getJourneyByJourneyOrder(int $order)
    {
        $list = new EntryList(Express::getObjectByHandle('journey'));
        $list->filterByJourneyOrder($order);
        $journey = $list->getResults();
        return (count($journey) > 0) ? $journey[0] : null;
    }

    /**
     * Get the current journey by journey group
     * 
     * @param string $group
     * 
     * @return array|null
     */
    public function getJourneysByJourneyGroup(string $group)
    {
        $list = new EntryList(Express::getObjectByHandle('journey'));
        $list->filterByJourneyGroup($group);
        $list->sortByJourneyOrder('asc');
        $journey = $list->getResults();
        return (count($journey) > 0) ? $journey : null;
    }

    /**
     * Get the current journey by journey group & journey order
     * 
     * @param string $group
     * @param int $order
     * 
     * @return array|null
     */
    public function getJourneyByJourneyGroupAndJourneyOrder(string $group, int $order)
    {
        $list = new EntryList(Express::getObjectByHandle('journey'));
        $list->filterByJourneyGroup($group);
        $list->filterByJourneyOrder($order);
        $list->sortByJourneyOrder('asc');
        $journey = $list->getResults();
        return (count($journey) > 0) ? $journey[0] : null;
    }

    /**
     * Get journey by question
     * 
     * @param Entry $question
     * 
     * @return Entry
     */
    public function getJourneyByQuestion($question)
    {
        $assoc = $question->getAssociation('journeys');
        $entry = $assoc->getSelectedEntries()->first();
        return $entry;
    }

    /**
     * Get journey by journey group & question
     * 
     * @param string $group
     * @param Entry $question
     * 
     * @return Entry
     */
    public function getJourneyByJourneyGroupAndQuestion(string $group, $question)
    {
        $assoc = $question->getAssociation('journeys');
        $entries = $assoc->getSelectedEntries()->filter(function ($journey) use ($group) {
            return ($journey->getJourneyGroup() === $group);
        });
        return $entries->first();
    }

    /**
     * Check if current question is the first question of its journey
     * 
     * @param Entry $question
     * @param Entry $journey
     * @param string sessionKey
     * 
     * @return bool
     */
    public function isQuestionFirstOfJourney(Entry $question, Entry $journey, string $sessionKey): bool
    {
        $assoc = $journey->getAssociation('questions');
        $preQuestion = null;

        if ($assoc) {
            $journeyQuestions = $assoc->getSelectedEntries()->toArray();

            if ($journeyQuestions) {

                // Sort questions by order
                usort($journeyQuestions, function ($a, $b) {
                    return ($a->getOrder() < $b->getOrder()) ? -1 : 1;
                });

                if ($journeyQuestions[0]->getId() === $question->getId()) {
                    return true;
                }

                // Get pre question and check if it is 'progress'
                foreach ($journeyQuestions as $journeyQuestion) {

                    if ($journeyQuestion->getOrder() < $question->getOrder()) {
                        if ($preQuestion === null) {
                            $preQuestion = $journeyQuestion;
                        } else {
                            if ($journeyQuestion->getOrder() > $preQuestion->getOrder()) {
                                $preQuestion = $journeyQuestion;
                            }
                        }
                    }
                }

                if ($preQuestion === null) {
                    return true;
                } else {
                    $answerSelection = (string) $preQuestion->getAnswerSelection();

                    if (strtolower($answerSelection) === 'progress') {
                        $options = $preQuestion->getOptions();

                        if (!$options) {
                            // Try and get options dynamically by session id
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

                        if (count($options) === 1) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Writes posted options as answers, calls deleteAnswer on each existing option answer which hasn't been selected this time
     * but was previously
     *
     * @param Entry $question
     * @param array $data
     * 
     * @return array
     * 
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function writeAnswers(Entry $question, array $data)
    {
        $sessionKey = session_id();
        $chosenOptions = [];
        
        if ($sessionKey && $question && $data) {
            $questionID = $question->getId();
            
            $em = \Database::connection()->getEntityManager();
            
            $assoc = $question->getAssociation('options');
            $questionOptions = [];
            if ($assoc) {
                $questionOptions = $assoc->getSelectedEntries();
            } else {
                // Try and get options dynamically by question id and session id
                $dynamicOptionMaps = $this->dynamicOptionMappingService->readBySessionKey($sessionKey);

                $optionEntity = Express::getObjectByHandle('option');
                $optionList = new EntryList($optionEntity);
                $query = $optionList->getQueryObject();

                foreach ($dynamicOptionMaps as $dynamicOptionMap) {
                    $dataHandle = $dynamicOptionMap['option_data_handle'];
                    $query->orWhere('ak_option_data_handle = "' . $dataHandle . '"');
                }

                $questionOptions = $optionList->getResults();
            }
            
            foreach ($data as $name => $values) {
                if ($name == 'Options') {
                    // handle single option ie radio button is not an array
                    $values = is_array($values) ? $values : [$values => $values];
                    
                    foreach ($values as $id => $value) {
                        foreach ($questionOptions as $option) {
                            if ($option->getId() == $id) {
                                switch ($option->getOptionType()) {
                                    case 'range':
                                        $values = explode(',', trim($option->getOptionData())) ?: [];
                                        $value = isset($values[$value]) ? $values[$value] : null;
                                        break;
                                }
                                $answerText = isset($data['AnswerText'][$id])
                                    ? trim($data['AnswerText'][$id])
                                    : '';
                                
                                $answerTextType = $option->getAnswerTextType();
                                
                                if (!$answerText && $answerTextType === 'Required') {
                                    return [];
                                }
                                
                                if (is_null($value)) {
                                    $value = $option->getDefaultValue();
                                }
                                $chosenOptions[$id] = [$option, $value, $answerText];
                            }
                        }

                        // Check if dynamic question
                        if (!array_key_exists($id, $chosenOptions)) {
                            $chosenOptions[$id] = [null, $value, null];
                        }

                    }
                    if ($chosenOptions) {
                        $existing = [];
                        // delete previous answers for this question
                        $answers = $this->sessionAnswerService->getSessionAnswers();
                        
                        foreach ($answers as $answer) {
                            if ($aq = $answer->getQuestion()) {
                                $aqID = $aq->getId();
                                if (!$aqID || $aqID === $questionID) {
                                    $option = $answer->getOption();
                                    if ($option) {
                                        $chosenOptionKey = $option->getId();
                                        $optionType = $option->getOptionType();
                                        $optionTypeValue = '';

                                        if (!is_null($optionType)) {
                                            // getValue() does not work, have to use __toString()
                                            $optionTypeValue = $optionType->__toString();
                                        }
                                    } else {
                                        $chosenOptionKey = '';
                                    }
                                    
                                    if (
                                        !isset($chosenOptions[$chosenOptionKey])
                                        && $optionTypeValue !== 'progress'
                                    ) {
                                        $this->deleteAnswer($answers, $answer, true);
                                    } else {
                                        $existing[$chosenOptionKey] = $answer;
                                    }
                                }
                            }
                        }
                        // create or update chosen options
                        foreach ($chosenOptions as $id => $info) {
                            list($option, $value, $content) = $info;
                            
                            if (!isset($existing[$id])) {
                                $answer = Express::buildEntry('answer');
                                $answer->setAnswerSessionKey($sessionKey);
                                $answer->setQuestion($question);
                                if ($option) {
                                    $answer->setOption($option);
                                }
                                $answer->setValue($value);
                                if ($content) {
                                    $answer->setContent($content);
                                }
                                
                                $answer = $answer->save();
                            } else {
                                $answer = $existing[$id];
                                $answer->setValue($value);
                                if ($content) {
                                    $answer->setContent($content);
                                }
                            }
                            $answer = Express::refresh($answer);
                        }
                    }
                    
                }
            }
            $em->flush();
        }
        return $chosenOptions;
    }

    /**
     * Write multiple answers
     * 
     * @param $questions
     * @param array $data
     * 
     * @return array
     * 
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function writeMultipleQuestionAnswers($questions, array $data)
    {
        foreach ($questions as $k => $question) {
            $this->writeAnswers($question, $data[$k]);
        }
    }

    /**
     * Delete answer
     * 
     * @param array $answers all answers
     * @param Entry $answer the answer to remove
     * @param bool  $chainForward delete answer options answers too
     * 
     * @return void
     */
    public function deleteAnswer($answers, $answer, $chainForward = true)
    {
        if ($chainForward) {
            $answerOption = $answer->getOption();

            if ($answerOption) {
                // Get $answer option id and add to $optionIds array
                $id = $answerOption->getId();
                $optionIds = [$id];

                // Check if any answer given depends on a previous answer to have been answered
                foreach ($answers as $tempAnswer) {
                    $question = $tempAnswer->getQuestion();

                    if ($question) {
                        $assocOptions = $question->getAssociation('dependency_options');

                        if ($assocOptions) {
                            $options = $assocOptions->getSelectedEntries();

                            foreach ($options as $option) {

                                if (in_array($option->getId(), $optionIds)) {
                                    $qOptions = $question->getOptions();

                                    foreach ($qOptions as $qOption) {
                                        $optionIds[] = $qOption->getId();
                                    }

                                    Express::deleteEntry($tempAnswer);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        Express::deleteEntry($answer);
    }

    /**
     * Check if the question of progress type is complete
     * 
     * @param Entry $quesiton
     * @param array $answers
     * @param array|null $dynamicOptions
     * 
     * @return bool
     */
    private function isQuestionProgressComplete(Entry $question, array $answers, array $dynamicOptions = null): bool
    {
        $options = $question->getOptions();
        if ($options) {
            $optionsCount = count($options);
        } elseif ($dynamicOptions) {
            $optionsCount = count($dynamicOptions);
        } else {
            $optionsCount = 0;
        }

        $foundCound = 0;
        $done = [];
        $classMethod = 'HookFunction::redirectToVehicleJourneyStart';

        foreach ($answers as $answer) {
            $aQuestion = $answer->getQuestion();
            $aQuestionId = (int) $aQuestion->getId();

            if (!in_array($aQuestionId, $done)) {

                if ($answer->getQuestion()->getServerOnQuestionSubmit() === $classMethod) {
                    $foundCount++;
                }

                $done[] = $aQuestion->getId();
            }
        }

        return ($foundCount === $optionsCount) ? true : false;
    }

    /**
     * Get answers from dependency options
     * 
     * @param array $answers
     * @param array $options
     * 
     * @return array
     */
    private function getDependingAnswersFromOptions(array $answers, array $options): array
    {
        // Get id's of all the question options
        $optionIds = array_map(
            function($option) {
                return (int) $option->getId();
            },
            $options
        );

        // Filter answers by the answers question dependency on the question options
        $filteredAnswers = array_filter($answers, function ($answer) use ($optionIds) {
            $question = $answer->getQuestion();
            $assocOptions = $question->getAssociation('dependency_options');

            if ($assocOptions) {
                $optionEntries = $assocOptions->getSelectedEntries();

                foreach ($optionEntries as $optionEntry) {
                    $optionId = (int) $optionEntry->getId();

                    // Key of the progress question option if exists
                    $key = array_search($optionId, $optionIds);

                    if ($key !== false) {
                        return true;
                    }
                }
            }

            return false;
        });

        return $filteredAnswers;
    }

    /**
     * Get last question from answers
     * 
     * @param array $answers
     * 
     * @return Entry|null
     */
    private function getLastQuestionFromAnswers(array $answers)
    {
        $order = 0;
        $question = null;
        foreach ($answers as $answer) {
            $aQuestion = $answer->getQuestion();
            $aqOrder = (int) $aQuestion->getOrder();

            if ($aqOrder > $order) {
                $order = $aqOrder;
                $question = $aQuestion;
            }
        }
        
        return $question;
    }

}
