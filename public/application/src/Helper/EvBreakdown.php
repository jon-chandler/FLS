<?php

namespace Application\Helper;

use Core;
use Database;
use DateTime;
use Exception;
use Page;

class EvBreakdown
{

	// mileage, in this instance, is a weekly sum, not annual
	public static function getRangeVal (int $mileage)
	{

		$suitability = 1;
		$msg = "Not for you";

		if($mileage <= 70) {
			$suitability = 5;
			$msg = "Perfect! All electric vehicles are within your mileage needs";
			$msg2 = "Very light use like this is unlikely to need a charge every week.";
			$score = "Very high";
		} else if ($mileage > 70 && $mileage <= 144) {
			$suitability = 4;
			$msg = "Great! The electric vehicles are within usage range";
			$msg2 = "Charge a Short Range electric cars at least once a week, others less often.";
			$score = "High";
		} else if ($mileage > 144 && $mileage <= 300) {
			$suitability = 3;
			$msg = "Most electric vehicles are within your usage requirements";
			$msg2 = "Charge most electric cars at least weekly, Long Range models less often.";
			$score = "Medium";
		}
		else if ($mileage > 300 && $mileage <= 500) {
			$suitability = 2;
			$msg = "Your usage indicates that an electric vehicle is not for you";
			$msg2 = "Expect to leave enough time to charge more than once a week.";
			$score = "Low";
		} else if ($mileage > 500) {
			$suitability = 1;
			$msg = "Not for you";
			$msg2 = "Caution. High use like this will need several charges every week.";
			$score = "Very low";
		}

		return [$suitability, $msg2, $score];

	}

	public static function getBudgetRange (int $budget)
	{
		$suitability = 1;
		$msg = "Sorry. No results within your price range";

		if($budget <= 12000) {
			$suitability = 1;
			$msg = "Sorry. No results within your price range";
			$msg2 = "Used and leased electric cars are within your budget";
			$score = "Very low";
		} else if ($budget > 12000 && $budget <= 25000) {
			$suitability = 2;
			$msg = "The vehicles are slightly beyond your budget";
			$msg2 = "Inexpensive electric cars are within your budget";
			$score = "Low";
		} else if ($budget > 25000 && $budget <= 50000) {
			$suitability = 3;
			$msg = "There are a number of vehicles within your budget";
			$msg2 = "Most new and used electric cars are within your budget";
			$score = "Medium";
		}
		else if ($budget > 50000 && $budget <= 75000) {
			$suitability = 4;
			$msg = "Great. There are vehicles within your budget";
			$msg2 = "Most new electric cars are within your budget";
			$score = "High";
		} else if ($budget > 75000) {
			$suitability = 5;
			$msg = "Excellent. There are a number of vehicles within your budget";
			$msg2 = "New luxury electric cars are within your budget";
			$score = "Very high";
		}

		return [$suitability, $msg2, $score];
	}

	public static function getChargeOpts (array $locations)
	{

		$suitability = 1;
		$msg = 'Caution - Few charging options';
		$scoreInc = 0;
		$prevScore = 0;

		foreach($locations as $i => $location) {
			switch($location) {
				case 'At home':
					if($prevScore <=5) {
						$scoreInc = 5;
					}
				break;
				case 'Near home':
					if($prevScore <=4) {
						$scoreInc = 4;
					}
				break;
				case 'At work':
					if($prevScore <=3) {
						$scoreInc = 3;
					}
				break;
				case 'En route':
					if($prevScore <=2) {
						$scoreInc = 2;
					}
				break;
				case 'Only route':
					if($prevScore <=1) {
						$scoreInc = 1;
					}
				break;
			}

			$prevScore = $scoreInc;
		}

		if($scoreInc >= 5) {
			$msg = 'Excellent charging options';
			$msg2 = 'Home charging is a huge advantage.';
			$score = "Very high";
			$suitability = 5;
		} else if($scoreInc <= 5 && $scoreInc >= 4) {
			$msg = 'Above average charging options';
			$msg2 = 'Being able to charge locally is a plus.';
			$score = "High";
			$suitability = 4;
		} else if ($scoreInc <= 4 && $scoreInc >=3) {
			$msg = 'Average charging options';
			$msg2 = 'Having charge access only at work may be inconvenient.'; 
			$score = "Medium";
			$suitability = 3;
		} else if ($scoreInc <= 3 && $scoreInc >= 2) {
			$msg = 'Below average charging options';
			$msg2 = 'Relying on service station charging alone may not be enough.';
			$score = "Low";
			$suitability = 2;
		} else if ($scoreInc <2) {
			$msg = 'Caution - Few charging options';
			$msg2 = 'Unless there is very reliable charging on your route, perhaps reconsider.';
			$score = "Very low";
			$suitability = 1;
		}

		return [$suitability, $msg2, $score];
	}

	public static function getScoreBreakdown (int $score, int $budget)
	{

		if($score >= 10) {
			$feedback = "Based on your answers, an electric car looks like a great fit for you.";
			$feedbackLine2 = "Not convinced, or want more flexibility to change your mind? Consider try-before-you-buy with a short-term subscription. Interested?";
		} elseif ($score <10 && $score > 6) {
			if($budget > 2) {
				$feedback = "Based on your mileage and charging answers, electric car ownership could present some frustration in the near term.";
				$feedbackLine2 = "If you would like more options, consider subscribing to or leasing an electric car, instead. Interested?";
			} else {
				$feedback = "Based on your answers, buying a used electric car could be a good entry point for you.";
				$feedbackLine2 = "If you are happy to wait whilst charging, all the better, or try subscribing to an electric car on a short term basis first. Interested?";
			}
		} else {
			$feedback = "Based on your answers, electric car ownership might not be right for you today.";
			$feedbackLine2 = "There are still options open to you via Sharing, or Subscribing to an elcetric car on a short term basis. Think of it as try-before-you-buy. Interested?";
		}

		$synopsis = "Please note that <strong>Low</strong> or <strong>Very low</strong> results for <strong>Charging</strong> and <strong>Range</strong> are reasons to think twice about whether switching now will be convenient for you, even if you are eager.";

		return [$feedback, $feedbackLine2, $synopsis];
	}

}