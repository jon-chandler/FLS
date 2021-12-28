<?php

namespace Application\Controller\PageType;

use Application\Helper\Pagination;
use Application\Service\KarfuAttributeMapService;
use Application\Service\ScrapedVehicleContentService;
use Application\Service\VehicleService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Page\Controller\PageTypeController;
use Concrete\Core\Page\Page;
use Core;
use Database;

class VehicleMobilityDetail extends PageTypeController
{
    /**
     * @var Connection
     */
    private $con;

    /**
     * @var ScrapedVehicleContentService
     */
    private $scrapedVehicleContentService;

    /**
     * @var VehicleService
     */
    private $vehicleService;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @param $obj
     * @param Connection $con
     * @param ScrapedVehicleContentService $scrapedVehicleContentService
     * @param VehicleService $vehicleService
     * @param KarfuAttributeMapService $karfuAttributeMapService
     */
    public function __construct(
        $obj = null,
        Connection $con,
        ScrapedVehicleContentService $scrapedVehicleContentService,
        VehicleService $vehicleService,
        KarfuAttributeMapService $karfuAttributeMapService
    )
    {
        parent::__construct($obj);
        $this->con = $con;
        $this->scrapedVehicleContentService = $scrapedVehicleContentService;
        $this->vehicleService = $vehicleService;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
    }

    /**
     * Concrete5 on_start hook
     */
    public function on_start()
    {
        // Get vehicle type by page attribute
        $c = Page::getCurrentPage();
        $vehicleType = $this->karfuAttributeMapService->mapToKarfuAttribute($c->getAttribute('vehicle_types'), 'apiToVehicleTable');
        
        // Get vehicle count
        $vehicleCount = $this->vehicleService->countByVehicleType($vehicleType);

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
        $vehicles = $this->vehicleService->readByVehicleType($vehicleType, ['limit' => ['offset' => $pagination->getOffset(), 'count' => $pagination->getCount()]]);

        // Add priceNew for each vehicle for template compatability
        $vehicles = array_map(function ($vehicle) {
            $vehicle['priceNew'] = $vehicle['Price'];
            return $vehicle;
        }, $vehicles);

        $this->set('scrapedVehicleContentService', $this->scrapedVehicleContentService);
        $this->set('vehicles', $vehicles);
        $this->set('vehicleCount', $vehicleCount);
        $this->set('pagination', $pagination);
    }

    public function view($id = null)
    {
    }

}
