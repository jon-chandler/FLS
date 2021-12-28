<?php

declare(strict_types = 1);

namespace Application\Service;

use Concrete\Core\Database\Connection\Connection;

/**
 * Service class for karfu vehicle
 */
class VehicleService
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
     * Get record by id
     * 
     * @param int $id
     * 
     * @return array
     */
    public function readById(int $id): array
    {
        return $this->con->fetchAssoc('SELECT * FROM karfu_vehicle WHERE Active = ? AND ID = ?', [1, $id]);
    }

    /**
     * Get records by mobility type
     * 
     * @param string $mobilityChoice
     * 
     * @return array
     */
    public function readByMobilityChoiceUses(string $mobilityChoice): array
    {
        return $this->con->fetchAll(
            'SELECT * FROM karfu_vehicle WHERE Active = ? AND FIND_IN_SET(?, MobilityChoiceUses)',
            [1, $mobilityChoice]
        );
    }

    /**
     * Get records by keywords & vehicle types
     * 
     * @param array $keywords
     * @param array $vehicleTypes
     * @param array $options
     * 
     * @return array
     */
    public function readByKeywordsAndVehicleTypes(array $keywords, array $vehicleTypes, array $options = null): array
    {
        $query = 'SELECT * FROM karfu_vehicle';
        $wheres = [];
        $bindings = [1];
        $where = '';

        foreach ($keywords as $keyword) {
            $wheres[] = 'ManName LIKE ?';
            $wheres[] = 'ModelName LIKE ?';
            $wheres[] = 'Derivative LIKE ?';
            $bindings[] = '%' . $keyword . '%';
            $bindings[] = '%' . $keyword . '%';
            $bindings[] = '%' . $keyword . '%';
        }

        if (count($wheres) > 0) {
            $where .= ' WHERE Active = ? AND (' . implode(' OR ', $wheres) . ')';
        }

        $wheres = [];
        foreach ($vehicleTypes as $vehicleType) {
            $wheres[] = 'VehicleType = ?';
            $bindings[] = $vehicleType;
        }

        if (count($wheres) > 0) {
            if (strlen($where) > 0) {
                $where .= ' AND';
            } else {
                $where .= ' WHERE Active = ? AND';
            }
            $where .= ' (' . implode(' OR ', $wheres) . ')';
        }

        $query .= $where;

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

        return $this->con->fetchAll($query, $bindings);
    }

    /**
     * Get records by shortlist user id
     * 
     * @param int $userId
     * 
     * @return array|bool
     */
    public function readyByShortlistUserId(int $userId)
    {
        $query = 'SELECT kv.*, s.*, ac.data FROM karfu_vehicle kv
        RIGHT JOIN shortlist s ON kv.ID = s.vehicle_id
        LEFT JOIN api_cache ac ON s.api_cache_id = ac.id
        WHERE (kv.Active = ? AND s.user_id = ?)
            OR (s.user_id = ? AND s.vehicle_id IS NULL AND s.vrm IS NOT NULL)
        ORDER BY
            s.saved_date DESC';
        return $this->con->fetchAll($query, [1, $userId, $userId]);
    }

    /**
     * Get record by cap id
     * 
     * @param int $capId
     * 
     * @return array
     */
    public function readByCapId(int $capId)
    {
        return $this->con->fetchAssoc('SELECT * FROM karfu_vehicle WHERE CapID = ?', [$capId]);
    }

    /**
     * Get records by vehicle type
     * 
     * @param string $vehicleType
     * @param array|null $options
     * 
     * @return array
     */
    public function readByVehicleType(string $vehicleType, array $options = null): array
    {
        $query = 'SELECT * FROM karfu_vehicle WHERE Active = ? AND VehicleType = ?';
        $bindings = [1, $vehicleType];

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
     * Get records by question filter & session key
     * 
     * @param array $questionFilter
     * @param array|null $options
     * @param string|null $sessionKey
     * 
     * @return array
     */
    public function readByQuestionFilter(array $questionFilter, array $options = null, string $sessionKey = null): array
    {
        if ($sessionKey) {
            $query = 'SELECT karfu_vehicle.*, karfu_vehicle_temp.* FROM karfu_vehicle';
        } else {
            $query = 'SELECT karfu_vehicle.* FROM karfu_vehicle';
        }
        
        $filters = $questionFilter['filters'];
        $sorts = $questionFilter['sorts'];
        $wheres = [];
        $bindings = [];

        if ($sessionKey) {
            $query .= ' INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID';
        }

        if ($filters) {
            $bindings[] = 1;

            foreach ($filters as $filter) {
                $wheres = $this->buildFilters($filter, $bindings);
            }

            $where = (count($wheres) > 0) ? ' WHERE Active = ? AND (' . implode(' AND ', $wheres) . ')' : ' WHERE Active = ?';
        }

        if ($sessionKey) {
            $bindings[] = $sessionKey;
            if (count($wheres) > 0) {
                $where .= ' AND SessionKey = ?';
            }
        }

        $orderBys = [];
        if ($sorts) {
            foreach ($sorts as $sort) {
                $orderBys[] = $sort['column'] . ' ' . $sort['ascDesc'];
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
     * Get count of records by keywords and vehicle types
     * 
     * @param array $keywords
     * @param array $vehicleTypes
     * 
     * @return int
     */
    public function countByKeywordsAndVehicleTypes(array $keywords, array $vehicleTypes): int
    {
        $query = 'SELECT COUNT(id) as vehicle_count FROM karfu_vehicle';
        $wheres = [];
        $bindings = [1];
        $where = '';

        foreach ($keywords as $keyword) {
            $wheres[] = 'ManName LIKE ?';
            $wheres[] = 'ModelName LIKE ?';
            $wheres[] = 'Derivative LIKE ?';
            $bindings[] = '%' . $keyword . '%';
            $bindings[] = '%' . $keyword . '%';
            $bindings[] = '%' . $keyword . '%';
        }

        if (count($wheres) > 0) {
            $where .= ' WHERE Active = ? AND (' . implode(' OR ', $wheres) . ')';
        }

        $wheres = [];
        foreach ($vehicleTypes as $vehicleType) {
            $wheres[] = 'VehicleType = ?';
            $bindings[] = $vehicleType;
        }

        if (count($wheres) > 0) {
            if (strlen($where) > 0) {
                $where .= ' AND';
            } else {
                $where .= ' WHERE Active = ? AND';
            }
            $where .= ' (' . implode(' OR ', $wheres) . ')';
        }

        $query .= $where;

        $results = $this->con->fetchAssoc($query, $bindings);
        return (int) $results['vehicle_count'];
    }

    /**
     * Get count of records by vehicle type
     * 
     * @param string $vehicleType
     * 
     * @return int
     */
    public function countByVehicleType(string $vehicleType): int
    {

        $query = 'SELECT COUNT(*) as vehicle_count FROM karfu_vehicle WHERE Active = ? AND VehicleType = ?';
        $bindings = [1, $vehicleType];

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

        $results = $this->con->fetchAssoc($query, $bindings);
        return (int) $results['vehicle_count'];
    }

    /**
     * Get count of records by question filter & session key
     * 
     * @param array $questionFilter
     * @param string $sessionKey
     * 
     * @return int
     */
    public function countByQuestionFilter(array $questionFilter, string $sessionKey): int
    {
        $query = 'SELECT COUNT(ID) as vehicle_count FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID';
        $filters = $questionFilter['filters'];
        $wheres = [];
        $bindings = [];

        if ($filters) {
            $bindings[] = 1;
            $bindings[] = $sessionKey;

            foreach ($filters as $filter) {
                $wheres = $this->buildFilters($filter, $bindings);
            }

            $where = (count($wheres) > 0) ? ' WHERE Active = ? AND SessionKey = ? AND (' . implode(' AND ', $wheres) . ')' : ' WHERE Active = ? AND SessionKey = ?';
        }

        if ($where) {
            $query .= $where;
        }

        $results = $this->con->fetchAssoc($query, $bindings);

        return (int) $results['vehicle_count'];
    }

    /**
     * Get count of records & vehicle type by question filter & session key
     * 
     * @param array $questionFilter
     * @param string $sessionKey
     * 
     * @return array
     */
    public function readVehicleTypeCountByQuestionFilter(array $questionFilter, string $sessionKey)
    {
        $query = 'SELECT COUNT(karfu_vehicle.ID) AS VehicleTypeCount, karfu_vehicle.VehicleType FROM karfu_vehicle
        INNER JOIN karfu_vehicle_temp ON karfu_vehicle_temp.KV_ID = karfu_vehicle.ID';
        $groupBy = 'GROUP BY karfu_vehicle.VehicleType';
        $filters = $questionFilter['filters'];
        $sorts = $questionFilter['sorts'];
        $wheres = [];
        $bindings = [];

        if ($filters) {
            $bindings[] = 1;
            $bindings[] = $sessionKey;

            foreach ($filters as $filter) {
                $wheres = $this->buildFilters($filter, $bindings);
            }
            $where = (count($wheres) > 0) ? ' WHERE Active = ? AND SessionKey = ? AND (' . implode(' AND ', $wheres) . ')' : ' WHERE Active = ? AND SessionKey = ?';
        }

        $orderBys = [];
        if ($sorts) {
            foreach ($sorts as $sort) {
                $orderBys[] = $sort['column'] . ' ' . $sort['ascDesc'];
            }

            $orderBy = (count($orderBys) > 0) ? ' ORDER BY ' . implode(',', $orderBys) : '';
        }

        if ($where) {
            $query .= $where;
        }

        if ($orderBy) {
            $query .= $orderBy;
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
     * @return array
     */
    private function buildFilters(array $filters, &$bindings): array
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

            $wheres[] = $comparison;
        } else if (isset($filters['or'])) {
            $ors = $filters['or'];
            $useRarenthesis = false;

            foreach ($ors as $or) {
                $tempComparisons = [];

                if (isset($or['column'])) {
                    $tempComparisons = $this->buildFilters($or, $bindings);
                    $tempWheres[] = implode('', $tempComparisons);
                    $useRarenthesis = true;
                } else {
                    foreach ($or as $v) {
                        $tempComparisons[] = $this->buildFilters($v, $bindings);
                    }

                    $tempComparisons = array_map(function ($wheres) {
                        return $wheres[0];
                    }, $tempComparisons);
    
                    $tempWheres[] = '(' . implode(' AND ', $tempComparisons) . ')';
                }
            }

            if ($useRarenthesis === true) {
                $wheres[] = '(' . implode(' OR ', $tempWheres) . ')';
            } else {
                $wheres[] = implode(' OR ', $tempWheres);
            }

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
}
