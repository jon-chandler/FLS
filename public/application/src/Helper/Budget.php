<?php

declare(strict_types = 1);

namespace Application\Helper;

use Application\Helper\SalaryCalculator;
use Application\Service\ApiCacheService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Session\SessionValidator;
use Core;

/**
 * Budget gets & returns various user budgets such as Total Budget & Total Deposit
 */
class Budget
{
    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var SalaryCalculator
     */
    private $salaryCalculator;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @param SessionValidator $sessionValidator
     * @param SessionAnswerService $sessionAnswerService
     * @param SalaryCalculator $salaryCalculator
     * @param ApiCacheService $apiCacheService
    */
    public function __construct(
        SessionValidator $sessionValidator,
        SessionAnswerService $sessionAnswerService,
        SalaryCalculator $salaryCalculator,
        ApiCacheService $apiCacheService
    )
    {
        $this->sessionValidator = $sessionValidator;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->salaryCalculator = $salaryCalculator;
        $this->apiCacheService = $apiCacheService;

        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
        
        if ($session) {
            $this->sessionKey = $session->getId();
        }
    }

    /**
     * Get user total budget
     * 
     * @param array $answers
     * 
     * @return float
     */
    public function getTotal(array $answers): float
    {
        $totalSpend = 0;

        if ($answers) {
            // Get answers
            $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                [
                    'whatIsYourMonthlyBudgetForThisSolution',
                    'howLongTerm',
                    'howMuchDoYouHave',
                    'whatVehicleDoYouHave',
                    'yourVehicleValuation',
                    'whatIsYourAnnualGrossSalary'
                ],
                $answers
            );

            // Assign answers to variables
            $whatIsYourMonthlyBudget = $filteredAnswers['whatIsYourMonthlyBudgetForThisSolution'][0];
            $howLongTerm = $filteredAnswers['howLongTerm'][0];
            $howMuchDoYouHave = $filteredAnswers['howMuchDoYouHave'][0];
            $vehicle = $filteredAnswers['whatVehicleDoYouHave'][0];
            $vehicleValuation = $filteredAnswers['yourVehicleValuation'][0];
            $whatIsYourAnnualGrossSalary = $filteredAnswers['whatIsYourAnnualGrossSalary'][0];

            // If monthly budget set, add to total
            if ($whatIsYourMonthlyBudget) {
                $monthlyBudget = (float) $whatIsYourMonthlyBudget->getValue();
                $howLong = (int) $howLongTerm->getValue();
                $totalSpend = $monthlyBudget * ($howLong * 12);
            }

            // If monthly budget not set, get salary, calculate monthly budget and add to total
            if ($howLongTerm && !$whatIsYourMonthlyBudget) {
                $howLong = (int) $howLongTerm->getValue();

                if ($whatIsYourAnnualGrossSalary && $howLong) {
                    $grossIncome = (float) $whatIsYourAnnualGrossSalary->getValue();

                    $salary = $this->salaryCalculator->calculate($grossIncome);
                    $monthlyNetIncome = $salary['monthly']['netIncome'];
                    $monthlyBudget = ($monthlyNetIncome / 100) * 17.5;
                    $totalSpend += $monthlyBudget * ($howLong * 12);
                }
            }

            // If deposit set, add to total
            if ($howMuchDoYouHave) {
                $value = $howMuchDoYouHave->getValue();
                if ($value) {
                    $totalSpend += (float) $value;
                }
            }

            // If vehicle valuation set, add to total
            if ($vehicle && $vehicleValuation) {
                $reg = $vehicle->getValue();
                
                $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
                $sessionKey = '';
                
                if ($session) {
                    $sessionKey = $session->getId();
                }

                // Get car data from the cache
                $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');
                $carData = $apiCache->getData();

                $privateValuation = (isset($carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'])) ?
                    (float) $carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'] : 0;
                $partExValuation = (float) $privateValuation - $privateValuation / 20;
                $cbsValuation = (float) $privateValuation - $privateValuation / 10;

                switch ($vehicleValuation->getOption()->getOptionTitle()) {
                    case 'Private Sale':
                        $valuation = $privateValuation;
                        break;
                    case 'Part Exchange';
                        $valuation = $partExValuation;
                        break;
                    case 'Car Buying Service':
                        $valuation = $cbsValuation;
                        break;
                }

                $totalSpend += $valuation;
            }
        }

        return $totalSpend;
    }

    /**
     * Get user total deposit
     * 
     * @param array $answers
     * 
     * @return float
     */
    public function getTotalDeposit(array $answers): float
    {
        $totalDeposit = 0;

        if ($answers) {
            // Get answers
            $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
                [
                    'howMuchDoYouHave',
                    'whatVehicleDoYouHave',
                    'yourVehicleValuation'
                ],
                $answers
            );

            // Assign answers to variables
            $howMuchDoYouHave = $filteredAnswers['howMuchDoYouHave'][0];
            $vehicle = $filteredAnswers['whatVehicleDoYouHave'][0];
            $vehicleValuation = $filteredAnswers['yourVehicleValuation'][0];

            // If deposit set, add to total
            if ($howMuchDoYouHave) {
                $value = $howMuchDoYouHave->getValue();
                if ($value) {
                    $totalDeposit += (float) $value;
                }
            }

            // If vehicle valuation set, add to total
            if ($vehicle && $vehicleValuation) {
                $reg = $vehicle->getValue();
                
                $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
                $sessionKey = '';
                
                if ($session) {
                    $sessionKey = $session->getId();
                }

                // Get car data from the cache
                $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');
                $carData = $apiCache->getData();

                $privateValuation = (isset($carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'])) ?
                    (float) $carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'] : 0;
                $partExValuation = (float) $privateValuation - $privateValuation / 20;
                $cbsValuation = (float) $privateValuation - $privateValuation / 10;

                switch ($vehicleValuation->getOption()->getOptionTitle()) {
                    case 'Private Sale':
                        $valuation = $privateValuation;
                        break;
                    case 'Part Exchange';
                        $valuation = $partExValuation;
                        break;
                    case 'Car Buying Service':
                        $valuation = $cbsValuation;
                        break;
                }

                $totalDeposit += $valuation;
            }
        }

        return $totalDeposit;
    }
}
