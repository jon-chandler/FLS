<?php

declare(strict_types = 1);

namespace Application\Service;

use Concrete\Core\Database\Connection\Connection;

/**
 * Service class for journey summary which helps build the display of
 * questions & answers on summary pages
 */
class JourneySummaryService
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
     * Get records by journey order
     * 
     * @param int $journeyOrder
     * @param array $options
     * 
     * @return array|bool
     */
    public function readByJourneyOrder(int $journeyOrder, array $options = [])
    {
        $query = 'SELECT * FROM journey_summary WHERE journey_id = ?';

        if (isset($options['order'])) {
            $order = $options['order'];
            $orderStr = '';

            if (array_key_exists('column', $order)) {
                $orderStr .= ' ORDER BY `' . $order['column'] . '`';

                if (array_key_exists('ascDesc', $order)) {
                    $orderStr .= ' ' . $order['ascDesc'];
                }
            }

            $query .= $orderStr;
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

        return $this->con->fetchAll($query, [$journeyOrder]);
    }

    /**
     * Get records by journey group & journey order
     * 
     * @param string $journeyGroup
     * @param int|null $journeyOrder
     * @param array $options
     * 
     * @return array|bool
     */
    public function readByJourneyGroupAndJourneyOrder(string $journeyGroup, $journeyOrder, array $options = [])
    {
        $query = 'SELECT * FROM journey_summary WHERE journey_group = ? AND journey_id = ?';

        if (isset($options['order'])) {
            $order = $options['order'];
            $orderStr = '';

            if (array_key_exists('column', $order)) {
                $orderStr .= ' ORDER BY `' . $order['column'] . '`';

                if (array_key_exists('ascDesc', $order)) {
                    $orderStr .= ' ' . $order['ascDesc'];
                }
            }

            $query .= $orderStr;
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

        return $this->con->fetchAll($query, [$journeyGroup, $journeyOrder]);
    }

    /**
     * Get records by journey order & page
     * 
     * @param int $journeyOrder
     * @param string $page
     * @param array $options
     * 
     * @return array|bool
     */
    public function readByJourneyOrderAndPage(int $journeyOrder, string $page, array $options = [])
    {
        $query = 'SELECT * FROM journey_summary WHERE journey_id = ? AND FIND_IN_SET(?, `page`)';

        if (isset($options['order'])) {
            $order = $options['order'];
            $orderStr = '';

            if (array_key_exists('column', $order)) {
                $orderStr .= ' ORDER BY `' . $order['column'] . '`';

                if (array_key_exists('ascDesc', $order)) {
                    $orderStr .= ' ' . $order['ascDesc'];
                }
            }

            $query .= $orderStr;
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

        return $this->con->fetchAll($query, [$journeyOrder, $page]);
    }

    /**
     * Get records by journey order & page
     * 
     * @param string $journeyGroup
     * @param int $journeyOrder
     * @param string $page
     * @param array $options
     * 
     * @return array|bool
     */
    public function readByJourneyGroupAndJourneyOrderAndPage(string $journeyGroup, int $journeyOrder, string $page, array $options = [])
    {
        $query = 'SELECT * FROM journey_summary WHERE journey_group = ? AND journey_id = ? AND FIND_IN_SET(?, `page`)';

        if (isset($options['order'])) {
            $order = $options['order'];
            $orderStr = '';

            if (array_key_exists('column', $order)) {
                $orderStr .= ' ORDER BY `' . $order['column'] . '`';

                if (array_key_exists('ascDesc', $order)) {
                    $orderStr .= ' ' . $order['ascDesc'];
                }
            }

            $query .= $orderStr;
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

        return $this->con->fetchAll($query, [$journeyGroup, $journeyOrder, $page]);
    }

    /**
     * Get records by journey data handle
     * 
     * @param string $dataHandle
     * @param array $options
     * 
     * @return array|bool
     */
    public function readByJourneyDataHandle(string $dataHandle, array $options = [])
    {
        $query = 'SELECT * FROM journey_summary WHERE journey_data_handle = ?';

        if (isset($options['order'])) {
            $order = $options['order'];
            $orderStr = '';

            if (array_key_exists('column', $order)) {
                $orderStr .= ' ORDER BY `' . $order['column'] . '`';

                if (array_key_exists('ascDesc', $order)) {
                    $orderStr .= ' ' . $order['ascDesc'];
                }
            }

            $query .= $orderStr;
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

        return $this->con->fetchAll($query, [$dataHandle]);
    }
}
