<?php

namespace Application\Controller\PageType;

use Application\Helper\QuestionJourney;
use Application\Karfu\Journey\Hook\Hook;
use Application\Karfu\Journey\Hook\HookExecuter;
use Application\Karfu\Journey\Hook\HookResultProgress;
use Application\Karfu\Journey\Hook\HookResultRedirect;
use Application\Karfu\Journey\Hook\HookResultResponse;
use Application\Karfu\Journey\Hook\HookResultAddOption;
use Application\Karfu\Journey\Hook\HookResultTemplateInject;
use Application\Karfu\Journey\Progress;
use Application\Karfu\Journey\SmartRedirect;
use Application\Service\DynamicOptionMappingService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Page\Controller\PageTypeController;
use Concrete\Core\Page\Page;
use Concrete\Core\Routing\Redirect;
use Concrete\Core\Session\SessionValidator;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Express;
use Core;

class JourneyPageType extends PageTypeController
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
     * @var DynamicOptionMappingService
     */
    private $dynamicOptionMappingService;

    /**
     * @param $obj
     * @param SmartRedirect $smartRedirect
     * @param QuestionJourney $questionJourney
     * @param DynamicOptionMappingService $dynamicOptionMappingService
     */
    public function __construct(
        $obj = null,
        SessionAnswerService $sessionAnswerService,
        SmartRedirect $smartRedirect,
        QuestionJourney $questionJourney,
        DynamicOptionMappingService $dynamicOptionMappingService
    )
    {
        parent::__construct($obj);
        $this->sessionAnswerService = $sessionAnswerService;
        $this->smartRedirect = $smartRedirect;
        $this->questionJourney = $questionJourney;
        $this->dynamicOptionMappingService = $dynamicOptionMappingService;
    }

    /**
     * Concrete5 on_start hook
     * 
     * @return void
     */
    public function on_start()
    {
        $new = $this->request->request('new');

        // Get page progress from page attribute
        $pageProgress = (int) $this->c->getAttribute('tool_progress')
            ->getSelectedOptions()
            ->current()
            ->getSelectAttributeOptionValue();

        // Get the journey
        $journey = $this->c->getAttribute('journey_page_journey')
            ->getSelectedEntries()
            ->first();

        $journeyType = (string) $journey->getJourneyType();

        // If new & session is active, empty & create new session
        if ($new && session_status() === PHP_SESSION_ACTIVE) {
            $configStore = Core::make('config');
            $sessionCookieKey = $configStore->get('concrete.session.name');
            $oldSession = $_SESSION;

            // Close current session
            session_destroy();
            unset($_COOKIE[$sessionCookieKey]);

            // Start session and copy old global session values
            session_start();
            $_SESSION = $oldSession;
            $_SESSION['KARFU_user']['completed'] = [];
            $_SESSION['KARFU_user']['currentJourneyType'] = $journeyType;
            $_SESSION['KARFU_user']['currentJourneyGroup'] = $journey->getJourneyGroup();
        }

        // Get progress from the session
        $sessionProgress = Progress::getProgress();

        if ($sessionProgress === 0) {
            $sessionProgress = 1;
            Progress::setProgress($progress);
        }
        
        // If user has skipped ahead
        if ($pageProgress > $sessionProgress) {
            // TODO: Redirect or show error
        }

        if (isset($_SESSION['KARFU_user']['currentJourneyType'])) {
            $currentJourneyType = $_SESSION['KARFU_user']['currentJourneyType'];

            // If current journey type does not match page journey type, redirect
            if ($currentJourneyType !== $journeyType) {
                $this->redirect('/');
            }
        } else {
            $this->redirect('/');
        }

        $this->set('progress', $pageProgress);
        $this->set('journey', $journey);
    }
    
    /**
     * Setup procesing, clear messages etc
     *
     * @return bool|RedirectResponse|Response
     */
    public function validateRequest()
    {
        $this->set('journey_message', '');
        return parent::validateRequest();
    }
    
    /**
     * Concrete5 view hook
     */
    public function view()
    {
        if (!$question) {
            // Get journey questions
            $questions = $this->get_current_journey_questions();

            if ($questions) {
                // Get first question
                $question = $questions->first();

                // Process hidden question answers
                $answers = $this->sessionAnswerService->getSessionAnswers(false, false);
                $sessionKey = $this->session_key();
                $journeyId = (int) $this->get('journey')->getId();
                $nextQuestionId = $this->processHiddenQuestionsWithAnswers($question, $answers, $sessionKey, $journeyId);

                if ($nextQuestionId) {
                    $question = Express::getEntry($nextQuestionId);
                }

                // Get page progress (What journey page they are on)
                $pageProgress = (int) $this->c->getAttribute('tool_progress')
                    ->getSelectedOptions()
                    ->current()
                    ->getSelectAttributeOptionValue();
            
                $prevJourney = $pageProgress - 1;
        
                // Check if there is a previous journey
                if ($prevJourney >= 1) {

                    // Check if previous journey is NOT in list of completed journeys then redirect
                    // as the user has skipped ahead
                    if (isset($_SESSION['KARFU_user']['completed'])) {
                        if (array_search($prevJourney, $_SESSION['KARFU_user']['completed']) === false) {
                            return $this->smartRedirect->redirectToLastAnsweredQuestion();
                        }
                    } else {
                        return $this->smartRedirect->redirectToLastAnsweredQuestion();
                    }
                }
            } else {
                $this->redirect('/');
            }
        }

        // Set up the view
        $this->setupView($question);
    }
    
    /**
     * Show question by question id
     * - if POST, get next question and redirect
     * - if POST and no next question available, show summary
     *
     * @param int|null $questionID
     * 
     * @return RedirectResponse|void
     * 
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function question($questionID = null)
    {
        $request = $this->getRequest();

        // Get page progress (What journey page they are on)
        $pageProgress = (int) $this->c->getAttribute('tool_progress')
            ->getSelectedOptions()
            ->current()
            ->getSelectAttributeOptionValue();
        
        $prevJourney = $pageProgress - 1;

        // Check if previous journey is NOT in list of completed journeys then redirect
        // as the user has skipped ahead
        if ($prevJourney >= 1) {
            if (isset($_SESSION['KARFU_user']['completed'])) {
                if (array_search($prevJourney, $_SESSION['KARFU_user']['completed']) === false) {
                    return $this->smartRedirect->redirectToLastAnsweredQuestion();
                }
            } else {
                return $this->smartRedirect->redirectToLastAnsweredQuestion();
            }
        }
        
        if ($questionID) {
            // Get question
            $question = Express::getEntry($questionID);

            if ($question) {
                // If method is POST, process the submitted answers
                if ($request->isMethod('POST')) {

                    // Get submitted data & content
                    $content = $request->getContent();
                    $data = [];
                    parse_str($content, $data);

                    // Write the answers
                    $chosenOptions = $this->questionJourney->writeAnswers($question, $data);

                    // If there are errors writing answers, set error message
                    if (count($chosenOptions) > 0) {
                        $this->set('journey_message', 'please answer the questions to continue');
                    }

                    // AHEM. This is super ropey, but dumped this in until the rankable options are fixed. Check the post data for that option type.
                    $opts = $data['Options'];
                    $hasData = strlen($content);
                    if (is_array($opts)) {
                        $hasData = (count(array_keys($opts)) > 1 || !empty(array_values($opts)[0]));
                    }

                    $answers = $this->sessionAnswerService->getSessionAnswers(false, false);
                    $sessionKey = $this->session_key();

                    if ($chosenOptions && $hasData) {

                        // Check for ServerOnQuestionSubmit hooks
                        if ($question->getServerOnQuestionSubmit()) {
                            $hook = new Hook(
                                Hook::SERVER_ON_QUESTION_SUBMIT,
                                $question->getServerOnQuestionSubmit(),
                                [
                                    'answers' => $answers,
                                    'question' => $question,
                                    'journey' => $this->get('journey'),
                                    'viewPath' => $this->getViewObject()->getViewPath()
                                ]
                            );

                            // Execute hook
                            $hookFuncExecuter = Core::make(HookExecuter::class);
                            $hookResult = $hookFuncExecuter->execute($hook);

                            // If hook response HookResultRedirect, redirect
                            if ($hookResult instanceof HookResultRedirect) {
                                return Redirect::to($hookResult->getData()['url']);
                            }

                            // If hook response HookResultResponse, set response
                            if ($hookResult instanceof HookResultResponse) {
                                $hookData = $hookResult->getData();

                                // If reponse has error
                                if (!$hookData['success']) {
                                    return $this->setupView($question, true, $hookData['errorMessage']);
                                }
                            }
                        }

                        // Check for ServerOnOptionSubmit hooks
                        foreach ($chosenOptions as $option) {
                            if ($option[0] && $option[0]->getServerOnOptionSubmit()) {
                                $hook = new Hook(
                                    Hook::SERVER_ON_OPTION_SUBMIT,
                                    $option[0]->getServerOnOptionSubmit(),
                                    [
                                        'answers' => $answers,
                                        'question' => $question,
                                        'chosenOptions' => $chosenOptions,
                                        'journey' => $this->get('journey'),
                                        'viewPath' => $this->getViewObject()->getViewPath()
                                    ]
                                );
    
                                // Execute hook
                                $hookFuncExecuter = Core::make(HookExecuter::class);
                                $hookResult = $hookFuncExecuter->execute($hook);
    
                                // If hook response HookResultRedirect, redirect
                                if ($hookResult instanceof HookResultRedirect) {
                                    return Redirect::to($hookResult->getData()['url']);
                                }
                            }
                        }

                        $journeyId = (int) $this->get('journey')->getId();

                        // Get the next question
                        $nextQuestion = $this->questionJourney->getNextQuestion($answers, $sessionKey, $question, $journeyId);

                        if ($nextQuestion !== null) {
                            // Process hidden question answers
                            $nextQuestionId = $this->processHiddenQuestionsWithAnswers($nextQuestion, $answers, $sessionKey, $journeyId);
                            
                            // Redirect to next question
                            $questionID = ($nextQuestionId) ? $nextQuestionId : $nextQuestion->getId();
                            return Redirect::to($this->getViewObject()->getViewPath() . '/question/' . $questionID);
                        } else {
                            // Redirect to summary page
                            return Redirect::to($this->getViewObject()->getViewPath() . '/summary');
                        }
                    } elseif (strtolower($question->getAnswerSelection()) === 'progress') {
                        $journeyId = (int) $this->get('journey')->getId();
                        $nextQuestion = $this->questionJourney->getNextQuestion($answers, $sessionKey, $question, $journeyId);

                        if ($nextQuestion !== null) {
                            $questionID = $nextQuestion->getId();
                            return Redirect::to($this->getViewObject()->getViewPath() . '/question/' . $questionID);
                        } else {
                            return Redirect::to($this->getViewObject()->getViewPath() . '/summary');
                        }
                    } elseif (!$this->get('journey_message') || !count($data)) {
                        // need to choose something
                        $this->set('journey_message', 'please answer the question to continue');
                    }
                }
            } else {
                $this->redirect($this->view->getViewPath() . '?error=e4');    
            }
        } else {
            $this->redirect($this->view->getViewPath());
        }

        // Set up the view
        return $this->setupView($question);
    }

    /**
     * Delete all existing answers for this question and go back to journey start
     *
     * @return RedirectResponse
     */
    public function reset()
    {
        $answers = $this->sessionAnswerService->getSessionAnswers();

        foreach ($answers as $answer) {
            Express::deleteEntry($answer->getId());
        }
        return Redirect::to($this->getViewObject()->getViewPath());
    }

    /**
     * Set provided question into view and previous answer details for current question if it exists
     *
     * @param Entry $question the question to show, generally the next question from the one just answered
     * @param bool $error
     * @param string $errorMessage
     * 
     * @throws Exception
     */
    protected function setupView($question, bool $error = false, string $errorMessage = null)
    {
        $answered = [];
        $answers = $this->sessionAnswerService->getSessionAnswers(false, false);
        $sessionKey = $this->session_key();
        $this->set('answers', $answers);
        
        if ($question) {
            $journeys = $this->questionJourney->getJourneysByJourneyGroup($this->get('journey')->getJourneyGroup());
            $answerSelection = $question->getAnswerSelection();
            $options = $question->getOptions();
            $injectableData = [];
            $additionalOptions = [];
            $dynamicOptions = null;

            // If there are no options for the question, see if there are any dynamic options
            if (!$options) {

                // Get options dynamically by session id
                $dynamicOptionMaps = $this->dynamicOptionMappingService->readBySessionKey($sessionKey);

                $optionEntity = Express::getObjectByHandle('option');
                $optionList = new EntryList($optionEntity);
                $query = $optionList->getQueryObject();

                foreach ($dynamicOptionMaps as $dynamicOptionMap) {
                    $dataHandle = $dynamicOptionMap['option_data_handle'];
                    $query->orWhere('ak_option_data_handle = "' . $dataHandle . '"');
                }

                $options = $optionList->getResults();
                $dynamicOptions = $options;
            }

            // Check for ServerOnQuestionLoad hooks
            if ($question->getServerOnQuestionLoad()) {

                // Get array of hooks
                $serverOnQuestionLoads = explode(',', trim($question->getServerOnQuestionLoad()));

                foreach ($serverOnQuestionLoads as $serverOnQuestionLoad) {
                    $hook = new Hook(
                        Hook::SERVER_ON_QUESTION_LOAD,
                        $serverOnQuestionLoad,
                        [
                            'answers' => $answers,
                            'question' => $question,
                            'dynamicOptions' => $dynamicOptions
                        ]
                    );

                    // Execute hook
                    $hookFuncExecuter = Core::make(HookExecuter::class);
                    $hookResults = $hookFuncExecuter->execute($hook);

                    if (is_array($hookResults)) {
                        foreach ($hookResults as $hookResult) {
                            // If hook response HookResultTemplateInject, add to inject array
                            if ($hookResult instanceof HookResultTemplateInject) {
                                foreach ($hookResult->getData() as $hookKey => $hookData) {
                                    $injectableData[$hookKey] = $hookData;
                                }
                            } elseif ($hookResult instanceof HookResultProgress) {
                                // If hook response HookResultProgress, set progress data
                                $this->set('progressData', $hookResult->getData());
                            } elseif ($hookResult instanceof HookResultAddOption) {
                                // If hook response HookResultAddOption, set additional options
                                $additionalOptions = $hookResult->getData();
                            }
                        }
                    } else {
                        // If hook response HookResultTemplateInject, add to inject array
                        if ($hookResults instanceof HookResultTemplateInject) {
                            foreach ($hookResults->getData() as $hookKey => $hookData) {
                                $injectableData[$hookKey] = $hookData;
                            }
                        } elseif ($hookResults instanceof HookResultProgress) {
                            // If hook response HookResultProgress, set progress data
                            $this->set('progressData', $hookResults->getData());
                        } elseif ($hookResults instanceof HookResultAddOption) {
                            // If hook response HookResultAddOption, set additional options
                            $additionalOptions = $hookResults->getData();
                        }
                    }
                }
            }

            // If there is only one progress option, write the answer automatically & redirect
            if (strtolower($answerSelection) === 'progress' && count($additionalOptions) === 1) {
                $optionId = $additionalOptions[0]->getId();

                // Write answer
                $chosenOptions = $this->questionJourney->writeAnswers($question, ['Options' => [$optionId => $optionId]]);
                if (count($chosenOptions) > 0) {
                    $this->set('journey_message', 'please answer the questions to continue');
                }

                // Manually create hook to redirect
                $hook = new Hook(
                    Hook::SERVER_ON_OPTION_SUBMIT,
                    'HookFunction::redirectToNextOptionDependency',
                    [
                        'question' => $question,
                        'chosenOptions' => $chosenOptions,
                        'journey' => $this->get('journey'),
                        'viewPath' => $this->getViewObject()->getViewPath()
                    ]
                );
                $hookFuncExecuter = Core::make(HookExecuter::class);
                $hookResult = $hookFuncExecuter->execute($hook);
                return $this->redirect($hookResult->getData()['url']);
            }

            if ($options) {
                // Check for ServerOnOptionLoad hooks
                foreach ($options as $option) {
                    if ($option->getServerOnOptionLoad()) {
                        $hook = new Hook(
                            Hook::SERVER_ON_OPTION_LOAD,
                            $option->getServerOnOptionLoad(),
                            $answers
                        );

                        // Execute hook
                        $hookFuncExecuter = Core::make(HookExecuter::class);
                        $hookResult = $hookFuncExecuter->execute($hook);

                        // If hook response HookResultTemplateInject, add to inject array
                        if ($hookResult instanceof HookResultTemplateInject) {
                            foreach ($hookResult->getData()  as $hookKey => $hookData) {
                                $injectableData[$hookKey] = $hookData;
                            }
                        }
                    }
                }
            }

            $this->set('additionalOptions', $additionalOptions);
            $this->set('injectableData', $injectableData);
            $this->set('question', $question);
            $this->set('journeys', $journeys);
            $questionID = $question->getId();
            
            foreach ($answers as $answer) {
                $answerQuestion = $answer->getQuestion();
                if ($answerQuestion) {
                    if ($answerQuestion->getId() == $questionID) {
                        $option = $answer->getOption();
                        if ($option) {
                            $answered[$option->getId()] = $answer;
                        } else {
                            $answered[$answer->getValue()] = $answer;
                        }
                    }
                }
            }
            // just answered
            $this->set('answered', $answered);
        } elseif (!$answers) {
            $this->set('journey_message', "Sorry, there are no questions to ask");
        }
        
        $previousQuestion = $this->questionJourney->getPreviousQuestion($sessionKey, $question, $answers);
        $this->set('previous', $previousQuestion);
        $this->set('error', $error);
        $this->set('errorMessage', $errorMessage);
    }

    /**
     * Process questions of type hidden with answers
     * 
     * @param Entity $question
     * @param array $answers
     * @param string $sessionKey
     * @param int $journeyId
     * @param int|null $prevNextQuestion
     * 
     * @return int|null
     */
    private function processHiddenQuestionsWithAnswers($question, array $answers, string $sessionKey, int $journeyId, $prevNextQuestion = null)
    {
        // Check if question is type hidden with answers
        $isHiddenQuestionWithAnswers = $question->getHiddenQuestionWithAnswers() ? true : false;
        if ($isHiddenQuestionWithAnswers) {

            // Get options
            $options = $question->getOptions();
            foreach ($options as $option) {
                $optionId = $option->getId();

                // Answer option
                $this->questionJourney->writeAnswers($question, ['Options' => [$optionId => $optionId]]);
            }

            $answers = $this->sessionAnswerService->getSessionAnswers(false, false);

            // Get the next question
            $nextQuestion = $this->questionJourney->getNextQuestion($answers, $sessionKey, $question, $journeyId);

            if ($nextQuestion !== null) {
                // Process hidden questions with answers for next question
                $nextQuestionId = $this->processHiddenQuestionsWithAnswers($nextQuestion, $answers, $sessionKey, $journeyId, $nextQuestion);

                if ($nextQuestionId) {
                    return $nextQuestionId;
                } else {
                    return $nextQuestion->getId();
                }
            } else {
                if ($prevNextQuestion) {
                    return $prevNextQuestion->getId();
                }
            }
        }

        return null;
    }
    
    /**
     * Return the current page journey's start question if it is set
     *
     * @return Entry|null
     */
    public function startQuestion()
    {
        if ($journey = $this->get('journey')) {
            return $journey->getStartQuestion();
        }
    }
    
    /**
     * Get questions of the current journey
     * 
     * @return ArrayCollection|mixed
     */
    public function get_current_journey_questions()
    {
        if ($journey = $this->get('journey')) {
            if ($assoc = $journey->getAssociation('questions')) {
                return $assoc->getSelectedEntries();
            }
        }
    }

    /**
     * Return the current or new session key if no session exists
     *
     * @return string
     */
    public function session_key()
    {
        $app = Application::getFacadeApplication();
        $sessionValidator = $app->make(SessionValidator::class);
        /** @var Session $session */
        $session = $sessionValidator->hasActiveSession() ? $app->make('session') : null;

        /** try again. **/
        if (!$session) {
            $currentPage = Page::getCurrentPage()->getCollectionPath();
            $this->redirect($currentPage);
        }

        return $session->getId();
    }
}
