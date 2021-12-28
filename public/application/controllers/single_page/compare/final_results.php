<?php

namespace Application\Controller\SinglePage\Compare;

use Application\Helper\Budget;
use Application\Helper\CapData;
use Application\Helper\CostCalculator\CostCalculator;
use Application\Helper\CostCalculator\CostCalculatorFactory;
use Application\Helper\MobilityTypeFilter;
use Application\Helper\Pagination;
use Application\Karfu\Journey\DefaultSort;
use Application\Karfu\Journey\SmartRedirect;
use Application\Service\KarfuAttributeMapService;
use Application\Service\SessionAnswerService;
use Application\Service\VehicleTempService;
use Application\Service\ScrapedVehicleContentService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Session\SessionValidator;
use Concrete\Core\Support\Facade\Express;
use Core;
use DateTime;
use Exception;
use Page;
use PageController;

class FinalResults extends PageController
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
     * @var CapData
     */
    private $capData;

    /**
     * @var ScrapedVehicleContentService
     */
    private $scrapedVehicleContentService;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var VehicleTempService
     */
    private $vehicleTempService;

    /**
     * @var MobilityTypeFilter
     */
    private $mobilityTypeFilter;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var CostCalculatorFactory
     */
    private $costCalculatorFactory;

    /**
     * @var Budget
     */
    private $budget;

    /**
     * @var SmartRedirect
     */
    private $smartRedirect;

    /**
     * @var DefaultSort
     */
    private $defaultSort;

    /**
     * @param $obj
     * @param Connection $con
     * @param SessionValidator $sessionValidator
     * @param CapData $capData
     * @param ScrapedVehicleContentService $scrapedVehicleContentService
     * @param SessionAnswerService $sessionAnswerService
     * @param VehicleTempService $vehicleTempService
     * @param MobilityTypeFilter $mobilityTypeFilter
     * @param KarfuAttributeMapService $karfuAttributeMapService
     * @param CostCalculatorFactory $costCalculatorFactory
     * @param SmartRedirect $smartRedirect
     * @param Budget $budget
     * @param DefaultSort $defaultSort
     */
    public function __construct(
        $obj = null,
        Connection $con,
        SessionValidator $sessionValidator,
        CapData $capData,
        ScrapedVehicleContentService $scrapedVehicleContentService,
        SessionAnswerService $sessionAnswerService,
        VehicleTempService $vehicleTempService,
        MobilityTypeFilter $mobilityTypeFilter,
        KarfuAttributeMapService $karfuAttributeMapService,
        CostCalculatorFactory $costCalculatorFactory,
        SmartRedirect $smartRedirect,
        Budget $budget,
        DefaultSort $defaultSort
    )
    {
        parent::__construct($obj);
        $this->con = $con;
        $this->sessionValidator = $sessionValidator;
        $this->capData = $capData;
        $this->scrapedVehicleContentService = $scrapedVehicleContentService;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->vehicleTempService = $vehicleTempService;
        $this->mobilityTypeFilter = $mobilityTypeFilter;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $this->costCalculatorFactory = $costCalculatorFactory;
        $this->smartRedirect = $smartRedirect;
        $this->budget = $budget;
        $this->defaultSort = $defaultSort;
    }

    /**
     * Concrete5 on_start hook
     */
    public function on_start()
    {
        // Check if user can view the page or needs to be redirected
        if (
            isset($_SESSION['KARFU_user']['currentJourneyType'])
            && isset($_SESSION['KARFU_user']['currentJourneyGroup'])
        ) {
            $currentJourneyType = $_SESSION['KARFU_user']['currentJourneyType'];
            $currentJourneyGroup = $_SESSION['KARFU_user']['currentJourneyGroup'];

            if (isset($_SESSION['KARFU_user']['completed'])) {

                // Get journeys by current journey type
                $list = new EntryList(Express::getObjectByHandle('journey'));
                $list->filterByAttribute('journey_type', $currentJourneyType, '=');
                $list->filterByAttribute('journey_group', $currentJourneyGroup, '=');
                $journeys = $list->getResults();

                // If journey type is Standard, check all sub journeys are complete
                if ($currentJourneyType === 'Standard') {
                    foreach ($journeys as $journey) {
                        $journeyOrder = (int) $journey->getJourneyOrder();
                        if (!in_array($journeyOrder, $_SESSION['KARFU_user']['completed'])) {
                            return $this->smartRedirect->redirectToLastAnsweredQuestion(
                                $journey->getJourneyTitle(),
                                $journey->getJourneyGroup()
                            );
                        }
                    }
                }
            } else {
                $this->redirect('/');
            }
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Concrete5 view hook
     */
    public function view()
    {
        $sessionKey = '';
        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;

        if ($session) {
            $sessionKey = $session->getId();
        }

        // Get answers
        $answers = $this->sessionAnswerService->getSessionAnswers(false, false);
        if (count($answers) > 0) {
            $filters = $this->getFiltersFromRequest($answers);
            $sorts = [$this->getSortFromRequest($answers)];

            // Get vehicle count
            $vehicleCount = $this->vehicleTempService->countBySessionKey($sessionKey, ['filters' => $filters]);

            if ($vehicleCount > 0) {

                // Get current page
                $currentPage = 1;
                if ($this->request->get('page') && is_numeric($this->request->get('page'))) {
                    $currentPage = (int) $this->request->get('page');
                }

                // Build pagination
                $pagination = new Pagination($vehicleCount, 10);
                $pagination->setCurrentPage($currentPage)
                    ->setMaxPrevPages(3)
                    ->setMaxNextPages(3);

                // Get vehicles
                $vehicles = $this->vehicleTempService->readBySessionKey(
                    $sessionKey,
                    [
                        'filters' => $filters,
                        'sorts' => $sorts,
                        'limit' => [
                            'offset' => $pagination->getOffset(),
                            'count' => $pagination->getCount()
                        ]
                    ]
                );

                // Build final vehicle list
                $vehiclesFinal = [];
                foreach ($vehicles as $vehicle) {
                    // Get mappings for vehicle & mobility types
                    $mobilityTypeMaps = $this->karfuAttributeMapService->readByMappingType('mobilityTypeToHr');
                    $vehicleTypeMap = $this->karfuAttributeMapService->mapToKarfuAttribute($vehicle['VehicleType']);

                    // Build query for partner
                    $wheres = [];
                    $bindings = [];
                    $wheres[] = 'active = ?';
                    $wheres[] = 'partner = ?';
                    $wheres[] = 'partner_type = ?';
                    $bindings[] = 1;
                    $bindings[] = $vehicle['ManName'];
                    $bindings[] = $vehicleTypeMap;
                    $where = implode(' AND ', $wheres);

                    // Get partner
                    $partner = $this->con->fetchAssoc('SELECT * FROM btPartnerManager WHERE ' . $where, $bindings);

                    // Set additional vehicle attributes
                    $vehicle['priceNew'] = $vehicle['Price'];
                    $vehicle['mobilityChoice'] = [
                        'name' => $vehicle['MobilityChoice'],
                        'type' => $vehicle['MobilityType'],
                        'typeHr' => $this->karfuAttributeMapService->mapNameFromList($vehicle['MobilityType'], $mobilityTypeMaps)
                    ];
                    $vehicle['provider'] = $partner['partner'];
                    $vehicle['postcode'] = null;
                    if (
                        $vehicle['mobilityChoice']['type'] === CostCalculator::OWNERSHIP_OUTRIGHT
                        || $vehicle['mobilityChoice']['type'] === CostCalculator::OWNERSHIP_HP
                    )
                    {
                        // Get scraped vehicle content
                        $carsForSale = $this->scrapedVehicleContentService->mapByFuelAndDerivative(
                            $vehicle['FuelType'],
                            $vehicle['Derivative']
                        );
            
                        // If scraped vehicle content found, emulate used vehicle
                        if ($carsForSale) {
                            $vehicle['Price'] = $carsForSale[0]['price'];
                            $vehicle['provider'] = $carsForSale[0]['data_provider'];
                            $vehicleProviderAddress = explode(',', $carsForSale[0]['dealer_address']);
                            $vehicle['postcode'] = $vehicleProviderAddress[count($vehicleProviderAddress) - 1];
                        }
                    }
                    $vehicle['partner'] = $partner;
                    $vehicle['totalCost'] = $vehicle['TotalCost'];
                    $vehicle['monthlyCost'] = $vehicle['TotalMonthlyCost'];
                    $vehiclesFinal[] = $vehicle;
                }

                $this->set('answers', $answers);
                $this->set('vehicles', $vehiclesFinal);
                $this->set('pagination', $pagination);
            }

            $this->set('vehicleCount', $vehicleCount);
            $this->set('scrapedVehicleContentService', $this->scrapedVehicleContentService);
            $this->set('saveRedirectUrl', '/compare/final_summary');
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Get query filters from the request
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function getFiltersFromRequest(array $answers): array
    {
        $filters = [];

        if (is_numeric($this->request->request('min-val'))) {
            $formFilters['min-val'] = (float) $this->request->request('min-val');
            $filters[] = [
                'column' => 'TotalCost',
                'operator' => '>=',
                'value' => $formFilters['min-val']
            ];
        }

        if (is_numeric($this->request->request('max-val'))) {
            $formFilters['max-val'] = (float) $this->request->request('max-val');
            $filters[] = [
                'column' => 'TotalCost',
                'operator' => '<=',
                'value' => $formFilters['max-val']
            ];
        }

        if (is_numeric($this->request->request('min-month-val'))) {
            $formFilters['min-month-val'] = (float) $this->request->request('min-month-val');
            $filters[] = [
                'column' => 'TotalMonthlyCost',
                'operator' => '>=',
                'value' => $formFilters['min-month-val']
            ];
        }

        if (is_numeric($this->request->request('max-month-val'))) {
            $formFilters['max-month-val'] = (float) $this->request->request('max-month-val');
            $filters[] = [
                'column' => 'TotalMonthlyCost',
                'operator' => '<=',
                'value' => $formFilters['max-month-val']
            ];
        }

        if (is_array($this->request->request('mobility-options'))) {
            $formFilters['mobility-options'] = $this->request->request('mobility-options');

            $mobilityTypes = $this->mobilityTypeFilter->getMobilityTypes($answers);
            $mobilityTypes = array_filter($mobilityTypes, function ($mobilityType) use ($formFilters) {
                return in_array($mobilityType, $formFilters['mobility-options']);
            });

            $ors = [];
            foreach ($mobilityTypes as $mobilityType) {
                $ors[] = [
                    'operator' => '=',
                    'value' => $mobilityType
                ];
            }

            $filters[] = [
                'column' => 'MobilityChoice',
                'or' => $ors
            ];
        }

        if (is_array($this->request->request('mobility-sub-options'))) {
            $formFilters['mobility-sub-options'] = $this->request->request('mobility-sub-options');
            $mobilityTypeMaps = $this->karfuAttributeMapService->readByMappingType('mobilityTypeToHr');

            $formFilters['mobility-sub-options'] = array_map(function ($mobilityType) use ($mobilityTypeMaps) {
                foreach ($mobilityTypeMaps as $mobilityTypeMap) {
                    if ($mobilityType === $mobilityTypeMap['attribute_name']) {
                        return $mobilityTypeMap['attribute_list'];
                    }
                }
            }, $formFilters['mobility-sub-options']);

            $ors = [];
            foreach ($formFilters['mobility-sub-options'] as $mobilityType) {
                $ors[] = [
                    'operator' => '=',
                    'value' => $mobilityType
                ];
            }

            $filters[] = [
                'column' => 'MobilityType',
                'or' => $ors
            ];
        }

        if (is_array($this->request->request('excluded-brands'))) {
            $formFilters['excluded-brands'] = $this->request->request('excluded-brands');

            foreach ($formFilters['excluded-brands'] as $excludedBrand) {
                $filters[] = [
                    'column' => 'ManName',
                    'operator' => '!=',
                    'value' => $excludedBrand
                ];
            }
        }

        if (is_array($this->request->request('vehicle-sub-types'))) {
            $formFilters['vehicle-sub-types'] = $this->request->request('vehicle-sub-types');

            $ors = [];
            foreach ($formFilters['vehicle-sub-types'] as $bodyStyle) {
                $ors[] = [
                    'operator' => '=',
                    'value' => $bodyStyle
                ];
            }
            $filters[] = [
                'column' => 'KarfuBodyStyle',
                'or' => $ors
            ];
        }

        if (is_numeric($this->request->request('min-emissions-val'))) {
            $formFilters['min-emissions-val'] = (float) $this->request->request('min-emissions-val');
            $filters[] = [
                'column' => 'CO2GKM',
                'operator' => '>=',
                'value' => $formFilters['min-emissions-val']
            ];
        }

        if (is_numeric($this->request->request('max-emissions-val'))) {
            $formFilters['max-emissions-val'] = (float) $this->request->request('max-emissions-val');
            $filters[] = [
                'column' => 'CO2GKM',
                'operator' => '<=',
                'value' => $formFilters['max-emissions-val']
            ];
        }

        if (is_numeric($this->request->request('min-env-val'))) {
            $formFilters['min-env-val'] = (float) $this->request->request('min-env-val');
            $filters[] = [
                'column' => 'EnviroImpact',
                'operator' => '>=',
                'value' => $formFilters['min-env-val']
            ];
        }

        if (is_numeric($this->request->request('max-env-val'))) {
            $formFilters['max-env-val'] = (float) $this->request->request('max-env-val');
            $filters[] = [
                'column' => 'EnviroImpact',
                'operator' => '<=',
                'value' => $formFilters['max-env-val']
            ];
        }

        if (is_numeric($this->request->request('min-mileage'))) {
            $formFilters['min-mileage'] = (float) $this->request->request('min-mileage');
            $filters[] = [
                'column' => 'CurrentMileage',
                'operator' => '>=',
                'value' => $formFilters['min-mileage']
            ];
        }

        if (is_numeric($this->request->request('max-mileage'))) {
            $formFilters['max-mileage'] = (float) $this->request->request('max-mileage');
            $filters[] = [
                'column' => 'CurrentMileage',
                'operator' => '<=',
                'value' => $formFilters['max-mileage']
            ];
        }

        if (is_numeric($this->request->request('min-distance'))) {
            $formFilters['min-distance'] = (float) $this->request->request('min-distance');
            $filters[] = [
                'column' => 'LocationDistance',
                'operator' => '>=',
                'value' => $formFilters['min-distance']
            ];
        }

        if (is_numeric($this->request->request('max-distance'))) {
            $formFilters['max-distance'] = (float) $this->request->request('max-distance');
            $filters[] = [
                'column' => 'LocationDistance',
                'operator' => '<=',
                'value' => $formFilters['max-distance']
            ];
        }

        if (is_array($this->request->request('vehicle-essentials'))) {
            $formFilters['vehicle-essentials'] = $this->request->request('vehicle-essentials');

            foreach ($formFilters['vehicle-essentials'] as $vehicleEssential) {
                switch (strtoupper($vehicleEssential)) {
                    case 'LOW CO2':
                        $filters[] = [
                            'column' => 'CO2GKM',
                            'operator' => '<',
                            'value' => 100
                        ];
                        break;
                    case '5 STAR SAFETY':
                        $filters[] = [
                            'column' => 'NCAPOverall',
                            'operator' => '>=',
                            'value' => 5
                        ];
                        break;
                    case 'LOW INSURANCE':
                        $filters[] = [
                            'column' => 'InsuranceGroup',
                            'operator' => '<',
                            'value' => 20
                        ];
                        break;
                    case 'MANUAL GEARBOX':
                        $filters[] = [
                            'column' => 'Transmission',
                            'operator' => '=',
                            'value' => 'MANUAL'
                        ];
                        break;
                    case 'TOWING CAPACITY':
                        $filters[] = [
                            'column' => 'TowingWeightBraked',
                            'operator' => '>',
                            'value' => 3000
                        ];
                        break;
                }
            }
        }

        if (is_array($this->request->request('vehicle-age'))) {
            $formFilters['vehicle-age'] = $this->request->request('vehicle-age');

            $ors = [];
            foreach ($formFilters['vehicle-age'] as $condition) {
                $ors[] = [
                    'operator' => '=',
                    'value' => $condition
                ];
            }
            $filters[] = [
                'column' => '`Condition`',
                'or' => $ors
            ];
        }

        if (is_numeric($this->request->request('min-vehicle-age-val'))) {
            $formFilters['min-vehicle-age-val'] = (int) $this->request->request('min-vehicle-age-val');
            
            if ($formFilters['min-vehicle-age-val'] > 0) {
                $today = new DateTime();
                $pastDate = $today->modify("-{$formFilters['min-vehicle-age-val']} year");

                $filters[] = [
                    'column' => 'RegistrationDate',
                    'operator' => '<=',
                    'value' => $pastDate->format('Y-m-d')
                ];
            }
        }

        if (is_numeric($this->request->request('max-vehicle-age-val'))) {
            $formFilters['max-vehicle-age-val'] = (int) $this->request->request('max-vehicle-age-val');

            if ($formFilters['max-vehicle-age-val'] > 0) {
                $today = new DateTime();
                $pastDate = $today->modify("-{$formFilters['max-vehicle-age-val']} year");

                $filters[] = [
                    'column' => 'RegistrationDate',
                    'operator' => '>=',
                    'value' => $pastDate->format('Y-m-d')
                ];
            }
        }

        if (is_array($this->request->request('fuel-types'))) {
            $formFilters['fuel-types'] = $this->request->request('fuel-types');
            $fuelTypeMaps = $this->karfuAttributeMapService->readByMappingType('karfuToCapFuelType');
            $ors = [];
            foreach ($formFilters['fuel-types'] as $fuelType) {
                foreach ($fuelTypeMaps as $fuelTypeMap) {
                    if ($fuelTypeMap['attribute_list'] === $fuelType) {
                        $ors[] = [
                            'operator' => '=',
                            'value' => $fuelTypeMap['attribute_name']
                        ];
                        break;
                    }
                }
            }

            $filters[] = [
                'column' => 'FuelType',
                'or' => $ors
            ];
        }

        if (is_numeric($this->request->request('no-of-seats'))) {
            $formFilters['no-of-seats'] = (int) $this->request->request('no-of-seats');
            $filters[] = [
                'column' => 'NumSeats',
                'operator' => '>=',
                'value' => $formFilters['no-of-seats']
            ];
        }

        return $filters;
    }

    /**
     * Get query sort from request
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function getSortFromRequest(array $answers): array
    {
        if ($this->request->get('sort') !== null) {
            return $this->vehicleTempService->mapSort($this->request->get('sort'));
        } else {
            return $this->defaultSort->getAsSortArray($answers);
        }
    }

    public function getFormVars()
    {
        echo '<h1>' . $_REQUEST['opt'] . ' -  ' . $_REQUEST['val'] . '</h1>';
    }

    /**
     * Get image path from vrm
     * 
     * @param string $vrm
     * 
     * @return string|void
     */
    public function getImageFromVRM($vrm) {
        $serverPath = "{$_SERVER['DOCUMENT_ROOT']}/application/files/vehicles/{$vrm}.jpg";

        if(!file_exists($serverPath)) {
            $imagePath = "/application/files/vehicles/{$vrm}.jpg";
            $this->capData->getImage($vrm, $serverPath, $imagePath);

            return $imagePath;
        }
    }
}
