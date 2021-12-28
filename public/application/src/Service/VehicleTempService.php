<?php

declare(strict_types = 1);

namespace Application\Service;

use Concrete\Core\Database\Connection\Connection;

/**
 * Service class for karfu vehicle temp
 */
class VehicleTempService
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
     * Get record by session key, karfu vehicle id, mobility type & mobility sub type
     * Join to karfu_vehicle
     * 
     * @param string $sessionKey
     * @param int $kvId
     * @param string $mobilityChoice
     * @param string $mobilityType
     * 
     * @return array|bool
     */
    public function readBySessionKeyAndKvIdAndMobilityChoiceAndMobilityType(
        string $sessionKey,
        int $kvId,
        string $mobilityChoice,
        string $mobilityType
    )
    {
        $query = 'SELECT karfu_vehicle.*, karfu_vehicle_temp.* FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID
        WHERE SessionKey = ? AND KV_ID = ? AND MobilityChoice = ? AND MobilityType = ?';

        return $this->con->fetchAssoc($query, [$sessionKey, $kvId, $mobilityChoice, $mobilityType]);
    }

    /**
     * Get records by session key
     * 
     * @param string $sessionKey
     * @param array $options
     * 
     * @return array
     */
    public function readBySessionKey(string $sessionKey, array $options = []): array
    {
        $query = 'SELECT karfu_vehicle.*, karfu_vehicle_temp.* FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID';
        $filters = (array_key_exists('filters', $options)) ? $options['filters'] : [];
        $sorts = (array_key_exists('sorts', $options)) ? $options['sorts'] : [];
        $wheres = [];
        $bindings = [];

        if (count($filters) > 0) {
            $bindings[] = 1;
            $bindings[] = $sessionKey;

            foreach ($filters as $filter) {
                $wheres[] = $this->buildFilters($filter, $bindings);
            }

            $where = (count($wheres) > 0) ? ' WHERE Active = ? AND SessionKey = ? AND (' . implode(' AND ', $wheres) . ')' : ' WHERE Active = ? AND SessionKey = ?';
        } else {
            $bindings[] = 1;
            $bindings[] = $sessionKey;
            $where = ' WHERE Active = ? AND SessionKey = ?';
        }

        $orderBys = [];
        if (count($sorts) > 0) {
            foreach ($sorts as $sort) {
                if (
                    array_key_exists('column', $sort)
                    && array_key_exists('ascDesc', $sort)
                ) {
                    $orderBys[] = $sort['column'] . ' ' . $sort['ascDesc'];
                }
            }

            $orderBy = (count($orderBys) > 0) ? ' ORDER BY ' . implode(', ', $orderBys) : '';
        }

        if ($where) {
            $query .= $where;
        }

        if ($orderBy) {
            $query .= $orderBy;
        }

        if (isset($options['limit'])) {
            $limit = $options['limit'];

            $limitStr = '';

            if (array_key_exists('count', $limit)) {
                $limitStr .= ' LIMIT ';
                if (array_key_exists('offset', $limit)) {
                    $limitStr .= $limit['offset'] . ',' . $limit['count'];
                } else {
                    $limitStr .= $limit['count'];
                }
            }

            $query .= $limitStr;
        }

        $results = $this->con->fetchAll($query, $bindings);

        return $results;
    }

    /**
     * Get records by session ket & mobility type
     * 
     * @param string $sessionKey
     * @param string $mobilityChoice
     * @param array $options
     * 
     * @return array
     */
    public function readBySessionKeyAndMobilityChoice(string $sessionKey, string $mobilityChoice, array $options = []): array
    {
        $query = 'SELECT karfu_vehicle.*, karfu_vehicle_temp.* FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID';
        $filters = (array_key_exists('filters', $options)) ? $options['filters'] : [];
        $sorts = (array_key_exists('sorts', $options)) ? $options['sorts'] : [];
        $wheres = [];
        $bindings = [];

        if (count($filters) > 0) {
            $bindings[] = 1;
            $bindings[] = $sessionKey;
            $bindings[] = $mobilityChoice;

            foreach ($filters as $filter) {
                $wheres[] = $this->buildFilters($filter, $bindings);
            }

            $where = (count($wheres) > 0) ? ' WHERE Active = ? AND SessionKey = ? AND MobilityChoice = ? AND (' . implode(' AND ', $wheres) . ')' : ' WHERE Active = ? AND SessionKey = ? AND MobilityChoice = ?';
        } else {
            $bindings[] = 1;
            $bindings[] = $sessionKey;
            $bindings[] = $mobilityChoice;
            $where = ' WHERE Active = ? AND SessionKey = ? AND MobilityChoice = ?';
        }

        $orderBys = [];
        if (count($sorts) > 0) {
            foreach ($sorts as $sort) {
                if (
                    array_key_exists('column', $sort)
                    && array_key_exists('ascDesc', $sort)
                ) {
                    $orderBys[] = $sort['column'] . ' ' . $sort['ascDesc'];
                }
            }

            $orderBy = (count($orderBys) > 0) ? ' ORDER BY ' . implode(',', $orderBys) : '';
        }

        if ($where) {
            $query .= $where;
        }

        if ($orderBy) {
            $query .= $orderBy;
        }

        if (isset($options['limit'])) {
            $limit = $options['limit'];

            $limitStr = '';

            if (array_key_exists('count', $limit)) {
                $limitStr .= ' LIMIT ';
                if (array_key_exists('offset', $limit)) {
                    $limitStr .= $limit['offset'] . ',' . $limit['count'];
                } else {
                    $limitStr .= $limit['count'];
                }
            }

            $query .= $limitStr;
        }

        $results = $this->con->fetchAll($query, $bindings);

        return $results;
    }

    /**
     * Get records by session key & mobility type
     * Group by mobility type
     * 
     * @param string $sessionKey
     * @param array $mobilityChoices
     * @param array $options
     * 
     * @return array
     */
    public function readBySessionKeyGroupByMobilityChoice(string $sessionKey, array $mobilityChoices, array $options = []): array
    {
        $vehicles = [];

        foreach ($mobilityChoices as $mobilityChoice) {
            $results = $this->readBySessionKeyAndMobilityChoice($sessionKey, $mobilityChoice, $options);
            $vehicles[$mobilityChoice] = $results;
        }

        return $vehicles;
    }

    /**
     * Get records by session key & group by custom value
     * 
     * @param string $sessionKey
     * @param array $custom
     * @param array $options
     * 
     * @return array
     */
    public function readBySessionKeyGroupByCustom(string $sessionKey, array $custom, array $options = []): array
    {
        $vehicles = [];

        foreach ($custom as $v) {
            $options['sorts'] = [];
            $options['sorts'][] = [
                'column' => $v['column'],
                'ascDesc' => $v['ascDesc']
            ];
            $results = $this->readBySessionKey($sessionKey, $options);
            $vehicles[$v['title']] = $results;
        }

        return $vehicles;
    }

    /**
     * Get count of records by session key
     * 
     * @param string $sessionKey
     * @param array $options
     * 
     * @return int
     */
    public function countBySessionKey(string $sessionKey, array $options = []): int
    {
        $query = 'SELECT COUNT(ID) as vehicle_count FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID';
        $filters = (array_key_exists('filters', $options)) ? $options['filters'] : [];
        $wheres = [];
        $bindings = [];

        if (count($filters) > 0) {
            $bindings[] = 1;
            $bindings[] = $sessionKey;

            foreach ($filters as $filter) {
                $wheres[] = $this->buildFilters($filter, $bindings);
            }

            $where = (count($wheres) > 0) ? ' WHERE Active = ? AND SessionKey = ? AND (' . implode(' AND ', $wheres) . ')' : ' WHERE Active = ? AND SessionKey = ?';
        } else {
            $bindings[] = 1;
            $bindings[] = $sessionKey;
            $where = ' WHERE Active = ? AND SessionKey = ?';
        }

        if ($where) {
            $query .= $where;
        }

        $results = $this->con->fetchAssoc($query, $bindings);

        return (int) $results['vehicle_count'];
    }

    /**
     * Get count of records & vehicle type by session key
     * 
     * @param string $sessionKey
     * 
     * @return array
     */
    public function readVehicleTypeCountBySessionKey(string $sessionKey)
    {
        $query = 'SELECT COUNT(karfu_vehicle.ID) AS VehicleTypeCount, karfu_vehicle.VehicleType FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID';
        $groupBy = 'GROUP BY karfu_vehicle.VehicleType';
        $filters = [];
        $wheres = [];
        $bindings = [];

        if ($filters) {
            $bindings[] = 1;
            $bindings[] = $sessionKey;

            foreach ($filters as $filter) {
                $wheres = $this->buildFilters($filter, $bindings);
            }
            $where = (count($wheres) > 0) ? ' WHERE Active = ? AND SessionKey = ? AND (' . implode(' AND ', $wheres) . ')' : ' WHERE Active = ? AND SessionKey = ?';
        } else {
            $bindings[] = 1;
            $bindings[] = $sessionKey;
            $where = ' WHERE Active = ? AND SessionKey = ?';
        }

        if ($where) {
            $query .= $where;
        }

        $query .= ' ' . $groupBy;

        $results = $this->con->fetchAll($query, $bindings);

        return $results;
    }

    /**
     * Build the filters SQL string
     * 
     * @param array $filters
     * @param array $bindings
     * 
     * @return string
     */
    private function buildFilters(array $filters, &$bindings): string
    {
        $wheres = [];
        $tempWheres = [];

        if (isset($filters['column'])) {
            $col = $filters['column'];

            if (isset($filters['operator'])) {
                $comparison = $this->buildComparisonFromFilter($col, $filters, $bindings);
            } elseif (isset($filters['or'])) {
                $ors = $filters['or'];
                $tempComparisons = [];
                
                foreach ($ors as $or) {
                    $tempComparisons[] = $this->buildComparisonFromFilter($col, $or, $bindings);
                }

                $comparison = implode(' OR ', $tempComparisons);

                if (count($ors) > 1) {
                    $comparison = '(' . $comparison . ')';
                }
            }

            $wheres = $comparison;
        } else if (isset($filters['or'])) {
            $ors = $filters['or'];

            foreach ($ors as $or) {
                $tempComparisons = [];

                foreach ($or as $v) {
                    $tempComparisons[] = $this->buildFilters($v, $bindings);
                }

                $tempWheres[] = '(' . implode(' AND ', $tempComparisons) . ')';
            }

            $wheres = implode(' OR ', $tempWheres);
        }

        return $wheres;
    }

    /**
     * Build the comparison SQL
     * 
     * @param array $filter
     * 
     * @return string
     */
    private function buildComparisonFromFilter(string $col, array $filter, array &$bindings): string
    {
        $op = $filter['operator'];

        switch ($op) {
            case '>=<':
                if (isset($filter['values'])) {
                    $vals = $filter['values'];

                    if (count($vals) === 2) {
                        $comparison = $col . ' BETWEEN ? AND ?';
                        $bindings[] = $vals[0];
                        $bindings[] = $vals[1];
                    }
                }
                break;
            case 'like':
                if (isset($filter['value'])) {
                    $comparison = $col . ' LIKE ?';
                    $bindings[] = $filter['value'];
                }
                break;
            case 'like%':
                if (isset($filter['value'])) {
                    $comparison = $col . ' LIKE ?';
                    $bindings[] = $filter['value'] . '%';
                }
                break;
            case '%like':
                if (isset($filter['value'])) {
                    $comparison = $col . ' LIKE ?';
                    $bindings[] = '%' . $filter['value'];
                }
                break;
            case '%like%':
                if (isset($filter['value'])) {
                    $comparison = $col . ' LIKE %?%';
                    $bindings[] = '%' . $filter['value'] . '%';
                }
                break;
            case 'find_in_set':
                if (isset($filter['value'])) {
                    $comparison = 'FIND_IN_SET(?, ' . $col . ')';
                    $bindings[] = $filter['value'];
                }
                break;
            case 'is':
                if ($filter['value'] === null) {
                    $comparison = $col . ' IS NULL';
                    // $bindings[] = $filter['value'];
                }
                break;
            default:
                if (isset($filter['value'])) {
                    $comparison = $col . ' ' . $op . ' ?';
                    $bindings[] = $filter['value'];
                }
        }

        return $comparison;
    }

    /**
     * @param string $value
     * 
     * @return array
     */
    public function mapSort(string $value): array
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
            case 'Price':
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
            case 'The Environment':
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
            case 'Net Position HIGH':
                $sort = [
                    'column' => 'NetPosition',
                    'ascDesc' => 'DESC'
                ];
                break;
            case 'Net Position LOW':
                $sort = [
                    'column' => 'NetPosition',
                    'ascDesc' => 'ASC'
                ];
                break;
            case 'Price per Mile HIGH':
                $sort = [
                    'column' => 'CostPerMile',
                    'ascDesc' => 'DESC'
                ];
                break;
            case 'Price per Mile LOW':
                $sort = [
                    'column' => 'CostPerMile',
                    'ascDesc' => 'ASC'
                ];
                break;
            case 'Age NEWEST':
                $sort = [
                    'column' => 'RegistrationDate',
                    'ascDesc' => 'DESC'
                ];
                break;
            case 'Age OLDEST':
                $sort = [
                    'column' => 'RegistrationDate',
                    'ascDesc' => 'ASC'
                ];
                break;
            case 'Distance NEAREST':
                $sort = [
                    'column' => 'LocationDistance',
                    'ascDesc' => 'ASC'
                ];
                break;
            case 'Distance FURTHEST':
                $sort = [
                    'column' => 'LocationDistance',
                    'ascDesc' => 'DESC'
                ];
                break;
            case 'Score HIGH':
                $sort = [
                    'column' => 'SuitabilityScore',
                    'ascDesc' => 'DESC'
                ];
                break;
            case 'Score LOW':
                $sort = [
                    'column' => 'SuitabilityScore',
                    'ascDesc' => 'ASC'
                ];
                break;
            default:
                $sort = [];
        }

        return $sort;
    }
}
