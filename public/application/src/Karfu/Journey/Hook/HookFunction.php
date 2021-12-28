<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

use Application\Helper\CapData;
use Application\Helper\DVSAData;
use Application\Helper\QuestionJourney;
use Application\Helper\VehicleFormula;
use Application\Karfu\Journey\Hook\HookResultAddOption;
use Application\Karfu\Journey\Hook\HookResultRedirect;
use Application\Karfu\Journey\Hook\HookResultResponse;
use Application\Karfu\Journey\Hook\HookResultProgress;
use Application\Karfu\Journey\Hook\HookResultTemplateInject;
use Application\Model\ApiCache;
use Application\Service\ApiCacheService;
use Application\Service\DynamicOptionMappingService;
use Application\Service\KarfuApiService;
use Application\Service\KarfuAttributeMapService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Session\SessionValidator;
use Concrete\Core\Support\Facade\Express;
use Core;
use DateTime;
use DateInterval;
use Exception;
use File;

/**
 * HookFunction contains all the hook functions for the question journey
 */
class HookFunction
{
    /**
     * @var Connection
     */
    private $con;

    /**
     * @var File
     */
    private $file;

    /**
     * @var CapData
     */
    private $capData;

    /**
     * @var DVSAData
     */
    private $DVSAData;

    /**
     * @var KarfuApiService
     */
    private $karfuApiService;

    /**
     * @var DynamicOptionMappingService
     */
    private $dynamicOptionMappingService;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var VehicleFormula
     */
    private $vehicleFormula;

    /**
     * @var QuestionJourney
     */
    private $questionJourney;

    /**
     * @param Connection $con
     * @param File $file
     * @param CapData $capData
     * @param DVSAData $dvsaData
     * @param KarfuApiService $karfuApiService
     * @param DynamicOptionMappingService $dynamicOptionMappingService
     * @param ApiCacheService $apiCacheService
     * @param SessionValidator $sessionValidator
     * @param SessionAnswerService $sessionAnswerService
     * @param KarfuAttributeMapService $karfuAttributeMapService
     * @param VehicleFormula $vehicleFormula
     * @param QuestionJourney $questionJourney
     */
    public function __construct(
        Connection $con,
        File $file,
        CapData $capData,
        DVSAData $dvsaData,
        KarfuApiService $karfuApiService,
        DynamicOptionMappingService $dynamicOptionMappingService,
        ApiCacheService $apiCacheService,
        SessionValidator $sessionValidator,
        SessionAnswerService $sessionAnswerService,
        KarfuAttributeMapService $karfuAttributeMapService,
        VehicleFormula $vehicleFormula,
        QuestionJourney $questionJourney
    )
    {
        $this->con = $con;
        $this->file = $file;
        $this->capData = $capData;
        $this->dvsaData = $dvsaData;
        $this->karfuApiService = $karfuApiService;
        $this->dynamicOptionMappingService = $dynamicOptionMappingService;
        $this->apiCacheService = $apiCacheService;
        $this->sessionValidator = $sessionValidator;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $this->vehicleFormula = $vehicleFormula;
        $this->questionJourney = $questionJourney;
    }

    /**
     * @param array $data
     * 
     * @return HookResultResponse
     */
    public function validateVehicleRegistrationPlate(array $data): HookResultResponse
    {
        $answers = $data['answers'];
        $answer = $this->sessionAnswerService->getAnswerByQuestionHandle('whatVehicleDoYouHave', $answers);

        if ($answer) {
            $reg = $answer->getValue();
            try {
                $carData = $this->capData->getCarData($reg);
            } catch (Exception $e) {
                $hookResult = new HookResultResponse();
                return $hookResult->setData([
                    'success' => false,
                    'errorMessage' => 'Invalid vehicle registration'
                ]);
            }
            $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
            $sessionKey = '';
            
            if ($session) {
                $sessionKey = $session->getId();
            }

            $apiCache = new ApiCache();
            $apiCache->setSessionKey($sessionKey)
                ->setService('cap-hpi')
                ->setCall('vrms')
                ->setData($carData);

            $this->apiCacheService->deleteBySessionKeyServiceCall($apiCache);
            $this->apiCacheService->create($apiCache);
        }
        
        $responseData = [
            'success' => true,
            'errorMessage' => null
        ];

        $hookResult = new HookResultResponse();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getVehicleByRegistrationPlate(array $data): HookResultTemplateInject
    {
        $html = '';
        $answers = $data['answers'];
        $answer = $this->sessionAnswerService->getAnswerByQuestionHandle('whatVehicleDoYouHave', $answers);

        if ($answer) {
            $reg = $answer->getValue();
            $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
            $sessionKey = '';
            
            if ($session) {
                $sessionKey = $session->getId();
            }

            $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');
            $carData = $apiCache->getData();
            
            $year = explode('-', $carData['vehicleDetails']['firstRegistration']['firstRegistrationDate'])[0];
            $make = $carData['vehicleDetails']['description']['brand'];
            $model = $carData['vehicleDetails']['description']['model'];
            $fuel = $carData['derivativeDetails']['fuelType']['name'];
            $trim = $carData['derivativeDetails']['trim']['name'];
            $colourClass = strtolower($carData['vehicleDetails']['colours']['current']['name']);
            $reg = strtoupper($carData['vehicleDetails']['vrm']);
            $img = '/application/files/vehicles/'. $reg .'.jpg';

            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $img) && filesize($_SERVER['DOCUMENT_ROOT'] . $img) > 1000) {
                $image = $img;
            } else {
                $image = '/application/files/vehicles/missing-image.svg';
                $imageClass = 'missing-image';
            }

            $trimDetail = (!empty($trim)) ? '<tr><th>Trim:</th><td>' . t($trim) . '</td></tr>' : '';

            if(!empty($colourClass)) {
                $colourString = "<div class='vehicle-colour'>
                                    <div class='key'>COLOUR: </div>
                                    <div class='colour-marker' style='background-color: {$colourClass}'></div>
                                </div>";
            }

            $html = '<div class="vehicle-info-sml">
                <div class="vehicle-img-wrapper">
                    '. $colourString .'
                    <img class="vehicle-img '. $imageClass .'" src="' . $image . '" title="'. $model .'" />
                </div>
                <table class="spec-table">
                    <tr>
                        <th>Make:</th>
                        <td>' . t($make) . '</td>
                    </tr>
                    <tr>
                        <th>Model:</th>
                        <td>' . t($model) . '</td>
                    </tr>
                    <tr>
                        <th>Fuel:</th>
                        <td>' . t($fuel) . '</td>
                    </tr>
                    <tr>
                        <th>Year:</th>
                        <td>' . t($year) . '</td>
                    </tr>
                    ' . $trimDetail . '
                    <tr>
                        <th>Reg:</th>
                        <td>' . t($reg) . '</td>
                    </tr>
                </table>
            </div>';
        }
        
        $responseData = [
            'html' => $html
        ];

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getVehicleValuations(array $data): HookResultTemplateInject
    {
        $responseData = [];
        $answers = $data['answers'];
        $answers = $this->sessionAnswerService->getAnswersByQuestionHandles(['whatVehicleDoYouHave', 'currentMileage'], $answers);

        if (
            array_key_exists('currentMileage', $answers)
            && array_key_exists('whatVehicleDoYouHave', $answers)
        ) {
            $hasCurrentMileageChanged = false;
            $getUpdatedCarData = false;
            $currentMileage = (int) $answers['currentMileage'][0]->getValue();
            $reg = (string) $answers['whatVehicleDoYouHave'][0]->getValue();
            $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
            $sessionKey = '';
            
            if ($session) {
                $sessionKey = $session->getId();
            }

            // Get car data from the cache
            $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');
            $carData = $apiCache->getData();

            $regDate = $carData['vehicleDetails']['firstRegistration']['firstRegistrationDate'];

            if (isset($carData['currentValuations']['valuations']) && $carData['currentValuations']['valuations'] !== null) {
                foreach ($carData['currentValuations']['valuations'] as $valuations) {
                    if (isset($valuations['valuationPoints'][0]['mileage'])) {
                        if ($valuations['valuationPoints'][0]['mileage'] !== $currentMileage) {

                            if (isset($_SESSION['KARFU_user']['temp']['currentMileage'])) {
                                $hasCurrentMileageChanged = true;
                                
                                // Get car data with updated valuations
                                $carData = $this->capData->getCarData($reg, $currentMileage, $regDate);
                            }

                            break;
                        }
                    }
                }
                unset($valuations);
            }

            if (isset($carData['currentValuations']['errors'])) {
                foreach ($carData['currentValuations']['errors'] as $curValErrors) {
                    if ($curValErrors['type'] === 'mileage_out_of_bounds') {

                        if (isset($curValErrors['detail']['minMileage'])) {
                            $minMileage = (int) $curValErrors['detail']['minMileage'];
                            $currentMileage = ($currentMileage < $minMileage) ? $minMileage : $currentMileage;
                        }
            
                        if (isset($curValErrors['detail']['maxMileage'])) {
                            $maxMileage = (int) $curValErrors['detail']['maxMileage'];
                            $currentMileage = ($currentMileage > $maxMileage) ? $maxMileage : $currentMileage;
                        }

                        $getUpdatedCarData = true;

                        break;
                    }
                }
                unset($curValErrors);
            }

            if ($hasCurrentMileageChanged === true && $getUpdatedCarData === false) {
                $getUpdatedCarData = true;
            }

            if ($getUpdatedCarData === true) {
                // Get car data with updated valuations
                $carData = $this->capData->getCarData($reg, $currentMileage, $regDate);

                $apiCache = new ApiCache();
                $apiCache->setSessionKey($sessionKey)
                    ->setService('cap-hpi')
                    ->setCall('vrms')
                    ->setData($carData);
            
                // Update cache
                $this->apiCacheService->deleteBySessionKeyServiceCall($apiCache);
                $this->apiCacheService->create($apiCache);
            }
            
            // Cap request we not use their DB value, so the valuation is adjusted (a little)
            $privateValuation = [
                (float) $carData['currentValuations']['valuations'][0]['valuationPoints'][0]['value'] * 1.001,
                (float) $carData['currentValuations']['valuations'][2]['valuationPoints'][0]['value'] * 1.001
            ];
            $cbsValuation = [
                (float) $privateValuation[0] - $privateValuation[0] / 10,
                (float) $privateValuation[1] - $privateValuation[1] / 10,
            ];
            $partExValuation = [
                (float) $privateValuation[0] - $privateValuation[0] / 20,
                (float) $privateValuation[1] - $privateValuation[1] / 20,
            ];

            $responseData = [
                'ps_estimated_value' => '£' . number_format($privateValuation[1]) . ' - £' . number_format($privateValuation[0]),
                'cbs_estimated_value' => '£' . number_format($cbsValuation[1]) . ' - £' . number_format($cbsValuation[0]),
                'pe_estimated_value' => '£' . number_format($partExValuation[1]) . ' - £' . number_format($partExValuation[0])
            ];
        } else {
            $responseData = [
                'ps_estimated_value' => '£0',
                'cbs_estimated_value' => '£0',
                'pe_estimated_value' => '£0'
            ];
        }

        $responseData['ps_company_logo'] = '<img src="/application/themes/KARFU/images/logo-green.svg" />';
        $responseData['cbs_company_logo'] = '<img src="/application/themes/KARFU/images/cbs-logo.png" />';
        $responseData['pe_company_logo'] = '<img src="/application/themes/KARFU/images/pe-logo.png" />';

        unset($_SESSION['KARFU_user']['temp']['currentMileage']);

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultRedirect
     */
    public function redirectToVehicleJourneyStart(array $data): HookResultRedirect
    {
        $answers = $data['answers'];
        $question = $data['question'];
        $journey = $data['journey'];
        $viewPath = $data['viewPath'];
        $journeyId = (int) $journey->getId();

        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
        $sessionKey = '';
        
        if ($session) {
            $sessionKey = $session->getId();
        }

        // Try and get options dynamically by session id
        $dynamicOptionMaps = $this->dynamicOptionMappingService->readBySessionKey($sessionKey);

        $optionEntity = Express::getObjectByHandle('option');
        $optionList = new EntryList($optionEntity);
        $query = $optionList->getQueryObject();

        foreach ($dynamicOptionMaps as $dynamicOptionMap) {
            $dataHandle = $dynamicOptionMap['option_data_handle'];
            $query->orWhere('ak_option_data_handle = "' . $dataHandle . '"');
        }

        $dynamicOptions = $optionList->getResults();

        if (count($dynamicOptions) === 1) {
            $dynamicOptionId = $dynamicOptions[0]->getId();

            $nextQuestion = $this->questionJourney->getNextQuestion($answers, $sessionKey, $question, $currentJourneyId);

            if ($nextQuestion !== null) {
                $questionId = $nextQuestion->getId();
                $url = $viewPath . '/question/' . $questionId;
            } else {
                $url = $viewPath . '/summary';
            }
        } else {
            $url = $viewPath;
        }

        $responseData = ['url' => $url];
        $hookResult = new HookResultRedirect();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultProgress
     */
    public function getProgress(array $data): HookResultProgress
    {
        $question = $data['question'];
        $answers = $data['answers'];
        $dynamicOptions = $data['dynamicOptions'];
        $resultData = [];
        $orders = [];

        // Get question options
        $options = $question->getOptions();
        $options = (!$options && $dynamicOptions) ? $dynamicOptions : null;

        if ($options) {
            // Get id's of all the question options
            $optionIds = array_map(function ($option) {
                return (int) $option->getId();
            }, $options);

            // Filter answers by the answers question dependency on the question options
            $filteredAnswers = array_filter($answers, function($answer) use ($optionIds, &$orders) {
                $question = $answer->getQuestion();

                if ($question) {
                    $assocOptions = $question->getAssociation('dependency_options');

                    if ($assocOptions) {
                        $optionEntries = $assocOptions->getSelectedEntries();

                        foreach ($optionEntries as $optionEntry) {
                            $optionId = (int) $optionEntry->getId();

                            // Key of the progress question option if exists
                            $key = array_search($optionId, $optionIds);

                            if ($key !== false) {
                                $questionId = (int) $question->getId();
                                $order = (int) $question->getOrder();

                                if (array_key_exists($optionId, $orders)) {
                                    if ($order > $orders[$optionId]['order']) {
                                        $orders[$optionIds[$key]] = [
                                            'id' => $questionId,
                                            'order' => $order
                                        ];
                                    }
                                } else {
                                    $orders[$optionIds[$key]] = [
                                        'id' => $questionId,
                                        'order' => $order
                                    ];
                                }

                                return true;
                            }
                        }
                    }
                }

                return false;
            });
            
            // Get the last chained dependency question answered for each progress option
            $newFilteredAnswers = [];
            foreach ($optionIds as $optionId) {
                foreach ($filteredAnswers as $filteredAnswer) {
                    $questionId = (int) $filteredAnswer->getQuestion()->getId();

                    if ($questionId === $orders[$optionId]['id']) {
                        $newFilteredAnswers[$optionId] = $filteredAnswer;
                    }
                }
            }

            $filteredAnswers = $newFilteredAnswers;
            unset($newFilteredAnswers);

            $classMethod = 'HookFunction::redirectToVehicleJourneyStart';
            foreach ($optionIds as $optionId) {
                if (array_key_exists($optionId, $filteredAnswers)) {
                    // Check if answer option has callback
                    if ($filteredAnswers[$optionId]->getQuestion()->getServerOnQuestionSubmit() === $classMethod) {
                        $resultData[] = [
                            'id' => $optionId,
                            'complete' => true
                        ];
                    } else {
                        $resultData[] = [
                            'id' => $optionId,
                            'complete' => false
                        ];
                    }
                } else {
                    $resultData[] = [
                        'id' => $optionId,
                        'complete' => false
                    ];
                }
            }
        }

        $hookResult = new HookResultProgress();
        $hookResult->setData($resultData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultAddOption
     */
    public function getCarManufacturerList(array $data): HookResultAddOption
    {
        $bodyStyles = [];
        $answers = $data['answers'];
        $answersByHandles = $this->sessionAnswerService->getAnswersByQuestionHandles(['whatSpecificTypeOfCarAreYouLookingFor'], $answers);

        if (count($answersByHandles) > 0 && array_key_exists('whatSpecificTypeOfCarAreYouLookingFor', $answersByHandles)) {
            $carTypeAnswers = $answersByHandles['whatSpecificTypeOfCarAreYouLookingFor'];
            $maps = $this->karfuAttributeMapService->readByMappingType('karfuBodyStyle');

            if ($maps !== false) {
                foreach ($carTypeAnswers as $carTypeAnswer) {
                    $option = $carTypeAnswer->getOption();
                    $value = $option->getOptionTitle();

                    foreach ($maps as $i => $map) {
                        if ($value === $map['attribute_name']) {
                            $key = $i;
                            break;
                        } else {
                            $key = false;
                        }
                    }

                    if ($key !== false) {
                        $bodyStyles[] = $key;
                    }
                }
            }
        }

        return $this->getManufacturerList($data, 'Car', $bodyStyles);
    }

    /**
     * @param array $data
     * 
     * @return HookResultAddOption
     */
    public function getBicycleManufacturerList(array $data): HookResultAddOption
    {
        return $this->getManufacturerList($data, 'Bicycle');
    }

    /**
     * @param array $data
     * 
     * @return HookResultAddOption
     */
    public function getScooterManufacturerList(array $data): HookResultAddOption
    {
        return $this->getManufacturerList($data, 'Kick / electric scooter');
    }

    /**
     * @param array $data
     * @param string $vehicleType
     * 
     * @return HookResultAddOption
     */
    private function getManufacturerList(array $data, string $vehicleType, array $bodyStyles = []): HookResultAddOption
    {
        $resultData = [];
        $hookResult = new HookResultAddOption();
        $vehicleManufacturer = $vehicleType . ' manufacturer';
        $query = 'SELECT partner, partner_logo FROM btPartnerManager';
        $whereOrs = [];
        $wheres = [
            'active = ?',
            'partner_type = ?'
        ];
        $bindings = [
            1,
            $vehicleManufacturer
        ];
        $orders = ['partner ASC'];

        foreach ($bodyStyles as $bodyStyle) {
            $whereOrs[] = 'FIND_IN_SET(?, body_styles)';
            $bindings[] = $bodyStyle;
        }

        $where .= ' WHERE ' . implode(' AND ', $wheres);
        if (count($whereOrs) > 0) {
            $where .= ' AND (' . implode(' OR ', $whereOrs) . ')';
        }

        $order = ' ORDER BY ' . implode(', ', $orders);
        $query .= $where . ' ' . $order;

        $results = $this->con->fetchAll($query, $bindings);

        if ($results) {

            foreach ($results as $result) {
                $file = File::getByID($result['partner_logo']);

                if ($file) {
                    $src = $file->getRelativePath();
                } else {
                    $src = '/application/themes/KARFU/images/icons/green_icons/service.svg';
                }

                $partner = strtoupper($result['partner']);

                $resultData[] = [
                    'type' => 'img',
                    'src' => $src,
                    'id' => $partner,
                    'title' => $partner,
                    'value' => $partner,
                    'defaultValue' => null,
                    'data' => '',
                    'exclusiveOption' => null,
                ];
            }
        }

        $hookResult->setData($resultData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultRedirect
     */
    public function redirectToNextOptionDependency(array $data): HookResultRedirect
    {
        $quesiton = $data['question'];
        $journey = $data['journey'];
        $viewPath = $data['viewPath'];
        $firstKey = key($data['chosenOptions']);
        $option = $data['chosenOptions'][$firstKey][0];
        $selectedOptionId = (int) $option->getId();
        $prevOrder = (int) $quesiton->getOrder();
        $redirectQuestion = null;

        $entity = Express::getObjectByHandle('question');
        $questionList = new EntryList($entity);
        $questionList->filterByAttribute('order', $prevOrder, '>');
        $questionList->filterByAttribute('question_type', 'Static', '=');
        $questionList->sortByOrder('desc');
        $questions = $questionList->getResults();
        
        foreach ($questions as $question) {
            $questionTitle = $question->getQuestionTitle();

            $assocOptions = $question->getAssociation('dependency_options');
            if ($assocOptions) {
                $optionEntries = $assocOptions->getSelectedEntries();

                foreach ($optionEntries as $option) {                    
                    if ($option->getId() === $selectedOptionId) {
                        $redirectQuestion = $question;
                        break;
                    }
                }
            }
        }

        $questionId = $redirectQuestion->getId();
        $resultData = [
            'url' => $viewPath . '/question/' . $questionId
        ];
        $hookResult = new HookResultRedirect();
        $hookResult->setData($resultData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getMonthlyBudget(array $data): HookResultTemplateInject
    {
        $answers = $data['answers'];
        $answer = $this->sessionAnswerService->getAnswerByQuestionHandle('whatIsYourAnnualGrossSalary', $answers);

        $grossIncome = (float) $answer->getValue();

        if ($grossIncome > 125000) {
            $personalAllowance = 0;
        } elseif ($grossIncome > 100000) {
            $personalAllowance = (12500 - 100000) / 2;
        } elseif ($grossIncome > 12500) {
            $personalAllowance = 12500;
        } else {
            $personalAllowance = $grossIncome;
        }

        $taxableIncome = $grossIncome - $personalAllowance;

        if ($grossIncome > 50000) {
            $taxOn20 = ((50000 - 12500) / 100) * 20;
        } elseif ($grossIncome > 12500) {
            $taxOn20 = (($grossIncome - 12500) / 100) * 20;
        } else {
            $taxOn20 = 0;
        }

        if ($grossIncome > 150000) {
            $taxOn40 = ((150000 - 50000) / 100) * 40;
        } elseif ($grossIncome > 50000) {
            $taxOn40 = (($grossIncome - 50000 - $personalAllowance + 12500) / 100) * 40;
        } else {
            $taxOn40 = 0;
        }

        if ($grossIncome > 150000) {
            $taxOn45 = (($grossIncome - 150000) / 100) * 45;
        } else {
            $taxOn45 = 0;
        }

        $incomeTaxDue = $taxOn20 + $taxOn40 + $taxOn45;
        $niContribution = 0;

        if ($grossIncome <= 8632) {
            $niContribution = 0;
        } elseif ($grossIncome <= 50024) {
            $niContribution = (($grossIncome - 8632) / 100) * 12;
        } else {
            $niContribution = (((50024 - 8632) / 100) * 12) + (($grossIncome - 50024) / 100) * 2;
        }

        $netIncome = $grossIncome - $incomeTaxDue - $niContribution;
        $monthlyNetIncome = $netIncome / 12;
        $ofNetSalary = ($monthlyNetIncome / 100) * 17.5;

        $responseData = [
            'net_salary' => '£' . number_format($monthlyNetIncome),
            'of_net_salary' => '£' . number_format($ofNetSalary)
        ];
        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }


    /**
     * @param array $data
     * 
     * @return array
     */
    public function estimateMileage(array $data)
    {
        $answers = $data['answers'];
        $answers = $this->sessionAnswerService->getAnswersByQuestionHandles(['howOften', 'journeyKind', 'whatIsYourEstimatedMileage'], $answers);

        if ($answers) {
            if (
                array_key_exists('howOften', $answers)
                && array_key_exists('journeyKind', $answers)
            )
            {
                $howOftenValues = [];
                foreach ($answers['howOften'] as $howOften) {
                    $howOftenValues[] = $howOften->getOption()->getOptionTitle();
                }

                $shortJourneys = (isset($answers['journeyKind'][0])) ? (int) $answers['journeyKind'][0]->getValue() : 0;
                $mediumJourneys = (isset($answers['journeyKind'][1])) ? (int) $answers['journeyKind'][1]->getValue() : 0;
                $longJourneys = (isset($answers['journeyKind'][2])) ? (int) $answers['journeyKind'][2]->getValue() : 0;
                $mileage = $this->vehicleFormula->calcEstimatedAnnualMileage($howOftenValues, $shortJourneys, $mediumJourneys, $longJourneys) / 2; // request to half the estimate (09/04)

                if (array_key_exists('whatIsYourEstimatedMileage', $answers)) {
                    $responseData = [
                        'mileage' => number_format($mileage)
                    ];
                } else {
                    $responseData = [
                        'mileage' => number_format($mileage),
                        'inputMileage' => round($mileage)
                    ];
                }
            }
        } else {
            $responseData = [
                'mileage' => number_format($mileage)
            ];
        }

        return $responseData;
    }


    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getEstimatedMileage(array $data): HookResultTemplateInject
    {
        $responseData = $this->estimateMileage($data);

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }


    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getEstimatedWeeklyMileage(array $data): HookResultTemplateInject
    {
        $responseData = $this->estimateMileage($data);

        $mileage = round($responseData['inputMileage']/52);

        $responseData['mileage'] = $mileage;
        $responseData['inputMileage'] = $mileage;
      
        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }


    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function getVehicleMileage(array $data): HookResultTemplateInject
    {
        $html = '';
        $mileage = 0;
        $dvsaError = true;
        $answers = $this->sessionAnswerService->getAnswersByQuestionHandles(['currentMileage', 'whatVehicleDoYouHave'], $data['answers']);

        if (array_key_exists('whatVehicleDoYouHave', $answers)) {
            $plate = $answers['whatVehicleDoYouHave'][0]->getValue();
            $dvsaData = $this->dvsaData->getDVSAData($plate);
            $milesPerDay = round(CONFIG_AVG_DOM_ANNUAL_MILEAGE / 365);
            $todaysDate = new DateTime('NOW');

            if ($dvsaData !== false && isset($dvsaData[0])) {

                if (isset($dvsaData[0]['motTests'])) {
                    $daysSinceLastMot = 0;

                    if (isset($dvsaData[0]['motTests'][0]['odometerValue'])) {
                        $dvsaError = false;
                        $mileage = (int) $dvsaData[0]['motTests'][0]['odometerValue'];
                        $html .= '<p>Your mileage from DVSA MOT data: ' . number_format($mileage) . '</p>';
                    }

                    if (isset($dvsaData[0]['motTests'][0]['completedDate'])) {
                        $dvsaError = false;
                        $lastMotDate = DateTime::createFromFormat('Y.m.d G:i:s', $dvsaData[0]['motTests'][0]['completedDate']);
                        $daysSinceLastMot = (int) $lastMotDate->diff($todaysDate)->format('%a');
                        $mileage += round($daysSinceLastMot * $milesPerDay);
                        $html .= '<p>Last MOT date: ' . $lastMotDate->format('d/m/Y') . '</p>';
                        $html .= '<p>Days since last MOT: ' . number_format($daysSinceLastMot) . '</p>';
                    }
                    
                } elseif (isset($dvsaData[0]['motTestExpiryDate'])) {
                    $dvsaError = false;
                    $motTestExpiryDate = new DateTime($dvsaData[0]['motTestExpiryDate']);
                    $motTestExpiryDate->sub(new DateInterval('P3Y'));
                    $ageInDays = (int) $todaysDate->diff($motTestExpiryDate)->format('%a');
                    $mileage = round($ageInDays * $milesPerDay);
                    $html .= '<p>Vehicle registration date: ' . $motTestExpiryDate->format('d/m/Y') . '</p>';
                    $html .= '<p>Days since vehicle registration: ' . number_format((int) $motTestExpiryDate->diff($todaysDate)->format('%a')) . '</p>';
                }
            } else {
                $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
                $sessionKey = '';
                
                if ($session) {
                    $sessionKey = $session->getId();
                }

                $ageInDays = 0;
                $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');
                $carData = $apiCache->getData();
                $year = $carData['vehicleDetails']['firstRegistration']['firstRegistrationDate'];
                if (isset($carData['vehicleDetails']['firstRegistration']['firstRegistrationDate'])) {
                    $registrationDate = DateTime::createFromFormat('Y-m-d', $carData['vehicleDetails']['firstRegistration']['firstRegistrationDate']);
                    $html .= '<p>Vehicle registration date: ' . $registrationDate->format('d/m/Y') . '</p>';
                    $ageInDays = (int) $todaysDate->diff($registrationDate)->format('%a');
                    $html .= '<p>Vehicle age in days: ' . number_format($ageInDays) . '</p>';
                }
                
                $mileage = round($ageInDays * $milesPerDay);
            }
        }

        $html .= '<p>Average UK daily mileage: ' . number_format($milesPerDay) . '</p><br />';
        $html .= '<p><b>Karfu’s mileage estimate for your vehicle: ' . number_format($mileage) . '</b></p><br />';
        $html .= '<p>If our estimate is inaccurate, please provide the current mileage in the box below</p>';

        if (array_key_exists('currentMileage', $answers)) {
            $mileage = (int) $answers['currentMileage'][0]->getValue();
        }

        $responseData = [
            'html' => $html,
            'currentMileage' => $mileage
        ];

        $hookResult = new HookResultTemplateInject();
        $hookResult->setData($responseData);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return HookResultTemplateInject
     */
    public function setVehicleMileageSession(array $data)
    {
        $answers = $data['answers'];
        $currentMileageAnswer = $this->sessionAnswerService->getAnswerByQuestionHandle('currentMileage', $answers);
        $currentMileage = (int) $currentMileageAnswer->getValue();

        // Set temporary session
        $_SESSION['KARFU_user']['temp']['currentMileage'] = $currentMileage;

        $hookResult = new HookResultResponse();
        $hookResult->setData([
            'success' => true,
            'errorMessage' => null
        ]);
        return $hookResult;
    }

    /**
     * @param array $data
     * 
     * @return array
     */
    public function getFilteredVehicles(array $data): array
    {
        $answers = $data['answers'];
        $responseData = [];
        $dataHandles = [];
        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
            
        if ($session) {
            $sessionKey = $session->getId();
        }

        $vehicleTypesCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'karfu', 'vehicle-type');

        if ($vehicleTypesCache === false) {
            $vehicleTypes = $this->karfuApiService->getVehicleTypes(false);
        } else {
            $vehicleTypes = $vehicleTypesCache->getData();
        }

        if ($vehicleTypes) {
            foreach ($vehicleTypes as $vehicleType) {
                switch ($vehicleType) {
                    case 'CAR':
                        $dataHandles[] = 'car';
                        break;
                    case 'VAN':
                        $dataHandles[] = 'van';
                        break;
                    case 'PICK-UP':
                        $dataHandles[] = 'pickupTruck';
                        break;
                    case 'MOTORCYCLE':
                        $dataHandles[] = 'motorcycle';
                        break;
                    case 'BICYCLE':
                        $dataHandles[] = 'bicycle';
                        break;
                    case 'MOPED OR MOTOR SCOOTER':
                        $dataHandles[] = 'mopedScooter';
                        break;
                    case 'KICK / ELECTRIC SCOOTER':
                        $dataHandles[] = 'kickElectricScooter';
                        break;
                    case 'ALTERNATIVES':
                        $dataHandles[] = 'alternative';
                        break;
                }
            }

            // Refresh available dynamic options
            $optionEntity = Express::getObjectByHandle('option');
            $optionList = new EntryList($optionEntity);
            $query = $optionList->getQueryObject();
            $this->dynamicOptionMappingService->deleteBySessionKey($sessionKey);
            $dynamicOptionMaps = [];
            foreach ($dataHandles as $dataHandle) {
                if ($session) {
                    $dynamicOptionMaps[] = $this->dynamicOptionMappingService->create($sessionKey, $dataHandle);
                }
                $query->orWhere('ak_option_data_handle = "' . $dataHandle . '"');
            }
            $options = $optionList->getResults();

            if (count($options) > 0) {
                $responseData = $options;
            }

            // Delete all answered questions for no longer existing dynamic options
            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandle('whichVehicleTypeDoYouWantToNarrowDownFirst?', $answers);
            $optionList = new EntryList($optionEntity);
            $query = $optionList->getQueryObject();

            foreach ($dynamicOptionMaps as $dynamicOptionMap) {
                $dataHandle = $dynamicOptionMap['option_data_handle'];
                $query->orWhere('ak_option_data_handle = "' . $dataHandle . '"');
            }            

            $dynamicOptions = $optionList->getResults();

            $answersToDelete = array_filter($tempAnswers, function ($answer) use ($dynamicOptions) {
                $option = $answer->getOption();

                if ($option) {
                    $optionId = $option->getId();

                    foreach ($dynamicOptions as $dynamicOption) {
                        $dOptionid = $dynamicOption->getId();

                        if ($dOptionid === $optionId) {
                            return false;
                        }
                    }
                }

                return true;
            });

            foreach ($answersToDelete as $answerToDelete) {
                $this->questionJourney->deleteAnswer($answers, $answerToDelete, true);
            }
        }

        $hookResult = new HookResultAddOption();
        $hookResult->setData($responseData);
        return [$hookResult];
    }
}
