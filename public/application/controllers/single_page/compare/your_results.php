<?php

namespace Application\Controller\SinglePage\Compare;

use Application\Helper\CapData;
use Application\Helper\CostCalculator\CostCalculator;
use Application\Helper\MobilityTypeFilter;
use Application\Karfu\Journey\DefaultSort;
use Application\Model\JourneyUserSession;
use Application\Service\KarfuAttributeMapService;
use Application\Service\SessionAnswerService;
use Application\Service\VehicleTempService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Express\EntryList;
use Concrete\Core\Session\SessionValidator;
use Concrete\Core\Support\Facade\Express;
use Core;
use Page;
use PageController;

class YourResults extends PageController
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
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var VehicleTempService
     */
    private $vehicleTempService;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var MobilityTypeFilter
     */
    private $mobilityTypeFilter;

    /**
     * @var CapData
     */
    private $capData;

    /**
     * @var DefaultSort
     */
    private $defaultSort;

    /**
     * @param $obj
     * @param Connection $con
     * @param SessionValidator $sessionValidator
     * @param SessionAnswerService $sessionAnswerService
     * @param VehicleTempService $vehicleTempService
     * @param KarfuAttributeMapService $karfuAttributeMapService
     * @param MobilityTypeFilter $mobilityTypeFilter
     * @param CapData $capData
     * @param DefaultSort $defaultSort
     */
    public function __construct(
        $obj = null,
        Connection $con,
        SessionValidator $sessionValidator,
        SessionAnswerService $sessionAnswerService,
        VehicleTempService $vehicleTempService,
        KarfuAttributeMapService $karfuAttributeMapService,
        MobilityTypeFilter $mobilityTypeFilter,
        CapData $capData,
        DefaultSort $defaultSort
    )
    {
        parent::__construct($obj);
        $this->con = $con;
        $this->sessionValidator = $sessionValidator;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->vehicleTempService = $vehicleTempService;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $this->mobilityTypeFilter = $mobilityTypeFilter;
        $this->capData = $capData;
        $this->defaultSort = $defaultSort;
    }

    /**
     * Concrete5 view hook
     */
    public function view()
    {
        // Get view mode
        $view = ($this->request->request('view') !== null) ? $this->request->request('view') : 'mobility';

        // Get session key
        $sessionKey = '';
        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
        if ($session) {
            $sessionKey = $session->getId();
        }

        // Get answers
        $answers = $this->sessionAnswerService->getSessionAnswers(false, false);

        if (count($answers) > 0) {
            // Get answers for questions
            $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(['whatIsYourEstimatedMileage'], $answers);

            // Get mobility types
            $mobilityTypes = $this->mobilityTypeFilter->getMobilityTypes($answers);

            // Get mappings for mobility types
            $mobilityTypeMaps = $this->karfuAttributeMapService->readByMappingType('mobilityTypeToHr');

            // Get vehicle count
            $vehicleCount = $this->vehicleTempService->countBySessionKey($sessionKey);

            switch ($view) {
                case 'preference':
                    // List of attributes for each grouping
                    $viewDetails = [
                        [
                            'title' => 'Cheapest price',
                            'column' => 'TotalCost',
                            'sort' => 'TCU LOW',
                            'ascDesc' => 'ASC'
                        ],
                        [
                            'title' => 'Most eco-friendly',
                            'column' => 'EnviroImpact',
                            'sort' => 'Enviro Impact LOW',
                            'ascDesc' => 'ASC'
                        ],
                        [
                            'title' => 'Lowest CO2',
                            'column' => 'CO2GKM',
                            'sort' => 'CO2 Emissions LOW',
                            'ascDesc' => 'ASC'
                        ],
                        [
                            'title' => 'Convenience',
                            'column' => 'LocationDistance',
                            'sort' => 'Distance NEAREST',
                            'ascDesc' => 'ASC'
                        ]
                    ];

                    // Get vehicles grouped by preference
                    $vehiclesGrouped = $this->vehicleTempService->readBySessionKeyGroupByCustom(
                        $sessionKey,
                        $viewDetails,
                        [
                            'limit' => [
                                'count' => 10
                            ]
                        ]
                    );
                    break;
                case 'mobility':
                default:
                    // Get vehicles grouped by mobility types
                    $vehiclesGrouped = $this->vehicleTempService->readBySessionKeyGroupByMobilityChoice(
                        $sessionKey,
                        $mobilityTypes,
                        [
                            'limit' => [
                                'count' => 10
                            ]
                        ]
                    );
            }

            $vehiclesFinal = [];
            foreach ($vehiclesGrouped as $vgk => $vehicles) {
                $vehiclesFinal[$vgk] = [
                    'groupTitle' => $vgk,
                    'groupUrl' => null,
                    'vehicles' => []
                ];

                switch ($view) {
                    case 'preference':
                        $viewDetailsKey = array_search($vgk, array_column($viewDetails, 'title'));
                        if ($viewDetails !== false) {
                            $vehiclesFinal[$vgk]['groupUrl'] = '/compare/final_results?sort=' . urlencode($viewDetails[$viewDetailsKey]['sort']);
                        }
                        break;
                    case 'mobility':
                    default:
                        $vehiclesFinal[$vgk]['groupUrl'] = '/compare/final_results?mobility-options' . urlencode('[]') . '=' . urlencode($vgk);
                }

                // Loop through each vehicle to add additional attributes
                foreach ($vehicles as $vehicle) {
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

                    // Add additional attributes to vehicle
                    $vehicle['priceNew'] = $vehicle['Price'];
                    $vehicle['mobilityChoice'] = [
                        'name' => $vehicle['MobilityChoice'],
                        'type' => $vehicle['MobilityType'],
                        'typeHr' => $this->karfuAttributeMapService->mapNameFromList($vehicle['MobilityType'], $mobilityTypeMaps)
                    ];
                    $vehicle['partner'] = $partner;
                    $vehicle['totalCost'] = $vehicle['TotalCost'];
                    $vehicle['monthlyCost'] = $vehicle['TotalMonthlyCost'];
                    $vehiclesFinal[$vgk]['vehicles'][] = $vehicle;
                }
            }

            $estMileage = (array_key_exists('whatIsYourEstimatedMileage', $tempAnswers))
                ? (int) $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue()
                : 0;

            $this->set('vehicles', $vehiclesFinal);
            $this->set('estMileage', $estMileage);
            $this->set('vehicleCount', $vehicleCount);
            $this->set('defaultSort', $this->defaultSort->getAsString($answers));
            $this->set('viewType', $view);
            $this->set('saveRedirectUrl', Page::getCurrentPage()->getCollectionPath());
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Get result count for option
     * 
     * @param $opt
     * 
     * @return int
     */
    public function getCountForOption($opt)
    {
        // Get view mode
        $view = ($this->request->request('view') !== null) ? $this->request->request('view') : 'mobility';

        // Get session
        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;

        if ($session) {
            switch ($view) {
                case 'preference':
                    $count = $this->con->fetchAssoc('SELECT COUNT(KV_ID) AS `count` FROM karfu_vehicle_temp WHERE SessionKey = ?', [$session->getId()]);
                    break;
                case 'mobility':
                default:
                    $count = $this->con->fetchAssoc('SELECT COUNT(KV_ID) AS `count` FROM karfu_vehicle_temp WHERE MobilityChoice = ? AND SessionKey = ?', [$opt, $session->getId()]);    
            }
            return (int) $count['count'];
        } else {
            return 0;
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
    public function getImageFromVRM($vrm)
    {
        $serverPath = "{$_SERVER['DOCUMENT_ROOT']}/application/files/vehicles/{$vrm}.jpg";

        if (!file_exists($serverPath)) {
            $imagePath = "/application/files/vehicles/{$vrm}.jpg";
            $this->capData->getImage($vrm, $serverPath, $imagePath);

            return $imagePath;
        }
    }
}
