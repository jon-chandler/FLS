<?php

declare(strict_types = 1);

namespace Application\Service;

use Application\Service\KarfuAttributeMapService;
use Concrete\Core\Database\Connection\Connection;

/**
 * Service class for questions
 */
class QuestionService
{
    /**
     * @var Connection
     */
    private $con;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @param Connection $con
     * @param KarfuAttributeMapService $karfuAttributeMapService
     */
    public function __construct(Connection $con, KarfuAttributeMapService $karfuAttributeMapService)
    {
        $this->con = $con;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
    }

    /**
     * Return list of manufacturers by vehicle type
     * 
     * @param string $vehicleType
     * 
     * @return array
     */
    public function getManufacturerListByVehicleType(string $vehicleType): array
    {
        $vehicles = array('C', 'B'); // will need to get these from the session value or progress option
        $vehicileOpts = implode("','", $vehicles);
        $manufacturerList = $this->con->fetchAll("SELECT DISTINCT ManName FROM karfu_vehicle WHERE VehicleType IN('{$vehicileOpts}') ORDER BY ManName");

        return $manufacturerList;
    }
}
