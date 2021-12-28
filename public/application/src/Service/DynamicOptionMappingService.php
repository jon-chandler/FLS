<?php

declare(strict_types = 1);

namespace Application\Service;

use Concrete\Core\Database\Connection\Connection;
use Exception;

/**
 * Service CRUD class for Dynamic Option Mapping
 * Used to add options to a question dynamically
 */
class DynamicOptionMappingService
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
     * Create dynamic option mapping record
     * 
     * @param string $sessionKey
     * @param string $optionDataHandle
     * 
     * @return array|bool
     */
    public function create(string $sessionKey, string $optionDataHandle)
    {
        try {
            $this->con->executeQuery(
                'INSERT INTO dynamic_option_mapping (session_key, option_data_handle)
                VALUES(?, ?)',
                [
                    $sessionKey,
                    $optionDataHandle
                ]
            );
            $id = (int) $this->con->lastInsertId();
            return [
                'id' => $id,
                'session_key' => $sessionKey,
                'option_data_handle' => $optionDataHandle
            ];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get records by session key
     * 
     * @param string $sessionKey
     * 
     * @return array
     */
    public function readBySessionKey(string $sessionKey): array
    {
        return $this->con->fetchAll("SELECT option_data_handle FROM dynamic_option_mapping WHERE session_key = ?", [$sessionKey]);
    }

    /**
     * Delete dynamic option mapping records by session key
     * 
     * @param string $sessionKey
     * 
     * @return bool
     */
    public function deleteBySessionKey(string $sessionKey): bool
    {
        try {
            $this->con->executeQuery('DELETE FROM dynamic_option_mapping WHERE session_key = ?', [$sessionKey]);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
