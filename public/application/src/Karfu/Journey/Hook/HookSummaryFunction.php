<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

use Application\Helper\CapData;
use Application\Helper\SalaryCalculator;
use Application\Karfu\Journey\Hook\HookResultAddOption;
use Application\Karfu\Journey\Hook\HookResultProgress;
use Application\Karfu\Journey\Hook\HookResultTemplateInject;
use Application\Model\ApiCache;
use Application\Service\ApiCacheService;
use Application\Service\KarfuApiService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Session\SessionValidator;
use Concrete\Core\Support\Facade\Express;
use Core;
use File;

/**
 * HookFunction contains all the hook functions for the question journey
 */
class HookSummaryFunction
{
    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @var CapData
     */
    private $capData;

    /**
     * @var SalaryCalculator
     */
    private $salaryCalculator;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var KarfuApiService
     */
    private $karfuApiService;

    /**
     * @param SessionValidator $sessionValidator
     * @param ApiCacheService $apiCacheService
     * @param CapData $capData
     * @param SalaryCalculator $salaryCalculator
     * @param SessionAnswerService $sessionAnswerService
     * @param KarfuApiService $karfuApiService
     */
    public function __construct(
        SessionValidator $sessionValidator,
        ApiCacheService $apiCacheService,
        CapData $capData,
        SalaryCalculator $salaryCalculator,
        SessionAnswerService $sessionAnswerService,
        KarfuApiService $karfuApiService
    )
    {
        $this->sessionValidator = $sessionValidator;
        $this->apiCacheService = $apiCacheService;
        $this->capData = $capData;
        $this->salaryCalculator = $salaryCalculator;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->karfuApiService = $karfuApiService;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getMonthlyBudget(array $data): HookResultTemplateInject
    {
        $answers = $data['answers'];
        $responseData = [];

        if ($answers) {
            $whatIsYourMonthlyBudget = $this->sessionAnswerService->getAnswerByQuestionHandle('whatIsYourMonthlyBudgetForThisSolution', $answers);

            if (!$whatIsYourMonthlyBudget) {
                $whatIsYourAnnualGrossSalary = $this->sessionAnswerService->getAnswerByQuestionHandle('whatIsYourAnnualGrossSalary', $answers);

                if ($whatIsYourAnnualGrossSalary) {
                    $grossIncome = (float) $whatIsYourAnnualGrossSalary->getValue();

                    $salary = $this->salaryCalculator->calculate($grossIncome);
                    $monthlyNetIncome = $salary['monthly']['netIncome'];
                    $monthlyBudget = ($monthlyNetIncome / 100) * 17.5;
                    $responseData['html'] = '£' . number_format($monthlyBudget);
                }
            }
        }

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getVehicleSaleContribution(array $data): HookResultTemplateInject
    {
        $answers = $data['answers'];
        $responseData = [];
        
        if ($answers) {
            $vehicle = $this->sessionAnswerService->getAnswerByQuestionHandle('whatVehicleDoYouHave', $answers);
            $vehicleValuation = $this->sessionAnswerService->getAnswerByQuestionHandle('yourVehicleValuation', $answers);
            $howMuchIsItWorth = $this->sessionAnswerService->getAnswerByQuestionHandle('howMuchIsItWorth', $answers);

            if ($howMuchIsItWorth) {
                $valuation = (float) $howMuchIsItWorth->getValue();
                if ($valuation) {
                    $responseData['html'] = '£' . number_format($valuation);
                }
            } elseif ($vehicle && $vehicleValuation) {
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

                $responseData['html'] = '£' . number_format($valuation);
            }
        }

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getVehicleKeepLength(array $data): HookResultTemplateInject
    {
        $answers = $data['answers'];
        $responseData = [];

        if ($answers) {
            $howLongTerm = $this->sessionAnswerService->getAnswerByQuestionHandle('howLongTerm', $answers);

            if ($howLongTerm) {
                $length = $howLongTerm->getValue();

                if ($length && is_numeric($length)) {
                    $years = ($length > 1) ? 'years' : 'year';
                    $responseData['html'] = $length . ' ' . $years;
                }
            }
        }

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getOngoingSpend(array $data): HookResultTemplateInject
    {
        $answers = $data['answers'];
        $responseData = [];

        if ($answers) {
            $ongoingSpend = 0;
            $whatIsYourMonthlyBudget = $this->sessionAnswerService->getAnswerByQuestionHandle('whatIsYourMonthlyBudgetForThisSolution', $answers);
            $howLongTerm = $this->sessionAnswerService->getAnswerByQuestionHandle('howLongTerm', $answers);

            if ($howLongTerm) {
                if ($whatIsYourMonthlyBudget) {
                    $monthlyBudget = (float) $whatIsYourMonthlyBudget->getValue();
                    $howLong = (int) $howLongTerm->getValue();
                    $ongoingSpend = $monthlyBudget * ($howLong * 12);
                    $responseData['html'] = '£' . number_format($ongoingSpend);
                } else {
                    $whatIsYourAnnualGrossSalary = $this->sessionAnswerService->getAnswerByQuestionHandle('whatIsYourAnnualGrossSalary', $answers);
                    $howLong = (int) $howLongTerm->getValue();

                    if ($whatIsYourAnnualGrossSalary && $howLong) {
                        $grossIncome = (float) $whatIsYourAnnualGrossSalary->getValue();

                        $salary = $this->salaryCalculator->calculate($grossIncome);
                        $monthlyNetIncome = $salary['monthly']['netIncome'];
                        $monthlyBudget = ($monthlyNetIncome / 100) * 17.5;
                        $ongoingSpend += $monthlyBudget * ($howLong * 12);
                        $responseData['html'] = '£' . number_format($ongoingSpend);
                    }
                }
            }
        }

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getTotalSpend(array $data): HookResultTemplateInject
    {
        $answers = $data['answers'];

        $responseData = [];

        if ($answers) {
            $totalSpend = 0;
            $whatIsYourMonthlyBudget = $this->sessionAnswerService->getAnswerByQuestionHandle('whatIsYourMonthlyBudgetForThisSolution', $answers);
            $howLongTerm = $this->sessionAnswerService->getAnswerByQuestionHandle('howLongTerm', $answers);
            $howMuchDoYouHave = $this->sessionAnswerService->getAnswerByQuestionHandle('howMuchDoYouHave', $answers);
            $vehicle = $this->sessionAnswerService->getAnswerByQuestionHandle('whatVehicleDoYouHave', $answers);
            $vehicleValuation = $this->sessionAnswerService->getAnswerByQuestionHandle('yourVehicleValuation', $answers);

            if ($whatIsYourMonthlyBudget) {
                $monthlyBudget = (float) $whatIsYourMonthlyBudget->getValue();
                $howLong = (int) $howLongTerm->getValue();
                $totalSpend = $monthlyBudget * ($howLong * 12);
                $responseData['html'] = '£' . number_format($totalSpend);
            } 
            if ($howLongTerm && !$whatIsYourMonthlyBudget) {
                $whatIsYourAnnualGrossSalary = $this->sessionAnswerService->getAnswerByQuestionHandle('whatIsYourAnnualGrossSalary', $answers);
                $howLong = (int) $howLongTerm->getValue();

                if ($whatIsYourAnnualGrossSalary && $howLong) {
                    $grossIncome = (float) $whatIsYourAnnualGrossSalary->getValue();

                    $salary = $this->salaryCalculator->calculate($grossIncome);
                    $monthlyNetIncome = $salary['monthly']['netIncome'];
                    $monthlyBudget = ($monthlyNetIncome / 100) * 17.5;
                    $totalSpend += $monthlyBudget * ($howLong * 12);
                    $responseData['html'] = '£' . number_format($totalSpend);
                }
            }
            if ($howMuchDoYouHave) {
                $value = $howMuchDoYouHave->getValue();
                if ($value) {
                    $totalSpend += (float) $value;
                    $responseData['html'] = '£' . number_format($totalSpend);
                }
            }
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

                $privateValuation = (float) $carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'];
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
                $responseData['html'] = '£' . number_format($totalSpend);
            }
        }

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getMaxDeposit(array $data): HookResultTemplateInject
    {
        $answers = $data['answers'];
        $responseData = [];
        $howMuchDoYouHave = 0;
        $yourVehicleValuation = 0;

        $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            [
                'howMuchDoYouHave',
                'whatVehicleDoYouHave',
                'yourVehicleValuation'
            ],
            $answers
        );

        if (count($tempAnswers) > 0) {
            if (array_key_exists('howMuchDoYouHave', $tempAnswers)) {
                $howMuchDoYouHave = (int) $tempAnswers['howMuchDoYouHave'][0]->getValue();
            }

            if (array_key_exists('yourVehicleValuation', $tempAnswers)) {
                $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
                $sessionKey = '';
                
                if ($session) {
                    $sessionKey = $session->getId();
                }
                $vehicleValuationType = $tempAnswers['yourVehicleValuation'][0]->getOption()->getOptionTitle();
                $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');
                $carData = $apiCache->getData();
                $privateValuation = (float) $carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'];

                switch ($vehicleValuationType) {
                    case 'Private Sale':
                        $yourVehicleValuation = $privateValuation;
                        break;
                    case 'Part Exchange';
                        $yourVehicleValuation = (float) $privateValuation - $privateValuation / 20;
                        break;
                    case 'Car Buying Service':
                        $yourVehicleValuation = (float) $privateValuation - $privateValuation / 10;
                        break;
                    default:
                        $yourVehicleValuation = $privateValuation;
                }
            }

            $maxDeposit = $howMuchDoYouHave + $yourVehicleValuation;
            $responseData = ['html' => '£' . number_format($maxDeposit)];
        }

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getWeeklyEstimatedMileage(array $data): HookResultTemplateInject
    {
        $answers = $data['answers'];
        $responseData = [];

        if ($answers) {
            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(['whatIsYourEstimatedMileage'], $answers);
            
            if (array_key_exists('whatIsYourEstimatedMileage', $tempAnswers)) {
                $estAnnualMileage = (int) $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue();
                //$estTotalMileage = $estAnnualMileage / 52;

                // testing
                $estTotalMileage = $estAnnualMileage;

                $responseData['html'] = number_format($estTotalMileage);
            }
        }

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getEstimatedMileageOverTerm(array $data): HookResultTemplateInject
    {
        $answers = $data['answers'];
        $responseData = [];

        if ($answers) {
            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(['howLongTerm', 'whatIsYourEstimatedMileage'], $answers);
            
            if (
                array_key_exists('howLongTerm', $tempAnswers)
                && array_key_exists('whatIsYourEstimatedMileage', $tempAnswers)
            ) {
                $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
                $estAnnualMileage = (int) $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue();

                $estTotalMileage = $estAnnualMileage * $ownershipPeriod;
                $responseData['html'] = number_format($estTotalMileage);
            }
        }

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getVehicleTypeMobilityChoices(array $data): HookResultTemplateInject
    {
        $responseData = [];
        $vehicleTypesMobilityChoices = $this->karfuApiService->getVehicleTypesMobilityChoices();

        if ($vehicleTypesMobilityChoices) {
            $responseData['html'] = '';

            foreach ($vehicleTypesMobilityChoices as $vehicleTypeMobilityChoices) {
                $vehicleYesNo = false;

                foreach ($vehicleTypeMobilityChoices['mobilityChoices'] as $mobilityChoice) {
                    if ($mobilityChoice['yesNo'] === true) {
                        if ($vehicleYesNo === false) {
                            $vehicleYesNo = true;
                            $responseData['html'] .= '<div class="">' . $vehicleTypeMobilityChoices['vehicleType'] . '</div>';
                        }
                        $responseData['html'] .= '<div class="summary-item active">' . ucfirst(strtolower($mobilityChoice['mobilityChoice'])) . '</div>';
                    }
                }
            }
        }

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }
}
