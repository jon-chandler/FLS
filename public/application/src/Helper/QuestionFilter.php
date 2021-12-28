<?php

declare(strict_types = 1);

namespace Application\Helper;

use Application\Helper\SalaryCalculator;
use Application\Service\ApiCacheService;
use Application\Service\KarfuAttributeMapService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Session\SessionValidator;
use Core;

/**
 * QuestionFilter creates filters from question answers for the final results query
 */
class QuestionFilter
{
    /**
     * @var Connection
     */
    private $con;

    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var SalaryCalculator
     */
    private $salaryCalculator;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @param Connection $con
     * @param SessionValidator $sessionValidator
     * @param ApiCacheService $apiCacheService
     * @param KarfuAttributeMapService $karfuAttributeMapService
     * @param SalaryCalculator $salaryCalculator
     * @param SessionAnswerService $sessionAnswerService
    */
    public function __construct(
        Connection $con,
        SessionValidator $sessionValidator,
        ApiCacheService $apiCacheService,
        KarfuAttributeMapService $karfuAttributeMapService,
        SalaryCalculator $salaryCalculator,
        SessionAnswerService $sessionAnswerService
    )
    {
        $this->con = $con;
        $this->sessionValidator = $sessionValidator;
        $this->apiCacheService = $apiCacheService;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $this->salaryCalculator = $salaryCalculator;
        $this->sessionAnswerService = $sessionAnswerService;

        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
        $this->sessionKey = ($session) ? $session->getId() : '';
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Generate the main filter
     * 
     * @param array $answers
     * 
     * @return void
     */
    public function generateFilters(array $answers)
    {
        $filters = [];
        $sorts = [];
        $ors = [];
        $service = 'karfu';
        $call = 'vehicle-type';

        // Get vehicles types from the api cache table
        $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($this->sessionKey, $service, $call);

        if ($apiCache) {
            $vehicleTypes = $apiCache->getData();
            $maps = $this->karfuAttributeMapService->mapToKarfuAttributes($vehicleTypes, 'apiToVehicleTable');

            // Loop through each available vehicle type and get filters
            if (count($maps) > 0) {
                foreach ($maps as $map) {
                    switch ($map['attribute_name']) {
                        case 'C':
                            $ors[] = $this->generateCFilters($answers, $map['attribute_name']);
                            break;
                        case 'B':
                            $ors[] = $this->generateBFilters($answers, $map['attribute_name']);
                            break;
                        case 'K-ES':
                            $ors[] = $this->generateKESFilters($answers, $map['attribute_name']);
                            break;
                    }
                }

                $filters[] = ['or' => $ors];
            }
        }

        $this->filters = [
            'filters' => $filters,
            'sorts' => $sorts
        ];
    }

    /**
     * @param array $mobilityFilters
     * 
     * @return void
     */
    public function addMobilityFilters(array $mobilityFilters)
    {
        if (count($mobilityFilters) > 0 && array_key_exists('filters', $this->filters)) {
            $ors = [];

            foreach ($mobilityFilters as $mobilityFilter) {
                $ors[] = [
                    'operator' => '=',
                    'value' => $mobilityFilter
                ];
            }

            $this->filters['filters'][0]['or'][] = [[
                'column' => 'MobilityChoice',
                'or' => $ors
            ]];
        }
    }

    /**
     * Map form input to query sort
     * TODO: This should live somewhere else
     * 
     * @param string $value
     */
    public function mapSort(string $value)
    {
        switch ($value) {
            case 'Monthly Price HIGH':
                $sort = [
                    'column' => 'TotalMonthlyCost',
                    'ascDesc' => 'DESC'
                ];
                break;
            case 'Monthly Price LOW':
                $sort = [
                    'column' => 'TotalMonthlyCost',
                    'ascDesc' => 'ASC'
                ];
                break;
            case 'TCU HIGH':
                $sort = [
                    'column' => 'TotalCost',
                    'ascDesc' => 'DESC'
                ];
                break;
            case 'TCU LOW':
                $sort = [
                    'column' => 'TotalCost',
                    'ascDesc' => 'ASC'
                ];
                break;
            case 'Enviro Impact HIGH':
                $sort = [
                    'column' => 'EnviroImpact',
                    'ascDesc' => 'DESC'
                ];
                break;
            case 'Enviro Impact LOW':
                $sort = [
                    'column' => 'EnviroImpact',
                    'ascDesc' => 'ASC'
                ];
                break;
            case 'CO2 Emissions HIGH':
                $sort = [
                    'column' => 'CO2GKM',
                    'ascDesc' => 'DESC'
                ];
            break;
            case 'CO2 Emissions LOW':
                $sort = [
                    'column' => 'CO2GKM',
                    'ascDesc' => 'ASC'
                ];
                break;
            default:
                $sort = [];
        }

        if (count($sort) > 0) {
            $this->filters['sorts'][] = $sort;
        }
    }

    /**
     * Generate Car filters
     * 
     * @param array $answers
     * @param string $vehicleType
     * 
     * @return array
     */
    private function generateCFilters(array $answers, string $vehicleType): array
    {
        $filters = [];

        $createVehicleTypeFilter = $this->createVehicleTypeFilter($vehicleType);
        if (count($createVehicleTypeFilter) > 0) {
            $filters[] = $createVehicleTypeFilter;
        }

        $createManufacturerNameFilter = $this->createManufacturerNameFilter($answers, 'carDoYouHaveAnyBrandPreference');
        if (count($createManufacturerNameFilter) > 0 ) {
            $filters = array_merge($filters, $createManufacturerNameFilter);
        }

        $createKarfuBodyStyleTypeFilter = $this->createKarfuBodyStyleTypeFilter($answers, 'whatSpecificTypeOfCarAreYouLookingFor');
        if (count($createKarfuBodyStyleTypeFilter) > 0) {
            $filters[] = $createKarfuBodyStyleTypeFilter;
        }

        $createFuelTypeFilter = $this->createFuelTypeFilter($answers, 'whatsYourPreferenceForFuel');
        if (count($createFuelTypeFilter) > 0) {
            $filters[] = $createFuelTypeFilter;
        }

        $createNumSeatsFilter = $this->createNumSeatsFilter($answers);
        if (count($createNumSeatsFilter) > 0) {
            $filters[] = $createNumSeatsFilter;
        }

        $essentialOrs = [];

        $createTowingFilter = $this->createTowingFilter($answers);
        if (count($createTowingFilter) > 0) {
            $essentialOrs[] = $createTowingFilter;
        }

        $createNcapOverallFilter = $this->createNcapOverallFilter($answers);
        if (count($createNcapOverallFilter) > 0) {
            $essentialOrs[] = $createNcapOverallFilter;
        }

        $createInsuranceGroupFilter = $this->createInsuranceGroupFilter($answers);
        if (count($createInsuranceGroupFilter) > 0) {
            $essentialOrs[] = $createInsuranceGroupFilter;
        }

        $createTransmissionFilter = $this->createTransmissionFilter($answers);
        if (count($createTransmissionFilter) > 0) {
            $essentialOrs[] = $createTransmissionFilter;
        }

        $createCo2Filter = $this->createCo2Filter($answers);
        if (count($createCo2Filter) > 0) {
            $essentialOrs[] = $createCo2Filter;
        }

        $createHighMpgFilter = $this->createHighMpgFilter($answers);
        if (count($createHighMpgFilter) > 0) {
            $essentialOrs[] = $createHighMpgFilter;
        }

        $createLowTaxFilter = $this->createLowTaxFilter($answers);
        if (count($createLowTaxFilter) > 0) {
            $essentialOrs[] = $createLowTaxFilter;
        }

        if (count($essentialOrs) > 0) {
            $filters[] = [
                'or' => $essentialOrs
            ];
        }

        // $createDoorPlanFilter = $this->createDoorPlanFilter($answers);
        // if (count($createDoorPlanFilter) > 0) {
        //     $filters[] = $createDoorPlanFilter;
        // }

        return $filters;
    }

    /**
     * Generate Bicycle filters
     * 
     * @param array $answers
     * @param string $vehicleType
     * 
     * @return array
     */
    private function generateBFilters(array $answers, string $vehicleType): array
    {
        $filters = [];

        $createVehicleTypeFilter = $this->createVehicleTypeFilter($vehicleType);
        if (count($createVehicleTypeFilter) > 0) {
            $filters[] = $createVehicleTypeFilter;
        }

        $createManufacturerNameFilter = $this->createManufacturerNameFilter($answers, 'bicycleDoYouHaveAnyBrandPreference');
        if (count($createManufacturerNameFilter) > 0 ) {
            $filters = array_merge($filters, $createManufacturerNameFilter);
        }

        $createBodyStyleTypeFilter = $this->createBodyStyleTypeFilter($answers, 'whatSpecificTypeOfBicycle');
        if (count($createBodyStyleTypeFilter) > 0) {
            $filters[] = $createBodyStyleTypeFilter;
        }

        $createFuelTypeFilter = $this->createFuelTypeFilter($answers, 'howIsYourBicyclePowered');
        if (count($createFuelTypeFilter) > 0) {
            $filters[] = $createFuelTypeFilter;
        }

        $createAudienceFilter = $this->createAudienceFilter($answers);
        if (count($createAudienceFilter) > 0) {
            $filters[] = $createAudienceFilter;
        }

        $createBodyHeightMinFilter = $this->createBodyHeightMinFilter($answers);
        if (count($createBodyHeightMinFilter) > 0) {
            $filters[] = $createBodyHeightMinFilter;
        }

        $createBodyHeightMaxFilter = $this->createBodyHeightMaxFilter($answers);
        if (count($createBodyHeightMaxFilter) > 0) {
            $filters[] = $createBodyHeightMaxFilter;
        }

        return $filters;
    }
    
    /**
     * Generate Kick / Electric Scooter filters
     * 
     * @param array $answers
     * @param string $vehicleType
     * 
     * @return array
     */
    private function generateKESFilters(array $answers, string $vehicleType): array
    {
        $filters = [];

        $createVehicleTypeFilter = $this->createVehicleTypeFilter($vehicleType);
        if (count($createVehicleTypeFilter) > 0) {
            $filters[] = $createVehicleTypeFilter;
        }

        $createManufacturerNameFilter = $this->createManufacturerNameFilter($answers, 'scooterDoYouHaveAnyBrandPreference');
        if (count($createManufacturerNameFilter) > 0 ) {
            $filters = array_merge($filters, $createManufacturerNameFilter);
        }

        $createBodyStyleTypeFilter = $this->createBodyStyleTypeFilter($answers, 'whatSpecificTypeOfScooter');
        if (count($createBodyStyleTypeFilter) > 0) {
            $filters[] = $createBodyStyleTypeFilter;
        }

        $createFuelTypeFilter = $this->createFuelTypeFilter($answers, 'howIsYourScooterPowered');
        if (count($createFuelTypeFilter) > 0) {
            $filters[] = $createFuelTypeFilter;
        }

        // $createMaxRiderWeightFilter = $this->createMaxRiderWeightFilter($answers);
        // if (count($createMaxRiderWeightFilter) > 0) {
        //     $filters[] = $createMaxRiderWeightFilter;
        // }

        return $filters;
    }

    /**
     * Create vehicle type filter
     * 
     * @param string $vehicleType
     * 
     * @return array
     */
    private function createVehicleTypeFilter(string $vehicleType): array
    {
        return [
            'column' => 'VehicleType',
            'operator' => '=',
            'value' => $vehicleType
        ];
    }

    /**
     * Create body style type filter
     * 
     * @param array $answers
     * @param string $questionHandle
     * 
     * @return array
     */
    private function createBodyStyleTypeFilter(array $answers, string $questionHandle): array
    {
        $returnData = [];
        $mapAttributes = [];
        $answersByHandles = $this->sessionAnswerService->getAnswersByQuestionHandles(
            [$questionHandle],
            $answers
        );

        if (count($answersByHandles) > 0) {
            foreach ($answersByHandles as $answersByHandle) {
                foreach ($answersByHandle as $typeAnswer) {
                    $option = $typeAnswer->getOption();
                    $mapAttributes[] = $option->getOptionTitle();
                }
            }

            $maps = $this->karfuAttributeMapService->mapFromKarfuAttributes($mapAttributes, 'karfuBodyStyle');

            if (count($maps) > 0) {
                foreach ($maps as $map) {
                    $explodedMaps = explode(',', $map['attribute_list']);
                    foreach ($explodedMaps as $explodedMap) {
                        $or[] = [
                            'operator' => '=',
                            'value' => $explodedMap
                        ];
                    }
                }

                $returnData = [
                    'column' => 'BodyStyle',
                    'or' => $or
                ];
            }
        }

        return $returnData;
    }

    /**
     * Create karfu body style type filter
     * 
     * @param array $answers
     * @param string $questionHandle
     * 
     * @return array
     */
    private function createKarfuBodyStyleTypeFilter(array $answers, string $questionHandle): array
    {
        $returnData = [];
        $answersByHandles = $this->sessionAnswerService->getAnswersByQuestionHandles(
            [$questionHandle],
            $answers
        );

        if (count($answersByHandles) > 0) {
            foreach ($answersByHandles as $answersByHandle) {
                foreach ($answersByHandle as $typeAnswer) {
                    $option = $typeAnswer->getOption();
                    $or[] = [
                        'operator' => '=',
                        'value' => $option->getOptionTitle()
                    ];
                }
            }

            if (count($or) > 0) {
                $returnData = [
                    'column' => 'KarfuBodyStyle',
                    'or' => $or
                ];
            }
        }

        return $returnData;
    }

    /**
     * Create manufacturer name filter
     * 
     * @param array $answers
     * @param string $questionHandle
     * 
     * @return array
     */
    private function createManufacturerNameFilter(array $answers, string $questionHandle): array
    {
        $returnData = [];

        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            [$questionHandle],
            $answers
        );

        if (count($filteredAnswers) > 0) {

            foreach ($filteredAnswers as $answers) {

                foreach ($answers as $answer) {
                    $option = $answer->getOption();
                    $value = $answer->getValue();

                    if (!$option && $value) {
                        $returnData[] = [
                            'column' => 'ManName',
                            'operator' => '!=',
                            'value' => $value
                        ];
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * Create fuel type filter
     * 
     * @param array $answers
     * @param string $questionHandle
     * 
     * @return array
     */
    private function createFuelTypeFilter(array $answers, string $questionHandle): array
    {
        $returnData = [];

        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            [$questionHandle],
            $answers
        );

        $mapFuelTypes = $this->karfuAttributeMapService->readByMappingType('karfuToCapFuelType');

        if (count($filteredAnswers) > 0) {
            $or = [];
            foreach ($filteredAnswers as $answers) {
                foreach ($answers as $answer) {
                    $option = $answer->getOption();
                    $value = null;

                    if ($option) {
                        $value = $option->getOptionTitle();

                        if ($mapFuelTypes) {
                            foreach ($mapFuelTypes as $mapFuelType) {
                                if ($mapFuelType['attribute_list'] === $value) {
                                    $value = $mapFuelType['attribute_name'];
                                    break;
                                }
                            }
                        }
                    }

                    if ($value) {
                        $or[] = [
                            'operator' => '=',
                            'value' => $value
                        ];
                    }
                }
            }

            $returnData = [
                'column' => 'FuelType',
                'or' => $or
            ];
        }

        return $returnData;
    }

    /**
     * Create car number of seats filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createNumSeatsFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswer = $this->sessionAnswerService->getAnswerByQuestionHandle(
            'howManySeatsDoYouNeed',
            $answers
        );

        if ($filteredAnswer) {
            $value = $filteredAnswer->getValue();

            if (is_numeric($value)) {
                $value = (int) $value;

                $returnData = [
                    'column' => 'NumSeats',
                    'operator' => '>=',
                    'value' => $value
                ];
            }
        }

        return $returnData;
    }

    /**
     * Create price filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createPriceFilter(array $answers): array
    {
        $returnData = [];

        if (count($answers) > 0) {
            $totalSpend = 0;
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

            $whatIsYourMonthlyBudget = $filteredAnswers['whatIsYourMonthlyBudgetForThisSolution'][0];
            $howLongTerm = $filteredAnswers['howLongTerm'][0];
            $howMuchDoYouHave = $filteredAnswers['howMuchDoYouHave'][0];
            $vehicle = $filteredAnswers['whatVehicleDoYouHave'][0];
            $vehicleValuation = $filteredAnswers['yourVehicleValuation'][0];
            $whatIsYourAnnualGrossSalary = $filteredAnswers['whatIsYourAnnualGrossSalary'][0];

            if ($whatIsYourMonthlyBudget) {
                $monthlyBudget = (float) $whatIsYourMonthlyBudget->getValue();
                $howLong = (int) $howLongTerm->getValue();
                $totalSpend = $monthlyBudget * ($howLong * 12);
            } 
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
            if ($howMuchDoYouHave) {
                $value = $howMuchDoYouHave->getValue();
                if ($value) {
                    $totalSpend += (float) $value;
                }
            }
            if ($vehicle && $vehicleValuation) {
                $reg = $vehicle->getValue();

                // Get car data from the cache
                $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($this->sessionKey, 'cap-hpi', 'vrms');
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

            if ($totalSpend > 0) {

                $to = $totalSpend;
                $from = $totalSpend - $totalSpend * (5 / 100);

                $returnData = [
                    'column' => 'Price',
                    'operator' => '>=<',
                    'value' => [
                        $from,
                        $to
                    ]
                ];
            }
        }

        return $returnData;
    }

    /**
     * Create car CO2 filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createCo2Filter(array $answers): array
    {
        $returnData = [];

        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            ['whatAreYourEessentialsThatYouWantFromACar'],
            $answers
        );

        if (count($filteredAnswers) > 0 && array_key_exists('whatAreYourEessentialsThatYouWantFromACar', $filteredAnswers)) {
            $essentialAnswers = $filteredAnswers['whatAreYourEessentialsThatYouWantFromACar'];

            foreach ($essentialAnswers as $essentialAnswer) {
                $option = $essentialAnswer->getOption();

                if ($option) {
                    $value = $option->getOptionTitle();

                    if (strtoupper($value) === 'LOW CO2') {
                        $returnData = [
                            'column' => 'CO2GKM',
                            'operator' => '<',
                            'value' => 100
                        ];

                        break;
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * Create car towing capacity filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createTowingFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            ['whatAreYourEessentialsThatYouWantFromACar'],
            $answers
        );

        if (count($filteredAnswers) > 0 && array_key_exists('whatAreYourEessentialsThatYouWantFromACar', $filteredAnswers)) {
            $essentialAnswers = $filteredAnswers['whatAreYourEessentialsThatYouWantFromACar'];

            foreach ($essentialAnswers as $essentialAnswer) {
                $option = $essentialAnswer->getOption();

                if ($option) {
                    $value = $option->getOptionTitle();

                    if (strtoupper($value) === 'TOWING CAPACITY') {
                        $returnData = [
                            'column' => 'TowingWeightBraked',
                            'operator' => '>',
                            'value' => 3000
                        ];

                        break;
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * Create car NCAP overall safety filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createNcapOverallFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            ['whatAreYourEessentialsThatYouWantFromACar'],
            $answers
        );

        if (count($filteredAnswers) > 0 && array_key_exists('whatAreYourEessentialsThatYouWantFromACar', $filteredAnswers)) {
            $essentialAnswers = $filteredAnswers['whatAreYourEessentialsThatYouWantFromACar'];

            foreach ($essentialAnswers as $essentialAnswer) {
                $option = $essentialAnswer->getOption();

                if ($option) {
                    $value = $option->getOptionTitle();

                    if (strtoupper($value) === '5 STAR SAFETY') {
                        $returnData = [
                            'column' => 'NCAPOverall',
                            'operator' => '>=',
                            'value' => 5
                        ];

                        break;
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * Create car low insurance group filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createInsuranceGroupFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            ['whatAreYourEessentialsThatYouWantFromACar'],
            $answers
        );

        if (count($filteredAnswers) > 0 && array_key_exists('whatAreYourEessentialsThatYouWantFromACar', $filteredAnswers)) {
            $essentialAnswers = $filteredAnswers['whatAreYourEessentialsThatYouWantFromACar'];

            foreach ($essentialAnswers as $essentialAnswer) {
                $option = $essentialAnswer->getOption();

                if ($option) {
                    $value = $option->getOptionTitle();

                    if (strtoupper($value) === 'LOW INSURANCE') {
                        $returnData = [
                            'column' => 'InsuranceGroup',
                            'operator' => '<',
                            'value' => 20
                        ];

                        break;
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * Create car manual transmission filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createTransmissionFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            ['whatAreYourEessentialsThatYouWantFromACar'],
            $answers
        );

        if (count($filteredAnswers) > 0 && array_key_exists('whatAreYourEessentialsThatYouWantFromACar', $filteredAnswers)) {
            $essentialAnswers = $filteredAnswers['whatAreYourEessentialsThatYouWantFromACar'];

            foreach ($essentialAnswers as $essentialAnswer) {
                $option = $essentialAnswer->getOption();

                if ($option) {
                    $value = $option->getOptionTitle();

                    if (strtoupper($value) === 'MANUAL GEARBOX') {
                        $returnData = [
                            'column' => 'Transmission',
                            'operator' => '=',
                            'value' => 'MANUAL'
                        ];

                        break;
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * Create car high mpg filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createHighMpgFilter(array $answers): array
    {
        $returnData = [];
        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            ['whatAreYourEessentialsThatYouWantFromACar'],
            $answers
        );

        if (count($filteredAnswers) > 0 && array_key_exists('whatAreYourEessentialsThatYouWantFromACar', $filteredAnswers)) {
            $essentialAnswers = $filteredAnswers['whatAreYourEessentialsThatYouWantFromACar'];

            foreach ($essentialAnswers as $essentialAnswer) {
                $option = $essentialAnswer->getOption();

                if ($option) {
                    $value = $option->getOptionTitle();

                    if (strtoupper($value) === 'HIGH MPG') {
                        $returnData = [
                            'column' => 'CombinedMPG',
                            'operator' => '>',
                            'value' => 55
                        ];

                        break;
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * Create car low vehicle tax filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createLowTaxFilter(array $answers): array
    {
        $returnData = [];
        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            ['whatAreYourEessentialsThatYouWantFromACar'],
            $answers
        );

        if (count($filteredAnswers) > 0 && array_key_exists('whatAreYourEessentialsThatYouWantFromACar', $filteredAnswers)) {
            $essentialAnswers = $filteredAnswers['whatAreYourEessentialsThatYouWantFromACar'];

            foreach ($essentialAnswers as $essentialAnswer) {
                $option = $essentialAnswer->getOption();

                if ($option) {
                    $value = $option->getOptionTitle();

                    if (strtoupper($value) === 'LOW TAX') {
                        $returnData = [
                            'column' => 'CO2GKM',
                            'operator' => '<',
                            'value' => 100
                        ];

                        break;
                    }
                }
            }
        }

        return $returnData;
    }

    /**
     * Create car door plan filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createDoorPlanFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            [
                'mobilityNeeds'
            ],
            $answers
        );

        if (count($filteredAnswers) > 0) {
            $or = [];

            if (array_key_exists('mobilityNeeds', $filteredAnswers)) {
                $answers = $filteredAnswers['mobilityNeeds'];
                $filterValues = [];

                foreach ($answers as $answer) {
                    $option = $answer->getOption();

                    if ($option) {
                        $value = $option->getOptionTitle();

                        switch ($value) {
                            case 'Family / Household Use':
                                $filterValues = [4, 5, 6, 7];
                                break;
                            case 'Carrying large items':
                                $filterValues = [5, 6, 7];
                                break;
                        }
                    }
                }

                if (count($filterValues) > 0) {

                    $filterValues = array_unique($filterValues);

                    foreach ($filterValues as $filterValue) {
                        $or[] = [
                            'operator' => 'like%',
                            'value' => $filterValue
                        ];
                    }
                }
            }

            if (count($or) > 0) {
                $returnData = [
                    'column' => 'NumDoors',
                    'or' => $or
                ];
            }
        }

        return $returnData;
    }

    /**
     * Create bicycle audience filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createAudienceFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswer = $this->sessionAnswerService->getAnswerByQuestionHandle(
            'whatFrameTypeSuitsYouBest',
            $answers
        );

        if ($filteredAnswer) {
            $option = $filteredAnswer->getOption();

            if ($option) {
                $value = $option->getOptionTitle();
                $returnData = [
                    'column' => 'Audience',
                    'operator' => '=',
                    'value' => $value
                ];
            }
        }

        return $returnData;
    }

    /**
     * Create bicycle minimum body height filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createBodyHeightMinFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswer = $this->sessionAnswerService->getAnswerByQuestionHandle(
            'whatsYourHeight',
            $answers
        );

        if ($filteredAnswer) {
            $value = $filteredAnswer->getValue();

            if (is_numeric($value)) {
                $value = (float) $value;
                $returnData = [
                    'column' => 'BodyHeightMinCM',
                    'operator' => '<=',
                    'value' => $value
                ];
            }
        }

        return $returnData;
    }

    /**
     * Create bicycle minimum body height filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createBodyHeightMaxFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswer = $this->sessionAnswerService->getAnswerByQuestionHandle(
            'whatsYourHeight',
            $answers
        );

        if ($filteredAnswer) {
            $value = $filteredAnswer->getValue();

            if (is_numeric($value)) {
                $value = (float) $value;
                $returnData = [
                    'column' => 'BodyHeightMaxCM',
                    'operator' => '>=',
                    'value' => $value
                ];
            }
        }

        return $returnData;
    }

    /**
     * Create bicycle maximum rider weight filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createMaxRiderWeightFilter(array $answers): array
    {
        $returnData = [];

        $filteredAnswer = $this->sessionAnswerService->getAnswerByQuestionHandle(
            'whatsYourWeight',
            $answers
        );

        if ($filteredAnswer) {
            // [\d -]+
            // [^0-9.-]
            $option = $filteredAnswer->getOption();

            if ($option) {
                $value = $option->getOptionTitle();
                $foo = preg_replace('[^0-9.-]', '', $value);
                explode('-', $foo);
                $returnData = [
                    'column' => 'MaxRiderWeightKG',
                    'operator' => '<=',
                    'value' => $value
                ];
            }
        }

        return $returnData;
    }
}
