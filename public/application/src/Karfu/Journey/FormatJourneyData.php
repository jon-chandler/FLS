<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey;

use Application\Controller\PageType\JourneyPageType;
use Application\Service\SessionAnswerService;
use Concrete\Core\Page\Page;
use Concrete\Core\Session\SessionValidator;

/**
 * FormatJourneyData formats the journey data for the karfu api
 */
class FormatJourneyData
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
	 * Get formatted journey data
	 * 
	 * @return string|false
	 */
	public function getJourneyData()
	{
		$answers = $this->sessionAnswerService->getSessionAnswers(false, false);

		$responseData = [];
		$questionHandles = [];
		
		foreach ($answers as $i => $answer) {

			$question = $answer->getQuestion();
			$questionHandle = $question->getQuestionDataHandle();
			$questionTitle = $question->getQuestionTitle();
			$option = $answer->getOption();
			if ($option) {
				$title = $answer->getAnswerTitle() ?: $option->getOptionTitle();
				$optionType = $option->getOptionType();
			} else  {
				$title = '';
				$optionType = '';
			}
			
			$value = $answer->getValue();
			$titleFormatted = ucfirst(strtolower($title));
			$answerType = $answer->getQuestion()->getAnswerSelection();
			$_option = '';

			if (in_array($optionType, ['slider', 'range'])) {
				$optionData = explode(',', $option->getOptionData());
				if (isset($optionData[$value - 1])) {
					$_option = $title;
					$_answer = $optionData[$value - 1];
				}
			} elseif ($optionType == 'postcode') {
				$titleFormatted = strtoupper($answer->getValue());
				if (strlen($titleFormatted) > 4) {
					$titleFormatted = trim(substr(trim($titleFormatted), 0, -3));
				}
				$_answer = $titleFormatted;
			}
			else {
				$_answer = $title;
			}

			$responseData[] = ['questionHandle' => $questionHandle, 'questionTitle' => $questionTitle, 'answer' => $_answer, 'options' => $_option];

		}

		return json_encode(self::cleanData($responseData));
	}

	/**
	 * Clean the data
	 * 
	 * @param array $data
	 * 
	 * @return array
	 */
	private function cleanData(array $data): array
	{
		$output = [];
		foreach ($data as $i => $deDupedResponse) {
			$key = array_search($deDupedResponse['questionHandle'], array_column($output, 'questionHandle'));
			if ($key === false) {
				array_push($output, $deDupedResponse);
			} else {
				$output[$key]['answer'] .= ", ". $deDupedResponse['answer'];
				if ($output[$key]['options']) {
					$output[$key]['options'] .= ", ". $deDupedResponse['options'];
				}
			}
		}

		foreach ($output as $i => $option) {
			$answers = explode(', ', $option['answer']);
			$options = explode(', ', $option['options']);

			$output[$i]['answer'] = count($answers) > 1 ? $answers : $output[$i]['answer'];

			if(count($options) > 1) {
				$output[$i]['options'] = $options;
			} else {
				unset($output[$i]['options']);
			}
		}

		return $output;
	}

}
