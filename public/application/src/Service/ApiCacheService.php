<?php

declare(strict_types = 1);

namespace Application\Service;

use Application\Model\ApiCache;
use Concrete\Core\Database\Connection\Connection;
use Exception;

/**
 * Service CRUD class for api cache
 */
class ApiCacheService
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
     * Create api cache record
     * 
     * @param ApiCache $apiCache
     * 
     * @return ApiCache|bool
     */
    public function create(ApiCache $apiCache)
    {
        $data = json_encode($apiCache->getData());

        try {
            $this->con->executeQuery(
                'INSERT INTO `api_cache` (`session_key`, `service`, `call`, `data`)
                VALUES (?, ?, ?, ?)',
                [
                    $apiCache->getSessionKey(),
                    $apiCache->getService(),
                    $apiCache->getCall(),
                    $data
                ]
            );
            $id = (int) $this->con->lastInsertId();
            $apiCache->setId($id);
        } catch (Exception $e) {
            return false;
        }
        return $apiCache;
    }

    /**
     * Get all by session key, service & call
     * 
     * @param string $sessionKey
     * @param string $service
     * @param string $call
     * 
     * @return ApiCache|false
     */
    public function readBySessionKeyServiceCall(string $sessionKey, string $service, string $call)
    {
        $result = $this->con->fetchAssoc(
            'SELECT `id`, `session_key`, `service`, `call`, `data`
            FROM `api_cache`
            WHERE `session_key` = ?
                AND `service` = ?
                AND `call` = ?',
            [
                $sessionKey,
                $service,
                $call
            ]
        );

        if ($result) {
            $id = (int) $result['id'];
            $sessionKey = $result['session_key'];
            $service = $result['service'];
            $call = $result['call'];
            $data = json_decode($result['data'], true);

            $apiCache = new ApiCache();
            $apiCache->setId($id)
                ->setSessionKey($sessionKey)
                ->setService($service)
                ->setCall($call)
                ->setData($data);

            return $apiCache;
        }

        return false;
    }

    /**
     * Update api cache record
     * 
     * @param ApiCache $apiCache
     * 
     * @return ApiCache|bool
     */
    public function update(ApiCache $apiCache)
    {
        $data = json_encode($apiCache->getData());

        try {
            $this->con->executeQuery(
                'UPDATE
                    `api_cache`
                SET
                    `session_key` = ?,
                    `service` = ?,
                    `call` = ?,
                    `data` = ?
                WHERE
                    id = ?',
                [
                    $apiCache->getSessionKey(),
                    $apiCache->getService(),
                    $apiCache->getCall(),
                    $data,
                    $apiCache->getId()
                ]
            );
            $id = (int) $this->con->lastInsertId();
            $apiCache->setId($id);
        } catch (Exception $e) {
            return false;
        }
        return $apiCache;
    }

    /**
     * Delete api cache record by session key & service call
     * 
     * @param ApiCache $apiCache
     * 
     * @return bool
     */
    public function deleteBySessionKeyServiceCall(ApiCache $apiCache): bool
    {
        try {
            $this->con->executeQuery(
                'DELETE FROM `api_cache` WHERE `session_key` = ? AND `service` = ? AND `call` = ?',
                [
                   $apiCache->getSessionKey(),
                   $apiCache->getService(),
                   $apiCache->getCall()
                ]
            );
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
