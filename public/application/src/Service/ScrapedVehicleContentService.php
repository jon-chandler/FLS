<?php

declare(strict_types = 1);

namespace Application\Service;

use Concrete\Core\Database\Connection\Connection;

/**
 * Service class for mapping scrapped vehicles
 */
class ScrapedVehicleContentService
{
    /**
     * @var Connection
     */
    private $con;

    /**
     * @param Connection $con
     */
    public function __construct(Connection $con)
    {
        $this->con = $con;
    }

    /**
     * Get record by manufacturer name, manufacturer model & fuel type
     * or trim & manufacturer model
     * 
     * @param string $manufacturerName
     * @param string $manufacturerModel
     * @param string $fuelType
     * @param string $trim
     * 
     * @return array
     */
    public function mapScrapedData(string $manufacturerName, string $manufacturerModel, string $fuelType, string $trim)
    {
        $query = 'SELECT * FROM scraped_vehicle_content
        WHERE manufacturer_name = ?
        AND manufacturer_model = ?
        AND fuel_type = ?
        OR manufacturer_model_variant LIKE ? AND manufacturer_model = ?';

        $bindings = [
            $manufacturerName,
            $manufacturerModel,
            $fuelType,
            '%' . $trim . '%',
            $manufacturerModel
        ];

        return $this->con->fetchAll($query, $bindings);
    }

    /**
     * Get record by fuel type & derivative
     * 
     * @param string $fuelType
     * @param string $derivative
     * 
     * @return array
     */
    public function mapByFuelAndDerivative(string $fuelType, string $derivative)
    {
        $query = 'SELECT * FROM scraped_vehicle_content
        WHERE fuel_type = ?
        AND manufacturer_model_variant LIKE ?';

        $bindings = [
            $fuelType,
            '%' . $derivative . '%'
        ];

        return $this->con->fetchAll($query, $bindings);
    }
}
