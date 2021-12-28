<?php

namespace Application\Controller\SinglePage;

use Application\Service\SessionAnswerService;
use Core;
use PageController;

class Feedback extends PageController
{
    public function on_start()
    {
        $sessionAnswerService = Core::make(SessionAnswerService::class);
      
        $answers = $sessionAnswerService->getSessionAnswers(false, false);
		$answerList = [];

		$prevPage = $_SESSION['KARFU_user']['previousPage'];

		$comment = $_REQUEST['comment'];
		$currPage = $_REQUEST['currentPage'];

		if(count($answers)) {
			foreach ($answers as $answer) {
				$question = $answer->getQuestion();
				$title = $question->getQuestionTitle();
				$option = $answer->getOption();
				$optionValue = $option->getOptionTitle();

				$answerList[] = '<li>' . $title . ' -- <strong>' . $optionValue . '</strong></li>';
			}
		}
		
		$parameters = ['answers' => $answerList, 'currPage' => $currPage, 'prevPage' => $prevPage, 'comment' => $comment];

		var_dump($parameters);

		$mh = Core::make('helper/mail');
		$mh->addParameter('uName', 'KARFU');
		$mh->to('general@karfu.com');
		$mh->from('noreply@karfu.com', 'KARFU system');
		foreach ($parameters as $key => $value) {
			$mh->addParameter($key, $value);
		}
		$mh->addParameter('siteName', 'KARFU');
		$mh->load('feedback');
		$mh->sendMail();
		unset($mh);
    }


}
