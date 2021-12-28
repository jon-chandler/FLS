<?php

declare(strict_types = 1);

namespace Application\Service;

use Concrete\Core\Database\Connection\Connection;

/**
 * Service class for mapping attributes
 */
class KarfuAttributeMapService
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
     * Get records by mapping type
     * 
     * @var string $mappingType
     * 
     * @return array|bool
     */
    public function readByMappingType(string $mappingType)
    {
        return $this->con->fetchAll('SELECT * FROM karfu_attribute_map WHERE mapping_type = ?', [$mappingType]);
    }

    /**
     * Get record by attribute
     * 
     * @param string $attribute
     * @param string|null $mappingType
     * 
     * @return mixed
     */
    public function mapToKarfuAttribute(string $attribute, string $mappingType = null)
    {
        $query = 'SELECT attribute_name FROM karfu_attribute_map';
        $wheres = ['FIND_IN_SET(?, attribute_list)'];
        $params = [$attribute];

        if ($mappingType) {
            $wheres[] = 'mapping_type = ?';
            $params[] = $mappingType;
        } else {
            $wheres[] = 'mapping_type IS NULL';
        }

        $where = implode(' AND ', $wheres);

        $query .= ' WHERE ' . $where;

        $attributeName = $this->con->fetchColumn($query, $params);

        return $attributeName;
    }

    /**
     * Get records by attributes
     * 
     * @param array $attributes
     * @param string $mappingType
     * 
     * @return array
     */
    public function mapToKarfuAttributes(array $attributes, string $mappingType = null): array
    {
        $query = 'SELECT attribute_name FROM karfu_attribute_map WHERE ';
        $params = [];
        $binds = [];
        $mappingTypeQuery = '';

        if ($mappingType) {
            $mappingTypeQuery = 'mapping_type = ?';
            $params[] = $mappingType;
        } else {
            $mappingTypeQuery = 'mapping_type IS NULL';
        }

        foreach ($attributes as $attribute) {
            $binds[] = 'FIND_IN_SET(?, attribute_list)';
            $params[] = $attribute;
        }

        $query .= $mappingTypeQuery;
        $query .= ' AND (' . implode(' OR ', $binds) . ')';

        $attributeName = $this->con->fetchAll($query, $params);

        return $attributeName;
    }

    /**
     * Get records by karfu attributes
     * 
     * @param array $attributes
     * @param string $mappingType
     * 
     * @return array
     */
    public function mapFromKarfuAttributes(array $attributes, string $mappingType): array
    {
        $query = 'SELECT attribute_list FROM karfu_attribute_map WHERE ';
        $params = [];
        $binds = [];
        $mappingTypeQuery = 'mapping_type = ?';
        $params[] = $mappingType;

        foreach ($attributes as $attribute) {
            $binds[] = 'attribute_name = ?';
            $params[] = $attribute;
        }

        $query .= $mappingTypeQuery;
        $query .= ' AND (' . implode(' OR ', $binds) . ')';

        $attributeName = $this->con->fetchAll($query, $params);

        return $attributeName;
    }

    /**
     * @param string $needle
     * @param array $haystack
     * 
     * @return string
     */
    public function mapNameFromList(string $needle, array $haystack): string
    {
        foreach ($haystack as $v) {
            if ($v['attribute_list'] === $needle) {
                return $v['attribute_name'];
            }
        }

        return $needle;
    }
}
