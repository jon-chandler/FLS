<?php

declare(strict_types = 1);

namespace Application\Helper;

use Concrete\Core\Database\Connection\Connection;

/**
 * VehicleAttributeMap maps vehicle data to custom labels
 */
class VehicleAttributeMap
{
    /**
     * @var Connection
     */
    private $con;

    /**
     * @var Connection $con
     */
    public function __construct(Connection $con)
    {
        $this->con = $con;
    }

    /**
     * Map vehicle data to custom labels from the database
     * 
     * @param array $vehicle
     * 
     * @return array
     */
    public function map(array $vehicle): array
    {
        $mappedData = [];
        $query = 'SELECT
            vehicle_attribute_map.*,
            vehicle_breakdown_categories.name AS cat_name
        FROM
            vehicle_attribute_map
        INNER JOIN
            vehicle_breakdown_categories
        ON
            vehicle_breakdown_categories.id = vehicle_attribute_map.display_category
        WHERE
            vehicle_attribute_map.display = ?
        AND
            vehicle_breakdown_categories.display = ?
        ORDER BY
            vehicle_attribute_map.display_category,
            vehicle_attribute_map.id';
        $results = $this->con->fetchAll($query, [1, 1]);

        if ($results) {
            $prevCategory = 0;
            $tempMapDataEntry = [];

            foreach ($results as $result) {
                if (array_key_exists($result['name'], $vehicle)) {
                    $vehicleColumnValue = $vehicle[$result['name']];

                    if ($this->isValueDisplayable($vehicleColumnValue)) {
                        if ($prevCategory !== $result['display_category']) {
                            if ($prevCategory !== 0) {
                                $mappedData[] = $tempMapDataEntry;
                            }
    
                            $tempMapDataEntry = [
                                'categoryTitle' => $result['cat_name'],
                                'attributes' => []
                            ];
                            $prevCategory = $result['display_category'];
                        }

                        $title = (empty($result['karfu_name'])) ? $result['name'] : $result['karfu_name'];
                        $value = '';

                        if ((int) $result['prefix_unit'] === 1 && !empty($result['unit'])) {
                            $value .= $result['unit'];
                        }

                        $value .= $vehicleColumnValue;

                        if (!empty($result['unit']) && (int) $result['prefix_unit'] === 0) {
                            $value .=  ' (' . $result['unit'] . ')';
                        }

                        $tempMapDataEntry['attributes'][] = [
                            'title' => $title,
                            'value' => $value
                        ];
                    }
                }
            }

            $mappedData[] = $tempMapDataEntry;
        }

        return $mappedData;
    }

    /**
     * Check if we want to display the value
     * 
     * @param mixed $value
     * 
     * @return bool
     */
    private function isValueDisplayable($value): bool
    {
        if (
            $value === null
            || $value === ''
            || strtoupper($value) === 'N'
        )
        {
            return false;
        } else {
            return true;
        }
    }
}
