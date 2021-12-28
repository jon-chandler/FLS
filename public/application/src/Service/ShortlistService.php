<?php

declare(strict_types = 1);

namespace Application\Service;

use Application\Model\ApiCache;
use Application\Model\Shortlist;
use Concrete\Core\Database\Connection\Connection;
use DateTime;

/**
 * Service class for shortlisted vehicle mobility type & sub type combo
 */
class ShortlistService
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
     * Create shortlist record
     * 
     * @param Shortlist $shortlist
     * 
     * @return Shortlist|bool
     */
    public function create(Shortlist $shortlist)
    {
        try {
            $savedDate = $shortlist->getSavedDate()->format('Y-m-d H:i:s');
            $answers = json_encode($shortlist->getAnswers());
            $vehicleTempData = ($shortlist->getVehicleTempData() !== null) ? json_encode($shortlist->getVehicleTempData()) : null;
            $apiCacheId = ($shortlist->getApiCache() === null) ? null : $shortlist->getApiCache()->getId();
            $this->con->executeQuery(
                'INSERT INTO shortlist (
                    vehicle_id,
                    user_id,
                    saved_date,
                    mobility_choice,
                    mobility_choice_type,
                    answers,
                    vrm,
                    api_cache_id,
                    vehicle_temp_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $shortlist->getVehicleId(),
                    $shortlist->getUserId(),
                    $savedDate,
                    $shortlist->getMobilityChoice(),
                    $shortlist->getMobilityChoiceType(),
                    $answers,
                    $shortlist->getVrm(),
                    $apiCacheId,
                    $vehicleTempData
                ]
            );
            $id = (int) $this->con->lastInsertId();
            $shortlist->setId($id);
        } catch (Exception $e) {
            return false;
        }
        return $shortlist;
    }

    /**
     * Get record by id
     * 
     * @param int $id
     * 
     * @return Shortlist|bool
     */
    public function readById(int $id)
    {
        $result = $this->con->fetchAssoc('SELECT * FROM shortlist WHERE id = ?', [$id]);

        if ($result) {
            $id = (int) $result['id'];
            $vehicleId = (is_numeric($result['vehicle_id'])) ? (int) $result['vehicle_id'] : null;
            $userId = (int) $result['user_id'];
            $savedDate = new DateTime($result['saved_date']);
            $mobilityChoice = $result['mobility_choice'];
            $mobilityChoiceType = $result['mobility_choice_type'];
            $answers = json_decode($result['answers'], true);
            $vehicleTempData = ($result['vehicle_temp_data'] !== null) ? json_decode($result['vehicle_temp_data'], true) : null;
            $vrm = $result['vrm'];

            $shortlist = new Shortlist();
            $shortlist->setId($id)
                ->setVehicleId($vehicleId)
                ->setUserId($userId)
                ->setSavedDate($savedDate)
                ->setMobilityChoice($mobilityChoice)
                ->setMobilityChoiceType($mobilityChoiceType)
                ->setAnswers($answers)
                ->setVehicleTempData($vehicleTempData)
                ->setVrm($vrm);

            return $shortlist;
        }

        return false;
    }

    /**
     * Get by id & join by api cache
     * 
     * @param int $id
     * 
     * @return Shortlist|bool
     */
    public function readByIdJoinApiCache(int $id)
    {
        $result = $this->con->fetchAssoc('SELECT api_cache.*, shortlist.* FROM shortlist
        LEFT JOIN api_cache ON api_cache.id = shortlist.api_cache_id
        WHERE shortlist.id = ?', [$id]);

        if ($result) {
            $id = (int) $result['id'];
            $vehicleId = (is_numeric($result['vehicle_id'])) ? (int) $result['vehicle_id'] : null;
            $userId = (int) $result['user_id'];
            $savedDate = new DateTime($result['saved_date']);
            $mobilityChoice = $result['mobility_choice'];
            $mobilityChoiceType = $result['mobility_choice_type'];
            $answers = json_decode($result['answers'], true);
            $vehicleTempData = ($result['vehicle_temp_data'] !== null) ? json_decode($result['vehicle_temp_data'], true) : null;
            $vrm = $result['vrm'];
            $apiCache = null;

            if ($result['api_cache_id']) {
                $apiCacheId = (int) $result['api_cache_id'];
                $sessionKey = $result['session_key'];
                $service = $result['service'];
                $call = $result['call'];
                $data = json_decode($result['data'], true);

                $apiCache = new ApiCache();
                $apiCache->setId($apiCacheId)
                    ->setSessionKey($sessionKey)
                    ->setService($service)
                    ->setCall($call)
                    ->setData($data);
            }

            $shortlist = new Shortlist();
            $shortlist->setId($id)
                ->setVehicleId($vehicleId)
                ->setUserId($userId)
                ->setSavedDate($savedDate)
                ->setMobilityChoice($mobilityChoice)
                ->setMobilityChoiceType($mobilityChoiceType)
                ->setAnswers($answers)
                ->setVehicleTempData($vehicleTempData)
                ->setVrm($vrm)
                ->setApiCache($apiCache);

            return $shortlist;
        }

        return false;
    }

    /**
     * Get records by user id
     * 
     * @param int $userId
     * 
     * @return array
     */
    public function readByUserId(int $userId): array
    {
        $shortlists = [];
        $results = $this->con->fetchAssoc('SELECT * FROM shortlist WHERE user_id = ?', [$userId]);

        if ($results) {
            foreach ($results as $result) {
                $id = (int) $result['id'];
                $vehicleId = (is_numeric($result['vehicle_id'])) ? (int) $result['vehicle_id'] : null;
                $userId = (int) $result['user_id'];
                $savedDate = new DateTime($result['saved_date']);
                $mobilityChoice = $result['mobility_choice'];
                $mobilityChoiceType = $result['mobility_choice_type'];
                $answers = json_decode($result['answers'], true);
                $vehicleTempData = ($result['vehicle_temp_data'] !== null) ? json_decode($result['vehicle_temp_data'], true) : null;
                $vrm = $result['vrm'];

                $shortlist = new Shortlist();
                $shortlist->setId($id)
                    ->setVehicleId($vehicleId)
                    ->setUserId($userId)
                    ->setSavedDate($savedDate)
                    ->setMobilityChoice($mobilityChoice)
                    ->setMobilityChoiceType($mobilityChoiceType)
                    ->setAnswers($answers)
                    ->setVehicleTempData($vehicleTempData)
                    ->setVrm($vrm);

                $shortlists[] = $shortlist;
            }
        }

        return $shortlists;
    }

    /**
     * Get records by uder id & vehicle id
     * 
     * @param int $vehicleId
     * @param int $userId
     * 
     * @return array
     */
    public function readByUserIdAndVehicleId(int $vehicleId, int $userId): array
    {
        $shortlists = [];
        $results = $this->con->fetchAssoc('SELECT * FROM shortlist WHERE user_id = ? AND vehicle_id = ?', [$userId, $vehicleId]);

        if ($results) {
            foreach ($results as $result) {
                $id = (int) $result['id'];
                $vehicleId = (int) $result['vehicle_id'];
                $userId = (int) $result['user_id'];
                $savedDate = new DateTime($result['saved_date']);
                $mobilityChoice = $result['mobility_choice'];
                $mobilityChoiceType = $result['mobility_choice_type'];
                $answers = json_decode($result['answers'], true);
                $vehicleTempData = ($result['vehicle_temp_data'] !== null) ? json_decode($result['vehicle_temp_data'], true) : null;
                $vrm = $result['vrm'];

                $shortlist = new Shortlist();
                $shortlist->setId($id)
                    ->setVehicleId($vehicleId)
                    ->setUserId($userId)
                    ->setSavedDate($savedDate)
                    ->setMobilityChoice($mobilityChoice)
                    ->setMobilityChoiceType($mobilityChoiceType)
                    ->setAnswers($answers)
                    ->setVehicleTempData($vehicleTempData)
                    ->setVrm($vrm);

                $shortlists[] = $shortlist;
            }
        }

        return $shortlists;
    }

    /**
     * Get record by user id & vrm
     * 
     * @param int $userId
     * @param int $vrm
     * 
     * @return Shortlist|bool
     */
    public function readByUserIdAndVrm(int $userId, string $vrm)
    {
        $result = $this->con->fetchAssoc('SELECT * FROM shortlist WHERE user_id = ? AND vrm = ?', [$userId, $vrm]);

        if ($result) {
            $id = (int) $result['id'];
            $userId = (int) $result['user_id'];
            $savedDate = new DateTime($result['saved_date']);
            $mobilityChoice = $result['mobility_choice'];
            $mobilityChoiceType = $result['mobility_choice_type'];
            $answers = json_decode($result['answers'], true);
            $vehicleTempData = ($result['vehicle_temp_data'] !== null) ? json_decode($result['vehicle_temp_data'], true) : null;
            $vrm = $result['vrm'];

            $shortlist = new Shortlist();
            $shortlist->setId($id)
                ->setUserId($userId)
                ->setSavedDate($savedDate)
                ->setMobilityChoice($mobilityChoice)
                ->setMobilityChoiceType($mobilityChoiceType)
                ->setAnswers($answers)
                ->setVehicleTempData($vehicleTempData)
                ->setVrm($vrm);

            return $shortlist;
        }

        return false;
    }

    /**
     * Get record by vehicle id, user id, mobility type & mobility sub type
     * 
     * @param int $vehicleId
     * @param int $userId
     * @param string $mobilityChoice
     * @param string $mobilityChoiceType
     * 
     * @return Shortlist|bool
     */
    public function readByUserVehicleMobilityChoiceMobilityChoiceType(int $vehicleId, int $userId, string $mobilityChoice, string $mobilityChoiceType)
    {
        $query = 'SELECT * FROM shortlist WHERE user_id = ? AND vehicle_id = ? AND mobility_choice = ? AND mobility_choice_type = ?';
        $bindings = [
            $userId,
            $vehicleId,
            $mobilityChoice,
            $mobilityChoiceType
        ];
        $result = $this->con->fetchAssoc($query, $bindings);

        if ($result) {
            $id = (int) $result['id'];
            $vehicleId = (int) $result['vehicle_id'];
            $userId = (int) $result['user_id'];
            $savedDate = new DateTime($result['saved_date']);
            $mobilityChoice = $result['mobility_choice'];
            $mobilityChoiceType = $result['mobility_choice_type'];
            $answers = json_decode($result['answers'], true);
            $vehicleTempData = ($result['vehicle_temp_data'] !== null) ? json_decode($result['vehicle_temp_data'], true) : null;
            $vrm = $result['vrm'];

            $shortlist = new Shortlist();
            $shortlist->setId($id)
                ->setVehicleId($vehicleId)
                ->setUserId($userId)
                ->setSavedDate($savedDate)
                ->setMobilityChoice($mobilityChoice)
                ->setMobilityChoiceType($mobilityChoiceType)
                ->setAnswers($answers)
                ->setVehicleTempData($vehicleTempData)
                ->setVrm($vrm);
            
            return $shortlist;
        }

        return false;
    }

    /**
     * Delete records by id & user id
     * 
     * @param int $id
     * @param int $userId
     * 
     * @return bool
     */
    public function deleteByIdAndUserId(int $id, int $userId)
    {
        return $this->con->executeQuery('DELETE FROM shortlist WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    /**
     * Delete records by user id
     * 
     * @param int $userId
     * 
     * @return bool
     */
    public function deleteAllForUser(int $userId)
    {
        return $this->con->executeQuery('DELETE FROM shortlist WHERE user_id = ?', [$userId]);
    }
}
