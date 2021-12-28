<?php

declare(strict_types = 1);

namespace Application\Service;

use Application\Model\JourneyUserSession;
use Concrete\Core\Database\Connection\Connection;
use DateTime;
use Exception;

/**
 * Service class for a saved user journey
 */
class JourneyUserSessionService
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
     * Create a journey user session record
     * 
     * @param JourneyUserSession $journeyUserSession
     * 
     * @return JourneyUserSession|bool
     * 
     * @throws Exception
     */
    public function create(JourneyUserSession $journeyUserSession)
    {
        try {
            $created = $journeyUserSession->getCreated()->format('Y-m-d H:i:s');
            $lastUpdated = $journeyUserSession->getLastUpdated()->format('Y-m-d H:i:s');
            $saved = ($journeyUserSession->getSaved()) ? 1 : 0;
            $progress = implode(',', $journeyUserSession->getProgress());
            $description = json_encode($journeyUserSession->getDescription());

            $this->con->executeQuery(
                'INSERT INTO `JourneyUserSession` (`Created`, `SessionKey`, `SessionStartURL`, `UserID`, `Saved`, `LastUpdated`, `Label`, `Progress`, `Description`, `JourneyType`, `JourneyGroup`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $created,
                    $journeyUserSession->getSessionKey(),
                    $journeyUserSession->getSessionStartUrl(),
                    $journeyUserSession->getUserId(),
                    $saved,
                    $lastUpdated,
                    $journeyUserSession->getLabel(),
                    $progress,
                    $description,
                    $journeyUserSession->getJourneyType(),
                    $journeyUserSession->getJourneyGroup()
                ]
            );
            $id = (int) $this->con->lastInsertId();
            $journeyUserSession->setId($id);
        } catch (Exception $e) {
            return false;
        }
        return $journeyUserSession;
    }

    /**
     * Get records by user Id
     * 
     * @param int $userId 
     * @param array|null $options
     * 
     * @return array|false
     */
    public function readByUserId(int $userId, array $options = null)
    {
        $journeyUserSessions = [];
        $query = 'SELECT * FROM `JourneyUserSession` WHERE `UserID` = ?';

        if (isset($options['order'])) {
            $order = $options['order'];
            $orderStr = '';

            if (array_key_exists('column', $order)) {
                $orderStr .= ' ORDER BY ' . $order['column'];

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

        $results = $this->con->fetchAll($query, [$userId]);

        if ($results) {
            foreach ($results as $result) {
                $id = (int) $result['ID'];
                $created = $result['Created'];
                $created = new DateTime($created);
                $sessionKey = $result['SessionKey'];
                $sessionStartUrl = $result['SessionStartURL'];
                $userId = (int) $result['UserID'];
                $saved = ((int) $result['Saved'] === 1) ? true : false;
                $lastUpdated = $result['LastUpdated'];
                $lastUpdated = new DateTime($lastUpdated);
                $label = $result['Label'];
                $progress = explode(',', $result['Progress']);
                $description = json_decode($result['Description'], true);
                $journeyType = $result['JourneyType'];
                $journeyGroup = $result['JourneyGroup'];

                $journeyUserSession = new JourneyUserSession();
                $journeyUserSession->setId($id)
                    ->setCreated($created)
                    ->setSessionKey($sessionKey)
                    ->setSessionStartUrl($sessionStartUrl)
                    ->setUserId($userId)
                    ->setSaved($saved)
                    ->setLastUpdated($lastUpdated)
                    ->setLabel($label)
                    ->setProgress($progress)
                    ->setDescription($description)
                    ->setJourneyType($journeyType)
                    ->setJourneyGroup($journeyGroup);

                $journeyUserSessions[] = $journeyUserSession;
            }

            return $journeyUserSessions;
        }

        return false;
    }

    /**
     * Get records by user Id with status of saved
     * 
     * @param int $userId
     * @param bool $saved
     * @param array|null $options
     * 
     * @return array|false
     */
    public function readByUserIdAndSaved(int $userId, bool $saved, array $options = null)
    {
        $journeyUserSessions = [];
        $query = 'SELECT * FROM `JourneyUserSession` WHERE `UserID` = ? AND Saved = ?';

        if (isset($options['order'])) {
            $order = $options['order'];
            $orderStr = '';

            if (array_key_exists('column', $order)) {
                $orderStr .= ' ORDER BY ' . $order['column'];

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

        $results = $this->con->fetchAll($query, [$userId, $saved]);

        if ($results) {
            foreach ($results as $result) {
                $id = (int) $result['ID'];
                $created = $result['Created'];
                $created = new DateTime($created);
                $sessionKey = $result['SessionKey'];
                $sessionStartUrl = $result['SessionStartURL'];
                $userId = (int) $result['UserID'];
                $saved = ((int) $result['Saved'] === 1) ? true : false;
                $lastUpdated = $result['LastUpdated'];
                $lastUpdated = new DateTime($lastUpdated);
                $label = $result['Label'];
                $progress = explode(',', $result['Progress']);
                $description = json_decode($result['Description'], true);
                $journeyType = $result['JourneyType'];
                $journeyGroup = $result['JourneyGroup'];

                $journeyUserSession = new JourneyUserSession();
                $journeyUserSession->setId($id)
                    ->setCreated($created)
                    ->setSessionKey($sessionKey)
                    ->setSessionStartUrl($sessionStartUrl)
                    ->setUserId($userId)
                    ->setSaved($saved)
                    ->setLastUpdated($lastUpdated)
                    ->setLabel($label)
                    ->setProgress($progress)
                    ->setDescription($description)
                    ->setJourneyType($journeyType)
                    ->setJourneyGroup($journeyGroup);

                $journeyUserSessions[] = $journeyUserSession;
            }

            return $journeyUserSessions;
        }

        return false;
    }

    /**
     * Get record by Id & user Id
     * 
     * @param int $id
     * @param int $userId
     * 
     * @return JourneyUserSession|false
     */
    public function readByIdAndUserId(int $id, int $userId)
    {
        $result = $this->con->fetchAssoc(
            'SELECT * FROM `JourneyUserSession` WHERE `ID` = ? AND `UserID` = ?',
            [
                $id,
                $userId
            ]
        );

        if ($result) {
            $id = (int) $result['ID'];
            $created = $result['Created'];
            $created = new DateTime($created);
            $sessionKey = $result['SessionKey'];
            $sessionStartUrl = $result['SessionStartURL'];
            $userId = (int) $result['UserID'];
            $saved = ((int) $result['Saved'] === 1) ? true : false;
            $lastUpdated = $result['LastUpdated'];
            $lastUpdated = new DateTime($lastUpdated);
            $label = $result['Label'];
            $progress = explode(',', $result['Progress']);
            $description = json_decode($result['Description'], true);
            $journeyType = $result['JourneyType'];
            $journeyGroup = $result['JourneyGroup'];

            $journeyUserSession = new JourneyUserSession();
            $journeyUserSession->setId($id)
                ->setCreated($created)
                ->setSessionKey($sessionKey)
                ->setSessionStartUrl($sessionStartUrl)
                ->setUserId($userId)
                ->setSaved($saved)
                ->setLastUpdated($lastUpdated)
                ->setLabel($label)
                ->setProgress($progress)
                ->setDescription($description)
                ->setJourneyType($journeyType)
                ->setJourneyGroup($journeyGroup);

            return $journeyUserSession;
        }

        return false;
    }

    /**
     * Get record by user Id & session key
     * 
     * @param int $userId
     * @param string $sessionKey
     * 
     * @return JourneyUserSession|false
     */
    public function readByUserIdAndSessionKey(int $userId, string $sessionKey)
    {
        $result = $this->con->fetchAssoc(
            'SELECT * FROM `JourneyUserSession` WHERE `UserID` = ? AND `SessionKey` = ?',
            [
                $userId,
                $sessionKey
            ]
        );

        if ($result) {
            $id = (int) $result['ID'];
            $created = $result['Created'];
            $created = new DateTime($created);
            $sessionKey = $result['SessionKey'];
            $sessionStartUrl = $result['SessionStartURL'];
            $userId = (int) $result['UserID'];
            $saved = ((int) $result['Saved'] === 1) ? true : false;
            $lastUpdated = $result['LastUpdated'];
            $lastUpdated = new DateTime($lastUpdated);
            $label = $result['Label'];
            $progress = array_map(function ($progress) {
                return (int) $progress;
            }, explode(',', $result['Progress']));
            $description = json_decode($result['Description'], true);
            $journeyType = $result['JourneyType'];
            $journeyGroup = $result['JourneyGroup'];

            $journeyUserSession = new JourneyUserSession();
            $journeyUserSession->setId($id)
                ->setCreated($created)
                ->setSessionKey($sessionKey)
                ->setSessionStartUrl($sessionStartUrl)
                ->setUserId($userId)
                ->setSaved($saved)
                ->setLastUpdated($lastUpdated)
                ->setLabel($label)
                ->setProgress($progress)
                ->setDescription($description)
                ->setJourneyType($journeyType)
                ->setJourneyGroup($journeyGroup);

            return $journeyUserSession;
        }

        return false;
    }

    /**
     * Update record
     * 
     * @param JourneyUserSession $journeyUserSession
     * 
     * @return JourneyUserSession|false
     * 
     * @throws Exception
     */
    public function update(JourneyUserSession $journeyUserSession)
    {
        try {
            $created = $journeyUserSession->getCreated()->format('Y-m-d H:i:s');
            $lastUpdated = $journeyUserSession->getLastUpdated()->format('Y-m-d H:i:s');
            $saved = ($journeyUserSession->getSaved()) ? 1 : 0;
            $progress = implode(',', $journeyUserSession->getProgress());
            $description = json_encode($journeyUserSession->getDescription());

            $this->con->executeQuery(
                'UPDATE `JourneyUserSession`
                SET
                    `Created` = ?,
                    `SessionKey` = ?,
                    `SessionStartURL` = ?,
                    `UserID` = ?,
                    `Saved` = ?,
                    `LastUpdated` = ?,
                    `Label` = ?,
                    `Progress` = ?,
                    `Description` = ?,
                    `JourneyType` = ?,
                    `JourneyGroup` = ?
                WHERE ID = ?',
                [
                    $created,
                    $journeyUserSession->getSessionKey(),
                    $journeyUserSession->getSessionStartUrl(),
                    $journeyUserSession->getUserId(),
                    $saved,
                    $lastUpdated,
                    $journeyUserSession->getLabel(),
                    $progress,
                    $description,
                    $journeyUserSession->getJourneyType(),
                    $journeyUserSession->getJourneyGroup(),
                    $journeyUserSession->getId()
                ]
            );
        } catch (Exception $e) {
            return false;
        }
        return $journeyUserSession;
    }

    /**
     * Delete record
     * 
     * @param JourneyUserSession $journeyUserSession
     * 
     * @return bool
     * 
     * @throws Exception
     */
    public function delete(JourneyUserSession $journeyUserSession)
    {
        try {
            $this->con->executeQuery(
                'DELETE FROM `JourneyUserSession` WHERE ID = ?',
                [$journeyUserSession->getId()]
            );
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Delete record by id & user id
     * 
     * @param int $id
     * @param int $userId
     * 
     * @return bool
     * 
     * @throws Exception
     */
    public function deleteByIdAndUserId(int $id, int $userId)
    {
        try {
            $this->con->executeQuery(
                'DELETE FROM `JourneyUserSession` WHERE ID = ? AND UserId = ?',
                [
                    $id,
                    $userId
                ]
            );
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
