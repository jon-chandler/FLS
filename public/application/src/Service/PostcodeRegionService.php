<?php

declare(strict_types = 1);

namespace Application\Service;

use Application\Model\PostcodeRegion;
use Concrete\Core\Database\Connection\Connection;

/**
 * Service class for postcode region
 */
class PostcodeRegionService
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
     * Get record by postcode area
     * 
     * @param string $postcodeArea
     * 
     * @return PostcodeRegion|bool
     */
    public function readByPostcodeArea(string $postcodeArea)
    {
        $query = 'SELECT * FROM postcode_region WHERE postcode_area = ?';
        $bindings = [$postcodeArea];
        $result = $this->con->fetchAssoc($query, $bindings);

        if ($result) {
            $id = (int) $result['id'];
            $postcodeArea = $result['postcode_area'];
            $region = $result['region'];

            $postcodeRegion = new PostcodeRegion();
            $postcodeRegion->setId($id)
                ->setPostcodeArea($postcodeArea)
                ->setRegion($region);

            return $postcodeRegion;
        }

        return false;
    }
}
